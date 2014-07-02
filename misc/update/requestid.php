<?php
require_once dirname(__FILE__) . '/config.php';

$c = new ColorCLI();

if (!isset($argv[1]) || ( $argv[1] != "all" && $argv[1] != "full" && !is_numeric($argv[1]))) {
	exit ($c->error(
			PHP_EOL
			. "This script tries to match a release request ID by group to a PreDB request ID by group doing local lookup only." . PHP_EOL
			. "In addition an optional final argument is time, in minutes, to check releases that have previously been checked." . PHP_EOL . PHP_EOL
			. "php requestid.php 1000 show		...: to limit to 1000 sorted by newest postdate and show renaming." . PHP_EOL
			. "php requestid.php full show		...: to run on full database and show renaming." . PHP_EOL
			. "php requestid.php all show		...: to run on all requestid releases (including previously renamed) and show renaming." . PHP_EOL
			)
		);
}

use nzedb\db\DB;
$reqidlocal = new RequestIDStandalone();

$reqidlocal->standaloneLocalLookup($argv);
exit;

/**
 * Attempts to find a PRE name for a release using a request ID from our local pre database,
 * or internet request id database using a Standalone -- more intensive methods
 *
 * Class RequestIDStandalone
 */
class RequestIDStandalone
{

	// Request ID.
	const REQID_OLD    = -4; // We rechecked the web a second time and didn't find a title so don't process it again.
	const REQID_NONE   = -3; // The Request ID was not found locally or via web lookup.
	const REQID_ZERO   = -2; // The Request ID was 0.
	const REQID_NOLL   = -1; // Request ID was not found via local lookup.
	const REQID_UPROC  =  0; // Release has not been processed.
	const REQID_FOUND  =  1; // Request ID found and release was updated.

	/**
	 * @var bool Echo to CLI?
	 */
	protected $echoOutput;

	/**
	 * @var Category
	 */
	protected $category;

	/**
	 * @var nzedb\db\DB
	 */
	protected $db;

	/**
	 * @var ConsoleTools
	 */
	protected $consoleTools;

	/**
	 * @var ColorCLI
	 */
	protected $colorCLI;

	/**
	 * @var bool The title found from a request ID lookup.
	 */
	protected $newTitle = false;

	/**
	 * @var int The found request ID for the release.
	 */
	protected $requestID = self::REQID_ZERO;

	/**
	 * @var array The result of a SQL query.
	 */
	protected $run = array();

	/**
	 * @var int the command line show arg represented
	 * as an assigned integer value
	 */
	protected $show = 0;

	/**
	 * Construct.
	 *
	 * @param bool $echoOutput
	 */
	public function __construct($echoOutput = false)
	{
		$this->echoOutput = ($echoOutput && nZEDb_ECHOCLI);
		$this->category = new Categorize();
		$this->db = new DB();
		$this->groups = new Groups();
		$this->namefixer = new NameFixer();
		$this->consoleTools = new ConsoleTools();
		$this->c = new ColorCLI();
	}

	/**
	 * Main Worker Function that spawns child functions
	 *
	 * @param array $argv
	 */
	public function standaloneLocalLookup($argv)
	{

		$timestart = TIME();
		$counted = $counter = 0;
		($argv[2] === 'show' ? $this->show = 1 : $this->show = 0);

		$res = $this->db->queryDirect($this->_buildWorkQuery($argv));

		if ($res !== false) {
			$total = $res->rowCount();
		}

		if ($total > 0) {

			$this->_enumerateWork($total);

			foreach ($res as $row) {
				$this->requestID = 0;
				$this->requestID = $this->_siftReqId($row['name']);

				if ($this->requestID === self::REQID_ZERO) {
					$this->_requestIdNotFound($row['id'], $this->requestID);
					continue;
				}

				$this->newTitle = '';

				// Do a local lookup using multiple possible methods
				$this->newTitle = $this->_stageLookup($this->requestID, $row['groupname'], $row['name']);

				if (is_array($this->newTitle) && $this->newTitle['title'] != '') {
					$title = $this->newTitle['title'];
					$preid = $this->newTitle['id'];
					$determinedcat = $this->category->determineCategory($title, $row['gid']);
					$this->_updateRelease($title, $preid, $determinedcat, $row, $this->show);
					$counted++;
				} else {
					$this->_requestIdNotFound($row['id'], ($row['reqidstatus'] == self::REQID_UPROC ? self::REQID_NOLL : self::REQID_NONE));
				}

				if ($this->show === 0) {
					$this->consoleTools->overWritePrimary("Renamed Releases: [" . number_format($counted) . "] " . $this->consoleTools->percentString(++$counter, $total));
				}
			}
			echo $this->c->header("\nRenamed " . number_format($counted) . " releases in " . $this->consoleTools->convertTime(TIME() - $timestart) . ".");
		} else {
			echo $this->c->info("No work to process." . PHP_EOL);
		}
	}

	/**
	 * Builds work query for main function
	 *
	 * @param array $argv
	 */
	protected function _buildWorkQuery($argv)
	{
		switch(true) {
			case isset($argv[2]) && is_numeric($argv[2]):
				$time =
					sprintf(
						' OR r.postdate > NOW() - INTERVAL %d HOUR)',
						$argv[2]
					);
				break;
			case isset($argv[3]) && is_numeric($argv[3]):
				$time =
					sprintf(
						' OR r.postdate > NOW() - INTERVAL %d HOUR)',
						$argv[3]
					);
				break;
			default:
				$time = ')';
				break;
		}

		switch ($argv[1]) {
			case "all":	//runs on every release not already PreDB Matched
				$where = "WHERE nzbstatus = 1 AND preid = 0 AND isrequestid = 1";
				break;
			case "full":	//runs on all releases not already renamed not already PreDB matched
				$where =
						sprintf(
							"WHERE nzbstatus = 1 AND preid = 0 AND (isrenamed = 0 AND isrequestid = 1 %s AND reqidstatus in (0, -1, -3)",
							$time
						);
				break;
			default:	// NUMERIC - runs on all releases not already renamed limited by user not already PreDB matched
				$where =
						sprintf(
							"WHERE nzbstatus = 1 AND preid = 0 AND (isrenamed = 0 AND isrequestid = 1 %s AND reqidstatus in (0, -1, -3) " .
							"ORDER BY postdate DESC LIMIT %d",
							$time,
							$argv[1]
						);
				break;
		}
		return
			sprintf(
				"SELECT r.id, r.name, r.categoryid, r.reqidstatus, g.name AS groupname, g.id as gid " .
				"FROM releases r LEFT JOIN groups g ON r.group_id = g.id %s",
				$where
			);
	}

	/**
	 * Displays pre-run statistics
	 *
	 * @param int $total
	 */
	protected function _enumerateWork($total)
	{
		$reqidcount = $this->db->queryOneRow('SELECT COUNT(*) AS count FROM predb WHERE requestid > 0');
		echo $this->c->header(PHP_EOL . "Comparing " . number_format($total) . " releases against " . number_format($reqidcount['count']) . " Local requestID's." . PHP_EOL);
		sleep(2);
	}

	/**
	 * Sub function that attempts to match RequestID Releases
	 * by preg_matching the title from the usenet name
	 *
	 * @param string $groupName
	 * @param string $oldName
	 */
	protected function _multiLookup($groupName, $oldName)
	{
		switch ($groupName) {
			case 'alt.binaries.boneless':
				$regex = '/^\[ ?#?scnzb(\@?ef{1,2}net)? ?\][ -]?\[\d+\][ -]\[?\s*(?P<title>.+?)\s*\]?[ -]\[\d+\/\d+\][ -].*/i';
				break;
			case 'alt.binaries.sounds.flac':
				$regex = '/^\[\d+\](-\[\w+\]-|-)?\[ ?#?a\.?b(inaries)?\.?flac(\@?ef{1,2}net)? ?\]-\[\s*(?P<title>.+?)\s*\]-\[\d+\/\d+\]/i';
				break;
			case 'alt.binaries.moovee':
				$regex = '/^\[\d+\](-\[\w+\]-|-)?\[ ?#?a\.b(inaries)?\.moovee(\@?ef{1,2}net)? ?\]-?\[\s*(?P<title>[^\d\/\d]+?)\s*\]?-\[\d+\/\d+\]/i';
				break;
			case 'alt.binaries.teevee':
				$regex = '/^\[\d+\]-\[FULL\]-\[ ?#?a\.b(inaries)?\.teevee(\@?ef{1,2}net)? ?\]-\[\s*(?P<title>.+?)\s*\]-\[\d+\/\d+\]/i';
				break;
			case 'alt.binaries.erotica':
				$regex = '/^\[\d+\](-\[\w+\]-|-)?\[ ?#?a\.b(inaries)?\.erotica(\@?ef{1,2}net)? ?\]-\[\s*(?P<title>.+?)\s*\](-\[\d+\/\d+\])?/i';
				break;
			case 'alt.binaries.mom':
				$regex = '/^(\[.*\]-)?\[\d+\](-\[\w+\]-|-)?\[ ?#?a\.b(inaries)?\.foreign(\@?ef{1,2}net)? ?\]-\[\s*(?P<title>.+?)\s*\]-\[\d+\/\d+\]/i';
				break;
			case 'alt.binaries.hdtv.x264':
				$regex = '/^\[\d+\]-\[ ?#?a\.b(inaries)?\.hdtv\.x264(\@?ef{1,2}net)? ?\][ -](\[\w+\][ -])?(?P<title>.+?)[ -](\[\d+\/\d+\]|\".*\")/i';
				break;
			case 'alt.binaries.inner-sanctum':
				$regex = '/^\[\d+\]-\[.+?\]-\[ ?#?a\.b(inaries)?\.inner-?sanctum(\@?ef{1,2}net)? ?\]-\[\s*(?P<title>.+?)\s*\][ -](\[[\w ]+\][ -])?\[\d+\/\d+\]/i';
				break;
			default:
				return false;
		}
		if (preg_match($regex, $oldName, $matches)) {
			var_dump($matches['title']);
			$this->run = $this->db->queryOneRow(
							sprintf(
								"SELECT id, title FROM predb " .
								"WHERE title = %s OR filename = %s",
								$this->db->escapeString($matches['title']),
								$this->db->escapeString($matches['title'])
							)
			);
			if ($this->run !== false) {
				return array('title' => $this->run['title'], 'id' => $this->run['id']);
			} else {
				return false;
			}
		}
		return false;
	}

	/**
	 * Sub function that updates release reqidstatus
	 * when no match is found or ReqID is bad
	 *
	 * @param int $releaseID
	 * @param int $status
	 */
	protected function _requestIdNotFound($releaseID, $status)
	{

		if ($releaseID == 0) {
			return;
		}

		$this->db->queryExec(
			sprintf(
				'UPDATE releases SET reqidstatus = %d ' .
				'WHERE id = %d',
				$status,
				$releaseID
			)
		);
	}

	/**
	 * Sub function that preg_matches the ReqID
	 * from the release usenet name and returns
	 *
	 * @param string $releaseName
	 */
	protected function _siftReqId($releaseName)
	{
		switch (true) {
			case preg_match('/\[scnzbefnet\]\[(?P<reqid>\d+)\]/', $releaseName, $requestID):
				var_dump($requestID['reqid']);
				if ((int) $requestID['reqid'] > 0) {
					return (int) $requestID['reqid'];
				} else {
					continue;
				}
			case preg_match('/\[\s*(\d+)\s*\]/', $releaseName, $requestID):
			case preg_match('/^REQ\s*(\d{4,6})/i', $releaseName, $requestID):
			case preg_match('/^(\d{4,6})-\d{1}\[/', $releaseName, $requestID):
			case preg_match('/(\d{4,6}) -/',$releaseName, $requestID):
				if ((int) $requestID[1] > 0) {
					return (int) $requestID[1];
				} else {
					continue;
				}
			default:
				return self::REQID_ZERO;
		}
	}

	/**
	 * Sub function that attempts to remap the
	 * release group_id by extracting the new groupname
	 * from the release usenet name
	 *
	 * @param int $requestID
	 * @param string $groupName
	 * @param string $oldName
	 */
	protected function _singleAltLookup($requestID, $groupName, $oldName)
	{
		switch (true) {
			case preg_match('/\[#?a\.b\.teevee\]/', $oldName):
				$groupName = 'alt.binaries.teevee';
			case preg_match('/\[#?a\.b\.moovee\]/', $oldName):
				$groupName = 'alt.binaries.moovee';
			case preg_match('/\[#?a\.b\.erotica\]/', $oldName):
				$groupName = 'alt.binaries.erotica';
			case preg_match('/\[#?a\.b\.foreign\]/', $oldName):
				$groupName = 'alt.binaries.mom';
			case $groupName == 'alt.binaries.etc':
				$groupName = 'alt.binaries.teevee';
			default:
				return false;
		}
		$groupid = $groups->getIDByName($groupName);
		$this->run = $this->db->queryOneRow(
						sprintf(
							"SELECT id, title FROM predb " .
							"WHERE requestid = %d AND group_id = %d",
							$requestID,
							$groupid
						)
		);
		if (isset($this->run['title'])) {
			return array('title' => $this->run['title'], 'id' => $this->run['id']);
		}
	}

	/**
	 * Match function that sorts requestID to
	 * its match function
	 *
	 * @param int $requestID
	 * @param string $groupName
	 * @param string $oldName
	 */
	protected function _stageLookup($requestID, $groupName, $oldName)
	{

		$this->groupName = $groupName;
		$this->oldName = $oldName;
		$this->requestID = $requestID;

		$groupid = $this->groups->getIDByName($groupName);
		$this->run = $this->db->queryDirect(
							sprintf(
								'SELECT id, title FROM predb ' .
								'WHERE requestid = %d AND group_id = %d',
								$this->requestID,
								$groupid
							)
		);
		if ($this->run !== false) {
			$total = $this->run->rowCount();
			switch ((int) $total) {
				case '0':
					return false;
				case '1':
					foreach ($this->run as $row) {
						if (preg_match('/s\d+/i', $row['title']) && !preg_match('/s\d+e\d+/i', $row['title'])) {
							return false;
						}
						return array('title' => $row['title'], 'id' => $row['id']);
					}
				default:
					//Prevents multiple Pre releases with the same requestid/group from being renamed to the same Pre
					return $this->_multiLookup($this->groupName, $this->oldName);
			}
		} else {
			$result = $this->_singleAltLookup($this->groupName, $this->oldname);
			if (is_array($result) && is_numeric($result['id']) && $result['title'] !== '') {
				return $result;
			} else {
				return $this->_multiLookup($this->groupName, $this->oldName);
			}
		}
	}

	/**
	 * Updates release information when a proper
	 * requestid match is found
	 *
	 * @param string $title
	 * @param int $preid
	 * @param int $determinedcat
	 * @param array $row
	 * @param int $show
	 */
	protected function _updateRelease($title, $preid, $determinedcat, $row, $show)
	{
		$this->show = $show;

		if ($determinedcat == $row['categoryid']) {
			$this->run = $this->db->queryExec(
							sprintf(
								'UPDATE releases SET preid = %d, reqidstatus = 1, isrenamed = 1, iscategorized = 1, searchname = %s ' .
								'WHERE id = %d',
								$preid,
								$this->db->escapeString($title),
								$row['id']
							)
			);
		} else {
			$this->run = $this->db->queryExec(
							sprintf(
								'UPDATE releases SET rageid = -1, seriesfull = NULL, season = NULL, episode = NULL, tvtitle = NULL, tvairdate = NULL, ' .
								'imdbid = NULL, musicinfoid = NULL, consoleinfoid = NULL, bookinfoid = NULL, anidbid = NULL, ' .
								'preid = %d, reqidstatus = 1, isrenamed = 1, iscategorized = 1, searchname = %s, categoryid = %d WHERE id = %d',
								$preid,
								$this->db->escapeString($title),
								$determinedcat,
								$row['id']
							)
			);
		}
		if ($row['name'] !== $title && $this->show === 1) {
				$newcatname = $this->category->getNameByID($determinedcat);
				$oldcatname = $this->category->getNameByID($row['categoryid']);

				$this->namefixer->echoChangedReleaseName(
										array(
											'new_name'     => $title,
											'old_name'     => $row['name'],
											'new_category' => $newcatname,
											'old_category' => $oldcatname,
											'group'        => $row['groupname'],
											'release_id'   => $row['id'],
											'method'       => 'misc/update/requestid.php'
										)
				);
		}
	}

}

?>