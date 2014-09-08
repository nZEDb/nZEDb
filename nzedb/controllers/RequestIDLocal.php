<?php

/**
 * Attempts to find a PRE name for a release using a request ID from our local pre database,
 * or internet request id database using a Standalone -- more intensive methods
 *
 * Class RequestIDLocal
 */
class RequestIDLocal extends RequestID
{
	/**
	 * @param array $options Class instances / Echo to cli?
	 */
	public function __construct(array $options = array())
	{
		parent::__construct($options);
	}

	/**
	 * Fetch releases with requestID's from MySQL.
	 */
	protected function _getReleases()
	{
		$query = (
			'SELECT r.id, r.name, r.categoryid, r.reqidstatus, g.name AS groupname, g.id as gid
			FROM releases r
			INNER JOIN groups g ON r.group_id = g.id
			WHERE r.nzbstatus = 1
			AND r.preid = 0
			AND r.isrequestid = 1'
		);

		$query .= ($this->_charGUID === '' ? '' : ' AND r.guid ' . $this->pdo->likeString($this->_charGUID, false, true));
		$query .= ($this->_groupID === '' ? '' : ' AND r.group_id = ' . $this->_groupID);
		$query .= ($this->_maxTime === 0 ? '' : sprintf(' AND r.adddate > NOW() - INTERVAL %d HOUR', $this->_maxTime));

		switch ($this->_limit) {
			case 'full':
				$query .= sprintf(
					" AND r.isrenamed = 0 AND r.reqidstatus in (%d, %d, %d)",
					self::REQID_UPROC,
					self::REQID_NOLL,
					self::REQID_NONE
				);
				break;
			case is_numeric($this->_limit):
				$query .= sprintf(
					" AND r.isrenamed = 0 AND r.reqidstatus in (%d, %d, %d) ORDER BY r.postdate DESC LIMIT %d",
					self::REQID_UPROC,
					self::REQID_NOLL,
					self::REQID_NONE,
					$this->_limit
				);
				break;
			case 'all':
			default:
				break;
		}
		$this->_releases = $this->pdo->queryDirect($query);
	}

	/**
	 * Process releases for requestID's.
	 *
	 * @return int How many did we rename?
	 */
	protected function _processReleases()
	{
		$renamed = $checked = 0;
		if ($this->_releases instanceof \Traversable) {
			foreach ($this->_releases as $this->_release) {
				$this->_requestID = $this->_siftReqId();

				// Do a local lookup using multiple possible methods
				$this->_newTitle = $this->_getNewTitle();

				if ($this->_newTitle !== false && isset($this->_newTitle['title'])) {
					$this->_updateRelease();
					$renamed++;
				} else {
					$this->_requestIdNotFound($this->_release['id'], ($this->_release['reqidstatus'] == self::REQID_UPROC ? self::REQID_NOLL : self::REQID_NONE));
				}

				if ($this->echoOutput && $this->_show === 0) {
					$this->consoleTools->overWritePrimary(
						"Checked Releases: [" . number_format($checked) . "] " .
						$this->consoleTools->percentString(++$checked, $this->_totalReleases)
					);
				}

			}
		}

		return $renamed;
	}

	/**
	 * Get a new title / pre ID for a release.
	 *
	 * @return array|bool
	 */
	protected function _getNewTitle()
	{
		if ($this->_requestID === -2) {
			return $this->_multiLookup();
		}

		$check = $this->pdo->queryDirect(
			sprintf(
				'SELECT id, title FROM predb WHERE requestid = %d AND group_id = %d',
				$this->_requestID,
				$this->_release['gid']
			)
		);

		if ($check instanceof \Traversable) {
			if ($check->rowCount() == 1) {
				foreach ($check as $row) {
					if (preg_match('/s\d+/i', $row['title']) && !preg_match('/s\d+e\d+/i', $row['title'])) {
						return false;
					}
					return array('title' => $row['title'], 'id' => $row['id']);
				}
			} else {
				//Prevents multiple releases with the same request id/group from being renamed to the same Pre.
				return $this->_multiLookup();
			}
		} else {
			$result = $this->_singleAltLookup();
			if (is_array($result) && is_numeric($result['id']) && $result['title'] !== '') {
				return $result;
			} else {
				return $this->_multiLookup();
			}
		}
		return false;
	}

	/**
	 * Sub function that attempts to match RequestID Releases
	 * by preg_matching the title from the usenet name
	 *
	 * @return array|bool
	 */
	protected function _multiLookup()
	{
		$regex1 =
				'/^\[\s*\d+\s*\][ -]+(\[(ISO|FULL|PART|MP3|0DAY|android)\][ -]+)?\[(alt-?bin| ?#?a[a-z0-9. -]+)((@?ef{1,2})?net)? ?\]' .
				'[ -]+(\[(ISO|FULL|PART|MP3|0DAY|android)\][ -]+)?(\[\s*\d+\s*\][ -]+)?(\[\d+\/\d+\][ -]+)?(\"|\[)\s*' .
				'(?P<title>.+?)(\.+(vol\d+\+\d+\.)?(-cd\d\.)?(avi|jpg|nzb|m3u|mkv|par2|part\d+|nfo|sample|sfv|rar|r?\d{1,3}|\d+|zip)*)?\s*(\"|\])' .
				'[ -]*(\[\d+\/\d+\][ -]*)?((\"\s*(?P<filename1>.+?)([-.]sample)?([-.]cd(\d|[ab]))?(\.+(vol\d+\+\d+\.)?([-.]d\d\.)?([-.]part\d+)?' .
				'(avi|jpg|nzb|m3u|mkv|par2|nfo|sample|sfv|rar|r?\d{1,3}|\d+|zip)*)?\s*\")| - (?P<filename2>.+?) (yEnc|\(\d+\/\d+\)))?.*/i'
		;

		$regex2 =
				'/^\[\s*\d+\s*\].*' .
				'\"\s*(?P<title>.+?)(\.+(vol\d+\+\d+\.)?(-cd\d\.)?' .
				'(avi|jpg|nzb|m3u|mkv|par2|part\d+|nfo|sample|sfv|rar|r?\d{1,3}|\d+|zip)*)\s*\".*/i'
		;

		$matches = array();
		switch (true) {
			case preg_match($regex1, $this->_release['name'], $matches):
			case preg_match($regex2, $this->_release['name'], $matches):
				$check = $this->pdo->queryOneRow(
					sprintf(
						"SELECT id, title FROM predb WHERE title = %s OR filename = %s %s",
						$this->pdo->escapeString($matches['title']),
						$this->pdo->escapeString($matches['title']),
						(
							isset($matches['filename1']) && $matches['filename1'] !== ''
							? 'OR filename = ' . $this->pdo->escapeString($matches['filename1'])
							:
							(
								isset($matches['filename2']) && $matches['filename2'] !== ''
								? 'OR filename = ' . $this->pdo->escapeString($matches['filename2'])
								: ''
							)
						)
					)
				);
				if ($check !== false) {
					return array('title' => $check['title'], 'id' => $check['id']);
				}
				continue;
			default:
				return false;
		}
		return false;
	}

	private $groupIDCache = array();

	/**
	 * Attempts to remap the release group_id by extracting the new group name from the release usenet name.
	 *
	 * @return array|bool
	 */
	protected function _singleAltLookup()
	{
		switch (true) {
			case $this->_release['name'] == 'alt.binaries.etc':
				$groupName = 'alt.binaries.teevee';
				break;
			case strpos($this->_release['name'], 'teevee') !== false:
				$groupName = 'alt.binaries.teevee';
				break;
			case strpos($this->_release['name'], 'moovee') !== false:
				$groupName = 'alt.binaries.moovee';
				break;
			case strpos($this->_release['name'], 'erotica') !== false:
				$groupName = 'alt.binaries.erotica';
				break;
			case strpos($this->_release['name'], 'foreign') !== false:
				$groupName = 'alt.binaries.mom';
				break;
			case strpos($this->_release['name'], 'inner-sanctum') !== false:
				$groupName = 'alt.binaries.inner-sanctum';
				break;
			case strpos($this->_release['name'], 'sounds.flac') !== false:
				$groupName = 'alt.binaries.sounds.flac';
				break;
			case strpos($this->_release['name'], 'scnzb') !== false:
				$groupName = 'alt.binaries.boneless';
				break;
			case strpos($this->_release['name'], 'hdtv.x264') !== false:
				$groupName = 'alt.binaries.hdtv.x264';
				break;
			default:
				return false;
		}
		if (isset($this->groupIDCache[$groupName])) {
			$groupID = $this->groupIDCache[$groupName];
		} else {
			$groupID = $this->groups->getIDByName($groupName);
		}
		$check = $this->pdo->queryOneRow(
			sprintf("
				SELECT id, title FROM predb WHERE requestid = %d AND group_id = %d",
				$this->_requestID,
				($groupID === '' ? 0 : $groupID)
			)
		);
		if ($check !== false) {
			return array('title' => $check['title'], 'id' => $check['id']);
		}
		return false;
	}

	/**
	 * Updates release information when a proper Request ID match is found.
	 */
	protected function _updateRelease()
	{
		$determinedCat = $this->category->determineCategory($this->_newTitle['title'], $this->_release['gid']);
		if ($determinedCat == $this->_release['categoryid']) {
			$newTitle = $this->pdo->escapeString($this->_newTitle['title']);
			$this->pdo->queryExec(
				sprintf('
					UPDATE releases
					SET preid = %d, reqidstatus = %d, isrenamed = 1, iscategorized = 1, searchname = %s
					WHERE id = %d',
					$this->_newTitle['id'],
					self::REQID_FOUND,
					$newTitle,
					$this->_release['id']
				)
			);
			$this->sphinx->updateReleaseSearchName($this->_release['id'], $newTitle);
		} else {
			$newTitle = $this->pdo->escapeString($this->_newTitle['title']);
			$this->pdo->queryExec(
				sprintf('
					UPDATE releases SET
						rageid = -1, seriesfull = NULL, season = NULL, episode = NULL, tvtitle = NULL,
						tvairdate = NULL, imdbid = NULL, musicinfoid = NULL, consoleinfoid = NULL,
						bookinfoid = NULL, anidbid = NULL, preid = %d, reqidstatus = %d, isrenamed = 1,
						iscategorized = 1, searchname = %s, categoryid = %d
					WHERE id = %d',
					$this->_newTitle['id'],
					self::REQID_FOUND,
					$newTitle,
					$determinedCat,
					$this->_release['id']
				)
			);
			$this->sphinx->updateReleaseSearchName($this->_release['id'], $newTitle);
		}

		if ($this->_release['name'] !== $this->_newTitle['title'] && $this->_show == 1) {
			\NameFixer::echoChangedReleaseName(
				array(
					'new_name'     => $this->_newTitle['title'],
					'old_name'     => $this->_release['name'],
					'new_category' => $this->category->getNameByID($determinedCat),
					'old_category' => $this->category->getNameByID($this->_release['categoryid']),
					'group'        => $this->_release['groupname'],
					'release_id'   => $this->_release['id'],
					'method'       => 'RequestIDLocal'
				)
			);
		}
	}
}
