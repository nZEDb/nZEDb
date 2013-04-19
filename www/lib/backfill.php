<?php
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/groups.php");
require_once(WWW_DIR."/lib/nntp.php");
require_once(WWW_DIR."/lib/binaries.php");

class Backfill 
{
	function Backfill() 
	{
		$this->n = "\n";
	}

	//
	// Update all active groups categories and descriptions
	//
	function backfillAllGroups($groupName='')
	{
		$n = $this->n;
		$groups = new Groups;
		/*$db = new DB();
        $db->queryDirect(sprintf("SELECT ID from collections where filecheck = 2"));
        $colcount = $db->getAffectedRows();
        if ( $colcount > 0 )
        {
			exit($n."Collections = ".$colcount.$n.", backfill exiting.".$n);
        }*/
		if ($groupName != '') 
		{
			$grp = $groups->getByName($groupName);
			if ($grp)
			{
				$res = array($grp);
			}
		} 
		else 
		{
			$res = $groups->getActive();
		}

		$counter = 1;
		if (@$res)
		{
			// No compression.
			$nntp = new Nntp();
			$nntp->doConnectNC();
			// Compression.
			$nntpc = new Nntp();
			$nntpc->doConnect();
			
			foreach($res as $groupArr)
			{
				echo $n."Starting group ".$counter." of ".sizeof($res).".".$n;
				$this->backfillGroup($nntp, $nntpc, $groupArr);
				$counter++;
			}
			$nntp->doQuit();
			$nntpc->doQuit();
		}
		else
		{
			echo "No groups specified. Ensure groups are added to nZEDb's database for updating.".$n;
		}
	}

	function backfillGroup($nntp, $nntpc, $groupArr)
	{
		$db = new DB();
		$binaries = new Binaries();
		$n = $this->n;
		$this->startGroup = microtime(true);

		echo 'Processing '.$groupArr['name'].$n;
		// Compression.
		$datac = $nntpc->selectGroup($groupArr['name']);
		if(PEAR::isError($datac))
		{
			echo "Could not select group (bad name?): {$groupArr['name']}".$n;
			return;
		}
		// No compression.
		$data = $nntp->selectGroup($groupArr['name']);
		if(PEAR::isError($data))
		{
			echo "Could not select group (bad name?): {$groupArr['name']}".$n;
			echo "Retrying to connect to usenet".$n;
			$nntp->doQuit();
			$nntp->doConnect();
			$data = $nntp->selectGroup($groupArr['name']);
			if(PEAR::isError($data))
			{
				echo "Failed to reconnect to usenet, skipping group".$n;
				return;
			}
		}
		// Get targetpost based on days target.
		$targetpost = $this->daytopost($nntp,$groupArr['name'],$groupArr['backfill_target'],TRUE);
		if($groupArr['first_record'] == 0 || $groupArr['backfill_target'] == 0)
		{
			echo "Group ".$groupArr['name']." has invalid numbers. Have you run update on it? Have you set the backfill days amount?".$n;
			return;
		}

		echo "Group ".$data["group"].": server has ".$data['first']." - ".$data['last'].", or ~".
				((int) (($this->postdate($nntp,$data['last'],FALSE) - $this->postdate($nntp,$data['first'],FALSE))/86400)).
				" days.".$n."Local first = ".$groupArr['first_record']." (".
				((int) ((date('U') - $this->postdate($nntp,$groupArr['first_record'],FALSE))/86400)).
				" days).  Backfill target of ".$groupArr['backfill_target']."days is post $targetpost".$n;
		
		// If our estimate comes back with stuff we already have, finish.
		if($targetpost >= $groupArr['first_record'])
		{
			echo "Nothing to do, we already have the target post".$n.$n;
			return "";
		}
		// Get first and last part numbers from newsgroup.
		if($targetpost < $data['first'])
		{
			echo "WARNING: Backfill came back as before server's first.  Setting targetpost to server first".$n."Skipping Group:".$n;
			return "";
		}
		// Calculate total number of parts.
		$total = $groupArr['first_record'] - $targetpost;
		$done = false;
		// Set first and last, moving the window by maxxMssgs.
		$last = $groupArr['first_record'] - 1;
		// Set the initial "chunk".
		$first = $last - $binaries->messagebuffer + 1;
		// Just in case this is the last chunk we needed.
		if($targetpost > $first)
		{
			$first = $targetpost;
		}
		while($done === false)
		{
			$binaries->startLoop = microtime(true);
			/*$colcount = array_shift($db->queryOneRow("SELECT COUNT(ID) from collections where filecheck = 2"));
			if ( $colcount > 0 )
			{
				 exit($n."Collections = ".$colcount.", backfill exiting.".$n);
			}*/

			echo "Getting ".($last-$first+1)." parts from ".str_replace('alt.binaries','a.b',$data["group"])." (".($first-$targetpost)." in queue).".$n;
			flush();
			$binaries->scan($nntpc, $groupArr, $first, $last, 'backfill');

			$db->query(sprintf("UPDATE groups SET first_record = %s, last_updated = now() WHERE ID = %d", $db->escapeString($first), $groupArr['ID']));
			if($first==$targetpost)
				$done = true;
			else
			{
				//Keep going: set new last, new first, check for last chunk.
				$last = $first - 1;
				$first = $last - $binaries->messagebuffer + 1;
				if($targetpost > $first)
				{
					$first = $targetpost;
				}
			}
		}
		$first_record_postdate = $this->postdate($nntp,$first,false);
		// Set group's first postdate.
		$db->query(sprintf("UPDATE groups SET first_record_postdate = FROM_UNIXTIME(".$first_record_postdate."), last_updated = now() WHERE ID = %d", $groupArr['ID']));

		$timeGroup = number_format(microtime(true) - $this->startGroup, 2);
		echo "Group processed in ".$timeGroup." seconds.".$n;
		// Increment the backfil target date.
	}
	
	//
	// Safe backfill using posts.
	//
	function safeBackfill($articles='')
	{
		$db = new DB();
		$n = $this->n;
		
		$targetdate = "2012-06-24";
		$groupname = $db->queryOneRow(sprintf("select name from groups WHERE (first_record_postdate BETWEEN %s and now()) and (active = 1) order by name asc", $db->escapeString($targetdate)));
		
		if (!$groupname)
		{
			exit("No groups to backfill, they are all at the target date ".$targetdate.".".$n);
		}
		else
		{
			$this->backfillPostAllGroups($groupname["name"], $articles);
		}
	}
	
	//
	// Update all active groups categories and descriptions using article numbers instead of date.
	//
	function backfillPostAllGroups($groupName='', $articles = '')
	{
		$n = $this->n;
		$groups = new Groups;
		if ($groupName != '') 
		{
			$grp = $groups->getByName($groupName);
			if ($grp)
			{
				$res = array($grp);
			}
		} 
		else
		{
			$res = $groups->getActive();
		}

		$counter = 1;
		if (@$res)
		{
			
			// We do not use interval here, so use a compressed connection only - testing.
			
			$nntp = new Nntp();
			$nntp->doConnect();
			
			foreach($res as $groupArr)
			{
				echo $n."Starting group ".$counter." of ".sizeof($res).".".$n;
				$this->backfillPostGroup($nntp, $groupArr, $articles);
				$counter++;
			}
			$nntp->doQuit();
		}
		else
		{
			echo "No groups specified. Ensure groups are added to nZEDb's database for updating.".$n;
		}
	}

	function backfillPostGroup($nntp, $groupArr, $articles = '')
	{
		$db = new DB();
		$binaries = new Binaries();
		$n = $this->n;
		$this->startGroup = microtime(true);

		echo 'Processing '.$groupArr['name'].$n;
		$data = $nntp->selectGroup($groupArr['name']);
		if(PEAR::isError($data))
		{
			echo "Could not select group (bad name?): {$groupArr['name']}".$n;
			return;
		}
		
		// Get targetpost based on days target.
		$targetpost =  round($groupArr['first_record']-$articles);
		
		echo "Group ".$data["group"]."'s oldest article is ".$data['first'].", newest is ".$data['last'].". The groups retention is: ".
				((int) (($this->postdate($nntp,$data['last'],FALSE) - $this->postdate($nntp,$data['first'],FALSE))/86400)).
				" days.".$n."Our oldest article is: ".$groupArr['first_record']." which is (".
				((int) ((date('U') - $this->postdate($nntp,$groupArr['first_record'],FALSE))/86400)).
				" days old). Our backfill target is article ".$targetpost." which is (".((int) ((date('U') - $this->postdate($nntp,$targetpost,FALSE))/86400)).$n.
				" days old).".$n;
		
		if($groupArr['first_record'] <= 0 || $targetpost <= 0)
		{
			echo "You need to run update_binaries on the group. Otherwise the group is dead, you must disable it.".$n;
			return "";
		}
		// Check if we are grabbing further than the server has.
		if($targetpost < $data['first'])
		{
			$groups = new Groups;
			$groups->disableForPost($groupArr['name']);
			echo "WARNING: Attempting to backfill further than usenet's first article, setting our first article date very high so safe backfill can skip it.".$n."Skipping Group:".$n;
			return "";
		}
		// If our estimate comes back with stuff we already have, finish.
		if($targetpost >= $groupArr['first_record'])
		{
			echo "Nothing to do, we already have the target post".$n.$n;
			return "";
		}
		// Calculate total number of parts.
		$total = $groupArr['first_record'] - $targetpost;
		$done = false;
		// Set first and last, moving the window by maxxMssgs.
		$last = $groupArr['first_record'] - 1;
		// Set the initial "chunk".
		$first = $last - $binaries->messagebuffer + 1;
		// Just in case this is the last chunk we needed.
		if($targetpost > $first)
		{
			$first = $targetpost;
		}
		while($done === false)
		{
			$binaries->startLoop = microtime(true);
			/*$colcount = array_shift($db->queryOneRow("SELECT COUNT(ID) from collections where filecheck = 2"));
			if ( $colcount > 0 )
			{
				 exit($n."Collections = ".$colcount.", backfill exiting.".$n);
			}*/

			echo "Getting ".($last-$first+1)." parts from ".str_replace('alt.binaries','a.b',$data["group"])." (".($first-$targetpost)." in queue).".$n;
			flush();
			$binaries->scan($nntp, $groupArr, $first, $last, 'backfill');

			$db->query(sprintf("UPDATE groups SET first_record = %s, last_updated = now() WHERE ID = %d", $db->escapeString($first), $groupArr['ID']));
			if($first==$targetpost)
				$done = true;
			else
			{
				//Keep going: set new last, new first, check for last chunk.
				$last = $first - 1;
				$first = $last - $binaries->messagebuffer + 1;
				if($targetpost > $first)
				{
					$first = $targetpost;
				}
			}
		}
		$first_record_postdate = $this->postdate($nntp,$first,false);
		// Set group's first postdate.
		$db->query(sprintf("UPDATE groups SET first_record_postdate = FROM_UNIXTIME(".$first_record_postdate."), last_updated = now() WHERE ID = %d", $groupArr['ID']));

		$timeGroup = number_format(microtime(true) - $this->startGroup, 2);
		echo "Group processed in ".$timeGroup." seconds.".$n;
		// Increment the backfil target date.
	}
	
	//
	// Returns a single timestamp from a local article number.
	//
	function postdate($nntp,$post,$debug=true)
	{
		$n = $this->n;
		$attempts=0;
		do
		{
			$msgs = $nntp->getOverview($post."-".$post,true,false);
			if(PEAR::isError($msgs))
			{
				echo "Error {$msgs->code}: {$msgs->message}.".$n."Returning from postdate.".$n;
				return "";
			}

			if(!isset($msgs[0]['Date']) || $msgs[0]['Date']=="" || is_null($msgs[0]['Date']))
			{
				$success=false;
			} 
			else 
			{
				$date = $msgs[0]['Date'];
				$success=true;
			}
			if($debug && $attempts > 0) echo "Retried ".$attempts." time(s).".$n;
			$attempts++;
		}while($attempts <= 3 && $success == false);
		
		if (!$success)
		{
			return "";
		}
		
		if($debug) echo "DEBUG: postdate for post: .".$post." came back ".$date." (";
		$date = strtotime($date);
		if($debug) echo $date." seconds unixtime or ".$this->daysOld($date)." days)".$n;
		return $date;
	}
	
	function daytopost($nntp, $group, $days, $debug=true)
	{
		$n = $this->n;
		// DEBUG every postdate call?!?!
		$pddebug = false;
		if ($debug)
		{
			echo "INFO: Finding article for ".$group.$days." days back.".$n;
		}
		
		$data = $nntp->selectGroup($group);
		if(PEAR::isError($data))
		{
			echo "Error {$data->code}: {$data->message}".$n."Returning from daytopost.".$n;
			return "";
		}
		// Goal timestamp.
		$goaldate = date('U')-(86400*$days);
		$totalnumberofarticles = $data['last'] - $data['first'];
		$upperbound = $data['last'];
		$lowerbound = $data['first'];
		if ($debug)
		{
			echo "Total Articles: ".$totalnumberofarticles." Newest: ".$upperbound." Oldest: ".$lowerbound.$n."Goal: ".date("r", $goaldate)." ($goaldate).".$n;
		}
		if ($data['last'] == PHP_INT_MAX)
		{
			die("ERROR: Group data is coming back as php's max value. You should not see this since we use a patched Net_NNTP that fixes this bug.".$n);
		}
		$firstDate = $this->postdate($nntp, $data['first'], $pddebug);
		$lastDate = $this->postdate($nntp, $data['last'], $pddebug);
		if ($goaldate < $firstDate)
		{
			echo "WARNING: Backfill target of $days day(s) is older than the first article stored on your news server.".$n.
				 "Starting from the first available article (".date("r", $firstDate)." or ".$this->daysOld($firstDate)." days).".$n;
			return $data['first'];
		}
		elseif ($goaldate > $lastDate)
		{
			echo "ERROR: Backfill target of ".$days." day(s) is newer than the last article stored on your news server.".$n.
				 "To backfill this group you need to set Backfill Days to at least ".ceil($this->daysOld($lastDate)+1)." days (".date("r", $lastDate-86400).").".$n;
			return "";
		}
		if ($debug)
		{
			echo "DEBUG: Searching for postdate.".$n."Goaldate: ".$goaldate." (".date("r", $goaldate).") ".$n." Firstdate: ".$firstDate." (".((is_int($firstDate))?date("r", $firstDate):'n/a').") ".$n.". Lastdate: ".$lastDate." (".date("r", $lastDate).").".$n;
		}
		
		$interval = floor(($upperbound - $lowerbound) * 0.5);
		$dateofnextone = "";
		$templowered = "";
		
		if ($debug)
		{
			echo "Start: ".$data['first'].$n."End: ".$data['last'].$n."Interval: ".$interval.$n;
		}
		
		$dateofnextone = $lastDate;
		// Match on days not timestamp to speed things up.
		while($this->daysOld($dateofnextone) < $days)
		{		
			while(($tmpDate = $this->postdate($nntp,($upperbound-$interval),$pddebug))>$goaldate)
			{
				$upperbound = $upperbound - $interval;
				if($debug) echo "New upperbound (".$upperbound.") is ".$this->daysOld($tmpDate)." days old.".$n;
			}
			if(!$templowered)
			{
				$interval = ceil(($interval/2));
				if($debug) echo "Set interval to ".$interval." articles.".$n;
		 	}
		 	$dateofnextone = $this->postdate($nntp,($upperbound-1),$pddebug);
			while(!$dateofnextone)
			{  $dateofnextone = $this->postdate($nntp,($upperbound-1),$pddebug); }
	 	}
		echo "Determined to be article $upperbound which is ".$this->daysOld($dateofnextone)." days old (".date("r", $dateofnextone).").".$n;
		return $upperbound;
	}
    
    private function daysOld($timestamp)
    {
    	return round((time()-$timestamp)/86400, 1);
    }
}
?>
