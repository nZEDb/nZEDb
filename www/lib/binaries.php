<?php
require_once(WWW_DIR.'lib/framework/db.php');
require_once(WWW_DIR.'lib/nntp.php');
require_once(WWW_DIR.'lib/groups.php');
require_once(WWW_DIR.'lib/backfill.php');
require_once(WWW_DIR.'lib/consoletools.php');
require_once(WWW_DIR.'lib/site.php');
require_once(WWW_DIR.'lib/namecleaning.php');
require_once(WWW_DIR.'lib/ColorCLI.php');

class Binaries
{
	const BLACKLIST_FIELD_SUBJECT = 1;
	const BLACKLIST_FIELD_FROM = 2;
	const BLACKLIST_FIELD_MESSAGEID = 3;

	public function Binaries()
	{
		$s = new Sites();
		$site = $s->get();
		$this->compressedHeaders = ($site->compressedheaders == '1') ? true : false;
		$this->messagebuffer = (!empty($site->maxmssgs)) ? $site->maxmssgs : 20000;
		$this->NewGroupScanByDays = ($site->newgroupscanmethod == '1') ? true : false;
		$this->NewGroupMsgsToScan = (!empty($site->newgroupmsgstoscan)) ? $site->newgroupmsgstoscan : 50000;
		$this->NewGroupDaysToScan = (!empty($site->newgroupdaystoscan)) ? $site->newgroupdaystoscan : 3;
		$this->DoPartRepair = ($site->partrepair == '0' || $site->partrepair == '2') ? false : true;
		$this->DoPartRepairMsg = ($site->partrepair == '2') ? false : true;
		$this->partrepairlimit = (!empty($site->maxpartrepair)) ? $site->maxpartrepair : 15000;
		$this->hashcheck = (!empty($site->hashcheck)) ? $site->hashcheck : 0;
		$this->debug = ($site->debuginfo == '0') ? false : true;
		$this->c = new ColorCLI;
		$this->primary = 'green';
		$this->warning = 'red';
		$this->header = 'yellow';

		// Cache of our black/white list.
		$this->blackList = $this->message = array();
		$this->blackListLoaded = false;
	}

	public function updateAllGroups()
	{
		if ($this->hashcheck == 0)
		{
			echo $this->c->setcolor('bold', $this->warning)."We have updated the way collections are created, the collection table has to be updated to use the new changes, if you want to run this now, type 'yes', else type no to see how to run manually.\n".$this->c->rsetcolor();
			if (trim(fgets(fopen('php://stdin', 'r'))) != 'yes')
				exit($this->c->setcolor('bold', $this->primary)."If you want to run this manually, there is a script in misc/testing/DB_scripts/ called reset_Collections.php\n".$this->c->rsetcolor());
			$relss = new Releases(true);
			$relss->resetCollections();
		}
		$groups = new Groups();
		$res = $groups->getActive();
		$counter = 1;

		if ($res)
		{
			$alltime = microtime(true);
			echo $this->c->setcolor('bold', $this->header)."\nUpdating: ".sizeof($res).' group(s) - Using compression? '.(($this->compressedHeaders)?'Yes':'No')."\n".$this->c->rsetcolor();

			foreach($res as $groupArr)
			{
				$this->message = array();
				echo $this->c->setcolor('bold', $this->header)."\nStarting group ".$counter.' of '.sizeof($res)."\n".$this->c->rsetcolor();
				$this->updateGroup($groupArr);
				$counter++;
			}

			echo $this->c->setcolor('bold', $this->primary).'Updating completed in '.number_format(microtime(true) - $alltime, 2)." seconds\n".$this->c->rsetcolor();
		}
		else
			echo $this->c->setcolor('bold', $this->warning)."No groups specified. Ensure groups are added to nZEDb's database for updating.\n".$this->c->rsetcolor();
	}

	public function updateGroup($groupArr)
	{
		$this->startGroup = microtime(true);
		echo $this->c->setcolor('bold', $this->primary).'Processing '.$groupArr['name']."\n".$this->c->rsetcolor();

		// Select the group.
		$nntp = new Nntp();
		if ($nntp->doConnect() === false)
			return;

		$data = $nntp->selectGroup($groupArr['name']);

		// Attempt to reconnect if there is an error.
		if(PEAR::isError($data))
		{
			$data = $nntp->dataError($nntp, $groupArr['name']);
			if ($data === false)
				return;
		}

		// Attempt to repair any missing parts before grabbing new ones.
		if ($this->DoPartRepair)
		{
			echo $this->c->setcolor('bold', $this->primary)."Part repair enabled. Checking for missing parts.\n".$this->c->rsetcolor();
			$this->partRepair($nntp, $groupArr);
		}
		else if ($this->DoPartRepairMsg)
			echo $this->c->setcolor('bold', $this->primary)."Part repair disabled by user.\n".$this->c->rsetcolor();

		// Get first and last part numbers from newsgroup.
		$last = $grouplast = $data['last'];

		$backfill = new Backfill();
		$db = new DB();
		// For new newsgroups - determine here how far you want to go back.
		if ($groupArr['last_record'] == 0)
		{
			if ($this->NewGroupScanByDays)
			{
				$first = $backfill->daytopost($nntp, $groupArr['name'], $this->NewGroupDaysToScan, true);
				if ($first == '')
				{
					$nntp->doQuit();
					echo $this->c->setcolor('bold', $this->warning)."Skipping group: {$groupArr['name']}\n".$this->c->rsetcolor();
					return;
				}
			}
			else
			{
				if ($data['first'] > ($data['last'] - $this->NewGroupMsgsToScan))
					$first = $data['first'];
				else
					$first = $data['last'] - $this->NewGroupMsgsToScan;
			}
			// In case postdate doesn't get a date.
			if (is_null($groupArr['first_record_postdate']) || $groupArr['first_record_postdate'] == 'NULL')
				$first_record_postdate = time();
			else
				$first_record_postdate = strtotime($groupArr['first_record_postdate']);

			$newdate = $backfill->postdate($nntp, $first, false, $groupArr['name'], true);

			if ($newdate !== false)
				$first_record_postdate = $newdate;
			$db->queryExec(sprintf('UPDATE groups SET first_record = %s, first_record_postdate = %s WHERE id = %d', $first, $db->from_unixtime($db->escapeString($first_record_postdate)), $groupArr['id']));
		}
		else
			$first = $groupArr['last_record'];

		// Generate last record postdate. In case there are missing articles in the loop it can use this (the loop will update this if it doesnt fail).
		if (is_null($groupArr['last_record_postdate']) || $groupArr['last_record_postdate'] == 'NULL' || $groupArr['last_record'] == '0')
			$lastr_postdate = time();
		else
		{
			$lastr_postdate = strtotime($groupArr['last_record_postdate']);
			$newdatel = $backfill->postdate($nntp, $groupArr['last_record'], false, $groupArr['name'], true);
			if ($groupArr['last_record'] != 0 && $newdatel !== false)
				$lastr_postdate = $newdatel;
		}

		// Generate postdates for first records, for those that upgraded.
		if (is_null($groupArr['first_record_postdate']) || $groupArr['first_record_postdate'] == 'NULL' || $groupArr['first_record'] == '0')
			$first_record_postdate = time();
		else
		{
			$first_record_postdate = strtotime($groupArr['first_record_postdate']);
			$newdate = $backfill->postdate($nntp, $groupArr['first_record'], false, $groupArr['name'], true);
			if ($groupArr['first_record'] != 0 && $newdate !== false)
				$first_record_postdate = $newdate;
		}
		$db->queryExec(sprintf('UPDATE groups SET first_record_postdate = %s, last_record_postdate = %s WHERE id = %d', $db->from_unixtime($first_record_postdate), $db->from_unixtime($lastr_postdate), $groupArr['id']));

		// Calculate total number of parts.
		$total = $grouplast - $first;

		// If total is bigger than 0 it means we have new parts in the newsgroup.
		if($total > 0)
		{
			echo $this->c->setcolor('bold', $this->primary).'Group '.$data['group'].' has '.number_format($total)." new articles.\nServer oldest: ".number_format($data['first']).' Server newest: '.number_format($data['last']).' Local newest: '.number_format($groupArr['last_record'])."\n\n".$this->c->rsetcolor();

			if ($groupArr['last_record'] == 0)
				echo $this->c->setcolor('bold', $this->primary).'New group starting with '.(($this->NewGroupScanByDays) ? $this->NewGroupDaysToScan.' days' : number_format($this->NewGroupMsgsToScan).' messages')." worth.\n".$this->c->rsetcolor();

			$done = false;
			// Get all the parts (in portions of $this->messagebuffer to not use too much memory).
			while ($done === false)
			{
				$this->startLoop = microtime(true);

				if ($total > $this->messagebuffer)
				{
					if ($first + $this->messagebuffer > $grouplast)
						$last = $grouplast;
					else
						$last = $first + $this->messagebuffer;
				}
				$first++;
				echo $this->c->setcolor('bold', $this->primary)."\nGetting ".number_format($last-$first+1).' articles ('.number_format($first).' to '.number_format($last).') from '.$data['group']." - (".number_format($grouplast - $last)." articles in queue).\n".$this->c->rsetcolor();
				flush();

				// Get article headers from newsgroup. Let scan deal with nntp connection, else compression fails after first grab
				$lastId = $this->scan(null, $groupArr, $first, $last);
				// Scan failed - skip group.
				if ($lastId != false)
					$lastId = $last;
				else
				{
					$nntp->doQuit();
					return;
				}

				$newdatek = $backfill->postdate($nntp, $last, false, $groupArr['name'], true);
				if ($newdatek !== false)
					$lastr_date = $newdatek;

				$db->queryExec(sprintf('UPDATE groups SET last_record = %s, last_record_postdate = %s, last_updated = NOW() WHERE id = %d', $db->escapeString($lastId), $db->from_unixtime($lastr_postdate), $groupArr['id']));
				if ($this->debug)
					printf('UPDATE groups SET last_record = %s, last_record_postdate = %s, last_updated = NOW() WHERE id = %d'."\n\n\n", $db->escapeString($lastId), $db->from_unixtime($lastr_postdate), $groupArr['id']);

				if ($last == $grouplast)
					$done = true;
				else
				{
					$last = $lastId;
					$first = $last;
				}
			}
			$nntp->doQuit();
			// Set group's last postdate.
			$db->queryExec(sprintf('UPDATE groups SET last_record_postdate = %s, last_updated = NOW() WHERE id = %d', $db->from_unixtime($lastr_postdate), $groupArr['id']));
			$timeGroup = number_format(microtime(true) - $this->startGroup, 2);
			echo $this->c->setcolor('bold', $this->primary).$data['group'].' processed in '.$timeGroup." seconds.\n\n".$this->c->rsetcolor();
		}
		else
			echo $this->c->setcolor('bold', $this->primary).'No new articles for '.$data['group'].' (first '.number_format($first).' last '.number_format($last).' total '.number_format($total).') grouplast '.number_format($groupArr['last_record'])."\n".$this->c->rsetcolor();
	}

	public function scan($nntp, $groupArr, $first, $last, $type='update')
	{
		$namecleaning = new nameCleaning();
		$s = new Sites;
		$site = $s->get();
		$tmpPath = $site->tmpunrarpath.'/';

		if ($this->debug)
			$consoletools = new ConsoleTools();

		$this->startHeaders = microtime(true);
		$this->startLoop = microtime(true);

		// Select the group.
		if (!isset($nntp))
			$nntp = new Nntp();
		if ($nntp->doConnect() === false)
			return;
		$data = $nntp->selectGroup($groupArr['name']);
		// Attempt to reconnect if there is an error.
		if(PEAR::isError($data))
		{
			$data = $nntp->dataError($nntp, $groupArr['name']);
			if ($data === false)
				return;
		}

		// Download the headers.
		$msgs = $nntp->getOverview($first."-".$last, true, false);
		if($type != 'partrepair' && PEAR::isError($msgs))
		{
			// This is usually a compression error, so lets try disabling compression.
			$nntp->doQuit();
			if ($nntp->doConnectNC() === false)
				return;

			$nntp->selectGroup($groupArr['name']);
			$msgs = $nntp->getOverview($first.'-'.$last, true, false);
			if(PEAR::isError($msgs))
			{
				$nntp->doQuit();
				echo $this->c->setcolor('bold', $this->warning)."Error {$msgs->code}: {$msgs->message}\nSkipping group: ${groupArr['name']}\033[0m\n".$this->c->rsetcolor();
				return;
			}
		}
		$nntp->doQuit();
		$timeHeaders = number_format(microtime(true) - $this->startHeaders, 2);

		$this->startCleaning = microtime(true);
		$rangerequested = range($first, $last);
		$msgsreceived = $msgsblacklisted = $msgsignored = $msgsnotinserted = array();
		$db = new DB();
		if (is_array($msgs))
		{
			// For looking at the difference between $subject/$cleansubject and to show non yEnc posts.
			if ($this->debug)
				$colnames = $orignames = $notyenc = array();

			// Loop articles, figure out files/parts.
			foreach($msgs AS $msg)
			{
				if (!isset($msg['Number']))
					continue;

				if (isset($msg['Bytes']))
					$bytes = $msg['Bytes'];
				else
					$bytes = $msg[':bytes'];

				$msgsreceived[] = $msg['Number'];

				// Not a binary post most likely.. continue.
				if (!isset($msg['Subject']) || !preg_match('/(.+yEnc) \((\d+)\/(\d+)\)$/', $msg['Subject'], $matches))
				{
					// Uncomment this and the print_r about 80 lines down to see which posts are not yenc.
					/*if ($this->debug)
					{
						preg_match('/(.+)\(\d+\/\d+\)$/i', $msg['Subject'], $ny);
						if(!in_array($ny[1], $notyenc))
							$notyenc[] = $ny[1];
					}*/
					$msgsignored[] = $msg['Number'];
					continue;
				}

				// Filter subject based on black/white list.
				if ($this->isBlackListed($msg, $groupArr['name']))
				{
					$msgsblacklisted[] = $msg['Number'];
					continue;
				}

				// Attempt to find the file count. If it is not found, set it to 0.
				$nofiles = false;
				$partless = $matches[1];
				if (!preg_match('/(\[|\(|\s)(\d{1,5})(\/|(\s|_)of(\s|_)|\-)(\d{1,5})(\]|\)|\s|$|:)/i', $partless, $filecnt))
				{
					$filecnt[2] = $filecnt[6] = 0;
					$nofiles = true;
				}

				if(is_numeric($matches[2]) && is_numeric($matches[3]))
				{
					array_map('trim', $matches);
					// Inserted into the collections table as the subject.
					$subject = utf8_encode(trim($partless));

					// Used for the sha1 hash (see below).
					$cleansubject = $namecleaning->collectionsCleaner($subject, $groupArr['name'], $nofiles);

					/*
					$ncarr = $namecleaning->collectionsCleaner($subject, $groupArr['name'], $nofiles);
					$cleansubject = $ncarr['hash'];
					*/

					// For looking at the difference between $subject and $cleansubject.
					if ($this->debug)
					{
						if (!in_array($cleansubject, $colnames))
						{
							/* Uncomment this to only show articles matched by generic function of namecleaning (might show some that match by collectionsCleaner, but rare). Helps when making regex.

							if (preg_match('/yEnc$/', $cleansubject))
							{
								$colnames[] = $cleansubject;
								$orignames[] = $msg['Subject'];
							}
							*/

							/*If you uncommented the above, comment following 2 lines..*/
							$colnames[] = $cleansubject;
							$orignames[] = $msg['Subject'];
						}
					}

					// Set up the info for inserting into parts/binaries/collections tables.
					if(!isset($this->message[$subject]))
					{
						$this->message[$subject] = $msg;
						$this->message[$subject]['MaxParts'] = (int)$matches[3];
						$this->message[$subject]['Date'] = strtotime($msg['Date']);
						// (hash) Groups articles together when forming the release/nzb.
						$this->message[$subject]['CollectionHash'] = sha1($cleansubject.$msg['From'].$groupArr['id'].$filecnt[6]);
						$this->message[$subject]['MaxFiles'] = (int)$filecnt[6];
						$this->message[$subject]['File'] = (int)$filecnt[2];
					}
					if($site->grabnzbs != 0 && preg_match('/".+?\.nzb" yEnc$/', $subject))
					{
						$ckmsg = $db->queryOneRow(sprintf('SELECT message_id FROM nzbs WHERE message_id = %s', $db->escapeString(substr($msg['Message-ID'],1,-1))));
						if (!isset($ckmsg['message_id']))
						{
							$db->queryInsert(sprintf('INSERT INTO nzbs (message_id, groupname, subject, collectionhash, filesize, partnumber, totalparts, postdate, dateadded) VALUES (%s, %s, %s, %s, %d, %d, %d, %s, NOW())', $db->escapeString(substr($msg['Message-ID'],1,-1)), $db->escapeString($groupArr['name']), $db->escapeString(substr($subject,0,255)), $db->escapeString($this->message[$subject]['CollectionHash']), (int)$bytes, (int)$matches[2], $this->message[$subject]['MaxParts'], $db->from_unixtime($this->message[$subject]['Date'])));
							$updatenzb = $db->queryExec(sprintf('UPDATE nzbs SET dateadded = NOW() WHERE collectionhash = %s', $db->escapeString($this->message[$subject]['CollectionHash'])));
						}
					}

					if((int)$matches[2] > 0)
						$this->message[$subject]['Parts'][(int)$matches[2]] = array('Message-ID' => substr($msg['Message-ID'], 1, -1), 'number' => $msg['Number'], 'part' => (int)$matches[2], 'size' => $bytes);
				}
			}

			// Uncomment this to see which articles are not yEnc.
			/*if ($this->debug && count($notyenc) > 1)
				print_r($notyenc);*/
			// For looking at the difference between $subject and $cleansubject.
			if ($this->debug && count($colnames) > 1 && count($orignames) > 1)
			{
				$arr = array_combine($colnames, $orignames);
				ksort($arr);
				print_r($arr);
			}
			$timeCleaning = number_format(microtime(true) - $this->startCleaning, 2);

			unset($msg,$msgs);
			$maxnum = $last;
			$rangenotreceived = array_diff($rangerequested, $msgsreceived);

			if ($type != 'partrepair')
				echo $this->c->setcolor('bold', $this->primary).'Received '.number_format(sizeof($msgsreceived)).' articles of '.(number_format($last-$first+1)).' requested, '.sizeof($msgsblacklisted).' blacklisted, '.sizeof($msgsignored)." not yEnc.\n".$this->c->rsetcolor();

			if (sizeof($rangenotreceived) > 0)
			{
				switch($type)
				{
					case 'backfill':
						// Don't add missing articles.
						break;
					case 'partrepair':
					case 'update':
					default:
						if ($this->DoPartRepair)
							$this->addMissingParts($rangenotreceived, $groupArr['id']);
					break;
				}
				if ($type != 'partrepair')
					echo $this->c->setcolor('bold', $this->primary).'Server did not return '.sizeof($rangenotreceived)." articles.\n".$this->c->rsetcolor();
			}

			$this->startUpdate = microtime(true);
			if(isset($this->message) && count($this->message))
			{
				$maxnum = $first;
				$pBinaryID = $pNumber = $pMessageID = $pPartNumber = $pSize = 1;
				// Insert collections, binaries and parts into database. When collection exists, only insert new binaries, when binary already exists, only insert new parts.
				if ($insPartsStmt = $db->Prepare('INSERT INTO parts (binaryid, number, messageid, partnumber, size) VALUES (?, ?, ?, ?, ?)'))
				{
					$insPartsStmt->bindParam(1, $pBinaryID, PDO::PARAM_INT);
					$insPartsStmt->bindParam(2, $pNumber, PDO::PARAM_INT);
					$insPartsStmt->bindParam(3, $pMessageID, PDO::PARAM_STR);
					$insPartsStmt->bindParam(4, $pPartNumber, PDO::PARAM_INT);
					$insPartsStmt->bindParam(5, $pSize, PDO::PARAM_INT);
				}
				else
					exit("Couldn't prepare parts insert statement!\n");

				$lastCollectionHash = $lastBinaryHash = "";
				$lastCollectionID = $lastBinaryID = -1;

				foreach($this->message AS $subject => $data)
				{
					if(isset($data['Parts']) && count($data['Parts']) > 0 && $subject != '')
					{
						$db->beginTransaction();
						$collectionHash = $data['CollectionHash'];
						if ($lastCollectionHash == $collectionHash)
							$collectionID = $lastCollectionID;
						else
						{
							$lastCollectionHash = $collectionHash;
							$lastBinaryHash = '';
							$lastBinaryID = -1;

							$cres = $db->queryOneRow(sprintf('SELECT id FROM collections WHERE collectionhash = %s', $db->escapeString($collectionHash)));
							if(!$cres)
							{
								// added utf8_encode on fromname, seems some foreign groups contains characters that were not escaping properly
								$csql = sprintf('INSERT INTO collections (subject, fromname, date, xref, groupid, totalfiles, collectionhash, dateadded) VALUES (%s, %s, %s, %s, %d, %d, %s, NOW())', $db->escapeString(substr($subject,0,255)), $db->escapeString($db->escapeString(utf8_encode($data['From']))), $db->from_unixtime($data['Date']), $db->escapeString(substr($data['Xref'],0,255)), $groupArr['id'], $data['MaxFiles'], $db->escapeString($collectionHash));
								$collectionID = $db->queryInsert($csql);
							}
							else
							{
								$collectionID = $cres['id'];
								//Update the collection table with the last seen date for the collection. This way we know when the last time a person posted for this hash.
								$db->queryExec(sprintf('UPDATE collections set dateadded = NOW() WHERE id = %s', $collectionID));
							}

							$lastCollectionID = $collectionID;
						}
						$binaryHash = md5($subject.$data['From'].$groupArr['id']);

						if ($lastBinaryHash == $binaryHash)
							$binaryID = $lastBinaryID;
						else
						{
							$lastBinaryHash = $binaryHash;

							$bres = $db->queryOneRow(sprintf('SELECT id FROM binaries WHERE binaryhash = %s', $db->escapeString($binaryHash)));
							if(!$bres)
							{
								$bsql = sprintf('INSERT INTO binaries (binaryhash, name, collectionid, totalparts, filenumber) VALUES (%s, %s, %d, %s, %s)', $db->escapeString($binaryHash), $db->escapeString($subject), $collectionID, $db->escapeString($data['MaxParts']), $db->escapeString(round($data['File'])));
								$binaryID = $db->queryInsert($bsql);
							}
							else
								$binaryID = $bres['id'];

							$lastBinaryID = $binaryID;
						}

						foreach($data['Parts'] AS $partdata)
						{
							$pBinaryID = $binaryID;
							$pMessageID = $partdata['Message-ID'];
							$pNumber = $partdata['number'];
							$pPartNumber = round($partdata['part']);
							$maxnum = ($partdata['number'] > $maxnum) ? $partdata['number'] : $maxnum;
							if (is_numeric($partdata['size']))
								$pSize = $partdata['size'];
							if (!$insPartsStmt->execute())
								$msgsnotinserted[] = $partdata['number'];
						}
						$db->Commit();
					}
				}
				if (sizeof($msgsnotinserted) > 0)
				{
					echo $this->c->setcolor('bold', $this->warning)."WARNING: ".sizeof($msgsnotinserted)." parts failed to insert.".$this->c->rsetcolor();
					if ($this->DoPartRepair)
						$this->addMissingParts($msgsnotinserted, $groupArr['id']);
				}
			}
			$timeUpdate = number_format(microtime(true) - $this->startUpdate, 2);
			$timeLoop = number_format(microtime(true)-$this->startLoop, 2);

			if ($type != 'partrepair')
				echo $this->c->setcolor('bold', $this->primary).$timeHeaders.'s to download articles, '.$timeCleaning.'s to process articles, '.$timeUpdate.'s to insert articles, '.$timeLoop."s total.\n".$this->c->rsetcolor();

			unset($this->message, $data);
			return $maxnum;
		}
		else
		{
			if ($type != 'partrepair')
			{
				echo $this->c->setcolor('bold', $this->warning)."Error: Can't get parts from server (msgs not array).\nSkipping group: ${groupArr['name']}\n".$this->c->rsetcolor();
				return false;
			}
		}
	}

	public function partRepair($nntp, $groupArr, $groupID='', $partID='')
	{
		$groups = new Groups();

		// Get all parts in partrepair table.
		$db = new DB();
		if ($partID == '')
			$missingParts = $db->query(sprintf("SELECT * FROM partrepair WHERE groupid = %d AND attempts < 5 ORDER BY numberid ASC LIMIT %d", $groupArr['id'], $this->partrepairlimit));
		else
		{
			$groupArr = $groups->getByID($groupID);
			$missingParts = array(array('numberid' => $partID, 'groupid' => $groupArr['id']));
		}
		$partsRepaired = $partsFailed = 0;

		if (sizeof($missingParts) > 0)
		{
			if ($partID == '')
				echo $this->c->setcolor('bold', $this->primary).'Attempting to repair '.sizeof($missingParts)." parts.\n".$this->c->rsetcolor();

			// Loop through each part to group into ranges.
			$ranges = array();
			$lastnum = $lastpart = 0;
			foreach($missingParts as $part)
			{
				if (($lastnum+1) == $part['numberid'])
					$ranges[$lastpart] = $part['numberid'];
				else
				{
					$lastpart = $part['numberid'];
					$ranges[$lastpart] = $part['numberid'];
				}
				$lastnum = $part['numberid'];
			}

			$num_attempted = 0;
			$consoleTools = new ConsoleTools();

			// Download missing parts in ranges.
			foreach($ranges as $partfrom=>$partto)
			{
				$this->startLoop = microtime(true);

				$num_attempted += $partto - $partfrom + 1;
				if ($partID == '')
				{
					echo "\n";
					$consoleTools->overWrite('Attempting repair: '.$consoleTools->percentString($num_attempted,sizeof($missingParts)).': '.$partfrom.' to '.$partto);
				}
				else
					echo $this->c->setcolor('bold', $this->primary).'Attempting repair: '.$partfrom."\n".$this->c->rsetcolor();

				// Get article from newsgroup.
				$this->scan($nntp, $groupArr, $partfrom, $partto, 'partrepair');

				// Check if the articles were added.
				$articles = implode(',', range($partfrom, $partto));
				$sql = sprintf('SELECT pr.id, pr.numberid, p.number from partrepair pr LEFT JOIN parts p ON p.number = pr.numberid WHERE pr.groupid=%d AND pr.numberid IN (%s) ORDER BY pr.numberid ASC', $groupArr['id'], $articles);

				$result = $db->query($sql);
				foreach ($result as $r)
				{
					if (isset($r['number']) && $r['number'] == $r['numberid'])
					{
						$partsRepaired++;

						// Article was added, delete from partrepair.
						$db->queryExec(sprintf('DELETE FROM partrepair WHERE id=%d', $r['id']));
					}
					else
					{
						$partsFailed++;

						// Article was not added, increment attempts.
						$db->queryExec(sprintf('UPDATE partrepair SET attempts=attempts+1 WHERE id=%d', $r['id']));
					}
				}
			}

			if ($partID == '')
				echo "\n";
			echo $this->c->setcolor('bold', $this->primary).$partsRepaired." parts repaired.\n".$this->c->rsetcolor();
		}

		// Remove articles that we cant fetch after 5 attempts.
		$db->queryExec(sprintf('DELETE FROM partrepair WHERE attempts >= 5 AND groupid = %d', $groupArr['id']));

	}

	private function addMissingParts($numbers, $groupID)
	{
		$db = new DB();
		$insertStr = 'INSERT INTO partrepair (numberid, groupid) VALUES ';
		foreach($numbers as $number)
			$insertStr .= sprintf('(%d, %d), ', $number, $groupID);

		$insertStr = substr($insertStr, 0, -2);
		if ($db->dbSystem() == 'mysql')
		{
			$insertStr .= ' ON DUPLICATE KEY UPDATE attempts=attempts+1';
			return $db->queryInsert($insertStr);
		}
		else
		{
			$id = $db->queryInsert($insertStr);
			$db->Exec('UPDATE partrepair SET attempts = attempts+1 WHERE id = '.$id);
			return $id;
		}
	}

	public function retrieveBlackList()
	{
		if ($this->blackListLoaded) { return $this->blackList; }
		$blackList = $this->getBlacklist(true);
		$this->blackList = $blackList;
		$this->blackListLoaded = true;
		return $blackList;
	}

	public function isBlackListed($msg, $groupName)
	{
		$blackList = $this->retrieveBlackList();
		$field = array();
		if (isset($msg['Subject']))
			$field[Binaries::BLACKLIST_FIELD_SUBJECT] = $msg['Subject'];

		if (isset($msg['From']))
			$field[Binaries::BLACKLIST_FIELD_FROM] = $msg['From'];

		if (isset($msg['Message-ID']))
			$field[Binaries::BLACKLIST_FIELD_MESSAGEID] = $msg['Message-ID'];

		$omitBinary = false;

		foreach ($blackList as $blist)
		{
			if (preg_match('/^'.$blist['groupname'].'$/i', $groupName))
			{
				//blacklist
				if ($blist['optype'] == 1)
				{
					if (preg_match('/'.$blist['regex'].'/i', $field[$blist['msgcol']]))
						$omitBinary = true;
				}
				else if ($blist['optype'] == 2)
				{
					if (!preg_match('/'.$blist['regex'].'/i', $field[$blist['msgcol']]))
						$omitBinary = true;
				}
			}
		}

		return $omitBinary;
	}

	public function search($search, $limit=1000, $excludedcats=array())
	{
		$db = new DB();

		// If the query starts with a ^ it indicates the search is looking for items which start with the term still do the like match, but mandate that all items returned must start with the provided word.
		$words = explode(' ', $search);
		$searchsql = '';
		$intwordcount = 0;
		if (count($words) > 0)
		{
			$like = 'ILIKE';
			if ($db->dbSystem() == 'mysql')
				$like = 'LIKE';
			foreach ($words as $word)
			{
				// See if the first word had a caret, which indicates search must start with term.
				if ($intwordcount == 0 && (strpos($word, '^') === 0))
					$searchsql.= sprintf(' AND b.name %s %s', $like, $db->escapeString(substr($word, 1).'%'));
				else
					$searchsql.= sprintf(' AND b.name %s %s', $like, $db->escapeString('%'.$word.'%'));

				$intwordcount++;
			}
		}

		$exccatlist = '';
		if (count($excludedcats) > 0)
			$exccatlist = ' AND b.categoryid NOT IN ('.implode(',', $excludedcats).') ';

		return $db->query(sprintf("SELECT b.*, g.name AS group_name, r.guid, (SELECT COUNT(id) FROM parts p WHERE p.binaryid = b.id) as 'binnum' FROM binaries b INNER JOIN groups g ON g.id = b.groupid LEFT OUTER JOIN releases r ON r.id = b.releaseid WHERE 1=1 %s %s order by DATE DESC LIMIT %d", $searchsql, $exccatlist, $limit));
	}

	public function getForReleaseId($id)
	{
		$db = new DB();
		return $db->query(sprintf('SELEC binaries.* FROM binaries WHERE releaseid = %d ORDER BY relpart', $id));
	}

	public function getById($id)
	{
		$db = new DB();
		return $db->queryOneRow(sprintf('SELECT binaries.*, collections.groupid, groups.name AS groupname FROM binaries, collections LEFT OUTER JOIN groups ON collections.groupid = groups.id WHERE binaries.id = %d', $id));
	}

	public function getBlacklist($activeonly=true)
	{
		$db = new DB();

		$where = '';
		if ($activeonly)
			$where = ' WHERE binaryblacklist.status = 1 ';

		return $db->query('SELECT binaryblacklist.id, binaryblacklist.optype, binaryblacklist.status, binaryblacklist.description, binaryblacklist.groupname AS groupname, binaryblacklist.regex, groups.id AS groupid, binaryblacklist.msgcol FROM binaryblacklist LEFT OUTER JOIN groups ON groups.name = binaryblacklist.groupname '.$where." ORDER BY coalesce(groupname,'zzz')");
	}

	public function getBlacklistByID($id)
	{
		$db = new DB();
		return $db->queryOneRow(sprintf('SELECT * FROM binaryblacklist WHERE id = %d', $id));
	}

	public function deleteBlacklist($id)
	{
		$db = new DB();
		return $db->queryExec(sprintf('DELETE FROM binaryblacklist WHERE id = %d', $id));
	}

	public function updateBlacklist($regex)
	{
		$db = new DB();

		$groupname = $regex['groupname'];
		if ($groupname == '')
			$groupname = 'null';
		else
		{
			$groupname = preg_replace('/a\.b\./i', 'alt.binaries.', $groupname);
			$groupname = sprintf('%s', $db->escapeString($groupname));
		}

		$db->queryExec(sprintf('UPDATE binaryblacklist SET groupname = %s, regex = %s, status = %d, description = %s, optype = %d, msgcol = %d WHERE id = %d ', $groupname, $db->escapeString($regex['regex']), $regex['status'], $db->escapeString($regex['description']), $regex['optype'], $regex['msgcol'], $regex['id']));
	}

	public function addBlacklist($regex)
	{
		$db = new DB();

		$groupname = $regex['groupname'];
		if ($groupname == '')
			$groupname = 'null';
		else
		{
			$groupname = preg_replace('/a\.b\./i', 'alt.binaries.', $groupname);
			$groupname = sprintf('%s', $db->escapeString($groupname));
		}

		return $db->queryInsert(sprintf('INSERT INTO binaryblacklist (groupname, regex, status, description, optype, msgcol) VALUES (%s, %s, %d, %s, %d, %d)', $groupname, $db->escapeString($regex['regex']), $regex['status'], $db->escapeString($regex['description']), $regex['optype'], $regex['msgcol']));
	}

	public function delete($id)
	{
		$db = new DB();
		$bins = $db->query(sprintf('SELECT id FROM binaries WHERE collectionid = %d', $id));
		foreach ($bins as $bin)
			$db->queryExec(sprintf('DELETE FROM parts WHERE binaryid = %d', $bin['id']));
		$db->queryExec(sprintf('DELETE FROM binaries WHERE collectionid = %d', $id));
		$db->queryExec(sprintf('DELETE FROM collections WHERE id = %d', $id));
	}
}
