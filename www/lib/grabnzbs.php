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
		$relres = $db->queryDirect("SELECT name, ID, groupID from releases where categoryID = 7010 and relnamestatus = 0");
		while ($relrow = $db->fetchAssoc($relres))
		{
			$catID = $cat->determineCategory($relrow['name'], $relrow['groupID']);
			$db->queryDirect(sprintf("UPDATE releases set categoryID = %d, relnamestatus = 1 where ID = %d", $catID, $relrow['ID']));
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
			if ($hashes = $db->queryDirect("select collectionhash from nzbs group by collectionhash, totalparts having count(*) >= totalparts"))
			{
				if (mysqli_num_rows($hashes) > 0)
				{
					while ($hash = $db->fetchAssoc($hashes))
					{
						$rel = $db->queryDirect(sprintf("select * from nzbs where collectionhash = '%s' order by partnumber", $hash['collectionhash']));
						$arr = '';
						foreach ($rel as $nzb)
   						{
	   						$arr[] = $nzb['message_id'];
						}
					}
				}
				else
				{
					echo "No NZBs to grab\n";
					exit();
				}
			}
		}
		else
		{
			$rel = $db->queryDirect(sprintf("select * from nzbs where collectionhash = '%s' order by partnumber", $hash));
			$arr = '';
			foreach ($rel as $nzb)
			{
				$arr[] = $nzb['message_id'];
			}
		}
		if($nzb && array_key_exists('groupname', $nzb))
		{
			$site->grabnzbs == "2" ? $nntp->doConnect_A() : $nntp->doConnect();
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
			if($article !== false)
				$this->processGrabNZBs($article, $hash);
			else
				$db->queryDirect(sprintf("DELETE collections, binaries, parts
						FROM collections INNER JOIN binaries ON collections.ID = binaries.collectionID INNER JOIN parts on binaries.ID = parts.binaryID
						WHERE collections.collectionhash = %s", $db->escapeString($hash)));
		}
		else
			return;
	}


	function processGrabNZBs($article, $hash)
	{
		if(!$article)
			return;
		//echo "Downloaded article for $hash\n";
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

		$groups = $db->query("SELECT ID, name FROM groups");
		foreach ($groups as $group)
			$siteGroups[$group["name"]] = $group["ID"];

		$isBlackListed = FALSE;
		$importfailed = false;
		$xml = @simplexml_load_string($article);
		if (!$xml)
		{
			//echo "*";
			$db->queryDirect(sprintf("DELETE from nzbs where collectionhash = %s", $db->escapeString($hash)));
		}
		else
		{
			//echo ",";
			$skipCheck = false;
			$i=0;
			$firstname = array();
			$postername = array();
			$postdate = array();
			$totalFiles = 0;
			$totalsize = 0;

			foreach($xml->file as $file)
			{
				//file info
				$groupID = -1;
				$name = (string)$file->attributes()->subject;
				$firstname[] = $name;
				$fromname = (string)$file->attributes()->poster;
				$postername[] = $fromname;
				$unixdate = (string)$file->attributes()->date;
				$totalFiles++;
				$date = date("Y-m-d H:i:s", (string)($file->attributes()->date));
				$postdate[] = $date;
				$partless = preg_replace('/yEnc.*?$/i', 'yEnc', $firstname['0']);
				$subject = utf8_encode(trim($partless));
				$namecleaning = new nameCleaning();


				// make a fake message object to use to check the blacklist
				$msg = array("Subject" => $firstname['0'], "From" => $fromname, "Message-ID" => "");

				// if the release is in our DB already then don't bother importing it
				if ($skipCheck !== true)
				{
					$usename = $db->escapeString($name);
					$dupeCheckSql = sprintf("SELECT name FROM releases WHERE name = %s AND postdate - interval %d hour <= %s AND postdate + interval %d hour > %s",
						$db->escapeString($firstname['0']), $crosspostt, $db->escapeString($date), $crosspostt, $db->escapeString($date));
					$res = $db->queryOneRow($dupeCheckSql);

					// only check one binary per nzb, they should all be in the same release anyway
					$skipCheck = true;

					// if the release is in the DB already then just skip this whole procedure
					if ($res !== false)
					{
						flush();
						$importfailed = true;
						break;
					}
				}

				//groups
				$groupArr = array();
				foreach($file->groups->group as $group)
				{
					$group = (string)$group;
					if (array_key_exists($group, $siteGroups))
					{
						$groupID = $siteGroups[$group];
					}
					$groupArr[] = $group;

					if ($binaries->isBlacklisted($msg, $group))
					{
						$isBlackListed = TRUE;
					}
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
				$relguid = sha1(uniqid().mt_rand());
				$nzb = new NZB();
				$cleanerName = $namecleaning->releaseCleaner($subject, $groupID);

				if($relID = $db->queryInsert(sprintf("INSERT IGNORE INTO releases (name, searchname, totalpart, groupID, adddate, guid, rageID, postdate, fromname, size, passwordstatus, haspreview, categoryID, nfostatus, nzbstatus) values (%s, %s, %d, %d, now(), %s, -1, %s, %s, %s, %d, -1, 7010, -1, 1)", $db->escapeString($subject), $db->escapeString($cleanerName), $totalFiles, $groupID, $db->escapeString($relguid), $db->escapeString($postdate['0']), $db->escapeString($postername['0']), $db->escapeString($totalsize), ($page->site->checkpasswordedrar == "1" ? -1 : 0))));
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
							$db->queryDirect(sprintf("UPDATE releases SET nzbstatus = 1 WHERE ID = %d", $relID));
							$db->queryDirect(sprintf("DELETE collections, binaries, parts
								FROM collections LEFT JOIN binaries ON collections.ID = binaries.collectionID LEFT JOIN parts on binaries.ID = parts.binaryID
								WHERE collections.collectionhash = %s", $db->escapeString($hash)));
							$db->queryDirect(sprintf("DELETE from nzbs where collectionhash = %s", $db->escapeString($hash)));
							$this->categorize();
							echo "+";
						}
						else
						{
							$db->queryDirect(sprintf("delete from releases where ID = %d", $relID));
							$importfailed = true;
							echo "-";
						}
					}
				}
			}
			else
			{
				$db->queryDirect(sprintf("DELETE from nzbs where collectionhash = %s", $db->escapeString($hash)));
				//echo "!";
			}
		}
	}
}
