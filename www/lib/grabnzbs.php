<?php
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/page.php");
require_once(WWW_DIR."lib/category.php");
require_once(WWW_DIR."lib/namecleaning.php");
require_once(WWW_DIR."lib/site.php");


class Import
{
	function categorize()
	{
		$db = new DB();
		$cat = new Category();
		$relres = $db->query("SELECT name, id, groupid FROM releases WHERE categoryid = 7010 AND relnamestatus = 0");
		foreach ($relres as $relrow)
		{
			$catID = $cat->determineCategory($relrow['name'], $relrow['groupid']);
			if ($relrow['groupid'] != 7010)
				$db->queryExec(sprintf("UPDATE releases SET categoryid = %d WHERE id = %d", $catID, $relrow['id']));
		}
	}

	public function GrabNZBs($hash='')
	{
		$db = new DB();
		$nntp = new Nntp();
		$nzb = array();
		$s = new Sites();
		$site = $s->get();
		$site->grabnzbs == "2" ? $nntp->doConnect_A() : $nntp->doConnect();

		if ($hash == '')
		{
			$hashes = $db->query("SELECT collectionhash FROM nzbs GROUP BY collectionhash, totalparts HAVING COUNT(*) >= totalparts");
			if (count($hashes) > 0)
			{
				foreach ($hashes as $hash)
				{
					$rel = $db->query(sprintf("SELECT * FROM nzbs WHERE collectionhash = %s ORDER BY partnumber", $db->escapeString($hash['collectionhash'])));
					$arr = '';
					foreach ($rel as $nzb)
					{
   						$arr[] = $nzb['message_id'];
					}
				}
			}
			else
				exit("No NZBs to grab\n");
		}
		else
		{
			$rel = $db->query(sprintf("SELECT * FROM nzbs WHERE collectionhash = %s ORDER BY partnumber", $db->escapestring($hash)));
			$arr = '';
			foreach ($rel as $nzb)
			{
				$arr[] = $nzb['message_id'];
			}
		}
		if($nzb && array_key_exists('groupname', $nzb))
		{
			$site->grabnzbs == "2" ? $nntp->doConnect_A() : $nntp->doConnect();
			if (sizeof($arr) > 10)
				echo "\nGetting ".sizeof($arr)." articles for ".$hash."\n";
			$article = $nntp->getArticles($nzb['groupname'], $arr);
			if ($article === false || PEAR::isError($article))
			{
				$nntp->doQuit();
				$site->grabnzbs == "2" ? $nntp->doConnect_A() : $nntp->doConnect();
				$article = $nntp->getArticles($nzb['groupname'], $arr);
				if ($article === false || PEAR::isError($article))
				{
					$nntp->doQuit();
					$article = false;
				}
			}
			$nntp->doQuit();
			// If article downloaded, to to import, else delete from nzbs
			if($article !== false)
				$this->processGrabNZBs($article, $hash);
			else
			{
				$db->queryExec(sprintf("DELETE FROM nzbs WHERE collectionhash = %s", $db->escapeString($hash)));
				echo "-";
				return;
			}
		}
		else
			return;
	}


	function processGrabNZBs($article, $hash)
	{
		if(!$article)
			return;
		$db = new DB();
		$binaries = new Binaries();
		$page = new Page();
		$n = "\n";
		$s = new Sites();
		$site = $s->get();
		$nzbsplitlevel = $site->nzbsplitlevel;
		$nzbpath = $site->nzbpath;
		$version = $site->version;
		$crosspostt = (!empty($site->crossposttime)) ? $site->crossposttime : 2;
		$namecleaning = new nameCleaning();

		$groups = $db->query("SELECT id, name FROM groups");
		foreach ($groups as $group)
			$siteGroups[$group["name"]] = $group["id"];

		$importfailed = $isBlackListed = false;
		$xml = @simplexml_load_string($article);
		// If article is not a valid xml, delete from nzbs
		if (!$xml)
		{
			$db->queryExec(sprintf("DELETE FROM nzbs WHERE collectionhash = %s", $db->escapeString($hash)));
			echo "-";
			return;
		}
		else
		{
			$skipCheck = false;
			$i = $totalFiles = $totalsize = 0;
			$firstname = $postername = $postdate = array();

			foreach($xml->file as $file)
			{
				// File info.
				$groupID = -1;
				$name = (string)$file->attributes()->subject;
				$firstname[] = $name;
				$fromname = (string)$file->attributes()->poster;
				$postername[] = $fromname;
				$unixdate = (string)$file->attributes()->date;
				$totalFiles++;
				$date = date('Y-m-d H:i:s', (string)($file->attributes()->date));
				$postdate[] = $date;
				$partless = preg_replace('/(\(\d+\/\d+\))*$/', 'yEnc', $firstname['0']);
				$partless = preg_replace('/yEnc.*?$/', 'yEnc', $partless);
				$subject = utf8_encode(trim($partless));

				// Make a fake message object to use to check the blacklist.
				$msg = array('Subject' => $firstname['0'], 'From' => $fromname, 'Message-ID' => '');

				// If the release is in our DB already then don't bother importing it.
				if ($skipCheck !== true)
				{
					$usename = $db->escapeString($name);
					if ($db->dbSystem() == 'mysql')
						$dupeCheckSql = sprintf('SELECT name FROM releases WHERE name = %s AND fromname = %s AND postdate - INTERVAL %d HOUR <= %s AND postdate + INTERVAL %d HOUR > %s', $db->escapeString($firstname['0']),$db->escapeString($fromname), $crosspostt, $db->escapeString($date), $crosspostt, $db->escapeString($date));
					else if ($db->dbSystem() == 'pgsql')
						$dupeCheckSql = sprintf("SELECT name FROM releases WHERE name = %s AND fromname = %s AND postdate - INTERVAL '%d HOURS' <= %s AND postdate + INTERVAL '%d HOURS' > %s", $db->escapeString($firstname['0']),$db->escapeString($fromname), $crosspostt, $db->escapeString($date), $crosspostt, $db->escapeString($date));
					$res = $db->queryOneRow($dupeCheckSql);

					// Only check one binary per nzb, they should all be in the same release anyway.
					$skipCheck = true;

					// If the release is in the DB already then just skip this whole procedure.
					if ($res !== false)
					{
						flush();
						$importfailed = true;
						break;
					}
				}

				// Groups.
				$groupArr = array();
				foreach($file->groups->group as $group)
				{
					$group = (string)$group;
					if (array_key_exists($group, $siteGroups))
					{
						$groupName = $group;
						$groupID = $siteGroups[$group];
					}
					$groupArr[] = $group;

					if ($binaries->isBlacklisted($msg, $group))
						$isBlackListed = TRUE;
				}
				if ($groupID != -1 && !$isBlackListed)
				{
					if (count($file->segments->segment) > 0)
					{
						foreach($file->segments->segment as $segment)
						{
							$size = $segment->attributes()->bytes;
							$totalsize = $totalsize+$size;
						}
					}
				}
				else
				{
					$importfailed = true;
					break;
				}
			}

			if (!$importfailed)
			{
				$relguid = sha1(uniqid(true).mt_rand());
				$nzb = new NZB();
				$propername = false;
				$cleanerName = $namecleaning->releaseCleaner($subject, $groupName);
				/*$ncarr = $namecleaner->collectionsCleaner($subject, $groupName);
				$cleanerName = $ncarr['subject'];
				$category = $ncarr['cat'];
				$relstat = $ncar['rstatus'];*/
				if (!is_array($cleanerName))
					$cleanName = $cleanerName;
				else
				{
					$cleanName = $cleanerName['cleansubject'];
					$propername = $cleanerName['properlynamed'];
				}
				// This only creates a release if there is not a match on name, poster, size and group
				$ckmsg = $db->queryOneRow(sprintf('SELECT id FROM releases WHERE name= %s AND fromname = %s AND size = %d AND groupid = %d', $db->escapeString($subject), $db->escapeString($postername['0']), $totalsize, $groupID));
				// If a release exists, delete the nzb/collection/binaries/parts
				if (isset($ckmsg['id']))
				{
					if ($db->dbSystem() == "mysql")
						$db->queryExec(sprintf("DELETE collections, binaries, parts FROM collections LEFT JOIN binaries ON collections.id = binaries.collectionid LEFT JOIN parts ON binaries.id = parts.binaryid WHERE collections.collectionhash = %s", $db->escapeString($hash)));
					elseif ($db->dbSystem() == "pgsql")
					{
						$idr = $db->query(sprintf("SELECT id FROM collections WHERE collectionhash = %s", $db->escapeString($hash)));
						if (count($idr) > 0)
						{
							foreach ($idr as $id)
							{
								$reccount = $db->queryExec(sprintf("DELETE FROM parts WHERE EXISTS (SELECT id FROM binaries WHERE binaries.id = parts.binaryid AND binaries.collectionid = %d)", $id["id"]));
								$reccount += $db->queryExec(sprintf("DELETE FROM binaries WHERE collectionid = %d", $id["id"]));
							}
							$reccount += $db->queryExec("DELETE FROM collections WHERE collectionshash = ", $db->escapeString($hash));
						}
					}
					$db->queryExec(sprintf("DELETE from nzbs where collectionhash = %s", $db->escapeString($hash)));
					echo "!";
					return;
				}
				if ($propername === true)
					$relid = $db->queryInsert(sprintf("INSERT INTO releases (name, searchname, totalpart, groupid, adddate, guid, rageid, postdate, fromname, size, passwordstatus, haspreview, categoryid, nfostatus, nzbstatus, relnamestatus) values (%s, %s, %d, %d, NOW(), %s, -1, %s, %s, %d, %d, -1, 7010, -1, 1, 6)", $db->escapeString($subject), $db->escapeString($cleanName), $totalFiles, $groupID, $db->escapeString($relguid), $db->escapeString($postdate['0']), $db->escapeString($postername['0']), $totalsize, ($page->site->checkpasswordedrar == "1" ? -1 : 0)));
				else
					$relid = $db->queryInsert(sprintf("INSERT INTO releases (name, searchname, totalpart, groupid, adddate, guid, rageid, postdate, fromname, size, passwordstatus, haspreview, categoryid, nfostatus, nzbstatus, relnamestatus) values (%s, %s, %d, %d, NOW(), %s, -1, %s, %s, %d, %d, -1, 7010, -1, 1, 6)", $db->escapeString($subject), $db->escapeString($cleanName), $totalFiles, $groupID, $db->escapeString($relguid), $db->escapeString($postdate['0']), $db->escapeString($postername['0']), $totalsize, ($page->site->checkpasswordedrar == "1" ? -1 : 0)));
				if (count($relid) > 0)
				{
					$path=$nzb->getNZBPath($relguid, $nzbpath, true, $nzbsplitlevel);
					$fp = gzopen($path, 'w6');
					if ($fp)
					{
						gzwrite($fp, str_replace("</nzb>", "  <!-- generated by nZEDb ".$version." -->\n</nzb>", $article));
						gzclose($fp);
						if (file_exists($path))
						{
							chmod($path, 0777);
							$db->queryExec(sprintf("UPDATE releases SET nzbstatus = 1 WHERE id = %d", $relid));
							if ($db->dbSystem() == "mysql")
								$db->queryExec(sprintf("DELETE collections, binaries, parts FROM collections LEFT JOIN binaries ON collections.id = binaries.collectionid LEFT JOIN parts ON binaries.id = parts.binaryid WHERE collections.collectionhash = %s", $db->escapeString($hash)));
							elseif ($db->dbSystem() == "pgsql")
							{
								$idr = $db->query(sprintf("SELECT id FROM collections WHERE collectionhash = %s", $db->escapeString($hash)));
								if (count($idr) > 0)
								{
									foreach ($idr as $id)
									{
										$reccount = $db->queryExec(sprintf("DELETE FROM parts WHERE EXISTS (SELECT id FROM binaries WHERE binaries.id = parts.binaryid AND binaries.collectionid = %d)", $id["id"]));
										$reccount += $db->queryExec(sprintf("DELETE FROM binaries WHERE collectionid = %d", $id["id"]));
									}
									$reccount += $db->queryExec("DELETE FROM collections WHERE collectionshash = ", $db->escapeString($hash));
								}
							}
							$db->queryExec(sprintf("DELETE from nzbs where collectionhash = %s", $db->escapeString($hash)));
							$this->categorize();
							echo "+";
						}
						else
						{
							$db->queryExec(sprintf("DELETE FROM releases WHERE id = %d", $relid));
							$importfailed = true;
							echo "-";
						}
					}
				}
			}
		}
	}
}
