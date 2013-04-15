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
		$db = new DB();
        $db->queryDirect(sprintf("SELECT ID from collections where filecheck < 2"));
        $colcount = mysql_affected_rows();
        if ( $colcount > 10000 )
        {
            echo "\nCollections that need to be processed has exceeded 10k, backfill exiting\n";
			exit();
        }
		if ($groupName != '') {
			$grp = $groups->getByName($groupName);
			if ($grp)
				$res = array($grp);
		} else {
			$res = $groups->getActive();
		}

		if ($res)
		{
			$nntp = new Nntp();
			$nntp->doConnect();

			foreach($res as $groupArr)
			{
				$this->backfillGroup($nntp, $groupArr);
			}

			$nntp->doQuit();
		}
		else
		{
			echo "No groups specified. Ensure groups are added to newznab's database for updating.$n";
		}
	}

	function backfillGroup($nntp, $groupArr)
	{
		$db = new DB();
		$binaries = new Binaries();
		$n = $this->n;
		$this->startGroup = microtime(true);

		echo 'Processing '.$groupArr['name'].$n;

		$data = $nntp->selectGroup($groupArr['name']);
		if(PEAR::isError($data))
		{
			echo "Could not select group (bad name?): {$groupArr['name']}$n";
			return;
		}
		$targetpost = $this->daytopost($nntp,$groupArr['name'],$groupArr['backfill_target'],TRUE); //get targetpost based on days target
	       if($groupArr['first_record'] == 0 || $groupArr['backfill_target'] == 0)
		{
			echo "Group ".$groupArr['name']." has invalid numbers.  Have you run update on it?  Have you set the backfill days amount?$n";
			return;
		}

		echo "Group ".$data["group"].": server has ".$data['first']." - ".$data['last'].", or ~";
		echo((int) (($this->postdate($nntp,$data['last'],FALSE) - $this->postdate($nntp,$data['first'],FALSE))/86400));
		echo " days.".$n."Local first = ".$groupArr['first_record']." (";
		echo((int) ((date('U') - $this->postdate($nntp,$groupArr['first_record'],FALSE))/86400));
		echo " days).  Backfill target of ".$groupArr['backfill_target']."days is post $targetpost.$n";

		if($targetpost >= $groupArr['first_record'])	//if our estimate comes back with stuff we already have, finish
		{
			echo "Nothing to do, we already have the target post.$n $n";
			return "";
		}
		//get first and last part numbers from newsgroup
		if($targetpost < $data['first'])
		{
			echo "WARNING: Backfill came back as before server's first.  Setting targetpost to server first.$n";
			echo "Skipping Group $n";
			return "";
		}
		//calculate total number of parts
		$total = $groupArr['first_record'] - $targetpost;
		$done = false;
		//set first and last, moving the window by maxxMssgs
		$last = $groupArr['first_record'] - 1;
		$first = $last - $binaries->messagebuffer + 1; //set initial "chunk"
		if($targetpost > $first)	//just in case this is the last chunk we needed
			$first = $targetpost;
		while($done === false)
		{
			$binaries->startLoop = microtime(true);
			$colcount = $db->queryDirect(sprintf("SELECT ID from collections where filecheck < 2"));
			if ( $colcount > 10000 )
			{
				break;
				echo "\nCollections that need to be processed has exceeded 10k, backfill exiting";
			}

			echo "Getting ".($last-$first+1)." parts from ".str_replace('alt.binaries','a.b',$data["group"])." (".($first-$targetpost)." in queue)".$n;
			flush();
			$binaries->scan($nntp, $groupArr, $first, $last, 'backfill');

			$db->query(sprintf("UPDATE groups SET first_record = %s, last_updated = now() WHERE ID = %d", $db->escapeString($first), $groupArr['ID']));
			if($first==$targetpost)
				$done = true;
			else
			{	//Keep going: set new last, new first, check for last chunk.
				$last = $first - 1;
				$first = $last - $binaries->messagebuffer + 1;
				if($targetpost > $first)
					$first = $targetpost;
			}
		}
		$first_record_postdate = $this->postdate($nntp,$first,false);
		$db->query(sprintf("UPDATE groups SET first_record_postdate = FROM_UNIXTIME(".$first_record_postdate."), last_updated = now() WHERE ID = %d", $groupArr['ID']));  //Set group's first postdate

		$timeGroup = number_format(microtime(true) - $this->startGroup, 2);
		echo "Group processed in $timeGroup seconds $n";
		//increment the backfil target date
		$db->query(sprintf("UPDATE groups set backfill_target=backfill_target + 1 where active=1 and backfill_target < 1000"));
	}
	
	function postdate($nntp,$post,$debug=true) //returns single timestamp from a local article number
	{
		$n = $this->n;
		$attempts=0;
		do
		{
			$msgs = $nntp->getOverview($post."-".$post,true,false);
			if(PEAR::isError($msgs))
			{
				echo "Error {$msgs->code}: {$msgs->message}$n";
				echo "Returning from postdate$n";
				return "";
			}

			if(!isset($msgs[0]['Date']) || $msgs[0]['Date']=="" || is_null($msgs[0]['Date']))
			{
				$success=false;
			} else {
				$date = $msgs[0]['Date'];
				$success=true;
			}
			if($debug && $attempts > 0) echo "retried $attempts time(s)".$n;
			$attempts++;
		} while($attempts <= 3 && $success == false);
		
		if (!$success) { return ""; }
		
		if($debug) echo "DEBUG: postdate for post: $post came back $date (";
		$date = strtotime($date);
		if($debug) echo "$date seconds unixtime or ".$this->daysOld($date)." days)".$n;
		return $date;
	}
	
	function daytopost($nntp, $group, $days, $debug=true)
	{
		$n = $this->n;
		$pddebug = false; //DEBUG every postdate call?!?!
		if ($debug) echo "INFO: daytopost finding post for $group $days days back.".$n;
		
		$data = $nntp->selectGroup($group);
		if(PEAR::isError($data))
		{
			echo "Error {$data->code}: {$data->message}$n";
			echo "Returning from daytopost$n";
			return "";
		}
		$goaldate = date('U')-(86400*$days); //goaltimestamp
		$totalnumberofarticles = $data['last'] - $data['first'];
		$upperbound = $data['last'];
		$lowerbound = $data['first'];
		
		if ($debug) echo "Total Articles: $totalnumberofarticles $n Upper: $upperbound $n Lower: $lowerbound $n Goal: ".date("r", $goaldate)." ($goaldate) $n";
		if ($data['last']==PHP_INT_MAX) { echo "ERROR: Group data is coming back as php's max value.  You should not see this since we use a patched Net_NNTP that fixes this bug.$n"; die(); }
		
		$firstDate = $this->postdate($nntp, $data['first'], $pddebug);
		$lastDate = $this->postdate($nntp, $data['last'], $pddebug);
		if ($goaldate < $firstDate)
		{
			echo "WARNING: Backfill target of $days day(s) is older than the first article stored on your news server.$n";
			echo "Starting from the first available article (".date("r", $firstDate)." or ".$this->daysOld($firstDate)." days).$n";
			return $data['first'];
		}
		elseif ($goaldate > $lastDate)
		{
			echo "ERROR: Backfill target of $days day(s) is newer than the last article stored on your news server.$n";
			echo "To backfill this group you need to set Backfill Days to at least ".ceil($this->daysOld($lastDate)+1)." days (".date("r", $lastDate-86400).").$n";
			return "";
		}
		if ($debug) echo "DEBUG: Searching for postdate $n Goaldate: $goaldate (".date("r", $goaldate).") $n Firstdate: $firstDate (".((is_int($firstDate))?date("r", $firstDate):'n/a').") $n Lastdate: $lastDate (".date("r", $lastDate).") $n";
			
		$interval = floor(($upperbound - $lowerbound) * 0.5);
		$dateofnextone = "";
		$templowered = "";
		
		if ($debug) echo "Start: ".$data['first']." $n End: ".$data['last']." $n Interval: $interval $n";
		
		$dateofnextone = $lastDate;
		
		while($this->daysOld($dateofnextone) < $days)  //match on days not timestamp to speed things up
		{		
			while(($tmpDate = $this->postdate($nntp,($upperbound-$interval),$pddebug))>$goaldate)
			{
				$upperbound = $upperbound - $interval;
				if($debug) echo "New upperbound ($upperbound) is ".$this->daysOld($tmpDate)." days old. $n";
			}
			if(!$templowered)
			{
				$interval = ceil(($interval/2));
				if($debug) echo "Set interval to $interval articles. $n";
		 	}
		 	$dateofnextone = $this->postdate($nntp,($upperbound-1),$pddebug);
			while(!$dateofnextone)
			{  $dateofnextone = $this->postdate($nntp,($upperbound-1),$pddebug); }
	 	}
		echo "Determined to be article $upperbound which is ".$this->daysOld($dateofnextone)." days old (".date("r", $dateofnextone).") $n";
		//echo round((($dateofnextone-$goaldate)/60), 0)." minutes off of orginal goal.$n";
		return $upperbound;
	}
    
    private function daysOld($timestamp)
    {
    	return round((time()-$timestamp)/86400, 1);
    }
	
}
?>
