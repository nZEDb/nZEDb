<?php
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/page.php");
require_once(WWW_DIR."lib/category.php");
require_once(WWW_DIR."lib/namecleaning.php");
require_once(WWW_DIR."lib/site.php");
require_once(WWW_DIR."lib/groups.php");
require_once(WWW_DIR."lib/releases.php");

class Import
{
	function __construct()
	{
		$this->db = new DB();
		$s = new Sites();
		$this->site = $s->get();
		$this->tablepergroup = (isset($this->site->tablepergroup)) ? $this->site->tablepergroup : 0;
		$this->replacenzbs = (isset($this->site->replacenzbs)) ? $this->site->replacenzbs : 0;
	}

	function categorize()
	{
		$cat = new Category();
		$relres = $this->db->prepare('SELECT name, id, groupid FROM releases WHERE categoryid = 7010 AND relnamestatus = 0');
		$relres->execute();
		$tot = $relres->rowCount();
		if ($tot > 0)
		{
			foreach ($relres as $relrow)
			{
				$catID = $cat->determineCategory($relrow['name'], $relrow['groupid']);
				if ($relrow['groupid'] != 7010)
					$this->db->queryExec(sprintf('UPDATE releases SET categoryid = %d WHERE id = %d', $catID, $relrow['id']));
			}
		}
	}

	public function GrabNZBs($hash='')
	{
		$nntp = new Nntp();
		$nzb = array();
		$this->site->grabnzbs == '2' ? $nntp->doConnect_A() : $nntp->doConnect();

		if ($hash == '')
		{
			$hashes = $this->db->query('SELECT collectionhash FROM nzbs GROUP BY collectionhash, totalparts HAVING COUNT(*) >= totalparts');
			if (count($hashes) > 0)
			{
				foreach ($hashes as $hash)
				{
					$rel = $this->db->query(sprintf('SELECT * FROM nzbs WHERE collectionhash = %s ORDER BY partnumber', $this->db->escapeString($hash['collectionhash'])));
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
			$rel = $this->db->query(sprintf('SELECT * FROM nzbs WHERE collectionhash = %s ORDER BY partnumber', $this->db->escapestring($hash)));
			$arr = '';
			foreach ($rel as $nzb)
			{
				$arr[] = $nzb['message_id'];
			}
		}
		if($nzb && array_key_exists('groupname', $nzb))
		{
			$this->site->grabnzbs == '2' ? $nntp->doConnect_A() : $nntp->doConnect();
			if (sizeof($arr) > 10)
				echo "\nGetting ".sizeof($arr).' articles for '.$hash."\n";
			$article = $nntp->getArticles($nzb['groupname'], $arr);
			if ($article === false || PEAR::isError($article))
			{
				$nntp->doQuit();
				$this->site->grabnzbs == '2' ? $nntp->doConnect_A() : $nntp->doConnect();
				$article = $nntp->getArticles($nzb['groupname'], $arr);
				if ($article === false || PEAR::isError($article))
				{
					$nntp->doQuit();
					$article = false;
				}
			}
			$nntp->doQuit();

			// If article downloaded, import it, else delete from nzbs table
			if($article !== false)
			{
				$groups = new Groups();
				$realgroupid = $groups->getIDByName($nzb['groupname']);
				$this->processGrabNZBs($article, $hash, $realgroupid);
			}
			else
			{
				$this->db->queryExec(sprintf('DELETE FROM nzbs WHERE collectionhash = %s', $this->db->escapeString($hash)));
				echo 'f';
				return;
			}
		}
		else
			return;
	}


	function processGrabNZBs($article, $hash, $realgroupid)
	{
		if(!$article)
			return;
		$binaries = new Binaries();
		$page = new Page();
		$n = "\n";
		$nzbsplitlevel = $this->site->nzbsplitlevel;
		$nzbpath = $this->site->nzbpath;
		$version = $this->site->version;
		$namecleaning = new nameCleaning();

		$groups = $this->db->query('SELECT id, name FROM groups');
		foreach ($groups as $group)
			$siteGroups[$group['name']] = $group['id'];

		$importfailed = $isBlackListed = false;
		$xml = @simplexml_load_string($article);
		// If article is not a valid xml, delete from nzbs
		if (!$xml)
		{
			$this->db->queryExec(sprintf('DELETE FROM nzbs WHERE collectionhash = %s', $this->db->escapeString($hash)));
			echo '-';
			return;
		}
		else
		{
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
				$msg = array('Subject' => $subject, 'From' => $postername[0], 'Message-ID' => '');

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
                        $isBlackListed = true;
                }
                if ($groupID != -1 && !$isBlackListed)
                {
                    if (count($file->segments->segment) > 0)
                    {
                        foreach($file->segments->segment as $segment)
                        {
                            $totalsize += $segment->attributes()->bytes;
                        }
                    }
                }
                else
                {
                    $importfailed = true;
                    break;
                }
			}

			// To get accurate size to check for true duplicates, we need to process the entire nzb first
			if (!$importfailed)
			{
				$usename = $this->db->escapeString($name);
				$res = $this->db->prepare(sprintf("SELECT id, guid FROM releases WHERE name = %s AND fromname = %s AND groupid = %s AND size = %s", $this->db->escapeString($subject), $this->db->escapeString($fromname), $this->db->escapeString($realgroupid), $this->db->escapeString($totalsize)));
				$res->execute();
				if ($this->replacenzbs == 1)
				{
					$releases = new Releases();
					foreach ($res as $rel)
					{
						if (isset($rel['id']) && isset($rel['guid']))
							$releases->fastDelete($rel['id'], $rel['guid'], $this->site);
					}
				}
				else if ($res->rowCount() > 0 && $this->replacenzbs == 0)
				{
					flush();
					$importfailed = true;
					break;
				}
			}

			if (!$importfailed)
			{
				$relguid = sha1(uniqid('',true).mt_rand());
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
				// If a release exists, delete the nzb/collection/binaries/parts
				if ($propername === true)
					$relid = $this->db->queryInsert(sprintf('INSERT INTO releases (name, searchname, totalpart, groupid, adddate, guid, rageid, postdate, fromname, size, passwordstatus, haspreview, categoryid, nfostatus, nzbstatus, relnamestatus) values (%s, %s, %d, %d, NOW(), %s, -1, %s, %s, %d, %d, -1, 7010, -1, 1, 6)', $this->db->escapeString($subject), $this->db->escapeString($cleanName), $totalFiles, $realgroupid, $this->db->escapeString($relguid), $this->db->escapeString($postdate['0']), $this->db->escapeString($fromname), $totalsize, ($page->site->checkpasswordedrar == '1' ? -1 : 0)));
				else
					$relid = $this->db->queryInsert(sprintf('INSERT INTO releases (name, searchname, totalpart, groupid, adddate, guid, rageid, postdate, fromname, size, passwordstatus, haspreview, categoryid, nfostatus, nzbstatus, relnamestatus) values (%s, %s, %d, %d, NOW(), %s, -1, %s, %s, %d, %d, -1, 7010, -1, 1, 6)', $this->db->escapeString($subject), $this->db->escapeString($cleanName), $totalFiles, $realgroupid, $this->db->escapeString($relguid), $this->db->escapeString($postdate['0']), $this->db->escapeString($fromname), $totalsize, ($page->site->checkpasswordedrar == '1' ? -1 : 0)));

				// Set table names
				if ($this->tablepergroup == 1)
				{
					$group = array();
					$group['cname'] = $realgroupid.'_collections';
					$group['bname'] = $realgroupid.'_binaries';
					$group['pname'] = $realgroupid.'_parts';
				}
				else
				{
					$group = array();
					$group['cname'] = 'collections';
					$group['bname'] = 'binaries';
					$group['pname'] = 'parts';
				}

				if ($relid == false)
				{
					if ($this->db->dbSystem() == 'mysql')
						$this->db->queryExec(sprintf('DELETE '.$group['cname'].', '.$group['bname'].', '.$group['pname'].' FROM '.$group['cname'].' LEFT JOIN '.$group['bname'].' ON '.$group['cname'].'.id = '.$group['bname'].'.collectionid LEFT JOIN '.$group['pname'].' ON '.$group['bname'].'.id = '.$group['pname'].'.binaryid WHERE '.$group['cname'].'.collectionhash = %s', $this->db->escapeString($hash)));
					elseif ($this->db->dbSystem() == 'pgsql')
					{
						$idr = $this->db->query(sprintf('SELECT id FROM '.$group['cname'].' WHERE collectionhash = %s', $this->db->escapeString($hash)));
						if (count($idr) > 0)
						{
							foreach ($idr as $id)
							{
								$reccount = $this->db->queryExec(sprintf('DELETE FROM '.$group['pname'].' WHERE EXISTS (SELECT id FROM '.$group['bname'].' WHERE '.$group['bname'].'.id = '.$group['pname'].'.binaryid AND '.$group['bname'].'.collectionid = %d)', $id["id"]));
								$reccount += $this->db->queryExec(sprintf('DELETE FROM '.$group['bname'].' WHERE collectionid = %d', $id['id']));
							}
							$reccount += $this->db->queryExec(sprintf('DELETE FROM '.$group['cname'].' WHERE collectionshash = %s', $this->db->escapeString($hash)));
						}
					}
					$this->db->queryExec(sprintf('DELETE from nzbs where collectionhash = %s', $this->db->escapeString($hash)));
					echo '!';
					return;
				}
				elseif (count($relid) > 0)
				{
					$path=$nzb->getNZBPath($relguid, $nzbpath, true, $nzbsplitlevel);
					$fp = gzopen($path, 'w6');
					if ($fp)
					{
						gzwrite($fp, str_replace('</nzb>', '  <!-- generated by nZEDb '.$version." -->\n</nzb>", $article));
						gzclose($fp);
						if (file_exists($path))
						{
							chmod($path, 0777);
							$this->db->queryExec(sprintf('UPDATE releases SET nzbstatus = 1 WHERE id = %d', $relid));
							if ($this->db->dbSystem() == 'mysql')
								$this->db->queryExec(sprintf('DELETE '.$group['cname'].', '.$group['bname'].', '.$group['pname'].' FROM '.$group['cname'].' LEFT JOIN '.$group['bname'].' ON '.$group['cname'].'.id = '.$group['bname'].'.collectionid LEFT JOIN '.$group['pname'].' ON '.$group['bname'].'.id = '.$group['pname'].'.binaryid WHERE '.$group['cname'].'.collectionhash = %s', $this->db->escapeString($hash)));
							elseif ($this->db->dbSystem() == 'pgsql')
							{
								$idr = $this->db->query(sprintf('SELECT id FROM '.$group['cname'].' WHERE collectionhash = %s', $this->db->escapeString($hash)));
								if (count($idr) > 0)
								{
									foreach ($idr as $id)
									{
										$reccount = $this->db->queryExec(sprintf('DELETE FROM '.$group['cname'].' WHERE EXISTS (SELECT id FROM '.$group['bname'].' WHERE '.$group['bname'].'.id = '.$group['pname'].'.binaryid AND '.$group['bname'].'.collectionid = %d)', $id['id']));
										$reccount += $this->db->queryExec(sprintf('DELETE FROM '.$group['bname'].' WHERE collectionid = %d', $id['id']));
									}
									$reccount += $this->db->queryExec(sprintf('DELETE FROM '.$group['cname'].' WHERE collectionshash = %s', $this->db->escapeString($hash)));
								}
							}
							$this->db->queryExec(sprintf('DELETE from nzbs where collectionhash = %s', $this->db->escapeString($hash)));
							$this->categorize();
							echo '+';
						}
						else
						{
							$this->db->queryExec(sprintf('DELETE FROM releases WHERE id = %d', $relid));
							$importfailed = true;
							echo '-';
						}
					}
				}
			}
		}
	}
}
