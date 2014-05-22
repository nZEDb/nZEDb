<?php

use nzedb\db\DB;
use nzedb\utility;

/**
 * Class RequestID
 */

class RequestID
{
	// Request ID.
	const REQID_NONE   = -3; // The Request ID was not found locally or via web lookup.
	const REQID_ZERO   = -2; // The Request ID was 0.
	const REQID_NOLL   = -1; // Request ID was not found via local lookup.
	const REQID_UPROC  =  0; // Release has not been processed.
	const REQID_FOUND  =  1; // Request ID found and release was updated.

	/**
	 * @param bool $echooutput
	 */
	public function __construct($echooutput = false)
	{
		$this->echooutput = ($echooutput && nZEDb_ECHOCLI);
		$this->category = new Category();
		$this->db = new DB();
		$this->consoleTools = new ConsoleTools();
		$this->c = new ColorCLI();
		$this->s = new Sites();
		$this->site = $this->s->get();
	}

	/**
	 * Process RequestID's via Local lookup.
	 *
	 * @param int $groupID
	 */
	public function lookupReqIDlocal($groupID)
	{
		$iFoundCnt = 0;
		$reqtimer = TIME();

		// Look for records that potentially have requestID titles and have not been matched to a PreDB title
		$resRel = $this->db->queryDirect(
			sprintf("
				SELECT r.id, r.name, r.searchname, g.name AS groupname, reqidstatus
				FROM releases r
				LEFT JOIN groups g ON r.groupid = g.id
				WHERE r.groupid = %d
				AND nzbstatus = 1
				AND preid = 0
				AND (isrequestid = 1 AND reqidstatus = 0 OR (reqidstatus = %d AND adddate > NOW() - INTERVAL %d HOUR))",
				$groupID,
				self::REQID_UPROC,
				self::REQID_NONE,
				(isset($this->site->request_hours) ? (int)$this->site->request_hours : 1)
			)
		);

		if ($resRel !== false && $resRel->rowCount() > 0) {
			$newTitle = false;

			foreach ($resRel as $rowRel) {
				$newTitle = $preId = false;

				// Try to get request id.
				if (preg_match('/\[\s*(\d+)\s*\]/', $rowRel['name'], $requestID) ||
					preg_match('/^REQ\s*(\d{4,6})/i', $rowRel['name'], $requestID) ||
					preg_match('/^(\d{4,6})-\d{1}\[/', $rowRel['name'], $requestID) ||
					preg_match('/(\d{4,6}) -/', $rowRel['name'], $requestID))  {
					$requestID = trim((int)$requestID[1]);
				} else {
					$requestID = 0;
				}

				if ($requestID == 0) {
					$this->db->queryExec(
						sprintf('
							UPDATE releases
							SET reqidstatus = %d
							WHERE id = %d',
							self::REQID_ZERO,
							$rowRel['id']
						)
					);
				} else {

					// Do a local lookup.
					$run = $this->db->queryOneRow(
						sprintf("
							SELECT id, title
							FROM predb
							WHERE requestid = %d
							AND groupid = %d",
							$requestID, $groupID
						)
					);

					if ($run !== false) {
						$newTitle = $run['title'];
						$preId = $run['id'];
						$iFoundCnt++;
					}
				}

				if ($newTitle !== false) {

					$determinedCat = $this->category->determineCategory($newTitle, $groupID);
					$this->db->queryExec(
						sprintf('
							UPDATE releases
							SET rageid = -1, seriesfull = NULL, season = NULL, episode = NULL, tvtitle = NULL,
							tvairdate = NULL, imdbid = NULL, musicinfoid = NULL, consoleinfoid = NULL, bookinfoid = NULL, anidbid = NULL,
							reqidstatus = %d, isrenamed = 1, proc_files = 1, searchname = %s,
							categoryid = %d, preid = %d
							WHERE id = %d',
							self::REQID_FOUND,
							$this->db->escapeString($newTitle),
							$determinedCat,
							$preId,
							$rowRel['id']
						)
					);

					if ($this->echooutput) {
						echo $this->c->primary(
							"\n\nNew name:  $newTitle" .
							"\nOld name:  " . $rowRel['searchname'] .
							"\nNew cat:   " . $this->category->getNameByID($determinedCat) .
							"\nGroup:     " . $rowRel['groupname'] .
							"\nMethod:    requestID local" .
							"\nReleaseID: " . $rowRel['id']
						);
					}
				} else if ($rowRel['reqidstatus'] == 0) {
					$this->db->queryExec(
						sprintf(
							'UPDATE releases SET reqidstatus = %d WHERE id = %d',
							self::REQID_NOLL,
							$rowRel['id']
						)
					);
				}
			}
			if ($this->echooutput && $newTitle !== false) {
				echo "\n";
			}
		}

		if ($this->echooutput) {
			$this->c->doEcho(
				$this->c->primary(
					number_format($iFoundCnt) .
					' Releases updated in ' .
					$this->consoleTools->convertTime(TIME() - $reqtimer)
				), true
			);
		}
	return $iFoundCnt;
	}

	/**
	 * Process RequestID's via Web lookup.
	 *
	 * @param int $groupID
	 * @param int $limit
	 */
	public function lookupReqIDweb($groupID, $limit)
	{
		$category = new Category();
		$iFoundCnt = 0;
		$reqtimer = TIME();

		// Look for records that potentially have requestID titles and have not been matched to a PreDB title
		$resRel = $this->db->queryDirect(
			sprintf("
				SELECT r.id, r.name, r.searchname, g.name AS groupname
				FROM releases r
				LEFT JOIN groups g ON r.groupid = g.id
				WHERE r.groupid = %d
				AND nzbstatus = 1
				AND preid = 0
				AND (isrequestid = 1 AND reqidstatus = %d OR (reqidstatus = %d AND adddate > NOW() - INTERVAL %d HOUR))
				ORDER BY postdate DESC
				LIMIT %d",
				$groupID,
				self::REQID_NOLL,
				self::REQID_NONE,
				(isset($this->site->request_hours) ? (int)$this->site->request_hours : 1),
				$limit
			)
		);

		if ($resRel !== false && $resRel->rowCount() > 0) {
			$newTitle = false;
			$web = (!empty($this->site->request_url) &&
					(nzedb\utility\getUrl($this->site->request_url) === false ? false : true));

			foreach ($resRel as $rowRel) {
				$newTitle = false;

				// Try to get request id.
				if (preg_match('/\[\s*(\d+)\s*\]/', $rowRel['name'], $requestID) ||
					preg_match('/^REQ\s*(\d{4,6})/i', $rowRel['name'], $requestID) ||
					preg_match('/^(\d{4,6})-\d{1}\[/', $rowRel['name'], $requestID) ||
					preg_match('/(\d{4,6}) -/', $rowRel['name'], $requestID))  {
					$requestID = trim((int)$requestID[1]);
				} else {
					$requestID = 0;
				}
					if ($requestID == 0) {
					$this->db->queryExec(
						sprintf('
							UPDATE releases
							SET reqidstatus = %d
							WHERE id = %d',
							self::REQID_ZERO,
							$rowRel['id']
						)
					);
				} else {

					// Do a web lookup.
					if ($web !== false) {
						$xml = nzedb\utility\getUrl(
							str_ireplace(
								'[REQUEST_ID]',
								$requestID,
								str_ireplace(
									'[GROUP_NM]',
									urlencode($rowRel['groupname']),
									$this->site->request_url
								)
							)
						);
						if ($xml !== false &&
							isset($xml->request[0]['name']) && !empty($xml->request[0]['name']) &&
							strtolower($xml->request[0]['name']) !== strtolower($rowRel['searchname'])) {
							var_dump(simplexml_load_string($xml));
							$newTitle = $xml->request[0]['name'];
							$iFoundCnt++;
						}
					}
				}

				if ($newTitle !== false) {
					$preid = false;
					$determinedCat = $category->determineCategory($newTitle, $groupID);
					$dupe = $this->db->queryOneRow(sprintf('SELECT id AS preid, requestid, groupid FROM predb WHERE title = %s',
							$this->db->escapeString($newTitle)));
					if ($dupe === false) {
						$md5 = md5($newTitle);
						$sha1 = sha1($newTitle);
						$preid = $this->db->queryInsert(
							sprintf("
							INSERT INTO predb (title, source, md5, sha1, requestid, groupid, predate)
							VALUES (%s, %s, %s, %s, %s, %d, NOW())",
								$this->db->escapeString($newTitle),
								$this->db->escapeString('requestWEB'),
								$this->db->escapeString($md5),
								$this->db->escapeString($sha1),
								$requestID,
								$groupID
							)
						);
					} else if ($dupe === true) {
						$preid = $dupe['preid'];
						$this->db->queryExec(sprintf('UPDATE predb SET requestid = %d, groupid = %d WHERE id = %d',
							$requestID,
							$groupID,
							$preid
							)
						);
					}
					$this->db->queryExec(
						sprintf('
							UPDATE releases
							SET rageid = -1, seriesfull = NULL, season = NULL, episode = NULL, tvtitle = NULL,
							tvairdate = NULL, imdbid = NULL, musicinfoid = NULL, consoleinfoid = NULL, bookinfoid = NULL, anidbid = NULL,
							reqidstatus = %d, isrenamed = 1, proc_files = 1, searchname = %s, categoryid = %d,
							preid = %d
							WHERE id = %d',
							self::REQID_FOUND,
							$this->db->escapeString($newTitle),
							$determinedCat,
							$preid,
							$rowRel['id']
						)
					);

					if ($this->echooutput) {
						echo $this->c->primary(
							"\n\nNew name:  $newTitle" .
							"\nOld name:  " . $rowRel['searchname'] .
							"\nNew cat:   " . $this->category->getNameByID($determinedCat) .
							"\nGroup:     " . $rowRel['groupname'] .
							"\nMethod:    requestID web" .
							"\nReleaseID: " . $rowRel['id']
						);
					}
				} else {
					$this->db->queryExec(
						sprintf(
							'UPDATE releases SET reqidstatus = %d WHERE id = %d',
							self::REQID_NONE,
							$rowRel['id']
						)
					);
				}
			}
			if ($this->echooutput && $newTitle !== false) {
				echo "\n";
			}
		}

		if ($this->echooutput) {
			$this->c->doEcho(
				$this->c->primary(
					number_format($iFoundCnt) .
					' Releases updated in ' .
					$this->consoleTools->convertTime(TIME() - $reqtimer)
				), true
			);
		}
	return $iFoundCnt;
	}
}