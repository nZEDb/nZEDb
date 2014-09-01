<?php

class Backfill
{
	/**
	 * Instance of class Settings
	 *
	 * @var nzedb\db\DB
	 */
	public $pdo;

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
	 * Instance of class debugging.
	 *
	 * @var Logger
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
	public function __construct(array $options = [])
	{
		$defaults = [
			'Echo'      => true,
			'Logger' => null,
			'Groups'    => null,
			'NNTP'      => null,
			'Settings'  => null
		];
		$options += $defaults;

		$this->_echoCLI = ($options['Echo'] && nZEDb_ECHOCLI);

		$this->pdo = ($options['Settings'] instanceof \nzedb\db\Settings ? $options['Settings'] : new \nzedb\db\Settings());
		$this->_groups = ($options['Groups'] instanceof \Groups ? $options['Groups'] : new \Groups(['Settings' => $this->pdo]));
		$this->_nntp = ($options['NNTP'] instanceof \NNTP
			? $options['NNTP'] : new \NNTP(['Settings' => $this->pdo])
		);

		$this->_debug = (nZEDb_LOGGING || nZEDb_DEBUG);
		if ($this->_debug) {
			try {
				$this->_debugging = ($options['Logger'] instanceof \Logger ? $options['Logger'] : new \Logger(['ColorCLI' => $this->pdo->log]));
			} catch (\LoggerException $error) {
				$this->_debug = false;
			}
		}

		$this->_compressedHeaders = ($this->pdo->getSetting('compressedheaders') == 1 ? true : false);
		$this->_safeBackFillDate = ($this->pdo->getSetting('safebackfilldate') != '') ? (string)$this->pdo->getSetting('safebackfilldate') : '2008-08-14';
		$this->_safePartRepair = ($this->pdo->getSetting('safepartrepair') == 1 ? 'update' : 'backfill');
		$this->_tablePerGroup = ($this->pdo->getSetting('tablepergroup') == 1 ? true : false);
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
		$res = [];
		if ($groupName !== '') {
			$grp = $this->_groups->getByName($groupName);
			if ($grp) {
				$res = [$grp];
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
				$this->_debugging->log('Backfill', "backfillAllGroups", $dMessage, \Logger::LOG_INFO);
			}

			if ($this->_echoCLI) {
				$this->pdo->log->doEcho($this->pdo->log->header($dMessage), true);
			}

			$this->_binaries = new \Binaries(
				['NNTP' => $this->_nntp, 'Echo' => $this->_echoCLI, 'Settings' => $this->pdo, 'Groups' => $this->_groups]
			);

			if ($articles !== '' && !is_numeric($articles)) {
				$articles = 20000;
			}

			// Loop through groups.
			foreach ($res as $groupArr) {
				if ($groupName === '') {
					$dMessage = "Starting group " . $counter . ' of ' . $groupCount;
					if ($this->_debug) {
						$this->_debugging->log('Backfill', "backfillAllGroups", $dMessage, \Logger::LOG_INFO);
					}

					if ($this->_echoCLI) {
						$this->pdo->log->doEcho($this->pdo->log->header($dMessage), true);
					}
				}
				$this->backfillGroup($groupArr, $groupCount - $counter, $articles);
				$counter++;
			}

			$dMessage = 'Backfilling completed in ' . number_format(microtime(true) - $allTime, 2) . " seconds.";
			if ($this->_debug) {
				$this->_debugging->log('Backfill', "backfillAllGroups", $dMessage, \Logger::LOG_INFO);
			}

			if ($this->_echoCLI) {
				$this->pdo->log->doEcho($this->pdo->log->primary($dMessage));
			}
		} else {
			$dMessage = "No groups specified. Ensure groups are added to nZEDb's database for updating.";
			if ($this->_debug) {
				$this->_debugging->log('Backfill', "backfillAllGroups", $dMessage, \Logger::LOG_FATAL);
			}

			if ($this->_echoCLI) {
				$this->pdo->log->doEcho($this->pdo->log->warning($dMessage), true);
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
				$this->_debugging->log('Backfill', "backfillGroup", $dMessage, \Logger::LOG_ERROR);
			}

			if ($this->_echoCLI) {
				$this->pdo->log->doEcho($this->pdo->log->error($dMessage));
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
			$this->pdo->log->doEcho($this->pdo->log->primary('Processing ' . $groupName), true);
		}

		// Check if this is days or post backfill.
		$postCheck = ($articles === '' ? false : true);

		// Get target post based on date or user specified number.
		$targetpost = (string)($postCheck
			?
				round($groupArr['first_record'] - $articles)
			:
			$this->_binaries->daytopost($groupArr['backfill_target'], $data)
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
				$this->_debugging->log('Backfill', "backfillGroup", $dMessage, \Logger::LOG_NOTICE);
			}

			if ($this->_echoCLI) {
				$this->pdo->log->doEcho($this->pdo->log->notice($dMessage), true);
			}
			return;
		}

		if ($this->_echoCLI) {
			$this->pdo->log->doEcho(
				$this->pdo->log->primary(
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
				$this->pdo->log->doEcho(
					$this->pdo->log->set256('Yellow') .
					"\nGetting " .
					(number_format($last - $first + 1)) .
					" articles from " .
					$groupName .
					", " .
					$left .
					" group(s) left. (" .
					(number_format($first - $targetpost)) .
					" articles in queue)." .
					$this->pdo->log->rsetColor(), true
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
				$newdate = $this->_binaries->postdate($first, $data);
			}

			$this->pdo->queryExec(
				sprintf('
					UPDATE groups
					SET first_record_postdate = %s, first_record = %s, last_updated = NOW()
					WHERE id = %d',
					$this->pdo->from_unixtime($newdate),
					$this->pdo->escapeString($first),
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
			$this->pdo->log->doEcho(
				$this->pdo->log->primary(
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
		$groupname = $this->pdo->queryOneRow(
			sprintf('
				SELECT name FROM groups
				WHERE first_record_postdate BETWEEN %s AND NOW()
				AND backfill = 1
				ORDER BY name ASC',
				$this->pdo->escapeString($this->_safeBackFillDate)
			)
		);

		if (!$groupname) {
			$dMessage =
				'No groups to backfill, they are all at the target date ' .
				$this->_safeBackFillDate .
				", or you have not enabled them to be backfilled in the groups page.\n";
			if ($this->_debug) {
				$this->_debugging->log('Backfill', "safeBackfill", $dMessage, \Logger::LOG_FATAL);
			}
			exit($dMessage);
		} else {
			$this->backfillAllGroups($groupname['name'], $articles);
		}
	}

}
