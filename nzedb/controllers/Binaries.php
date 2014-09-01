<?php

/**
 * Class Binaries
 */
class Binaries
{
	const OPTYPE_BLACKLIST          = 1;
	const OPTYPE_WHITELIST          = 2;

	const BLACKLIST_DISABLED        = 0;
	const BLACKLIST_ENABLED         = 1;

	const BLACKLIST_FIELD_SUBJECT   = 1;
	const BLACKLIST_FIELD_FROM      = 2;
	const BLACKLIST_FIELD_MESSAGEID = 3;

	/**
	 * The cache of the blacklist.
	 *
	 * @var array
	 */
	public $blackList = [];

	/**
	 * How many headers do we download per loop?
	 *
	 * @var int
	 */
	public $messageBuffer;

	/**
	 * @var ColorCLI
	 */
	protected $_colorCLI;

	/**
	 * @var CollectionsCleaning
	 */
	protected $_collectionsCleaning;

	/**
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
	 * Is the blacklist already cached?
	 *
	 * @var bool
	 */
	protected $_blackListLoaded = false;

	/**
	 * Should we use header compression?
	 *
	 * @var bool
	 */
	protected $_compressedHeaders;

	/**
	 * Should we use part repair?
	 *
	 * @var bool
	 */
	protected $_partRepair;

	/**
	 * @var nzedb\db\Settings
	 */
	protected $_pdo;

	/**
	 * How many days to go back on a new group?
	 *
	 * @var bool
	 */
	protected $_newGroupScanByDays;

	/**
	 * How many headers to download on new groups?
	 *
	 * @var int
	 */
	protected $_newGroupMessagesToScan;

	/**
	 * How many days to go back on new groups?
	 *
	 * @var int
	 */
	protected $_newGroupDaysToScan;

	/**
	 * How many headers to download per run of part repair?
	 *
	 * @var int
	 */
	protected $_partRepairLimit;

	/**
	 * Should we show dropped yEnc to CLI?
	 *
	 * @var bool
	 */
	protected $_showDroppedYEncParts;

	/**
	 * Should we use table per group?
	 *
	 * @var bool
	 */
	protected $_tablePerGroup;

	/**
	 * Echo to cli?
	 *
	 * @var bool
	 */
	protected $_echoCLI;

	/**
	 * @var bool
	 */
	protected $_debug = false;

	/**
	 * Does the user have any blacklists enabled?
	 * @var bool
	 */
	protected $_blackListEmpty = false;

	/**
	 * Max tries to download headers.
	 * @var int
	 */
	protected $_partRepairMaxTries;

	/**
	 * Constructor.
	 *
	 * @param array $options Class instances / echo to CLI?
	 */
	public function __construct(array $options = [])
	{
		$defaults = [
			'Echo'                => true,
			'CollectionsCleaning' => null,
			'ColorCLI'            => null,
			'Logger'           => null,
			'Groups'              => null,
			'NNTP'                => null,
			'Settings'            => null,
		];
		$options += $defaults;

		$this->_echoCLI = ($options['Echo'] && nZEDb_ECHOCLI);

		$this->_pdo = ($options['Settings'] instanceof \nzedb\db\Settings ? $options['Settings'] : new \nzedb\db\Settings());
		$this->_groups = ($options['Groups'] instanceof \Groups ? $options['Groups'] : new \Groups(['Settings' => $this->_pdo]));
		$this->_colorCLI = ($options['ColorCLI'] instanceof \ColorCLI ? $options['ColorCLI'] : new \ColorCLI());
		$this->_nntp = ($options['NNTP'] instanceof \NNTP ? $options['NNTP'] : new \NNTP(['Echo' => $this->_colorCLI, 'Settings' => $this->_pdo, 'ColorCLI' => $this->_colorCLI]));
		$this->_collectionsCleaning = ($options['CollectionsCleaning'] instanceof \CollectionsCleaning ? $options['CollectionsCleaning'] : new \CollectionsCleaning());

		$this->_debug = (nZEDb_DEBUG || nZEDb_LOGGING);

		if ($this->_debug) {
			try {
				$this->_debugging = ($options['Logger'] instanceof \Logger ? $options['Logger'] : new \Logger(['ColorCLI' => $this->_colorCLI]));
			} catch (\LoggerException $error) {
				$this->_debug = false;
			}
		}

		$this->messageBuffer = ($this->_pdo->getSetting('maxmssgs') != '') ? $this->_pdo->getSetting('maxmssgs') : 20000;
		$this->_compressedHeaders = ($this->_pdo->getSetting('compressedheaders') == 1 ? true : false);
		$this->_partRepair = ($this->_pdo->getSetting('partrepair') == 0 ? false : true);
		$this->_newGroupScanByDays = ($this->_pdo->getSetting('newgroupscanmethod') == 1 ? true : false);
		$this->_newGroupMessagesToScan = ($this->_pdo->getSetting('newgroupmsgstoscan') != '') ? $this->_pdo->getSetting('newgroupmsgstoscan') : 50000;
		$this->_newGroupDaysToScan = ($this->_pdo->getSetting('newgroupdaystoscan') != '') ? (int)$this->_pdo->getSetting('newgroupdaystoscan') : 3;
		$this->_partRepairLimit = ($this->_pdo->getSetting('maxpartrepair') != '') ? (int)$this->_pdo->getSetting('maxpartrepair') : 15000;
		$this->_partRepairMaxTries = ($this->_pdo->getSetting('partrepairmaxtries') != '' ? (int)$this->_pdo->getSetting('partrepairmaxtries') : 3);
		$this->_showDroppedYEncParts = ($this->_pdo->getSetting('showdroppedyencparts') == 1 ? true : false);
		$this->_tablePerGroup = ($this->_pdo->getSetting('tablepergroup') == 1 ? true : false);

		$this->blackList = [];
		$this->_blackListLoaded = false;
	}

	/**
	 * Download new headers for all active groups.
	 *
	 * @param int $maxHeaders (Optional) How many headers to download max.
	 *
	 * @return void
	 */
	public function updateAllGroups($maxHeaders = 0)
	{
		$groups = $this->_groups->getActive();

		$groupCount = count($groups);
		if ($groupCount > 0) {
			$counter = 1;
			$allTime = microtime(true);

			$this->log(
				'Updating: ' . $groupCount . ' group(s) - Using compression? ' . ($this->_compressedHeaders ? 'Yes' : 'No'),
				'updateAllGroups',
				\Logger::LOG_INFO,
				'header'
			);

			// Loop through groups.
			foreach ($groups as $group) {
				$this->log(
					'Starting group ' . $counter . ' of ' . $groupCount,
					'updateAllGroups',
					\Logger::LOG_INFO,
					'header'
				);
				$this->updateGroup($group, $maxHeaders);
				$counter++;
			}

			$this->log(
				'Updating completed in ' . number_format(microtime(true) - $allTime, 2) . ' seconds.',
				'updateAllGroups',
				\Logger::LOG_INFO,
				'primary'
			);
		} else {
			$this->log(
				'No groups specified. Ensure groups are added to nZEDb\'s database for updating.',
				'updateAllGroups',
				\Logger::LOG_NOTICE,
				'warning'
			);
		}
	}

	/**
	 * Download new headers for a single group.
	 *
	 * @param array $groupMySQL Array of MySQL results for a single group.
	 * @param int   $maxHeaders (Optional) How many headers to download max.
	 *
	 * @return void
	 */
	public function updateGroup($groupMySQL, $maxHeaders = 0)
	{
		$startGroup = microtime(true);

		// Select the group on the NNTP server, gets the latest info on it.
		$groupNNTP = $this->_nntp->selectGroup($groupMySQL['name']);
		if ($this->_nntp->isError($groupNNTP)) {
			$groupNNTP = $this->_nntp->dataError($this->_nntp, $groupMySQL['name']);
			if ($this->_nntp->isError($groupNNTP)) {
				return;
			}
		}

		if ($this->_echoCLI) {
			$this->_colorCLI->doEcho($this->_colorCLI->primary('Processing ' . $groupMySQL['name']), true);
		}

		// Attempt to repair any missing parts before grabbing new ones.
		if ($groupMySQL['last_record'] != 0) {
			if ($this->_partRepair) {
				if ($this->_echoCLI) {
					$this->_colorCLI->doEcho($this->_colorCLI->primary('Part repair enabled. Checking for missing parts.'), true);
				}
				$this->partRepair($groupMySQL);
			} else if ($this->_echoCLI) {
				$this->_colorCLI->doEcho($this->_colorCLI->primary('Part repair disabled by user.'), true);
			}
		}

		// Generate postdate for first record, for those that upgraded.
		if (is_null($groupMySQL['first_record_postdate']) && $groupMySQL['first_record'] != 0) {

			$groupMySQL['first_record_postdate'] = $this->postdate($groupMySQL['first_record'], $groupNNTP);

			$this->_pdo->queryExec(
				sprintf('
					UPDATE groups
					SET first_record_postdate = %s
					WHERE id = %d',
					$this->_pdo->from_unixtime($groupMySQL['first_record_postdate']),
					$groupMySQL['id']
				)
			);
		}

		// Get first article we want aka the oldest.
		if ($groupMySQL['last_record'] == 0) {
			if ($this->_newGroupScanByDays) {
				// For new newsgroups - determine here how far we want to go back using date.
				$first = $this->daytopost($this->_newGroupDaysToScan, $groupNNTP);
			} else if ($groupNNTP['first'] >= ($groupNNTP['last'] - ($this->_newGroupMessagesToScan + $this->messageBuffer))) {
				// If what we want is lower than the groups first article, set the wanted first to the first.
				$first = $groupNNTP['first'];
			} else {
				// Or else, use the newest article minus how much we should get for new groups.
				$first = (string)($groupNNTP['last'] - ($this->_newGroupMessagesToScan + $this->messageBuffer));
			}

			// We will use this to subtract so we leave articles for the next time (in case the server doesn't have them yet)
			$leaveOver = $this->messageBuffer;

		// If this is not a new group, go from our newest to the servers newest.
		} else {
			// Set our oldest wanted to our newest local article.
			$first = $groupMySQL['last_record'];

			// This is how many articles we will grab. (the servers newest minus our newest).
			$totalCount = (string)($groupNNTP['last'] - $first);

			// Check if the server has more articles than our loop limit x 2.
			if ($totalCount > ($this->messageBuffer * 2)) {
				// Get the remainder of $totalCount / $this->message buffer
				$leaveOver = round(($totalCount % $this->messageBuffer), 0, PHP_ROUND_HALF_DOWN) + $this->messageBuffer;
			} else {
				// Else get half of the available.
				$leaveOver = round(($totalCount / 2), 0, PHP_ROUND_HALF_DOWN);
			}
		}

		// The last article we want, aka the newest.
		$last = $groupLast = (string)($groupNNTP['last'] - $leaveOver);

		// If the newest we want is older than the oldest we want somehow.. set them equal.
		if ($last < $first) {
			$last = $groupLast = $first;
		}

		// This is how many articles we are going to get.
		$total = (string)($groupLast - $first);
		// This is how many articles are available (without $leaveOver).
		$realTotal = (string)($groupNNTP['last'] - $first);

		// Check if we should limit the amount of fetched new headers.
		if ($maxHeaders > 0) {
			if ($maxHeaders < ($groupLast - $first)) {
				$groupLast = $last = (string)($first + $maxHeaders);
			}
			$total = (string)($groupLast - $first);
		}

		// If total is bigger than 0 it means we have new parts in the newsgroup.
		if ($total > 0) {

			if ($this->_echoCLI) {
				$this->_colorCLI->doEcho(
					$this->_colorCLI->primary(
						($groupMySQL['last_record'] == 0
							? 'New group ' . $groupNNTP['group'] . ' starting with ' .
								($this->_newGroupScanByDays
									? $this->_newGroupDaysToScan . ' days'
									: number_format($this->_newGroupMessagesToScan) . ' messages'
								) . ' worth.'
							: 'Group ' . $groupNNTP['group'] . ' has ' . number_format($realTotal) . ' new articles.'
						) .
						' Leaving ' . number_format($leaveOver) .
						" for next pass.\nServer oldest: " . number_format($groupNNTP['first']) .
						' Server newest: ' . number_format($groupNNTP['last']) .
						' Local newest: ' . number_format($groupMySQL['last_record'])
					), true
				);
			}

			$done = false;
			// Get all the parts (in portions of $this->messageBuffer to not use too much memory).
			while ($done === false) {

				// Increment last until we reach $groupLast (group newest article).
				if ($total > $this->messageBuffer) {
					if ((string)($first + $this->messageBuffer) > $groupLast) {
						$last = $groupLast;
					} else {
						$last = (string)($first + $this->messageBuffer);
					}
				}
				// Increment first so we don't get an article we already had.
				$first++;

				if ($this->_echoCLI) {
					$this->_colorCLI->doEcho(
						$this->_colorCLI->header(
							"\nGetting " . number_format($last - $first + 1) . ' articles (' . number_format($first) .
							' to ' . number_format($last) . ') from ' . $groupMySQL['name'] . " - (" .
							number_format($groupLast - $last) . " articles in queue)."
						)
					);
				}

				// Get article headers from newsgroup.
				$scanSummary = $this->scan($groupMySQL, $first, $last);

				// Check if we fetched headers.
				if (!empty($scanSummary)) {

					// If new group, update first record & postdate
					if (is_null($groupMySQL['first_record_postdate']) && $groupMySQL['first_record'] == 0) {
						$groupMySQL['first_record'] = $scanSummary['firstArticleNumber'];

						if (isset($scanSummary['firstArticleDate'])) {
							$groupMySQL['first_record_postdate'] = strtotime($scanSummary['firstArticleDate']);
						} else {
							$groupMySQL['first_record_postdate'] = $this->postdate($groupMySQL['first_record'], $groupNNTP);
						}

						$this->_pdo->queryExec(
							sprintf('
								UPDATE groups
								SET first_record = %s, first_record_postdate = %s
								WHERE id = %d',
								$scanSummary['firstArticleNumber'],
								$this->_pdo->from_unixtime($this->_pdo->escapeString($groupMySQL['first_record_postdate'])),
								$groupMySQL['id']
							)
						);
					}

					if (isset($scanSummary['lastArticleDate'])) {
						$scanSummary['lastArticleDate'] = strtotime($scanSummary['lastArticleDate']);
					} else {
						$scanSummary['lastArticleDate'] = $this->postdate($scanSummary['lastArticleNumber'], $groupNNTP);
					}

					$this->_pdo->queryExec(
						sprintf('
							UPDATE groups
							SET last_record = %s, last_record_postdate = %s, last_updated = NOW()
							WHERE id = %d',
							$this->_pdo->escapeString($scanSummary['lastArticleNumber']),
							$this->_pdo->from_unixtime($scanSummary['lastArticleDate']),
							$groupMySQL['id']
						)
					);
				} else {
					// If we didn't fetch headers, update the record still.
					$this->_pdo->queryExec(
						sprintf('
							UPDATE groups
							SET last_record = %s, last_updated = NOW()
							WHERE id = %d',
							$this->_pdo->escapeString($last),
							$groupMySQL['id']
						)
					);
				}

				if ($last == $groupLast) {
					$done = true;
				} else {
					$first = $last;
				}
			}

			if ($this->_echoCLI) {
				$this->_colorCLI->doEcho(
					$this->_colorCLI->primary(
						PHP_EOL . 'Group ' . $groupMySQL['name'] . ' processed in ' .
						number_format(microtime(true) - $startGroup, 2) . ' seconds.'
					), true
				);
			}
		} else if ($this->_echoCLI) {
			$this->_colorCLI->doEcho(
				$this->_colorCLI->primary(
					'No new articles for ' . $groupMySQL['name'] . ' (first ' . number_format($first) .
					', last ' . number_format($last) . ', grouplast ' . number_format($groupMySQL['last_record']) .
					', total ' . number_format($total) . ")\n" . 'Server oldest: ' . number_format($groupNNTP['first']) .
					' Server newest: ' . number_format($groupNNTP['last']) . ' Local newest: ' . number_format($groupMySQL['last_record'])
				), true
			);
		}
	}

	/**
	 * Loop over range of wanted headers, insert headers into DB.
	 *
	 * @param array      $groupMySQL   The group info from mysql.
	 * @param int        $first        The oldest wanted header.
	 * @param int        $last         The newest wanted header.
	 * @param string     $type         Is this partrepair or update or backfill?
	 * @param null|array $missingParts If we are running in partrepair, the list of missing article numbers.
	 *
	 * @return array Empty on failure.
	 */
	public function scan($groupMySQL, $first, $last, $type = 'update', $missingParts = null)
	{
		// Start time of scan method and of fetching headers.
		$startLoop = microtime(true);

		// Check if MySQL tables exist, create if they do not, get their names at the same time.
		$tableNames = $this->_groups->getCBPTableNames($this->_tablePerGroup, $groupMySQL['id']);

		$returnArray = [];

		$partRepair = ($type === 'partrepair');
		$addToPartRepair = ($type === 'update' && $this->_partRepair);

		// Download the headers.
		if ($partRepair === true) {
			// This is slower but possibly is better with missing headers.
			$headers = $this->_nntp->getOverview($first . '-' . $last, true, false);
		} else {
			$headers = $this->_nntp->getXOVER($first . '-' . $last);
		}

		// If there was an error, try to reconnect.
		if ($this->_nntp->isError($headers)) {

			// Increment if part repair and return false.
			if ($partRepair === true) {
				$this->_pdo->queryExec(
					sprintf(
						'UPDATE partrepair SET attempts = attempts + 1 WHERE group_id = %d AND numberid %s',
						$groupMySQL['id'],
						($first == $last ? '= ' . $first : 'IN (' . implode(',', range($first, $last)) . ')')
					)
				);
				return $returnArray;
			}

			// This is usually a compression error, so try disabling compression.
			$this->_nntp->doQuit();
			if ($this->_nntp->doConnect(false) !== true) {
				return $returnArray;
			}

			// Re-select group, download headers again without compression and re-enable compression.
			$this->_nntp->selectGroup($groupMySQL['name']);
			$headers = $this->_nntp->getXOVER($first . '-' . $last);
			$this->_nntp->enableCompression();

			// Check if the non-compression headers have an error.
			if ($this->_nntp->isError($headers)) {
				$this->log(
					"Code {$headers->code}: {$headers->message}\nSkipping group: ${$groupMySQL['name']}",
					'scan',
					\Logger::LOG_WARNING,
					'error'
				);
				return $returnArray;
			}
		}

		// Start of processing headers.
		$startCleaning = microtime(true);

		// End of the getting data from usenet.
		$timeHeaders = number_format($startCleaning - $startLoop, 2);

		// Check if we got headers.
		$msgCount = count($headers);

		if ($msgCount < 1) {
			return $returnArray;
		}

		// Get highest and lowest article numbers/dates.
		$iterator1 = 0;
		$iterator2 = $msgCount - 1;
		while (true) {
			if (!isset($returnArray['firstArticleNumber']) && isset($headers[$iterator1]['Number'])) {
				$returnArray['firstArticleNumber'] = $headers[$iterator1]['Number'];
				$returnArray['firstArticleDate'] = $headers[$iterator1]['Date'];
			}

			if (!isset($returnArray['lastArticleNumber']) && isset($headers[$iterator2]['Number'])) {
				$returnArray['lastArticleNumber'] = $headers[$iterator2]['Number'];
				$returnArray['lastArticleDate'] = $headers[$iterator2]['Date'];
			}

			// Break if we found non empty articles.
			if (isset($returnArray['firstArticleNumber']) && isset($returnArray['lastArticleNumber'])) {
				break;
			}

			// Break out if we couldn't find anything.
			if ($iterator1++ >= $msgCount - 1 || $iterator2-- <= 0) {
				break;
			}
		}

		$headersRepaired = $articles = $rangeNotReceived = $collectionIDs = $binariesUpdate = $headersReceived = $headersNotInserted = [];
		$notYEnc = $headersBlackListed = 0;

		$partsQuery = $partsCheck = sprintf('INSERT INTO %s (binaryid, number, messageid, partnumber, size, collection_id) VALUES ', $tableNames['pname']);

		$this->_pdo->beginTransaction();
		// Loop articles, figure out files/parts.
		foreach ($headers as $header) {

			// Check if we got the article or not.
			if (isset($header['Number'])) {
				$headersReceived[] = $header['Number'];
			} else {
				if ($addToPartRepair) {
					$rangeNotReceived[] = $header['Number'];
				}
				continue;
			}

			// If set we are running in partRepair mode.
			if ($partRepair === true && !is_null($missingParts)) {
				if (!in_array($header['Number'], $missingParts)) {
					// If article isn't one that is missing skip it.
					continue;
				} else {
					// We got the part this time. Remove article from part repair.
					$headersRepaired[] = $header['Number'];
				}
			}

			/*
			 * Find part / total parts. Ignore if no part count found.
			 *
			 * \s* Trims the leading space.
			 * (?!"Usenet Index Post) ignores these types of articles, they are useless.
			 * (.+) Fetches the subject.
			 * \s+ Trims trailing space after the subject.
			 * \((\d+)\/(\d+)\) Gets the part count.
			 * No ending ($) as there are cases of subjects with extra data after the part count.
			 */
			if (preg_match('/^\s*(?!"Usenet Index Post)(.+)\s+\((\d+)\/(\d+)\)/', $header['Subject'], $matches)) {
				// Add yEnc to subjects that do not have them, but have the part number at the end of the header.
				if (!stristr($header['Subject'], 'yEnc')) {
					$matches[1] .= ' yEnc';
				}
			} else {
				if ($this->_showDroppedYEncParts === true && strpos($header['Subject'], '"Usenet Index Post') !== 0) {
					file_put_contents(
						nZEDb_LOGS . 'not_yenc' . $groupMySQL['name'] . '.dropped.log',
						$header['Subject'] . PHP_EOL, FILE_APPEND
					);
				}
				$notYEnc++;
				continue;
			}

			// Filter subject based on black/white list.
			if ($this->_blackListEmpty === false && $this->isBlackListed($header, $groupMySQL['name'])) {
				$headersBlackListed++;
				continue;
			}

			if (!isset($header['Bytes'])) {
				$header['Bytes'] = (isset($header[':bytes']) ? $header[':bytes'] : 0);
			}
			$header['Bytes'] = (int) $header['Bytes'];

			// Set up the info for inserting into parts/binaries/collections tables.
			if (!isset($articles[$matches[1]])) {

				// Attempt to find the file count. If it is not found, set it to 0.
				if (!preg_match('/[[(\s](\d{1,5})(\/|[\s_]of[\s_]|-)(\d{1,5})[])\s$:]/i', $matches[1], $fileCount)) {
					$fileCount[1] = $fileCount[3] = 0;

					if ($this->_showDroppedYEncParts === true) {
						file_put_contents(
							nZEDb_LOGS . 'no_files' . $groupMySQL['name'] . '.log',
							$header['Subject'] . PHP_EOL, FILE_APPEND
						);
					}
				}

				// Used to group articles together when forming the release/nzb.
				$header['CollectionKey'] = (
					$this->_collectionsCleaning->collectionsCleaner($matches[1], $groupMySQL['name']) .
					$header['From'] .
					$groupMySQL['id'] .
					$fileCount[3]
				);

				if (!isset($collectionIDs[$header['CollectionKey']])) {

					/* Date from header should be a string this format:
					 * 31 Mar 2014 15:36:04 GMT or 6 Oct 1998 04:38:40 -0500
					 * Still make sure it's not unix time, convert it to unix time if it is.
					 */
					$header['Date'] = (is_numeric($header['Date']) ? $header['Date'] : strtotime($header['Date']));

					// Get the current unixtime from PHP.
					$now = time();

					$collectionID = $this->_pdo->queryInsert(
						sprintf("
							INSERT INTO %s (subject, fromname, date, xref, group_id,
								totalfiles, collectionhash, dateadded)
							VALUES (%s, %s, FROM_UNIXTIME(%s), %s, %d, %d, '%s', NOW())
							ON DUPLICATE KEY UPDATE dateadded = NOW()",
							$tableNames['cname'],
							$this->_pdo->escapeString(substr(utf8_encode($matches[1]), 0, 255)),
							$this->_pdo->escapeString(utf8_encode($header['From'])),
							(is_numeric($header['Date']) ? ($header['Date'] > $now ? $now : $header['Date']) : $now),
							$this->_pdo->escapeString(substr($header['Xref'], 0, 255)),
							$groupMySQL['id'],
							$fileCount[3],
							sha1($header['CollectionKey'])
						)
					);

					if ($collectionID === false) {
						if ($addToPartRepair) {
							$headersNotInserted[] = $header['Number'];
						}
						$this->_pdo->Rollback();
						$this->_pdo->beginTransaction();
						continue;
					}
					$collectionIDs[$header['CollectionKey']] = $collectionID;
				} else {
					$collectionID = $collectionIDs[$header['CollectionKey']];
				}

				$binaryID = $this->_pdo->queryInsert(
					sprintf("
						INSERT INTO %s (binaryhash, name, collectionid, totalparts, currentparts, filenumber, partsize)
						VALUES ('%s', %s, %d, %d, 1, %d, %d)
						ON DUPLICATE KEY UPDATE currentparts = currentparts + 1, partsize = partsize + %d",
						$tableNames['bname'],
						md5($matches[1] . $header['From'] . $groupMySQL['id']),
						$this->_pdo->escapeString(utf8_encode($matches[1])),
						$collectionID,
						$matches[3],
						$fileCount[1],
						$header['Bytes'],
						$header['Bytes']
					)
				);

				if ($binaryID === false) {
					if ($addToPartRepair) {
						$headersNotInserted[] = $header['Number'];
					}
					$this->_pdo->Rollback();
					$this->_pdo->beginTransaction();
					continue;
				}

				$binariesUpdate[$binaryID]['Size'] = 0;
				$binariesUpdate[$binaryID]['Parts'] = 0;

				$articles[$matches[1]]['CollectionID'] = $collectionID;
				$articles[$matches[1]]['BinaryID'] = $binaryID;

			} else {
				$binaryID = $articles[$matches[1]]['BinaryID'];
				$collectionID = $articles[$matches[1]]['CollectionID'];
				$binariesUpdate[$binaryID]['Size'] += $header['Bytes'];
				$binariesUpdate[$binaryID]['Parts']++;
			}

			// Strip the < and >, saves space in DB.
			$header['Message-ID'][0] = "'";

			$partsQuery .=
				'(' . $binaryID . ',' . $header['Number'] . ',' . rtrim($header['Message-ID'], '>') . "'," .
				$matches[2] . ',' . $header['Bytes'] . ',' . $collectionID . '),';

		}
		unset($headers); // Reclaim memory.

		// Start of inserting into SQL.
		$startUpdate = microtime(true);

		// End of processing headers.
		$timeCleaning = number_format($startUpdate - $startCleaning, 2);

		$binariesQuery = $binariesCheck = sprintf('INSERT INTO %s (id, partsize, currentparts) VALUES ', $tableNames['bname']);
		foreach ($binariesUpdate as $binaryID => $binary) {
			$binariesQuery .= '(' . $binaryID . ',' . $binary['Size'] . ',' . $binary['Parts'] . '),';
		}
		$binariesEnd = ' ON DUPLICATE KEY UPDATE partsize = VALUES(partsize) + partsize, currentparts = VALUES(currentparts) + currentparts';
		$binariesQuery = rtrim($binariesQuery, ',') . $binariesEnd;

		// Check if we got any binaries. If we did, try to insert them.
		if (((strlen($binariesCheck . $binariesEnd) === strlen($binariesQuery)) ? true : $this->_pdo->queryExec($binariesQuery))) {
			if ($this->_debug) {
				$this->_colorCLI->doEcho(
					$this->_colorCLI->debug(
						'Sending ' . round(strlen($partsQuery) / 1024, 2) . ' KB of parts to MySQL'
					)
				);
			}

			if (((strlen($partsQuery) === strlen($partsCheck)) ? true  : $this->_pdo->queryExec(rtrim($partsQuery, ',')))) {
				$this->_pdo->Commit();
			} else {
				if ($addToPartRepair) {
					$headersNotInserted += $headersReceived;
				}
				$this->_pdo->Rollback();
			}
		} else {
			if ($addToPartRepair) {
				$headersNotInserted += $headersReceived;
			}
			$this->_pdo->Rollback();
		}

		if ($this->_echoCLI && $partRepair === false) {
			$this->_colorCLI->doEcho(
				$this->_colorCLI->primary(
					'Received ' . count($headersReceived) .
					' articles of ' . (number_format($last - $first + 1)) . ' requested, ' .
					$headersBlackListed . ' blacklisted, ' . $notYEnc . ' not yEnc.'
				)
			);
		}

		// Start of part repair.
		$startPR = microtime(true);

		// End of inserting.
		$timeInsert = number_format($startPR - $startUpdate, 2);

		if ($partRepair && count($headersRepaired) > 0) {
			$this->removeRepairedParts($headersRepaired, $tableNames['prname'], $groupMySQL['id']);
		}

		if ($addToPartRepair) {

			$notInsertedCount = count($headersNotInserted);
			if ($notInsertedCount > 0) {
				$this->addMissingParts($headersNotInserted, $tableNames['prname'], $groupMySQL['id']);

				$this->log(
					$notInsertedCount . ' articles failed to insert!',
					'scan',
					\Logger::LOG_WARNING,
					'warning'
				);
			}

			// Check if we have any missing headers.
			if (($last - $first - $notYEnc - $headersBlackListed + 1) > count($headersReceived)) {
				$rangeNotReceived = array_merge($rangeNotReceived, array_diff(range($first, $last), $headersReceived));
			}
			$notReceivedCount = count($rangeNotReceived);

			if ($notReceivedCount > 0) {
				$this->addMissingParts($rangeNotReceived, $tableNames['prname'], $groupMySQL['id']);

				if ($this->_echoCLI) {
					$this->_colorCLI->doEcho(
						$this->_colorCLI->alternate(
							'Server did not return ' . $notReceivedCount .
							' articles from ' . $groupMySQL['name'] . '.'
						), true
					);
				}
			}
		}

		$currentMicroTime = microtime(true);
		if ($this->_echoCLI) {
			$this->_colorCLI->doEcho(
				$this->_colorCLI->alternateOver($timeHeaders . 's') .
				$this->_colorCLI->primaryOver(' to download articles, ') .
				$this->_colorCLI->alternateOver($timeCleaning . 's') .
				$this->_colorCLI->primaryOver(' to process collections, ') .
				$this->_colorCLI->alternateOver($timeInsert . 's') .
				$this->_colorCLI->primaryOver(' to insert binaries/parts, ') .
				$this->_colorCLI->alternateOver(number_format($currentMicroTime - $startPR, 2) . 's') .
				$this->_colorCLI->primaryOver(' for part repair, ') .
				$this->_colorCLI->alternateOver(number_format($currentMicroTime - $startLoop, 2) . 's') .
				$this->_colorCLI->primary(' total.')
			);
		}
		return $returnArray;
	}

	/**
	 * If we failed to insert Collections/Binaries/Parts, rollback the transaction and add the parts to part repair.
	 *
	 * @param array $headers Array of headers containing sub-arrays with parts.
	 *
	 * @return array Array of article numbers to add to part repair.
	 *
	 * @access protected
	 */
	protected function _rollbackAddToPartRepair(array $headers)
	{
		$headersNotInserted = [];
		foreach ($headers as $header) {
			foreach ($header as $file) {
				$headersNotInserted[] = $file['Parts']['number'];
			}
		}
		$this->_pdo->Rollback();
		return $headersNotInserted;
	}

	/**
	 * Attempt to get missing article headers.
	 *
	 * @param array $groupArr The info for this group from mysql.
	 *
	 * @return void
	 */
	public function partRepair($groupArr)
	{
		$tableNames = $this->_groups->getCBPTableNames($this->_tablePerGroup, $groupArr['id']);
		// Get all parts in partrepair table.
		$missingParts = $this->_pdo->query(
			sprintf('
				SELECT * FROM %s
				WHERE group_id = %d AND attempts < %d
				ORDER BY numberid ASC LIMIT %d',
				$tableNames['prname'],
				$groupArr['id'],
				$this->_partRepairMaxTries,
				$this->_partRepairLimit
			)
		);

		$missingCount = count($missingParts);
		if ($missingCount > 0) {
			if ($this->_echoCLI) {
				$this->_colorCLI->doEcho(
					$this->_colorCLI->primary(
						'Attempting to repair ' .
						number_format($missingCount) .
						' parts.'
					), true
				);
			}

			// Loop through each part to group into continuous ranges with a maximum range of messagebuffer/4.
			$ranges = $partList = [];
			$firstPart = $lastNum = $missingParts[0]['numberid'];

			foreach ($missingParts as $part) {
				if (($part['numberid'] - $firstPart) > ($this->messageBuffer / 4)) {

					$ranges[] = [
						'partfrom' => $firstPart,
						'partto'   => $lastNum,
						'partlist' => $partList
					];

					$firstPart = $part['numberid'];
					$partList = [];
				}
				$partList[] = $part['numberid'];
				$lastNum = $part['numberid'];
			}

			$ranges[] = [
				'partfrom' => $firstPart,
				'partto'   => $lastNum,
				'partlist' => $partList
			];

			// Download missing parts in ranges.
			foreach ($ranges as $range) {

				$partFrom = $range['partfrom'];
				$partTo   = $range['partto'];
				$partList = $range['partlist'];

				if ($this->_echoCLI) {
					echo chr(rand(45,46)) . "\r";
				}

				// Get article headers from newsgroup.
				$this->scan($groupArr, $partFrom, $partTo, 'partrepair', $partList);
			}

			// Calculate parts repaired
			$result = $this->_pdo->queryOneRow(
				sprintf('
					SELECT COUNT(id) AS num
					FROM %s
					WHERE group_id = %d
					AND numberid <= %d',
					$tableNames['prname'],
					$groupArr['id'],
					$missingParts[$missingCount - 1]['numberid']
				)
			);

			$partsRepaired = 0;
			if ($result !== false) {
				$partsRepaired = ($missingCount - $result['num']);
			}

			// Update attempts on remaining parts for active group
			if (isset($missingParts[$missingCount - 1]['id'])) {
				$this->_pdo->queryExec(
					sprintf('
						UPDATE %s
						SET attempts = attempts + 1
						WHERE group_id = %d
						AND numberid <= %d',
						$tableNames['prname'],
						$groupArr['id'],
						$missingParts[$missingCount - 1]['numberid']
					)
				);
			}

			if ($this->_echoCLI) {
				$this->_colorCLI->doEcho(
					$this->_colorCLI->primary(
						PHP_EOL .
						number_format($partsRepaired) .
						' parts repaired.'
					), true
				);
			}
		}

		// Remove articles that we cant fetch after x attempts.
		$this->_pdo->queryExec(
			sprintf(
				'DELETE FROM %s WHERE attempts >= %d AND group_id = %d',
				$tableNames['prname'],
				$this->_partRepairMaxTries,
				$groupArr['id']
			)
		);
	}

	/**
	 * Returns unix time for an article number.
	 *
	 * @param int    $post      The article number to get the time from.
	 * @param array  $groupData Usenet group info from NNTP selectGroup method.
	 *
	 * @return bool|int
	 */
	public function postdate($post, array $groupData)
	{
		// Set table names
		$groupID = $this->_groups->getIDByName($groupData['group']);
		$group = [];
		if ($groupID !== '') {
			$group = $this->_groups->getCBPTableNames($this->_tablePerGroup, $groupID);
		}

		$currentPost = $post;

		$attempts = $date = 0;
		do {
			// Try to get the article date locally first.
			if ($groupID !== '') {
				// Try to get locally.
				$local = $this->_pdo->queryOneRow(
					sprintf('
						SELECT c.date AS date
						FROM %s c, %s p
						WHERE c.id = p.collection_id
						AND c.group_id = %s
						AND p.number = %s LIMIT 1',
						$group['cname'],
						$group['pname'],
						$groupID,
						$currentPost
					)
				);
				if ($local !== false) {
					$date = $local['date'];
					break;
				}
			}

			// If we could not find it locally, try usenet.
			$header = $this->_nntp->getXOVER($currentPost);
			if (!$this->_nntp->isError($header)) {
				// Check if the date is set.
				if (isset($header[0]['Date']) && strlen($header[0]['Date']) > 0) {
					$date = $header[0]['Date'];
					break;
				}
			}

			// Try to get a different article number.
			if (abs($currentPost - $groupData['first']) > abs($groupData['last'] - $currentPost)) {
				$tempPost = round($currentPost / (mt_rand(1005, 1012) / 1000), 0, PHP_ROUND_HALF_UP);
				if ($tempPost < $groupData['first']) {
					$tempPost = $groupData['first'];
				}
			} else {
				$tempPost = round((mt_rand(1005, 1012) / 1000) * $currentPost, 0, PHP_ROUND_HALF_UP);
				if ($tempPost > $groupData['last']) {
					$tempPost = $groupData['last'];
				}
			}
			// If we got the same article number as last time, give up.
			if ($tempPost === $currentPost) {
				break;
			}
			$currentPost = $tempPost;

			if ($this->_debug) {
				$this->_colorCLI->doEcho($this->_colorCLI->debug('Postdate retried ' . $attempts . " time(s)."));
			}
		} while ($attempts++ <= 20);

		// If we didn't get a date, set it to now.
		if (!$date) {
			$date = time();
		} else {
			$date = strtotime($date);
		}

		if ($this->_debug) {
			$this->_debugging->log(
				'Binaries',
				"postdate",
				'Article (' .
				$post .
				"'s) date is (" .
				$date .
				') (' .
				$this->daysOld($date) .
				" days old)",
				\Logger::LOG_INFO
			);
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
		$goalTime =          // The time we want =
			time()           // current unix time (ex. 1395699114)
			-                // minus
			(86400 * $days); // 86400 (seconds in a day) times days wanted. (ie 1395699114 - 2592000 (30days)) = 1393107114

		// The servers oldest date.
		$firstDate = $this->postdate($data['first'], $data);
		if ($goalTime < $firstDate) {
			// If the date we want is older than the oldest date in the group return the groups oldest article.
			return $data['first'];
		}

		// The servers newest date.
		$lastDate = $this->postdate($data['last'], $data);
		if ($goalTime > $lastDate) {
			// If the date we want is newer than the groups newest date, return the groups newest article.
			return $data['last'];
		}

		$totalArticles = (int)($lastDate - $firstDate);

		if ($this->_echoCLI) {
			$this->_colorCLI->doEcho(
				$this->_colorCLI->primary(
					'Searching for an approximate article number for group ' . $data['group'] . ' ' . $days . ' days back.'
				)
			);
		}

		switch (true) {
			case $totalArticles < 1000000:
				$matchPercentage = 1.0100;
				break;
			case $totalArticles < 10000000:
				$matchPercentage = 1.0070;

				break;
			case $totalArticles < 100000000:
				$matchPercentage = 1.0030;
				break;
			case $totalArticles < 500000000:
				$matchPercentage = 1.0010;
				break;
			case $totalArticles < 1000000000:
				$matchPercentage = 1.0008;
				break;
			default:
				$matchPercentage = 1.0005;
				break;
		}

		$wantedArticle = ($data['last'] * (($goalTime - $firstDate) / ($totalArticles)));
		$articleTime = 0;
		$percent = 1.01;
		for ($i = 0; $i < 100; $i++) {
			$wantedArticle = (int)$wantedArticle;

			if ($wantedArticle <= $data['first'] || $wantedArticle >= $data['last']) {
				break;
			}

			$articleTime = $this->postdate($wantedArticle, $data);
			if ($articleTime >= ($goalTime / $matchPercentage) && $articleTime <= ($goalTime * $matchPercentage)) {
				break;
			}

			if ($articleTime > $goalTime) {
				$wantedArticle /= $percent;
			} else if ($articleTime < $goalTime) {
				$wantedArticle *= $percent;
			}
			$percent -= 0.001;
		}

		$wantedArticle = (int)$wantedArticle;
		if ($this->_echoCLI) {
			$this->_colorCLI->doEcho(
				$this->_colorCLI->primary(
					 'Found article #' . $wantedArticle . ' which has a date of ' . date('r', $articleTime) .
					', vs wanted date of ' . date('r', $goalTime) . '.'
				)
			);
		}

		return $wantedArticle;
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
	 * Add article numbers from missing headers to DB.
	 *
	 * @param array  $numbers   The article numbers of the missing headers.
	 * @param string $tableName Name of the partrepair table to insert into.
	 * @param int    $groupID   The ID of this groups.
	 *
	 * @return bool
	 */
	private function addMissingParts($numbers, $tableName, $groupID)
	{
		$insertStr = 'INSERT INTO ' . $tableName . ' (numberid, group_id) VALUES ';
		foreach ($numbers as $number) {
			$insertStr .= '(' . $number . ',' . $groupID .'),';
		}
		return $this->_pdo->queryInsert((rtrim($insertStr, ',') . ' ON DUPLICATE KEY UPDATE attempts=attempts+1'));
	}

	/**
	 * Clean up part repair table.
	 *
	 * @param array  $numbers   The article numbers.
	 * @param string $tableName Name of the part repair table to work on.
	 * @param int    $groupID   The ID of the group.
	 *
	 * @return void
	 */
	private function removeRepairedParts($numbers, $tableName, $groupID)
	{
		$sql = 'DELETE FROM ' . $tableName . ' WHERE numberid in (';
		foreach ($numbers as $number) {
			$sql .= $number . ',';
		}
		$this->_pdo->queryExec((rtrim($sql, ',') . ') AND group_id = ' . $groupID));
	}

	/**
	 * Get blacklist and cache it. Return if already cached.
	 *
	 * @return void
	 */
	protected function retrieveBlackList()
	{
		if ($this->_blackListLoaded) {
			return;
		}
		$this->blackList = $this->getBlacklist(true);
		$this->_blackListLoaded = true;
		if (count($this->blackList) === 0) {
			$this->_blackListEmpty = true;
		}
	}

	/**
	 * Check if an article is blacklisted.
	 *
	 * @param array  $msg       The article header (OVER format).
	 * @param string $groupName The group name.
	 *
	 * @return bool
	 */
	public function isBlackListed($msg, $groupName)
	{
		$this->retrieveBlackList();
		$field = [];
		$field[self::BLACKLIST_FIELD_SUBJECT]   = $msg['Subject'];
		$field[self::BLACKLIST_FIELD_FROM]      = $msg['From'];
		$field[self::BLACKLIST_FIELD_MESSAGEID] = $msg['Message-ID'];

		foreach ($this->blackList as $blackList) {
			if (preg_match('/^' . $blackList['groupname'] . '$/i', $groupName)) {
				// Black?
				if ($blackList['optype'] == self::OPTYPE_BLACKLIST && preg_match('/' . $blackList['regex'] . '/i', $field[$blackList['msgcol']])) {
					return true;
				// White?
				} else if ($blackList['optype'] == self::OPTYPE_WHITELIST && !preg_match('/' . $blackList['regex'] . '/i', $field[$blackList['msgcol']])) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Return all blacklists.
	 *
	 * @param bool $activeOnly Only display active blacklists ?
	 *
	 * @return array
	 */
	public function getBlacklist($activeOnly = true)
	{
		return $this->_pdo->query(
			sprintf('
				SELECT
					binaryblacklist.id, binaryblacklist.optype, binaryblacklist.status, binaryblacklist.description,
					binaryblacklist.groupname AS groupname, binaryblacklist.regex, groups.id AS group_id, binaryblacklist.msgcol
				FROM binaryblacklist
				LEFT OUTER JOIN groups ON groups.name = binaryblacklist.groupname %s
				ORDER BY coalesce(groupname,\'zzz\')',
				($activeOnly ? ' WHERE binaryblacklist.status = 1 ' : '')
			)
		);
	}

	/**
	 * Return the specified blacklist.
	 *
	 * @param int $id The blacklist ID.
	 *
	 * @return array|bool
	 */
	public function getBlacklistByID($id)
	{
		return $this->_pdo->queryOneRow(sprintf('SELECT * FROM binaryblacklist WHERE id = %d', $id));
	}

	/**
	 * Delete a blacklist.
	 *
	 * @param int $id The ID of the blacklist.
	 *
	 * @return bool
	 */
	public function deleteBlacklist($id)
	{
		return $this->_pdo->queryExec(sprintf('DELETE FROM binaryblacklist WHERE id = %d', $id));
	}

	/**
	 * Updates a blacklist from binary blacklist edit admin web page.
	 *
	 * @param Array $blacklistArray
	 *
	 * @return bool
	 */
	public function updateBlacklist($blacklistArray)
	{
		$this->_pdo->queryExec(
			sprintf('
				UPDATE binaryblacklist
				SET groupname = %s, regex = %s, status = %d, description = %s, optype = %d, msgcol = %d
				WHERE id = %d ',
				($blacklistArray['groupname'] == ''
					? 'null'
					: $this->_pdo->escapeString(preg_replace('/a\.b\./i', 'alt.binaries.', $blacklistArray['groupname']))
				),
				$this->_pdo->escapeString($blacklistArray['regex']), $blacklistArray['status'],
				$this->_pdo->escapeString($blacklistArray['description']),
				$blacklistArray['optype'],
				$blacklistArray['msgcol'],
				$blacklistArray['id']
			)
		);
	}

	/**
	 * Adds a new blacklist from binary blacklist edit admin web page.
	 *
	 * @param Array $blacklistArray
	 *
	 * @return bool
	 */
	public function addBlacklist($blacklistArray)
	{
		return $this->_pdo->queryInsert(
			sprintf('
				INSERT INTO binaryblacklist (groupname, regex, status, description, optype, msgcol)
				VALUES (%s, %s, %d, %s, %d, %d)',
				($blacklistArray['groupname'] == ''
					? 'null'
					: $this->_pdo->escapeString(preg_replace('/a\.b\./i', 'alt.binaries.', $blacklistArray['groupname']))
				),
				$this->_pdo->escapeString($blacklistArray['regex']),
				$blacklistArray['status'],
				$this->_pdo->escapeString($blacklistArray['description']),
				$blacklistArray['optype'],
				$blacklistArray['msgcol']
			)
		);
	}

	/**
	 * Delete Collections/Binaries/Parts for a Collection ID.
	 *
	 * @param int $collectionID Collections table ID
	 *
	 * @note A trigger automatically deletes the parts/binaries.
	 *
	 * @return void
	 */
	public function delete($collectionID)
	{
		$this->_pdo->queryExec(sprintf('DELETE FROM collections WHERE id = %d', $collectionID));
	}

	/**
	 * Delete all Collections/Binaries/Parts for a group ID.
	 *
	 * @param int $groupID The ID of the group.
	 *
	 * @note A trigger automatically deletes the parts/binaries.
	 *
	 * @return void
	 */
	public function purgeGroup($groupID)
	{
		$this->_pdo->queryExec(sprintf('DELETE c FROM collections c WHERE c.group_id = %d', $groupID));
	}

	/**
	 * Log / Echo message.
	 *
	 * @param string $message Message to log.
	 * @param string $method  Method that called this.
	 * @param int    $level   Logger severity level constant.
	 * @param string $color   ColorCLI method name.
	 */
	private function log($message, $method, $level, $color)
	{
		if ($this->_echoCLI) {
			$this->_colorCLI->doEcho(
				$this->_colorCLI->$color($message), true
			);
		}

		if ($this->_debug) {
			$this->_debugging->log('Binaries', $method, $message, $level);
		}
	}

}
