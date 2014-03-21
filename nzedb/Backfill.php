<?php

class Backfill
{
	/**
	 * Instance of class ColorCLI.
	 *
	 * @var object
	 */
	private $c;

	/**
	 * Should we use compression for headers?
	 *
	 * @var bool
	 */
	private $compressedHeaders;

	/**
	 * Instance of class DB
	 *
	 * @var object
	 */
	private $db;

	/**
	 * Instance of class debugging.
	 *
	 * @var object
	 */
	private $debugging;

	/**
	 * Do we need to reset the collection hashes?
	 *
	 * @var int
	 */
	private $hashcheck;

	/**
	 * Are we using nntpproxy?
	 *
	 * @var int
	 */
	private $nntpproxy;

	/**
	 * How far back should we go on safebackfill?
	 *
	 * @var string
	 */
	private $safebdate;

	/**
	 * @var int
	 */
	private $safepartrepair;

	/**
	 * Should we use tpg?
	 *
	 * @var int
	 */
	private $tablepergroup;

	/**
	 * Echo to cli?
	 * @var bool
	 */
	protected $echo;

	/**
	 * Log and or echo debug.
	 * @var bool
	 */
	protected $debug = false;

	/**
	 * @var NNTP
	 */
	protected $nntp;

	/**
	 * @var Binaries
	 */
	protected $binaries;

	/**
	 * @var int
	 */
	protected $startGroup;

	/**
	 * @var int
	 */
	protected $startLoop;

	/**
	 * Constructor.
	 *
	 * @param null $site Object with site settings.
	 * @param bool $echo Echo to cli?
	 */
	public function __construct($site = null, $echo = true)
	{
		$this->echo = ($echo && nZEDb_ECHOCLI);
		$this->c = new ColorCLI();
		$this->db = new DB();
		$this->debug = (nZEDb_LOGGING || nZEDb_DEBUG);
		if ($this->debug) {
			$this->debugging = new Debugging("Backfill");
		}

		if (!isset($site)) {
			$s = new Sites();
			$site = $s->get();
		}

		$this->compressedHeaders = ($site->compressedheaders == '1') ? true : false;
		$this->hashcheck = (!empty($site->hashcheck)) ? $site->hashcheck : 0;
		$this->nntpproxy = (isset($site->nntpproxy)) ? $site->nntpproxy : 0;
		$this->safebdate = (!empty($site->safebackfilldate)) ? $site->safebackfilldate : '2012 - 06 - 24';
		$this->safepartrepair = (!empty($site->safepartrepair)) ? $site->safepartrepair : 0;
		$this->tablepergroup = (isset($site->tablepergroup)) ? $site->tablepergroup : 0;

		// Deprecated?
		$this->primary = 'Green';
		$this->warning = 'Red';
		$this->header = 'Yellow';
	}

	/**
	 * Backfill all the groups up to user specified time/date.
	 *
	 * @param object $nntp
	 * @param string $groupName
	 *
	 * @return void
	 */
	public function backfillAllGroups($nntp, $groupName = '')
	{
		if (!isset($nntp)) {
			$dmessage = "Not connected to usenet(backfill->backfillAllGroups).\n";
			if ($this->debug) {
				$this->debugging->start("backfillAllGroups", $dmessage, 1);
			}
			exit($this->c->error($dmessage));
		}

		if ($this->hashcheck == 0) {
			$dmessage = "You must run update_binaries.php to update your collectionhash.";
			if ($this->debug) {
				$this->debugging->start("backfillAllGroups", $dmessage, 1);
			}
			exit($this->c->error($dmessage));
		}

		$this->nntp = $nntp;
		$groups = new Groups();

		$res = array();
		if ($groupName != '') {
			$grp = $groups->getByName($groupName);
			if ($grp) {
				$res = array($grp);
			}
		} else {
			$res = $groups->getActiveBackfill();
		}

		$groupCount = count($res);
		if ($groupCount > 0) {
			$counter = 1;
			$this->binaries = new Binaries($this->echo);
			foreach ($res as $groupArr) {
				if ($groupName === '') {
					$dmessage = "Starting group " . $counter . ' of ' . $groupCount;
					if ($this->debug) {
						$this->debugging->start("backfillAllGroups", $dmessage, 5);
					}

					if ($this->echo) {
						$this->c->doEcho($this->c->set256($this->header) .$dmessage . $this->c->rsetColor(), true);
					}
				}
				$this->backfillGroup($groupArr, $groupCount - $counter);
				$counter++;
			}
		} else {
			$dmessage = "No groups specified. Ensure groups are added to nZEDb's database for updating.";
			if ($this->debug) {
				$this->debugging->start("backfillAllGroups", $dmessage, 1);
			}

			if ($this->echo) {
				$this->c->doEcho($this->c->set256($this->primary) . $dmessage . $this->c->rsetColor(), true);
			}
		}
	}

	/**
	 * Backfill single group.
	 *
	 * @param array $groupArr
	 * @param int $left
	 *
	 * @return void
	 */
	public function backfillGroup($groupArr, $left)
	{
		$this->startGroup = microtime(true);

		// Select group, here, only once
		$data = $this->nntp->selectGroup($groupArr['name']);
		if ($this->nntp->isError($data)) {
			$data = $this->nntp->dataError($this->nntp, $groupArr['name']);
			if ($this->nntp->isError($data)) {
				return;
			}
		}

		// Get targetpost based on days target.
		$targetpost = $this->daytopost($this->nntp, $groupArr['name'], $groupArr['backfill_target'], $data);
		if ($targetpost < 0) {
			$targetpost = round($data['first']);
		}

		if ($groupArr['first_record'] == 0 || $groupArr['backfill_target'] == 0) {
			$dmessage = "Group ${groupArr['name']} has invalid numbers. Have you run update on it? Have you set the backfill days amount?";
			if ($this->debug) {
				$this->debugging->start("backfillGroup", $dmessage, 3);
			}

			if ($this->echo) {
				$this->c->doEcho($this->c->warning($dmessage), true);
			}
			return;
		}

		// Check if we are grabbing further than the server has.
		if ($groupArr['first_record'] <= ($data['first'] + 50000)) {
			$dmessage = "We have hit the maximum we can backfill for " . str_replace('alt.binaries', 'a.b', $groupArr['name']) . ", skipping it.";
			if ($this->debug) {
				$this->debugging->start("backfillGroup", $dmessage, 4);
			}

			if ($this->echo) {
				$this->c->doEcho($this->c->notice($dmessage), true);
			}
			return;
		}

		// If our estimate comes back with stuff we already have, finish.
		if ($targetpost >= $groupArr['first_record']) {
			$dmessage = "Nothing to do, we already have the target post.";
			if ($this->debug) {
				$this->debugging->start("backfillGroup", $dmessage, 4);
			}

			if ($this->echo) {
				$this->c->doEcho($this->c->notice($dmessage), true);
			}
			return;
		}

		if ($this->echo) {
			$this->c->doEcho(
				$this->c->set256($this->primary) .
				'Group ' . $data['group'] .
				"'s oldest article is " .
				number_format($data['first']) .
				', newest is ' .
				number_format($data['last']) .
				".\nOur target article is " .

				number_format($targetpost) .
				'. Our oldest article is article ' .
				number_format($groupArr['first_record']) .
				'.' .
				$this->c->rsetColor()
			);
		}

		$done = false;
		// Set first and last, moving the window by maxxMssgs.
		$last = $groupArr['first_record'] - 1;
		// Set the initial "chunk".
		$first = $last - $this->binaries->messagebuffer + 1;

		// Just in case this is the last chunk we needed.
		if ($targetpost > $first) {
			$first = $targetpost;
		}

		while ($done === false) {
			$this->binaries->startLoop = microtime(true);

			if ($this->echo) {
				$this->c->doEcho(
					$this->c->set256($this->header) .
					'Getting ' .
					(number_format($last - $first + 1)) .
					" articles from " .
					str_replace('alt.binaries', 'a.b', $data['group']) .
					", " . $left .
					" group(s) left. (" .
					(number_format($first - $targetpost)) .
					" articles in queue)." .
					$this->c->rsetColor(), true
				);
			}

			flush();
			$process = $this->safepartrepair ? 'update' : 'backfill';
			$lastMsg = $this->binaries->scan($this->nntp, $groupArr, $first, $last, $process);

			// Get the oldest date.
			if (isset($lastMsg['firstArticleDate'])) {
				// Try to get it from the oldest pulled article.
				$newdate = strtotime($lastMsg['firstArticleDate']);
			} else {
				// If above failed, try to get it with postdate method.
				$newdate = $this->postdate($this->nntp, $first, false, $groupArr['name'], true, 'oldest');

				if ($newdate === false) {
					// If above failed, try to get the old date, and if that fails set the current date.
					if (is_null($groupArr['first_record_postdate']) || $groupArr['first_record_postdate'] == 'NULL') {
						$newdate = time();
					} else {
						$newdate = strtotime($groupArr['first_record_postdate']);
					}
				}
			}

			$this->db->queryExec(sprintf('UPDATE groups SET first_record_postdate = %s, first_record = %s, last_updated = NOW() WHERE id = %d', $this->db->from_unixtime($newdate), $this->db->escapeString($first), $groupArr['id']));
			if ($first == $targetpost) {
				$done = true;
			} else {
				// Keep going: set new last, new first, check for last chunk.
				$last = $first - 1;
				$first = $last - $this->binaries->messagebuffer + 1;
				if ($targetpost > $first) {
					$first = $targetpost;
				}
			}
		}

		$timeGroup = number_format(microtime(true) - $this->startGroup, 2);

		if ($this->echo) {
			$this->c->doEcho(
				$this->c->set256($this->primary) .
				'Group processed in ' .
				$timeGroup .
				" seconds." .
				$this->c->rsetColor(), true
			);
		}
	}

	/**
	 * Safe backfill using posts. Going back to a date specified by the user on the site settings.
	 * This does 1 group for x amount of parts until it reaches the date.
	 * @param object $nntp
	 * @param string $articles
	 *
	 * @return void
	 */
	public function safeBackfill($nntp, $articles = '')
	{
		if (!isset($nntp)) {
			$dmessage = "Not connected to usenet(backfill->safeBackfill).\n";
			if ($this->debug) {
				$this->debugging->start("safeBackfill", $dmessage, 1);
			}
			exit($this->c->error($dmessage));
		}

		if ($this->hashcheck == 0) {
			$dmessage = "You must run update_binaries.php to update your collectionhash.\n";
			if ($this->debug) {
				$this->debugging->start("safeBackfill", $dmessage, 1);
			}
			exit($dmessage);
		}

		$groupname = $this->db->queryOneRow(
			sprintf('
				SELECT name FROM groups
				WHERE first_record_postdate BETWEEN %s AND NOW()
				AND backfill = 1
				ORDER BY name ASC',
				$this->db->escapeString($this->safebdate)
			)
		);

		if (!$groupname) {
			$dmessage =
				'No groups to backfill, they are all at the target date ' .
				$this->safebdate .
				", or you have not enabled them to be backfilled in the groups page.\n";
			if ($this->debug) {
				$this->debugging->start("safeBackfill", $dmessage, 1);
			}
			exit($dmessage);
		} else {
			$this->backfillPostAllGroups($nntp, $groupname['name'], $articles, $type = '');
		}
	}

	/**
	 * Backfill groups using user specified article count.
	 *
	 * @param object $nntp
	 * @param string $groupName
	 * @param string $articles
	 * @param string $type
	 *
	 * @return void
	 */
	public function backfillPostAllGroups($nntp, $groupName = '', $articles = '', $type = '')
	{
		if (!isset($nntp)) {
			$dmessage = "Not connected to usenet(backfill->backfillPostAllGroups).\n";
			if ($this->debug) {
				$this->debugging->start("backfillPostAllGroups", $dmessage, 1);
			}
			exit($this->c->error($dmessage));
		}

		if ($this->hashcheck == 0) {
			$dmessage = "You must run update_binaries.php to update your collectionhash.";
			if ($this->debug) {
				$this->debugging->start("backfillPostAllGroups", $dmessage, 1);
			}
			exit($this->c->error($dmessage));
		}

		$this->nntp = $nntp;

		$res = array();
		$groups = new Groups();
		if ($groupName != '') {
			$grp = $groups->getByName($groupName);
			if ($grp) {
				$res = array($grp);
			}
		} else {
			if ($type == 'normal') {
				$res = $groups->getActiveBackfill();
			} else if ($type == 'date') {
				$res = $groups->getActiveByDateBackfill();
			}
		}

		if (!is_numeric($articles)) {
			$articles = 20000;
		}

		$groupCount = count($res);
		if ($groupCount > 0) {
			$counter = 1;
			$this->binaries = new Binaries($this->echo);
			foreach ($res as $groupArr) {
				if ($groupName === '') {
					$dmessage =  "\nStarting group " . $counter . ' of ' . $groupCount;
					if ($this->debug) {
						$this->debugging->start("backfillPostAllGroups", $dmessage, 5);
					}

					if ($this->echo) {
						$this->c->doEcho($this->c->set256($this->header) . $dmessage . $this->c->rsetColor(), true);
					}
				}
				$this->backfillPostGroup($groupArr, $groupCount - $counter, $articles);
				$counter++;
			}
		} else {
			$dmessage = "No groups specified. Ensure groups are added to nZEDb's database for updating.";
			if ($this->debug) {
				$this->debugging->start("backfillPostAllGroups", $dmessage, 3);
			}

			if ($this->echo) {
				$this->c->doEcho($this->c->warning($dmessage), true);
			}
		}
	}

	/**
	 * @param array $groupArr
	 * @param int $left
	 * @param int $articles
	 *
	 * @return void
	 */
	public function backfillPostGroup($groupArr, $left, $articles = 20000)
	{
		$this->startGroup = microtime(true);

		if ($this->echo) {
			$this->c->doEcho(
				$this->c->set256($this->header) .
				'Processing ' .
				$groupArr['name'] .
				$this->c->rsetColor()
				, true
			);
		}

		// Select group, here, only once
		$data = $this->nntp->selectGroup($groupArr['name']);
		if ($this->nntp->isError($data)) {
			$data = $this->nntp->dataError($this->nntp, $groupArr['name']);
			if ($this->nntp->isError($data)) {
				return;
			}
		}

		// Get targetpost based on days target.
		$targetpost = round($groupArr['first_record'] - $articles);
		if ($targetpost < 0) {
			$targetpost = round($data['first']);
		}

		if ($groupArr['first_record'] <= 0 || $targetpost <= 0) {
			$dmessage =
				"You need to run update_binaries on " .
				str_replace('alt.binaries', 'a.b', $data['group']) .
				". Otherwise the group is dead, you must disable it.";
			if ($this->debug) {
				$this->debugging->start("backfillPostGroup", $dmessage, 2);
			}

			if ($this->echo) {
				$this->c->doEcho($this->c->error($dmessage), true);
			}
			return;
		}

		// Check if we are grabbing further than the server has.
		if ($groupArr['first_record'] <= $data['first'] + $articles) {
			$dmessage =
				"We have hit the maximum we can backfill for " .
				str_replace('alt.binaries', 'a.b', $groupArr['name']) .
				", skipping it.";
			if ($this->debug) {
				$this->debugging->start("backfillPostGroup", $dmessage, 4);
			}

			if ($this->echo) {
				$this->c->doEcho($this->c->notice($dmessage), true);
			}
			return;
		}

		// If our estimate comes back with stuff we already have, finish.
		if ($targetpost >= $groupArr['first_record']) {
			$dmessage = "Nothing to do, we already have the target post.";
			if ($this->debug) {
				$this->debugging->start("backfillPostGroup", $dmessage, 4);
			}

			if ($this->echo) {
				$this->c->doEcho($this->c->notice($dmessage), true);
			}
			return;
		}

		if ($this->echo) {
			$this->c->doEcho(
				$this->c->set256($this->primary) .
				'Group ' . $data['group'] .
				"'s oldest article is " .
				number_format($data['first']) .
				', newest is ' .
				number_format($data['last']) .
				".\nOur target article is " .

				number_format($targetpost) .
				'. Our oldest article is article ' .
				number_format($groupArr['first_record']) .
				'.' .
				$this->c->rsetColor()
			);
		}

		// Calculate total number of parts.
		$done = false;
		// Set first and last, moving the window by maxxMssgs.
		$last = $groupArr['first_record'] - 1;
		// Set the initial "chunk".
		$first = $last - $this->binaries->messagebuffer + 1;
		// Just in case this is the last chunk we needed.
		if ($targetpost > $first) {
			$first = $targetpost;
		}

		while ($done === false) {
			$this->binaries->startLoop = microtime(true);

			if ($this->echo) {
				$this->c->doEcho(
					$this->c->set256($this->header) .
					"\nGetting " .
					($last - $first + 1) .
					" articles from " .
					str_replace('alt.binaries', 'a.b', str_replace('alt.binaries', 'a.b', $data['group'])) .
					", " .
					$left .
					" group(s) left. (" .
					(number_format($first - $targetpost)) .
					" articles in queue)" .
					$this->c->rsetColor(), true
				);
			}

			flush();
			$process = $this->safepartrepair ? 'update' : 'backfill';
			$lastMsg = $this->binaries->scan($this->nntp, $groupArr, $first, $last, $process);

			// Get the oldest date.
			if (isset($lastMsg['firstArticleDate'])) {
				// Try to get it from the oldest pulled article.
				$newdate = strtotime($lastMsg['firstArticleDate']);
			} else {
				// If above failed, try to get it with postdate method.
				$newdate = $this->postdate($this->nntp, $first, false, $groupArr['name'], true, 'oldest');

				if ($newdate === false) {
					// If above failed, try to get the old date, and if that fails set the current date.
					if (is_null($groupArr['first_record_postdate']) || $groupArr['first_record_postdate'] == 'NULL') {
						$newdate = time();
					} else {
						$newdate = strtotime($groupArr['first_record_postdate']);
					}
				}
			}

			$this->db->queryExec(sprintf('UPDATE groups SET first_record_postdate = %s, first_record = %s, last_updated = NOW() WHERE id = %d', $this->db->from_unixtime($newdate), $this->db->escapeString($first), $groupArr['id']));
			if ($first == $targetpost) {
				$done = true;
			} else {
				// Keep going: set new last, new first, check for last chunk.
				$last = $first - 1;
				$first = $last - $this->binaries->messagebuffer + 1;
				if ($targetpost > $first) {
					$first = $targetpost;
				}
			}
		}

		$timeGroup = number_format(microtime(true) - $this->startGroup, 2);

		if ($this->echo) {
			$this->c->doEcho(
				$this->c->set256($this->header) .
				$data['group'] .
				' processed in ' .
				$timeGroup .
				" seconds." .
				$this->c->rsetColor(), true
			);
		}
	}

	/**
	 * Returns a single timestamp from a local article number.
	 * If the article is missing, you can pass $old as true to return false (then use the last known date).
	 *
	 * @param object $nntp
	 * @param int $post
	 * @param bool $debug
	 * @param string $group
	 * @param bool $old
	 * @param string $type
	 *
	 * @return bool|int
	 */
	public function postdate($nntp, $post, $debug = true, $group, $old = false, $type)
	{
		if (!isset($nntp)) {
			$dmessage = "Not connected to usenet(backfill->postdate).";
			if ($this->debug) {
				$this->debugging->start("postdate", $dmessage, 2);
			}

			if ($this->echo) {
				$this->c->doEcho($this->c->error($dmessage), true);
			}
			return false;
		}

		$keeppost = $post;

		$attempts = $date = 0;
		$success = $record = false;
		do {
			$msgs = $nntp->getOverview($post . "-" . $post, true, false);
			$attempts++;
			if (!$nntp->isError($msgs)) {
				// Set table names
				$groups = new Groups();
				$groupID = $groups->getIDByName($group);
				if ($this->tablepergroup == 1) {
					if ($this->db->newtables($groupID) === false) {
						$dmessage = "There is a problem creating new parts/files tables for this group.";
						if ($this->debug) {
							$this->debugging->start("postdate", $dmessage, 2);
						}

						if ($this->echo) {
							$this->c->doEcho($this->c->error($dmessage), true);
						}
					}
					$groupa = array();
					$groupa['cname'] = 'collections_' . $groupID;
					$groupa['bname'] = 'binaries_' . $groupID;
					$groupa['pname'] = 'parts_' . $groupID;
				} else {
					$groupa = array();
					$groupa['cname'] = 'collections';
					$groupa['bname'] = 'binaries';
					$groupa['pname'] = 'parts';
				}
				if ((!isset($msgs[0]['Date']) || $msgs[0]['Date'] == '' || is_null($msgs[0]['Date'])) && $attempts == 0) {
					$old_post = $post;
					if ($type == 'newest') {
						$res = $this->db->queryOneRow('SELECT p.number AS number FROM ' . $groupa['cname'] . ' c, ' . $groupa['bname'] . ' b, ' . $groupa['pname'] . ' p WHERE c.id = b.collectionid AND b.id = p.binaryid AND c.groupid = ' . $groupID . ' ORDER BY p.number DESC LIMIT 1');
						if (isset($res['number']) && is_numeric($res['number'])) {
							$post = $res['number'];
							$dmessage =
								"Unable to fetch article $old_post from " .
								str_replace('alt.binaries', 'a.b', $group) .
								". Retrying with newest article, from parts table, [$post] from ${groupa['pname']}";
							if ($this->debug) {
								$this->debugging->start("postdate", $dmessage, 4);
							}

							if ($this->echo) {
								$this->c->doEcho($this->c->info($dmessage), true);
							}
						}
					} else {
						$res = $this->db->queryOneRow('SELECT p.number FROM ' . $groupa['cname'] . ' c, ' . $groupa['bname'] . ' b, ' . $groupa['pname'] . ' p WHERE c.id = b.collectionid AND b.id = p.binaryid AND c.groupid = ' . $groupID . ' ORDER BY p.number ASC LIMIT 1');
						if (isset($res['number']) && is_numeric($res['number'])) {
							$post = $res['number'];
							$dmessage =
								"Unable to fetch article $old_post from " .
								str_replace('alt.binaries', 'a.b', $group) .
								". Retrying with oldest article, from parts table, [$post] from ${groupa['pname']}.";
							if ($this->debug) {
								$this->debugging->start("postdate", $dmessage, 5);
							}

							if ($this->echo) {
								$this->c->doEcho($this->c->info($dmessage), true);
							}
						}
					}
					$success = false;
				}
				if ((!isset($msgs[0]['Date']) || $msgs[0]['Date'] == '' || is_null($msgs[0]['Date'])) && $attempts != 0) {
					if ($type == 'newest') {
						$res = $this->db->queryOneRow('SELECT date FROM ' . $groupa['cname'] . ' ORDER BY date DESC LIMIT 1');
						if (isset($res['date'])) {
							$date = $res['date'];
							$dmessage =
								"Unable to fetch article $post from " .
								str_replace('alt.binaries', 'a.b', $group) .
								". Using newest date from ${groupa['cname']}.";
							if ($this->debug) {
								$this->debugging->start("postdate", $dmessage, 5);
							}

							if ($this->echo) {
								$this->c->doEcho($this->c->info($dmessage), true);
							}
							if (strlen($date) > 0) {
								$success = true;
							}
						}
					} else {
						$res = $this->db->queryOneRow('SELECT date FROM ' . $groupa['cname'] . ' ORDER BY date ASC LIMIT 1');
						if (isset($res['date'])) {
							$date = $res['date'];
							$dmessage =
								"Unable to fetch article $post from " .
								str_replace('alt.binaries', 'a.b', $group) .
								". Using oldest date from ${groupa['cname']}.";
							if ($this->debug) {
								$this->debugging->start("postdate", $dmessage, 5);
							}

							if ($this->echo) {
								$this->c->doEcho($this->c->info($dmessage), true);
							}
							if (strlen($date) > 0) {
								$success = true;
							}
						}
					}
				}

				if (isset($msgs[0]['Date']) && $msgs[0]['Date'] != '' && $success === false) {
					$date = $msgs[0]['Date'];
					if (strlen($date) > 0) {
						$success = true;
					}
				}

				if ($debug && $this->echo && $attempts > 0) {
					$this->c->doEcho($this->c->debug('Retried ' . $attempts . " time(s)."), true);
				}
			}
		} while ($attempts <= 20 && $success === false);

		if ($success === false && $old === true) {
			if ($type == 'oldest') {
				$res = $this->db->queryOneRow(sprintf("SELECT first_record_postdate from groups where name = '%s'", $group));
				if (array_key_exists('first_record_postdate', $res)) {
					$dmessage =
						'Unable to fetch article ' .
						$keeppost . ' from ' .
						str_replace('alt.binaries', 'a.b', $group) .
						'. Using current first_record_postdate[' .
						$res['first_record_postdate'] .
						"], instead.";
					if ($this->debug) {
						$this->debugging->start("postdate", $dmessage, 5);
					}

					if ($this->echo) {
						$this->c->doEcho($this->c->info($dmessage), true);
					}
					return strtotime($res['first_record_postdate']);
				} else {
					return false;
				}
			} else {
				$res = $this->db->queryOneRow(sprintf("SELECT last_record_postdate from groups where name = '%s'", $group));
				if (array_key_exists('last_record_postdate', $res)) {
					$dmessage =
						'Unable to fetch article ' .
						$keeppost . ' from ' .
						str_replace('alt.binaries', 'a.b', $group) .
						'. Using current last_record_postdate[' .
						$res['last_record_postdate'] .
						"], instead.";
					if ($this->debug) {
						$this->debugging->start("postdate", $dmessage, 5);
					}

					if ($this->echo) {
						$this->c->doEcho($this->c->info($dmessage), true);
					}
					return strtotime($res['last_record_postdate']);
				} else {
					return false;
				}
			}
		} else if ($success === false) {
			return false;
		}

		if ($this->debug) {
			$this->debugging->start(
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

		$date = strtotime($date);
		return $date;
	}

	/**
	 * Returns article number based on # of days.
	 *
	 * @param object $nntp
	 * @param string $group
	 * @param int $days
	 * @param array $data
	 *
	 * @return string
	 */
	public function daytopost($nntp, $group, $days, $data)
	{
		if (!isset($nntp)) {
			$dmessage = "Not connected to usenet(backfill->daytopost).\n";
			$this->debugging->start("daytopost", $dmessage, 1);
			exit($this->c->error($dmessage));
		}
		// DEBUG every postdate call?!?!
		$pddebug = false;
		if ($this->debug) {
			$this->debugging->start("daytopost", 'Finding article for ' . $group . ' ' . $days . " days back.", 5);
		}

		// Goal timestamp.
		$goaldate = date('U') - (86400 * $days);
		$totalnumberofarticles = $data['last'] - $data['first'];
		$upperbound = $data['last'];
		$lowerbound = $data['first'];

		if ($this->debug) {
			$this->debugging->start(
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

		if ($data['last'] == PHP_INT_MAX) {

			$dmessage = "Group data is coming back as php's max value. You should not see this since we use a patched Net_NNTP that fixes this bug.\n";

			if ($this->debug) {
				$this->debugging->start("daytopost", $dmessage, 1);
			}
			exit($this->c->info($dmessage));
		}

		$firstDate = $this->postdate($nntp, $data['first'], $pddebug, $group, false, 'oldest');
		$lastDate = $this->postdate($nntp, $data['last'], $pddebug, $group, false, 'oldest');

		if ($goaldate < $firstDate) {
			$dmessage =
				"Backfill target of $days day(s) is older than the first article stored on your news server.\nStarting from the first available article (" .
				date('r', $firstDate) . ' or ' .
				$this->daysOld($firstDate) . " days).";
			if ($this->debug) {
				$this->debugging->start("daytopost", $dmessage, 3);
			}

			if ($this->echo) {
				$this->c->doEcho($this->c->warning($dmessage), true);
			}
			return $data['first'];
		} else if ($goaldate > $lastDate) {
			$dmessage =
				'Backfill target of ' .
				$days .
				" day(s) is newer than the last article stored on your news server.\nTo backfill this group you need to set Backfill Days to at least " .
				ceil($this->daysOld($lastDate) + 1) .
				' days (' .
				date('r', $lastDate - 86400) .
				").";
			if ($this->debug) {
				$this->debugging->start("daytopost", $dmessage, 2);
			}

			if ($this->echo) {
				$this->c->doEcho($this->c->error($dmessage), true);
			}
			return '';
		}

		if ($this->debug) {
			$this->debugging->start("daytopost",
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

		$interval = floor(($upperbound - $lowerbound) * 0.5);
		$templowered = '';
		$dateofnextone = $lastDate;

		if ($this->debug) {
			$this->debugging->start(
				"daytopost",
				'First Post: ' .
				number_format($data['first']) .
				' Last Post: ' .
				number_format($data['last']) .
				' Posts Available: ' .
				number_format($interval * 2),
				5);
		}

		// Match on days not timestamp to speed things up.
		while ($this->daysOld($dateofnextone) < $days) {
			while (($tmpDate = $this->postdate($nntp, ($upperbound - $interval), $pddebug, $group, false, 'oldest')) > $goaldate) {
				$upperbound = $upperbound - $interval;

				if ($this->debug) {
					$this->debugging->start(
						"daytopost",
						'New upperbound: ' .
						number_format($upperbound) .
						' is ' .
						$this->daysOld($tmpDate) .
						' days old.',
						5);
				}
			}

			if (!$templowered) {
				$interval = ceil(($interval / 2));
				if ($this->debug) {
					$this->debugging->start(
						"daytopost",
						'Checking interval at: (' .
						number_format($interval) .
						') articles.',
						5);
				}
			}
			$dateofnextone = $this->postdate($nntp, ($upperbound - 1), $pddebug, $group, false, 'oldest');
			while (!$dateofnextone) {
				$dateofnextone = $this->postdate($nntp, ($upperbound - 1), $pddebug, $group, false, 'oldest');
			}
		}

		$dmessage =
			'Determined to be article: ' .
			number_format($upperbound) .
			' which is ' .
			$this->daysOld($dateofnextone) .
			' days old (' .
			date('r', $dateofnextone) .
			')';
		if ($this->debug) {
			$this->debugging->start("daytopost", $dmessage, 5);
		}

		if ($this->echo) {
			$this->c->doEcho($dmessage, true);
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
	 * @param object $nntp
	 *
	 * @return void
	 */
	public function getRange($group, $first, $last, $threads, $nntp)
	{
		if (!isset($nntp)) {
			exit($this->c->error("Not connected to usenet(backfill->getRange).\n"));
		}

		$groups = new Groups();
		$this->startGroup = microtime(true);
		$binaries = new Binaries($this->echo);
		$groupArr = $groups->getByName($group);
		$process = $this->safepartrepair ? 'update' : 'backfill';

		if ($this->echo) {
			if ($this->nntpproxy == 0) {
				$this->c->doEcho(
					$this->c->set256($this->header) .
					'Processing ' .
					str_replace('alt.binaries', 'a.b', $groupArr['name']) .
					(($this->compressedHeaders) ? ' Using Compression' : ' Not Using Compression') .
					' ==> T-' .
					$threads .
					' ==> ' .
					number_format($first) .
					' to ' .
					number_format($last) .
					$this->c->rsetColor()
					, true
				);
			} else {
				$this->c->doEcho(
					$this->c->set256($this->header) .
					'Processing ' .
					str_replace('alt.binaries', 'a.b', $groupArr['name']) .
					' Using NNTPProxy ==> T-' .
					$threads .
					' ==> ' .
					number_format($first) .
					' to ' .
					number_format($last) .
					$this->c->rsetColor()
					, true
				);
			}
		}
		$this->startLoop = microtime(true);

		// Select group, here, only once
		$data = $nntp->selectGroup($groupArr['name']);
		if ($nntp->isError($data)) {
			$data = $nntp->dataError($nntp, $groupArr['name']);
			if ($nntp->isError($data)) {
				return;
			}
		}

		$binaries->scan($nntp, $groupArr, $last, $first, $process);
	}

	/**
	 * @param string $group
	 * @param int $first
	 * @param int $type
	 * @param object $nntp
	 *
	 * @return void
	 */
	function getFinal($group, $first, $type, $nntp)
	{
		if (!isset($nntp)) {
			exit($this->c->error("Not connected to usenet(backfill->getFinal).\n"));
		}

		$groups = new Groups();
		$groupArr = $groups->getByName($group);

		// Select group, here, only once
		$data = $nntp->selectGroup($groupArr['name']);
		if ($nntp->isError($data)) {
			$data = $nntp->dataError($nntp, $groupArr['name']);
			if ($nntp->isError($data)) {
				return;
			}
		}

		if ($type == 'Backfill') {
			$postsdate = $this->postdate($nntp, $first, false, $group, true, 'oldest');
		} else {
			$postsdate = $this->postdate($nntp, $first, false, $group, true, 'newest');
		}
		$postsdate = $this->db->from_unixtime($postsdate);

		if ($type == 'Backfill') {
			$this->db->queryExec(sprintf('UPDATE groups SET first_record_postdate = %s, first_record = %s, last_updated = NOW() WHERE id = %d', $postsdate, $this->db->escapeString($first), $groupArr['id']));
		} else {
			$this->db->queryExec(sprintf('UPDATE groups SET last_record_postdate = %s, last_record = %s, last_updated = NOW() WHERE id = %d', $postsdate, $this->db->escapeString($first), $groupArr['id']));
		}

		if ($this->echo) {
			$this->c->doEcho(
				$this->c->set256($this->primary) .
				$type .
				' Safe Threaded for ' .
				$group .
				" completed." .
				$this->c->rsetColor()
				, true
			);
		}
	}
}
