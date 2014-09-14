<?php
require_once nZEDb_LIBS . 'ZipFile.php';

use nzedb\db\Settings;
use nzedb\utility;

/**
 * Class Releases
 */
class Releases
{
	// RAR/ZIP Passworded indicator.
	const PASSWD_NONE      =  0; // No password.
	const PASSWD_POTENTIAL =  1; // Might have a password.
	const BAD_FILE         =  2; // Possibly broken RAR/ZIP.
	const PASSWD_RAR       = 10; // Definitely passworded.

	/**
	 * @var nzedb\db\Settings
	 */
	public $pdo;

	/**
	 * @var Groups
	 */
	public $groups;

	/**
	 * @var bool
	 */
	public $updategrabs;

	/**
	 * @var ReleaseSearch
	 */
	public $releaseSearch;

	/**
	 * @var SphinxSearch
	 */
	public $sphinxSearch;

	/**
	 * @var array $options Class instances.
	 */
	public function __construct(array $options = [])
	{
		$defaults = [
			'Settings' => null,
			'Groups'   => null
		];
		$options += $defaults;

		$this->pdo = ($options['Settings'] instanceof Settings ? $options['Settings'] : new Settings());
		$this->groups = ($options['Groups'] instanceof \Groups ? $options['Groups'] : new \Groups(['Settings' => $this->pdo]));
		$this->updategrabs = ($this->pdo->getSetting('grabstatus') == '0' ? false : true);
		$this->passwordStatus = ($this->pdo->getSetting('checkpasswordedrar') == 1 ? -1 : 0);
		$this->sphinxSearch = new \SphinxSearch();
		$this->releaseSearch = new \ReleaseSearch($this->pdo, $this->sphinxSearch);
	}

	/**
	 * Insert a single release returning the ID on success or false on failure.
	 *
	 * @param array $parameters Insert parameters, must be escaped if string.
	 *
	 * @return bool|int
	 */
	public function insertRelease(array $parameters = [])
	{
		$parameters['id'] = $this->pdo->queryInsert(
			sprintf(
				"INSERT INTO releases
					(name, searchname, totalpart, group_id, adddate, guid, rageid, postdate, fromname,
					size, passwordstatus, haspreview, categoryid, nfostatus, nzbstatus,
					isrenamed, iscategorized, reqidstatus, preid)
				 VALUES (%s, %s, %d, %d, NOW(), %s, -1, %s, %s, %s, %d, -1, %d, -1, %d, %d, 1, %d, %d)",
				$parameters['name'],
				$parameters['searchname'],
				$parameters['totalpart'],
				$parameters['group_id'],
				$parameters['guid'],
				$parameters['postdate'],
				$parameters['fromname'],
				$parameters['size'],
				$this->passwordStatus,
				$parameters['categoryid'],
				$parameters['nzbstatus'],
				$parameters['isrenamed'],
				$parameters['reqidstatus'],
				$parameters['preid']
			)
		);
		$this->sphinxSearch->insertRelease($parameters);
		return $parameters['id'];
	}

	/**
	 * Create a GUID for a release.
	 * @return string
	 */
	public function createGUID()
	{
		return sha1(uniqid('', true) . mt_rand());
	}

	/**
	 * @return array
	 */
	public function get()
	{
		return $this->pdo->query(
			sprintf(
				'SELECT r.*, g.name AS group_name, c.title AS category_name
				FROM releases r
				INNER JOIN category c ON c.id = r.categoryid
				INNER JOIN groups g ON g.id = r.group_id
				WHERE r.nzbstatus = %d',
				NZB::NZB_ADDED
			)
		);
	}

	/**
	 * Used for admin page release-list.
	 *
	 * @param $start
	 * @param $num
	 *
	 * @return array
	 */
	public function getRange($start, $num)
	{
		return $this->pdo->query(
			sprintf(
				"SELECT r.*, CONCAT(cp.title, ' > ', c.title) AS category_name
				FROM releases r
				INNER JOIN category c ON c.id = r.categoryid
				INNER JOIN category cp ON cp.id = c.parentid
				WHERE r.nzbstatus = %d
				ORDER BY r.postdate DESC %s",
				NZB::NZB_ADDED,
				($start === false ? '' : 'LIMIT ' . $num . ' OFFSET ' . $start)
			)
		);
	}

	/**
	 * Used for pager on browse page.
	 *
	 * @param array  $cat
	 * @param int    $maxAge
	 * @param array  $excludedCats
	 * @param string $groupName
	 *
	 * @return int
	 */
	public function getBrowseCount($cat, $maxAge = -1, $excludedCats = [], $groupName = '')
	{
		return $this->getPagerCount(
			sprintf(
				'SELECT r.id
				FROM releases r
				%s
				WHERE r.nzbstatus = %d
				AND r.passwordstatus %s
				%s %s %s %s',
				($groupName != '' ? 'INNER JOIN groups g ON g.id = r.group_id' : ''),
				NZB::NZB_ADDED,
				$this->showPasswords(),
				($groupName != '' ? sprintf(' AND g.name = %s', $this->pdo->escapeString($groupName)) : ''),
				$this->categorySQL($cat),
				($maxAge > 0 ? (' AND r.postdate > NOW() - INTERVAL ' . $maxAge . ' DAY ') : ''),
				(count($excludedCats) ? (' AND r.categoryid NOT IN (' . implode(',', $excludedCats) . ')') : '')
			)
		);
	}

	/**
	 * Used for browse results.
	 *
	 * @param array  $cat
	 * @param        $start
	 * @param        $num
	 * @param string $orderBy
	 * @param int    $maxAge
	 * @param array  $excludedCats
	 * @param string $groupName
	 *
	 * @return array
	 */
	public function getBrowseRange($cat, $start, $num, $orderBy, $maxAge = -1, $excludedCats = [], $groupName = '')
	{
		$orderBy = $this->getBrowseOrder($orderBy);
		return $this->pdo->query(
			sprintf(
				"SELECT r.*,
					CONCAT(cp.title, ' > ', c.title) AS category_name,
					CONCAT(cp.id, ',', c.id) AS category_ids,
					g.name AS group_name,
					rn.id AS nfoid,
					re.releaseid AS reid
				FROM releases r
				STRAIGHT_JOIN groups g ON g.id = r.group_id
				STRAIGHT_JOIN category c ON c.id = r.categoryid
				INNER JOIN category cp ON cp.id = c.parentid
				LEFT OUTER JOIN releasevideo re ON re.releaseid = r.id
				LEFT OUTER JOIN releasenfo rn ON rn.releaseid = r.id
				AND rn.nfo IS NOT NULL
				WHERE r.nzbstatus = %d
				AND r.passwordstatus %s
				%s %s %s %s
				ORDER BY %s %s %s",
				NZB::NZB_ADDED,
				$this->showPasswords(),
				$this->categorySQL($cat),
				($maxAge > 0 ? (" AND postdate > NOW() - INTERVAL " . $maxAge . ' DAY ') : ''),
				(count($excludedCats) ? (' AND r.categoryid NOT IN (' . implode(',', $excludedCats) . ')') : ''),
				($groupName != '' ? sprintf(' AND g.name = %s ', $this->pdo->escapeString($groupName)) : ''),
				$orderBy[0],
				$orderBy[1],
				($start === false ? '' : ' LIMIT ' . $num . ' OFFSET ' . $start)
			),
			true, nZEDb_CACHE_EXPIRY_MEDIUM
		);
	}

	/**
	 * Buffer of the password status string to form part of the query.
	 * @var null|string
	 */
	private $passwordSettingBuffer = null;

	/**
	 * Return site setting for hiding/showing passworded releases.
	 *
	 * @return int
	 */
	public function showPasswords()
	{
		if (!is_null($this->passwordSettingBuffer)) {
			return $this->passwordSettingBuffer;
		}
		$setting = $this->pdo->queryOneRow(
			"SELECT value
			FROM settings
			WHERE setting = 'showpasswordedrelease'"
		);
		$passwordStatus = ('= ' . \Releases::PASSWD_NONE);
		if ($setting !== false) {
			switch ($setting['value']) {
				case 1:
					$passwordStatus = ('<= ' . \Releases::PASSWD_POTENTIAL);
					break;
				case 10:
					$passwordStatus = ('<= ' . \Releases::PASSWD_RAR);
					break;
			}
		}
		$this->passwordSettingBuffer = $passwordStatus;
		return $passwordStatus;
	}

	/**
	 * Use to order releases on site.
	 *
	 * @param string $orderBy
	 *
	 * @return array
	 */
	public function getBrowseOrder($orderBy)
	{
		$orderArr = explode('_', ($orderBy == '' ? 'posted_desc' : $orderBy));
		switch ($orderArr[0]) {
			case 'cat':
				$orderField = 'categoryid';
				break;
			case 'name':
				$orderField = 'searchname';
				break;
			case 'size':
				$orderField = 'size';
				break;
			case 'files':
				$orderField = 'totalpart';
				break;
			case 'stats':
				$orderField = 'grabs';
				break;
			case 'posted':
			default:
				$orderField = 'postdate';
				break;
		}
		return [$orderField, (isset($orderArr[1]) && preg_match('/^(asc|desc)$/i', $orderArr[1]) ? $orderArr[1] : 'desc')];
	}

	/**
	 * Return ordering types usable on site.
	 *
	 * @return array
	 */
	public function getBrowseOrdering()
	{
		return [
			'name_asc',
			'name_desc',
			'cat_asc',
			'cat_desc',
			'posted_asc',
			'posted_desc',
			'size_asc',
			'size_desc',
			'files_asc',
			'files_desc',
			'stats_asc',
			'stats_desc'
		];
	}

	/**
	 * Get list of releases available for export.
	 *
	 * @param string $postFrom (optional) Date in this format : 01/01/2014
	 * @param string $postTo   (optional) Date in this format : 01/01/2014
	 * @param string $groupID  (optional) Group ID.
	 *
	 * @return array
	 */
	public function getForExport($postFrom = '', $postTo = '', $groupID = '')
	{
		return $this->pdo->query(
			sprintf(
				"SELECT searchname, guid, groups.name AS gname, CONCAT(cp.title,'_',category.title) AS catName
				FROM releases r
				INNER JOIN category ON r.categoryid = category.id
				INNER JOIN groups ON r.group_id = groups.id
				INNER JOIN category cp ON cp.id = category.parentid
				WHERE r.nzbstatus = %d
				%s %s %s",
				NZB::NZB_ADDED,
				$this->exportDateString($postFrom),
				$this->exportDateString($postTo, false),
				(($groupID != '' && $groupID != '-1') ? sprintf(' AND group_id = %d ', $groupID) : '')
			)
		);
	}

	/**
	 * Create a date query string for exporting.
	 *
	 * @param string $date
	 * @param bool   $from
	 *
	 * @return string
	 */
	private function exportDateString($date, $from = true)
	{
		if ($date != '') {
			$dateParts = explode('/', $date);
			if (count($dateParts) === 3) {
				$date = sprintf(
					' AND postdate %s %s ',
					($from ? '>' : '<'),
					$this->pdo->escapeString(
						$dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0] .
						($from ? ' 00:00:00' : ' 23:59:59')
					)
				);
			} else {
				$date = '';
			}
		}
		return $date;
	}

	/**
	 * Get date in this format : 01/01/2014 of the oldest release.
	 *
	 * @return mixed
	 */
	public function getEarliestUsenetPostDate()
	{
		$row = $this->pdo->queryOneRow("SELECT DATE_FORMAT(min(postdate), '%d/%m/%Y') AS postdate FROM releases LIMIT 1");

		return ($row === false ? '01/01/2014' : $row['postdate']);
	}

	/**
	 * Get date in this format : 01/01/2014 of the newest release.
	 *
	 * @return mixed
	 */
	public function getLatestUsenetPostDate()
	{
		$row = $this->pdo->queryOneRow("SELECT DATE_FORMAT(max(postdate), '%d/%m/%Y') AS postdate FROM releases LIMIT 1");

		return ($row === false ? '01/01/2014' : $row['postdate']);
	}

	/**
	 * Gets all groups for drop down selection on NZB-Export web page.
	 *
	 * @param bool $blnIncludeAll
	 *
	 * @return array
	 */
	public function getReleasedGroupsForSelect($blnIncludeAll = true)
	{
		$groups = $this->pdo->query(
			'SELECT DISTINCT g.id, g.name
			FROM releases r
			INNER JOIN groups g ON g.id = r.group_id'
		);
		$temp_array = [];

		if ($blnIncludeAll) {
			$temp_array[-1] = '--All Groups--';
		}

		foreach ($groups as $group) {
			$temp_array[$group['id']] = $group['name'];
		}
		return $temp_array;
	}

	/**
	 * Get releases for RSS.
	 *
	 * @param     $cat
	 * @param int $offset
	 * @param int $userID
	 * @param int $rageID
	 * @param int $aniDbID
	 * @param int $airDate
	 *
	 * @return array
	 */
	public function getRss($cat, $offset, $userID = 0, $rageID, $aniDbID, $airDate = -1)
	{
		$catSearch = $cartSearch = '';

		$catLimit = "AND r.categoryid BETWEEN 5000 AND 5999";

		if (count($cat)) {
			if ($cat[0] == -2) {
				$cartSearch = sprintf(' INNER JOIN usercart ON usercart.user_id = %d AND usercart.releaseid = r.id ', $userID);
			} else if ($cat[0] != -1) {
				$catSearch = ' AND (';
				$Category = new \Category(['Settings' => $this->pdo]);
				foreach ($cat as $category) {
					if ($category != -1) {
						if ($Category->isParent($category)) {
							$children = $Category->getChildren($category);
							$childList = '-99';
							foreach ($children as $child) {
								$childList .= ', ' . $child['id'];
							}

							if ($childList != '-99') {
								$catSearch .= ' r.categoryid IN (' . $childList . ') OR ';
							}
						} else {
							$catSearch .= sprintf(' r.categoryid = %d OR ', $category);
						}
					}
				}
				$catSearch .= '1=2 )';
			}
		}

		return $this->pdo->query(
			sprintf(
				"SELECT r.*, m.cover, m.imdbid, m.rating, m.plot,
					m.year, m.genre, m.director, m.actors, g.name AS group_name,
					CONCAT(cp.title, ' > ', c.title) AS category_name,
					CONCAT(cp.id, ',', c.id) AS category_ids,
					COALESCE(cp.id,0) AS parentCategoryid,
					mu.title AS mu_title, mu.url AS mu_url, mu.artist AS mu_artist,
					mu.publisher AS mu_publisher, mu.releasedate AS mu_releasedate,
					mu.review AS mu_review, mu.tracks AS mu_tracks, mu.cover AS mu_cover,
					mug.title AS mu_genre, co.title AS co_title, co.url AS co_url,
					co.publisher AS co_publisher, co.releasedate AS co_releasedate,
					co.review AS co_review, co.cover AS co_cover, cog.title AS co_genre
				FROM releases r
				INNER JOIN category c ON c.id = r.categoryid
				INNER JOIN category cp ON cp.id = c.parentid
				INNER JOIN groups g ON g.id = r.group_id
				LEFT OUTER JOIN movieinfo m ON m.imdbid = r.imdbid AND m.title != ''
				LEFT OUTER JOIN musicinfo mu ON mu.id = r.musicinfoid
				LEFT OUTER JOIN genres mug ON mug.id = mu.genre_id
				LEFT OUTER JOIN consoleinfo co ON co.id = r.consoleinfoid
				LEFT OUTER JOIN genres cog ON cog.id = co.genre_id %s
				WHERE r.passwordstatus %s
				AND r.nzbstatus = %d
				%s %s %s %s
				ORDER BY postdate DESC %s",
				$cartSearch,
				$this->showPasswords(),
				NZB::NZB_ADDED,
				$catSearch,
				($rageID > -1 ? sprintf(' AND r.rageid = %d %s ', $rageID, ($catSearch == '' ? $catLimit : '')) : ''),
				($aniDbID > -1 ? sprintf(' AND r.anidbid = %d %s ', $aniDbID, ($catSearch == '' ? $catLimit : '')) : ''),
				($airDate > -1 ? sprintf(' AND r.tvairdate >= DATE_SUB(CURDATE(), INTERVAL %d DAY) ', $airDate) : ''),
				(' LIMIT 0,' . ($offset > 100 ? 100 : $offset))
			)
		);
	}

	/**
	 * Get TV shows for RSS.
	 *
	 * @param int   $limit
	 * @param int   $userID
	 * @param array $excludedCats
	 * @param int   $airDate
	 *
	 * @return array
	 */
	public function getShowsRss($limit, $userID = 0, $excludedCats = [], $airDate = -1)
	{
		return $this->pdo->query(
			sprintf("
				SELECT r.*, tvr.rageid, tvr.releasetitle, g.name AS group_name,
					CONCAT(cp.title, '-', c.title) AS category_name,
					CONCAT(cp.id, ',', c.id) AS category_ids,
					COALESCE(cp.id,0) AS parentCategoryid
				FROM releases r
				INNER JOIN category c ON c.id = r.categoryid
				INNER JOIN category cp ON cp.id = c.parentid
				INNER JOIN groups g ON g.id = r.group_id
				LEFT OUTER JOIN tvrage tvr ON tvr.rageid = r.rageid
				WHERE %s %s %s
				AND r.nzbstatus = %d
				AND r.categoryid BETWEEN 5000 AND 5999
				AND r.passwordstatus %s
				ORDER BY postdate DESC %s",
				$this->uSQL($this->pdo->query(sprintf('SELECT rageid, categoryid FROM userseries WHERE user_id = %d', $userID), true), 'rageid'),
				(count($excludedCats) ? ' AND r.categoryid NOT IN (' . implode(',', $excludedCats) . ')' : ''),
				($airDate > -1 ? sprintf(' AND r.tvairdate >= DATE_SUB(CURDATE(), INTERVAL %d DAY) ', $airDate) : ''),
				NZB::NZB_ADDED,
				$this->showPasswords(),
				(' LIMIT ' . ($limit > 100 ? 100 : $limit) . ' OFFSET 0')
			)
		);
	}

	/**
	 * Get movies for RSS.
	 *
	 * @param int   $limit
	 * @param int   $userID
	 * @param array $excludedCats
	 *
	 * @return array
	 */
	public function getMyMoviesRss($limit, $userID = 0, $excludedCats = [])
	{
		return $this->pdo->query(
			sprintf("
				SELECT r.*, mi.title AS releasetitle, g.name AS group_name,
					CONCAT(cp.title, '-', c.title) AS category_name,
					CONCAT(cp.id, ',', c.id) AS category_ids,
					COALESCE(cp.id,0) AS parentCategoryid
				FROM releases r
				INNER JOIN category c ON c.id = r.categoryid
				INNER JOIN category cp ON cp.id = c.parentid
				INNER JOIN groups g ON g.id = r.group_id
				LEFT OUTER JOIN movieinfo mi ON mi.imdbid = r.imdbid
				WHERE %s %s
				AND r.nzbstatus = %d
				AND r.categoryid BETWEEN 2000 AND 2999
				AND r.passwordstatus %s
				ORDER BY postdate DESC %s",
				$this->uSQL($this->pdo->query(sprintf('SELECT imdbid, categoryid FROM usermovies WHERE user_id = %d', $userID), true), 'imdbid'),
				(count($excludedCats) ? ' AND r.categoryid NOT IN (' . implode(',', $excludedCats) . ')' : ''),
				NZB::NZB_ADDED,
				$this->showPasswords(),
				(' LIMIT ' . ($limit > 100 ? 100 : $limit) . ' OFFSET 0')
			)
		);
	}

	/**
	 * Get TV for my shows page.
	 *
	 * @param          $userShows
	 * @param int|bool $offset
	 * @param int      $limit
	 * @param string   $orderBy
	 * @param int      $maxAge
	 * @param array    $excludedCats
	 *
	 * @return array
	 */
	public function getShowsRange($userShows, $offset, $limit, $orderBy, $maxAge = -1, $excludedCats = [])
	{
		$orderBy = $this->getBrowseOrder($orderBy);
		return $this->pdo->query(
			sprintf(
				"SELECT r.*, CONCAT(cp.title, '-', c.title) AS category_name,
					CONCAT(cp.id, ',', c.id) AS category_ids, groups.name AS group_name,
					rn.id AS nfoid, re.releaseid AS reid
				FROM releases r
				LEFT OUTER JOIN releasevideo re ON re.releaseid = r.id
				INNER JOIN groups ON groups.id = r.group_id
				LEFT OUTER JOIN releasenfo rn ON rn.releaseid = r.id AND rn.nfo IS NOT NULL
				INNER JOIN category c ON c.id = r.categoryid
				INNER JOIN category cp ON cp.id = c.parentid
				WHERE %s %s
				AND r.nzbstatus = %d
				AND r.categoryid BETWEEN 5000 AND 5999
				AND r.passwordstatus %s
				%s
				ORDER BY %s %s %s",
				$this->uSQL($userShows, 'rageid'),
				(count($excludedCats) ? ' AND r.categoryid NOT IN (' . implode(',', $excludedCats) . ')' : ''),
				NZB::NZB_ADDED,
				$this->showPasswords(),
				($maxAge > 0 ? sprintf(' AND r.postdate > NOW() - INTERVAL %d DAY ', $maxAge) : ''),
				$orderBy[0],
				$orderBy[1],
				($offset === false ? '' : (' LIMIT ' . $limit . ' OFFSET ' . $offset))
			)
		);
	}

	/**
	 * Get count for my shows page pagination.
	 *
	 * @param       $userShows
	 * @param int   $maxAge
	 * @param array $excludedCats
	 *
	 * @return int
	 */
	public function getShowsCount($userShows, $maxAge = -1, $excludedCats = [])
	{
		return $this->getPagerCount(
			sprintf(
				'SELECT r.id
				FROM releases r
				WHERE %s %s
				AND r.nzbstatus = %d
				AND r.categoryid BETWEEN 5000 AND 5999
				AND r.passwordstatus %s
				%s',
				$this->uSQL($userShows, 'rageid'),
				(count($excludedCats) ? ' AND r.categoryid NOT IN (' . implode(',', $excludedCats) . ')' : ''),
				NZB::NZB_ADDED,
				$this->showPasswords(),
				($maxAge > 0 ? sprintf(' AND r.postdate > NOW() - INTERVAL %d DAY ', $maxAge) : '')
			)
		);
	}

	/**
	 * Get count for admin release list page.
	 *
	 * @return int
	 */
	public function getCount()
	{
		$res = $this->pdo->queryOneRow('SELECT COUNT(id) AS num FROM releases');
		return ($res === false ? 0 : $res['num']);
	}

	/**
	 * Delete multiple releases, or a single by ID.
	 *
	 * @param array|int|string $list   Array of GUID or ID of releases to delete.
	 * @param bool             $isGUID Are the identifiers GUID or ID?
	 */
	public function deleteMultiple($list, $isGUID = false)
	{
		if (!is_array($list)) {
			$list = [$list];
		}

		$nzb = new \NZB($this->pdo);
		$releaseImage = new \ReleaseImage($this->pdo);

		foreach ($list as $identifier) {
			if ($isGUID) {
				$this->deleteSingle(['g' => $identifier, 'i' => false], $nzb, $releaseImage);
			} else {
				$release = $this->pdo->queryOneRow(sprintf('SELECT guid FROM releases WHERE id = %d', $identifier));
				if ($release === false) {
					continue;
				}
				$this->deleteSingle(['g' => $release['guid'], 'i' => false], $nzb, $releaseImage);
			}
		}
	}

	/**
	 * Deletes a single release by GUID, and all the corresponding files.
	 *
	 * @param array        $identifiers ['g' => Release GUID(mandatory), 'id => ReleaseID(optional, pass false)]
	 * @param NZB          $nzb
	 * @param ReleaseImage $releaseImage
	 */
	public function deleteSingle($identifiers, $nzb, $releaseImage)
	{
		// Delete NZB from disk.
		$nzbPath = $nzb->NZBPath($identifiers['g']);
		if ($nzbPath) {
			@unlink($nzbPath);
		}

		// Delete images.
		$releaseImage->delete($identifiers['g']);

		// Delete from sphinx.
		$this->sphinxSearch->deleteRelease($identifiers, $this->pdo);

		// Delete from DB.
		$this->pdo->queryExec(
			sprintf('
				DELETE r, rn, rc, uc, rf, ra, rs, rv, re
				FROM releases r
				LEFT OUTER JOIN releasenfo rn ON rn.releaseid = r.id
				LEFT OUTER JOIN releasecomment rc ON rc.releaseid = r.id
				LEFT OUTER JOIN usercart uc ON uc.releaseid = r.id
				LEFT OUTER JOIN releasefiles rf ON rf.releaseid = r.id
				LEFT OUTER JOIN releaseaudio ra ON ra.releaseid = r.id
				LEFT OUTER JOIN releasesubs rs ON rs.releaseid = r.id
				LEFT OUTER JOIN releasevideo rv ON rv.releaseid = r.id
				LEFT OUTER JOIN releaseextrafull re ON re.releaseid = r.id
				WHERE r.guid = %s',
				$this->pdo->escapeString($identifiers['g'])
			)
		);
	}

	/**
	 * Used for release edit page on site.
	 *
	 * @param int    $ID
	 * @param string $name
	 * @param string $searchName
	 * @param string $fromName
	 * @param int    $categoryID
	 * @param int    $parts
	 * @param int    $grabs
	 * @param int    $size
	 * @param string $postedDate
	 * @param string $addedDate
	 * @param int    $rageID
	 * @param string $seriesFull
	 * @param string $season
	 * @param string $episode
	 * @param int    $imDbID
	 * @param int    $aniDbID
	 */
	public function update(
		$ID, $name, $searchName, $fromName, $categoryID, $parts, $grabs, $size,
		$postedDate, $addedDate, $rageID, $seriesFull, $season, $episode, $imDbID, $aniDbID
	)
	{
		$this->pdo->queryExec(
			sprintf(
				'UPDATE releases
				SET name = %s, searchname = %s, fromname = %s, categoryid = %d,
					totalpart = %d, grabs = %d, size = %s, postdate = %s, adddate = %s, rageid = %d,
					seriesfull = %s, season = %s, episode = %s, imdbid = %d, anidbid = %d
				WHERE id = %d',
				$this->pdo->escapeString($name),
				$this->pdo->escapeString($searchName),
				$this->pdo->escapeString($fromName),
				$categoryID,
				$parts,
				$grabs,
				$this->pdo->escapeString($size),
				$this->pdo->escapeString($postedDate),
				$this->pdo->escapeString($addedDate),
				$rageID,
				$this->pdo->escapeString($seriesFull),
				$this->pdo->escapeString($season),
				$this->pdo->escapeString($episode),
				$imDbID,
				$aniDbID,
				$ID
			)
		);
	}

	/**
	 * Used for updating releases on site.
	 *
	 * @param array  $guids
	 * @param int    $category
	 * @param int    $grabs
	 * @param int    $rageID
	 * @param string $season
	 * @param int  $imdDbID
	 *
	 * @return array|bool|int
	 */
	public function updatemulti($guids, $category, $grabs, $rageID, $season, $imdDbID)
	{
		if (!is_array($guids) || count($guids) < 1) {
			return false;
		}

		$update = [
			'categoryid' => (($category == '-1') ? '' : $category),
			'grabs'      => $grabs,
			'rageid'     => $rageID,
			'season'     => $season,
			'imdbid'     => $imdDbID
		];

		$updateSql = [];
		foreach ($update as $key => $value) {
			if ($value != '') {
				$updateSql[] = sprintf($key . '=%s', $this->pdo->escapeString($value));
			}
		}

		if (count($updateSql) < 1) {
			return -1;
		}

		$updateGuids = [];
		foreach ($guids as $guid) {
			$updateGuids[] = $this->pdo->escapeString($guid);
		}

		return $this->pdo->query(
			sprintf(
				'UPDATE releases SET %s WHERE guid IN (%s)',
				implode(', ', $updateSql),
				implode(', ', $updateGuids)
			)
		);
	}

	/**
	 * Creates part of a query for some functions.
	 *
	 * @param array  $userQuery
	 * @param string $type
	 *
	 * @return string
	 */
	public function uSQL($userQuery, $type)
	{
		$sql = '(1=2 ';
		foreach ($userQuery as $query) {
			$sql .= sprintf('OR (r.%s = %d', $type, $query[$type]);
			if ($query['categoryid'] != '') {
				$catsArr = explode('|', $query['categoryid']);
				if (count($catsArr) > 1) {
					$sql .= sprintf(' AND r.categoryid IN (%s)', implode(',', $catsArr));
				} else {
					$sql .= sprintf(' AND r.categoryid = %d', $catsArr[0]);
				}
			}
			$sql .= ') ';
		}
		$sql .= ') ';

		return $sql;
	}

	/**
	 * Creates part of a query for searches requiring the categoryID's.
	 *
	 * @param array $categories
	 *
	 * @return string
	 */
	public function categorySQL($categories)
	{
		$sql = '';
		if (count($categories) > 0 && $categories[0] != -1) {
			$Category = new \Category(['Settings' => $this->pdo]);
			$sql = ' AND (';
			foreach ($categories as $category) {
				if ($category != -1) {
					if ($Category->isParent($category)) {
						$children = $Category->getChildren($category);
						$childList = '-99';
						foreach ($children as $child) {
							$childList .= ', ' . $child['id'];
						}

						if ($childList != '-99') {
							$sql .= ' r.categoryid IN (' . $childList . ') OR ';
						}
					} else {
						$sql .= sprintf(' r.categoryid = %d OR ', $category);
					}
				}
			}
			$sql .= '1=2 )';
		}

		return $sql;
	}

	/**
	 * Function for searching on the site (by subject, searchname or advanced).
	 *
	 * @param string $searchName
	 * @param string $usenetName
	 * @param string $posterName
	 * @param string $groupName
	 * @param array  $cat
	 * @param int    $sizeFrom
	 * @param int    $sizeTo
	 * @param int    $hasNfo
	 * @param int    $hasComments
	 * @param int    $daysNew
	 * @param int    $daysOld
	 * @param int    $offset
	 * @param int    $limit
	 * @param string $orderBy
	 * @param int    $maxAge
	 * @param array  $excludedCats
	 * @param string $type
	 *
	 * @return array
	 */
	public function search(
		$searchName, $usenetName, $posterName, $groupName, $cat = [-1], $sizeFrom,
		$sizeTo, $hasNfo, $hasComments, $daysNew, $daysOld, $offset = 0, $limit = 1000,
		$orderBy = '', $maxAge = -1, $excludedCats = [], $type = 'basic'
	)
	{
		$sizeRange = range(1,11);

		if ($orderBy == '') {
			$orderBy = [];
			$orderBy[0] = 'postdate ';
			$orderBy[1] = 'desc ';
		} else {
			$orderBy = $this->getBrowseOrder($orderBy);
		}

		$searchOptions = [];
		if ($searchName != -1) {
			$searchOptions['searchname'] = $searchName;
		}
		if ($usenetName != -1) {
			$searchOptions['name'] = $usenetName;
		}
		if ($posterName != -1) {
			$searchOptions['fromname'] = $posterName;
		}

		$whereSql = sprintf(
			"%s
			WHERE r.passwordstatus %s AND r.nzbstatus = %d %s %s %s %s %s %s %s %s %s %s %s",
			$this->releaseSearch->getFullTextJoinString(),
			$this->showPasswords(),
			NZB::NZB_ADDED,
			($maxAge > 0 ? sprintf(' AND r.postdate > (NOW() - INTERVAL %d DAY) ', $maxAge) : ''),
			($groupName != -1 ? sprintf(' AND r.group_id = %d ', $this->groups->getIDByName($groupName)) : ''),
			(in_array($sizeFrom, $sizeRange) ? ' AND r.size > ' . (string)(104857600 * (int)$sizeFrom) . ' ' : ''),
			(in_array($sizeTo, $sizeRange) ? ' AND r.size < ' . (string)(104857600 * (int)$sizeTo) . ' ' : ''),
			($hasNfo != 0 ? ' AND r.nfostatus = 1 ' : ''),
			($hasComments != 0 ? ' AND r.comments > 0 ' : ''),
			($type !== 'advanced' ? $this->categorySQL($cat) : ($cat[0] != '-1' ? sprintf(' AND (r.categoryid = %d) ', $cat[0]) : '')),
			($daysNew != -1 ? sprintf(' AND r.postdate < (NOW() - INTERVAL %d DAY) ', $daysNew) : ''),
			($daysOld != -1 ? sprintf(' AND r.postdate > (NOW() - INTERVAL %d DAY) ', $daysOld) : ''),
			(count($excludedCats) > 0 ? ' AND r.categoryid NOT IN (' . implode(',', $excludedCats) . ')' : ''),
			(count($searchOptions) > 0 ? $this->releaseSearch->getSearchSQL($searchOptions) : '')
		);

		$baseSql = sprintf(
			"SELECT r.*,
				CONCAT(cp.title, ' > ', c.title) AS category_name,
				CONCAT(cp.id, ',', c.id) AS category_ids,
				groups.name AS group_name,
				rn.id AS nfoid,
				re.releaseid AS reid,
				cp.id AS categoryparentid
			FROM releases r
			LEFT OUTER JOIN releasevideo re ON re.releaseid = r.id
			LEFT OUTER JOIN releasenfo rn ON rn.releaseid = r.id
			INNER JOIN groups ON groups.id = r.group_id
			INNER JOIN category c ON c.id = r.categoryid
			INNER JOIN category cp ON cp.id = c.parentid
			%s",
			$whereSql
		);

		$sql = sprintf(
			"SELECT * FROM (
				%s
			) r
			ORDER BY r.%s %s
			LIMIT %d OFFSET %d",
			$baseSql,
			$orderBy[0],
			$orderBy[1],
			$limit,
			$offset
		);
		$releases = $this->pdo->query($sql);
		if ($releases && count($releases)) {
			$releases[0]['_totalrows'] = $this->getPagerCount($baseSql);
		}
		return $releases;
	}

	/**
	 * @param        $rageId
	 * @param string $series
	 * @param string $episode
	 * @param int    $offset
	 * @param int    $limit
	 * @param string $name
	 * @param array  $cat
	 * @param int    $maxAge
	 *
	 * @return array
	 */
	public function searchbyRageId($rageId, $series = '', $episode = '', $offset = 0, $limit = 100, $name = '', $cat = [-1], $maxAge = -1)
	{
		$whereSql = sprintf(
			"%s
			WHERE r.categoryid BETWEEN 5000 AND 5999
			AND r.nzbstatus = %d
			AND r.passwordstatus %s %s %s %s %s %s %s",
			($name !== '' ? $this->releaseSearch->getFullTextJoinString() : ''),
			NZB::NZB_ADDED,
			$this->showPasswords(),
			($rageId != -1 ? sprintf(' AND rageid = %d ', $rageId) : ''),
			($series != '' ? sprintf(' AND UPPER(r.season) = UPPER(%s)', $this->pdo->escapeString(((is_numeric($series) && strlen($series) != 4) ? sprintf('S%02d', $series) : $series))) : ''),
			($episode != '' ? sprintf(' AND r.episode %s', $this->pdo->likeString((is_numeric($episode) ? sprintf('E%02d', $episode) : $episode))) : ''),
			($name !== '' ? $this->releaseSearch->getSearchSQL(['searchname' => $name]) : ''),
			$this->categorySQL($cat),
			($maxAge > 0 ? sprintf(' AND r.postdate > NOW() - INTERVAL %d DAY ', $maxAge) : '')
		);

		$baseSql = sprintf(
			"SELECT r.*,
				concat(cp.title, ' > ', c.title) AS category_name,
				CONCAT(cp.id, ',', c.id) AS category_ids,
				groups.name AS group_name,
				rn.id AS nfoid,
				re.releaseid AS reid
			FROM releases r
			INNER JOIN category c ON c.id = r.categoryid
			INNER JOIN groups ON groups.id = r.group_id
			LEFT OUTER JOIN releasevideo re ON re.releaseid = r.id
			LEFT OUTER JOIN releasenfo rn ON rn.releaseid = r.id AND rn.nfo IS NOT NULL
			INNER JOIN category cp ON cp.id = c.parentid
			%s",
			$whereSql
		);

		$sql = sprintf(
			"%s
			ORDER BY postdate DESC
			LIMIT %d OFFSET %d",
			$baseSql,
			$limit,
			$offset
		);
		$releases = $this->pdo->query($sql);
		if ($releases && count($releases)) {
			$releases[0]['_totalrows'] = $this->getPagerCount($baseSql);
		}
		return $releases;
	}

	/**
	 * @param int    $aniDbID
	 * @param string $episodeNumber
	 * @param int    $offset
	 * @param int    $limit
	 * @param string $name
	 * @param array  $cat
	 * @param int    $maxAge
	 *
	 * @return array
	 */
	public function searchbyAnidbId($aniDbID, $episodeNumber = '', $offset = 0, $limit = 100, $name = '', $cat = [-1], $maxAge = -1)
	{
		$whereSql = sprintf(
			"%s
			WHERE r.passwordstatus %s AND r.nzbstatus = %d %s %s %s %s %s",
			($name !== '' ? $this->releaseSearch->getFullTextJoinString() : ''),
			$this->showPasswords(),
			NZB::NZB_ADDED,
			($aniDbID > -1 ? sprintf(' AND anidbid = %d ', $aniDbID) : ''),
			(is_numeric($episodeNumber) ? sprintf(" AND r.episode '%s' ", $this->pdo->likeString($episodeNumber)) : ''),
			($name !== '' ? $this->releaseSearch->getSearchSQL(['searchname' => $name]) : ''),
			$this->categorySQL($cat),
			($maxAge > 0 ? sprintf(' AND r.postdate > NOW() - INTERVAL %d DAY ', $maxAge) : '')
		);

		$baseSql = sprintf(
			"SELECT r.*,
				CONCAT(cp.title, ' > ', c.title) AS category_name,
				CONCAT(cp.id, ',', c.id) AS category_ids,
				groups.name AS group_name,
				rn.id AS nfoid
			FROM releases r
			INNER JOIN category c ON c.id = r.categoryid
			INNER JOIN groups ON groups.id = r.group_id
			LEFT OUTER JOIN releasenfo rn ON rn.releaseid = r.id AND rn.nfo IS NOT NULL
			INNER JOIN category cp ON cp.id = c.parentid
			%s",
			$whereSql
		);

		$sql = sprintf(
			"%s
			ORDER BY postdate DESC
			LIMIT %d OFFSET %d",
			$baseSql,
			$limit,
			$offset
		);
		$releases = $this->pdo->query($sql);
		if ($releases && count($releases)) {
			$releases[0]['_totalrows'] = $this->getPagerCount($baseSql);
		}
		return $releases;
	}

	/**
	 * @param int    $imDbId
	 * @param int    $offset
	 * @param int    $limit
	 * @param string $name
	 * @param array  $cat
	 * @param int    $maxAge
	 *
	 * @return array
	 */
	public function searchbyImdbId($imDbId, $offset = 0, $limit = 100, $name = '', $cat = [-1], $maxAge = -1)
	{
		$whereSql = sprintf(
			"%s
			WHERE r.categoryid BETWEEN 2000 AND 2999
			AND r.nzbstatus = %d
			AND r.passwordstatus %s
			%s %s %s %s",
			($name !== '' ? $this->releaseSearch->getFullTextJoinString() : ''),
			NZB::NZB_ADDED,
			$this->showPasswords(),
			($name !== '' ? $this->releaseSearch->getSearchSQL(['searchname' => $name]) : ''),
			(($imDbId != '-1' && is_numeric($imDbId)) ? sprintf(' AND imdbid = %d ', str_pad($imDbId, 7, '0', STR_PAD_LEFT)) : ''),
			$this->categorySQL($cat),
			($maxAge > 0 ? sprintf(' AND r.postdate > NOW() - INTERVAL %d DAY ', $maxAge) : '')
		);

		$baseSql = sprintf(
			"SELECT r.*,
				concat(cp.title, ' > ', c.title) AS category_name,
				CONCAT(cp.id, ',', c.id) AS category_ids,
				g.name AS group_name,
				rn.id AS nfoid
			FROM releases r
			INNER JOIN groups g ON g.id = r.group_id
			INNER JOIN category c ON c.id = r.categoryid
			LEFT OUTER JOIN releasenfo rn ON rn.releaseid = r.id AND rn.nfo IS NOT NULL
			INNER JOIN category cp ON cp.id = c.parentid
			%s",
			$whereSql
		);

		$sql = sprintf(
			"%s
			ORDER BY postdate DESC
			LIMIT %d OFFSET %d",
			$baseSql,
			$limit,
			$offset
		);
		$releases = $this->pdo->query($sql);
		if ($releases && count($releases)) {
			$releases[0]['_totalrows'] = $this->getPagerCount($baseSql);
		}
		return $releases;
	}

	/**
	 * Get count of releases for pager.
	 *
	 * @param string $query The query to get the count from.
	 *
	 * @return int
	 */
	private function getPagerCount($query)
	{
		$count = $this->pdo->queryOneRow(
			sprintf(
				'SELECT COUNT(*) AS count FROM (%s LIMIT %s) z',
				preg_replace('/SELECT.+?FROM\s+releases/is', 'SELECT r.id FROM releases', $query),
				nZEDb_MAX_PAGER_RESULTS
			)
		);
		if (isset($count['count']) && is_numeric($count['count'])) {
			return $count['count'];
		}
		return 0;
	}

	/**
	 * @param       $currentID
	 * @param       $name
	 * @param int   $limit
	 * @param array $excludedCats
	 *
	 * @return array
	 */
	public function searchSimilar($currentID, $name, $limit = 6, $excludedCats = [])
	{
		// Get the category for the parent of this release.
		$currRow = $this->getById($currentID);
		$catRow = (new \Category(['Settings' => $this->pdo]))->getById($currRow['categoryid']);
		$parentCat = $catRow['parentid'];

		$results = $this->search(
			$this->getSimilarName($name), -1, -1, -1, [$parentCat], -1, -1, 0, 0, -1, -1, 0, $limit, '', -1, $excludedCats
		);
		if (!$results) {
			return $results;
		}

		$ret = [];
		foreach ($results as $res) {
			if ($res['id'] != $currentID && $res['categoryparentid'] == $parentCat) {
				$ret[] = $res;
			}
		}
		return $ret;
	}

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	public function getSimilarName($name)
	{
		return implode(' ', array_slice(str_word_count(str_replace(['.', '_'], ' ', $name), 2), 0, 2));
	}

	/**
	 * @param string $guid
	 *
	 * @return array|bool
	 */
	public function getByGuid($guid)
	{
		if (is_array($guid)) {
			$tempGuids = [];
			foreach ($guid as $identifier) {
				$tempGuids[] = $this->pdo->escapeString($identifier);
			}
			$gSql = sprintf('r.guid IN (%s)', implode(',', $tempGuids));
		} else {
			$gSql = sprintf('r.guid = %s', $this->pdo->escapeString($guid));
		}
		$sql = sprintf(
			"SELECT r.*, CONCAT(cp.title, ' > ', c.title) AS category_name, CONCAT(cp.id, ',', c.id) AS category_ids,
				g.name AS group_name FROM releases r
			INNER JOIN groups g ON g.id = r.group_id
			INNER JOIN category c ON c.id = r.categoryid
			INNER JOIN category cp ON cp.id = c.parentid
			WHERE %s",
			$gSql
		);

		return (is_array($guid)) ? $this->pdo->query($sql) : $this->pdo->queryOneRow($sql);
	}

	// Writes a zip file of an array of release guids directly to the stream.
	/**
	 * @param $guids
	 *
	 * @return string
	 */
	public function getZipped($guids)
	{
		$nzb = new \NZB($this->pdo);
		$zipFile = new \ZipFile();

		foreach ($guids as $guid) {
			$nzbPath = $nzb->NZBPath($guid);

			if ($nzbPath) {
				$nzbContents = nzedb\utility\Utility::unzipGzipFile($nzbPath);

				if ($nzbContents) {
					$filename = $guid;
					$r = $this->getByGuid($guid);
					if ($r) {
						$filename = $r['searchname'];
					}
					$zipFile->addFile($nzbContents, $filename . '.nzb');
				}
			}
		}
		return $zipFile->file();
	}

	/**
	 * @param        $rageID
	 * @param string $series
	 * @param string $episode
	 *
	 * @return array|bool
	 */
	public function getbyRageId($rageID, $series = '', $episode = '')
	{
		if ($series != '') {
			// Exclude four digit series, which will be the year 2010 etc.
			if (is_numeric($series) && strlen($series) != 4) {
				$series = sprintf('S%02d', $series);
			}

			$series = sprintf(' AND UPPER(r.season) = UPPER(%s)', $this->pdo->escapeString($series));
		}

		if ($episode != '') {
			if (is_numeric($episode)) {
				$episode = sprintf('E%02d', $episode);
			}

			$episode = sprintf(' AND UPPER(r.episode) = UPPER(%s)', $this->pdo->escapeString($episode));
		}

		return $this->pdo->queryOneRow(
			sprintf(
				"SELECT r.*, CONCAT(cp.title, ' > ', c.title) AS category_name,
				groups.name AS group_name
				FROM releases r
				INNER JOIN groups ON groups.id = r.group_id
				INNER JOIN category c ON c.id = r.categoryid
				INNER JOIN category cp ON cp.id = c.parentid
				WHERE r.categoryid BETWEEN 5000 AND 5999
				AND r.passwordstatus %s
				AND rageid = %d %s %s",
				$this->showPasswords(),
				$rageID,
				$series,
				$episode
			)
		);
	}

	/**
	 * @param int $rageID
	 *
	 * @return int
	 */
	public function removeRageIdFromReleases($rageID)
	{
		$res = $this->pdo->queryOneRow(
			sprintf('SELECT COUNT(r.id) AS num FROM releases r WHERE rageid = %d', $rageID)
		);
		$this->pdo->queryExec(
			sprintf('UPDATE releases SET rageid = -1, seriesfull = NULL, season = NULL, episode = NULL WHERE rageid = %d', $rageID)
		);

		return ($res === false ? 0 : $res['num']);
	}

	/**
	 * @param int $anidbID
	 *
	 * @return mixed
	 */
	public function removeAnidbIdFromReleases($anidbID)
	{
		$res = $this->pdo->queryOneRow(
			sprintf('SELECT COUNT(r.id) AS num FROM releases r WHERE anidbid = %d', $anidbID)
		);
		$this->pdo->queryExec(
			sprintf('UPDATE releases SET anidbid = -1, episode = NULL, tvtitle = NULL, tvairdate = NULL WHERE anidbid = %d', $anidbID)
		);

		return ($res === false ? 0 : $res['num']);
	}

	/**
	 * @param int $id
	 *
	 * @return array|bool
	 */
	public function getById($id)
	{
		return $this->pdo->queryOneRow(
			sprintf(
				'SELECT r.*, g.name AS group_name
				FROM releases r
				INNER JOIN groups g ON g.id = r.group_id
				WHERE r.id = %d',
				$id
			)
		);
	}

	/**
	 * @param int  $id
	 * @param bool $getNfoString
	 *
	 * @return array|bool
	 */
	public function getReleaseNfo($id, $getNfoString = true)
	{
		return $this->pdo->queryOneRow(
			sprintf(
				'SELECT id, releaseid %s FROM releasenfo WHERE releaseid = %d AND nfo IS NOT NULL',
				($getNfoString ? ", UNCOMPRESS(nfo) AS nfo" : ''),
				$id
			)
		);
	}

	/**
	 * @param string $guid
	 */
	public function updateGrab($guid)
	{
		if ($this->updategrabs) {
			$this->pdo->queryExec(
				sprintf('UPDATE releases SET grabs = grabs + 1 WHERE guid = %s', $this->pdo->escapeString($guid))
			);
		}
	}

	/**
	 * @return array
	 */
	public function getTopDownloads()
	{
		return $this->pdo->query(
			'SELECT id, searchname, guid, adddate, SUM(grabs) AS grabs
			FROM releases
			WHERE grabs > 0
			GROUP BY id, searchname, adddate
			HAVING SUM(grabs) > 0
			ORDER BY grabs DESC
			LIMIT 10'
		);
	}

	/**
	 * @return array
	 */
	public function getTopComments()
	{
		return $this->pdo->query(
			'SELECT id, guid, searchname, adddate, SUM(comments) AS comments
			FROM releases
			WHERE comments > 0
			GROUP BY id, searchname, adddate
			HAVING SUM(comments) > 0
			ORDER BY comments DESC
			LIMIT 10'
		);
	}

	public function getRecentlyAdded()
	{
		return $this->pdo->query(
			"SELECT CONCAT(cp.title, ' > ', category.title) AS title, COUNT(*) AS count
			FROM category
			INNER JOIN category cp on cp.id = category.parentid
			INNER JOIN releases r ON r.categoryid = category.id
			WHERE r.adddate > NOW() - INTERVAL 1 WEEK
			GROUP BY concat(cp.title, ' > ', category.title)
			ORDER BY COUNT(*) DESC"
		);
	}

	/**
	 * Get all newest movies with coves for poster wall.
	 *
	 * @return array
	 */
	public function getNewestMovies()
	{
		return $this->pdo->queryDirect(
			"SELECT r.imdbid, r.guid, r.name, r.searchname, r.size, r.completion,
				postdate, categoryid, comments, grabs,
				m.cover
			FROM releases r
			INNER JOIN movieinfo m USING (imdbid)
			WHERE r.categoryid BETWEEN 2000 AND 2999
			AND m.imdbid > 0
			AND m.cover = 1
			GROUP BY m.imdbid
			ORDER BY r.postdate DESC
			LIMIT 24"
		);
	}

	/**
	 * Get all newest xxx with covers for poster wall.
	 *
	 * @return array
	 */
	public function getNewestXXX()
	{
		return $this->pdo->queryDirect(
			"SELECT r.xxxinfo_id, r.guid, r.name, r.searchname, r.size, r.completion,
				r.postdate, r.categoryid, r.comments, r.grabs,
				xxx.cover, xxx.title
			FROM releases r
			INNER JOIN xxxinfo xxx ON r.xxxinfo_id = xxx.id
			WHERE r.categoryid BETWEEN 6000 AND 6040
			AND xxx.id > 0
			AND xxx.cover = 1
			GROUP BY xxx.id
			ORDER BY r.postdate DESC
			LIMIT 24"
		);
	}

	/**
	 * Get all newest console games with covers for poster wall.
	 *
	 * @return array
	 */
	public function getNewestConsole()
	{
		return $this->pdo->queryDirect(
			"SELECT r.consoleinfoid, r.guid, r.name, r.searchname, r.size, r.completion,
				r.postdate, r.categoryid, r.comments, r.grabs,
				con.cover
			FROM releases r
			INNER JOIN consoleinfo con ON r.consoleinfoid = con.id
			WHERE r.categoryid BETWEEN 1000 AND 1999
			AND con.id > 0
			AND con.cover > 0
			GROUP BY con.id
			ORDER BY r.postdate DESC
			LIMIT 35"
		);
	}

	/**
	 * Get all newest PC games with covers for poster wall.
	 *
	 * @return array
	 */
	public function getNewestGames()
	{
		return $this->pdo->queryDirect(
			"SELECT r.gamesinfo_id, r.guid, r.name, r.searchname, r.size, r.completion,
				r.postdate, r.categoryid, r.comments, r.grabs,
				gi.cover
			FROM releases r
			INNER JOIN gamesinfo gi ON r.gamesinfo_id = gi.id
			WHERE r.categoryid = 4050
			AND gi.id > 0
			AND gi.cover > 0
			GROUP BY gi.id
			ORDER BY r.postdate DESC
			LIMIT 35"
		);
	}

	/**
	 * Get all newest music with covers for poster wall.
	 *
	 * @return array
	 */
	public function getNewestMP3s()
	{
		return $this->pdo->queryDirect(
			"SELECT r.musicinfoid, r.guid, r.name, r.searchname, r.size, r.completion,
				r.postdate, r.categoryid, r.comments, r.grabs,
				m.cover
			FROM releases r
			INNER JOIN musicinfo m ON r.musicinfoid = m.id
			WHERE r.categoryid BETWEEN 3000 AND 3999
			AND r.categoryid != 3030
			AND m.id > 0
			AND m.cover > 0
			GROUP BY m.id
			ORDER BY r.postdate DESC
			LIMIT 24"
		);
	}

	/**
	 * Get all newest books with covers for poster wall.
	 *
	 * @return array
	 */
	public function getNewestBooks()
	{
		return $this->pdo->queryDirect(
			"SELECT r.bookinfoid, r.guid, r.name, r.searchname, r.size, r.completion,
				r.postdate, r.categoryid, r.comments, r.grabs,
				b.url,	b.cover, b.title as booktitle, b.author
			FROM releases r
			INNER JOIN bookinfo b ON r.bookinfoid = b.id
			WHERE r.categoryid BETWEEN 8000 AND 8999
			OR r.categoryid = 3030
			AND b.id > 0
			AND b.cover > 0
			GROUP BY b.id
			ORDER BY r.postdate DESC
			LIMIT 24"
		);
	}

	/**
	 * Get all newest TV with covers for poster wall.
	 *
	 * @return array
	 */
	public function getNewestTV()
	{
		return $this->pdo->queryDirect(
			"SELECT r.rageid, r.guid, r.name, r.searchname, r.size, r.completion,
				r.postdate, r.categoryid, r.comments, r.grabs,
				tv.id as tvid, tv.imgdata, tv.releasetitle as tvtitle
			FROM releases r
			INNER JOIN tvrage tv USING (rageid)
			WHERE r.categoryid BETWEEN 5000 AND 5999
			AND tv.rageid > 0
			AND length(tv.imgdata) > 0
			GROUP BY tv.rageid
			ORDER BY r.postdate DESC
			LIMIT 24"
		);
	}

}
