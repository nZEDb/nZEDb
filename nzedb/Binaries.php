<?php

/**
 * Class Binaries
 */
class Binaries
{
	/**
	 * @const int
	 */
	const BLACKLIST_FIELD_SUBJECT = 1;

	/**
	 * @const int
	 */
	const BLACKLIST_FIELD_FROM = 2;

	/**
	 * @const int
	 */
	const BLACKLIST_FIELD_MESSAGEID = 3;

	/**
	 * Instance of class Backfill.
	 * @var object
	 */
	private $backfill;

	/**
	 * Instance of class colorCLI
	 * @var object
	 */
	private $c;

	/**
	 * Instance of class CollectionsCleaning
	 * @var object
	 */
	private $collectionsCleaning;

	/**
	 * Instance of class ConsoleTools
	 * @var object
	 */
	private $consoleTools;

	/**
	 * Instance of class DB
	 * @var object
	 */
	private $db;

	/**
	 * Instance of class Debugging.
	 * @var object
	 */
	private $debugging;

	/**
	 * Instance of class Groups.
	 * @var object
	 */
	private $groups;

	/**
	 * Array with site settings.
	 * @var bool|stdClass
	 */
	private $site;


	/**
	 * The cache of the blacklist.
	 * @var array
	 */
	public $blackList = array();

	/**
	 * Is the blacklist already cached?
	 * @var bool
	 */
	private $blackListLoaded = false;

	/**
	 * Should we use header compression?
	 * @var bool
	 */
	private $compressedHeaders;

	/**
	 * Should we use part repair?
	 * @var bool
	 */
	private $DoPartRepair;

	/**
	 * Should we use grabnzbs?
	 * @var bool
	 */
	private $grabnzbs;

	/**
	 * Do we need to reset collection hash?
	 * @var int
	 */
	private $hashcheck;

	/**
	 * The cache for headers.
	 * @var array
	 */
	public $message = array();

	/**
	 * How many headers do we download per loop?
	 * @var int
	 */
	public $messagebuffer;

	/**
	 * How many days to go back on a new group?
	 * @var bool
	 */
	private $NewGroupScanByDays;

	/**
	 * How many headers to download on new groups?
	 * @var int
	 */
	private $NewGroupMsgsToScan;

	/**
	 * How many headers to download per run of part repair?
	 * @var int
	 */
	private $partrepairlimit;

	/**
	 * Should we show dropped yEnc to CLI?
	 * @var int
	 */
	private $showdroppedyencparts;

	/**
	 * Should we use table per group?
	 * @var int
	 */
	private $tablepergroup;

	/**
	 * Echo to cli?
	 * @var bool
	 */
	protected $echo;

	/**
	 * @var bool
	 */
	protected $debug = false;

	/**
	 * @var int
	 */
	protected $startHeaders;

	/**
	 * @var int
	 */
	public $startLoop;

	/**
	 * @var int
	 */
	protected $startGroup;

	/**
	 * @var int
	 */
	protected $startCleaning;

	/**
	 * @var int
	 */
	protected $startUpdate;

	/**
	 * @var NNTP
	 */
	protected $nntp;

	/**
	 * Constructor.
	 *
	 * @param NNTP $nntp Class instance of NNTP.
	 * @param bool $echo Echo to cli?
	 * @param bool|Backfill $backfill Pass Backfill class if started from there.
	 */
	public function __construct($nntp = null, $echo = true, $backfill = false)
	{
		$this->nntp = $nntp;
		$this->echo = ($echo && nZEDb_ECHOCLI);
		$this->debug = (nZEDb_DEBUG || nZEDb_LOGGING);
		if ($backfill === false) {
			$this->backfill = new Backfill($this->nntp, $echo);
		} else {
			$this->backfill = $backfill;
		}
		$this->c = new ColorCLI();
		$this->collectionsCleaning = new CollectionsCleaning();
		$this->consoleTools = new ConsoleTools();
		$this->db = new DB();
		if ($this->debug) {
			$this->debugging = new Debugging("Binaries");
		}
		$this->groups = new Groups($this->db);

		$s = new Sites();
		$this->site = $s->get();

		$this->compressedHeaders = ($this->site->compressedheaders == '1') ? true : false;
		$this->DoPartRepair = ($this->site->partrepair == '0') ? false : true;
		$this->grabnzbs = ($this->site->grabnzbs == '0') ? false : true;
		$this->hashcheck = (!empty($this->site->hashcheck)) ? (int)$this->site->hashcheck : 0;
		$this->messagebuffer = (!empty($this->site->maxmssgs)) ? (int)$this->site->maxmssgs : 20000;
		$this->NewGroupScanByDays = ($this->site->newgroupscanmethod == '1') ? true : false;
		$this->NewGroupMsgsToScan = (!empty($this->site->newgroupmsgstoscan)) ? (int)$this->site->newgroupmsgstoscan : 50000;
		$this->NewGroupDaysToScan = (!empty($this->site->newgroupdaystoscan)) ? (int)$this->site->newgroupdaystoscan : 3;
		$this->partrepairlimit = (!empty($this->site->maxpartrepair)) ? (int)$this->site->maxpartrepair : 15000;
		$this->showdroppedyencparts = (!empty($this->site->showdroppedyencparts)) ? (int)$this->site->showdroppedyencparts : 0;
		$this->tablepergroup = (!empty($this->site->tablepergroup)) ? (int)$this->site->tablepergroup : 0;

		$this->blackList = $this->message = array();
		$this->blackListLoaded = false;
	}

	/**
	 * Download new headers for all active groups.
	 *
	 * @return void
	 */
	public function updateAllGroups()
	{
		if ($this->hashcheck === 0) {
			$dMessage = "We have updated the way collections are created, the collection table has to be updated to
				use the new changes, if you want to run this now, type 'yes', else type no to see how to run manually.";
			if ($this->debug) {
				$this->debugging->start("updateAllGroups", $dMessage, 5);
			}
			echo $this->c->warning($dMessage);
			if (trim(fgets(fopen('php://stdin', 'r'))) != 'yes') {
				$dMessage = "If you want to run this manually, there is a script in misc/testing/DB/ called reset_Collections.php";
				if ($this->debug) {
					$this->debugging->start("updateAllGroups", $dMessage, 1);
				}
				exit($this->c->primary($dMessage));
			}
			$relss = new Releases($this->echo);
			$relss->resetCollections();
		}

		$res = $this->groups->getActive();

		$groupCount = count($res);
		if ($groupCount > 0) {
			$counter = 1;
			$allTime = microtime(true);
			$dMessage = "Updating: " . $groupCount . ' group(s) - Using compression? ' . (($this->compressedHeaders) ? 'Yes' : 'No');
			if ($this->debug) {
				$this->debugging->start("updateAllGroups", $dMessage, 5);
			}

			if ($this->echo) {
				$this->c->doEcho($this->c->header($dMessage), true);
			}

			// Loop through groups.
			foreach ($res as $groupArr) {
				$this->message = array();
				$dMessage = "Starting group " . $counter . ' of ' . $groupCount;
				if ($this->debug) {
					$this->debugging->start("updateAllGroups", $dMessage, 5);
				}

				if ($this->echo) {
					$this->c->doEcho($this->c->header($dMessage), true);
				}
				$this->updateGroup($groupArr);
				$counter++;
			}

			$dMessage = 'Updating completed in ' . number_format(microtime(true) - $allTime, 2) . " seconds.";
			if ($this->debug) {
				$this->debugging->start("updateAllGroups", $dMessage, 5);
			}

			if ($this->echo) {
				$this->c->doEcho($this->c->primary($dMessage));
			}
		} else {
			$dMessage = "No groups specified. Ensure groups are added to nZEDb's database for updating.";
			if ($this->debug) {
				$this->debugging->start("updateAllGroups", $dMessage, 4);
			}

			if ($this->echo) {
				$this->c->doEcho($this->c->warning($dMessage), true);
			}
		}
	}

	/**
	 * Download new headers for a single group.
	 *
	 * @param array $groupArr Array of MySQL results for a single group.
	 *
	 * @return void
	 */
	public function updateGroup($groupArr)
	{
		$this->startGroup = microtime(true);

		// Select the group, here, needed for processing the group
		$data = $this->nntp->selectGroup($groupArr['name']);
		if ($this->nntp->isError($data)) {
			$data = $this->nntp->dataError($this->nntp, $groupArr['name']);
			if ($this->nntp->isError($data)) {
				return;
			}
		}

		$groupName = str_replace('alt.binaries', 'a.b', $groupArr['name']);
		if ($this->echo) {
			$this->c->doEcho($this->c->primary('Processing ' . $groupName), true);
		}

		// Attempt to repair any missing parts before grabbing new ones.
		if ($groupArr['last_record'] != 0) {
			if ($this->DoPartRepair) {
				if ($this->echo) {
					$this->c->doEcho($this->c->primary("Part repair enabled. Checking for missing parts."), true);
				}
				$this->partRepair($groupArr);
			} else {
				if ($this->echo) {
					$this->c->doEcho($this->c->primary("Part repair disabled by user."), true);
				}
			}
		}

		// Get first and last part numbers from newsgroup.
		if ($groupArr['last_record'] == 0) {
			// For new newsgroups - determine here how far you want to go back using date.
			if ($this->NewGroupScanByDays) {
				$first = $this->backfill->daytopost($this->NewGroupDaysToScan, $data);
				if ($first == '') {
					if ($this->echo) {
						$this->c->doEcho($this->c->warning("Skipping group: {$groupName}"), true);
					}
					return;
				}
			// If not using date, use post count.
			} else {
				if ($data['first'] > ($data['last'] - ($this->NewGroupMsgsToScan + $this->messagebuffer))) {
					$first = $data['first'];
				} else {
					$first = $data['last'] - ($this->NewGroupMsgsToScan + $this->messagebuffer);
				}
			}

			$left = $this->messagebuffer;
			$last = $grouplast = ($data['last'] - $left);
		} else {
			$first = $groupArr['last_record'];

			// Leave 50%+ of the new articles on the server for next run (allow server enough time to actually make parts available).
			$newcount = $data['last'] - $first;

			if ($newcount > $this->messagebuffer) {
				// Drop the remaining plus $this->messagebuffer, pick them up on next run
				if ($newcount < (2 * $this->messagebuffer)) {
					$left = ($newcount / 2);
				} else {
					$remainingcount = $newcount % $this->messagebuffer;
					$left = $remainingcount + $this->messagebuffer;
				}
			} else {
				$left = ($newcount / 2);
			}
		}
		$last = $grouplast = ($data['last'] - $left);
		if ($last < $first) {
			$last= $first;
		}

		// Generate postdate for first record, for those that upgraded.
		if (is_null($groupArr['first_record_postdate']) && $groupArr['first_record'] != '0') {

			$groupArr['first_record_postdate'] = $first_record_postdate = $this->backfill->postdate($groupArr['first_record'], $data);

			$this->db->queryExec(
				sprintf('
					UPDATE groups
					SET first_record_postdate = %s
					WHERE id = %d',
					$this->db->from_unixtime($first_record_postdate),
					$groupArr['id']
				)
			);
		}

		// Defaults for post record first/last postdate
		if (is_null($groupArr['first_record_postdate'])) {
			$first_record_postdate = time();
		} else {
			$first_record_postdate = strtotime($groupArr['first_record_postdate']);
		}

		if (is_null($groupArr['last_record_postdate'])) {
			$last_record_postdate = time();
		} else {
			$last_record_postdate = strtotime($groupArr['last_record_postdate']);
		}

		// Calculate total number of parts.
		$total = $grouplast - $first;
		$realtotal = $data['last'] - $first;

		// If total is bigger than 0 it means we have new parts in the newsgroup.
		if ($total > 0) {
			if ($this->echo) {
				if ($groupArr['last_record'] == 0) {
					$this->c->doEcho(
						$this->c->primary(
							'New group ' .
							$data['group'] .
							' starting with ' .
							(($this->NewGroupScanByDays) ? $this->NewGroupDaysToScan
								. ' days' : number_format($this->NewGroupMsgsToScan) .
								' messages'
							) .
							" worth. Leaving " .
							number_format($left) .
							" for next pass.\nServer oldest: " .
							number_format($data['first']) .
							' Server newest: ' .
							number_format($data['last']) .
							' Local newest: ' .
							number_format($groupArr['last_record']), true
						)
					);
				} else {
					$this->c->doEcho(
						$this->c->primary(
							'Group ' .
							$data['group'] .
							' has ' .
							number_format($realtotal) .
							" new articles. Leaving " .
							number_format($left) .
							" for next pass.\nServer oldest: " .
							number_format($data['first']) . ' Server newest: ' .
							number_format($data['last']) .
							' Local newest: ' .
							number_format($groupArr['last_record']), true
						)
					);
				}
			}

			$done = false;
			// Get all the parts (in portions of $this->messagebuffer to not use too much memory).
			while ($done === false) {

				if ($total > $this->messagebuffer) {
					if ($first + $this->messagebuffer > $grouplast) {
						$last = $grouplast;
					} else {
						$last = (int)$first + $this->messagebuffer;
					}
				}
				$first++;

				if ($this->echo) {
					$this->c->doEcho(
						$this->c->header(
							"\nGetting " .
							number_format($last - $first + 1) .
							' articles (' . number_format($first) .
							' to ' .
							number_format($last) .
							') from ' .
							str_replace('alt.binaries', 'a.b', $data['group']) .
							" - (" .
							number_format($grouplast - $last) .
							" articles in queue)."
						)
					);
				}
				flush();

				// Get article headers from newsgroup. Let scan deal with nntp connection, else compression fails after first grab
				$scanSummary = $this->scan($groupArr, $first, $last);

				// Scan failed - skip group.
				if ($scanSummary == false) {
					return;
				}

				// If new group, update first record & postdate
				if (is_null($groupArr['first_record_postdate']) && $groupArr['first_record'] == '0') {
					$groupArr['first_record'] = $scanSummary['firstArticleNumber'];

					if (isset($scanSummary['firstArticleDate'])) {
						$first_record_postdate = strtotime($scanSummary['firstArticleDate']);
					} else {
						$first_record_postdate = $this->backfill->postdate($groupArr['first_record'], $data);
					}

					$groupArr['first_record_postdate'] = $first_record_postdate;

					$this->db->queryExec(
						sprintf('
							UPDATE groups
							SET first_record = %s, first_record_postdate = %s
							WHERE id = %d',
							$scanSummary['firstArticleNumber'],
							$this->db->from_unixtime($this->db->escapeString($first_record_postdate)),
							$groupArr['id']
						)
					);
				}

				if (isset($scanSummary['lastArticleDate'])) {
					$last_record_postdate = strtotime($scanSummary['lastArticleDate']);
				} else {
					$last_record_postdate = $this->backfill->postdate($scanSummary['lastArticleNumber'], $data);
				}

				$this->db->queryExec(
					sprintf('
						UPDATE groups
						SET last_record = %s, last_record_postdate = %s, last_updated = NOW()
						WHERE id = %d',
						$this->db->escapeString($scanSummary['lastArticleNumber']),
						$this->db->from_unixtime($last_record_postdate),
						$groupArr['id']
					)
				);

				if ($last == $grouplast) {
					$done = true;
				} else {
					$first = $last;
				}
			}

			$timeGroup = number_format(microtime(true) - $this->startGroup, 2);
			if ($this->echo) {
				$this->c->doEcho(
					$this->c->primary(
						PHP_EOL .
						'Group ' .
						$groupName .
						' processed in ' .
						$timeGroup .
						" seconds."
					), true
				);
			}
		} else {
			if ($this->echo) {
				$this->c->doEcho(
					$this->c->primary(
						'No new articles for ' .
						$groupName .
						' (first ' .
						number_format($first) .
						' last ' .
						number_format($last) .
						' grouplast ' .
						number_format($groupArr['last_record']) .
						' total ' .
						number_format($total) .
						")\n" .
						"Server oldest: " .
						number_format($data['first']) .
						' Server newest: ' .
						number_format($data['last']) .
						' Local newest: ' .
						number_format($groupArr['last_record'])
					), true
				);
			}
		}
	}

	/**
	 * Loop over range of wanted headers, insert headers into DB.
	 *
	 * @param array $groupArr     The group info from mysql.
	 * @param int $first          The oldest wanted header.
	 * @param int $last           The newest wanted header.
	 * @param string $type        Is this partrepair or update?
	 * @param null $missingParts
	 *
	 * @return array|bool
	 */
	public function scan($groupArr, $first, $last, $type = 'update', $missingParts = null)
	{
		// Start time of scan method.
		$this->startLoop = microtime(true);

		// Start time of getting data from usenet.
		$this->startHeaders = $this->startLoop;

		// Empty array, will contain return values.
		$returnArray = array();

		// Check that tables exist, create if they do not
		if ($this->tablepergroup === 1) {
			if ($this->db->newtables($groupArr['id']) === false) {
				$dMessage = "There is a problem creating new parts/files tables for this group.";
				if ($this->debug) {
					$this->debugging->start("scan", $dMessage, 1);
				}
				exit($this->c->error($dMessage));
			}
			$group['cname'] = 'collections_' . $groupArr['id'];
			$group['bname'] = 'binaries_' . $groupArr['id'];
			$group['pname'] = 'parts_' . $groupArr['id'];
		} else {
			$group['cname'] = 'collections';
			$group['bname'] = 'binaries';
			$group['pname'] = 'parts';
		}

		// Download the headers.
		$msgs = $this->nntp->getOverview((int)$first . "-" . (int)$last, true, false);

		// If there were an error, try to reconnect.
		if ($this->nntp->isError($msgs)) {
			// This is usually a compression error, so try disabling compression.
			$this->nntp->doQuit();
			if ($this->nntp->doConnect(false) !== true) {
				return false;
			}

			$this->nntp->selectGroup($groupArr['name']);
			$msgs = $this->nntp->getOverview((int)$first . '-' . (int)$last, true, false);
			if ($this->nntp->isError($msgs)) {
				if ($type !== 'partrepair') {

					$dMessage = "Code {$msgs->code}: {$msgs->message}\nSkipping group: ${groupArr['name']}";
					if ($this->debug) {
						$this->debugging->start("scan", $dMessage, 3);
					}

					if ($this->echo) {
						$this->c->doEcho($this->c->error($dMessage), true);
					}

				// If partrepair, increment attempts.
				} else {

					$query = sprintf(
						'UPDATE partrepair SET attempts = attempts + 1 WHERE groupid = %d AND numberid ',
						$groupArr['id']
					);

					// Check if it's more than 1 article.
					if ($first !== $last) {
						$query .= 'IN (' . implode(',', range($first, $last)) . ')';
					} else {
						$query .= '= ' . $first;
					}

					$this->db->queryExec($query);

				}
				return false;
			}
		}
		// Start of processing headers.
		$this->startCleaning = microtime(true);

		// End of the getting data from usenet.
		$timeHeaders = number_format($this->startCleaning - $this->startHeaders, 2);

		// Array of all the requested article numbers.
		$rangerequested = range($first, $last);

		$msgsreceived = $msgsblacklisted = $msgsignored = $msgsnotinserted = $msgrepaired = array();

		$msgCount = count($msgs);
		if ($msgCount > 0) {

			// Get highest and lowest article numbers/dates.
			$iterator1 = 0;
			$iterator2 = $msgCount-1;
			while (true) {
				if (!isset($returnArray['firstArticleNumber']) && isset($msgs[$iterator1]['Number'])) {
					$returnArray['firstArticleNumber'] = $msgs[$iterator1]['Number'];
					$returnArray['firstArticleDate'] = $msgs[$iterator1]['Date'];
				}

				if (!isset($returnArray['lastArticleNumber']) && isset($msgs[$iterator2]['Number'])) {
					$returnArray['lastArticleNumber'] = $msgs[$iterator2]['Number'];
					$returnArray['lastArticleDate'] = $msgs[$iterator2]['Date'];
				}

				// Break if we found non empty articles.
				if (isset($returnArray['firstArticleNumber']) && isset($returnArray['lastArticleNumber'])) {
					break;
				}

				// Break out if we couldn't find anything.
				if ($iterator1++ >= $msgCount-1 || $iterator2-- <= 0) {
					break;
				}
			}

			// Sort the articles before processing, alphabetically by subject. This is to try to use the shortest subject and those without .vol01 in the subject
			usort($msgs,
				function ($elem1, $elem2) {
					return strcmp($elem1['Subject'], $elem2['Subject']);
				}
			);

			// Loop articles, figure out files/parts.
			foreach ($msgs AS $msg) {
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

					if ($this->showdroppedyencparts === '1' && !isset($UIP[1])) {
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
				$nofiles = false;
				$partless = $matches[1];
				$filecnt = '';
				if (!preg_match('/(\[|\(|\s)(\d{1,5})(\/|(\s|_)of(\s|_)|\-)(\d{1,5})(\]|\)|\s|$|:)/i', $partless, $filecnt)) {
					$filecnt[2] = $filecnt[6] = 0;
					$nofiles = true;

					if ($this->showdroppedyencparts === '1') {
						file_put_contents(nZEDb_RES . "logs" . DS . 'no_parts' . $groupArr['name'] . ".log", $msg['Subject'] . PHP_EOL, FILE_APPEND);
					}
				}

				if (is_numeric($matches[2]) && is_numeric($matches[3])) {

					array_map('trim', $matches);
					// Inserted into the collections table as the subject.
					$subject = utf8_encode(trim($partless));

					// Used for the sha1 hash (see below).
					$cleansubject = utf8_encode($this->collectionsCleaning->collectionsCleaner($subject, $groupArr['name'], $nofiles));

					// Set up the info for inserting into parts/binaries/collections tables.
					if (!isset($this->message[$subject])) {
						$this->message[$subject] = $msg;
						$this->message[$subject]['MaxParts'] = (int) $matches[3];
						$this->message[$subject]['Date'] = strtotime($msg['Date']);
						// (hash) Groups articles together when forming the release/nzb.
						$this->message[$subject]['CollectionHash'] = sha1($cleansubject . $msg['From'] . $groupArr['id'] . $filecnt[6]);
						$this->message[$subject]['MaxFiles'] = (int) $filecnt[6];
						$this->message[$subject]['File'] = (int) $filecnt[2];
					}

					$nowPart = (int) $matches[2];
					if ($this->grabnzbs && stristr($subject, '.nzb"')) {
						$this->db->queryInsert(
							sprintf(
								'INSERT INTO nzbs (message_id, groupname, subject, collectionhash, filesize, partnumber, totalparts, postdate, dateadded)
								VALUES (%s, %s, %s, %s, %d, %d, %d, %s, NOW()) ON DUPLICATE KEY UPDATE dateadded = NOW()',
								// Remove < and > from Message-ID.
								$this->db->escapeString(substr($msg['Message-ID'], 1, -1)),
								$this->db->escapeString($groupArr['name']),
								// Cut the subject if it's too long.
								$this->db->escapeString(substr($subject, 0, 255)),
								$this->db->escapeString($this->message[$subject]['CollectionHash']),
								(int) $bytes,
								$nowPart,
								$this->message[$subject]['MaxParts'],
								$this->db->from_unixtime($this->message[$subject]['Date'])
							)
						);
					}

					if ($nowPart > 0) {
						$this->message[$subject]['Parts'][$nowPart] =
							array(
								'Message-ID' => substr($msg['Message-ID'], 1, -1),
								'number' => $msg['Number'],
								'part' => $nowPart,
								'size' => $bytes
							);
					}
				}
			}

			unset($msg, $msgs);
			$maxnum = $last;
			$rangenotreceived = array_diff($rangerequested, $msgsreceived);

			if ($this->echo && $type != 'partrepair') {
				$this->c->doEcho(
					$this->c->primary(
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
						if ($this->DoPartRepair) {
							$this->addMissingParts($rangenotreceived, $groupArr['id']);
						}
						break;
				}

				if ($this->echo && $type != 'partrepair') {
					$this->c->doEcho(
						$this->c->alternate(
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
			$this->startUpdate = microtime(true);

			// End of processing headers.
			$timeCleaning = number_format($this->startUpdate - $this->startCleaning, 2);


			if (isset($this->message) && count($this->message) > 0) {
				$maxnum = $first;
				$pBinaryID = $pNumber = $pMessageID = $pPartNumber = $pSize = 1;
				// Insert collections, binaries and parts into database. When collection exists, only insert new binaries, when binary already exists, only insert new parts.
				$insPartsStmt = $this->db->Prepare("INSERT INTO ${group['pname']} (binaryid, number, messageid, partnumber, size) VALUES (?, ?, ?, ?, ?)");
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

						$this->db->beginTransaction();

						$collectionHash = $data['CollectionHash'];

						// Check if the last collection hash is the same.
						if ($lastCollectionHash == $collectionHash && $lastCollectionID !== -1) {
							$collectionID = $lastCollectionID;
						} else {
							$lastCollectionHash = $collectionHash;
							$lastBinaryHash = '';
							$lastBinaryID = -1;

							$cres = $this->db->queryOneRow(sprintf("SELECT id, subject FROM ${group['cname']} WHERE collectionhash = %s", $this->db->escapeString($collectionHash)));
							if ($cres && array_key_exists($collectionHash, $collectionHashes)) {
								$collectionID = $collectionHashes[$collectionHash];
								if (preg_match('/\.vol\d+/i', $subject) && !preg_match('/\.vol\d+/i', $cres['subject'])) {
									$this->db->queryExec(sprintf("UPDATE ${group['cname']} SET subject = %s WHERE id = %s", $this->db->escapeString(substr($subject, 0, 255)), $collectionID));
								}
							} else {
								if (!$cres) {
									// added utf8_encode on fromname, seems some foreign groups contains characters that were not escaping properly
									$csql = sprintf("INSERT INTO ${group['cname']} (subject, fromname, date, xref, groupid, totalfiles, collectionhash, dateadded) VALUES (%s, %s, %s, %s, %d, %d, %s, NOW())", $this->db->escapeString(substr($subject, 0, 255)), $this->db->escapeString(utf8_encode($data['From'])), $this->db->from_unixtime($data['Date']), $this->db->escapeString(substr($data['Xref'], 0, 255)), $groupArr['id'], $data['MaxFiles'], $this->db->escapeString($collectionHash));
									$collectionID = $this->db->queryInsert($csql);
								} else {
									$collectionID = $cres['id'];
									//Update the collection table with the last seen date for the collection. This way we know when the last time a person posted for this hash.
									if (preg_match('/\.vol\d+/i', $subject) && !preg_match('/\.vol\d+/i', $cres['subject'])) {
										$this->db->queryExec(sprintf("UPDATE ${group['cname']} SET subject = %s WHERE id = %s", $this->db->escapeString(substr($subject, 0, 255)), $collectionID));
									} else {
										$this->db->queryExec(sprintf("UPDATE ${group['cname']} SET dateadded = NOW() WHERE id = %s", $collectionID));
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

								$bres = $this->db->queryOneRow(
									sprintf("SELECT id FROM ${group['bname']} WHERE binaryhash = %s",
										$this->db->escapeString($binaryHash)
									)
								);
								if (!$bres) {
									$bsql = sprintf(
										"INSERT INTO ${group['bname']} (binaryhash, name, collectionid, totalparts, filenumber)
										VALUES (%s, %s, %d, %s, %s)",
										$this->db->escapeString($binaryHash),
										$this->db->escapeString($subject),
										$collectionID,
										$this->db->escapeString($data['MaxParts']),
										$this->db->escapeString(round($data['File'])
										)
									);
									$binaryID = $this->db->queryInsert($bsql);
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
									if ($this->debug) {
										$this->debugging->start("scan", $e->getMessage(), 3);
									}
									continue;
								}
							}
						}
						$this->db->Commit();
					}
				}
				$notInsertedCount = count($msgsnotinserted);
				if ($notInsertedCount > 0) {
					$dMessage = $notInsertedCount . " parts failed to insert.";
					if ($this->debug) {
						$this->debugging->start("scan", $dMessage, 3);
					}

					if ($this->echo) {
						$this->c->doEcho($this->c->warning($dMessage), true);
					}

					if ($this->DoPartRepair) {
						$this->addMissingParts($msgsnotinserted, $groupArr['id']);
					}
				}
			}

			$currentMicroTime = microtime(true);
			if ($this->echo && $type != 'partrepair') {
				$this->c->doEcho(
					$this->c->alternateOver($timeHeaders . 's') .
					$this->c->primaryOver(' to download articles, ') .
					$this->c->alternateOver($timeCleaning . 's') .
					$this->c->primaryOver(' to process articles, ') .
					$this->c->alternateOver(number_format($currentMicroTime - $this->startUpdate, 2) . 's') .
					$this->c->primaryOver(' to insert articles, ') .
					$this->c->alternateOver(number_format($currentMicroTime - $this->startLoop, 2) . 's') .
					$this->c->primary(' total.')
				);
			}

			unset($this->message, $data);
			return $returnArray;
		} else {
			if ($type != 'partrepair') {
				$dMessage = "Can't get parts from server (msgs not array).\nSkipping group: ${groupArr['name']}";
				if ($this->debug) {
					$this->debugging->start("scan", $dMessage, 3);
				}

				if ($this->echo) {
					$this->c->doEcho($this->c->error($dMessage), true);
				}
			}
		}
		return $returnArray;
	}

	/**
	 * Attempt to get missing headers.
	 *
	 * @param array        $groupArr The info for this group from mysql.
	 *
	 * @return void
	 */
	public function partRepair($groupArr)
	{
		// Check that tables exist, create if they do not
		if ($this->tablepergroup === 1) {
			if ($this->db->newtables($groupArr['id']) === false) {
				$dMessage = "There is a problem creating new parts/files tables for this group.";
				if ($this->debug) {
					$this->debugging->start("partRepair", $dMessage, 1);
				}
				exit($this->c->error($dMessage));
			}
			$group['prname'] = 'partrepair_' . $groupArr['id'];
		} else {
			$group['prname'] = 'partrepair';
		}

		// Get all parts in partrepair table.
		$missingParts = $this->db->query(
			sprintf(
				'SELECT * FROM ' . $group['prname'] . '
				WHERE groupid = %d AND attempts < 5
				ORDER BY numberid ASC LIMIT %d',
				$groupArr['id'],
				$this->partrepairlimit
			)
		);
		$partsRepaired = 0;

		$missingCount = count($missingParts);
		if ($missingCount > 0) {
			if ($this->echo) {
				$this->consoleTools->overWritePrimary(
					'Attempting to repair ' .
					number_format(count($missingParts)) .
					" parts."
				);
			}

			// Loop through each part to group into continuous ranges with a maximum range of messagebuffer/4.
			$ranges = array();
			$partlist = array();
			$firstpart = $lastnum = $missingParts[0]['numberid'];
			foreach ($missingParts as $part) {
				if (($part['numberid'] - $firstpart) > ($this->messagebuffer / 4)) {
					$ranges[] = array('partfrom' => $firstpart, 'partto' => $lastnum, 'partlist' => $partlist);
					$firstpart = $part['numberid'];
					$partlist = array();
				}
				$partlist[] = $part['numberid'];
				$lastnum = $part['numberid'];
			}
			$ranges[] = array('partfrom' => $firstpart, 'partto' => $lastnum, 'partlist' => $partlist);

			$num_attempted = 0;

			// Download missing parts in ranges.
			foreach ($ranges as $range) {

				$partfrom = $range['partfrom'];
				$partto = $range['partto'];
				$partlist = $range['partlist'];
				$count = count($range['partlist']);

				$num_attempted += $count;
				$this->consoleTools->overWritePrimary(
					"Attempting repair: " .
					$this->consoleTools->percentString2($num_attempted - $count + 1, $num_attempted, count($missingParts)) .
					': ' . $partfrom . ' to ' . $partto . ' .'
				);

				// Get article from newsgroup.
				$this->scan($groupArr, $partfrom, $partto, 'partrepair', $partlist);
			}

			// Calculate parts repaired
			$result = $this->db->queryOneRow(
				sprintf(
					'SELECT COUNT(id) AS num
					FROM ' . $group['prname'] . '
					WHERE groupid=%d
					AND numberid <= %d',
					$groupArr['id'],
					$missingParts[count($missingParts) - 1]['numberid']
				)
			);
			if (isset($result['num'])) {
				$partsRepaired = (count($missingParts)) - $result['num'];
			}

			// Update attempts on remaining parts for active group
			if (isset($missingParts[count($missingParts) - 1]['id'])) {
				$this->db->queryExec(
					sprintf(
						"UPDATE ${group['prname']}
						SET attempts=attempts+1
						WHERE groupid=%d
						AND numberid <= %d",
						$groupArr['id'],
						$missingParts[count($missingParts) - 1]['numberid']
					)
				);
			}

			if ($this->echo) {
				$this->c->doEcho(
					$this->c->primary(
						"\n" .
						number_format($partsRepaired) .
						" parts repaired."
					), true
				);
			}
		}

		// Remove articles that we cant fetch after 5 attempts.
		$this->db->queryExec(sprintf('DELETE FROM ' . $group['prname'] . ' WHERE attempts >= 5 AND groupid = %d', $groupArr['id']));
	}

	/**
	 * Add missing headers to DB.
	 *
	 * @param array $numbers The article numbers of the missing headers.
	 * @param int $groupID   The ID of this groups.
	 *
	 * @return bool
	 */
	private function addMissingParts($numbers, $groupID)
	{
		// Check that tables exist, create if they do not
		if ($this->tablepergroup === 1) {
			if ($this->db->newtables($groupID) === false) {
				$dMessage = "There is a problem creating new parts/files tables for this group.";
				if ($this->debug) {
					$this->debugging->start("addMissingParts", $dMessage, 1);
				}
				exit($this->c->error($dMessage));
			}
			$group['prname'] = 'partrepair_' . $groupID;
		} else {
			$group['prname'] = 'partrepair';
		}

		$insertStr = "INSERT INTO ${group['prname']} (numberid, groupid) VALUES ";
		foreach ($numbers as $number) {
			$insertStr .= sprintf('(%d, %d), ', $number, $groupID);
		}

		$insertStr = substr($insertStr, 0, -2);
		if ($this->db->dbSystem() === 'mysql') {
			$insertStr .= ' ON DUPLICATE KEY UPDATE attempts=attempts+1';
			return $this->db->queryInsert($insertStr);
		} else {
			$id = $this->db->queryInsert($insertStr);
			$this->db->Exec('UPDATE partrepair SET attempts = attempts+1 WHERE id = ' . $id);
			return $id;
		}
	}

	/**
	 * Clean up part repair table.
	 *
	 * @param array $numbers The article numbers.
	 * @param int$groupID     The ID of the group.
	 *
	 * @return void
	 */
	private function removeRepairedParts($numbers, $groupID)
	{
		if ($this->tablepergroup === 1) {
			$group['prname'] = 'partrepair_' . $groupID;
		} else {
			$group['prname'] = 'partrepair';
		}

		$sql = 'DELETE FROM ' . $group['prname'] . ' WHERE numberid in (';
		foreach ($numbers as $number) {
			$sql .= sprintf('%d, ', $number);
		}
		$sql = substr($sql, 0, -2);
		$sql .= sprintf(') AND groupid = %d', $groupID);
		$this->db->queryExec($sql);
	}

	/**
	 * Get blacklist cache.
	 *
	 * @return array
	 */
	public function retrieveBlackList()
	{
		if ($this->blackListLoaded) {
			return $this->blackList;
		}
		$blackList = $this->getBlacklist(true);
		$this->blackList = $blackList;
		$this->blackListLoaded = true;
		return $blackList;
	}

	/**
	 * Check if article is blacklisted.
	 *
	 * @param array $msg        The article header.
	 * @param string $groupName The group.
	 *
	 * @return bool
	 */
	public function isBlackListed($msg, $groupName)
	{
		$blackList = $this->retrieveBlackList();
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

		foreach ($blackList as $blist) {
			if (preg_match('/^' . $blist['groupname'] . '$/i', $groupName)) {
				//blacklist
				if ($blist['optype'] == 1) {
					if (preg_match('/' . $blist['regex'] . '/i', $field[$blist['msgcol']])) {
						$omitBinary = true;
					}
				} else if ($blist['optype'] == 2) {
					if (!preg_match('/' . $blist['regex'] . '/i', $field[$blist['msgcol']])) {
						$omitBinary = true;
					}
				}
			}
		}

		return $omitBinary;
	}

	/**
	 * @param $search
	 * @param int $limit
	 * @param array $excludedcats
	 *
	 * @return array
	 */
	public function search($search, $limit = 1000, $excludedcats = array())
	{
		/* If the query starts with a ^ it indicates the search is looking for items which start with the term still do
		 * the like match, but mandate that all items returned must start with the provided word.
		 */
		$words = explode(' ', $search);
		$searchsql = '';
		$intwordcount = 0;
		if (count($words) > 0) {
			$like = 'ILIKE';
			if ($this->db->dbSystem() === 'mysql') {
				$like = 'LIKE';
			}
			foreach ($words as $word) {
				// See if the first word had a caret, which indicates search must start with term.
				if ($intwordcount == 0 && (strpos($word, '^') === 0)) {
					$searchsql.= sprintf(' AND b.name %s %s', $like, $this->db->escapeString(substr($word, 1) . '%'));
				} else {
					$searchsql.= sprintf(' AND b.name %s %s', $like, $this->db->escapeString('%' . $word . '%'));
				}

				$intwordcount++;
			}
		}

		$exccatlist = '';
		if (count($excludedcats) > 0) {
			$exccatlist = ' AND b.categoryid NOT IN (' . implode(',', $excludedcats) . ') ';
		}

		return $this->db->query(
			sprintf("
				SELECT b.*, g.name AS group_name, r.guid,
				(SELECT COUNT(id) FROM parts p WHERE p.binaryid = b.id) as 'binnum'
				FROM binaries b
				INNER JOIN groups g ON g.id = b.groupid
				LEFT OUTER JOIN releases r ON r.id = b.releaseid
				WHERE 1=1 %s %s
				order by DATE DESC LIMIT %d", $searchsql, $exccatlist, $limit
			)
		);
	}

	/**
	 * @param $id
	 *
	 * @return array
	 */
	public function getForReleaseId($id)
	{
		return $this->db->query(
			sprintf(
				'SELECT binaries.*
				FROM binaries
				WHERE releaseid = %d
				ORDER BY relpart', $id
			)
		);
	}

	/**
	 * @param $id
	 *
	 * @return array|bool
	 */
	public function getById($id)
	{
		return $this->db->queryOneRow(
			sprintf(
				'SELECT binaries.*, collections.groupid, groups.name AS groupname
				FROM binaries, collections
				LEFT OUTER JOIN groups ON collections.groupid = groups.id
				WHERE binaries.id = %d', $id
			)
		);
	}

	/**
	 * @param bool $activeonly
	 *
	 * @return array
	 */
	public function getBlacklist($activeonly = true)
	{
		$where = '';
		if ($activeonly) {
			$where = ' WHERE binaryblacklist.status = 1 ';
		}

		return $this->db->query(
			'SELECT binaryblacklist.id, binaryblacklist.optype, binaryblacklist.status, binaryblacklist.description,
			binaryblacklist.groupname AS groupname, binaryblacklist.regex, groups.id AS groupid, binaryblacklist.msgcol
			FROM binaryblacklist LEFT OUTER JOIN groups ON groups.name = binaryblacklist.groupname ' . $where . "
			ORDER BY coalesce(groupname,'zzz')"
		);
	}

	/**
	 * @param $id
	 *
	 * @return array|bool
	 */
	public function getBlacklistByID($id)
	{
		return $this->db->queryOneRow(sprintf('SELECT * FROM binaryblacklist WHERE id = %d', $id));
	}

	/**
	 * @param $id
	 *
	 * @return bool
	 */
	public function deleteBlacklist($id)
	{
		return $this->db->queryExec(sprintf('DELETE FROM binaryblacklist WHERE id = %d', $id));
	}

	/**
	 * @param $regex
	 *
	 * @return void
	 */
	public function updateBlacklist($regex)
	{
		$groupname = $regex['groupname'];
		if ($groupname == '') {
			$groupname = 'null';
		} else {
			$groupname = preg_replace('/a\.b\./i', 'alt.binaries.', $groupname);
			$groupname = sprintf('%s', $this->db->escapeString($groupname));
		}

		$this->db->queryExec(
			sprintf('
				UPDATE binaryblacklist
				SET groupname = %s, regex = %s, status = %d, description = %s, optype = %d, msgcol = %d
				WHERE id = %d ',
				$groupname,
				$this->db->escapeString($regex['regex']), $regex['status'],
				$this->db->escapeString($regex['description']),
				$regex['optype'],
				$regex['msgcol'],
				$regex['id']
			)
		);
	}

	/**
	 * @param $regex
	 *
	 * @return bool
	 */
	public function addBlacklist($regex)
	{
		$groupname = $regex['groupname'];
		if ($groupname == '') {
			$groupname = 'null';
		} else {
			$groupname = preg_replace('/a\.b\./i', 'alt.binaries.', $groupname);
			$groupname = sprintf('%s', $this->db->escapeString($groupname));
		}

		return $this->db->queryInsert(
			sprintf('
				INSERT INTO binaryblacklist (groupname, regex, status, description, optype, msgcol)
				VALUES (%s, %s, %d, %s, %d, %d)',
				$groupname,
				$this->db->escapeString($regex['regex']),
				$regex['status'],
				$this->db->escapeString($regex['description']),
				$regex['optype'],
				$regex['msgcol']
			)
		);
	}

	/**
	 * @param $id
	 *
	 * @return void
	 */
	public function delete($id)
	{
		$bins = $this->db->query(sprintf('SELECT id FROM binaries WHERE collectionid = %d', $id));
		foreach ($bins as $bin) {
			$this->db->queryExec(sprintf('DELETE FROM parts WHERE binaryid = %d', $bin['id']));
		}
		$this->db->queryExec(sprintf('DELETE FROM binaries WHERE collectionid = %d', $id));
		$this->db->queryExec(sprintf('DELETE FROM collections WHERE id = %d', $id));
	}

}