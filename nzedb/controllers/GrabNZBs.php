<?php

use nzedb\db\DB;

class GrabNZBs
{

	function __construct()
	{
		$this->echo = nZEDb_ECHOCLI;
		$this->db = new DB();
		$s = new Sites();
		$this->site = $s->get();
		$this->tablepergroup = (isset($this->site->tablepergroup)) ? (int)$this->site->tablepergroup : 0;
		$this->replacenzbs = (isset($this->site->replacenzbs)) ? $this->site->replacenzbs : 0;
		$this->alternateNNTP = ($this->site->alternate_nntp === '1' ? true : false);
		$this->ReleaseCleaning = new ReleaseCleaning();
		//$this->CollectionsCleaning = new CollectionsCleaning();
		$this->categorize = new Category();
		$this->c = new ColorCLI();
	}

	public function Import($hash = '', $nntp)
	{
		if (!isset($nntp)) {
			exit($this->c->error("Unable to connect to usenet.\n"));
		}

		$nzb = array();
		if ($hash == '') {
			$hashes = $this->db->queryDirect('SELECT collectionhash FROM nzbs GROUP BY collectionhash, totalparts HAVING COUNT(*) >= totalparts');
			if ($hashes->rowCount > 0) {
				foreach ($hashes as $hash) {
					$rel = $this->db->queryDirect(sprintf('SELECT * FROM nzbs WHERE collectionhash = %s ORDER BY partnumber', $this->db->escapeString($hash['collectionhash'])));
					$arr = '';
					foreach ($rel as $nzb) {
						$arr[] = $nzb['message_id'];
					}
				}
			} else {
				exit("No NZBs to grab\n");
			}
		} else {
			$rel = $this->db->queryDirect(sprintf('SELECT * FROM nzbs WHERE collectionhash = %s ORDER BY partnumber', $this->db->escapestring($hash)));
			$arr = '';
			foreach ($rel as $nzb) {
				$arr[] = $nzb['message_id'];
			}
		}
		if ($nzb && array_key_exists('groupname', $nzb)) {
			if (sizeof($arr) > 10) {
				if ($this->echo) {
					$this->c->doEcho($this->c->header("\nGetting " . sizeof($arr) . ' articles for ' . $hash));
				}
			}

			$article = $nntp->getMessages($nzb['groupname'], $arr, $this->alternateNNTP);
			if ($nntp->isError($article)) {
				$article = false;
			}

			// If article downloaded, import it, else delete from nzbs table
			if ($article !== false) {
				$groups = new Groups();
				$realgroupid = $groups->getIDByName($nzb['groupname']);
				$this->processGrabNZBs($article, $hash, $realgroupid);
			} else {
				$this->db->queryExec(sprintf('DELETE FROM nzbs WHERE collectionhash = %s', $this->db->escapeString($hash)));

				if ($this->echo) {
					echo 'f';
				}
				return;
			}
		} else {
			return;
		}
	}

	function processGrabNZBs($article, $hash, $realgroupid)
	{
		if (!$article) {
			return;
		}

		$binaries = new Binaries();

		$groups = $this->db->queryDirect('SELECT id, name FROM groups');
		foreach ($groups as $group) {
			$siteGroups[$group['name']] = $group['id'];
		}

		$importfailed = $isBlackListed = false;
		$xml = @simplexml_load_string($article);
		// If article is not a valid xml, delete from nzbs
		if (!$xml) {
			$this->db->queryExec(sprintf('DELETE FROM nzbs WHERE collectionhash = %s', $this->db->escapeString($hash)));

			if ($this->echo) {
				echo '-';
			}
			return;
		} else {
			$totalFiles = $totalsize = $groupID =0;
			$firstname = $postername = $postdate = array();

			foreach ($xml->file as $file) {
				// File info.
				$groupID = -1;
				$name = (string) $file->attributes()->subject;
				$firstname[] = $name;
				$fromname = (string) $file->attributes()->poster;
				$postername[] = $fromname;
				$totalFiles++;
				$date = date('Y-m-d H:i:s', (string) ($file->attributes()->date));
				$postdate[] = $date;
				$partless = preg_replace('/(\(\d+\/\d+\))*$/', 'yEnc', $firstname['0']);
				$partless = preg_replace('/yEnc.*?$/', 'yEnc', $partless);
				$partless = preg_replace('/\[#?a\.b\.teevee@?EFNet\]/', '[#a.b.teevee@EFNet]', $partless);
				$subject = utf8_encode(trim($partless));

				// Make a fake message object to use to check the blacklist.
				$msg = array('Subject' => $subject, 'From' => $postername[0], 'Message-ID' => '');

				// Groups.
				$groupArr = array();
				foreach ($file->groups->group as $group) {
					$group = (string) $group;
					if (array_key_exists($group, $siteGroups)) {
						$groupName = $group;
						$groupID = $siteGroups[$group];
					}
					$groupArr[] = $group;

					if ($binaries->isBlacklisted($msg, $group)) {
						$isBlackListed = true;
					}
				}
				if ($groupID != -1 && !$isBlackListed) {
					if (count($file->segments->segment) > 0) {
						foreach ($file->segments->segment as $segment) {
							$totalsize += $segment->attributes()->bytes;
						}
					}
				} else {
					$importfailed = true;
				}
			}

			// To get accurate size to check for true duplicates, we need to process the entire nzb first
			if ($importfailed === false) {
				// A 1% variance in size is considered the same size when the subject and poster are the same
				$minsize = $totalsize * .99;
				$maxsize = $totalsize * 1.01;

				$res = $this->db->queryDirect(sprintf('SELECT id, guid FROM releases WHERE name = %s AND fromname = %s AND size BETWEEN %s AND %s', $this->db->escapeString($subject), $this->db->escapeString($fromname), $this->db->escapeString($minsize), $this->db->escapeString($maxsize)));
				if ($this->replacenzbs == 1) {
					$releases = new Releases();
					foreach ($res as $rel) {
						if (isset($rel['id']) && isset($rel['guid'])) {
							$releases->fastDelete($rel['id'], $rel['guid']);
						}
					}
				} else if ($res->rowCount() > 0 && $this->replacenzbs == 0) {
					flush();
					$importfailed = true;

					if ($this->echo) {
						echo '!';
					}
				}
			}

			if ($importfailed === true) {
				$this->db->queryExec(sprintf('DELETE from nzbs where collectionhash = %s', $this->db->escapeString($hash)));
				return;
			} else {
				$propername = true;
				$relguid = sha1(uniqid('', true) . mt_rand());
				$nzb = new NZB();
				$cleanerName = $this->ReleaseCleaning->releaseCleaner($subject, $fromname, $totalsize, $groupName);
				/* $ncarr = $this->CollectionsCleaning->collectionsCleaner($subject, $groupName);
				  $cleanerName = $ncarr['subject'];
				  $category = $ncarr['cat'];
				  $relstat = $ncar['rstatus']; */
				if (!is_array($cleanerName)) {
					$cleanName = $cleanerName;
				} else {
					$cleanName = $cleanerName['cleansubject'];
					$propername = $cleanerName['properlynamed'];
				}

				$subject = utf8_encode($subject);
				$cleanName = utf8_encode($cleanName);
				$fromname = utf8_encode($fromname);

				$category = $this->categorize->determineCategory($cleanName, $groupID);
				// If a release exists, delete the nzb/collection/binaries/parts
				if ($propername === true) {
					$relid = $this->db->queryInsert(sprintf('INSERT INTO releases (name, searchname, totalpart, groupid, adddate, guid, rageid, postdate, fromname, size, passwordstatus, haspreview, categoryid, nfostatus, isrenamed, iscategorized) values (%s, %s, %d, %d, NOW(), %s, -1, %s, %s, %s, %d, -1, %d, -1, 1, 1)', $this->db->escapeString($subject), $this->db->escapeString($cleanName), $totalFiles, $realgroupid, $this->db->escapeString($relguid), $this->db->escapeString($postdate['0']), $this->db->escapeString($fromname), $this->db->escapeString($totalsize), ($this->site->checkpasswordedrar === '1' ? -1 : 0), $category));
				} else {
					$relid = $this->db->queryInsert(sprintf('INSERT INTO releases (name, searchname, totalpart, groupid, adddate, guid, rageid, postdate, fromname, size, passwordstatus, haspreview, categoryid, nfostatus, iscategorized) values (%s, %s, %d, %d, NOW(), %s, -1, %s, %s, %s, %d, -1, %d, -1, 1)', $this->db->escapeString($subject), $this->db->escapeString($cleanName), $totalFiles, $realgroupid, $this->db->escapeString($relguid), $this->db->escapeString($postdate['0']), $this->db->escapeString($fromname), $this->db->escapeString($totalsize), ($this->site->checkpasswordedrar === '1' ? -1 : 0), $category));
				}

				// Set table names
				if ($this->tablepergroup === 1) {
					$group = array();
					$group['cname'] = 'collections_' . $realgroupid;
					$group['bname'] = 'binaries_' . $realgroupid;
					$group['pname'] = 'parts_' . $realgroupid;
				} else {
					$group = array();
					$group['cname'] = 'collections';
					$group['bname'] = 'binaries';
					$group['pname'] = 'parts';
				}

				if ($relid == false) {
					if ($this->db->dbSystem() === 'mysql') {
						$this->db->queryExec(sprintf('DELETE ' . $group['cname'] . ', ' . $group['bname'] . ', ' . $group['pname'] . ' FROM ' . $group['cname'] . ' LEFT JOIN ' . $group['bname'] . ' ON ' . $group['cname'] . '.id = ' . $group['bname'] . '.collectionid LEFT JOIN ' . $group['pname'] . ' ON ' . $group['bname'] . '.id = ' . $group['pname'] . '.binaryid WHERE ' . $group['cname'] . '.collectionhash = %s', $this->db->escapeString($hash)));
					} else if ($this->db->dbSystem() === 'pgsql') {
						$idr = $this->db->queryDirect(sprintf('SELECT id FROM ' . $group['cname'] . ' WHERE collectionhash = %s', $this->db->escapeString($hash)));
						if ($idr->rowCount() > 0) {
							foreach ($idr as $id) {
								$reccount = $this->db->queryExec(sprintf('DELETE FROM ' . $group['pname'] . ' WHERE EXISTS (SELECT id FROM ' . $group['bname'] . ' WHERE ' . $group['bname'] . '.id = ' . $group['pname'] . '.binaryid AND ' . $group['bname'] . '.collectionid = %d)', $id['id']));
								$reccount += $this->db->queryExec(sprintf('DELETE FROM ' . $group['bname'] . ' WHERE collectionid = %d', $id['id']));
							}
							$reccount += $this->db->queryExec(sprintf('DELETE FROM ' . $group['cname'] . ' WHERE collectionshash = %s', $this->db->escapeString($hash)));
						}
					}
					$this->db->queryExec(sprintf('DELETE from nzbs where collectionhash = %s', $this->db->escapeString($hash)));

					if ($this->echo) {
						echo '!';
					}
					return;
				} else if (count($relid) > 0) {
					$path = $nzb->getNZBPath($relguid, 0, true);
					$fp = gzopen($path, 'w5');
					if ($fp) {
						$date1 = htmlspecialchars(date('F j, Y, g:i a O'), ENT_QUOTES, 'utf-8');
						$article = preg_replace('/dtd">\s*<nzb xmlns=/', "dtd\">\n<!-- NZB Generated by: nZEDb " . $this->site->version . ' ' . $date1 . " -->\n<nzb xmlns=", $article);
						gzwrite($fp, preg_replace('/<\/file>\s*(<!--.+)?\s*<\/nzb>\s*/si', "</file>\n  <!--GrabNZBs-->\n</nzb>", $article));
						gzclose($fp);
						if (file_exists($path)) {
							chmod($path, 0777);
							$this->db->queryExec(sprintf('UPDATE releases SET nzbstatus = 1 WHERE id = %d', $relid));
							if ($this->db->dbSystem() === 'mysql') {
								$this->db->queryExec(sprintf('DELETE ' . $group['cname'] . ', ' . $group['bname'] . ', ' . $group['pname'] . ' FROM ' . $group['cname'] . ' LEFT JOIN ' . $group['bname'] . ' ON ' . $group['cname'] . '.id = ' . $group['bname'] . '.collectionid LEFT JOIN ' . $group['pname'] . ' ON ' . $group['bname'] . '.id = ' . $group['pname'] . '.binaryid WHERE ' . $group['cname'] . '.collectionhash = %s', $this->db->escapeString($hash)));
							} else if ($this->db->dbSystem() === 'pgsql') {
								$idr = $this->db->queryDirect(sprintf('SELECT id FROM ' . $group['cname'] . ' WHERE collectionhash = %s', $this->db->escapeString($hash)));
								if ($idr->rowCount() > 0) {
									foreach ($idr as $id) {
										$reccount = $this->db->queryExec(sprintf('DELETE FROM ' . $group['cname'] . ' WHERE EXISTS (SELECT id FROM ' . $group['bname'] . ' WHERE ' . $group['bname'] . '.id = ' . $group['pname'] . '.binaryid AND ' . $group['bname'] . '.collectionid = %d)', $id['id']));
										$reccount += $this->db->queryExec(sprintf('DELETE FROM ' . $group['bname'] . ' WHERE collectionid = %d', $id['id']));
									}
									$reccount += $this->db->queryExec(sprintf('DELETE FROM ' . $group['cname'] . ' WHERE collectionshash = %s', $this->db->escapeString($hash)));
								}
							}
							$this->db->queryExec(sprintf('DELETE from nzbs where collectionhash = %s', $this->db->escapeString($hash)));

							if ($this->echo) {
								echo '+';
							}
						} else {
							$this->db->queryExec(sprintf('DELETE FROM releases WHERE id = %d', $relid));
							$importfailed = true;

							if ($this->echo) {
								echo '-';
							}
						}
					}
				}
			}
		}
	}

}
