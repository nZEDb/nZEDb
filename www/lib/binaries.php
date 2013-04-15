<?php
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/nntp.php");
require_once(WWW_DIR."/lib/groups.php");
require_once(WWW_DIR."/lib/backfill.php");
require_once(WWW_DIR."/lib/consoletools.php");
require_once(WWW_DIR."/lib/site.php");

class Binaries
{	
	const BLACKLIST_FIELD_SUBJECT = 1;
	const BLACKLIST_FIELD_FROM = 2;
	const BLACKLIST_FIELD_MESSAGEID = 3;

	function Binaries() 
	{
		$this->n = "\n";
			
		$s = new Sites();
		$site = $s->get();
		$this->compressedHeaders = ($site->compressedheaders == "1") ? true : false;	
		$this->messagebuffer = (!empty($site->maxmssgs)) ? $site->maxmssgs : 20000;
		$this->NewGroupScanByDays = ($site->newgroupscanmethod == "1") ? true : false;
		$this->NewGroupMsgsToScan = (!empty($site->newgroupmsgstoscan)) ? $site->newgroupmsgstoscan : 50000;
		$this->NewGroupDaysToScan = (!empty($site->newgroupdaystoscan)) ? $site->newgroupdaystoscan : 3;
		
		$this->blackList = array(); //cache of our black/white list
		$this->message = array();
	}
	
	function updateAllGroups() 
	{
		$n = $this->n;
		$groups = new Groups;
		$res = $groups->getActive();
		
		$s = new Sites();

		if ($res)
		{	
			$alltime = microtime(true);	
			echo $n.'Updating: '.sizeof($res).' group(s) - Using compression? '.(($this->compressedHeaders)?'Yes':'No').$n;
			
			$nntp = new Nntp();
			$nntp->doConnect();
			
			foreach($res as $groupArr) 
			{
				$this->message = array();
				$this->updateGroup($nntp, $groupArr);
			}
			
			$nntp->doQuit();
			echo 'Updating completed in '.number_format(microtime(true) - $alltime, 2).' seconds'.$n;
		}
		else
		{
			echo "No groups specified. Ensure groups are added to newznab's database for updating.".$n;
		}		
	}
	
	function updateGroup($nntp, $groupArr)
	{
		$db = new DB();
		$backfill = new Backfill();
		$n = $this->n;
		$this->startGroup = microtime(true);
		
		echo 'Processing '.$groupArr['name'].$n;
		
		// Connect to server
		$data = $nntp->selectGroup($groupArr['name']);
		if (PEAR::isError($data))
		{
			echo "Could not select group (bad name?): {$groupArr['name']}".$n;
			return;
		}
		
		//Attempt to repair any missing parts before grabbing new ones
		$this->partRepair($nntp, $groupArr);

		//Get first and last part numbers from newsgroup
		$last = $grouplast = $data['last'];
		
		// For new newsgroups - determine here how far you want to go back.
		if ($groupArr['last_record'] == 0)
		{
			if ($this->NewGroupScanByDays) 
			{
				$first = $backfill->daytopost($nntp, $groupArr['name'], $this->NewGroupDaysToScan, true);
				if ($first == '')
				{
					echo "Skipping group: {$groupArr['name']}".$n;
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
			$first_record_postdate = $backfill->postdate($nntp, $first, false);
			$db->query(sprintf("UPDATE groups SET first_record = %s, first_record_postdate = FROM_UNIXTIME(".$first_record_postdate.") WHERE ID = %d", $db->escapeString($first), $groupArr['ID']));
		}
		else
		{
			$first = $groupArr['last_record'] + 1;
		}
		
		// Generate postdates for first and last records, for those that upgraded
		if ((is_null($groupArr['first_record_postdate']) || is_null($groupArr['last_record_postdate'])) && ($groupArr['last_record'] != "0" && $groupArr['first_record'] != "0"))
			 $db->query(sprintf("UPDATE groups SET first_record_postdate = FROM_UNIXTIME(".$backfill->postdate($nntp,$groupArr['first_record'],false)."), last_record_postdate = FROM_UNIXTIME(".$backfill->postdate($nntp,$groupArr['last_record'],false).") WHERE ID = %d", $groupArr['ID']));

		// Deactivate empty groups
		if (($data['last'] - $data['first']) <= 5)
			$db->query(sprintf("UPDATE groups SET active = %s, last_updated = now() WHERE ID = %d", $db->escapeString('0'), $groupArr['ID']));
		
		// Calculate total number of parts
		$total = $grouplast - $first + 1;
		
		// If total is bigger than 0 it means we have new parts in the newsgroup
		if($total > 0)
		{
			echo "Group ".$data["group"]." has ".number_format($total)." new articles.".$n;
			echo "Server oldest: ".$data['first']." Server newest: ".$data['last']." Local newest: ".$groupArr['last_record'].$n.$n;
			if ($groupArr['last_record'] == 0)
				echo "New group starting with ".(($this->NewGroupScanByDays) ? $this->NewGroupDaysToScan." days" : $this->NewGroupMsgsToScan." messages")." worth.".$n;
			
			$done = false;

			// Get all the parts (in portions of $this->messagebuffer to not use too much memory)
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
				
				echo "Getting ".number_format($last-$first+1)." articles (".$first." to ".$last.") - ".number_format($grouplast - $last)." in queue".$n;
				flush();
				
				//get headers from newsgroup
				$lastId = $this->scan($nntp, $groupArr, $first, $last);
				if ($lastId === false)
				{
					//scan failed - skip group
					return;
				}
				$db->query(sprintf("UPDATE groups SET last_record = %s, last_updated = now() WHERE ID = %d", $db->escapeString($lastId), $groupArr['ID']));
				
				if ($last == $grouplast)
					$done = true;
				else
					$last = $lastId;
					$first = $last + 1;
			}
			
			$last_record_postdate = $backfill->postdate($nntp,$last,false);
			$db->query(sprintf("UPDATE groups SET last_record_postdate = FROM_UNIXTIME(".$last_record_postdate."), last_updated = now() WHERE ID = %d", $groupArr['ID']));	//Set group's last postdate
			$timeGroup = number_format(microtime(true) - $this->startGroup, 2);
			echo "Group processed in $timeGroup seconds $n $n";
		}
		else
		{
			echo "No new articles for ".$data["group"]." (first $first last $last total $total) grouplast ".$groupArr['last_record'].$n.$n;

		}
	}
	
	function scan($nntp, $groupArr, $first, $last, $type='update')
	{
		$db = new Db();
		$n = $this->n;
		$this->startHeaders = microtime(true);
		
		if ($this->compressedHeaders)
			$msgs = $nntp->getXOverview($first."-".$last, true, false);
		else
			$msgs = $nntp->getOverview($first."-".$last, true, false);
		
		if (PEAR::isError($msgs) && $msgs->code == 400)
		{
			echo "NNTP connection timed out. Reconnecting...$n";
			$nntp->doConnect();
			$nntp->selectGroup($groupArr['name']);
			if ($this->compressedHeaders)
				$msgs = $nntp->getXOverview($first."-".$last, true, false);
			else
				$msgs = $nntp->getOverview($first."-".$last, true, false);
		}
		
		$rangerequested = range($first, $last);
		$msgsreceived = array();
		$msgsblacklisted = array();
		$msgsignored = array();
		$msgsinserted = array();
		$msgsnotinserted = array();
		
		$timeHeaders = number_format(microtime(true) - $this->startHeaders, 2);
		
		if(PEAR::isError($msgs))
		{
			echo "Error {$msgs->code}: {$msgs->message}$n";
			echo "Skipping group$n";
			return false;
		}
	
		$this->startUpdate = microtime(true);
		if (is_array($msgs))
		{	
			//loop articles, figure out files/parts
			foreach($msgs AS $msg)
			{
				if (!isset($msg['Number']))
					continue;
					
				$msgsreceived[] = $msg['Number'];
			
				$pattern = '/\((\d+)\/(\d+)\)$/i';
				if (!isset($msg['Subject']) || !preg_match($pattern, $msg['Subject'], $matches)) // not a binary post most likely.. continue
				{
					$msgsignored[] = $msg['Number'];
					continue;
				}
				
				//Filter subject based on black/white list
				if ($this->isBlackListed($msg, $groupArr['name'])) 
				{
					$msgsblacklisted[] = $msg['Number'];
					continue;
				}
	
				if (!preg_match('/(\[|\()(\d+)(\/|(\s|_)of(\s|_)|\-)(\d+)(\]|\))(?!"?$)/i', $msg['Subject'], $filecnt))
				{
					$filecnt[2] = "0";
					$filecnt[6] = "0";
				}

				if(is_numeric($matches[1]) && is_numeric($matches[2]))
				{
					array_map('trim', $matches);
					$subject = utf8_encode(trim(preg_replace($pattern, '', $msg['Subject'])));
					//Parts/files
					$cleansubject = preg_replace('/\[\d+(\/|(\s|_)of(\s|_)|\-)\d+\]|\(\d+(\/|\sof\s|\-)\d+\)|File\s\d+\sof\s\d{1,4}|\-\s\d{1,3}\/\d{1,3}\s\-|\d{1,3}\/\d{1,3}\]\s\-|\s\d{2,3}(\\|\/)\d{2,3}\s/i', '', $msg['Subject']);
					//Anything between the quotes. Too much variance within the quotes, so remove it completely.
					$cleansubject = preg_replace('/\".+\"/i', '', $cleansubject);
					//File extensions - If it was not quotes.
					$cleansubject = preg_replace('/(\.part(\d{1,5})?)?\.(7z|\d{3}(?=(\s|"))|avi|idx|jpg|mp4|nfo|nzb|par\s?2|pdf|rar|rev|r\d\d|sfv|srs|srr|sub|txt|vol.+(par2)|zip)"?|\d{2,3}\.pdf|\d{2,3}\s\-\s.+\.mp3|\.part\d{1,4}\./i', '', $cleansubject);
					//File Sizes - Non unique ones.
					$cleansubject = preg_replace('/\-\s\d{1,3}\.\d{1,3}\s(M|K)B\s(?=\-\s\d{1,3}\.\d{1,3}\s(G|M)B\s)|><\s\d{1,3}\/\d{1,3}\s\(.+><\s\d{1,3},\d{1,3}\s(G|M)B\s>|(\])?\s\d{1,}KB\s(yENC)?|"?\s\d{1,}\sbytes|(\-\s)?\d{1,}(\.|,)?\d{1,}\s(g|k|m)?B\s\-?(\s?yenc)?/i', '', $cleansubject);
					//Random stuff.
					$cleansubject = preg_replace('/AutoRarPar\d{1,5}/i', '', $cleansubject);
					$cleansubject = utf8_encode(trim($cleansubject));
					
					if(!isset($this->message[$subject]))
					{
						$this->message[$subject] = $msg;
						$this->message[$subject]['MaxParts'] = (int)$matches[2];
						$this->message[$subject]['Date'] = strtotime($this->message[$subject]['Date']);
						$this->message[$subject]['CollectionHash'] = md5($cleansubject.$msg['From'].$groupArr['ID'].$filecnt[6]);
						$this->message[$subject]['MaxFiles'] = (int)$filecnt[6];
						$this->message[$subject]['File'] = (int)$filecnt[2];
					}
					if((int)$matches[1] > 0)
					{
						$this->message[$subject]['Parts'][(int)$matches[1]] = array('Message-ID' => substr($msg['Message-ID'],1,-1), 'number' => $msg['Number'], 'part' => (int)$matches[1], 'size' => $msg['Bytes']);
					}
				}
			}
			unset($msg);
			unset($msgs);
			$maxnum = $last;
			$rangenotreceived = array_diff($rangerequested, $msgsreceived);

			if ($type != 'partrepair')
				echo "Received ".sizeof($msgsreceived)." articles of ".($last-$first+1)." requested, ".sizeof($msgsblacklisted)." blacklisted, ".sizeof($msgsignored)." not binary.".$n;
			
			if (sizeof($rangenotreceived) > 0) {
				switch($type)
				{
					case 'backfill':
						//don't add missing articles
					break;
					case 'partrepair':
					case 'update':
					default:
						$this->addMissingParts($rangenotreceived, $groupArr['ID']);
					break;
				}
				if ($type != 'partrepair')
					echo 'Server did not return article numbers '.implode(',', $rangenotreceived).".".$n;
			}
			
			if(isset($this->message) && count($this->message))
			{
				$maxnum = $first;
				//insert binaries and parts into database. when binary already exists; only insert new parts
				foreach($this->message AS $subject => $data)
				{
					if(isset($data['Parts']) && count($data['Parts']) > 0 && $subject != '')
					{
						$cres = $db->queryOneRow(sprintf("SELECT ID FROM collections WHERE collectionhash = %s", $db->escapeString($data['CollectionHash'])));
						if(!$cres)
						{
							//File and part count.
							$cleanerName = preg_replace('/\[\d+(\/|(\s|_)of(\s|_)|\-)\d+\]|\(\d+(\/|\sof\s|\-)\d+\)|File\s\d+\sof\s\d{1,4}|\-\s\d{1,3}\/\d{1,3}\s\-|\d{1,3}\/\d{1,3}\]\s\-|\s\d{2,3}(\\|\/)\d{2,3}\s/i', '', $subject);
							//Size.
							$cleanerName = preg_replace('/\d{1,3}(\.|,)\d{1,3}\s(K|M|G)B|\d{1,}(K|M|G)B|\d{1,}\sbytes|(\-\s)?\d{1,}(\.|,)?\d{1,}\s(g|k|m)?B\s\-(\syenc)?/i', '', $cleanerName);
							//Extensions.
							$cleanerName = preg_replace('/(\.part(\d{1,5})?)?\.(7z|\d{3}(?=(\s|"))|avi|idx|jpg|mp4|nfo|nzb|par\s?2|pdf|rar|rev|r\d\d|sfv|srs|srr|sub|txt|vol.+(par2)|zip)"?|\d{2,3}\.pdf|yEnc|\.part\d{1,4}\./i', '', $cleanerName);
							//Unwanted stuff.
							$cleanerName = preg_replace('/SECTIONED brings you|usenet\-space\-cowboys\.info|<.+https:\/\/secretusenet\.com>|> USC <|\[\d{1,}\]\-\[FULL\].+#a\.b[\w.#!@$%^&*\(\){}\|\\:"\';<>,?~` ]+\]|brothers\-of\-usenet\.info(\/\.net)?|Partner von SSL\-News\.info|AutoRarPar\d{1,5}/i', '', $cleanerName);
							//Removes some characters.
							$cleanerName = preg_replace('/<|>|"|=|\[|\]|\(|\)|\{|\}/i', '', $cleanerName);
							//Replaces some characters with 1 space.
							$cleanerName = preg_replace('/\.|\_|\-/i', ' ', $cleanerName);
							//Replace multiple spaces with 1 space
							$cleanerName = preg_replace('/\s\s+/i', ' ', $cleanerName);
							$cleanerName = trim($cleanerName);
							$csql = sprintf("INSERT INTO collections (name, subject, fromname, date, xref, groupID, totalFiles, collectionhash, dateadded, filecheckdate) VALUES (%s, %s, %s, FROM_UNIXTIME(%s), %s, %d, %s, %s, now(), now())", $db->escapeString($cleanerName), $db->escapeString($subject), $db->escapeString($data['From']), $db->escapeString($data['Date']), $db->escapeString($data['Xref']), $groupArr['ID'], $db->escapeString($data['MaxFiles']), $db->escapeString($data['CollectionHash']));
							$collectionID = $db->queryInsert($csql);
						}
						else
						{
							$collectionID = $cres["ID"];
							//Update the collection table with the last seen date for the collection.
							$cusql = sprintf("UPDATE collections set dateadded = now() where ID = %s", $collectionID);
							$db ->queryDirect($cusql);
						}
						$binaryHash = md5($subject.$data['From'].$groupArr['ID']);
						$bres = $db->queryOneRow(sprintf("SELECT ID FROM binaries WHERE binaryhash = %s", $db->escapeString($binaryHash)));
						if(!$bres)
						{
							$bsql = sprintf("INSERT INTO binaries (binaryhash, name, collectionID, totalParts, filenumber, partcheckdate) VALUES (%s, %s, %d, %s, %s, now())", $db->escapeString($binaryHash), $db->escapeString($subject), $collectionID, $db->escapeString($data['MaxParts']), $db->escapeString(round($data['File'])));
							$binaryID = $db->queryInsert($bsql);
						}
						else
						{
							$binaryID = $bres["ID"];
						}
						foreach($data['Parts'] AS $partdata)
						{
							$maxnum = ($partdata['number'] > $maxnum) ? $partdata['number'] : $maxnum;
							$pidata = $db->queryInsert(sprintf("INSERT INTO parts (binaryID, number, messageID, partnumber, size) VALUES (%d, %s, %s, %s, %s)", $binaryID, $db->escapeString($partdata['number']), $db->escapeString($partdata['Message-ID']), $db->escapeString(round($partdata['part'])), $db->escapeString($partdata['size'])), false);
							if (!$pidata) 
							{
								$msgsnotinserted[] = $partdata['number'];
							} 
							else 
							{
								$msgsinserted[] = $partdata['number'];
							}
						}
					}
				}
				if (sizeof($msgsnotinserted) > 0)
				{
					echo 'WARNING: '.sizeof($msgsnotinserted).' parts failed to insert'.$n;
					$this->addMissingParts($msgsnotinserted, $groupArr['ID']);
				}
			}	
			$timeUpdate = number_format(microtime(true) - $this->startUpdate, 2);
			$timeLoop = number_format(microtime(true)-$this->startLoop, 2);
			
			if ($type != 'partrepair')
			{
				echo $timeHeaders."s to download articles, ".$timeUpdate."s to insert articles, ".$timeLoop."s total.".$n.$n;
			}
			unset($this->message);
			unset($data);
			return $maxnum;
		}
		else
		{
			echo "Error: Can't get parts from server (msgs not array)".$n;
			echo "Skipping group".$n;
			return false;
		}

	}
	
	private function partRepair($nntp, $groupArr)
	{
		$n = $this->n;
		
		//get all parts in partrepair table
		$db = new DB;
		$missingParts = $db->query(sprintf("SELECT * FROM partrepair WHERE groupID = %d AND attempts < 5 ORDER BY numberID ASC LIMIT 15000", $groupArr['ID']));
		$partsRepaired = $partsFailed = 0;
		
		if (sizeof($missingParts) > 0)
		{
			echo 'Attempting to repair '.sizeof($missingParts).' parts...'.$n;
			
			//loop through each part to group into ranges
			$ranges = array();
			$lastnum = $lastpart = 0;
			foreach($missingParts as $part)
			{
				if (($lastnum+1) == $part['numberID']) {
					$ranges[$lastpart] = $part['numberID'];
				} else {
					$lastpart = $part['numberID'];
					$ranges[$lastpart] = $part['numberID'];
				}
				$lastnum = $part['numberID'];
			}
			
			$num_attempted = 0;
			$consoleTools = new ConsoleTools();
			
			//download missing parts in ranges
			foreach($ranges as $partfrom=>$partto)
			{
				$this->startLoop = microtime(true);
				
				$num_attempted += $partto - $partfrom + 1;
				$consoleTools->overWrite("Attempting repair: ".$consoleTools->percentString($num_attempted,sizeof($missingParts)).": ".$partfrom." to ".$partto);
				
				//get article from newsgroup
				$this->scan($nntp, $groupArr, $partfrom, $partto, 'partrepair');
				
				//check if the articles were added
				$articles = implode(',', range($partfrom, $partto));
				$sql = sprintf("SELECT pr.ID, pr.numberID, p.number from partrepair pr LEFT JOIN parts p ON p.number = pr.numberID WHERE pr.groupID=%d AND pr.numberID IN (%s) ORDER BY pr.numberID ASC", $groupArr['ID'], $articles);
				
				$result = $db->queryDirect($sql);
				while ($r = mysql_fetch_assoc($result)) 
				{
					if (isset($r['number']) && $r['number'] == $r['numberID'])
					{
						$partsRepaired++;
						
						//article was added, delete from partrepair
						$db->query(sprintf("DELETE FROM partrepair WHERE ID=%d", $r['ID']));
					} 
					else 
					{
						$partsFailed++;
						
						//article was not added, increment attempts
						$db->query(sprintf("UPDATE partrepair SET attempts=attempts+1 WHERE ID=%d", $r['ID']));
					}
				}
			}
			
			echo $n;
			
			echo $partsRepaired.' parts repaired.'.$n;
		}
		
		//remove articles that we cant fetch after 5 attempts
		$db->query(sprintf("DELETE FROM partrepair WHERE attempts >= 5 AND groupID = %d", $groupArr['ID']));
			
	}
	
	private function addMissingParts($numbers, $groupID) 
	{
		$db = new DB;
		$insertStr = "INSERT INTO partrepair (numberID, groupID) VALUES ";
		foreach($numbers as $number)
		{
			$insertStr .= sprintf("(%d, %d), ", $number, $groupID);
		}
		$insertStr = substr($insertStr, 0, -2);
		$insertStr .= " ON DUPLICATE KEY UPDATE attempts=attempts+1";
		return $db->queryInsert($insertStr, false);
	}
	
	public function retrieveBlackList() 
	{
		if (is_array($this->blackList) && !empty($this->blackList)) { return $this->blackList; }
		$blackList = $this->getBlacklist(true);
		$this->blackList = $blackList;
		return $blackList;
	}
	
	public function isBlackListed($msg, $groupName) 
	{
		$blackList = $this->retrieveBlackList();
		$field = array();
		if (isset($msg["Subject"]))
			$field[Binaries::BLACKLIST_FIELD_SUBJECT] = $msg["Subject"];
			
		if (isset($msg["From"]))
			$field[Binaries::BLACKLIST_FIELD_FROM] = $msg["From"];
	
		if (isset($msg["Message-ID"]))
			$field[Binaries::BLACKLIST_FIELD_MESSAGEID] = $msg["Message-ID"];

		$omitBinary = false;		
		
		foreach ($blackList as $blist)
		{
			if (preg_match('/^'.$blist['groupname'].'$/i', $groupName))
			{
				//blacklist
				if ($blist['optype'] == 1)
				{
					if (preg_match('/'.$blist['regex'].'/i', $field[$blist['msgcol']])) {
						$omitBinary = true;
					}
				}
				else if ($blist['optype'] == 2) 
				{
					if (!preg_match('/'.$blist['regex'].'/i', $field[$blist['msgcol']])) {
						$omitBinary = true;
					}
				}
			}
		}

		return $omitBinary;
	}
	
	public function search($search, $limit=1000, $excludedcats=array())
	{			
		$db = new DB();

		//
		// if the query starts with a ^ it indicates the search is looking for items which start with the term
		// still do the like match, but mandate that all items returned must start with the provided word
		//
		$words = explode(" ", $search);
		$searchsql = "";
		$intwordcount = 0;
		if (count($words) > 0)
		{
			foreach ($words as $word)
			{
				//
				// see if the first word had a caret, which indicates search must start with term
				//
				if ($intwordcount == 0 && (strpos($word, "^") === 0))
					$searchsql.= sprintf(" and b.name like %s", $db->escapeString(substr($word, 1)."%"));
				else
					$searchsql.= sprintf(" and b.name like %s", $db->escapeString("%".$word."%"));

				$intwordcount++;
			}
		}
		
		$exccatlist = "";
		if (count($excludedcats) > 0)
			$exccatlist = " and b.categoryID not in (".implode(",", $excludedcats).") ";
		
		$res = $db->query(sprintf("
					SELECT b.*, 
					g.name AS group_name,
					r.guid,
					(SELECT COUNT(ID) FROM parts p where p.binaryID = b.ID) as 'binnum'
					FROM binaries b
					INNER JOIN groups g ON g.ID = b.groupID
					LEFT OUTER JOIN releases r ON r.ID = b.releaseID
					WHERE 1=1 %s %s order by DATE DESC LIMIT %d ", 
					$searchsql, $exccatlist, $limit));
		
		return $res;
	}	

	public function getForReleaseId($id)
	{			
		$db = new DB();
		return $db->query(sprintf("select binaries.* from binaries where releaseID = %d order by relpart", $id));		
	}

	public function getById($id)
	{			
		$db = new DB();
		return $db->queryOneRow(sprintf("select binaries.*, collections.groupID, groups.name as groupname from binaries, collections left outer join groups on collections.groupID = groups.ID where binaries.ID = %d ", $id));
	}

	public function getBlacklist($activeonly=true)
	{			
		$db = new DB();
		
		$where = "";
		if ($activeonly)
			$where = " where binaryblacklist.status = 1 ";
			
		return $db->query("SELECT binaryblacklist.ID, binaryblacklist.optype, binaryblacklist.status, binaryblacklist.description, binaryblacklist.groupname AS groupname, binaryblacklist.regex, 
												groups.ID AS groupID, binaryblacklist.msgcol FROM binaryblacklist 
												left outer JOIN groups ON groups.name = binaryblacklist.groupname 
												".$where."
												ORDER BY coalesce(groupname,'zzz')");		
	}

	public function getBlacklistByID($id)
	{			
		$db = new DB();
		return $db->queryOneRow(sprintf("select * from binaryblacklist where ID = %d ", $id));		
	}

	public function deleteBlacklist($id)
	{			
		$db = new DB();
		return $db->query(sprintf("delete from binaryblacklist where ID = %d", $id));		
	}		
	
	public function updateBlacklist($regex)
	{			
		$db = new DB();
		
		$groupname = $regex["groupname"];
		if ($groupname == "")
			$groupname = "null";
		else
		{
			$groupname = preg_replace("/a\.b\./i", "alt.binaries.", $groupname);
			$groupname = sprintf("%s", $db->escapeString($groupname));
		}
			
		$db->query(sprintf("update binaryblacklist set groupname=%s, regex=%s, status=%d, description=%s, optype=%d, msgcol=%d where ID = %d ", $groupname, $db->escapeString($regex["regex"]), $regex["status"], $db->escapeString($regex["description"]), $regex["optype"], $regex["msgcol"], $regex["id"]));	
	}
	
	public function addBlacklist($regex)
	{			
		$db = new DB();
		
		$groupname = $regex["groupname"];
		if ($groupname == "")
			$groupname = "null";
		else
		{
			$groupname = preg_replace("/a\.b\./i", "alt.binaries.", $groupname);
			$groupname = sprintf("%s", $db->escapeString($groupname));
		}
			
		return $db->queryInsert(sprintf("insert into binaryblacklist (groupname, regex, status, description, optype, msgcol) values (%s, %s, %d, %s, %d, %d) ", 
			$groupname, $db->escapeString($regex["regex"]), $regex["status"], $db->escapeString($regex["description"]), $regex["optype"], $regex["msgcol"]));	
	}	
	
	public function delete($id)
	{			
		$db = new DB();
		$db->query(sprintf("delete from parts where binaryID = %d", $id));
		$db->query(sprintf("delete from binaries where ID = %d", $id));
	}	
}
?>
