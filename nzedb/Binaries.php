<?php

class Binaries
{
	const BLACKLIST_FIELD_SUBJECT = 1;
	const BLACKLIST_FIELD_FROM = 2;
	const BLACKLIST_FIELD_MESSAGEID = 3;

	public function __construct()
	{
		$this->db = new DB();
		$s = new Sites();
		$this->site = $s->get();
		$this->backfill = new Backfill($this->site);
		$this->groups = new Groups($this->db);
		$this->collectionsCleaning = new CollectionsCleaning();
		$this->consoleTools = new ConsoleTools();
		$this->compressedHeaders = ($this->site->compressedheaders == '1') ? true : false;
		$this->messagebuffer = (!empty($this->site->maxmssgs)) ? $this->site->maxmssgs : 20000;
		$this->NewGroupScanByDays = ($this->site->newgroupscanmethod == '1') ? true : false;
		$this->NewGroupMsgsToScan = (!empty($this->site->newgroupmsgstoscan)) ? $this->site->newgroupmsgstoscan : 50000;
		$this->NewGroupDaysToScan = (!empty($this->site->newgroupdaystoscan)) ? $this->site->newgroupdaystoscan : 3;
		$this->DoPartRepair = ($this->site->partrepair == '0') ? false : true;
		$this->partrepairlimit = (!empty($this->site->maxpartrepair)) ? $this->site->maxpartrepair : 15000;
		$this->hashcheck = (!empty($this->site->hashcheck)) ? $this->site->hashcheck : 0;
		$this->debug = ($this->site->debuginfo == '0') ? false : true;
		$this->grabnzbs = ($this->site->grabnzbs == '0') ? false : true;
		$this->tablepergroup = (!empty($this->site->tablepergroup)) ? $this->site->tablepergroup : 0;
		$this->showdroppedyencparts = (!empty($this->site->showdroppedyencparts)) ? $this->site->showdroppedyencparts : 0;
		$this->c = new ColorCLI();

		// Cache of our black/white list.
		$this->blackList = $this->message = array();
		$this->blackListLoaded = false;
	}

	public function updateAllGroups($nntp)
	{
		if (!isset($nntp)) {
			exit($this->c->error("Not connected to usenet(binaries->updateAllGroups)."));
		}

		if ($this->hashcheck == 0) {
			echo $this->c->warning("We have updated the way collections are created, the collection table has to be updated to use the new changes, if you want to run this now, type 'yes', else type no to see how to run manually.");
			if (trim(fgets(fopen('php://stdin', 'r'))) != 'yes') {
				exit($this->c->primary("If you want to run this manually, there is a script in misc/testing/DB/ called reset_Collections.php"));
			}
			$relss = new Releases(true);
			$relss->resetCollections();
		}
		$res = $this->groups->getActive();
		$counter = 1;

		if ($res) {
			$alltime = microtime(true);
			echo $this->c->header("\nUpdating: " . sizeof($res) . ' group(s) - Using compression? ' . (($this->compressedHeaders) ? 'Yes' : 'No'));

			foreach ($res as $groupArr) {
				$this->message = array();
				echo $this->c->header("\nStarting group " . $counter . ' of ' . sizeof($res));
				$this->updateGroup($groupArr, $nntp);
				$counter++;
			}
			echo $this->c->primary('Updating completed in ' . number_format(microtime(true) - $alltime, 2) . " seconds.");
		} else {
			echo $this->c->warning("No groups specified. Ensure groups are added to nZEDb's database for updating.");
		}
	}

	public function updateGroup($groupArr, $nntp)
	{
		if (!isset($nntp)) {
			exit($this->c->error("Not connected to usenet(binaries->updateGroup)."));
		}

		$this->startGroup = microtime(true);
		echo $this->c->primary('Processing ' . str_replace('alt.binaries', 'a.b', $groupArr['name']));

		// Select the group, here, needed for processing the group
		$data = $nntp->selectGroup($groupArr['name']);
		if (PEAR::isError($data)) {
			$data = $nntp->dataError($nntp, $groupArr['name']);
			if ($data === false) {
				return;
			}
		}

		// Attempt to repair any missing parts before grabbing new ones.
		if ($groupArr['last_record'] != 0) {
			if ($this->DoPartRepair) {
				echo $this->c->primary("Part repair enabled. Checking for missing parts.");
				$this->partRepair($nntp, $groupArr);
			} else {
				echo $this->c->primary("Part repair disabled by user.");
			}
		}

		// Get first and last part numbers from newsgroup.
		$db = $this->db;

		if ($groupArr['last_record'] == 0) {
			// For new newsgroups - determine here how far you want to go back.
			if ($this->NewGroupScanByDays) {
				$first = $this->backfill->daytopost($nntp, $groupArr['name'], $this->NewGroupDaysToScan, $data, true);
				if ($first == '') {
					echo $this->c->warning("Skipping group: {$groupArr['name']}");
					return;
				}
			} else {
				if ($data['first'] > ($data['last'] - ($this->NewGroupMsgsToScan + $this->messagebuffer))) {
					$first = $data['first'];
				} else {
					$first = $data['last'] - ($this->NewGroupMsgsToScan + $this->messagebuffer);
				}
			}

			$left = $this->messagebuffer;
			$last = $grouplast = $data['last'] - $left;
		} else {
			$first = $groupArr['last_record'];

			// Leave 50%+ of the new articles on the server for next run (allow server enough time to actually make parts available).
			$newcount = $data['last'] - $first;
			$left = 0;
			if ($newcount > $this->messagebuffer) {
				// Drop the remaining plus $this->messagebuffer, pick them up on next run
				if ($newcount < (2 * $this->messagebuffer)) {
					$left = ((int) ($newcount / 2));
					$last = $grouplast = ($data['last'] - $left);
				} else {
					$remainingcount = $newcount % $this->messagebuffer;
					$left = $remainingcount + $this->messagebuffer;
					$last = $grouplast = ($data['last'] - $left);
				}
			} else {
				$left = ((int) ($newcount / 2));
				$last = $grouplast = ($data['last'] - $left);
			}
		}

		// Generate postdate for first record, for those that upgraded.
		if (is_null($groupArr['first_record_postdate']) && $groupArr['first_record'] != '0') {
			$newdate = $this->backfill->postdate($nntp, $groupArr['first_record'], false, $groupArr['name'], true, 'oldest');
			if ($newdate !== false) {
				$first_record_postdate = $newdate;
			} else {
				$first_record_postdate = time();
			}

			$groupArr['first_record_postdate'] = $first_record_postdate;

			$db->queryExec(sprintf('UPDATE groups SET first_record_postdate = %s WHERE id = %d', $db->from_unixtime($first_record_postdate), $groupArr['id']));
		}

		// Defaults for post record first/last postdate
		if (is_null($groupArr['first_record_postdate'])) {
			$first_record_postdate = time();
		} else {
			$first_record_postdate = strtotime($groupArr['first_record_postdate']);
		}

		if (is_null($groupArr['last_record_postdate'])) {
			$last_record_postdate = time();
		} else {
			$last_record_postdate = strtotime($groupArr['last_record_postdate']);
		}


		// Calculate total number of parts.
		$total = $grouplast - $first;
		$realtotal = $data['last'] - $first;

		// If total is bigger than 0 it means we have new parts in the newsgroup.
		if ($total > 0) {
			if ($groupArr['last_record'] == 0) {
				echo $this->c->primary('New group ' . $data['group'] . ' starting with ' . (($this->NewGroupScanByDays) ? $this->NewGroupDaysToScan . ' days' : number_format($this->NewGroupMsgsToScan) . ' messages') . " worth. Leaving " . number_format($left) . " for next pass.\nServer oldest: " . number_format($data['first']) . ' Server newest: ' . number_format($data['last']) . ' Local newest: ' . number_format($groupArr['last_record']));
			} else {
				echo $this->c->primary('Group ' . $data['group'] . ' has ' . number_format($realtotal) . " new articles. Leaving " . number_format($left) . " for next pass.\nServer oldest: " . number_format($data['first']) . ' Server newest: ' . number_format($data['last']) . ' Local newest: ' . number_format($groupArr['last_record']));
			}

			$done = false;
			// Get all the parts (in portions of $this->messagebuffer to not use too much memory).
			while ($done === false) {
				$this->startLoop = microtime(true);

				if ($total > $this->messagebuffer) {
					if ($first + $this->messagebuffer > $grouplast) {
						$last = $grouplast;
					} else {
						$last = $first + $this->messagebuffer;
					}
				}
				$first++;
				echo $this->c->header("\nGetting " . number_format($last - $first + 1) . ' articles (' . number_format($first) . ' to ' . number_format($last) . ') from ' . str_replace('alt.binaries', 'a.b', $data['group']) . " - (" . number_format($grouplast - $last) . " articles in queue).");
				flush();

				// Get article headers from newsgroup. Let scan deal with nntp connection, else compression fails after first grab
				$scanSummary = $this->scan($nntp, $groupArr, $first, $last);

				// Scan failed - skip group.
				if ($scanSummary == false) {
					return;
				}

				// If new group, update first record & postdate
				if (is_null($groupArr['first_record_postdate']) && $groupArr['first_record'] == '0') {
					$groupArr['first_record'] = $scanSummary['firstArticleNumber'];

					if (isset($scanSummary['firstArticleDate'])) {
						$first_record_postdate = strtotime($scanSummary['firstArticleDate']);
					}

					$groupArr['first_record_postdate'] = $first_record_postdate;

					$db->queryExec(sprintf('UPDATE groups SET first_record = %s, first_record_postdate = %s WHERE id = %d', $scanSummary['firstArticleNumber'], $db->from_unixtime($db->escapeString($first_record_postdate)), $groupArr['id']));
				}

				if (isset($scanSummary['lastArticleDate'])) {
					$last_record_postdate = strtotime($scanSummary['lastArticleDate']);
				}

				$db->queryExec(sprintf('UPDATE groups SET last_record = %s, last_record_postdate = %s, last_updated = NOW() WHERE id = %d', $db->escapeString($scanSummary['lastArticleNumber']), $db->from_unixtime($last_record_postdate), $groupArr['id']));

				if ($last == $grouplast) {
					$done = true;
				} else {
					$first = $last;
				}
			}
			$timeGroup = number_format(microtime(true) - $this->startGroup, 2);
			echo $this->c->primary("\n" . $data['group'] . ' processed in ' . $timeGroup . " seconds.\n");
		} else {
			echo $this->c->primary('No new articles for ' . $data['group'] . ' (first ' . number_format($first) . ' last ' . number_format($last) . ' grouplast ' . number_format($groupArr['last_record']) . ' total ' . number_format($total) . ")");
			echo $this->c->primary("Server oldest: " . number_format($data['first']) . ' Server newest: ' . number_format($data['last']) . ' Local newest: ' . number_format($groupArr['last_record']) . "\n");
		}
	}

	public function scan($nntp, $groupArr, $first, $last, $type = 'update', $missingParts = null)
	{
		$returnArray = array();

		if (!isset($nntp)) {
			exit($this->c->error("Not connected to usenet(binaries->scan)."));
		}

		$db = $this->db;
		$this->startHeaders = microtime(true);
		$this->startLoop = microtime(true);

		// Check that tables exist, create if they do not
		if ($this->tablepergroup == 1) {
			if ($db->newtables($groupArr['id']) === false) {
				exit($this->c->error("There is a problem creating new parts/files tables for this group."));
			}
			$group['cname'] = 'collections_' . $groupArr['id'];
			$group['bname'] = 'binaries_' . $groupArr['id'];
			$group['pname'] = 'parts_' . $groupArr['id'];
		} else {
			$group['cname'] = 'collections';
			$group['bname'] = 'binaries';
			$group['pname'] = 'parts';
		}

		// Download the headers.
		$msgs = $nntp->getOverview($first . "-" . $last, true, false);
		// If there ware an error, try to reconnect.
		if ($type != 'partrepair' && PEAR::isError($msgs)) {
			// This is usually a compression error, so try disabling compression.
			$nntp->doQuit();
			if ($nntp->doConnectNC() === false) {
				return false;
			}

			$nntp->selectGroup($groupArr['name']);
			$msgs = $nntp->getOverview($first . '-' . $last, true, false);
			if (PEAR::isError($msgs)) {
				echo $this->c->error(" Code {$msgs->code}: {$msgs->message}\nSkipping group: ${groupArr['name']}");
				return false;
			}
		}
		$timeHeaders = number_format(microtime(true) - $this->startHeaders, 2);

		$this->startCleaning = microtime(true);
		$rangerequested = range($first, $last);
		$msgsreceived = $msgsblacklisted = $msgsignored = $msgsnotinserted = $msgrepaired = array();
		if (is_array($msgs)) {
			// For looking at the difference between $subject/$cleansubject and to show non yEnc posts.
			if ($this->debug) {
				$colnames = $orignames = $notyenc = array();
			}

			// Sort the articles before processing, alphabetically by subject. This is to try to use the shortest subject and those without .vol01 in the subject
			usort($msgs, function ($elem1, $elem2) {
				return strcmp($elem1['Subject'], $elem2['Subject']);
			});

			// Loop articles, figure out files/parts.
			foreach ($msgs AS $msg) {
				if (!isset($msg['Number'])) {
					continue;
				}

				if (isset($returnArray['firstArticleNumber'])) {
					if ($msg['Number'] < $returnArray['firstArticleNumber']) {
						$returnArray['firstArticleNumber'] = $msg['Number'];
					}
					if (isset($msg['Date'])) {
						$returnArray['firstArticleDate'] = $msg['Date'];
					}
				} else {
					$returnArray['firstArticleNumber'] = $msg['Number'];
					if (isset($msg['Date'])) {
						$returnArray['firstArticleDate'] = $msg['Date'];
					}
				}

				if (isset($returnArray['lastArticleNumber'])) {
					if ($msg['Number'] > $returnArray['lastArticleNumber']) {
						$returnArray['lastArticleNumber'] = $msg['Number'];
					}
					if (isset($msg['Date'])) {
						$returnArray['lastArticleDate'] = $msg['Date'];
					}
				} else {
					$returnArray['lastArticleNumber'] = $msg['Number'];
					if (isset($msg['Date'])) {
						$returnArray['lastArticleDate'] = $msg['Date'];
					}
				}

				// If set we are running in partRepair mode
				if (isset($missingParts)) {
					if (!in_array($msg['Number'], $missingParts)) { // If article isn't one that is missing skip it.
						continue;
					} else { // We got the part this time. Remove article from partrepair.
						$msgrepaired[] = $msg['Number'];
					}
				}

				if (isset($msg['Bytes'])) {
					$bytes = $msg['Bytes'];
				} else {
					$bytes = $msg[':bytes'];
				}

				$msgsreceived[] = $msg['Number'];
				$partnumber = '';
				// Add yEnc to headers that do not have them, but are nzbs and that have the part number at the end of the header
				if (!preg_match('/yEnc/i', $msg['Subject'])) {
					if (preg_match('/.+(\(\d+\/\d+\))$/', $msg['Subject'], $partnumber)) {
						$msg['Subject'] = preg_replace('/\(\d+\/\d+\)$/', ' yEnc ' . $partnumber[1], $msg['Subject']);
					}
				}

				/* 				if (!preg_match('/yEnc/i', $msg['Subject']) && preg_match('/.+nzb.+\(\d+\/\d+\)$/', $msg['Subject'])) {
				  if (preg_match('/.+\.nzb.+(\(\d+\/\d+\))$/', $msg['Subject'], $partnumber)) {
				  $msg['Subject'] = preg_replace('/\(\d+\/\d+\)$/', ' yEnc ' . $partnumber[1], $msg['Subject']);
				  }
				  }
				  if (preg_match('/"(Usenet Index Post) \d+ yEnc \(\d+\/\d+\)"/', $msg['Subject'], $number)) {
				  $msg['Subject'] = preg_replace('/Usenet Index Post/', $number[1] . '.nzb ', $msg['Subject']);
				  }

				  if (preg_match('/"(Usenet Index Post) \d+_\d+ yEnc \(\d+\/\d+\)"/', $msg['Subject'], $number)) {
				  continue;
				  }
				 */
				if (preg_match('/"(Usenet Index Post) \d+(_\d+)? yEnc \(\d+\/\d+\)"/', $msg['Subject'], $number)) {
					continue;
				}
				$matches = '';
				// Not a binary post most likely.. continue.
				if (!isset($msg['Subject']) || !preg_match('/(.+yEnc)(\.\s*|\s*by xMas\s*|_|\s*--\s*READ NFO!\s*|\s*| \[S\d+E\d+\]|\s*".+"\s*)\((\d+)\/(\d+)\)/', $msg['Subject'], $matches)) {
					//if (!preg_match('/"Usenet Index Post [\d_]+ yEnc \(\d+\/\d+\)"/', $msg['Subject']) && preg_match('/yEnc/i', $msg['Subject']) && $this->showdroppedyencparts === '1') {
					if ($this->showdroppedyencparts === '1' && !preg_match('/"Usenet Index Post [\d_]+ yEnc \(\d+\/\d+\)"/', $msg['Subject'])) {
						file_put_contents(nZEDb_ROOT . "not_yenc/" . $groupArr['name'] . ".dropped.txt", $msg['Subject'] . "\n", FILE_APPEND);
					}

					// Uncomment this and the print_r about 80 lines down to see which posts are not yenc.
					/* if ($this->debug)
					  {
					  preg_match('/(.+)\(\d+\/\d+\)$/i', $msg['Subject'], $ny);
					  if(!in_array($ny[1], $notyenc))
					  $notyenc[] = $ny[1];
					  } */
					$msgsignored[] = $msg['Number'];
					continue;
				}

				// Filter subject based on black/white list.
				if ($this->isBlackListed($msg, $groupArr['name'])) {
					$msgsblacklisted[] = $msg['Number'];
					continue;
				}

				// Attempt to find the file count. If it is not found, set it to 0.
				$nofiles = false;
				$partless = $matches[1];
				$filecnt = '';
				if (!preg_match('/(\[|\(|\s)(\d{1,5})(\/|(\s|_)of(\s|_)|\-)(\d{1,5})(\]|\)|\s|$|:)/i', $partless, $filecnt)) {
					$filecnt[2] = $filecnt[6] = 0;
					$nofiles = true;
					if ($this->showdroppedyencparts === '1' && preg_match('/yEnc/i', $msg['Subject'])) {
						file_put_contents("/var/www/nZEDb/not_yenc/" . $groupArr['name'] . ".no_parts.txt", $msg['Subject'] . "\n", FILE_APPEND);
					}
				}

				if (is_numeric($matches[3]) && is_numeric($matches[4])) {
					array_map('trim', $matches);
					// Inserted into the collections table as the subject.
					$subject = utf8_encode(trim($partless));

					// Used for the sha1 hash (see below).
					$cleansubject = utf8_encode($this->collectionsCleaning->collectionsCleaner($subject, $groupArr['name'], $nofiles));

					/*
					  $ncarr = $this->collectionsCleaning->collectionsCleaner($subject, $groupArr['name'], $nofiles);
					  $cleansubject = $ncarr['hash'];
					 */

					// For looking at the difference between $subject and $cleansubject.
					if ($this->debug) {
						if (!in_array($cleansubject, $colnames)) {
							// Uncomment this to only show articles matched by generic function of collectioncleaning (might show some that match by collectionsCleaner, but rare). Helps when making regex.

							/* if (preg_match('/yEnc$/', $cleansubject))
							  {
							  $colnames[] = $cleansubject;
							  $orignames[] = $msg['Subject'];
							  } */

							/* If you uncommented the above, comment following 2 lines.. */
							$colnames[] = $cleansubject;
							$orignames[] = $msg['Subject'];
						}
					}

					// Set up the info for inserting into parts/binaries/collections tables.
					if (!isset($this->message[$subject])) {
						$this->message[$subject] = $msg;
						$this->message[$subject]['MaxParts'] = (int) $matches[4];
						$this->message[$subject]['Date'] = strtotime($msg['Date']);
						// (hash) Groups articles together when forming the release/nzb.
						$this->message[$subject]['CollectionHash'] = sha1($cleansubject . $msg['From'] . $groupArr['id'] . $filecnt[6]);
						$this->message[$subject]['MaxFiles'] = (int) $filecnt[6];
						$this->message[$subject]['File'] = (int) $filecnt[2];
					}

					if ($this->grabnzbs && preg_match('/.+\.nzb/', $subject)) {
						$db->queryInsert(sprintf('INSERT INTO nzbs (message_id, groupname, subject, collectionhash, filesize, partnumber, totalparts, postdate, dateadded) VALUES (%s, %s, %s, %s, %d, %d, %d, %s, NOW()) ON DUPLICATE KEY UPDATE dateadded = NOW()', $db->escapeString(substr($msg['Message-ID'], 1, -1)), $db->escapeString($groupArr['name']), $db->escapeString(substr($subject, 0, 255)), $db->escapeString($this->message[$subject]['CollectionHash']), (int) $bytes, (int) $matches[3], $this->message[$subject]['MaxParts'], $db->from_unixtime($this->message[$subject]['Date'])));
					}
					if ((int) $matches[3] > 0) {
						$this->message[$subject]['Parts'][(int) $matches[3]] = array('Message-ID' => substr($msg['Message-ID'], 1, -1), 'number' => $msg['Number'], 'part' => (int) $matches[3], 'size' => $bytes);
					}
				}
			}

			// Uncomment this to see which articles are not yEnc.
			//if ($this->debug && count($notyenc) > 1)
			//	print_r($notyenc);
			// For looking at the difference between $subject and $cleansubject.
			if ($this->debug && count($colnames) > 1 && count($orignames) > 1) {
				$arr = array_combine($colnames, $orignames);
				ksort($arr);
				print_r($arr);
			}
			$timeCleaning = number_format(microtime(true) - $this->startCleaning, 2);

			unset($msg, $msgs);
			$maxnum = $last;
			$rangenotreceived = array_diff($rangerequested, $msgsreceived);

			if ($type != 'partrepair') {
				echo $this->c->primary('Received ' . number_format(sizeof($msgsreceived)) . ' articles of ' . (number_format($last - $first + 1)) . ' requested, ' . sizeof($msgsblacklisted) . ' blacklisted, ' . sizeof($msgsignored) . " not yEnc.");
			}

			if (sizeof($msgrepaired) > 0) {
				$this->removeRepairedParts($msgrepaired, $groupArr['id']);
			}

			if (sizeof($rangenotreceived) > 0) {
				switch ($type) {
					case 'backfill':
						// Don't add missing articles.
						break;
					case 'partrepair':
						// Don't add here. Bulk update in partRepair
						break;
					case 'update':
					default:
						if ($this->DoPartRepair) {
							$this->addMissingParts($rangenotreceived, $groupArr['id']);
						}
						break;
				}
				if ($type != 'partrepair') {
					echo $this->c->alternate('Server did not return ' . sizeof($rangenotreceived) . " articles from " . str_replace('alt.binaries', 'a.b', $groupArr['name']) . ".");
				}
			}

			$this->startUpdate = microtime(true);
			if (isset($this->message) && count($this->message)) {
				$maxnum = $first;
				$pBinaryID = $pNumber = $pMessageID = $pPartNumber = $pSize = 1;
				// Insert collections, binaries and parts into database. When collection exists, only insert new binaries, when binary already exists, only insert new parts.
				$insPartsStmt = $db->Prepare('INSERT INTO ' . $group['pname'] . ' (binaryid, number, messageid, partnumber, size) VALUES (?, ?, ?, ?, ?)');
				$insPartsStmt->bindParam(1, $pBinaryID, PDO::PARAM_INT);
				$insPartsStmt->bindParam(2, $pNumber, PDO::PARAM_INT);
				$insPartsStmt->bindParam(3, $pMessageID, PDO::PARAM_STR);
				$insPartsStmt->bindParam(4, $pPartNumber, PDO::PARAM_INT);
				$insPartsStmt->bindParam(5, $pSize, PDO::PARAM_INT);

				$collectionHashes = $binaryHashes = array();
				$lastCollectionHash = $lastBinaryHash = "";
				$lastCollectionID = $lastBinaryID = -1;

				foreach ($this->message AS $subject => $data) {
					if (isset($data['Parts']) && count($data['Parts']) > 0 && $subject != '') {
						$db->beginTransaction();
						$collectionHash = $data['CollectionHash'];
						if ($lastCollectionHash == $collectionHash) {
							$collectionID = $lastCollectionID;
						} else {
							$lastCollectionHash = $collectionHash;
							$lastBinaryHash = '';
							$lastBinaryID = -1;

							$cres = $db->queryOneRow(sprintf('SELECT id, subject FROM ' . $group['cname'] . ' WHERE collectionhash = %s', $db->escapeString($collectionHash)));
							if (array_key_exists($collectionHash, $collectionHashes)) {
								$collectionID = $collectionHashes[$collectionHash];
								if (preg_match('/\.vol\d+/i', $subject) && !preg_match('/\.vol\d+/i', $cres['subject'])) {
									$db->queryExec(sprintf('UPDATE ' . $group['cname'] . ' set subject = %s WHERE id = %s', $db->escapeString(substr($subject, 0, 255)), $collectionID));
								}
							} else {
								if (!$cres) {
									// added utf8_encode on fromname, seems some foreign groups contains characters that were not escaping properly
									$csql = sprintf('INSERT INTO ' . $group['cname'] . ' (subject, fromname, date, xref, groupid, totalfiles, collectionhash, dateadded) VALUES (%s, %s, %s, %s, %d, %d, %s, NOW())', $db->escapeString(substr($subject, 0, 255)), $db->escapeString(utf8_encode($data['From'])), $db->from_unixtime($data['Date']), $db->escapeString(substr($data['Xref'], 0, 255)), $groupArr['id'], $data['MaxFiles'], $db->escapeString($collectionHash));
									$collectionID = $db->queryInsert($csql);
								} else {
									$collectionID = $cres['id'];
									//Update the collection table with the last seen date for the collection. This way we know when the last time a person posted for this hash.
									if (preg_match('/\.vol\d+/i', $subject) && !preg_match('/\.vol\d+/i', $cres['subject'])) {
										$db->queryExec(sprintf('UPDATE ' . $group['cname'] . ' set subject = %s WHERE id = %s', $db->escapeString(substr($subject, 0, 255)), $collectionID));
									} else {
										$db->queryExec(sprintf('UPDATE ' . $group['cname'] . ' set dateadded = NOW() WHERE id = %s', $collectionID));
									}
								}
								$collectionHashes[$collectionHash] = $collectionID;
							}
							$lastCollectionID = $collectionID;
						}
						$binaryHash = md5($subject . $data['From'] . $groupArr['id']);

						if ($lastBinaryHash == $binaryHash) {
							$binaryID = $lastBinaryID;
						} else {
							if (array_key_exists($binaryHash, $binaryHashes)) {
								$binaryID = $binaryHashes[$binaryHash];
							} else {
								$lastBinaryHash = $binaryHash;

								$bres = $db->queryOneRow(sprintf('SELECT id FROM ' . $group['bname'] . ' WHERE binaryhash = %s', $db->escapeString($binaryHash)));
								if (!$bres) {
									$bsql = sprintf('INSERT INTO ' . $group['bname'] . ' (binaryhash, name, collectionid, totalparts, filenumber) VALUES (%s, %s, %d, %s, %s)', $db->escapeString($binaryHash), $db->escapeString($subject), $collectionID, $db->escapeString($data['MaxParts']), $db->escapeString(round($data['File'])));
									$binaryID = $db->queryInsert($bsql);
								} else {
									$binaryID = $bres['id'];
								}

								$binaryHashes[$binaryHash] = $binaryID;
							}
							$lastBinaryID = $binaryID;
						}

						foreach ($data['Parts'] AS $partdata) {
							$pBinaryID = $binaryID;
							$pMessageID = $partdata['Message-ID'];
							$pNumber = $partdata['number'];
							$pPartNumber = round($partdata['part']);
							$maxnum = ($partdata['number'] > $maxnum) ? $partdata['number'] : $maxnum;
							if (is_numeric($partdata['size'])) {
								$pSize = $partdata['size'];
							}
							try {
								if (!$insPartsStmt->execute()) {
									$msgsnotinserted[] = $partdata['number'];
								}
							} catch (PDOException $e) {
								if ($e->errorInfo[0] == 1213 || $e->errorInfo[0] == 40001 || $e->errorInfo[0] == 1205) {
									continue;
								}
							}
						}
						$db->Commit();
					}
				}
				if (sizeof($msgsnotinserted) > 0) {
					echo $this->c->warning("" . sizeof($msgsnotinserted) . " parts failed to insert.");
					if ($this->DoPartRepair) {
						$this->addMissingParts($msgsnotinserted, $groupArr['id']);
					}
				}
			}
			$timeUpdate = number_format(microtime(true) - $this->startUpdate, 2);
			$timeLoop = number_format(microtime(true) - $this->startLoop, 2);

			if ($type != 'partrepair') {
				echo $this->c->primary($timeHeaders . 's to download articles, ' . $timeCleaning . 's to process articles, ' . $timeUpdate . 's to insert articles, ' . $timeLoop . 's total.');
			}

			unset($this->message, $data);
			return $returnArray;
		} else {
			if ($type != 'partrepair') {
				echo $this->c->error("Can't get parts from server (msgs not array).\nSkipping group: ${groupArr['name']}");
				return false;
			}
		}
	}

	public function partRepair($nntp, $groupArr)
	{
		if (!isset($nntp)) {
			exit($this->c->error("Not connected to usenet(binaries->partRepair)."));
		}

		// Get all parts in partrepair table.
		$db = $this->db;

		// Check that tables exist, create if they do not
		if ($this->tablepergroup == 1) {
			if ($db->newtables($groupArr['id']) === false) {
				exit($this->c->error("There is a problem creating new parts/files tables for this group."));
			}
			$group['prname'] = 'partrepair_' . $groupArr['id'];
		} else {
			$group['prname'] = 'partrepair';
		}

		$missingParts = $db->query(sprintf('SELECT * FROM ' . $group['prname'] . ' WHERE groupid = %d AND attempts < 5 ORDER BY numberid ASC LIMIT %d', $groupArr['id'], $this->partrepairlimit));
		$partsRepaired = $partsFailed = 0;

		if (sizeof($missingParts) > 0) {
			echo $this->consoleTools->overWritePrimary('Attempting to repair ' . number_format(sizeof($missingParts)) . " parts.");

			// Loop through each part to group into continuous ranges with a maximum range of messagebuffer/4.
			$ranges = array();
			$partlist = array();
			$firstpart = $lastnum = $missingParts[0]['numberid'];
			foreach ($missingParts as $part) {
				if (($part['numberid'] - $firstpart) > ($this->messagebuffer / 4)) {
					$ranges[] = array('partfrom' => $firstpart, 'partto' => $lastnum, 'partlist' => $partlist);
					$firstpart = $part['numberid'];
					$partlist = array();
				}
				$partlist[] = $part['numberid'];
				$lastnum = $part['numberid'];
			}
			$ranges[] = array('partfrom' => $firstpart, 'partto' => $lastnum, 'partlist' => $partlist);

			$num_attempted = 0;

			// Download missing parts in ranges.
			foreach ($ranges as $range) {
				$this->startLoop = microtime(true);

				$partfrom = $range['partfrom'];
				$partto = $range['partto'];
				$partlist = $range['partlist'];
				$count = sizeof($range['partlist']);

				$num_attempted += $count;
				$this->consoleTools->overWritePrimary("Attempting repair: " . $this->consoleTools->percentString2($num_attempted - $count + 1, $num_attempted, sizeof($missingParts)) . ': ' . $partfrom . ' to ' . $partto);

				// Get article from newsgroup.
				$this->scan($nntp, $groupArr, $partfrom, $partto, 'partrepair', $partlist);
			}

			// Calculate parts repaired
			$sql = sprintf('SELECT COUNT(id) AS num FROM ' . $group['prname'] . ' WHERE groupid=%d AND numberid <= %d', $groupArr['id'], $missingParts[sizeof($missingParts) - 1]['numberid']);
			$result = $db->queryOneRow($sql);
			if (isset($result['num'])) {
				$partsRepaired = (sizeof($missingParts)) - $result['num'];
			}

			// Update attempts on remaining parts for active group
			if (isset($missingParts[sizeof($missingParts) - 1]['id'])) {
				$sql = sprintf('UPDATE ' . $group['prname'] . ' SET attempts=attempts+1 WHERE groupid=%d AND numberid <= %d', $groupArr['id'], $missingParts[sizeof($missingParts) - 1]['numberid']);
				$result = $db->queryExec($sql);
				if ($result) {
					$partsFailed = $result->rowCount();
				}
			}
			echo $this->c->primary("\n" . number_format($partsRepaired) . " parts repaired.");
		}

		// Remove articles that we cant fetch after 5 attempts.
		$db->queryExec(sprintf('DELETE FROM ' . $group['prname'] . ' WHERE attempts >= 5 AND groupid = %d', $groupArr['id']));
	}

	private function addMissingParts($numbers, $groupID)
	{
		$db = $this->db;

		// Check that tables exist, create if they do not
		if ($this->tablepergroup == 1) {
			if ($db->newtables($groupID) === false) {
				exit($this->c->error("There is a problem creating new parts/files tables for this group."));
			}
			$group['prname'] = 'partrepair_' . $groupID;
		} else {
			$group['prname'] = 'partrepair';
		}

		$insertStr = 'INSERT INTO ' . $group['prname'] . ' (numberid, groupid) VALUES ';
		foreach ($numbers as $number) {
			$insertStr .= sprintf('(%d, %d), ', $number, $groupID);
		}

		$insertStr = substr($insertStr, 0, -2);
		if ($db->dbSystem() == 'mysql') {
			$insertStr .= ' ON DUPLICATE KEY UPDATE attempts=attempts+1';
			return $db->queryInsert($insertStr);
		} else {
			$id = $db->queryInsert($insertStr);
			$db->Exec('UPDATE partrepair SET attempts = attempts+1 WHERE id = ' . $id);
			return $id;
		}
	}

	private function removeRepairedParts($numbers, $groupID)
	{
		$db = $this->db;
		if ($this->tablepergroup == 1) {
			$group['prname'] = 'partrepair_' . $groupID;
		} else {
			$group['prname'] = 'partrepair';
		}

		$sql = 'DELETE FROM ' . $group['prname'] . ' WHERE numberid in (';
		foreach ($numbers as $number) {
			$sql .= sprintf('%d, ', $number);
		}
		$sql = substr($sql, 0, -2);
		$sql .= sprintf(') AND groupid = %d', $groupID);
		$db->queryExec($sql);
	}

	public function retrieveBlackList()
	{
		if ($this->blackListLoaded) {
			return $this->blackList;
		}
		$blackList = $this->getBlacklist(true);
		$this->blackList = $blackList;
		$this->blackListLoaded = true;
		return $blackList;
	}

	public function isBlackListed($msg, $groupName)
	{
		$blackList = $this->retrieveBlackList();
		$field = array();
		if (isset($msg['Subject'])) {
			$field[Binaries::BLACKLIST_FIELD_SUBJECT] = $msg['Subject'];
		}

		if (isset($msg['From'])) {
			$field[Binaries::BLACKLIST_FIELD_FROM] = $msg['From'];
		}

		if (isset($msg['Message-ID'])) {
			$field[Binaries::BLACKLIST_FIELD_MESSAGEID] = $msg['Message-ID'];
		}

		$omitBinary = false;

		foreach ($blackList as $blist) {
			if (preg_match('/^' . $blist['groupname'] . '$/i', $groupName)) {
				//blacklist
				if ($blist['optype'] == 1) {
					if (preg_match('/' . $blist['regex'] . '/i', $field[$blist['msgcol']])) {
						$omitBinary = true;
					}
				} else if ($blist['optype'] == 2) {
					if (!preg_match('/' . $blist['regex'] . '/i', $field[$blist['msgcol']])) {
						$omitBinary = true;
					}
				}
			}
		}

		return $omitBinary;
	}

	public function search($search, $limit = 1000, $excludedcats = array())
	{
		$db = $this->db;

		// If the query starts with a ^ it indicates the search is looking for items which start with the term still do the like match, but mandate that all items returned must start with the provided word.
		$words = explode(' ', $search);
		$searchsql = '';
		$intwordcount = 0;
		if (count($words) > 0) {
			$like = 'ILIKE';
			if ($db->dbSystem() == 'mysql') {
				$like = 'LIKE';
			}
			foreach ($words as $word) {
				// See if the first word had a caret, which indicates search must start with term.
				if ($intwordcount == 0 && (strpos($word, '^') === 0)) {
					$searchsql.= sprintf(' AND b.name %s %s', $like, $db->escapeString(substr($word, 1) . '%'));
				} else {
					$searchsql.= sprintf(' AND b.name %s %s', $like, $db->escapeString('%' . $word . '%'));
				}

				$intwordcount++;
			}
		}

		$exccatlist = '';
		if (count($excludedcats) > 0) {
			$exccatlist = ' AND b.categoryid NOT IN (' . implode(',', $excludedcats) . ') ';
		}

		return $db->query(sprintf("SELECT b.*, g.name AS group_name, r.guid, (SELECT COUNT(id) FROM parts p WHERE p.binaryid = b.id) as 'binnum' FROM binaries b INNER JOIN groups g ON g.id = b.groupid LEFT OUTER JOIN releases r ON r.id = b.releaseid WHERE 1=1 %s %s order by DATE DESC LIMIT %d", $searchsql, $exccatlist, $limit));
	}

	public function getForReleaseId($id)
	{
		$db = $this->db;
		return $db->query(sprintf('SELECT binaries.* FROM binaries WHERE releaseid = %d ORDER BY relpart', $id));
	}

	public function getById($id)
	{
		$db = $this->db;
		return $db->queryOneRow(sprintf('SELECT binaries.*, collections.groupid, groups.name AS groupname FROM binaries, collections LEFT OUTER JOIN groups ON collections.groupid = groups.id WHERE binaries.id = %d', $id));
	}

	public function getBlacklist($activeonly = true)
	{
		$db = $this->db;

		$where = '';
		if ($activeonly) {
			$where = ' WHERE binaryblacklist.status = 1 ';
		}

		return $db->query('SELECT binaryblacklist.id, binaryblacklist.optype, binaryblacklist.status, binaryblacklist.description, binaryblacklist.groupname AS groupname, binaryblacklist.regex, groups.id AS groupid, binaryblacklist.msgcol FROM binaryblacklist LEFT OUTER JOIN groups ON groups.name = binaryblacklist.groupname ' . $where . " ORDER BY coalesce(groupname,'zzz')");
	}

	public function getBlacklistByID($id)
	{
		$db = $this->db;
		return $db->queryOneRow(sprintf('SELECT * FROM binaryblacklist WHERE id = %d', $id));
	}

	public function deleteBlacklist($id)
	{
		$db = $this->db;
		return $db->queryExec(sprintf('DELETE FROM binaryblacklist WHERE id = %d', $id));
	}

	public function updateBlacklist($regex)
	{
		$db = $this->db;

		$groupname = $regex['groupname'];
		if ($groupname == '') {
			$groupname = 'null';
		} else {
			$groupname = preg_replace('/a\.b\./i', 'alt.binaries.', $groupname);
			$groupname = sprintf('%s', $db->escapeString($groupname));
		}

		$db->queryExec(sprintf('UPDATE binaryblacklist SET groupname = %s, regex = %s, status = %d, description = %s, optype = %d, msgcol = %d WHERE id = %d ', $groupname, $db->escapeString($regex['regex']), $regex['status'], $db->escapeString($regex['description']), $regex['optype'], $regex['msgcol'], $regex['id']));
	}

	public function addBlacklist($regex)
	{
		$db = $this->db;

		$groupname = $regex['groupname'];
		if ($groupname == '') {
			$groupname = 'null';
		} else {
			$groupname = preg_replace('/a\.b\./i', 'alt.binaries.', $groupname);
			$groupname = sprintf('%s', $db->escapeString($groupname));
		}

		return $db->queryInsert(sprintf('INSERT INTO binaryblacklist (groupname, regex, status, description, optype, msgcol) VALUES (%s, %s, %d, %s, %d, %d)', $groupname, $db->escapeString($regex['regex']), $regex['status'], $db->escapeString($regex['description']), $regex['optype'], $regex['msgcol']));
	}

	public function delete($id)
	{
		$db = $this->db;
		$bins = $db->query(sprintf('SELECT id FROM binaries WHERE collectionid = %d', $id));
		foreach ($bins as $bin) {
			$db->queryExec(sprintf('DELETE FROM parts WHERE binaryid = %d', $bin['id']));
		}
		$db->queryExec(sprintf('DELETE FROM binaries WHERE collectionid = %d', $id));
		$db->queryExec(sprintf('DELETE FROM collections WHERE id = %d', $id));
	}
}
