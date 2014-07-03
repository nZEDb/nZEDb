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
	 * The cache for headers.
	 *
	 * @var array
	 */
	public $message = array();

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

		$this->blackList = $this->message = array();
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
				$this->message = array();
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
	 * @param array  $groupArr The group info from mysql.
	 * @param int    $first    The oldest wanted header.
	 * @param int    $last     The newest wanted header.
	 * @param string $type     Is this partrepair or update?
	 * @param null   $missingParts
	 *
	 * @return array|bool
	 */
	public function scan($groupArr, $first, $last, $type = 'update', $missingParts = null)
	{
		// Start time of scan method.
		$startLoop = $startHeaders = microtime(true);

		// Empty array, will contain return values.
		$returnArray = array();

		// Check that tables exist, create if they do not
		$group = $this->_db->tryTablePerGroup($this->_tablePerGroup, $groupArr['id']);

		// Download the headers.
		if ($type === 'partrepair') {
			// This is slower but possibly is better with missing headers.
			$headers = $this->_nntp->getOverview($first . "-" . $last, true, false);
		} else {
			$headers = $this->_nntp->getXOVER($first . "-" . $last);
		}

		// If there were an error, try to reconnect.
		if ($this->_nntp->isError($headers)) {

			// Increment if part repair and return false.
			if ($type === 'partrepair') {
				$this->_db->queryExec(
					sprintf(
						'UPDATE partrepair SET attempts = attempts + 1 WHERE group_id = %d AND numberid %s',
						$groupArr['id'],
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
			$this->_nntp->selectGroup($groupArr['name']);
			$headers = $this->_nntp->getXOVER($first . '-' . $last);
			$this->_nntp->enableCompression();

			// Check if the non-compression headers have an error.
			if ($this->_nntp->isError($headers)) {

				$dMessage = "Code {$headers->code}: {$headers->message}\nSkipping group: ${groupArr['name']}";
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

		// Array of all the requested article numbers.
		$rangerequested = $msgsreceived = $msgsblacklisted = $msgsignored = $msgsnotinserted = $msgrepaired = array();
		$total = ($last - $first);
		if ($total > 1) {
			$rangerequested = range($first, $last);
		} elseif ($total === 1) {
			$rangerequested = array($first, $last);
		} else {
			$rangerequested[] = $first;
		}

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

			// Sort the articles before processing, alphabetically by subject. This is to try to use the shortest subject and those without .vol01 in the subject
			usort($headers,
				function ($elem1, $elem2) {
					return strcmp($elem1['Subject'], $elem2['Subject']);
				}
			);

			// Loop articles, figure out files/parts.
			foreach ($headers AS $msg) {
				if (!isset($msg['Number'])) {
					continue;
				}

				// If set we are running in partRepair mode
				if (isset($missingParts)) {
					if (!in_array($msg['Number'], $missingParts)) { // If article isn't one that is missing skip it.
						continue;
					} else { // We got the part this time. Remove article from partrepair.
						$msgrepaired[] = $msg['Number'];
					}
				}

				if (isset($msg['Bytes'])) {
					$bytes = $msg['Bytes'];
				} else {
					$bytes = $msg[':bytes'];
				}

				$msgsreceived[] = $msg['Number'];
				$partnumber = '';
				// Add yEnc to headers that do not have them, but are nzbs and that have the part number at the end of the header
				if (!stristr($msg['Subject'], 'yEnc') && preg_match('/(.+)(\(\d+\/\d+\))$/', $msg['Subject'], $partnumber)) {
					$msg['Subject'] = $partnumber[1] . ' yEnc ' . $partnumber[2];
				}

				$matches = '';
				// Not a binary post most likely.. continue.
				if (!isset($msg['Subject']) ||
					!preg_match('/(.+yEnc).*\((\d+)\/(\d+)\)/', $msg['Subject'], $matches) ||
					preg_match('/"(Usenet Index Post) \d+(_\d+)? yEnc \(\d+\/\d+\)"/', $msg['Subject'], $UIP)
				) {

					if ($this->_showDroppedYEncParts === '1' && !isset($UIP[1])) {
						file_put_contents(nZEDb_RES . 'logs' . DS . 'not_yenc' . $groupArr['name'] . ".dropped.log", $msg['Subject'] . PHP_EOL, FILE_APPEND);
					}

					$msgsignored[] = $msg['Number'];
					continue;
				}

				// Filter subject based on black/white list.
				if ($this->isBlackListed($msg, $groupArr['name'])) {
					$msgsblacklisted[] = $msg['Number'];
					continue;
				}

				// Attempt to find the file count. If it is not found, set it to 0.
				$partless = $matches[1];
				$filecnt = '';
				if (!preg_match('/(\[|\(|\s)(\d{1,5})(\/|(\s|_)of(\s|_)|\-)(\d{1,5})(\]|\)|\s|$|:)/i', $partless, $filecnt)) {
					$filecnt[2] = $filecnt[6] = 0;

					if ($this->_showDroppedYEncParts === '1') {
						file_put_contents(nZEDb_RES . "logs" . DS . 'no_parts' . $groupArr['name'] . ".log", $msg['Subject'] . PHP_EOL, FILE_APPEND);
					}
				}

				if (is_numeric($matches[2]) && is_numeric($matches[3])) {

					array_map('trim', $matches);
					// Inserted into the collections table as the subject.
					$subject = utf8_encode(trim($partless));

					// Set up the info for inserting into parts/binaries/collections tables.
					if (!isset($this->message[$subject])) {
						$this->message[$subject] = $msg;

						/* Date from header should be a string this format:
						 * 31 Mar 2014 15:36:04 GMT or 6 Oct 1998 04:38:40 -0500
						 * Still make sure it's not unix time, convert it to unix time if it is.
						 */
						$date = (is_numeric($msg['Date']) ? $msg['Date'] : strtotime($msg['Date']));

						// Get the current unixtime from PHP.
						$now = time();

						// Check if the header's time is newer than now, if so, set it now.
						$this->message[$subject]['Date'] = ($date > $now ? $now : $date);

						$this->message[$subject]['MaxParts'] = (int)$matches[3];

						// (hash) Groups articles together when forming the release/nzb.
						$this->message[$subject]['CollectionHash'] =
							sha1(
								utf8_encode($this->_collectionsCleaning->collectionsCleaner($subject, $groupArr['name'])) .
								$msg['From'] .
								$groupArr['id'] .
								$filecnt[6]
							);
						$this->message[$subject]['MaxFiles'] = (int)$filecnt[6];
						$this->message[$subject]['File'] = (int)$filecnt[2];
					}

					$nowPart = (int)$matches[2];

					if ($nowPart > 0) {
						$this->message[$subject]['Parts'][$nowPart] =
							array(
								'Message-ID' => substr($msg['Message-ID'], 1, -1),
								'number' => $msg['Number'],
								'part'   => $nowPart,
								'size'   => $bytes
							);
					}
				}
			}

			unset($msg, $headers);
			$maxnum = $last;
			$rangenotreceived = array_diff($rangerequested, $msgsreceived);

			if ($this->_echoCLI && $type != 'partrepair') {
				$this->_colorCLI->doEcho(
					$this->_colorCLI->primary(
						'Received ' .
						number_format(count($msgsreceived)) .
						' articles of ' .
						(number_format($last - $first + 1)) .
						' requested, ' .
						count($msgsblacklisted) .
						' blacklisted, ' .
						count($msgsignored) .
						" not yEnc."
					)
				);
			}

			if (count($msgrepaired) > 0) {
				$this->removeRepairedParts($msgrepaired, $groupArr['id']);
			}

			if (count($rangenotreceived) > 0) {
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
							$this->addMissingParts($rangenotreceived, $groupArr['id']);
						}
						break;
				}

				if ($this->_echoCLI && $type != 'partrepair') {
					$this->_colorCLI->doEcho(
						$this->_colorCLI->alternate(
							'Server did not return ' .
							count($rangenotreceived) .
							" articles from " .
							str_replace('alt.binaries', 'a.b', $groupArr['name']) .
							"."
						), true
					);
				}
			}

			// Start of inserting into SQL.
			$startUpdate = microtime(true);

			// End of processing headers.
			$timeCleaning = number_format($startUpdate - $startCleaning, 2);


			if (isset($this->message) && count($this->message) > 0) {
				$maxnum = $first;
				$pBinaryID = $pNumber = $pMessageID = $pPartNumber = $pSize = 1;
				// Insert collections, binaries and parts into database. When collection exists, only insert new binaries, when binary already exists, only insert new parts.
				$insPartsStmt = $this->_db->Prepare(sprintf("INSERT INTO %s (binaryid, number, messageid, partnumber, size) VALUES (?, ?, ?, ?, ?)",
						$group['pname']
					)
				);
				$insPartsStmt->bindParam(1, $pBinaryID, PDO::PARAM_INT);
				$insPartsStmt->bindParam(2, $pNumber, PDO::PARAM_INT);
				$insPartsStmt->bindParam(3, $pMessageID, PDO::PARAM_STR);
				$insPartsStmt->bindParam(4, $pPartNumber, PDO::PARAM_INT);
				$insPartsStmt->bindParam(5, $pSize, PDO::PARAM_INT);

				$collectionHashes = $binaryHashes = array();
				$lastCollectionHash = $lastBinaryHash = "";
				$lastCollectionID = $lastBinaryID = -1;

				// Loop through the reformed article headers.
				foreach ($this->message AS $subject => $data) {
					if (isset($data['Parts']) && count($data['Parts']) > 0 && $subject != '') {

						$this->_db->beginTransaction();

						$collectionHash = $data['CollectionHash'];

						// Check if the last collection hash is the same.
						if ($lastCollectionHash == $collectionHash && $lastCollectionID !== -1) {
							$collectionID = $lastCollectionID;
						} else {
							$lastCollectionHash = $collectionHash;
							$lastBinaryHash = '';
							$lastBinaryID = -1;
							$cres = $this->_db->queryOneRow(sprintf("SELECT id, subject FROM %s WHERE collectionhash = %s", $group['cname'], $this->_db->escapeString($collectionHash)));
							if ($cres && array_key_exists($collectionHash, $collectionHashes)) {
								$collectionID = $collectionHashes[$collectionHash];
								if (preg_match('/\.vol\d+/i', $subject) && !preg_match('/\.vol\d+/i', $cres['subject'])) {
									$this->_db->queryExec(sprintf("UPDATE %s SET subject = %s WHERE id = %s", $group['cname'], $this->_db->escapeString(substr($subject, 0, 255)),
											$collectionID
										)
									);
								}
							} else {
								if (!$cres) {
									// added utf8_encode on fromname, seems some foreign groups contains characters that were not escaping properly
									$csql = sprintf("INSERT INTO %s (subject, fromname, date, xref, group_id, totalfiles, collectionhash, dateadded)
									VALUES (%s, %s, %s, %s, %d, %d, %s, NOW()) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)",
										$group['cname'],
										$this->_db->escapeString(substr($subject, 0, 255)),
										$this->_db->escapeString(utf8_encode($data['From'])),
										$this->_db->from_unixtime($data['Date']),
										$this->_db->escapeString(substr($data['Xref'], 0, 255)),
										$groupArr['id'], $data['MaxFiles'],
										$this->_db->escapeString($collectionHash)
									);
									$collectionID = $this->_db->queryInsert($csql);
								} else {
									$collectionID = $cres['id'];
									//Update the collection table with the last seen date for the collection. This way we know when the last time a person posted for this hash.
									if (preg_match('/\.vol\d+/i', $subject) && !preg_match('/\.vol\d+/i', $cres['subject'])) {
										$this->_db->queryExec(sprintf("UPDATE %s SET subject = %s WHERE id = %s",
												$group['cname'],
												$this->_db->escapeString(substr($subject, 0, 255)),
												$collectionID
											)
										);
									} else {
										$this->_db->queryExec(sprintf("UPDATE %s SET dateadded = NOW() WHERE id = %s", $group['cname'], $collectionID));
									}
								}
								$collectionHashes[$collectionHash] = $collectionID;
							}
							$lastCollectionID = $collectionID;
						}
						$binaryHash = md5($subject . $data['From'] . $groupArr['id']);

						if ($lastBinaryHash == $binaryHash) {
							$binaryID = $lastBinaryID;
						} else {
							if (array_key_exists($binaryHash, $binaryHashes)) {
								$binaryID = $binaryHashes[$binaryHash];
							} else {
								$lastBinaryHash = $binaryHash;

								$bres = $this->_db->queryOneRow(
									sprintf("SELECT id FROM %s WHERE binaryhash = %s",
										$group['bname'],
										$this->_db->escapeString($binaryHash)
									)
								);
								if (!$bres) {
									$bsql = sprintf(
										"INSERT INTO %s (binaryhash, name, collectionid, totalparts, filenumber)
										VALUES (%s, %s, %d, %s, %s)",
										$group['bname'],
										$this->_db->escapeString($binaryHash),
										$this->_db->escapeString($subject),
										$collectionID,
										$this->_db->escapeString($data['MaxParts']),
										$this->_db->escapeString(round($data['File'])
										)
									);
									$binaryID = $this->_db->queryInsert($bsql);
								} else {
									$binaryID = $bres['id'];
								}

								$binaryHashes[$binaryHash] = $binaryID;
							}
							$lastBinaryID = $binaryID;
						}

						foreach ($data['Parts'] AS $partdata) {
							// These show as not used in PHPStorm, but they are, the query is prepared above with mock values, the values are set here.
							$pBinaryID = $binaryID;
							$pMessageID = $partdata['Message-ID'];
							$pNumber = $partdata['number'];
							$pPartNumber = round($partdata['part']);
							$maxnum = ($partdata['number'] > $maxnum) ? $partdata['number'] : $maxnum;
							if (is_numeric($partdata['size'])) {
								$pSize = $partdata['size'];
							}
							try {
								if (!$insPartsStmt->execute()) {
									$msgsnotinserted[] = $partdata['number'];
								}
							} catch (PDOException $e) {
								if ($e->errorInfo[0] == 1213 || $e->errorInfo[0] == 40001 || $e->errorInfo[0] == 1205) {
									if ($this->_debug) {
										$this->_debugging->start("scan", $e->getMessage(), 3);
									}
									continue;
								}
							}
						}
						$this->_db->Commit();
					}
				}
				$notInsertedCount = count($msgsnotinserted);
				if ($notInsertedCount > 0) {
					$dMessage = $notInsertedCount . " parts failed to insert.";
					if ($this->_debug) {
						$this->_debugging->start("scan", $dMessage, 3);
					}

					if ($this->_echoCLI) {
						$this->_colorCLI->doEcho($this->_colorCLI->warning($dMessage), true);
					}

					if ($this->_partRepair) {
						$this->addMissingParts($msgsnotinserted, $groupArr['id']);
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

			unset($this->message, $data);

			return $returnArray;
		}

		return $returnArray;
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
	 * @param int $id Collections table ID
	 *
	 * @return void
	 */
	public function delete($id)
	{
		$bins = $this->_db->query(sprintf('SELECT id FROM binaries WHERE collectionid = %d', $id));
		foreach ($bins as $bin) {
			$this->_db->queryExec(sprintf('DELETE FROM parts WHERE binaryid = %d', $bin['id']));
		}
		$this->_db->queryExec(sprintf('DELETE FROM binaries WHERE collectionid = %d', $id));
		$this->_db->queryExec(sprintf('DELETE FROM collections WHERE id = %d', $id));
	}

}
