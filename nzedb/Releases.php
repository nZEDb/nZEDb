<?php
namespace nzedb;

use nzedb\db\Settings;
use nzedb\utility\Misc;

/**
 * Class Releases
 */
class Releases
{
	// RAR/ZIP Passworded indicator.
	const PASSWD_NONE      = 0; // No password.
	const PASSWD_POTENTIAL = 1; // Might have a password.
	const BAD_FILE         = 2; // Possibly broken RAR/ZIP.
	const PASSWD_RAR       = 10; // Definitely passworded.

	/**
	 * @var \nzedb\db\Settings
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
	 * @var string
	 */
	public $showPasswords;

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
		$this->groups = ($options['Groups'] instanceof Groups ? $options['Groups'] : new Groups(['Settings' => $this->pdo]));
		$this->updategrabs = ($this->pdo->getSetting('grabstatus') == '0' ? false : true);
		$this->passwordStatus = ($this->pdo->getSetting('checkpasswordedrar') == 1 ? -1 : 0);
		$this->sphinxSearch = new SphinxSearch();
		$this->releaseSearch = new ReleaseSearch($this->pdo, $this->sphinxSearch);
		$this->showPasswords = self::showPasswords($this->pdo);
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
					(name, searchname, totalpart, group_id, adddate, guid, postdate, fromname,
					size, passwordstatus, haspreview, categories_id, nfostatus, nzbstatus,
					isrenamed, iscategorized, reqidstatus, preid)
				 VALUES (%s, %s, %d, %d, NOW(), %s, %s, %s, %s, %d, -1, %d, -1, %d, %d, 1, %d, %d)",
				$parameters['name'],
				$parameters['searchname'],
				$parameters['totalpart'],
				$parameters['group_id'],
				$parameters['guid'],
				$parameters['postdate'],
				$parameters['fromname'],
				$parameters['size'],
				$this->passwordStatus,
				$parameters['categories_id'],
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
		return bin2hex(openssl_random_pseudo_bytes(20));
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
				INNER JOIN categories c ON c.id = r.categories_id
				INNER JOIN groups g ON g.id = r.group_id
				WHERE r.nzbstatus = %d',
				NZB::NZB_ADDED
			), true, nZEDb_CACHE_EXPIRY_LONG
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
				INNER JOIN categories c ON c.id = r.categories_id
				INNER JOIN categories cp ON cp.id = c.parentid
				WHERE r.nzbstatus = %d
				ORDER BY r.postdate DESC %s",
				NZB::NZB_ADDED,
				($start === false ? '' : 'LIMIT ' . $num . ' OFFSET ' . $start)
			), true, nZEDb_CACHE_EXPIRY_MEDIUM
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
				($groupName != '' ? 'LEFT JOIN groups g ON g.id = r.group_id' : ''),
				NZB::NZB_ADDED,
				$this->showPasswords,
				($groupName != '' ? sprintf(' AND g.name = %s', $this->pdo->escapeString($groupName)) : ''),
				$this->categorySQL($cat),
				($maxAge > 0 ? (' AND r.postdate > NOW() - INTERVAL ' . $maxAge . ' DAY ') : ''),
				(count($excludedCats) ? (' AND r.categories_id NOT IN (' . implode(',', $excludedCats) . ')') : '')
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

		$qry = sprintf(
				"SELECT r.*,
					CONCAT(cp.title, ' > ', c.title) AS category_name,
					CONCAT(cp.id, ',', c.id) AS category_ids,
					df.failed AS failed,
					rn.releaseid AS nfoid,
					re.releaseid AS reid,
					v.tvdb, v.trakt, v.tvrage, v.tvmaze, v.imdb, v.tmdb,
					tve.title, tve.firstaired
				FROM
				(
					SELECT r.*, g.name AS group_name
					FROM releases r
					LEFT JOIN groups g ON g.id = r.group_id
					WHERE r.nzbstatus = %d
					AND r.passwordstatus %s
					%s %s %s %s
					ORDER BY %s %s %s
				) r
				INNER JOIN categories c ON c.id = r.categories_id
				INNER JOIN categories cp ON cp.id = c.parentid
				LEFT OUTER JOIN videos v ON r.videos_id = v.id
				LEFT OUTER JOIN tv_episodes tve ON r.tv_episodes_id = tve.id
				LEFT OUTER JOIN video_data re ON re.releaseid = r.id
				LEFT OUTER JOIN release_nfos rn ON rn.releaseid = r.id
				LEFT OUTER JOIN dnzb_failures df ON df.release_id = r.id
				GROUP BY r.id
				ORDER BY %7\$s %8\$s",
				NZB::NZB_ADDED,
				$this->showPasswords,
				$this->categorySQL($cat),
				($maxAge > 0 ? (" AND postdate > NOW() - INTERVAL " . $maxAge . ' DAY ') : ''),
				(count($excludedCats) ? (' AND r.categories_id NOT IN (' . implode(',', $excludedCats) . ')') : ''),
				($groupName != '' ? sprintf(' AND g.name = %s ', $this->pdo->escapeString($groupName)) : ''),
				$orderBy[0],
				$orderBy[1],
				($start === false ? '' : ' LIMIT ' . $num . ' OFFSET ' . $start)
		);
		$sql = $this->pdo->query($qry, true, nZEDb_CACHE_EXPIRY_MEDIUM);
		return $sql;
	}

	/**
	 * Return site setting for hiding/showing passworded releases.
	 *
	 * @param Settings $pdo
	 *
	 * @return string
	 */
	public static function showPasswords(Settings $pdo)
	{
		$setting = $pdo->query(
			"SELECT value FROM settings WHERE setting = 'showpasswordedrelease'",
			true, nZEDb_CACHE_EXPIRY_LONG
		);
		switch ((isset($setting[0]['value']) && is_numeric($setting[0]['value']) ? $setting[0]['value'] : 10)) {
			case 0: // Hide releases with a password or a potential password (Hide unprocessed releases).
				return ('= ' . Releases::PASSWD_NONE);
			case 1: // Show releases with no password or a potential password (Show unprocessed releases).
				return ('<= ' . Releases::PASSWD_POTENTIAL);
			case 2: // Hide releases with a password or a potential password (Show unprocessed releases).
				return ('<= ' . Releases::PASSWD_NONE);
			case 10: // Shows everything.
			default:
				return ('<= ' . Releases::PASSWD_RAR);
		}
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
				$orderField = 'categories_id';
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
	 * @return string[]
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
				INNER JOIN categories ON r.categories_id = categories_id.id
				INNER JOIN groups ON r.group_id = groups.id
				INNER JOIN categories cp ON cp.id = categories.parentid
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
	 * @note Used for exporting NZB's.
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
	 * @note Used for exporting NZB's.
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
	 * @note Used for exporting NZB's.
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
	 * Cache of concatenated category ID's used in queries.
	 * @var null|array
	 */
	private $concatenatedCategoryIDsCache = null;

	/**
	 * Gets / sets a string of concatenated category ID's used in queries.
	 *
	 * @return array|null
	 */
	public function getConcatenatedCategoryIDs()
	{
		if (is_null($this->concatenatedCategoryIDsCache)) {
			$result = $this->pdo->query(
				"SELECT CONCAT(cp.id, ',', c.id) AS category_ids FROM categories c INNER JOIN categories cp ON cp.id = c.parentid",
				true, nZEDb_CACHE_EXPIRY_LONG
			);
			if (isset($result[0]['category_ids'])) {
				$this->concatenatedCategoryIDsCache = $result[0]['category_ids'];
			}
		}
		return $this->concatenatedCategoryIDsCache;
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
				"SELECT r.*,
					CONCAT(cp.title, '-', c.title) AS category_name,
					%s AS category_ids,
					groups.name AS group_name,
					rn.releaseid AS nfoid, re.releaseid AS reid,
					tve.firstaired,
					df.failed AS failed
				FROM releases r
				LEFT OUTER JOIN video_data re ON re.releaseid = r.id
				INNER JOIN groups ON groups.id = r.group_id
				LEFT OUTER JOIN release_nfos rn ON rn.releaseid = r.id
				LEFT OUTER JOIN tv_episodes tve ON tve.videos_id = r.videos_id
				INNER JOIN categories c ON c.id = r.categories_id
				INNER JOIN categories cp ON cp.id = c.parentid
				LEFT OUTER JOIN dnzb_failures df ON df.release_id = r.id
				WHERE %s %s
				AND r.nzbstatus = %d
				AND r.categories_id BETWEEN %d AND %d
				AND r.passwordstatus %s
				%s
				GROUP BY r.id
				ORDER BY %s %s %s",

				$this->getConcatenatedCategoryIDs(),
				$this->uSQL($userShows, 'videos_id'),
				(count($excludedCats) ? ' AND r.categories_id NOT IN (' . implode(',', $excludedCats) . ')' : ''),
				NZB::NZB_ADDED,
				Category::TV_ROOT,
				Category::TV_OTHER,
				$this->showPasswords,
				($maxAge > 0 ? sprintf(' AND r.postdate > NOW() - INTERVAL %d DAY ', $maxAge) : ''),
				$orderBy[0],
				$orderBy[1],
				($offset === false ? '' : (' LIMIT ' . $limit . ' OFFSET ' . $offset))
			), true, nZEDb_CACHE_EXPIRY_MEDIUM
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
				AND r.categories_id BETWEEN %d AND %d
				AND r.passwordstatus %s
				%s',
				$this->uSQL($userShows, 'videos_id'),
				(count($excludedCats) ? ' AND r.categories_id NOT IN (' . implode(',', $excludedCats) . ')' : ''),
				NZB::NZB_ADDED,
				Category::TV_ROOT,
				Category::TV_OTHER,
				$this->showPasswords,
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

		$nzb = new NZB($this->pdo);
		$releaseImage = new ReleaseImage($this->pdo);

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
				DELETE r, rn, rc, uc, rf, ra, rs, rv, re, df
				FROM releases r
				LEFT OUTER JOIN release_nfos rn ON rn.releaseid = r.id
				LEFT OUTER JOIN release_comments rc ON rc.releaseid = r.id
				LEFT OUTER JOIN users_releases uc ON uc.releaseid = r.id
				LEFT OUTER JOIN release_files rf ON rf.releaseid = r.id
				LEFT OUTER JOIN audio_data ra ON ra.releaseid = r.id
				LEFT OUTER JOIN release_subtitles rs ON rs.releaseid = r.id
				LEFT OUTER JOIN video_data rv ON rv.releaseid = r.id
				LEFT OUTER JOIN releaseextrafull re ON re.releaseid = r.id
				LEFT OUTER JOIN dnzb_failures df ON df.release_id = r.id
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
	 * @param        $videoId
	 * @param        $episodeId
	 * @param int    $imDbID
	 * @param int    $aniDbID
	 *
	 */
	public function update($ID, $name, $searchName, $fromName, $categoryID, $parts, $grabs, $size,
						   $postedDate, $addedDate, $videoId, $episodeId, $imDbID, $aniDbID)
	{
		$this->pdo->queryExec(
			sprintf(
				'UPDATE releases
				SET name = %s, searchname = %s, fromname = %s, categories_id = %d,
					totalpart = %d, grabs = %d, size = %s, postdate = %s, adddate = %s, videos_id = %d,
					tv_episodes_id = %s, imdbid = %d, anidbid = %d
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
				$videoId,
				$episodeId,
				$imDbID,
				$aniDbID,
				$ID
			)
		);
		$this->sphinxSearch->updateRelease($ID, $this->pdo);
	}

	/**
	 * @param $guids
	 * @param $category
	 * @param $grabs
	 * @param $videoId
	 * @param $episodeId
	 * @param $imdbId
	 *
	 * @return array|bool|int
	 */
	public function updateMulti($guids, $category, $grabs, $videoId, $episodeId, $anidbId, $imdbId)
	{
		if (!is_array($guids) || count($guids) < 1) {
			return false;
		}

		$update = [
			'categoryid'     => (($category == '-1') ? 'categoryid' : $category),
			'grabs'          => $grabs,
			'videos_id'      => $videoId,
			'tv_episodes_id' => $episodeId,
			'anidbid'        => $anidbId,
			'imdbid'         => $imdbId
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

		return $this->pdo->queryExec(
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
					$sql .= sprintf(' AND r.categories_id IN (%s)', implode(',', $catsArr));
				} else {
					$sql .= sprintf(' AND r.categories_id = %d', $catsArr[0]);
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
		if (is_array($categories) && $categories[0] != -1) {
			$Category = new Category(['Settings' => $this->pdo]);
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
							$sql .= ' r.categories_id IN (' . $childList . ') OR ';
						}
					} else {
						$sql .= sprintf(' r.categories_id = %d OR ', $category);
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
	 * @param string $fileName
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
	 * @param integer[] $excludedCats
	 * @param string $type
	 * @param array  $cat
	 *
	 * @return array
	 */
	public function search(
		$searchName,
		$usenetName,
		$posterName,
		$fileName,
		$groupName,
		$sizeFrom,
		$sizeTo,
		$hasNfo,
		$hasComments,
		$daysNew,
		$daysOld,
		$offset = 0,
		$limit = 1000,
		$orderBy = '',
		$maxAge = -1,
		$excludedCats = [],
		$type = 'basic',
		$cat = [-1]
	) {
		$sizeRange = [
			1 => 1,
			2 => 2.5,
			3 => 5,
			4 => 10,
			5 => 20,
			6 => 30,
			7 => 40,
			8 => 80,
			9 => 160,
			10 => 320,
			11 => 640,
		];

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
		if ($fileName != -1) {
			$searchOptions['filename'] = $fileName;
		}

		$whereSql = sprintf(
			"%s
			WHERE r.passwordstatus %s AND r.nzbstatus = %d %s %s %s %s %s %s %s %s %s %s %s",
			$this->releaseSearch->getFullTextJoinString(),
			$this->showPasswords,
			NZB::NZB_ADDED,
			($maxAge > 0 ? sprintf(' AND r.postdate > (NOW() - INTERVAL %d DAY) ', $maxAge) : ''),
			($groupName != -1 ? sprintf(' AND r.group_id = %d ', $this->groups->getIDByName($groupName)) : ''),
			(array_key_exists($sizeFrom, $sizeRange) ? ' AND r.size > ' . (string)(104857600 * (int)$sizeRange[$sizeFrom]) . ' ' : ''),
			(array_key_exists($sizeTo, $sizeRange) ? ' AND r.size < ' . (string)(104857600 * (int)$sizeRange[$sizeTo]) . ' ' : ''),
			($hasNfo != 0 ? ' AND r.nfostatus = 1 ' : ''),
			($hasComments != 0 ? ' AND r.comments > 0 ' : ''),
			($type !== 'advanced' ? $this->categorySQL($cat) : ($cat[0] != '-1' ? sprintf(' AND (r.categories_id = %d) ', $cat[0]) : '')),
			($daysNew != -1 ? sprintf(' AND r.postdate < (NOW() - INTERVAL %d DAY) ', $daysNew) : ''),
			($daysOld != -1 ? sprintf(' AND r.postdate > (NOW() - INTERVAL %d DAY) ', $daysOld) : ''),
			(count($excludedCats) > 0 ? ' AND r.categories_id NOT IN (' . implode(',', $excludedCats) . ')' : ''),
			(count($searchOptions) > 0 ? $this->releaseSearch->getSearchSQL($searchOptions) : '')
		);

		$baseSql = sprintf(
			"SELECT r.*,
				CONCAT(cp.title, ' > ', c.title) AS category_name,
				%s AS category_ids,
				df.failed AS failed,
				groups.name AS group_name,
				rn.releaseid AS nfoid,
				re.releaseid AS reid,
				cp.id AS categoryparentid,
				v.tvdb, v.trakt, v.tvrage, v.tvmaze, v.imdb, v.tmdb,
				tve.firstaired
			FROM releases r
			LEFT OUTER JOIN video_data re ON re.releaseid = r.id
			LEFT OUTER JOIN videos v ON r.videos_id = v.id
			LEFT OUTER JOIN tv_episodes tve ON r.tv_episodes_id = tve.id
			LEFT OUTER JOIN release_nfos rn ON rn.releaseid = r.id
			INNER JOIN groups ON groups.id = r.group_id
			INNER JOIN categories c ON c.id = r.categories_id
			INNER JOIN categories cp ON cp.id = c.parentid
			LEFT OUTER JOIN dnzb_failures df ON df.release_id = r.id
			%s",
			$this->getConcatenatedCategoryIDs(),
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
		$releases = $this->pdo->query($sql, true, nZEDb_CACHE_EXPIRY_MEDIUM);
		if (!empty($releases) && count($releases)) {
			$releases[0]['_totalrows'] = $this->getPagerCount($baseSql);
		}
		return $releases;
	}

	/**
	 * @param array  $siteIdArr
	 * @param string $series
	 * @param string $episode
	 * @param string $airdate
	 * @param int    $offset
	 * @param int    $limit
	 * @param string $name
	 * @param array  $cat
	 * @param int    $maxAge
	 *
	 * @return array
	 */
	public function searchShows($siteIdArr = array(), $series = '', $episode = '', $airdate = '', $offset = 0,
								$limit = 100, $name = '', $cat = [-1], $maxAge = -1)
	{
		$siteSQL = array();

		if (is_array($siteIdArr)) {
			foreach ($siteIdArr as $column => $Id) {
				if ($Id > 0) {
					$siteSQL[] = sprintf('v.%s = %d', $column, $Id);
				}
			}
		}

		$siteCount = count($siteSQL);

		$whereSql = sprintf(
			"%s
			WHERE r.categories_id BETWEEN %d AND %d
			AND r.nzbstatus = %d
			AND r.passwordstatus %s
			AND (%s)
			%s %s %s %s %s %s",

			($name !== '' ? $this->releaseSearch->getFullTextJoinString() : ''),
			Category::TV_ROOT,
			Category::TV_OTHER,
			NZB::NZB_ADDED,
			$this->showPasswords,
			($siteCount > 0 ? implode(' OR ', $siteSQL) : '1=1'),
			($series != '' ? sprintf('AND tve.series = %d', (int)preg_replace('/^s0*/i', '', $series)) : ''),
			($episode != '' ? sprintf('AND tve.episode = %d', (int)preg_replace('/^e0*/i', '', $episode)) : ''),
			($airdate != '' ? sprintf('AND DATE(tve.firstaired) = %s', $this->pdo->escapeString($airdate)) : ''),
			($name !== '' ? $this->releaseSearch->getSearchSQL(['searchname' => $name]) : ''),
			$this->categorySQL($cat),
			($maxAge > 0 ? sprintf('AND r.postdate > NOW() - INTERVAL %d DAY', $maxAge) : '')
		);

		$baseSql = sprintf(
			"SELECT r.*,
				v.title, v.countries_id, v.started, v.tvdb, v.trakt,
					v.imdb, v.tmdb, v.tvmaze, v.tvrage, v.source,
				tvi.summary, tvi.publisher, tvi.image,
				tve.series, tve.episode, tve.se_complete, tve.title, tve.firstaired, tve.summary,
				CONCAT(cp.title, ' > ', c.title) AS category_name,
				%s AS category_ids,
				groups.name AS group_name,
				rn.releaseid AS nfoid,
				re.releaseid AS reid
			FROM releases r
			LEFT OUTER JOIN videos v ON r.videos_id = v.id AND v.type = 0
			LEFT OUTER JOIN tv_info tvi ON v.id = tvi.videos_id
			LEFT OUTER JOIN tv_episodes tve ON r.tv_episodes_id = tve.id
			INNER JOIN categories c ON c.id = r.categories_id
			INNER JOIN groups ON groups.id = r.group_id
			LEFT OUTER JOIN video_data re ON re.releaseid = r.id
			LEFT OUTER JOIN release_nfos rn ON rn.releaseid = r.id
			INNER JOIN categories cp ON cp.id = c.parentid
			%s",
			$this->getConcatenatedCategoryIDs(),
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

		$releases = $this->pdo->query($sql, true, nZEDb_CACHE_EXPIRY_MEDIUM);
		return $releases;
	}

	/**
	 * @param        $aniDbID
	 * @param int    $offset
	 * @param int    $limit
	 * @param string $name
	 * @param array  $cat
	 * @param int    $maxAge
	 *
	 * @return array
	 */
	public function searchbyAnidbId($aniDbID, $offset = 0, $limit = 100, $name = '', $cat = [-1], $maxAge = -1)
	{
		$whereSql = sprintf(
			"%s
			WHERE r.passwordstatus %s
			AND r.nzbstatus = %d
			%s %s %s %s",
			($name !== '' ? $this->releaseSearch->getFullTextJoinString() : ''),
			$this->showPasswords,
			NZB::NZB_ADDED,
			($aniDbID > -1 ? sprintf(' AND anidbid = %d ', $aniDbID) : ''),
			($name !== '' ? $this->releaseSearch->getSearchSQL(['searchname' => $name]) : ''),
			$this->categorySQL($cat),
			($maxAge > 0 ? sprintf(' AND r.postdate > NOW() - INTERVAL %d DAY ', $maxAge) : '')
		);

		$baseSql = sprintf(
			"SELECT r.*,
				CONCAT(cp.title, ' > ', c.title) AS category_name,
				%s AS category_ids,
				groups.name AS group_name,
				rn.releaseid AS nfoid,
				re.releaseid AS reid
			FROM releases r
			INNER JOIN categories c ON c.id = r.categories_id
			INNER JOIN groups ON groups.id = r.group_id
			LEFT OUTER JOIN release_nfos rn ON rn.releaseid = r.id
			LEFT OUTER JOIN releaseextrafull re ON re.releaseid = r.id
			INNER JOIN categories cp ON cp.id = c.parentid
			%s",
			$this->getConcatenatedCategoryIDs(),
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
		$releases = $this->pdo->query($sql, true, nZEDb_CACHE_EXPIRY_MEDIUM);

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
			WHERE r.categories_id BETWEEN " . Category::MOVIE_ROOT . " AND " . Category::MOVIE_OTHER . "
			AND r.nzbstatus = %d
			AND r.passwordstatus %s
			%s %s %s %s",
			($name !== '' ? $this->releaseSearch->getFullTextJoinString() : ''),
			NZB::NZB_ADDED,
			$this->showPasswords,
			($name !== '' ? $this->releaseSearch->getSearchSQL(['searchname' => $name]) : ''),
			(($imDbId != '-1' && is_numeric($imDbId)) ? sprintf(' AND imdbid = %d ', str_pad($imDbId, 7, '0', STR_PAD_LEFT)) : ''),
			$this->categorySQL($cat),
			($maxAge > 0 ? sprintf(' AND r.postdate > NOW() - INTERVAL %d DAY ', $maxAge) : '')
		);

		$baseSql = sprintf(
			"SELECT r.*,
				concat(cp.title, ' > ', c.title) AS category_name,
				%s AS category_ids,
				g.name AS group_name,
				rn.releaseid AS nfoid
			FROM releases r
			INNER JOIN groups g ON g.id = r.group_id
			INNER JOIN categories c ON c.id = r.categories_id
			LEFT OUTER JOIN release_nfos rn ON rn.releaseid = r.id
			INNER JOIN categories cp ON cp.id = c.parentid
			%s",
			$this->getConcatenatedCategoryIDs(),
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
		$releases = $this->pdo->query($sql, true, nZEDb_CACHE_EXPIRY_MEDIUM);

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
		$count = $this->pdo->query(
			sprintf(
				'SELECT COUNT(z.id) AS count FROM (%s LIMIT %s) z',
				preg_replace('/SELECT.+?FROM\s+releases/is', 'SELECT r.id FROM releases', $query),
				nZEDb_MAX_PAGER_RESULTS
			)
		);
		return (isset($count[0]['count']) ? $count[0]['count'] : 0);
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
		$catRow = (new Category(['Settings' => $this->pdo]))->getById($currRow['categoryid']);
		$parentCat = $catRow['parentid'];

		$results = $this->search(
			$this->getSimilarName($name), -1, -1, -1, -1, -1, -1, 0, 0, -1, -1, 0, $limit, '', -1, $excludedCats, null, [$parentCat]
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
			"SELECT r.*,
				CONCAT(cp.title, ' > ', c.title) AS category_name,
				CONCAT(cp.id, ',', c.id) AS category_ids,
				g.name AS group_name,
				v.title AS showtitle, v.tvdb, v.trakt, v.tvrage, v.tvmaze, v.source,
				tvi.summary, tvi.image,
				tve.title, tve.firstaired, tve.se_complete
				FROM releases r
			INNER JOIN groups g ON g.id = r.group_id
			INNER JOIN categories c ON c.id = r.categories_id
			INNER JOIN categories cp ON cp.id = c.parentid
			LEFT OUTER JOIN videos v ON r.videos_id = v.id
			LEFT OUTER JOIN tv_info tvi ON r.videos_id = tvi.videos_id
			LEFT OUTER JOIN tv_episodes tve ON r.tv_episodes_id = tve.id
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
		$nzb = new NZB($this->pdo);
		$zipFile = new \ZipFile();

		foreach ($guids as $guid) {
			$nzbPath = $nzb->NZBPath($guid);

			if ($nzbPath) {
				$nzbContents = Misc::unzipGzipFile($nzbPath);

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
	 * @param        $videoId
	 * @param string $series
	 * @param string $episode
	 *
	 * @return array|bool
	 */
	public function getbyVideoId($videoId, $series = '', $episode = '')
	{
		$tvWhere = '';
		if ($series != '') {
			$tvWhere = 'INNER JOIN tv_episodes tve ON r.tv_episodes_id = tve.id';
			$series = sprintf(' AND tve.series = %s', $this->pdo->escapeString($series));
		}

		if ($episode != '') {
			$episode = sprintf(' AND tve.episode = %s', $this->pdo->escapeString($episode));
		}

		return $this->pdo->queryOneRow(
			sprintf(
				"SELECT r.*,
					CONCAT(cp.title, ' > ', c.title) AS category_name,
					g.name AS group_name
				FROM releases r
				INNER JOIN groups g ON g.id = r.group_id
				INNER JOIN categories c ON c.id = r.categories_id
				INNER JOIN categories cp ON cp.id = c.parentid
				INNER JOIN videos v ON r.videos_id = v.id
				%s
				WHERE r.categories_id BETWEEN %d AND %d
				AND r.passwordstatus %s
				AND v.id = %d %s %s",

				$tvWhere,
				Category::TV_ROOT,
				Category::TV_OTHER,
				$this->showPasswords,
				$videoId,
				$series,
				$episode
			)
		);
	}

	/**
	 * Resets the videos_id and tv_episodes_id column on all releases to zero for a given Video ID
	 *
	 * @param $videoId
	 *
	 * @return bool|\PDOStatement
	 */
	public function removeVideoIdFromReleases($videoId)
	{
		return $this->pdo->queryExec(
				sprintf('
					UPDATE releases
					SET videos_id = 0, tv_episodes_id = 0
					WHERE videos_id = %d',
					$videoId
				)
		);
	}

	/**
	 * @param $anidbID
	 *
	 * @return bool|\PDOStatement
	 */
	public function removeAnidbIdFromReleases($anidbID)
	{
		return	$this->pdo->queryExec(
					sprintf('
						UPDATE releases
						SET anidbid = -1
						WHERE anidbid = %d',
						$anidbID
					)
		);
	}

	/**
	 * @param int $id
	 *
	 * @return array|bool
	 */
	public function getById($id)
	{
		$qry = sprintf('
				SELECT r.*, g.name AS group_name
				FROM releases r
				INNER JOIN groups g ON g.id = r.group_id
				WHERE r.id = %d',
				$id
		);
		return $this->pdo->queryOneRow($qry);
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
				'SELECT releaseid %s FROM release_nfos WHERE releaseid = %d AND nfo IS NOT NULL',
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
			LIMIT 10', true, nZEDb_CACHE_EXPIRY_LONG
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
			LIMIT 10', true, nZEDb_CACHE_EXPIRY_LONG
		);
	}

	/**
	 * @return array
	 */
	public function getRecentlyAdded()
	{
		return $this->pdo->query(
			"SELECT CONCAT(cp.title, ' > ', category.title) AS title, COUNT(r.id) AS count
			FROM categories
			INNER JOIN categories cp ON cp.id = categories.parentid
			INNER JOIN releases r ON r.categories_id = categories.id
			WHERE r.adddate > NOW() - INTERVAL 1 WEEK
			GROUP BY CONCAT(cp.title, ' > ', categories.title)
			ORDER BY count DESC", true, nZEDb_CACHE_EXPIRY_MEDIUM
		);
	}

	/**
	 * Get all newest movies with coves for poster wall.
	 *
	 * @return array
	 */
	public function getNewestMovies()
	{
		return $this->pdo->query(
			"SELECT r.imdbid, r.guid, r.name, r.searchname, r.size, r.completion,
				postdate, categories_id, comments, grabs,
				m.cover
			FROM releases r
			INNER JOIN movieinfo m USING (imdbid)
			WHERE r.categories_id BETWEEN " . Category::MOVIE_ROOT . " AND " . Category::MOVIE_OTHER . "
			AND m.imdbid > 0
			AND m.cover = 1
			AND r.id in (select max(id) from releases where imdbid > 0 group by imdbid)
			ORDER BY r.postdate DESC
			LIMIT 24", true, nZEDb_CACHE_EXPIRY_LONG
		);
	}

	/**
	 * Get all newest xxx with covers for poster wall.
	 *
	 * @return array
	 */
	public function getNewestXXX()
	{
		return $this->pdo->query(
			"SELECT r.xxxinfo_id, r.guid, r.name, r.searchname, r.size, r.completion,
				r.postdate, r.categories_id, r.comments, r.grabs,
				xxx.cover, xxx.title
			FROM releases r
			INNER JOIN xxxinfo xxx ON r.xxxinfo_id = xxx.id
			WHERE r.categories_id BETWEEN " . Category::XXX_ROOT . " AND " . Category::XXX_OTHER . "
			AND xxx.id > 0
			AND xxx.cover = 1
			AND r.id in (select max(id) from releases where xxxinfo_id > 0 group by xxxinfo_id)
			ORDER BY r.postdate DESC LIMIT 20",
				true,
				nZEDb_CACHE_EXPIRY_LONG
		);
	}

	/**
	 * Get all newest console games with covers for poster wall.
	 *
	 * @return array
	 */
	public function getNewestConsole()
	{
		return $this->pdo->query(
			"SELECT r.consoleinfoid, r.guid, r.name, r.searchname, r.size, r.completion,
				r.postdate, r.categories_id, r.comments, r.grabs,
				con.cover
			FROM releases r
			INNER JOIN consoleinfo con ON r.consoleinfoid = con.id
			WHERE r.categories_id BETWEEN " . Category::GAME_ROOT . " AND " . Category::GAME_OTHER . "
			AND con.id > 0
			AND con.cover > 0
			AND r.id in (select max(id) from releases where consoleinfoid > 0 group by consoleinfoid)
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
		return $this->pdo->query(
			"SELECT r.gamesinfo_id, r.guid, r.name, r.searchname, r.size, r.completion,
				r.postdate, r.categories_id, r.comments, r.grabs,
				gi.cover
			FROM releases r
			INNER JOIN gamesinfo gi ON r.gamesinfo_id = gi.id
			WHERE r.categories_id = " . Category::PC_GAMES . "
			AND gi.id > 0
			AND gi.cover > 0
			AND r.id in (select max(id) from releases where gamesinfo_id > 0 group by gamesinfo_id)
			ORDER BY r.postdate DESC
			LIMIT 24", true, nZEDb_CACHE_EXPIRY_LONG
		);
	}

	/**
	 * Get all newest music with covers for poster wall.
	 *
	 * @return array
	 */
	public function getNewestMP3s()
	{
		return $this->pdo->query(
			"SELECT r.musicinfoid, r.guid, r.name, r.searchname, r.size, r.completion,
				r.postdate, r.categories_id, r.comments, r.grabs,
				m.cover
			FROM releases r
			INNER JOIN musicinfo m ON r.musicinfoid = m.id
			WHERE r.categories_id BETWEEN " . Category::MUSIC_ROOT . " AND " . Category::MUSIC_OTHER . "
			AND r.categories_id != " . Category::MUSIC_AUDIOBOOK . "
			AND m.id > 0
			AND m.cover > 0
			AND r.id in (select max(id) from releases where musicinfoid > 0 group by musicinfoid)
			ORDER BY r.postdate DESC
			LIMIT 24", true, nZEDb_CACHE_EXPIRY_LONG
		);
	}

	/**
	 * Get all newest books with covers for poster wall.
	 *
	 * @return array
	 */
	public function getNewestBooks()
	{
		return $this->pdo->query(
			"SELECT r.bookinfoid, r.guid, r.name, r.searchname, r.size, r.completion,
				r.postdate, r.categories_id, r.comments, r.grabs,
				b.url,	b.cover, b.title as booktitle, b.author
			FROM releases r
			INNER JOIN bookinfo b ON r.bookinfoid = b.id
			WHERE r.categories_id BETWEEN " . Category::BOOKS_ROOT . " AND " . Category::BOOKS_UNKNOWN . "
			OR r.categories_id = " . Category::MUSIC_AUDIOBOOK . "
			AND b.id > 0
			AND b.cover > 0
			AND r.id in (select max(id) from releases where bookinfoid > 0 group by bookinfoid)
			ORDER BY r.postdate DESC
			LIMIT 24", true, nZEDb_CACHE_EXPIRY_LONG
		);
	}

	/**
	 * Get all newest TV with covers for poster wall.
	 *
	 * @return array
	 */
	public function getNewestTV()
	{
		return $this->pdo->query(
			"SELECT r.videos_id, r.guid, r.name, r.searchname, r.size, r.completion,
				r.postdate, r.categories_id, r.comments, r.grabs,
				v.id AS tvid, v.title AS tvtitle, v.tvdb, v.trakt, v.tvrage, v.tvmaze, v.imdb, v.tmdb,
				tvi.image
			FROM releases r
			INNER JOIN videos v ON r.videos_id = v.id
			INNER JOIN tv_info tvi ON r.videos_id = tvi.videos_id
			WHERE r.categories_id BETWEEN " . Category::TV_ROOT . " AND "	. Category::TV_OTHER .
			" AND v.id > 0
			AND v.type = 0
			AND tvi.image = 1
			AND r.id in (select max(id) from releases where videos_id > 0 group by videos_id)
			ORDER BY r.postdate DESC
			LIMIT 24", true, nZEDb_CACHE_EXPIRY_LONG
		);
	}

	/**
	 * Get all newest anime with covers for poster wall.
	 *
	 * @return array
	 */
	public function getNewestAnime()
	{
		return $this->pdo->query(
			"SELECT r.anidbid, r.guid, r.name, r.searchname, r.size, r.completion,
				r.postdate, r.categories_id, r.comments, r.grabs, at.title
			FROM releases r
			INNER JOIN anidb_titles at USING (anidbid)
			INNER JOIN anidb_info ai USING (anidbid)
			WHERE r.categories_id = " . Category::TV_ANIME . "
			AND at.anidbid > 0
			AND at.lang = 'en'
			AND ai.picture != ''
			AND r.id IN (SELECT MAX(id) FROM releases WHERE anidbid > 0 GROUP BY anidbid)
			GROUP BY r.id
			ORDER BY r.postdate DESC
			LIMIT 24", true, nZEDb_CACHE_EXPIRY_LONG
		);
	}
}
