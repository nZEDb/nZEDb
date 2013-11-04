<?php
require_once(WWW_DIR.'lib/framework/db.php');
require_once(WWW_DIR.'lib/groups.php');
require_once(WWW_DIR.'lib/nntp.php');
require_once(WWW_DIR.'lib/binaries.php');
require_once(WWW_DIR.'lib/ColorCLI.php');

class Backfill
{
	public function __construct($site=null)
	{
		if (!isset($site))
		{
			$s = new Sites();
			$site = $s->get();
		}
		$this->safebdate = (!empty($site->safebackfilldate)) ? $site->safebackfilldate : 2012-06-24;
		$this->hashcheck = (!empty($site->hashcheck)) ? $site->hashcheck : 0;
		$this->compressedHeaders = ($site->compressedheaders == '1') ? true : false;
		$this->nntpproxy = (isset($site->nntpproxy)) ? $site->nntpproxy : 0;
		$this->tablepergroup = (isset($site->tablepergroup)) ? $site->tablepergroup : 0;
		$this->c = new ColorCLI;
		$this->primary = 'green';
		$this->warning = 'red';
		$this->header = 'yellow';
	}

	// Backfill groups using user specified time/date.
	public function backfillAllGroups($groupName='')
	{
		if ($this->hashcheck == 0)
			exit($this->c->set256($this->warning)."You must run update_binaries.php to update your collectionhash.".$this->c->rsetColor());
		$groups = new Groups();

		if ($groupName != '')
		{
			$grp = $groups->getByName($groupName);
			if ($grp)
				$res = array($grp);
		}
		else
			$res = $groups->getActiveBackfill();


		if (@$res)
		{
			$nntp = new Nntp();
			// Connect to usenet.
			if ($nntp->doConnect() === false)
			{
				echo "Error connecting to usenet.\n";
				return;
			}

			$counter = 1;
			$db = new DB();
			$binaries = new Binaries();
			foreach($res as $groupArr)
			{
				echo $this->c->set256($this->header)."\nStarting group ".$counter.' of '.sizeof($res).".\n".$this->c->rsetColor();
				$this->backfillGroup($nntp, $db, $binaries, $groupArr, sizeof($res)-$counter);
				$counter++;
			}
			$nntp->doQuit();
		}
		else
			echo $this->c->set256($this->primary)."No groups specified. Ensure groups are added to nZEDb's database for updating.\n".$this->c->rsetColor();
	}

	// Backfill 1 group using time.
	public function backfillGroup($nntp, $db, $binaries, $groupArr, $left)
	{
		$this->startGroup = microtime(true);

		if (!isset($nntp))
		{
			$nntp = new Nntp();
			if ($nntp->doConnect() === false)
				return;
		}

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
			echo $this->c->set256($this->warning).'Group '.$groupArr['name']." has invalid numbers. Have you run update on it? Have you set the backfill days amount?\n".$this->c->rsetColor();
			return;
		}

		// Check if we are grabbing further than the server has.
		if($groupArr['first_record'] <= ($data['first'] + 50000))
		{
			echo $this->c->set256($this->warning).'We have hit the maximum we can backfill for this '.$groupArr['name'].", disabling it.\n\n".$this->c->rsetColor();
			$groups = new Groups();
			$groups->disableForPost($groupArr['name']);
			return '';
		}

		// If our estimate comes back with stuff we already have, finish.
		if($targetpost >= $groupArr['first_record'])
		{
			echo $this->c->set256($this->warning)."Nothing to do, we already have the target post\n\n".$this->c->rsetColor();
			return '';
		}

		echo $this->c->set256($this->primary).'Group '.$data['group'].': server has '.number_format($data['first']).' - '.number_format($data['last']).', or ~'.
				((int) (($this->postdate($nntp,$data['last'],false,$groupArr['name'],false,'oldest') - $this->postdate($nntp,$data['first'],false,$groupArr['name'],false,'oldest'))/86400)).
				" days.\nLocal first = ".number_format($groupArr['first_record']).' ('.
				((int) ((date('U') - $this->postdate($nntp,$groupArr['first_record'],false,$groupArr['name'],false,'oldest'))/86400)).
				' days).  Backfill target of '.$groupArr['backfill_target'].' days is post '.$targetpost."\n".$this->c->rsetColor();

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
		if (is_null($groupArr['first_record_postdate']) || $groupArr['first_record_postdate'] == 'NULL')
			$firstr_date = time();
		else
			$firstr_date = strtotime($groupArr['first_record_postdate']);

		while($done === false)
		{
			$binaries->startLoop = microtime(true);

			echo $this->c->set256($this->primary).'Getting '.(number_format($last-$first+1))." articles from ".$data['group'].", ".$left." group(s) left. (".(number_format($first-$targetpost))." articles in queue).\n".$this->c->rsetColor();
			flush();
			$binaries->scan($nntp, $groupArr, $first, $last, 'backfill');
			$newdate = $this->postdate($nntp, $first, false, $groupArr['name'], true, 'oldest');
			if ($newdate !== false)
				$firstr_date = $newdate;

			$db->queryExec(sprintf('UPDATE groups SET first_record_postdate = %s, first_record = %s, last_updated = NOW() WHERE id = %d', $db->from_unixtime($firstr_date), $db->escapeString($first), $groupArr['id']));
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
		// Set group's first postdate.
		$db->queryExec(sprintf('UPDATE groups SET first_record_postdate = %s, last_updated = NOW() WHERE id = %d', $db->from_unixtime($firstr_date), $groupArr['id']));

		$timeGroup = number_format(microtime(true) - $this->startGroup, 2);
		echo $this->c->set256($this->primary).'Group processed in '.$timeGroup." seconds.\n".$this->c->rsetColor();
	}

	// Safe backfill using posts. Going back to a date specified by the user on the site settings.
	// This does 1 group for x amount of parts until it reaches the date.
	public function safeBackfill($articles='')
	{
		if ($this->hashcheck == 0)
			exit("You must run update_binaries.php to update your collectionhash.\n");

		$db = new DB();
		$groupname = $db->queryOneRow(sprintf('SELECT name FROM groups WHERE first_record_postdate BETWEEN %s AND NOW() AND backfill = 1 ORDER BY name ASC', $db->escapeString($this->safebdate)));

		if (!$groupname)
			exit('No groups to backfill, they are all at the target date '.$this->safebdate.", or you have not enabled them to be backfilled in the groups page.\n");
		else
			$this->backfillPostAllGroups($groupname['name'], $articles);
	}

	// Backfill groups using user specified article count.
	public function backfillPostAllGroups($groupName='', $articles='', $type='')
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
			if ($type == 'normal')
				$res = $groups->getActiveBackfill();
			else if ($type == 'date')
				$res = $groups->getActiveByDateBackfill();
		}

		if (@$res)
		{
			$nntp = new Nntp();
			// Connect to usenet.
			if ($nntp->doConnect() === false)
			{
				echo "Error connecting to usenet.\n";
				return;
			}

			$counter = 1;
			$db = new DB();
			$binaries = new Binaries();
			foreach($res as $groupArr)
			{
				echo $this->c->set256($this->header)."\nStarting group ".$counter.' of '.sizeof($res).".\n".$this->c->rsetColor();
				$this->backfillPostGroup($nntp, $db, $binaries, $groupArr, $articles, sizeof($res)-$counter);
				$counter++;
			}
			$nntp->doQuit();
		}
		else
			echo $this->c->set256($this->warning)."No groups specified. Ensure groups are added to nZEDb's database for updating.\n".$this->c->rsetColor();
	}

	public function backfillPostGroup($nntp, $db, $binaries, $groupArr, $articles='', $left)
	{
		$this->startGroup = microtime(true);

		echo $this->c->set256($this->header).'Processing '.$groupArr['name']."\n".$this->c->rsetColor();

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
			echo $this->c->set256($this->warning).'You need to run update_binaries on the '.$data['group'].". Otherwise the group is dead, you must disable it.\n".$this->c->rsetColor();
			return '';
		}

		// Check if we are grabbing further than the server has.
		if($groupArr['first_record'] <= $data['first']+$articles)
		{
			echo $this->c->set256($this->warning).'We have hit the maximum we can backfill for '.$data['group'].", disabling it.\n\n".$this->c->rsetColor();
			$groups = new Groups();
			$groups->disableForPost($groupArr['name']);
			return '';
		}

		// If our estimate comes back with stuff we already have, finish.
		if($targetpost >= $groupArr['first_record'])
		{
			echo $this->c->set256($this->warning)."Nothing to do, we already have the target post.\n\n".$this->c->rsetColor();
			return '';
		}

		echo $this->c->set256($this->primary).'Group '.$data['group']."'s oldest article is ".number_format($data['first']).', newest is '.number_format($data['last']).'. The groups retention is: '.
				((int) (($this->postdate($nntp,$data['last'],false,$groupArr['name'],false,'oldest') - $this->postdate($nntp,$data['first'],false,$groupArr['name'],false,'oldest'))/86400)).
				" days.\nOur oldest article is: ".number_format($groupArr['first_record']).' which is ('.
				((int) ((date('U') - $this->postdate($nntp,$groupArr['first_record'],false,$groupArr['name'],false,'oldest'))/86400)).
				' days old). Our backfill target is article '.number_format($targetpost).' which is ('.((int) ((date('U') - $this->postdate($nntp,$targetpost,false,$groupArr['name'],false,'oldest'))/86400)).
				"\n days old).\n".$this->c->rsetColor();

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
		if (is_null($groupArr['first_record_postdate']) || $groupArr['first_record_postdate'] == 'NULL')
			$firstr_date = time();
		else
			$firstr_date = strtotime($groupArr['first_record_postdate']);

		while($done === false)
		{
			$binaries->startLoop = microtime(true);

			echo $this->c->set256($this->primary)."\nGetting ".($last-$first+1)." articles from ".$data['group'].", ".$left." group(s) left. (".(number_format($first-$targetpost))." articles in queue)\n".$this->c->rsetColor();
			flush();
			$binaries->scan($nntp, $groupArr, $first, $last, 'backfill');
			$newdate = $this->postdate($nntp, $first, false, $groupArr['name'], true, 'oldest');
			if ($newdate !== false)
				$firstr_date = $newdate;

			$db->queryExec(sprintf('UPDATE groups SET first_record_postdate = %s, first_record = %s, last_updated = NOW() WHERE id = %d', $db->from_unixtime($firstr_date), $db->escapeString($first), $groupArr['id']));
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
		// Set group's first postdate.
		$db->queryExec(sprintf('UPDATE groups SET first_record_postdate = %s, last_updated = NOW() WHERE id = %d', $db->from_unixtime($firstr_date), $groupArr['id']));

		$timeGroup = number_format(microtime(true) - $this->startGroup, 2);
		echo $this->c->set256($this->header).$data['group'].' processed in '.$timeGroup." seconds.\n".$this->c->rsetColor();
	}

	// Returns a single timestamp from a local article number. If the article is missing, you can pass $old as true to return false (then use the last known date).
	public function postdate($nntp, $post, $debug=true, $group, $old=false, $type='newest')
	{
		$db = new DB();
		$st = false;
		$keeppost = $post;
		if (!isset($nntp) || $nntp->doConnect() === false)
		{
			$nntp = new Nntp();
			if ($nntp->doConnect() === false)
				return;
			$st = true;
		}
		$attempts=0;
		$success = $record = false;
		do
		{
			$data = $nntp->selectGroup($group);
			if (PEAR::isError($data))
			{
				$data = $nntp->dataError($nntp, $group);
				if ($data === false)
					return;
			}
			$msgs = $nntp->getOverview($post."-".$post, true, false);
			if (PEAR::isError($msgs))
			{
				$nntp->doQuit();
				if ($nntp->doConnect() === false)
					return;

				$nntp->selectGroup($group);
				$msgs = $nntp->getOverview($post."-".$post, true, false);
				if (PEAR::isError($msgs))
				{
					echo $this->c->set256($this->warning)."Error {$msgs->code}: {$msgs->message}.\nUnable to fetch the article.".$this->c->rsetColor();
					if ($old === true)
						return false;
					else
						return;
				}
			}
			// Set table names
			$groups = new Groups();
			$groupID = $groups->getIDByName($group);
			if ($this->tablepergroup == 1)
			{
				if ($db->newtables($groupID) === false)
					exit ("There is a problem creating new parts/files tables for this group.\n");
				$groupa = array();
				$groupa['cname'] = $groupID.'_collections';
				$groupa['bname'] = $groupID.'_binaries';
				$groupa['pname'] = $groupID.'_parts';
			}
			else
			{
				$groupa = array();
				$groupa['cname'] = 'collections';
				$groupa['bname'] = 'binaries';
				$groupa['pname'] = 'parts';
			}

			if (!isset($msgs[0]['Date']) || $msgs[0]['Date'] == '' || is_null($msgs[0]['Date']))
			{
				$old_post = $post;
				if ($attempts == 0)
				{
					if ($type == 'newest')
					{
						$res = $db->queryOneRow('SELECT p.number AS number FROM '.$groupa['cname'].' c, '.$groupa['bname'].' b, '.$groupa['pname'].' p WHERE c.id = b.collectionid AND b.id = p.binaryid AND c.groupid = '.$groupID.' ORDER BY p.number DESC LIMIT 1');
						if (isset($res['namber']))
						{
							$post = $res['number'];
							echo $this->c->set256($this->warning).'Error: Unable to fetch article '.$old_post.' from '.$group.'. Retrying with newest article, from parts table, ['.$post.'] from '.$groupa['pname'].".\n".$this->c->rsetColor();
							$record = true;
						}
					}
					else
					{
						$res = $db->queryOneRow('SELECT p.number FROM '.$groupa['cname'].' c, '.$groupa['bname'].' b, '.$groupa['pname'].' p WHERE c.id = b.collectionid AND b.id = p.binaryid AND c.groupid = '.$groupID.' ORDER BY p.number ASC LIMIT 1');
						if (isset($res['namber']))
						{
							$post = $res['number'];
							echo $this->c->set256($this->warning).'Error: Unable to fetch article '.$old_post.' from '.$group.'. Retrying with oldest article, from parts table, ['.$post.'] from '.$groupa['pname'].".\n\n\n".$this->c->rsetColor();
							$record = true;
						}
					}
					$success = false;
				}
				if ($record === false)
				{
					$old_post = $post;
					// for random, backfill will increase into range that we have and binary will decrease into the range that we have
					if ($type == 'newest')
						$post = ($post - MT_RAND(100,500));
					else
						$post = ($post + MT_RAND(100,500));
					echo $this->c->set256($this->warning).'Error: Unable to fetch article '.$old_post.' from '.$group.'. Retrying with article '.$post.".\n".$this->c->rsetColor();
					$success = false;
					$record = false;
				}
			}
			else if (isset($msgs[0]['Date']) && $msgs[0]['Date'] != '')
			{
				$date = $msgs[0]['Date'];
				if (strlen($date) > 0)
					$success = true;
			}

			if ($debug && $attempts > 0)
				echo $this->c->set256($this->warning).'Retried '.$attempts." time(s).\n".$this->c->rsetColor();

			$attempts++;
		} while ($attempts <= 20 && $success === false);

		if ($st === true)
			$nntp->doQuit();
		if ($success === false && $old === true)
		{
			if ($type == 'oldest')
			{
				$res = $db->queryOneRow(sprintf("SELECT first_record_postdate from groups where name = '%s'", $group));
				if (array_key_exists('first_record_postdate', $res))
				{
					echo $this->c->set256($this->warning).'Error: Unable to fetch article '.$keeppost.' from '.$group.'. Using current first_record_postdate['.$res['first_record_postdate']."], instead.\n".$this->c->rsetColor();
					return strtotime($res['first_record_postdate']);
				}
				else
					return false;
			}
			else
			{
				$res = $db->queryOneRow(sprintf("SELECT last_record_postdate from groups where name = '%s'", $group));
				if (array_key_exists('last_record_postdate', $res))
				{
					echo $this->c->set256($this->warning).'Error: Unable to fetch article '.$keeppost.' from '.$group.'. Using current last_record_postdate['.$res['last_record_postdate']."], instead.\n".$this->c->rsetColor();
					return strtotime($res['last_record_postdate']);
				}
				else
					return false;
			}
		}
		else if($success === false)
			return false;

		if ($record === true)
		{
			$db = new DB();
			if ($type = 'newest')
				$db->queryExec('UPDATE groups set first_record = '.$post);
			else
				$db->queryExec('UPDATE groups set last_record = '.$post);
		}

		if ($debug)
			echo $this->c->set256($this->primary).'DEBUG: postdate for post: '.$post.' came back '.$date.' ('.$this->c->rsetColor();
		$date = strtotime($date);

		if ($debug)
			echo $this->c->set256($this->primary).$date.' seconds unixtime or '.$this->daysOld($date)." days)\n".$this->c->rsetColor();
		return $date;
	}

	// Returns article number based on # of days.
	public function daytopost($nntp, $group, $days, $debug=true)
	{
		// DEBUG every postdate call?!?!
		$pddebug = $st = false;
		if ($debug)
			echo $this->c->set256($this->primary).'INFO: Finding article for '.$group.' '.$days." days back.\n".$this->c->rsetColor();

		if (!isset($nntp))
		{
			$nntp = new Nntp();
			if ($nntp->doConnectNC() === false)
				return;

			$st = true;
		}

		$data = $nntp->selectGroup($group);
		if (PEAR::isError($data))
		{
			$data = $nntp->dataError($nntp, $group);
			if ($data === false)
				return;
		}

		// Goal timestamp.
		$goaldate = date('U')-(86400*$days);
		$totalnumberofarticles = $data['last'] - $data['first'];
		$upperbound = $data['last'];
		$lowerbound = $data['first'];

		if ($debug)
			echo $this->c->set256($this->primary).'Total Articles: '.number_format($totalnumberofarticles).' Newest: '.number_format($upperbound).' Oldest: '.number_format($lowerbound)."\nGoal: ".date('r', $goaldate)." ({$goaldate}).\n".$this->c->rsetColor();

		if ($data['last'] == PHP_INT_MAX)
			exit($this->c->set256($this->warning)."ERROR: Group data is coming back as php's max value. You should not see this since we use a patched Net_NNTP that fixes this bug.\n");

		$firstDate = $this->postdate($nntp, $data['first'], $pddebug, $group, false, 'oldest');
		$lastDate = $this->postdate($nntp, $data['last'], $pddebug, $group, false, 'oldest');

		if ($goaldate < $firstDate)
		{
			if ($st === true)
				$nntp->doQuit();
			echo $this->c->set256($this->warning)."WARNING: Backfill target of $days day(s) is older than the first article stored on your news server.\nStarting from the first available article (".date('r', $firstDate).' or '.$this->daysOld($firstDate)." days).\n".$this->c->rsetColor();
			return $data['first'];
		}
		elseif ($goaldate > $lastDate)
		{
			if ($st === true)
				$nntp->doQuit();
			echo $this->c->set256($this->warning).'ERROR: Backfill target of '.$days." day(s) is newer than the last article stored on your news server.\nTo backfill this group you need to set Backfill Days to at least ".ceil($this->daysOld($lastDate)+1).' days ('.date('r', $lastDate-86400).").\n".$this->c->rsetColor();
			return '';
		}

		$mask1 = "%-25s %15s %-30s\n";
		$mask2 = "%-25s %15s\n";
		$mask3 = "%-10s %30s\n";
		if ($debug)
		{
			printf($mask3, 'DEBUG:', 'Searching for postdate.');
			printf($mask1, 'Goaldate:', $goaldate, '('.date('r', $goaldate).')');
			printf($mask1, 'Firstdate:', $firstDate, '('.((is_int($firstDate))?date('r', $firstDate):'n/a').')');
			printf($mask1, 'Lastdate:', $lastDate, '('.date('r', $lastDate).')');
		}

		$interval = floor(($upperbound - $lowerbound) * 0.5);
		$dateofnextone = $templowered = '';

		if ($debug)
		{
			printf($mask2, 'First Post:', number_format($data['first']));
			printf($mask2, 'Last Post:', number_format($data['last']));
			printf($mask2, 'Posts Available:', number_format($interval*2));
		}

		$dateofnextone = $lastDate;
		// Match on days not timestamp to speed things up.
		while($this->daysOld($dateofnextone) < $days)
		{
			while(($tmpDate = $this->postdate($nntp,($upperbound-$interval),$pddebug,$group,false,'oldest'))>$goaldate)
			{
				$upperbound = $upperbound - $interval;
				if ($debug)
					printf($mask1, 'New upperbound:', number_format($upperbound), 'is '.$this->daysOld($tmpDate).' days old.');
			}

			if(!$templowered)
			{
				$interval = ceil(($interval/2));
				if ($debug)
					printf($mask1, 'Checking interval at:', number_format($interval), 'articles.');
		 	}
		 	$dateofnextone = $this->postdate($nntp,($upperbound-1),$pddebug,$group,false,'oldest');
			while(!$dateofnextone)
			{  $dateofnextone = $this->postdate($nntp,($upperbound-1),$pddebug,$group,false,'oldest'); }
	 	}
	 	if ($st === true)
			$nntp->doQuit();

		printf($mask1, 'Determined to be article:', number_format($upperbound), 'which is '.$this->daysOld($dateofnextone).' days old ('.date('r', $dateofnextone).')');
		return $upperbound;
	}

	private function daysOld($timestamp)
	{
		return round((time()-$timestamp)/86400, 1);
	}

	public function getRange($group, $first, $last, $threads)
	{
		$groups = new Groups();
		$this->startGroup = microtime(true);
		$site = new Sites();
		$backthread = $site->get()->backfillthreads;
		$binaries = new Binaries();
		$groupArr = $groups->getByName($group);

		if ($this->nntpproxy == 0)
			echo $this->c->set256($this->header).'Processing '.str_replace('alt.binaries', 'a.b', $groupArr['name']).(($this->compressedHeaders)?' Using Compression':' Not Using Compression').' ==> T-'.$threads.' ==> '.number_format($first).' to '.number_format($last)."\n".$this->c->rsetColor();
		else
			echo $this->c->set256($this->header).'Processing '.str_replace('alt.binaries', 'a.b', $groupArr['name']).' Using NNTPProxy ==> T-'.$threads.' ==> '.number_format($first).' to '.number_format($last)."\n".$this->c->rsetColor();
		$this->startLoop = microtime(true);
		// Let scan handle the connection.
		$lastId = $binaries->scan(null, $groupArr, $last, $first, 'backfill');
		// Scan failed - retry once
		if ($lastId === false)
			$binaries->scan(null, $groupArr, $last, $first, 'backfill');
	}

	function getFinal($group, $first, $type)
	{
		$db = new DB();
		$groups = new Groups();
		$groupArr = $groups->getByName($group);
		if ($type == 'Backfill')
			$postsdate = $this->postdate(null,$first,false,$group,true,'oldest');
		else
			$postsdate = $this->postdate(null,$first,false,$group,true,'newest');

		$postsdate = $db->from_unixtime($postsdate);
		if ($type == 'Backfill')
			$db->queryExec(sprintf('UPDATE groups SET first_record_postdate = %s, first_record = %s, last_updated = NOW() WHERE id = %d', $postsdate, $db->escapeString($first), $groupArr['id']));
		else
			$db->queryExec(sprintf('UPDATE groups SET last_record_postdate = %s, last_record = %s, last_updated = NOW() WHERE id = %d', $postsdate, $db->escapeString($first), $groupArr['id']));

		echo $this->c->set256($this->primary).$type.' Safe Threaded for '.$group." completed.\n".$this->c->rsetColor();
	}
}
