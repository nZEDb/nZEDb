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
		$s = new Sites();
		$site = $s->get();
		$this->safebdate = (!empty($site->safebackfilldate)) ? $site->safebackfilldate : 2012-06-24;
		$this->hashcheck = (!empty($site->hashcheck)) ? $site->hashcheck : 0;
		$this->sleeptime = (!empty($site->postdelay)) ? $site->postdelay : 300;
	}

	// Backfill groups using user specified time/date.
	function backfillAllGroups($groupName='')
	{
		if ($this->hashcheck == 0)
			exit("You must run update_binaries.php to update your collectionhash.\n");
		$n = $this->n;
		$groups = new Groups();

		if ($groupName != '')
		{
			$grp = $groups->getByName($groupName);
			if ($grp)
				$res = array($grp);
		}
		else
			$res = $groups->getActiveBackfill();

		$counter = 1;
		if (@$res)
		{
			$nntp = new Nntp;
			$nntpc = new Nntp;
			foreach($res as $groupArr)
			{
				echo "\nStarting group ".$counter." of ".sizeof($res).".\n";
				$this->backfillGroup($nntp, $nntpc, $groupArr, sizeof($res)-$counter);
				$counter++;
			}
		}
		else
			echo "No groups specified. Ensure groups are added to nZEDb's database for updating.\n";
	}

	// Backfill 1 group using time.
	function backfillGroup($nntp, $nntpc, $groupArr, $left)
	{
		$db = new DB();
		$binaries = new Binaries();
		$n = $this->n;
		$this->startGroup = microtime(true);

		if (!isset($nntp))
			$nntp = new Nntp;
		if (!isset($nntpc))
			$nntpc = new Nntp;
		// No compression.
		$nntp->doConnectNC();

		$data = $nntp->selectGroup($groupArr['name']);
		if(PEAR::isError($data))
		{
			$data = $nntp->dataError($nntp, $groupArr['name']);
			if ($data === false)
				return;
		}

		// Get targetpost based on days target.
		$targetpost = $this->daytopost($nntp, $groupArr['name'], $groupArr['backfill_target'], true);
		if ($targetpost < 0)
			$targetpost = round($data['first']);
		if($groupArr['first_record'] == 0 || $groupArr['backfill_target'] == 0)
		{
			$nntp->doQuit();
			echo "Group ".$groupArr['name']." has invalid numbers. Have you run update on it? Have you set the backfill days amount?\n";
			return;
		}

		// Check if we are grabbing further than the server has.
		if($groupArr['first_record'] <= $data['first']+50000)
		{
			$nntp->doQuit();
			echo "We have hit the maximum we can backfill for this ".$groupArr['name'].", disabling it.\n\n";
			$groups = new Groups();
			$groups->disableForPost($groupArr['name']);
			return "";
		}
		// If our estimate comes back with stuff we already have, finish.
		if($targetpost >= $groupArr['first_record'])
		{
			$nntp->doQuit();
			echo "Nothing to do, we already have the target post\n\n";
			return "";
		}

		echo "Group ".$data["group"].": server has ".number_format($data['first'])." - ".number_format($data['last']).", or ~".
				((int) (($this->postdate($nntp,$data['last'],FALSE,$groupArr['name']) - $this->postdate($nntp,$data['first'],FALSE,$groupArr['name']))/86400)).
				" days.".$n."Local first = ".number_format($groupArr['first_record'])." (".
				((int) ((date('U') - $this->postdate($nntp,$groupArr['first_record'],FALSE,$groupArr['name']))/86400)).
				" days).  Backfill target of ".$groupArr['backfill_target']." days is post $targetpost\n";

		// Calculate total number of parts.
		$total = $groupArr['first_record'] - $targetpost;
		$done = false;
		// Set first and last, moving the window by maxxMssgs.
		$last = $groupArr['first_record'] - 1;
		// Set the initial "chunk".
		$first = $last - $binaries->messagebuffer + 1;
		// Just in case this is the last chunk we needed.
		if($targetpost > $first)
			$first = $targetpost;

		// In case postdate doesn't get a date.
		if (is_null($groupArr['first_record_postdate']) || $groupArr['first_record_postdate'] == "NULL")
			$firstr_date = time();
		else
			$firstr_date = strtotime($groupArr['first_record_postdate']);

		while($done === false)
		{
			$binaries->startLoop = microtime(true);

			echo "Getting ".(number_format($last-$first+1))." articles from ".$data["group"].", ".$left." group(s) left. (".(number_format($first-$targetpost))." articles in queue).\n";
			flush();
			$binaries->scan($nntpc, $groupArr, $first, $last, 'backfill');

			$newdate = $this->postdate($nntp, $first, false, $groupArr['name'], true);
			if ($newdate !== false)
				$firstr_date = $newdate;

			$db->queryExec(sprintf("UPDATE groups SET first_record_postdate = %s, first_record = %s, last_updated = NOW() WHERE id = %d", $db->from_unixtime($firstr_date), $db->escapeString($first), $groupArr['id']));
			if($first==$targetpost)
				$done = true;
			else
			{
				// Keep going: set new last, new first, check for last chunk.
				$last = $first - 1;
				$first = $last - $binaries->messagebuffer + 1;
				if($targetpost > $first)
					$first = $targetpost;
			}
		}
		$nntp->doQuit();
		// Set group's first postdate.
		$db->queryExec(sprintf("UPDATE groups SET first_record_postdate = %s, last_updated = NOW() WHERE id = %d", $db->from_unixtime($firstr_date), $groupArr['id']));

		$timeGroup = number_format(microtime(true) - $this->startGroup, 2);
		echo "Group processed in ".$timeGroup." seconds.".$n;
		// Increment the backfil target date.
	}

	// Safe backfill using posts. Going back to a date specified by the user on the site settings.
	// This does 1 group for x amount of parts until it reaches the date.
	function safeBackfill($articles='')
	{
		if ($this->hashcheck == 0)
			exit("You must run update_binaries.php to update your collectionhash.\n");

		$db = new DB();
		$groupname = $db->queryOneRow(sprintf("SELECT name FROM groups WHERE first_record_postdate BETWEEN %s AND NOW() AND backfill = 1 ORDER BY name ASC", $db->escapeString($this->safebdate)));

		if (!$groupname)
			exit("No groups to backfill, they are all at the target date ".$this->safebdate.", or you have not enabled them to be backfilled in the groups page.\n");
		else
			$this->backfillPostAllGroups($groupname["name"], $articles);
	}

	// Backfill groups using user specified article count.
	function backfillPostAllGroups($groupName='', $articles='', $type='')
	{
		if ($this->hashcheck == 0)
			exit("You must run update_binaries.php to update your collectionhash.\n");

		$groups = new Groups();
		if ($groupName != '')
		{
			$grp = $groups->getByName($groupName);
			if ($grp)
				$res = array($grp);
		}
		else
		{
			if($type == "normal")
				$res = $groups->getActiveBackfill();
			else if($type == "date")
				$res = $groups->getActiveByDateBackfill();
		}

		if (@$res)
		{
			$counter = 1;
			foreach($res as $groupArr)
			{
				echo "\nStarting group ".$counter." of ".sizeof($res).".\n";
				$this->backfillPostGroup($groupArr, $articles, sizeof($res)-$counter);
				$counter++;
			}
		}
		else
			echo "No groups specified. Ensure groups are added to nZEDb's database for updating.\n";
	}

	function backfillPostGroup($groupArr, $articles='', $left)
	{
		$db = new DB();
		$binaries = new Binaries();
		$nntp = new Nntp();
		$n = $this->n;
		$this->startGroup = microtime(true);

		echo 'Processing '.$groupArr['name'].$n;

		$nntp->doConnectNC();
		$data = $nntp->selectGroup($groupArr['name']);
		if(PEAR::isError($data))
		{
			$data = $nntp->dataError($nntp, $groupArr['name']);
			if ($data === false)
				return;
		}

		// Get targetpost based on days target.
		$targetpost =  round($groupArr['first_record']-$articles);
		if ($targetpost < 0)
			$targetpost = round($data['first']);

		if($groupArr['first_record'] <= 0 || $targetpost <= 0)
		{
			$nntp->doQuit();
			echo "You need to run update_binaries on the ".$data['group'].". Otherwise the group is dead, you must disable it.\n";
			return "";
		}

		// Check if we are grabbing further than the server has.
		if($groupArr['first_record'] <= $data['first']+$articles)
		{
			$nntp->doQuit();
			echo "We have hit the maximum we can backfill for ".$data['group'].", disabling it.\n";
			$groups = new Groups();
			$groups->disableForPost($groupArr['name']);
			return "";
		}

		// If our estimate comes back with stuff we already have, finish.
		if($targetpost >= $groupArr['first_record'])
		{
			$nntp->doQuit();
			echo "Nothing to do, we already have the target post.\n\n";
			return "";
		}

		echo "Group ".$data["group"]."'s oldest article is ".number_format($data['first']).", newest is ".number_format($data['last']).". The groups retention is: ".
				((int) (($this->postdate($nntp,$data['last'],FALSE,$groupArr['name']) - $this->postdate($nntp,$data['first'],FALSE,$groupArr['name']))/86400)).
				" days.".$n."Our oldest article is: ".number_format($groupArr['first_record'])." which is (".
				((int) ((date('U') - $this->postdate($nntp,$groupArr['first_record'],FALSE,$groupArr['name']))/86400)).
				" days old). Our backfill target is article ".number_format($targetpost)." which is (".((int) ((date('U') - $this->postdate($nntp,$targetpost,FALSE,$groupArr['name']))/86400)).$n.
				" days old).\n";

		// Calculate total number of parts.
		$total = $groupArr['first_record'] - $targetpost;
		$done = false;
		// Set first and last, moving the window by maxxMssgs.
		$last = $groupArr['first_record'] - 1;
		// Set the initial "chunk".
		$first = $last - $binaries->messagebuffer + 1;
		// Just in case this is the last chunk we needed.
		if($targetpost > $first)
			$first = $targetpost;

		// In case postdate doesn't get a date.
		if (is_null($groupArr['first_record_postdate']) || $groupArr['first_record_postdate'] == "NULL")
			$firstr_date = time();
		else
			$firstr_date = strtotime($groupArr['first_record_postdate']);

		while($done === false)
		{
			$binaries->startLoop = microtime(true);

			echo "\nGetting ".($last-$first+1)." articles from ".$data["group"].", ".$left." group(s) left. (".($first-$targetpost)." articles in queue).\n";
			flush();

			$binaries->scan($nntp, $groupArr, $first, $last, 'backfill');

			$newdate = $this->postdate($nntp, $first, false, $groupArr['name'], true);
			if ($newdate !== false)
				$firstr_date = $newdate;

			$db->queryExec(sprintf("UPDATE groups SET first_record_postdate = %s, first_record = %s, last_updated = now() WHERE id = %d", $db->from_unixtime($firstr_date), $db->escapeString($first), $groupArr['id']));
			if($first==$targetpost)
				$done = true;
			else
			{
				// Keep going: set new last, new first, check for last chunk.
				$last = $first - 1;
				$first = $last - $binaries->messagebuffer + 1;
				if($targetpost > $first)
					$first = $targetpost;

			}
		}
		$nntp->doQuit();
		// Set group's first postdate.
		$db->queryExec(sprintf("UPDATE groups SET first_record_postdate = %s, last_updated = NOW() WHERE id = %d", $db->from_unixtime($firstr_date), $groupArr['id']));

		$timeGroup = number_format(microtime(true) - $this->startGroup, 2);
		echo $data["group"]." processed in ".$timeGroup." seconds.\n";
		// Increment the backfil target date.
	}

	// Returns a single timestamp from a local article number. If the article is missing, you can pass $old as true to return false (then use the last known date).
	function postdate($nntp, $post, $debug=true, $group, $old=false)
	{
		$st = false;
		if (!isset($nntp))
		{
			$nntp = new Nntp;
			$nntp->doConnectNC();
			$st = true;
		}

		$attempts=0;
		$success = false;
		do
		{
			$data = $nntp->selectGroup($group);
			if(PEAR::isError($data))
			{
				$data = $nntp->dataError($nntp, $group, false);
				if ($data === false)
					return;
			}

			$msgs = $nntp->getOverview($post."-".$post,true,false);
			if(PEAR::isError($msgs))
			{
				$nntp->doQuit();
				$nntp->doConnectNC();
				$nntp->selectGroup($group);
				$msgs = $nntp->getOverview($post."-".$post,true,false);
				if(PEAR::isError($msgs))
				{
					echo "Error {$msgs->code}: {$msgs->message}.\nUnable to fetch the article.\n";
					$nntp->doQuit();
					if ($old === true)
						return false;
					else
						return;
				}
			}

			if(!isset($msgs[0]['Date']) || $msgs[0]['Date']=="" || is_null($msgs[0]['Date']))
			{
				$post = $post+1;
				$success = false;
			}
			else
			{
				$date = $msgs[0]['Date'];
				if (strlen($date > 0))
					$success = true;
			}

			if($debug && $attempts > 0)
				echo "Retried ".$attempts." time(s).\n";

			usleep(100000);
			$attempts++;
		}while($attempts <= 5 && $success === false);

		if ($st === true)
			$nntp->doQuit();

		if ($success === false)
		{
			// Use now - 365 days.
			$date = TIME() - 31536000;
			if ($old === true)
				$date = false;
			return $date;
		}

		if($debug)
			echo "DEBUG: postdate for post: .".$post." came back ".$date." (";
		$date = strtotime($date);

		if($debug)
			echo $date." seconds unixtime or ".$this->daysOld($date)." days)\n";
		return $date;
	}

	// Returns article number based on # of days.
	function daytopost($nntp, $group, $days, $debug=true)
	{
		$n = $this->n;
		// DEBUG every postdate call?!?!
		$pddebug = $st = false;
		if ($debug)
			echo "INFO: Finding article for ".$group." ".$days." days back.\n";

		if (!isset($nntp))
		{
			$nntp = new Nntp;
			$nntp->doConnectNC();
			$st = true;
		}

		$data = $nntp->selectGroup($group);
		if(PEAR::isError($data))
		{
			$data = $nntp->dataError($nntp, $group, false);
			if ($data === false)
				return;
		}

		// Goal timestamp.
		$goaldate = date('U')-(86400*$days);
		$totalnumberofarticles = $data['last'] - $data['first'];
		$upperbound = $data['last'];
		$lowerbound = $data['first'];

		if ($debug)
			echo "Total Articles: ".number_format($totalnumberofarticles)." Newest: ".number_format($upperbound)." Oldest: ".number_format($lowerbound).$n."Goal: ".date("r", $goaldate)." ($goaldate).\n";

		if ($data['last'] == PHP_INT_MAX)
			exit("ERROR: Group data is coming back as php's max value. You should not see this since we use a patched Net_NNTP that fixes this bug.\n");

		$firstDate = $this->postdate($nntp, $data['first'], $pddebug, $group);
		$lastDate = $this->postdate($nntp, $data['last'], $pddebug, $group);

		if ($goaldate < $firstDate)
		{
			if ($st === true)
				$nntp->doQuit();
			echo "WARNING: Backfill target of $days day(s) is older than the first article stored on your news server.\nStarting from the first available article (".date("r", $firstDate)." or ".$this->daysOld($firstDate)." days).\n";
			return $data['first'];
		}
		elseif ($goaldate > $lastDate)
		{
			if ($st === true)
				$nntp->doQuit();
			echo "ERROR: Backfill target of ".$days." day(s) is newer than the last article stored on your news server.\nTo backfill this group you need to set Backfill Days to at least ".ceil($this->daysOld($lastDate)+1)." days (".date("r", $lastDate-86400).").\n";
			return "";
		}

		if ($debug)
			echo "DEBUG: Searching for postdate.\nGoaldate: ".$goaldate." (".date("r", $goaldate).").\nFirstdate: ".$firstDate." (".((is_int($firstDate))?date("r", $firstDate):'n/a').").\nLastdate: ".$lastDate." (".date("r", $lastDate).").\n";

		$interval = floor(($upperbound - $lowerbound) * 0.5);
		$dateofnextone = $templowered = "";

		if ($debug)
			echo "Start: ".$data['first']."\nEnd: ".$data['last']."\nInterval: ".$interval.$n;

		$dateofnextone = $lastDate;
		// Match on days not timestamp to speed things up.
		while($this->daysOld($dateofnextone) < $days)
		{
			while(($tmpDate = $this->postdate($nntp,($upperbound-$interval),$pddebug,$group))>$goaldate)
			{
				$upperbound = $upperbound - $interval;
				if($debug)
					echo "New upperbound (".$upperbound.") is ".$this->daysOld($tmpDate)." days old.\n";
			}

			if(!$templowered)
			{
				$interval = ceil(($interval/2));
				if($debug)
					echo "Set interval to ".$interval." articles.\n";
		 	}
		 	$dateofnextone = $this->postdate($nntp,($upperbound-1),$pddebug,$group);
			while(!$dateofnextone)
			{  $dateofnextone = $this->postdate($nntp,($upperbound-1),$pddebug,$group); }
	 	}
	 	if ($st === true)
				$nntp->doQuit();
		echo "Determined to be article $upperbound which is ".$this->daysOld($dateofnextone)." days old (".date("r", $dateofnextone).").\n";
		return $upperbound;
	}

	private function daysOld($timestamp)
	{
		return round((time()-$timestamp)/86400, 1);
	}

	function getRange($group, $first, $last, $threads)
	{
		$db = new DB();
		$n = $this->n;
		$groups = new Groups();
		$this->startGroup = microtime(true);
		$site = new Sites();
		$backthread = $site->get()->backfillthreads;
		$binaries = new Binaries();
		$groupArr = $groups->getByName($group);

		echo 'Processing '.$groupArr['name']." ==> T-".$threads." ==> ".number_format($first)." to ".number_format($last).$n;
		$this->startLoop = microtime(true);
		// Let scan handle the connection.
		$lastId = $binaries->scan(null, $groupArr, $last, $first, 'backfill');
		// Scan failed - skip group.
		if ($lastId === false)
			return;
	}

	function getFinal($group, $first)
	{
		$db = new DB();
		$groups = new Groups();
		$groupArr = $groups->getByName($group);
		// Let postdate handle the connection.
		$db->queryExec(sprintf("UPDATE groups SET first_record_postdate = %s, first_record = %s, last_updated = NOW() WHERE id = %d", $db->from_unixtime($this->postdate(null,$first,false,$group)), $db->escapeString($first), $groupArr['id']));
		echo "Backfill Safe Threaded on ".$group." completed.\n\n";
	}
}
