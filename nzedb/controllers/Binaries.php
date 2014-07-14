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
	public $blackList = array();

	/**
	 * How many headers do we download per loop?
	 *
	 * @var int
	 */
	public $messageBuffer;

	/**
	 * @var Backfill
	 */
	protected $_backFill;

	/**
	 * @var ColorCLI
	 */
	protected $_colorCLI;

	/**
	 * @var CollectionsCleaning
	 */
	protected $_collectionsCleaning;

	/**
	 * @var ConsoleTools
	 */
	protected $_consoleTools;

	/**
	 * @var nzedb\db\DB
	 */
	protected $_db;

	/**
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
	 * If we changed collections, this will be false and the collection hashes will need to be recalculated.
	 *
	 * @var bool
	 */
	protected $_hashCheck;

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
	 * @var int
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
	 * Constructor.
	 *
	 * @param NNTP $nntp Class instance of NNTP.
	 * @param bool $echo Echo to cli?
	 * @param bool|Backfill $backFill Pass Backfill class if started from there.
	 */
	public function __construct($nntp = null, $echo = true, $backFill = false)
	{
		$this->_nntp = $nntp;
		$this->_echoCLI = ($echo && nZEDb_ECHOCLI);
		$this->_debug = (nZEDb_DEBUG || nZEDb_LOGGING);
		if ($backFill === false) {
			$this->_backFill = new Backfill($this->_nntp, $echo);
		} else {
			$this->_backFill = $backFill;
		}
		$this->_colorCLI = new ColorCLI();
		$this->_collectionsCleaning = new CollectionsCleaning();
		$this->_consoleTools = new ConsoleTools();
		$this->_db = new nzedb\db\DB();
		if ($this->_debug) {
			$this->_debugging = new Debugging("Binaries");
		}
		$this->_groups = new Groups($this->_db);

		$site = (new Sites())->get();
		$this->messageBuffer = (!empty($site->maxmssgs) ? $site->maxmssgs : 20000);
		$this->_compressedHeaders = ($site->compressedheaders == 1 ? true : false);
		$this->_partRepair = ($site->partrepair == 0 ? false : true);
		$this->_hashCheck = ($site->hashcheck == 1 ? true : false);
		$this->_newGroupScanByDays = ($site->newgroupscanmethod == 1 ? true : false);
		$this->_newGroupMessagesToScan = (!empty($site->newgroupmsgstoscan) ? $site->newgroupmsgstoscan : 50000);
		$this->_newGroupDaysToScan = (!empty($site->newgroupdaystoscan) ? (int)$site->newgroupdaystoscan : 3);
		$this->_partRepairLimit = (!empty($site->maxpartrepair) ? (int)$site->maxpartrepair : 15000);
		$this->_showDroppedYEncParts = ($site->showdroppedyencparts == 1 ? true : false);
		$this->_tablePerGroup = ($site->tablepergroup == 1 ? true : false);

		$this->blackList = array();
		$this->_blackListLoaded = false;

		$SQLTime = $this->_db->queryOneRow('SELECT UNIX_TIMESTAMP(NOW()) AS time');
		if ($SQLTime !== false) {
			if ($SQLTime['time'] != time()) {
				$difference = abs($SQLTime['time'] - time());
				if ($difference > 60) {
					exit('FATAL ERROR: PHP and MySQL time do not match!' . PHP_EOL);
				}
			}
		} else {
			exit('FATAL ERROR: Unable to get current time from MySQL!' . PHP_EOL);
		}
	}

	/**
	 * Download new headers for all active groups.
	 *
	 * @return void
	 */
	public function updateAllGroups()
	{
		if ($this->_hashCheck === false) {
			$dMessage = "We have updated the way collections are created, the collection table has to be updated to
				use the new changes, if you want to run this now, type 'yes', else type no to see how to run manually.";
			if ($this->_debug) {
				$this->_debugging->start("updateAllGroups", $dMessage, 5);
			}
			echo $this->_colorCLI->warning($dMessage);
			if (trim(fgets(fopen('php://stdin', 'r'))) != 'yes') {
				$dMessage = "If you want to run this manually, there is a script in misc/testing/DB/ called reset_Collections.php";
				if ($this->_debug) {
					$this->_debugging->start("updateAllGroups", $dMessage, 1);
				}
				exit($this->_colorCLI->primary($dMessage));
			}
			(new Releases($this->_echoCLI))->resetCollections();
		}

		$groups = $this->_groups->getActive();

		$groupCount = count($groups);
		if ($groupCount > 0) {
			$counter = 1;
			$allTime = microtime(true);
			$dMessage = "Updating: " . $groupCount . ' group(s) - Using compression? ' . ($this->_compressedHeaders ? 'Yes' : 'No');
			if ($this->_debug) {
				$this->_debugging->start("updateAllGroups", $dMessage, 5);
			}

			if ($this->_echoCLI) {
				$this->_colorCLI->doEcho($this->_colorCLI->header($dMessage), true);
			}

			// Loop through groups.
			foreach ($groups as $group) {
				$dMessage = "Starting group " . $counter . ' of ' . $groupCount;
				if ($this->_debug) {
					$this->_debugging->start("updateAllGroups", $dMessage, 5);
				}

				if ($this->_echoCLI) {
					$this->_colorCLI->doEcho($this->_colorCLI->header($dMessage), true);
				}
				$this->updateGroup($group);
				$counter++;
			}

			$dMessage = 'Updating completed in ' . number_format(microtime(true) - $allTime, 2) . " seconds.";
			if ($this->_debug) {
				$this->_debugging->start("updateAllGroups", $dMessage, 5);
			}

			if ($this->_echoCLI) {
				$this->_colorCLI->doEcho($this->_colorCLI->primary($dMessage));
			}
		} else {
			$dMessage = "No groups specified. Ensure groups are added to nZEDb's database for updating.";
			if ($this->_debug) {
				$this->_debugging->start("updateAllGroups", $dMessage, 4);
			}

			if ($this->_echoCLI) {
				$this->_colorCLI->doEcho($this->_colorCLI->warning($dMessage), true);
			}
		}
	}

	/**
	 * Download new headers for a single group.
	 *
	 * @param array $groupMySQL Array of MySQL results for a single group.
	 *
	 * @return void
	 */
	public function updateGroup($groupMySQL)
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

			$groupMySQL['first_record_postdate'] = $this->_backFill->postdate($groupMySQL['first_record'], $groupNNTP);

			$this->_db->queryExec(
				sprintf('
					UPDATE groups
					SET first_record_postdate = %s
					WHERE id = %d',
					$this->_db->from_unixtime($groupMySQL['first_record_postdate']),
					$groupMySQL['id']
				)
			);
		}

		// Get first article we want aka the oldest.
		if ($groupMySQL['last_record'] == 0) {
			if ($this->_newGroupScanByDays) {
				// For new newsgroups - determine here how far we want to go back using date.
				$first = $this->_backFill->daytopost($this->_newGroupDaysToScan, $groupNNTP);
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
						' Local newest: ' . number_format($groupMySQL['last_record']), true
					)
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
							$groupMySQL['first_record_postdate'] = $this->_backFill->postdate($groupMySQL['first_record'], $groupNNTP);
						}

						$this->_db->queryExec(
							sprintf('
								UPDATE groups
								SET first_record = %s, first_record_postdate = %s
								WHERE id = %d',
								$scanSummary['firstArticleNumber'],
								$this->_db->from_unixtime($this->_db->escapeString($groupMySQL['first_record_postdate'])),
								$groupMySQL['id']
							)
						);
					}

					if (isset($scanSummary['lastArticleDate'])) {
						$scanSummary['lastArticleDate'] = strtotime($scanSummary['lastArticleDate']);
					} else {
						$scanSummary['lastArticleDate'] = $this->_backFill->postdate($scanSummary['lastArticleNumber'], $groupNNTP);
					}

					$this->_db->queryExec(
						sprintf('
							UPDATE groups
							SET last_record = %s, last_record_postdate = %s, last_updated = NOW()
							WHERE id = %d',
							$this->_db->escapeString($scanSummary['lastArticleNumber']),
							$this->_db->from_unixtime($scanSummary['lastArticleDate']),
							$groupMySQL['id']
						)
					);
				} else {
					// If we didn't fetch headers, update the record still.
					$this->_db->queryExec(
						sprintf('
							UPDATE groups
							SET last_record = %s, last_updated = NOW()
							WHERE id = %d',
							$this->_db->escapeString($last),
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
	 * @param array  $groupMySQL The group info from mysql.
	 * @param int    $first    The oldest wanted header.
	 * @param int    $last     The newest wanted header.
	 * @param string $type     Is this partrepair or update?
	 * @param null   $missingParts
	 *
	 * @return array|bool
	 */
	public function scan($groupMySQL, $first, $last, $type = 'update', $missingParts = null)
	{
		// Start time of scan method and of fetching headers.
		$startLoop = $startHeaders = microtime(true);

		// Check if MySQL tables exist, create if they do not, get their names at the same time.
		$groupNames = $this->_db->tryTablePerGroup($this->_tablePerGroup, $groupMySQL['id']);

		// Download the headers.
		if ($type === 'partrepair') {
			// This is slower but possibly is better with missing headers.
			$headers = $this->_nntp->getOverview($first . '-' . $last, true, false);
		} else {
			$headers = $this->_nntp->getXOVER($first . '-' . $last);
		}

		// If there was an error, try to reconnect.
		if ($this->_nntp->isError($headers)) {

			// Increment if part repair and return false.
			if ($type === 'partrepair') {
				$this->_db->queryExec(
					sprintf(
						'UPDATE partrepair SET attempts = attempts + 1 WHERE group_id = %d AND numberid %s',
						$groupMySQL['id'],
						($first == $last ? '= ' . $first : 'IN (' . implode(',', range($first, $last)) . ')')
					)
				);
				return false;
			}

			// This is usually a compression error, so try disabling compression.
			$this->_nntp->doQuit();
			if ($this->_nntp->doConnect(false) !== true) {
				return false;
			}

			// Re-select group, download headers again without compression and re-enable compression.
			$this->_nntp->selectGroup($groupMySQL['name']);
			$headers = $this->_nntp->getXOVER($first . '-' . $last);
			$this->_nntp->enableCompression();

			// Check if the non-compression headers have an error.
			if ($this->_nntp->isError($headers)) {

				$dMessage = "Code {$headers->code}: {$headers->message}\nSkipping group: ${$groupMySQL['name']}";
				if ($this->_debug) {
					$this->_debugging->start("scan", $dMessage, 3);
				}

				if ($this->_echoCLI) {
					$this->_colorCLI->doEcho($this->_colorCLI->error($dMessage), true);
				}

				return false;
			}
		}

		// Start of processing headers.
		$startCleaning = microtime(true);

		// End of the getting data from usenet.
		$timeHeaders = number_format($startCleaning - $startHeaders, 2);

		$returnArray = array();

		// Check if we got headers.
		$msgCount = count($headers);
		if ($msgCount > 0) {

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

			$headersReceived = $headersBlackListed = $headersIgnored = $headersRepaired = $articles = array();

			// Loop articles, figure out files/parts.
			foreach ($headers as $header) {
				if (!isset($header['Number'])) {
					continue;
				}

				// If set we are running in partRepair mode.
				if (isset($missingParts)) {
					if (!in_array($header['Number'], $missingParts)) {
						// If article isn't one that is missing skip it.
						continue;
					} else {
						// We got the part this time. Remove article from part repair.
						$headersRepaired[] = $header['Number'];
					}
				}

				$headersReceived[] = $header['Number'];

				// Find part / total parts. Ignore if no part count found.
				if (preg_match('/^\s*(?!"Usenet Index Post)(.+)\s+\((\d+)\/(\d+)\)$/', $header['Subject'], $matches)) {
					// Add yEnc to subjects that do not have them, but have the part number at the end of the header.
					if (!stristr($header['Subject'], 'yEnc')) {
						$matches[1] .= ' yEnc';
					}
				} else {
					if ($this->_showDroppedYEncParts === true) {
						file_put_contents(
							nZEDb_LOGS . 'not_yenc' . $groupMySQL['name'] . '.dropped.log',
							$header['Subject'] . PHP_EOL, FILE_APPEND
						);
					}
					$headersIgnored[] = $header['Number'];
					continue;
				}

				// Filter subject based on black/white list.
				if ($this->_blackListEmpty === false && $this->isBlackListed($header, $groupMySQL['name'])) {
					$headersBlackListed[] = $header['Number'];
					continue;
				}

				// Set up the info for inserting into parts/binaries/collections tables.
				if (!isset($articles[$matches[1]])) {
					$articles[$matches[1]] = $header;

					/* Date from header should be a string this format:
					 * 31 Mar 2014 15:36:04 GMT or 6 Oct 1998 04:38:40 -0500
					 * Still make sure it's not unix time, convert it to unix time if it is.
					 */
					$date = (is_numeric($header['Date']) ? $header['Date'] : strtotime($header['Date']));

					// Get the current unixtime from PHP.
					$now = time();

					// Check if the header's time is newer than now, if so, set it now.
					$articles[$matches[1]]['Date'] = ($date > $now ? $now : $date);

					$articles[$matches[1]]['MaxParts'] = $matches[3];

					// Attempt to find the file count. If it is not found, set it to 0.
					if (!preg_match('/(\[|\(|\s)(\d{1,5})(\/|(\s|_)of(\s|_)|\-)(\d{1,5})(\]|\)|\s|$|:)/i', $matches[1], $fileCount)) {
						$fileCount[2] = $fileCount[6] = 0;

						if ($this->_showDroppedYEncParts === true) {
							file_put_contents(
								nZEDb_LOGS . 'no_files' . $groupMySQL['name'] . '.log',
								$header['Subject'] . PHP_EOL, FILE_APPEND
							);
						}
					}

					// (hash) Used to group articles together when forming the release/nzb.
					$articles[$matches[1]]['CollectionHash'] =
						sha1(
							$this->_collectionsCleaning->collectionsCleaner($matches[1], $groupMySQL['name']) .
							$header['From'] .
							$groupMySQL['id'] .
							$fileCount[6]
						);
					$articles[$matches[1]]['MaxFiles'] = $fileCount[6];
					$articles[$matches[1]]['File']     = $fileCount[2];
				}

				if (!isset($header['Bytes'])) {
					$header['Bytes'] = (isset($header[':bytes']) ? $header[':bytes'] : 0);
				}

				$articles[$matches[1]]['Parts'][$matches[2]] =
					array(
						'Message-ID' => substr($header['Message-ID'], 1, -1), // Strip the < and >, saves space in DB.
						'number'     => $header['Number'],
						'part'       => $matches[2],
						'size'       => (is_numeric($header['Bytes']) ? $header['Bytes'] : 0)
					);
			}

			// Array of all the requested article numbers.
			$total = ($last - $first);
			if ($total > 1) {
				$rangeRequested = range($first, $last);
			} elseif ($total === 1) {
				$rangeRequested = array($first, $last);
			} else {
				$rangeRequested[] = $first;
			}

			unset($headers); // Reclaim memory.
			$rangeNotReceived = array_diff($rangeRequested, $headersReceived);

			if ($this->_echoCLI && $type !== 'partrepair') {
				$this->_colorCLI->doEcho(
					$this->_colorCLI->primary(
						'Received ' . number_format(count($headersReceived)) .
						' articles of ' . (number_format($last - $first + 1)) . ' requested, ' .
						count($headersBlackListed) . ' blacklisted, ' . count($headersIgnored) . ' not yEnc.'
					)
				);
			}

			if (count($headersRepaired) > 0) {
				$this->removeRepairedParts($headersRepaired, $groupMySQL['id']);
			}

			if (count($rangeNotReceived) > 0) {
				switch ($type) {
					case 'backfill':
						// Don't add missing articles.
						break;
					case 'partrepair':
						// Don't add here. Bulk update in partRepair
						break;
					case 'update':
					default:
						if ($this->_partRepair) {
							$this->addMissingParts($rangeNotReceived, $groupMySQL['id']);
						}
						break;
				}

				if ($this->_echoCLI && $type != 'partrepair') {
					$this->_colorCLI->doEcho(
						$this->_colorCLI->alternate(
							'Server did not return ' . count($rangeNotReceived) .
							' articles from ' . $groupMySQL['name'] . '.'
						), true
					);
				}
			}

			// Start of inserting into SQL.
			$startUpdate = microtime(true);

			// End of processing headers.
			$timeCleaning = number_format($startUpdate - $startCleaning, 2);

			if (count($articles) > 0) {

				$collectionHashes = $headersNotInserted = array();

				$partsQuery = sprintf('INSERT IGNORE INTO %s (binaryid, number, messageid, partnumber, size) VALUES ', $groupNames['pname']);

				// Loop through the reformed article headers.
				foreach ($articles AS $subject => $data) {
					if (isset($data['Parts'])) {

						$this->_db->beginTransaction();

						// Check if we already inserted the collection.
						if (isset($collectionHashes[$data['CollectionHash']])) {
							// Re-use the collectionID.
							$collectionID = $collectionHashes[$data['CollectionHash']];
						} else {

							// Check if we already have the collection.
							$collectionCheck = $this->_db->queryOneRow(
								sprintf("
									SELECT id, subject
									FROM %s
									WHERE collectionhash = '%s'",
									$groupNames['cname'],
									$data['CollectionHash']
								)
							);

							// If we don't have the collection, insert it.
							if ($collectionCheck === false) {
								// The update on duplicate key is needed for those who run multiple instances
								// of the script not to get errors of duplicate keys.
								$collectionID = $this->_db->queryInsert(
									sprintf("
										INSERT INTO %s (subject, fromname, date, xref, group_id,
											totalfiles, collectionhash, dateadded)
										VALUES (%s, %s, FROM_UNIXTIME(%s), %s, %d, %d, '%s', NOW())
										ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)",
										$groupNames['cname'],
										$this->_db->escapeString(substr(utf8_encode($subject), 0, 255)),
										$this->_db->escapeString(utf8_encode($data['From'])),
										$data['Date'],
										$this->_db->escapeString(substr($data['Xref'], 0, 255)),
										$groupMySQL['id'],
										$data['MaxFiles'],
										$data['CollectionHash']
									)
								);

								if ($collectionID === false) {
									$headersNotInserted += $this->_rollbackAddToPartRepair($data['Parts']);
									continue;
								}
							} else {
								$collectionID = $collectionCheck['id'];
								// Update the collection table with the last seen date for the collection.
								// This way we know when the last time a person posted for this hash.
								$this->_db->queryExec(
									sprintf(
										'UPDATE %s SET dateadded = NOW() WHERE id = %s',
										$groupNames['cname'],
										$collectionID
									)
								);
							}
							// Buffer found collection hashes / ID's.
							$collectionHashes[$data['CollectionHash']] = $collectionID;
						}

						$binaryHash = md5($subject . $data['From'] . $groupMySQL['id']);

						$binaryCheck = $this->_db->queryOneRow(
							sprintf(
								"SELECT id FROM %s WHERE binaryhash = '%s'",
								$groupNames['bname'],
								$binaryHash
							)
						);

						if ($binaryCheck === false) {
							$binaryID = $this->_db->queryInsert(
								sprintf("
									INSERT INTO %s (binaryhash, name, collectionid, totalparts, filenumber)
									VALUES ('%s', %s, %d, %d, %d)",
									$groupNames['bname'],
									$binaryHash,
									$this->_db->escapeString(utf8_encode($subject)),
									$collectionID,
									$data['MaxParts'],
									$data['File']
								)
							);

							if ($binaryID === false) {
								$headersNotInserted += $this->_rollbackAddToPartRepair($data['Parts']);
								continue;
							}
						} else {
							$binaryID = $binaryCheck['id'];
						}

						$tempPartsQuery = $partsQuery;

						foreach ($data['Parts'] as $partData) {
							$tempPartsQuery .=
								'(' . $binaryID . ',' . $partData['number'] . ",'" .
								$partData['Message-ID'] . "'," .
								$partData['part'] . ',' . $partData['size'] . '),';
						}

						if ($this->_db->queryExec(rtrim($tempPartsQuery, ',')) === false) {
							$headersNotInserted += $this->_rollbackAddToPartRepair($data['Parts']);
						} else {
							$this->_db->Commit();
						}

					}
				}

				$notInsertedCount = count($headersNotInserted);
				if ($notInsertedCount > 0) {
					$dMessage = $notInsertedCount . ' parts failed to insert.';
					if ($this->_debug) {
						$this->_debugging->start('scan', $dMessage, 3);
					}

					if ($this->_echoCLI) {
						$this->_colorCLI->doEcho($this->_colorCLI->warning($dMessage), true);
					}

					if ($this->_partRepair) {
						$this->addMissingParts($headersNotInserted, $groupMySQL['id']);
					}
				}
			}

			$currentMicroTime = microtime(true);
			if ($this->_echoCLI && $type != 'partrepair') {
				$this->_colorCLI->doEcho(
					$this->_colorCLI->alternateOver($timeHeaders . 's') .
					$this->_colorCLI->primaryOver(' to download articles, ') .
					$this->_colorCLI->alternateOver($timeCleaning . 's') .
					$this->_colorCLI->primaryOver(' to process articles, ') .
					$this->_colorCLI->alternateOver(number_format($currentMicroTime - $startUpdate, 2) . 's') .
					$this->_colorCLI->primaryOver(' to insert articles, ') .
					$this->_colorCLI->alternateOver(number_format($currentMicroTime - $startLoop, 2) . 's') .
					$this->_colorCLI->primary(' total.')
				);
			}
		}
		return $returnArray;
	}

	/**
	 * If we failed to insert Collections/Binaries/Parts, rollback the transaction and add the parts to part repair.
	 *
	 * @param array $parts Array of parts we tried to insert.
	 *
	 * @return array Array of article numbers to add to part repair.
	 *
	 * @access protected
	 */
	protected function _rollbackAddToPartRepair(&$parts)
	{
		$headersNotInserted = array();
		foreach ($parts as $part) {
			$headersNotInserted[] = $part['number'];
		}
		$this->_db->Rollback();
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
		// Check that tables exist, create if they do not.
		$group = $this->_db->tryTablePerGroup($this->_tablePerGroup, $groupArr['id']);;

		// Get all parts in partrepair table.
		$missingParts = $this->_db->query(
			sprintf('
				SELECT * FROM %s
				WHERE group_id = %d AND attempts < 5
				ORDER BY numberid ASC LIMIT %d',
				$group['prname'],
				$groupArr['id'],
				$this->_partRepairLimit
			)
		);
		$partsRepaired = 0;

		$missingCount = count($missingParts);
		if ($missingCount > 0) {
			if ($this->_echoCLI) {
				$this->_consoleTools->overWritePrimary(
					'Attempting to repair ' .
					number_format($missingCount) .
					' parts.'
				);
			}

			// Loop through each part to group into continuous ranges with a maximum range of messagebuffer/4.
			$ranges = $partList = array();
			$firstPart = $lastNum = $missingParts[0]['numberid'];

			foreach ($missingParts as $part) {
				if (($part['numberid'] - $firstPart) > ($this->messageBuffer / 4)) {

					$ranges[] = array(
						'partfrom' => $firstPart,
						'partto' => $lastNum,
						'partlist' => $partList
					);

					$firstPart = $part['numberid'];
					$partList = array();
				}
				$partList[] = $part['numberid'];
				$lastNum = $part['numberid'];
			}

			$ranges[] = array(
				'partfrom' => $firstPart,
				'partto' => $lastNum,
				'partlist' => $partList
			);

			$num_attempted = 0;

			// Download missing parts in ranges.
			foreach ($ranges as $range) {

				$partFrom = $range['partfrom'];
				$partTo = $range['partto'];
				$partList = $range['partlist'];
				$count = count($range['partlist']);

				$num_attempted += $count;
				$this->_consoleTools->overWritePrimary(
					'Attempting repair: ' .
					$this->_consoleTools->percentString2($num_attempted - $count + 1, $num_attempted, $missingCount) .
					': ' . $partFrom . ' to ' . $partTo . ' .'
				);

				// Get article headers from newsgroup.
				$this->scan($groupArr, $partFrom, $partTo, 'partrepair', $partList);
			}

			// Calculate parts repaired
			$result = $this->_db->queryOneRow(
				sprintf('
					SELECT COUNT(id) AS num
					FROM %s
					WHERE group_id = %d
					AND numberid <= %d',
					$group['prname'],
					$groupArr['id'],
					$missingParts[$missingCount - 1]['numberid']
				)
			);
			if (isset($result['num'])) {
				$partsRepaired = $missingCount - $result['num'];
			}

			// Update attempts on remaining parts for active group
			if (isset($missingParts[$missingCount - 1]['id'])) {
				$this->_db->queryExec(
					sprintf('
						UPDATE %s
						SET attempts = attempts + 1
						WHERE group_id = %d
						AND numberid <= %d',
						$group['prname'],
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

		// Remove articles that we cant fetch after 5 attempts.
		$this->_db->queryExec(sprintf('DELETE FROM %s WHERE attempts >= 5 AND group_id = %d', $group['prname'], $groupArr['id']));
	}

	/**
	 * Add article numbers from missing headers to DB.
	 *
	 * @param array $numbers The article numbers of the missing headers.
	 * @param int   $groupID The ID of this groups.
	 *
	 * @return bool
	 */
	private function addMissingParts($numbers, $groupID)
	{
		// Check that tables exist, create if they do not.
		$group = $this->_db->tryTablePerGroup($this->_tablePerGroup, $groupID);

		$insertStr = 'INSERT INTO ' . $group['prname'] . ' (numberid, group_id) VALUES ';
		foreach ($numbers as $number) {
			$insertStr .= sprintf('(%d, %d), ', $number, $groupID);
		}

		$insertStr = substr($insertStr, 0, -2);
		$insertStr .= ' ON DUPLICATE KEY UPDATE attempts=attempts+1';

		return $this->_db->queryInsert($insertStr);
	}

	/**
	 * Clean up part repair table.
	 *
	 * @param array $numbers The article numbers.
	 * @param int   $groupID The ID of the group.
	 *
	 * @return void
	 */
	private function removeRepairedParts($numbers, $groupID)
	{
		// Check that tables exist, create if they do not.
		$group = $this->_db->tryTablePerGroup($this->_tablePerGroup, $groupID);

		$sql = 'DELETE FROM ' . $group['prname'] . ' WHERE numberid in (';
		foreach ($numbers as $number) {
			$sql .= sprintf('%d, ', $number);
		}
		$sql = substr($sql, 0, -2);
		$sql .= sprintf(') AND group_id = %d', $groupID);
		$this->_db->queryExec($sql);
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

		foreach ($this->blackList as $blackList) {
			if (preg_match('/^' . $blackList['groupname'] . '$/i', $groupName)) {
				// Black?
				if ($blackList['optype'] == Binaries::OPTYPE_BLACKLIST && preg_match('/' . $blackList['regex'] . '/i', $field[$blackList['msgcol']])) {
					$omitBinary = true;
					// White?
				} else if ($blackList['optype'] == Binaries::OPTYPE_WHITELIST && !preg_match('/' . $blackList['regex'] . '/i', $field[$blackList['msgcol']])) {
					$omitBinary = true;
				}
			}
		}

		return $omitBinary;
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
		return $this->_db->query(
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
		return $this->_db->queryOneRow(sprintf('SELECT * FROM binaryblacklist WHERE id = %d', $id));
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
		return $this->_db->queryExec(sprintf('DELETE FROM binaryblacklist WHERE id = %d', $id));
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
		$this->_db->queryExec(
			sprintf('
				UPDATE binaryblacklist
				SET groupname = %s, regex = %s, status = %d, description = %s, optype = %d, msgcol = %d
				WHERE id = %d ',
				($blacklistArray['groupname'] == ''
					? 'null'
					: $this->_db->escapeString(preg_replace('/a\.b\./i', 'alt.binaries.', $blacklistArray['groupname']))
				),
				$this->_db->escapeString($blacklistArray['regex']), $blacklistArray['status'],
				$this->_db->escapeString($blacklistArray['description']),
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
		return $this->_db->queryInsert(
			sprintf('
				INSERT INTO binaryblacklist (groupname, regex, status, description, optype, msgcol)
				VALUES (%s, %s, %d, %s, %d, %d)',
				($blacklistArray['groupname'] == ''
					? 'null'
					: $this->_db->escapeString(preg_replace('/a\.b\./i', 'alt.binaries.', $blacklistArray['groupname']))
				),
				$this->_db->escapeString($blacklistArray['regex']),
				$blacklistArray['status'],
				$this->_db->escapeString($blacklistArray['description']),
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
	 * @return void
	 */
	public function delete($collectionID)
	{
		$bins = $this->_db->query(sprintf('SELECT id FROM binaries WHERE collectionid = %d', $collectionID));
		foreach ($bins as $bin) {
			$this->_db->queryExec(sprintf('DELETE FROM parts WHERE binaryid = %d', $bin['id']));
		}
		$this->_db->queryExec(sprintf('DELETE FROM binaries WHERE collectionid = %d', $collectionID));
		$this->_db->queryExec(sprintf('DELETE FROM collections WHERE id = %d', $collectionID));
	}

	/**
	 * Delete all Collections/Binaries/Parts for a group ID.
	 *
	 * @param int $groupID The ID of the group.
	 *
	 * @return void
	 */
	public function purgeGroup($groupID)
	{
		$this->_db->queryExec(
			sprintf('
				DELETE c, b, p
				FROM collections c
				LEFT OUTER JOIN binaries b ON b.collectionid = c.id
				LEFT OUTER JOIN parts p ON p.binaryid = b.id
				WHERE c.group_id = %d',
				$groupID
			)
		);
	}

}
