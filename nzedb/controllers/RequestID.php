<?php

use nzedb\utility;

/**
 * Attempts to find a PRE name for a release using a request ID from our local pre database,
 * or internet request id database.
 *
 * Class RequestID
 */
class RequestID
{
	const MAX_WEB_LOOKUPS = 100; // Please don't exceed this, not to be to harsh on the Request ID server.

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
	protected $pdo;

	/**
	 * @var ConsoleTools
	 */
	protected $consoleTools;

	/**
	 * @var ColorCLI
	 */
	protected $colorCLI;

	/**
	 * @var int How many request id's did we find?
	 */
	protected $reqIDsFound = 0;

	/**
	 * @var bool Is this a local or web lookup ?
	 */
	protected $local = true;

	/**
	 * @var int What group are we working on ?
	 */
	protected $groupID = 0;

	/**
	 * @var array MySQL results for releases with RequestID's.
	 */
	protected $results = array();

	/**
	 * @var array Single MySQL result.
	 */
	protected $result = array();

	/**
	 * @var int How many releases max to do a web lookup.
	 */
	protected $limit = 100;

	/**
	 * @var bool The title found from a request ID lookup.
	 */
	protected $newTitle = false;

	/**
	 * @var int The found request ID for the release.
	 */
	protected $requestID = self::REQID_ZERO;

	/**
	 * The ID of the PRE entry the found request ID belongs to.
	 * @var bool|int
	 */
	protected $preDbID = false;

	/**
	 * Construct.
	 *
	 * @param bool $echoOutput
	 */
	public function __construct($echoOutput = false)
	{
		$this->echoOutput = ($echoOutput && nZEDb_ECHOCLI);
		$this->category = new Categorize();
		$this->pdo = new nzedb\db\Settings();
		$this->consoleTools = new ConsoleTools();
		$this->colorCLI = new ColorCLI();
		$this->_request_hours = ($this->pdo->getSetting('request_hours') != '') ? (int)$this->pdo->getSetting('request_hours') : 1;
	}

	/**
	 * Process RequestID's via Web or Local lookup.
	 *
	 * @param int  $groupID The ID of the group.
	 * @param int  $limit   How many requests to do.
	 * @param bool $local   Do a local or web lookup?
	 *
	 * @return int How many request ID's were found.
	 */
	public function lookupReqIDs($groupID, $limit, $local)
	{
		$this->groupID = $groupID;
		$this->limit = $limit;
		if ($local === false && $this->limit > self::MAX_WEB_LOOKUPS) {
			$this->limit = self::MAX_WEB_LOOKUPS;
		}
		$this->local = $local;
		$this->reqIDsFound = 0;
		$this->_getResults();

		if ($this->results !== false && $this->results->rowCount() > 0) {
			if ($this->local === true) {
				$this->_localCheck();
			} else {
				$this->_remoteCheck();
			}
		}
		return $this->reqIDsFound;
	}

	/**
	 * Get all results from the releases table that have request ID's to be processed.
	 */
	protected function _getResults()
	{
		// Look for records that potentially have requestID titles and have not been matched to a PreDB title
		if ($this->local === true) {
			$weblookup = '';
		} else {
			$weblookup = sprintf('OR (reqidstatus = %d AND adddate < NOW() - INTERVAL %d HOUR)',
						self::REQID_NONE,
						$this->_request_hours
						);
		}

		$this->results = $this->pdo->queryDirect(
			sprintf ('
				SELECT r.id, r.name, r.searchname, g.name AS groupname, r.group_id
				FROM releases r
				LEFT JOIN groups g ON r.group_id = g.id
				WHERE nzbstatus = 1
				AND preid = 0
				AND isrequestid = 1
				AND (
					reqidstatus = %d
					%s
				)
				%s %s %s LIMIT %d',
				($this->local === true ? self::REQID_UPROC : self::REQID_NOLL),
				$weblookup,
				(empty($this->groupID) ? '' : ('AND group_id = ' . $this->groupID)),
				($this->local === true ? '' : $this->_getReqIdGroups()), // Limit to req id groups on web look ups.
				($this->local === true ? '' :  'ORDER BY postdate DESC'),
				$this->limit
			)
		);
	}

	/**
	 * Create "AND" part of query for request ID groups.
	 * Less load on the request ID web server, by limiting results.
	 *
	 * @return string
	 */
	protected function _getReqIdGroups()
	{
		return (
			"AND g.name IN (
				'alt.binaries.boneless',
				'alt.binaries.cd.image',
				'alt.binaries.console.ps3',
				'alt.binaries.erotica',
				'alt.binaries.games.nintendods',
				'alt.binaries.games.wii',
				'alt.binaries.games.xbox360',
				'alt.binaries.inner-sanctum',
				'alt.binaries.mom',
				'alt.binaries.moovee',
				'alt.binaries.movies.divx',
				'alt.binaries.sony.psp',
				'alt.binaries.sounds.mp3.complete_cd',
				'alt.binaries.sounds.flac',
				'alt.binaries.teevee',
				'alt.binaries.warez'," .

				// Extra groups we will need to remap later, etc is teevee for example.
				"'alt.binaries.etc'
			)"
		);
	}

	/**
	 * Go through a release name and find a request ID.
	 *
	 * @param string $releaseName
	 *
	 * @return int
	 */
	protected function _siftReqId($releaseName)
	{
		if (preg_match('/\[\s*(\d+)\s*\]/', $releaseName, $requestID) ||
			preg_match('/^REQ\s*(\d{4,6})/i', $releaseName, $requestID) ||
			preg_match('/^(\d{4,6})-\d{1}\[/', $releaseName, $requestID) ||
			preg_match('/(\d{4,6}) -/',$releaseName, $requestID)
		)  {
			if ((int) $requestID[1] > 0) {
				return (int) $requestID[1];
			}
		}
		return self::REQID_ZERO;
	}

	/**
	 * No request ID was found, update the release.
	 *
	 * @param int  $releaseID
	 * @param int  $status    REQID constant status.
	 */
	protected function _requestIdNotFound($releaseID, $status)
	{
		if ($releaseID == 0) {
			return;
		}

		$this->pdo->queryExec(
			sprintf('
				UPDATE releases
				SET reqidstatus = %d
				WHERE id = %d',
				$status,
				$releaseID
			)
		);
	}

	/**
	 * Try to find a PRE name using the request ID in our local PRE database.
	 */
	protected function _localCheck()
	{
		$this->newTitle = false;

		foreach ($this->results as $result) {
			$this->result = $result;

			$this->groupID = $result['group_id'];

			$this->newTitle = false;

			// Try to get request id.
			$this->requestID = $this->_siftReqId($result['name']);

			if ($this->requestID === self::REQID_ZERO) {
				$this->_requestIdNotFound($result['id'], self::REQID_ZERO);
			} else {

				$localCheck = $this->pdo->queryOneRow(
					sprintf('
						SELECT id, title
						FROM predb
						WHERE requestid = %d
						AND group_id = %d
						LIMIT 1',
						$this->requestID,
						$this->result['group_id']
					)
				);

				if ($localCheck !== false) {
					$this->newTitle = $localCheck['title'];
					$this->preDbID = $localCheck['id'];
					$this->reqIDsFound++;

					$this->_updateRelease();
				} else {
					$this->pdo->queryExec(
						sprintf(
							'UPDATE releases SET reqidstatus = %d WHERE id = %d',
							self::REQID_NOLL,
							$result['id']
						)
					);
				}
			}
		}
	}

	/**
	 * Try to find a PRE name on the internet using the found request ID.
	 */
	protected function _remoteCheck()
	{
		// Array to store results.
		$requestArray = array();

		// Loop all the results.
		foreach($this->results as $result) {

			// Try to find a request ID for the release.
			$requestId = $this->_siftReqId($result['name']);

			// If there's none, update the release and continue.
			if ($requestId === self::REQID_ZERO) {
				$this->_requestIdNotFound($result['id'], self::REQID_NONE);
				if ($this->echoOutput) {
					echo '-';
				}
				continue;
			}

			$this->groupID = $result['group_id'];

			// Change etc to teevee.
			if ($result['groupname'] === 'alt.binaries.etc') {
				$result['groupname'] = 'alt.binaries.teevee';
			}

			// Send the release ID so we can track the return data.
			$requestArray[$result['id']] = array(
				'reqid' => $requestId,
				'ident' => $result['id'],
				'group' => $result['groupname'],
				'sname' => $result['searchname']
			);
		}

		// Check if we requests to send to the web.
		if (count($requestArray) < 1) {
			return;
		}

		// Mock array for isset check on server.
		$requestArray[0] = array('ident' => 0, 'group' => 'none', 'reqid' => 0);

		// Do a web lookup.
		$returnXml = nzedb\utility\getUrl($this->pdo->getSetting('request_url'), 'post', 'data=' . serialize($requestArray));

		// Change the release titles and insert the PRE's if they don't exist.
		if ($returnXml !== false) {
			$returnXml = @simplexml_load_string($returnXml);
			if ($returnXml !== false) {

				// Store the returned identifiers so we can check which releases we didn't find a request id.
				$returnedIdentifiers = array();

				foreach($returnXml->request as $result) {
					if (isset($result['name']) && isset($result['ident']) && (int)$result['ident'] > 0) {
						$this->newTitle = (string)$result['name'];
						$this->requestID = (int)$result['reqid'];
						$this->result['id'] = (int)$result['ident'];
						$this->result['group_id'] = $this->groupID;
						$this->result['groupname'] = $requestArray[(int)$result['ident']]['group'];
						$this->result['searchname'] = $requestArray[(int)$result['ident']]['sname'];
						$this->_insertIntoPreDB();
						$this->_updateRelease();
						$this->reqIDsFound++;
						if ($this->echoOutput) {
							echo '+';
						}
						$returnedIdentifiers[] = (string)$result['ident'];
					}
				}

				// Check if the WEB didn't send back some titles, update the release.
				if (count($returnedIdentifiers) > 0) {
					foreach ($returnedIdentifiers as $identifier) {
						if (array_key_exists($identifier, $requestArray)) {
							unset($requestArray[$identifier]);
						}
					}
				}

				unset($requestArray[0]);
				foreach ($requestArray as $request) {

					$adddate = $this->pdo->queryOneRow(
						sprintf(
							'SELECT UNIX_TIMESTAMP(adddate) AS adddate FROM releases WHERE id = %d', $request['ident']
						)
					);

					$status = self::REQID_NONE;
					if ($adddate !== false && !empty($adddate['adddate'])) {
						if ((bool) (intval((time() - (int)$adddate['adddate']) / 3600) > $this->_request_hours)) {
							$status = self::REQID_OLD;
						}
					} else {
						$status = self::REQID_OLD;
					}

					$this->_requestIdNotFound(
						$request['ident'],
						$status
					);
					if ($this->echoOutput) {
						echo '-';
					}
				}
			}
		}
		echo PHP_EOL;
	}

	/**
	 * If we found a PRE name, update the releases name and reset post processing.
	 */
	protected function _updateRelease()
	{
		$determinedCategory = $this->category->determineCategory($this->newTitle, $this->result['group_id']);
		$this->pdo->queryExec(
			sprintf('
				UPDATE releases
				SET rageid = -1, seriesfull = NULL, season = NULL, episode = NULL, tvtitle = NULL,
				tvairdate = NULL, imdbid = NULL, musicinfoid = NULL, consoleinfoid = NULL, bookinfoid = NULL, anidbid = NULL,
				reqidstatus = %d, isrenamed = 1, proc_files = 1, searchname = %s, categoryid = %d,
				preid = %d
				WHERE id = %d',
				self::REQID_FOUND,
				$this->pdo->escapeString($this->newTitle),
				$determinedCategory,
				$this->preDbID,
				$this->result['id']
			)
		);

		if ($this->echoOutput) {
			NameFixer::echoChangedReleaseName(array(
					'new_name'     => $this->newTitle,
					'old_name'     => $this->result['searchname'],
					'new_category' => $this->category->getNameByID($determinedCategory),
					'old_category' => '',
					'group'        => $this->result['groupname'],
					'release_id'   => $this->result['id'],
					'method'       => 'RequestID->updateRelease<' . ($this->local ? 'local' : 'web') . '>'
				)
			);
		}
	}

	/**
	 * If we found a request ID on the internet, check if our PRE database has it, insert it if not.
	 */
	protected function _insertIntoPreDB()
	{
		$dupeCheck = $this->pdo->queryOneRow(
			sprintf('
				SELECT id AS preid, requestid, group_id
				FROM predb
				WHERE title = %s',
				$this->pdo->escapeString($this->newTitle)
			)
		);

		if ($dupeCheck === false) {
			$this->preDbID = $this->pdo->queryInsert(
				sprintf("
					INSERT INTO predb (title, source, requestid, group_id, predate)
					VALUES (%s, %s, %d, %d, NOW())",
					$this->pdo->escapeString($this->newTitle),
					$this->pdo->escapeString('requestWEB'),
					$this->requestID,
					$this->result['group_id']
				)
			);
		} else {
			$this->preDbID = $dupeCheck['preid'];
			$this->pdo->queryExec(
				sprintf('
					UPDATE predb
					SET requestid = %d, group_id = %d
					WHERE id = %d',
					$this->requestID,
					$this->result['group_id'],
					$this->preDbID
				)
			);
		}
	}
}
