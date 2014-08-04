<?php

class Backfill
{

	/**
	 * @var Binaries
	 */
	protected $_binaries;

	/**
	 * Instance of class ColorCLI.
	 *
	 * @var ColorCLI
	 */
	protected $_colorCLI;

	/**
	 * Instance of class Settings
	 *
	 * @var nzedb\db\DB
	 */
	protected $_pdo;

	/**
	 * Instance of class debugging.
	 *
	 * @var Debugging
	 */
	protected $_debugging;

	/**
	 * @var Groups
	 */
	protected $_groups;

	/**
	 * @var NNTP
	 */
	protected $_nntp;
	/**
	 * Should we use compression for headers?
	 *
	 * @var bool
	 */
	protected $_compressedHeaders;

	/**
	 * Log and or echo debug.
	 * @var bool
	 */
	protected $_debug = false;

	/**
	 * Echo to cli?
	 * @var bool
	 */
	protected $_echoCLI;

	/**
	 * Are we using nntp proxy?
	 *
	 * @var bool
	 */
	protected $_nntpProxy;

	/**
	 * Should we use tpg?
	 *
	 * @var bool
	 */
	protected $_tablePerGroup;

	/**
	 * How far back should we go on safe back fill?
	 *
	 * @var string
	 */
	protected $_safeBackFillDate;

	/**
	 * @var string
	 */
	protected $_safePartRepair;

	/**
	 * Constructor.
	 *
	 * @param array $options Class instances / Echo to cli?
	 */
	public function __construct(array $options = array())
	{
		$defOptions = [
			'Echo'     => true,
			'ColorCLI' => null,
			'Groups'   => null,
			'NNTP'     => null,
			'Settings' => null
		];
		$defOptions = array_replace($defOptions, $options);

		$this->_echoCLI = ($defOptions['Echo'] && nZEDb_ECHOCLI);

		$this->_colorCLI = ($defOptions['ColorCLI'] instanceof ColorCLI ? $defOptions['ColorCLI'] : new ColorCLI());
		$this->_pdo = ($defOptions['Settings'] instanceof \nzedb\db\Settings ? $defOptions['Settings'] : new \nzedb\db\Settings());
		$this->_groups = ($defOptions['Groups'] instanceof Groups ? $defOptions['Groups'] : new Groups(['Settings' => $this->_pdo]));
		$this->_nntp = ($defOptions['NNTP'] instanceof NNTP
			? $defOptions['NNTP'] : new NNTP(['Settings' => $this->_pdo, 'Echo' => $this->_echoCLI, 'ColorCLI' => $this->_colorCLI])
		);

		$this->_debug = (nZEDb_LOGGING || nZEDb_DEBUG);
		if ($this->_debug) {
			$this->_debugging = new Debugging(['Class' => 'Backfill', 'ColorCLI' => $this->_colorCLI]);
		}

		$this->_compressedHeaders = ($this->_pdo->getSetting('compressedheaders') == 1 ? true : false);
		$this->_nntpProxy = ($this->_pdo->getSetting('nntpproxy') == 1 ? true : false);
		$this->_safeBackFillDate = ($this->_pdo->getSetting('safebackfilldate') != '') ? $this->_pdo->getSetting('safebackfilldate') : '2008-08-14';
		$this->_safePartRepair = ($this->_pdo->getSetting('safepartrepair') == 1 ? 'update' : 'backfill');
		$this->_tablePerGroup = ($this->_pdo->getSetting('tablepergroup') == 1 ? true : false);
	}

	/**
	 * Backfill all the groups up to user specified time/date.
	 *
	 * @param string $groupName
	 * @param string|int $articles
	 * @param string $type
	 *
	 * @return void
	 */
	public function backfillAllGroups($groupName = '', $articles ='', $type = '')
	{
		$res = array();
		if ($groupName !== '') {
			$grp = $this->_groups->getByName($groupName);
			if ($grp) {
				$res = array($grp);
			}
		} else {
			if ($type === 'normal' || $type === '') {
				$res = $this->_groups->getActiveBackfill();
			} else if ($type === 'date') {
				$res = $this->_groups->getActiveByDateBackfill();
			}
		}

		$groupCount = count($res);
		if ($groupCount > 0) {
			$counter = 1;
			$allTime = microtime(true);
			$dMessage = (
				'Backfilling: ' .
				$groupCount .
				' group(s) - Using compression? ' .
				($this->_compressedHeaders ? 'Yes' : 'No')
			);
			if ($this->_debug) {
				$this->_debugging->start("backfillAllGroups", $dMessage, 5);
			}

			if ($this->_echoCLI) {
				$this->_colorCLI->doEcho($this->_colorCLI->header($dMessage), true);
			}

			$this->_binaries = new Binaries(['NNTP' => $this->_nntp, 'Echo' => $this->_echoCLI, 'Backfill' => $this, 'ColorCLI' => $this->_colorCLI, 'Settings' => $this->_pdo, 'Groups' => $this->_groups]);

			if ($articles !== '' && !is_numeric($articles)) {
				$articles = 20000;
			}

			// Loop through groups.
			foreach ($res as $groupArr) {
				if ($groupName === '') {
					$dMessage = "Starting group " . $counter . ' of ' . $groupCount;
					if ($this->_debug) {
						$this->_debugging->start("backfillAllGroups", $dMessage, 5);
					}

					if ($this->_echoCLI) {
						$this->_colorCLI->doEcho($this->_colorCLI->header($dMessage), true);
					}
				}
				$this->backfillGroup($groupArr, $groupCount - $counter, $articles);
				$counter++;
			}

			$dMessage = 'Backfilling completed in ' . number_format(microtime(true) - $allTime, 2) . " seconds.";
			if ($this->_debug) {
				$this->_debugging->start("backfillAllGroups", $dMessage, 5);
			}

			if ($this->_echoCLI) {
				$this->_colorCLI->doEcho($this->_colorCLI->primary($dMessage));
			}
		} else {
			$dMessage = "No groups specified. Ensure groups are added to nZEDb's database for updating.";
			if ($this->_debug) {
				$this->_debugging->start("backfillAllGroups", $dMessage, 1);
			}

			if ($this->_echoCLI) {
				$this->_colorCLI->doEcho($this->_colorCLI->warning($dMessage), true);
			}
		}
	}

	/**
	 * Backfill single group.
	 *
	 * @param array $groupArr
	 * @param int $left
	 * @param int|string $articles
	 *
	 * @return void
	 */
	public function backfillGroup($groupArr, $left, $articles = '')
	{
		// Start time for this group.
		$startGroup = microtime(true);
		$groupName = str_replace('alt.binaries', 'a.b', $groupArr['name']);

		// If our local oldest article 0, it means we never ran update_binaries on the group.
		if ($groupArr['first_record'] <= 0) {
			$dMessage =
				"You need to run update_binaries on " .
				$groupName .
				". Otherwise the group is dead, you must disable it.";
			if ($this->_debug) {
				$this->_debugging->start("backfillGroup", $dMessage, 2);
			}

			if ($this->_echoCLI) {
				$this->_colorCLI->doEcho($this->_colorCLI->error($dMessage));
			}
			return;
		}

		// Select group, here, only once
		$data = $this->_nntp->selectGroup($groupArr['name']);
		if ($this->_nntp->isError($data)) {
			$data = $this->_nntp->dataError($this->_nntp, $groupArr['name']);
			if ($this->_nntp->isError($data)) {
				return;
			}
		}

		if ($this->_echoCLI) {
			$this->_colorCLI->doEcho($this->_colorCLI->primary('Processing ' . $groupName), true);
		}

		// Check if this is days or post backfill.
		$postCheck = ($articles === '' ? false : true);

		// Get target post based on date or user specified number.
		$targetpost = (string)($postCheck
			?
				round($groupArr['first_record'] - $articles)
			:
				$this->daytopost($groupArr['backfill_target'], $data)
		);

		// Check if target post is smaller than server's oldest, set it to oldest if so.
		if ($targetpost < $data['first']) {
			$targetpost = $data['first'];
		}

		// Check if our target post is newer than our oldest post or if our local oldest article is older than the servers oldest.
		if ($targetpost >= $groupArr['first_record'] || $groupArr['first_record'] <= $data['first']) {
			$dMessage =
				"We have hit the maximum we can backfill for " .
				$groupName .
				", skipping it, consider disabling backfill on it.";
			if ($this->_debug) {
				$this->_debugging->start("backfillGroup", $dMessage, 4);
			}

			if ($this->_echoCLI) {
				$this->_colorCLI->doEcho($this->_colorCLI->notice($dMessage), true);
			}
			return;
		}

		if ($this->_echoCLI) {
			$this->_colorCLI->doEcho(
				$this->_colorCLI->primary(
					'Group ' .
					$groupName .
					"'s oldest article is " .
					number_format($data['first']) .
					', newest is ' .
					number_format($data['last']) .
					".\nOur target article is " .
					number_format($targetpost) .
					'. Our oldest article is article ' .
					number_format($groupArr['first_record']) .
					'.'
				)
			);
		}

		// Set first and last, moving the window by max messages.
		$last = (string)($groupArr['first_record'] - 1);
		// Set the initial "chunk".
		$first = (string)($last - $this->_binaries->messageBuffer + 1);

		// Just in case this is the last chunk we needed.
		if ($targetpost > $first) {
			$first = $targetpost;
		}

		$done = false;
		while ($done === false) {

			if ($this->_echoCLI) {
				$this->_colorCLI->doEcho(
					$this->_colorCLI->set256('Yellow') .
					"\nGetting " .
					(number_format($last - $first + 1)) .
					" articles from " .
					$groupName .
					", " .
					$left .
					" group(s) left. (" .
					(number_format($first - $targetpost)) .
					" articles in queue)." .
					$this->_colorCLI->rsetColor(), true
				);
			}

			flush();
			$lastMsg = $this->_binaries->scan($groupArr, $first, $last, $this->_safePartRepair);

			// Get the oldest date.
			if (isset($lastMsg['firstArticleDate'])) {
				// Try to get it from the oldest pulled article.
				$newdate = strtotime($lastMsg['firstArticleDate']);
			} else {
				// If above failed, try to get it with postdate method.
				$newdate = $this->postdate($first, $data);
			}

			$this->_pdo->queryExec(
				sprintf('
					UPDATE groups
					SET first_record_postdate = %s, first_record = %s, last_updated = NOW()
					WHERE id = %d',
					$this->_pdo->from_unixtime($newdate),
					$this->_pdo->escapeString($first),
					$groupArr['id'])
			);
			if ($first == $targetpost) {
				$done = true;
			} else {
				// Keep going: set new last, new first, check for last chunk.
				$last = (string)($first - 1);
				$first = (string)($last - $this->_binaries->messageBuffer + 1);
				if ($targetpost > $first) {
					$first = $targetpost;
				}
			}
		}

		if ($this->_echoCLI) {
			$this->_colorCLI->doEcho(
				$this->_colorCLI->primary(
					PHP_EOL .
					'Group ' .
					$groupName .
					' processed in ' .
					number_format(microtime(true) - $startGroup, 2) .
					" seconds."
				), true
			);
		}
	}

	/**
	 * Safe backfill using posts. Going back to a date specified by the user on the site settings.
	 * This does 1 group for x amount of parts until it reaches the date.
	 *
	 * @param string $articles
	 *
	 * @return void
	 */
	public function safeBackfill($articles = '')
	{
		$groupname = $this->_pdo->queryOneRow(
			sprintf('
				SELECT name FROM groups
				WHERE first_record_postdate BETWEEN %s AND NOW()
				AND backfill = 1
				ORDER BY name ASC',
				$this->_pdo->escapeString($this->_safeBackFillDate)
			)
		);

		if (!$groupname) {
			$dMessage =
				'No groups to backfill, they are all at the target date ' .
				$this->_safeBackFillDate .
				", or you have not enabled them to be backfilled in the groups page.\n";
			if ($this->_debug) {
				$this->_debugging->start("safeBackfill", $dMessage, 1);
			}
			exit($dMessage);
		} else {
			$this->backfillAllGroups($groupname['name'], $articles);
		}
	}

	/**
	 * Returns a single timestamp from a local article number.
	 * If the article is missing, you can pass $old as true to return false (then use the last known date).
	 *
	 * @param int    $post      The article number to download.
	 * @param array  $groupData Usenet group info from NNTP selectGroup method.
	 *
	 * @return bool|int
	 */
	public function postdate($post, $groupData)
	{
		// Set table names
		$groupID = $this->_groups->getIDByName($groupData['group']);
		$group = array();
		if ($groupID !== '') {
			$group = $this->_groups->getCBPTableNames($this->_tablePerGroup, $groupID);
		}

		$currentPost = $post;

		$attempts = $date = 0;
		do {
			$attempts++;

			// Download a single article.
			$header = $this->_nntp->getXOVER($currentPost . "-" . $currentPost);

			// Check if the article is missing, if it is, retry downloading it.
			if (!$this->_nntp->isError($header)) {

				// Check if the date is set.
				if (isset($header[0]['Date']) && strlen($header[0]['Date']) > 0) {
					$date = $header[0]['Date'];
					break;
				}
			} else {
				$local = false;
				if ($groupID !== '') {
					// Try to get locally.
					$local = $this->_pdo->queryOneRow(
						'SELECT c.date AS date FROM ' .
						$group['cname'] .
						' c, ' .
						$group['bname'] .
						' b, ' .
						$group['pname'] .
						' p WHERE c.id = b.collectionid AND b.id = p.binaryid AND c.group_id = ' .
						$groupID .
						' AND p.number = ' .
						$currentPost .
						' LIMIT 1'
					);
				}

				// If the row exists return.
				if ($local !== false) {
					$date = $local['date'];
					break;
				}
			}

			// Increment $currentPost if closer to oldest post.
			$minPossible = ($currentPost - $groupData['first']);
			if ($minPossible <= 1) {
				// If we hit the minimum, try to decrement instead.
				$maxPossible = ($groupData['last'] - $currentPost);
				if ($maxPossible <= 1) {
					break;
				} else {
					// Change current post to 0.5 to 2.5% lower.
					$currentPost = round($currentPost / (mt_rand(1005, 1025) / 1000), 0 , PHP_ROUND_HALF_UP);
					if ($currentPost <= $groupData['first']) {
						break;
					}
				}
			} else {
				// Change current post to 0.5 to 2.5% higher.
				$currentPost += round((mt_rand(1005, 1025) / 1000) * $currentPost, 0 , PHP_ROUND_HALF_UP);
				if ($currentPost >= $groupData['last']) {
					break;
				}
			}

			if ($this->_debug) {
				$this->_colorCLI->doEcho($this->_colorCLI->debug('Postdate retried ' . $attempts . " time(s)."));
			}
		} while ($attempts <= 20);

		// If we didn't get a date, set it to now.
		if ($date === 0) {
			$date = Date('r');
		}

		$date = strtotime($date);

		if ($this->_debug && $date !== false) {
			$this->_debugging->start(
				"postdate",
				'Article (' .
				$post .
				"'s) date is (" .
				$date .
				') (' .
				$this->daysOld($date) .
				" days old)",
				5);
		}

		return $date;
	}

	/**
	 * Returns article number based on # of days.
	 *
	 * @param int   $days      How many days back we want to go.
	 * @param array $data      Group data from usenet.
	 *
	 * @return string
	 */
	public function daytopost($days, $data)
	{
		if ($this->_debug) {
			$this->_debugging->start("daytopost", 'Finding article for ' . $data['group'] . ' ' . $days . " days back.", 5);
		}

		// The date we want.
		$goaldate =
			//current unix time (ex. 1395699114)
			time()
			//minus
			-
			// 86400 (seconds in a day) times days wanted. (ie 1395699114 - 2592000 (30days)) = 1393107114
			(86400 * $days);

		// The total number of articles in this group.
		$totalnumberofarticles = $data['last'] - $data['first'];

		// The newest article in the group.
		$upperbound = $data['last'];
		// The oldest article in the group.
		$lowerbound = $data['first'];

		if ($this->_debug) {
			$this->_debugging->start(
				"daytopost",
				'Total Articles: (' .
				number_format($totalnumberofarticles) .
				') Newest: (' .
				number_format($upperbound) .
				') Oldest: (' .
				number_format($lowerbound) .
				") Goal: (" .
				date('r', $goaldate)
				.')',
				5);
		}

		// The servers oldest date.
		$firstDate = $this->postdate($data['first'], $data);
		// The servers newest date.
		$lastDate = $this->postdate($data['last'], $data);

		// If the date we want is older than the oldest date in the group return the groups oldest article.
		if ($goaldate < $firstDate) {
			$dMessage =
				"Backfill target of $days day(s) is older than the first article stored on your news server.\nStarting from the first available article (" .
				date('r', $firstDate) . ' or ' .
				$this->daysOld($firstDate) . " days).";
			if ($this->_debug) {
				$this->_debugging->start("daytopost", $dMessage, 3);
			}

			if ($this->_echoCLI) {
				$this->_colorCLI->doEcho($this->_colorCLI->warning($dMessage), true);
			}
			return $data['first'];

		// If the date we want is newer than the groups newest date, return the groups newest article.
		} else if ($goaldate > $lastDate) {
			$dMessage =
				'Backfill target of ' .
				$days .
				" day(s) is newer than the last article stored on your news server.\nTo backfill this group you need to set Backfill Days to at least " .
				ceil($this->daysOld($lastDate) + 1) .
				' days (' .
				date('r', $lastDate - 86400) .
				").";
			if ($this->_debug) {
				$this->_debugging->start("daytopost", $dMessage, 2);
			}

			if ($this->_echoCLI) {
				$this->_colorCLI->doEcho($this->_colorCLI->error($dMessage), true);
			}
			return $data['last'];
		}

		if ($this->_debug) {
			$this->_debugging->start("daytopost",
				'Searching for postdate. Goal: ' .
				'(' .
				date('r',  $goaldate) .
				') Firstdate: ' .
				'(' .
				((is_int($firstDate)) ? date('r', $firstDate) : 'n/a') .
				')' .
				' Lastdate: ' .
				'(' .
				date('r', $lastDate) .
				')',
				5);
		}

		// Half of total groups articles.
		$interval = floor(($upperbound - $lowerbound) * 0.5);
		$dateofnextone = $lastDate;

		if ($this->_debug) {
			$this->_debugging->start(
				"daytopost",
				'First Post: ' .
				number_format($data['first']) .
				' Last Post: ' .
				number_format($data['last']) .
				' Posts Available: ' .
				number_format($interval * 2),
				5);
		}

		$firstTries = $middleTries = $endTries = 0;
		$done = false;
		// Loop until wanted days is bigger than found days.
		while (!$done) {

			// Keep going half way from oldest to newest article, trying to get a date until we have a date newer than the goal.
			$tmpDate =$this->postdate(($upperbound - $interval), $data);
			if (round($tmpDate) >= $goaldate || $firstTries++ >= 30) {

				// Now we found a date newer than the goal, so try going back older (in smaller steps) until we get closer to the target date.
				while (true) {
					$interval = ceil(($interval * 1.08));
					if ($this->_debug) {
						$this->_debugging->start(
							"daytopost",
							'Increased interval to: (' .
							number_format($interval) .
							') articles, article ' .
							($upperbound - $interval),
							5);
					}

					$tmpDate =$this->postdate(($upperbound - $interval), $data);

					// Go newer again, in even smaller steps.
					if (round($tmpDate) <= $goaldate || $middleTries++ >= 20) {
						while (true) {
							$interval = ceil(($interval / 1.008));
							if ($this->_debug) {
								$this->_debugging->start(
									"daytopost",
									'Increased interval to: (' .
									number_format($interval) .
									') articles, article ' .
									($upperbound - $interval),
									5);
							}

							$tmpDate =$this->postdate(($upperbound - $interval), $data);
							if (round($tmpDate) >= $goaldate || $endTries++ > 10) {
								$dateofnextone = $tmpDate;
								$upperbound = ($upperbound - $interval);
								$done = true;
								break;
							}
						}
					}
					if ($done) {
						break;
					}
				}
			} else {
				$interval = ceil(($interval / 2));
				if ($this->_debug) {
					$this->_debugging->start(
						"daytopost",
						'Reduced interval to: (' .
						number_format($interval) .
						') articles, article ' .
						($upperbound - $interval),
						5);
				}
			}
		}

		if ($this->_debug) {
			$dMessage =
				'Determined to be article: ' .
				number_format($upperbound) .
				' which is ' .
				$this->daysOld($dateofnextone) .
				' days old (' .
				date('r', $dateofnextone) .
				')';
			$this->_debugging->start("daytopost", $dMessage, 5);
		}

		return $upperbound;
	}

	/**
	 * Convert unix time to days ago.
	 *
	 * @param int $timestamp unix time
	 *
	 * @return float
	 */
	private function daysOld($timestamp)
	{
		return round((time() - (!is_numeric($timestamp) ? strtotime($timestamp) : $timestamp)) / 86400, 1);
	}

	/**
	 * @param string $group
	 * @param int $first
	 * @param int $last
	 * @param int $threads
	 *
	 * @return void
	 */
	public function getRange($group, $first, $last, $threads)
	{
		$binaries = new Binaries(['NNTP' => $this->_nntp, 'Echo' => $this->_echoCLI, 'Backfill' => $this, 'ColorCLI' => $this->_colorCLI, 'Settings' => $this->_pdo, 'Groups' => $this->_groups]);
		$groupArr = $this->_groups->getByName($group);

		if ($this->_echoCLI) {
			$this->_colorCLI->doEcho(
				$this->_colorCLI->set256('Yellow') .
				'Processing ' .
				str_replace('alt.binaries', 'a.b', $groupArr['name']) .
				($this->_nntpProxy === false
					? ($this->_compressedHeaders ? ' Using Compression' : ' Not Using Compression')
					: ' Using NNTPProxy ==> T-'
				) .
				' ==> T-' .
				$threads .
				' ==> ' .
				number_format($first) .
				' to ' .
				number_format($last) .
				$this->_colorCLI->rsetColor()
				, true
			);
		}

		// Select group, here, only once
		$data = $this->_nntp->selectGroup($groupArr['name']);
		if ($this->_nntp->isError($data)) {
			$data = $this->_nntp->dataError($this->_nntp, $groupArr['name']);
			if ($this->_nntp->isError($data)) {
				return;
			}
		}

		$binaries->scan($groupArr, $last, $first, $this->_safePartRepair);
	}

	/**
	 * Set the oldest/newest article number / date after backfill or binaries when using threaded scripts.
	 *
	 * @param string $group
	 * @param int    $articleNumber
	 * @param int    $type
	 *
	 * @return void
	 */
	public function getFinal($group, $articleNumber, $type)
	{
		$groupArr = $this->_groups->getByName($group);

		// Select group, here, only once
		$data = $this->_nntp->selectGroup($groupArr['name']);
		if ($this->_nntp->isError($data)) {
			$data = $this->_nntp->dataError($this->_nntp, $groupArr['name']);
			if ($this->_nntp->isError($data)) {
				return;
			}
		}

		if ($type === 'Backfill') {
			$this->_pdo->queryExec(
				sprintf(
					'UPDATE groups SET first_record_postdate = %s, first_record = %s, last_updated = NOW() WHERE id = %d',
					$this->_pdo->from_unixtime($this->postdate($articleNumber, $data)),
					$this->_pdo->escapeString($articleNumber),
					$groupArr['id']
				)
			);
		} else {
			$this->_pdo->queryExec(
				sprintf(
					'UPDATE groups SET last_record_postdate = %s, last_record = %s, last_updated = NOW() WHERE id = %d',
					$this->_pdo->from_unixtime($this->postdate($articleNumber, $data)),
					$this->_pdo->escapeString($articleNumber),
					$groupArr['id']
				)
			);
		}

		if ($this->_echoCLI) {
			$this->_colorCLI->doEcho(
				$this->_colorCLI->set256('Green') .
				$type .
				' Safe Threaded for ' .
				$group .
				" completed." .
				$this->_colorCLI->rsetColor()
				, true
			);
		}
	}
}
