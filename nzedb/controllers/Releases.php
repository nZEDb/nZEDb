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
	 * @var array $options Class instances.
	 */
	public function __construct(array $options = array('Settings' => null, 'Groups' => null))
	{
		$this->pdo = ($options['Settings'] === null ? new Settings() : $options['Settings']);
		$this->groups = ($options['Groups'] === null ? new Groups($this->pdo) : $options['Groups']);
		$this->updategrabs = ($this->pdo->getSetting('grabstatus') == '0' ? false : true);
	}

	/**
	 * @return array
	 */
	public function get()
	{
		return $this->pdo->query('
						SELECT r.*, g.name AS group_name, c.title AS category_name
						FROM releases r
						INNER JOIN category c ON c.id = r.categoryid
						INNER JOIN groups g ON g.id = r.group_id
						WHERE nzbstatus = 1'
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
				WHERE nzbstatus = 1
				ORDER BY postdate DESC %s",
				($start === false ? '' : 'LIMIT ' . $num . ' OFFSET ' . $start)
			)
		);
	}

	/**
	 * Used for paginator.
	 *
	 * @param        $cat
	 * @param        $maxage
	 * @param array  $excludedcats
	 * @param string $grp
	 *
	 * @return mixed
	 */
	public function getBrowseCount($cat, $maxage = -1, $excludedcats = array(), $grp = '')
	{
		$catsrch = $this->categorySQL($cat);

		$exccatlist = $grpjoin = $grpsql = '';

		$maxagesql = (
		$maxage > 0
			? " AND postdate > NOW() - INTERVAL " .
			($this->pdo->dbSystem() === 'mysql' ? $maxage . ' DAY ' : "'" . $maxage . " DAYS' ")
			: ''
		);

		if ($grp != '') {
			$grpjoin = 'INNER JOIN groups g ON g.id = r.group_id';
			$grpsql = sprintf(' AND g.name = %s ', $this->pdo->escapeString($grp));
		}

		if (count($excludedcats) > 0) {
			$exccatlist = ' AND r.categoryid NOT IN (' . implode(',', $excludedcats) . ')';
		}

		$res = $this->pdo->queryOneRow(
						sprintf(
							'SELECT COUNT(r.id) AS num
							FROM releases r %s
							WHERE nzbstatus = 1
							AND r.passwordstatus <= %d %s %s %s %s',
							$grpjoin,
							$this->showPasswords(),
							$catsrch,
							$maxagesql,
							$exccatlist,
							$grpsql
						)
		);
		return ($res === false ? 0 : $res['num']);
	}

	/**
	 * Used for browse results.
	 *
	 * @param        $cat
	 * @param        $start
	 * @param        $num
	 * @param        $orderby
	 * @param        $maxage
	 * @param array  $excludedcats
	 * @param string $grp
	 *
	 * @return array
	 */
	public function getBrowseRange($cat, $start, $num, $orderby, $maxage = -1, $excludedcats = array(), $grp = '')
	{
		$limit = ($start === false ? '' : ' LIMIT ' . $num . ' OFFSET ' . $start);

		$catsrch = $this->categorySQL($cat);

		$grpsql = $exccatlist = '';
		$maxagesql = (
		$maxage > 0
			? " AND postdate > NOW() - INTERVAL " .
			($this->pdo->dbSystem() === 'mysql' ? $maxage . ' DAY ' : "'" . $maxage . " DAYS' ")
			: ''
		);

		if ($grp != '') {
			$grpsql = sprintf(' AND g.name = %s ', $this->pdo->escapeString($grp));
		}

		if (count($excludedcats) > 0) {
			$exccatlist = ' AND r.categoryid NOT IN (' . implode(',', $excludedcats) . ')';
		}

		$order = $this->getBrowseOrder($orderby);

		return $this->pdo->query(
					sprintf(
						"SELECT r.*,
							CONCAT(cp.title, ' > ', c.title) AS category_name,
							CONCAT(cp.id, ',', c.id) AS category_ids,
							g.name AS group_name,
							rn.id AS nfoid,
							re.releaseid AS reid
						FROM releases r
						INNER JOIN groups g ON g.id = r.group_id
						LEFT OUTER JOIN releasevideo re ON re.releaseid = r.id
						LEFT OUTER JOIN releasenfo rn ON rn.releaseid = r.id
						AND rn.nfo IS NOT NULL
						INNER JOIN category c ON c.id = r.categoryid
						INNER JOIN category cp ON cp.id = c.parentid
						WHERE nzbstatus = 1 AND r.passwordstatus <= %d %s %s %s %s
						ORDER BY %s %s %s",
						$this->showPasswords(),
						$catsrch,
						$maxagesql,
						$exccatlist,
						$grpsql,
						$order[0],
						$order[1],
						$limit
			), true
		);
	}

	/**
	 * Return site setting for hiding/showing passworded releases.
	 *
	 * @return int
	 */
	public function showPasswords()
	{
		$res = $this->pdo->queryOneRow(
							"SELECT value
							FROM settings
							WHERE setting = 'showpasswordedrelease'");

		return ($res === false ? 0 : $res['value']);
	}

	/**
	 * Use to order releases on site.
	 *
	 * @param string $orderby
	 *
	 * @return array
	 */
	public function getBrowseOrder($orderby)
	{
		$order = ($orderby == '') ? 'posted_desc' : $orderby;
		$orderArr = explode('_', $order);
		switch ($orderArr[0]) {
			case 'cat':
				$orderfield = 'categoryid';
				break;
			case 'name':
				$orderfield = 'searchname';
				break;
			case 'size':
				$orderfield = 'size';
				break;
			case 'files':
				$orderfield = 'totalpart';
				break;
			case 'stats':
				$orderfield = 'grabs';
				break;
			case 'posted':
			default:
				$orderfield = 'postdate';
				break;
		}
		$ordersort = (isset($orderArr[1]) && preg_match('/^asc|desc$/i', $orderArr[1])) ? $orderArr[1] : 'desc';

		return array($orderfield, $ordersort);
	}

	/**
	 * Return ordering types usable on site.
	 *
	 * @return array
	 */
	public function getBrowseOrdering()
	{
		return array(
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
		);
	}

	/**
	 * Get list of releases avaible for export.
	 *
	 * @param string $postfrom (optional) Date in this format : 01/01/2014
	 * @param string $postto   (optional) Date in this format : 01/01/2014
	 * @param string $group    (optional) Group ID.
	 *
	 * @return array
	 */
	public function getForExport($postfrom = '', $postto = '', $group = '')
	{
		if ($postfrom != '') {
			$dateparts = explode('/', $postfrom);
			if (count($dateparts) == 3) {
				$postfrom = sprintf(
					' AND postdate > %s ',
					$this->pdo->escapeString($dateparts[2] . '-' . $dateparts[1] . '-' . $dateparts[0] . ' 00:00:00')
				);
			} else {
				$postfrom = '';
			}
		}

		if ($postto != '') {
			$dateparts = explode('/', $postto);
			if (count($dateparts) == 3) {
				$postto = sprintf(
					' AND postdate < %s ',
					$this->pdo->escapeString($dateparts[2] . '-' . $dateparts[1] . '-' . $dateparts[0] . ' 23:59:59')
				);
			} else {
				$postto = '';
			}
		}

		if ($group != '' && $group != '-1') {
			$group = sprintf(' AND group_id = %d ', $group);
		} else {
			$group = '';
		}

		return $this->pdo->query(
						sprintf(
								"SELECT searchname, guid, groups.name AS gname, CONCAT(cp.title,'_',category.title) AS catName
								FROM releases r
								INNER JOIN category ON r.categoryid = category.id
								INNER JOIN groups ON r.group_id = groups.id
								INNER JOIN category cp ON cp.id = category.parentid
								WHERE nzbstatus = 1 %s %s %s",
								$postfrom,
								$postto,
								$group
						)
		);
	}

	/**
	 * Get date in this format : 01/01/2014 of the oldest release.
	 *
	 * @return mixed
	 */
	public function getEarliestUsenetPostDate()
	{
		$row = $this->pdo->queryOneRow(
						sprintf(
							"SELECT %s AS postdate FROM releases",
							($this->pdo->dbSystem() === 'mysql'
								? "DATE_FORMAT(min(postdate), '%d/%m/%Y')"
								: "to_char(min(postdate), 'dd/mm/yyyy')"
							)
						)
		);

		return ($row === false ? '01/01/2014' : $row['postdate']);
	}

	/**
	 * Get date in this format : 01/01/2014 of the newest release.
	 *
	 * @return mixed
	 */
	public function getLatestUsenetPostDate()
	{
		$row = $this->pdo->queryOneRow(
						sprintf(
							"SELECT %s AS postdate FROM releases",
							($this->pdo->dbSystem() === 'mysql'
								? "DATE_FORMAT(max(postdate), '%d/%m/%Y')"
								: "to_char(max(postdate), 'dd/mm/yyyy')"
							)
						)
		);

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
		$groups = $this->pdo->query('SELECT DISTINCT g.id, g.name
						FROM releases r
						INNER JOIN groups g ON g.id = r.group_id');
		$temp_array = array();

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
	 * @param     $num
	 * @param int $uid
	 * @param int $rageid
	 * @param int $anidbid
	 * @param int $airdate
	 *
	 * @return array
	 */
	public function getRss($cat, $num, $uid = 0, $rageid, $anidbid, $airdate = -1)
	{
		if ($this->pdo->dbSystem() === 'mysql') {
			$limit = ' LIMIT 0,' . ($num > 100 ? 100 : $num);
		} else {
			$limit = ' LIMIT ' . ($num > 100 ? 100 : $num) . ' OFFSET 0';
		}

		$catsrch = $cartsrch = '';
		if (count($cat) > 0) {
			if ($cat[0] == -2) {
				$cartsrch = sprintf(' INNER JOIN usercart ON usercart.userid = %d AND usercart.releaseid = r.id ', $uid);
			} else if ($cat[0] != -1) {
				$catsrch = ' AND (';
				$categ = new Category();
				foreach ($cat as $category) {
					if ($category != -1) {
						if ($categ->isParent($category)) {
							$children = $categ->getChildren($category);
							$chlist = '-99';
							foreach ($children as $child) {
								$chlist .= ', ' . $child['id'];
							}

							if ($chlist != '-99') {
								$catsrch .= ' r.categoryid IN (' . $chlist . ') OR ';
							}
						} else {
							$catsrch .= sprintf(' r.categoryid = %d OR ', $category);
						}
					}
				}
				$catsrch .= '1=2 )';
			}
		}

		$rage = ($rageid > -1) ? sprintf(' AND r.rageid = %d ', $rageid) : '';
		$anidb = ($anidbid > -1) ? sprintf(' AND r.anidbid = %d ', $anidbid) : '';
		if ($this->pdo->dbSystem() === 'mysql') {
			$airdate = ($airdate >
				-1) ? sprintf(' AND r.tvairdate >= DATE_SUB(CURDATE(), INTERVAL %d DAY) ', $airdate) : '';
		} else {
			$airdate = ($airdate >
				-1) ? sprintf(" AND r.tvairdate >= (CURDATE() - INTERVAL '%d DAYS') ", $airdate) : '';
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
						LEFT OUTER JOIN genres mug ON mug.id = mu.genreid
						LEFT OUTER JOIN consoleinfo co ON co.id = r.consoleinfoid
						LEFT OUTER JOIN genres cog ON cog.id = co.genreid %s
						WHERE r.passwordstatus <= %d %s %s %s %s ORDER BY postdate DESC %s",
						$cartsrch,
						$this->showPasswords(),
						$catsrch,
						$rage,
						$anidb,
						$airdate,
						$limit
					)
		);
	}

	/**
	 * Get TV shows for RSS.
	 *
	 * @param       $num
	 * @param int   $uid
	 * @param array $excludedcats
	 * @param       $airdate
	 *
	 * @return array
	 */
	public function getShowsRss($num, $uid = 0, $excludedcats = array(), $airdate = -1)
	{
		$exccatlist = '';
		if (count($excludedcats) > 0) {
			$exccatlist = ' AND r.categoryid NOT IN (' . implode(',', $excludedcats) . ')';
		}

		$usql = $this->uSQL($this->pdo->query(sprintf('SELECT rageid, categoryid FROM userseries WHERE userid = %d', $uid), true), 'rageid');
		if ($this->pdo->dbSystem() === 'mysql') {
			$airdate = ($airdate >
				-1) ? sprintf(' AND r.tvairdate >= DATE_SUB(CURDATE(), INTERVAL %d DAY) ', $airdate) : '';
		} else {
			$airdate = ($airdate >
				-1) ? sprintf(" AND r.tvairdate >= (CURDATE() - INTERVAL '%d DAYS') ", $airdate) : '';
		}
		$limit = ' LIMIT ' . ($num > 100 ? 100 : $num) . ' OFFSET 0';

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
						AND r.passwordstatus <= %d
						ORDER BY postdate DESC %s",
						$usql,
						$exccatlist,
						$airdate,
						$this->showPasswords(),
						$limit
					)
		);
	}

	/**
	 * Get movies for RSS.
	 *
	 * @param       $num
	 * @param int   $uid
	 * @param array $excludedcats
	 *
	 * @return array
	 */
	public function getMyMoviesRss($num, $uid = 0, $excludedcats = array())
	{
		$exccatlist = '';
		if (count($excludedcats) > 0) {
			$exccatlist = ' AND r.categoryid NOT IN (' . implode(',', $excludedcats) . ')';
		}

		$usql = $this->uSQL($this->pdo->query(sprintf('SELECT imdbid, categoryid FROM usermovies WHERE userid = %d', $uid), true), 'imdbid');
		$limit = ' LIMIT ' . ($num > 100 ? 100 : $num) . ' OFFSET 0';

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
						AND r.passwordstatus <= %d
						ORDER BY postdate DESC %s",
						$usql,
						$exccatlist,
						$this->showPasswords(),
						$limit
			)
		);
	}

	/**
	 * Get TV for my shows page.
	 *
	 * @param       $usershows
	 * @param       $start
	 * @param       $num
	 * @param       $orderby
	 * @param       $maxage
	 * @param array $excludedcats
	 *
	 * @return array
	 */
	public function getShowsRange($usershows, $start, $num, $orderby, $maxage = -1, $excludedcats = array())
	{
		if ($start === false) {
			$limit = '';
		} else {
			$limit = ' LIMIT ' . $num . ' OFFSET ' . $start;
		}

		$exccatlist = $maxagesql = '';
		if (count($excludedcats) > 0) {
			$exccatlist = ' AND r.categoryid NOT IN (' . implode(',', $excludedcats) . ')';
		}

		$usql = $this->uSQL($usershows, 'rageid');

		if ($maxage > 0) {
			if ($this->pdo->dbSystem() === 'mysql') {
				$maxagesql = sprintf(' AND r.postdate > NOW() - INTERVAL %d DAY ', $maxage);
			} else {
				$maxagesql = sprintf(" AND r.postdate > NOW() - INTERVAL '%d DAYS' ", $maxage);
			}
		}

		$order = $this->getBrowseOrder($orderby);

		return $this->pdo->query(
			sprintf(
				"
								SELECT r.*, CONCAT(cp.title, '-', c.title) AS category_name,
									CONCAT(cp.id, ',', c.id) AS category_ids, groups.name AS group_name,
									rn.id AS nfoid, re.releaseid AS reid
								FROM releases r
								LEFT OUTER JOIN releasevideo re ON re.releaseid = r.id
								INNER JOIN groups ON groups.id = r.group_id
								LEFT OUTER JOIN releasenfo rn ON rn.releaseid = r.id AND rn.nfo IS NOT NULL
								INNER JOIN category c ON c.id = r.categoryid
								INNER JOIN category cp ON cp.id = c.parentid
								WHERE %s %s
								AND r.passwordstatus <= %d %s
								ORDER BY %s %s %s",
				$usql, $exccatlist, $this->showPasswords(), $maxagesql, $order[0], $order[1], $limit
			)
		);
	}

	/**
	 * Get count for my shows page pagination.
	 *
	 * @param       $usershows
	 * @param       $maxage
	 * @param array $excludedcats
	 *
	 * @return int
	 */
	public function getShowsCount($usershows, $maxage = -1, $excludedcats = array())
	{
		$exccatlist = $maxagesql = '';
		if (count($excludedcats) > 0) {
			$exccatlist = ' AND r.categoryid NOT IN (' . implode(',', $excludedcats) . ')';
		}

		$usql = $this->uSQL($usershows, 'rageid');

		if ($maxage > 0) {
			if ($this->pdo->dbSystem() === 'mysql') {
				$maxagesql = sprintf(' AND r.postdate > NOW() - INTERVAL %d DAY ', $maxage);
			} else {
				$maxagesql = sprintf(" AND r.postdate > NOW() - INTERVAL '%d DAYS' ", $maxage);
			}
		}

		$res = $this->pdo->queryOneRow(
			sprintf(
				'
								SELECT COUNT(r.id) AS num
								FROM releases r
								WHERE %s %s
								AND r.passwordstatus <= %d %s',
				$usql, $exccatlist, $this->showPasswords(), $maxagesql
			), true
		);

		return ($res === false ? 0 : $res['num']);
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
			$list = array($list);
		}

		$nzb = new NZB($this->pdo);

		foreach ($list as $identifier) {
			if ($isGUID) {
				$this->deleteSingle($identifier, $nzb);
			} else {
				$release = $this->pdo->queryOneRow(sprintf('SELECT guid FROM releases WHERE id = %d', $identifier));
				if ($release === false) {
					continue;
				}
				$this->deleteSingle($release['guid'], $nzb);
			}
		}
	}

	/**
	 * Deletes a single release by GUID, and all the corresponding files.
	 *
	 * @param string $guid Release GUID.
	 * @param NZB    $nzb
	 */
	public function deleteSingle($guid, $nzb = null)
	{
		if ($nzb === null) {
			$nzb = new NZB($this->pdo);
		}
		// Delete NZB from disk.
		$nzbPath = $nzb->getNZBPath($guid);
		if (is_file($nzbPath)) {
			@unlink($nzbPath);
		}

		// Delete images.
		$ri = new ReleaseImage($this->pdo);
		$ri->delete($guid);

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
				$this->pdo->escapeString($guid)
			)
		);
	}

	/**
	 * Used for release edit page on site.
	 *
	 * @param $id
	 * @param $name
	 * @param $searchname
	 * @param $fromname
	 * @param $category
	 * @param $parts
	 * @param $grabs
	 * @param $size
	 * @param $posteddate
	 * @param $addeddate
	 * @param $rageid
	 * @param $seriesfull
	 * @param $season
	 * @param $episode
	 * @param $imdbid
	 * @param $anidbid
	 */
	public function update(
		$id, $name, $searchname, $fromname, $category, $parts, $grabs, $size,
		$posteddate, $addeddate, $rageid, $seriesfull, $season, $episode, $imdbid, $anidbid
	)
	{
		$this->pdo->queryExec(
			sprintf(
				'UPDATE releases ' .
				'SET name = %s, searchname = %s, fromname = %s, categoryid = %d, ' .
					'totalpart = %d, grabs = %d, size = %s, postdate = %s, adddate = %s, rageid = %d, ' .
					'seriesfull = %s, season = %s, episode = %s, imdbid = %d, anidbid = %d ' .
				'WHERE id = %d',
				$this->pdo->escapeString($name),
				$this->pdo->escapeString($searchname),
				$this->pdo->escapeString($fromname),
				$category,
				$parts,
				$grabs,
				$this->pdo->escapeString($size),
				$this->pdo->escapeString($posteddate),
				$this->pdo->escapeString($addeddate),
				$rageid,
				$this->pdo->escapeString($seriesfull),
				$this->pdo->escapeString($season),
				$this->pdo->escapeString($episode),
				$imdbid,
				$anidbid,
				$id
			)
		);
	}

	/**
	 * Used for updating releases on site.
	 *
	 * @param $guids
	 * @param $category
	 * @param $grabs
	 * @param $rageid
	 * @param $season
	 * @param $imdbid
	 *
	 * @return array|bool|int
	 */
	public function updatemulti($guids, $category, $grabs, $rageid, $season, $imdbid)
	{
		if (!is_array($guids) || sizeof($guids) < 1) {
			return false;
		}

		$update = array(
			'categoryid' => (($category == '-1') ? '' : $category),
			'grabs'      => $grabs,
			'rageid'     => $rageid,
			'season'     => $season,
			'imdbid'     => $imdbid
		);

		$updateSql = array();
		foreach ($update as $updk => $updv) {
			if ($updv != '') {
				$updateSql[] = sprintf($updk . '=%s', $this->pdo->escapeString($updv));
			}
		}

		if (count($updateSql) < 1) {
			return -1;
		}

		$updateGuids = array();
		foreach ($guids as $guid) {
			$updateGuids[] = $this->pdo->escapeString($guid);
		}

		return $this->pdo->query(
			sprintf(
				'
								UPDATE releases SET %s WHERE guid IN (%s)',
				implode(', ', $updateSql),
				implode(', ', $updateGuids)
			)
		);
	}

	/**
	 * Creates part of a query for some functions.
	 *
	 * @param $userquery
	 * @param $type
	 *
	 * @return string
	 */
	public function uSQL($userquery, $type)
	{
		$usql = '(1=2 ';
		foreach ($userquery as $u) {
			$usql .= sprintf('OR (r.%s = %d', $type, $u[$type]);
			if ($u['categoryid'] != '') {
				$catsArr = explode('|', $u['categoryid']);
				if (count($catsArr) > 1) {
					$usql .= sprintf(' AND r.categoryid IN (%s)', implode(',', $catsArr));
				} else {
					$usql .= sprintf(' AND r.categoryid = %d', $catsArr[0]);
				}
			}
			$usql .= ') ';
		}
		$usql .= ') ';

		return $usql;
	}

	/**
	 * Creates part of a query for searches based on the type of search.
	 *
	 * @param $search
	 * @param $type
	 *
	 * @return string
	 */
	public function searchSQL($search, $type)
	{
		// If the query starts with a ^ or ! it indicates the search is looking for items which start with the term
		// still do the fulltext match, but mandate that all items returned must start with the provided word.
		$words = explode(' ', $search);

		//only used to get a count of words
		$searchwords = $searchsql = '';
		$intwordcount = 0;

		if (count($words) > 0) {
			if ($type === 'name' || $type === 'searchname') {
				//at least 1 term needs to be mandatory
				if (!preg_match('/[+|!|^]/', $search)) {
					$search = '+' . $search;
					$words = explode(' ', $search);
				}
				foreach ($words as $word) {
					$word = trim(rtrim(trim($word), '-'));
					$word = str_replace('!', '+', $word);
					$word = str_replace('^', '+', $word);
					$word = str_replace("'", "\\'", $word);

					if ($word !== '' && $word !== '-' && strlen($word) >= 2) {
						$searchwords .= sprintf('%s ', $word);
					}
				}
				$searchwords = trim($searchwords);

				$searchsql .= sprintf(" AND MATCH(rs.name, rs.searchname) AGAINST('%s' IN BOOLEAN MODE)",
					$searchwords
				);
			}
			if ($searchwords === '') {
				$words = explode(' ', $search);
				$like = 'ILIKE';
				if ($this->pdo->dbSystem() === 'mysql') {
					$like = 'LIKE';
				}
				foreach ($words as $word) {
					if ($word != '') {
						$word = trim(rtrim(trim($word), '-'));
						if ($intwordcount == 0 && (strpos($word, '^') === 0)) {
							$searchsql .= sprintf(
								' AND r.%s %s %s', $type, $like, $this->pdo->escapeString(
									substr($word, 1) . '%'
								)
							);
						} else if (substr($word, 0, 2) == '--') {
							$searchsql .= sprintf(
								' AND r.%s NOT %s %s', $type, $like, $this->pdo->escapeString(
									'%' . substr($word, 2) . '%'
								)
							);
						} else {
							$searchsql .= sprintf(
								' AND r.%s %s %s', $type, $like, $this->pdo->escapeString(
									'%' . $word . '%'
								)
							);
						}

						$intwordcount++;
					}
				}
			}
		}
		return $searchsql;
	}

	// Creates part of a query for searches requiring the categoryID's.
	public function categorySQL($cat)
	{
		$catsrch = '';
		if (count($cat) > 0 && $cat[0] != -1) {
			$categ = new Category();
			$catsrch = ' AND (';
			foreach ($cat as $category) {
				if ($category != -1) {
					if ($categ->isParent($category)) {
						$children = $categ->getChildren($category);
						$chlist = '-99';
						foreach ($children as $child) {
							$chlist .= ', ' . $child['id'];
						}

						if ($chlist != '-99') {
							$catsrch .= ' r.categoryid IN (' . $chlist . ') OR ';
						}
					} else {
						$catsrch .= sprintf(' r.categoryid = %d OR ', $category);
					}
				}
			}
			$catsrch .= '1=2 )';
		}

		return $catsrch;
	}

	// Function for searching on the site (by subject, searchname or advanced).
	public function search($searchname, $usenetname, $postername, $groupname, $cat = array(-1), $sizefrom, $sizeto, $hasnfo, $hascomments, $daysnew, $daysold, $offset = 0, $limit = 1000, $orderby = '', $maxage = -1, $excludedcats = array(), $type = 'basic')
	{
		if ($type !== 'advanced') {
			$catsrch = $this->categorySQL($cat);
		} else {
			$catsrch = '';
			if ($cat != '-1') {
				$catsrch = sprintf(' AND (r.categoryid = %d) ', $cat);
			}
		}

		$daysnewsql = $daysoldsql = $maxagesql = $groupIDsql = $parentcatsql = '';

		$searchnamesql = ($searchname != '-1' ? $this->searchSQL($searchname, 'searchname') : '');
		$usenetnamesql = ($usenetname != '-1' ? $this->searchSQL($usenetname, 'name') : '');
		$posternamesql = ($postername != '-1' ? $this->searchSQL($postername, 'fromname') : '');
		$hasnfosql = ($hasnfo != '0' ? ' AND r.nfostatus = 1 ' : '');
		$hascommentssql = ($hascomments != '0' ? ' AND r.comments > 0 ' : '');
		$exccatlist = (count($excludedcats) > 0 ?
			' AND r.categoryid NOT IN (' . implode(',', $excludedcats) . ')' : '');

		if ($daysnew != '-1') {
			if ($this->pdo->dbSystem() === 'mysql') {
				$daysnewsql = sprintf(' AND r.postdate < (NOW() - INTERVAL %d DAY) ', $daysnew);
			} else {
				$daysnewsql = sprintf(" AND r.postdate < NOW() - INTERVAL '%d DAYS' ", $daysnew);
			}
		}

		if ($daysold != '-1') {
			if ($this->pdo->dbSystem() === 'mysql') {
				$daysoldsql = sprintf(' AND r.postdate > (NOW() - INTERVAL %d DAY) ', $daysold);
			} else {
				$daysoldsql = sprintf(" AND r.postdate > NOW() - INTERVAL '%d DAYS' ", $daysold);
			}
		}

		if ($maxage > 0) {
			if ($this->pdo->dbSystem() === 'mysql') {
				$maxagesql = sprintf(' AND r.postdate > (NOW() - INTERVAL %d DAY) ', $maxage);
			} else {
				$maxagesql = sprintf(" AND r.postdate > NOW() - INTERVAL '%d DAYS' ", $maxage);
			}
		}

		if ($groupname != '-1') {
			$groupID = $this->groups->getIDByName($groupname);
			$groupIDsql = sprintf(' AND r.group_id = %d ', $groupID);
		}

		$sizefromsql = '';
		switch ($sizefrom) {
			case '1':
			case '2':
			case '3':
			case '4':
			case '5':
			case '6':
			case '7':
			case '8':
			case '9':
			case '10':
			case '11':
				$sizefromsql = ' AND r.size > ' . (string)(104857600 * (int)$sizefrom) . ' ';
				break;
			default:
				break;
		}

		$sizetosql = '';
		switch ($sizeto) {
			case '1':
			case '2':
			case '3':
			case '4':
			case '5':
			case '6':
			case '7':
			case '8':
			case '9':
			case '10':
			case '11':
				$sizetosql = ' AND r.size < ' . (string)(104857600 * (int)$sizeto) . ' ';
				break;
			default:
				break;
		}

		if ($orderby == '') {
			$order[0] = 'postdate ';
			$order[1] = 'desc ';
		} else {
			$order = $this->getBrowseOrder($orderby);
		}

		$sql = sprintf(
			"SELECT * FROM (SELECT r.*, CONCAT(cp.title, ' > ', c.title) AS category_name,
			CONCAT(cp.id, ',', c.id) AS category_ids,
			groups.name AS group_name, rn.id AS nfoid,
			re.releaseid AS reid, cp.id AS categoryparentid
			FROM releases r
			INNER JOIN releasesearch rs on rs.releaseid = r.id
			LEFT OUTER JOIN releasevideo re ON re.releaseid = r.id
			LEFT OUTER JOIN releasenfo rn ON rn.releaseid = r.id
			INNER JOIN groups ON groups.id = r.group_id
			INNER JOIN category c ON c.id = r.categoryid
			INNER JOIN category cp ON cp.id = c.parentid
			WHERE r.passwordstatus <= %d %s %s %s %s %s %s %s %s %s %s %s %s %s) r
			ORDER BY r.%s %s LIMIT %d OFFSET %d",
			$this->showPasswords(), $searchnamesql, $usenetnamesql, $maxagesql, $posternamesql, $groupIDsql, $sizefromsql,
			$sizetosql, $hasnfosql, $hascommentssql, $catsrch, $daysnewsql, $daysoldsql, $exccatlist, $order[0],
			$order[1], $limit, $offset
		);
		$wherepos = strpos($sql, 'WHERE');
		$countres = $this->pdo->queryOneRow(
			'SELECT COUNT(r.id) AS num FROM releases r INNER JOIN releasesearch rs ON rs.releaseid = r.id ' .
			substr($sql, $wherepos, strrpos($sql, ')') - $wherepos)
		);
		$res = $this->pdo->query($sql);
		if (count($res) > 0) {
			$res[0]['_totalrows'] = $countres['num'];
		}

		return $res;
	}

	public function searchbyRageId($rageId, $series = '', $episode = '', $offset = 0, $limit = 100, $name = '', $cat = array(-1), $maxage = -1)
	{
		$rageIdsql = $maxagesql = '';

		if ($rageId != '-1') {
			$rageIdsql = sprintf(' AND rageid = %d ', $rageId);
		}

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

			$like = 'ILIKE';
			if ($this->pdo->dbSystem() === 'mysql') {
				$like = 'LIKE';
			}
			$episode = sprintf(' AND r.episode %s %s', $like, $this->pdo->escapeString('%' . $episode . '%'));
		}

		$searchsql = '';
		if ($name !== '') {
			$searchsql = $this->searchSQL($name, 'searchname');
		}
		$catsrch = $this->categorySQL($cat);

		if ($maxage > 0) {
			if ($this->pdo->dbSystem() === 'mysql') {
				$maxagesql = sprintf(' AND r.postdate > NOW() - INTERVAL %d DAY ', $maxage);
			} else {
				$maxagesql = sprintf(" AND r.postdate > NOW() - INTERVAL '%d DAYS' ", $maxage);
			}
		}
		$sql = sprintf("
					SELECT r.*, concat(cp.title, ' > ', c.title) AS category_name, CONCAT(cp.id, ',', c.id) AS category_ids,
						groups.name AS group_name, rn.id AS nfoid, re.releaseid AS reid
					FROM releases r
					INNER JOIN category c ON c.id = r.categoryid
					INNER JOIN groups ON groups.id = r.group_id
					INNER JOIN releasesearch rs on rs.releaseid = r.id
					LEFT OUTER JOIN releasevideo re ON re.releaseid = r.id
					LEFT OUTER JOIN releasenfo rn ON rn.releaseid = r.id AND rn.nfo IS NOT NULL
					INNER JOIN category cp ON cp.id = c.parentid
					WHERE r.categoryid BETWEEN 5000 AND 5999
					AND nzbstatus = 1
					AND r.passwordstatus <= %d %s %s %s %s %s %s
					ORDER BY postdate DESC
					LIMIT %d
					OFFSET %d",
					$this->showPasswords(),
					$rageIdsql,
					$series,
					$episode,
					$searchsql,
					$catsrch,
					$maxagesql,
					$limit,
					$offset
		);

		$orderpos = strpos($sql, 'ORDER BY');
		$wherepos = strpos($sql, 'WHERE');
		$sqlcount = 'SELECT COUNT(r.id) AS num FROM releases r INNER JOIN releasesearch rs ON rs.releaseid = r.id ' . substr($sql, $wherepos, $orderpos - $wherepos);

		$countres = $this->pdo->queryOneRow($sqlcount);
		$res = $this->pdo->query($sql);
		if (count($res) > 0) {
			$res[0]['_totalrows'] = $countres['num'];
		}

		return $res;
	}

	public function searchbyAnidbId($anidbID, $epno = '', $offset = 0, $limit = 100, $name = '', $cat = array(-1), $maxage = -1)
	{
		$anidbID = ($anidbID > -1) ? sprintf(' AND anidbid = %d ', $anidbID) : '';

		$like = 'ILIKE';
		if ($this->pdo->dbSystem() === 'mysql') {
			$like = 'LIKE';
		}

		is_numeric($epno) ? $epno = sprintf(
			" AND r.episode %s '%s' ", $like, $this->pdo->escapeString(
				'%' . $epno . '%'
			)
		) : $epno = '';

		$searchsql = '';
		if ($name !== '') {
			$searchsql = $this->searchSQL($name, 'searchname');
		}
		$catsrch = $this->categorySQL($cat);

		$maxagesql = '';
		if ($maxage > 0) {
			if ($this->pdo->dbSystem() === 'mysql') {
				$maxagesql = sprintf(' AND r.postdate > NOW() - INTERVAL %d DAY ', $maxage);
			} else {
				$maxagesql = sprintf(" AND r.postdate > NOW() - INTERVAL '%d DAYS' ", $maxage);
			}
		}

		$sql = sprintf("
			SELECT r.*, CONCAT(cp.title, ' > ', c.title) AS category_name, CONCAT(cp.id, ',', c.id) AS category_ids,
				groups.name AS group_name, rn.id AS nfoid
			FROM releases r
			INNER JOIN releasesearch rs on rs.releaseid = r.id
			INNER JOIN category c ON c.id = r.categoryid
			INNER JOIN groups ON groups.id = r.group_id
			LEFT OUTER JOIN releasenfo rn ON rn.releaseid = r.id AND rn.nfo IS NOT NULL
			INNER JOIN category cp ON cp.id = c.parentid
			WHERE r.passwordstatus <= %d %s %s %s %s %s
			ORDER BY postdate DESC LIMIT %d OFFSET %d",
			$this->showPasswords(),
			$anidbID,
			$epno,
			$searchsql,
			$catsrch,
			$maxagesql,
			$limit,
			$offset
		);
		$orderpos = strpos($sql, 'ORDER BY');
		$wherepos = strpos($sql, 'WHERE');
		$sqlcount = 'SELECT COUNT(r.id) AS num FROM releases r INNER JOIN releasesearch rs ON rs.releaseid = r.id ' . substr($sql, $wherepos, $orderpos - $wherepos);

		$countres = $this->pdo->queryOneRow($sqlcount);
		$res = $this->pdo->query($sql);
		if (count($res) > 0) {
			$res[0]['_totalrows'] = $countres['num'];
		}
		return $res;
	}

	public function searchbyImdbId($imdbId, $offset = 0, $limit = 100, $name = '', $cat = array(-1), $maxage = -1)
	{
		if ($imdbId != '-1' && is_numeric($imdbId)) {
			// Pad ID with zeros just in case.
			$imdbId = str_pad($imdbId, 7, '0', STR_PAD_LEFT);
			$imdbId = sprintf(' AND imdbid = %d ', $imdbId);
		} else {
			$imdbId = '';
		}

		$searchsql = '';
		if ($name !== '') {
			$searchsql = $this->searchSQL($name, 'searchname');
		}
		$catsrch = $this->categorySQL($cat);

		if ($maxage > 0) {
			if ($this->pdo->dbSystem() === 'mysql') {
				$maxage = sprintf(' AND r.postdate > NOW() - INTERVAL %d DAY ', $maxage);
			} else {
				$maxage = sprintf(" AND r.postdate > NOW() - INTERVAL '%d DAYS ", $maxage);
			}
		} else {
			$maxage = '';
		}

		$sql = sprintf("
				SELECT r.*, concat(cp.title, ' > ', c.title) AS category_name,
					CONCAT(cp.id, ',', c.id) AS category_ids,
					g.name AS group_name, rn.id AS nfoid
				FROM releases r
				INNER JOIN groups g ON g.id = r.group_id
				INNER JOIN category c ON c.id = r.categoryid
				INNER JOIN releasesearch rs ON rs.releaseid = r.id
				LEFT OUTER JOIN releasenfo rn ON rn.releaseid = r.id AND rn.nfo IS NOT NULL
				INNER JOIN category cp ON cp.id = c.parentid
				WHERE r.categoryid BETWEEN 2000 AND 2999
				AND nzbstatus = 1 AND r.passwordstatus <= %d
				%s %s %s %s ORDER BY postdate DESC LIMIT %d OFFSET %d",
				$this->showPasswords(),
				$searchsql,
				$imdbId,
				$catsrch,
				$maxage,
				$limit,
				$offset
		);

		$orderpos = strpos($sql, 'ORDER BY');
		$wherepos = strpos($sql, 'WHERE');
		$sqlcount = 'SELECT COUNT(r.id) AS num FROM releases r INNER JOIN releasesearch rs ON rs.releaseid = r.id ' . substr($sql, $wherepos, $orderpos - $wherepos);

		$countres = $this->pdo->queryOneRow($sqlcount);
		$res = $this->pdo->query($sql);
		if (count($res) > 0) {
			$res[0]['_totalrows'] = $countres['num'];
		}
		return $res;
	}

	public function searchSimilar($currentid, $name, $limit = 6, $excludedcats = array())
	{
		// Get the category for the parent of this release.
		$currRow = $this->getById($currentid);
		$cat = new Category();
		$catrow = $cat->getById($currRow['categoryid']);
		$parentCat = $catrow['parentid'];

		$name = $this->getSimilarName($name);
		$results = $this->search(
			$name, -1, -1, -1, array($parentCat), -1, -1, 0, 0, -1, -1, 0, $limit, '', -1,
			$excludedcats
		);
		if (!$results) {
			return $results;
		}

		$ret = array();
		foreach ($results as $res) {
			if ($res['id'] != $currentid && $res['categoryparentid'] == $parentCat) {
				$ret[] = $res;
			}
		}

		return $ret;
	}

	public function getSimilarName($name)
	{
		$words = str_word_count(str_replace(array('.', '_'), ' ', $name), 2);
		$firstwords = array_slice($words, 0, 2);

		return implode(' ', $firstwords);
	}

	public function getByGuid($guid)
	{
		if (is_array($guid)) {
			$tmpguids = array();
			foreach ($guid as $g) {
				$tmpguids[] = $this->pdo->escapeString($g);
			}
			$gsql = sprintf('r.guid IN (%s)', implode(',', $tmpguids));
		} else {
			$gsql = sprintf('r.guid = %s', $this->pdo->escapeString($guid));
		}
		$sql = sprintf("
					SELECT r.*, CONCAT(cp.title, ' > ', c.title) AS category_name, CONCAT(cp.id, ',', c.id) AS category_ids,
						g.name AS group_name FROM releases r
					INNER JOIN groups g ON g.id = r.group_id
					INNER JOIN category c ON c.id = r.categoryid
					INNER JOIN category cp ON cp.id = c.parentid
					WHERE %s",
					$gsql
		);

		return (is_array($guid)) ? $this->pdo->query($sql) : $this->pdo->queryOneRow($sql);
	}

	// Writes a zip file of an array of release guids directly to the stream.
	public function getZipped($guids)
	{
		$nzb = new NZB($this->pdo);
		$zipfile = new ZipFile();

		foreach ($guids as $guid) {
			$nzbpath = $nzb->getNZBPath($guid);

			if (is_file($nzbpath)) {
				ob_start();
				@readgzfile($nzbpath);
				$nzbfile = ob_get_contents();
				ob_end_clean();

				$filename = $guid;
				$r = $this->getByGuid($guid);
				if ($r) {
					$filename = $r['searchname'];
				}

				$zipfile->addFile($nzbfile, $filename . '.nzb');
			}
		}

		return $zipfile->file();
	}

	public function getbyRageId($rageid, $series = '', $episode = '')
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
						sprintf("
							SELECT r.*, CONCAT(cp.title, ' > ', c.title) AS category_name,
							groups.name AS group_name
							FROM releases r
							INNER JOIN groups ON groups.id = r.group_id
							INNER JOIN category c ON c.id = r.categoryid
							INNER JOIN category cp ON cp.id = c.parentid
							WHERE r.categoryid BETWEEN 5000 AND 5999
							AND r.passwordstatus <= %d AND rageid = %d %s %s",
							$this->showPasswords(),
							$rageid,
							$series,
							$episode
						)
		);
	}

	public function removeRageIdFromReleases($rageid)
	{
		$res = $this->pdo->queryOneRow(sprintf('SELECT COUNT(r.id) AS num FROM releases r WHERE rageid = %d', $rageid));
		$this->pdo->queryExec(sprintf('UPDATE releases SET rageid = -1, seriesfull = NULL, season = NULL, episode = NULL WHERE rageid = %d', $rageid));

		return $res['num'];
	}

	public function removeAnidbIdFromReleases($anidbID)
	{
		$res = $this->pdo->queryOneRow(sprintf('SELECT COUNT(r.id) AS num FROM releases r WHERE anidbid = %d', $anidbID));
		$this->pdo->queryExec(sprintf('UPDATE releases SET anidbid = -1, episode = NULL, tvtitle = NULL, tvairdate = NULL WHERE anidbid = %d', $anidbID));

		return $res['num'];
	}

	public function getById($id)
	{
		return $this->pdo->queryOneRow(
						sprintf('
							SELECT r.*, g.name AS group_name
							FROM releases r
							INNER JOIN groups g ON g.id = r.group_id
							WHERE r.id = %d',
							$id
						)
		);
	}

	public function getReleaseNfo($id, $incnfo = true)
	{
		if ($this->pdo->dbSystem() === 'mysql') {
			$uc = 'UNCOMPRESS(nfo)';
		} else {
			$uc = 'nfo';
		}
		$selnfo = ($incnfo) ? ", {$uc} AS nfo" : '';

		return $this->pdo->queryOneRow(
			sprintf(
				'SELECT id, releaseid' . $selnfo . ' FROM releasenfo WHERE releaseid = %d AND nfo IS NOT NULL', $id
			)
		);
	}

	public function updateGrab($guid)
	{
		if ($this->updategrabs) {
			$this->pdo->queryExec(sprintf('UPDATE releases SET grabs = grabs + 1 WHERE guid = %s', $this->pdo->escapeString($guid)));
		}
	}

	public function getTopDownloads()
	{
		return $this->pdo->query('SELECT id, searchname, guid, adddate, SUM(grabs) AS grabs FROM releases WHERE grabs > 0 GROUP BY id, searchname, adddate HAVING SUM(grabs) > 0 ORDER BY grabs DESC LIMIT 10');
	}

	public function getTopComments()
	{
		return $this->pdo->query('SELECT id, guid, searchname, adddate, SUM(comments) AS comments FROM releases WHERE comments > 0 GROUP BY id, searchname, adddate HAVING SUM(comments) > 0 ORDER BY comments DESC LIMIT 10');
	}

	public function getRecentlyAdded()
	{
		if ($this->pdo->dbSystem() === 'mysql') {
			return $this->pdo->query("SELECT CONCAT(cp.title, ' > ', category.title) AS title, COUNT(*) AS count FROM category INNER JOIN category cp on cp.id = category.parentid INNER JOIN releases r ON r.categoryid = category.id WHERE r.adddate > NOW() - INTERVAL 1 WEEK GROUP BY concat(cp.title, ' > ', category.title) ORDER BY COUNT(*) DESC");
		} else {
			return $this->pdo->query("SELECT CONCAT(cp.title, ' > ', category.title) AS title, COUNT(*) AS count FROM category INNER JOIN category cp on cp.id = category.parentid INNER JOIN releases r ON r.categoryid = category.id WHERE r.adddate > NOW() - INTERVAL '1 WEEK' GROUP BY concat(cp.title, ' > ', category.title) ORDER BY COUNT(*) DESC");
		}
	}

	/**
	 * Get all newest movies with coves for poster wall.
	 *
	 * @return array
	 */
	public function getNewestMovies()
	{
		return $this->pdo->query(
			"SELECT DISTINCT (a.imdbID),
				guid, name, b.title, searchname, size, completion,
				postdate, categoryid, comments, grabs, c.cover
			FROM releases a, category b, movieinfo c
			WHERE a.categoryid BETWEEN 2000 AND 2999
			AND b.title = 'Movies'
			AND a.imdbid = c.imdbid
			AND a.imdbid !='NULL'
			AND a.imdbid != 0
			AND c.cover = 1
			GROUP BY a.imdbid
			ORDER BY a.postdate
			DESC LIMIT 24"
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
			"SELECT DISTINCT (a.xxxinfo_id),
				guid, name, b.title, searchname, size, completion,
				postdate, categoryid, comments, grabs, c.cover, c.title
			FROM releases a, category b, xxxinfo c
			WHERE a.categoryid BETWEEN 6000 AND 6040
			AND b.title = 'XXX'
			AND a.xxxinfo_id = c.id
			AND a.xxxinfo_id !='NULL'
			AND a.xxxinfo_id != 0
			AND c.cover = 1
			GROUP BY a.xxxinfo_id
			ORDER BY a.postdate
			DESC LIMIT 24"
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
			"SELECT DISTINCT (a.consoleinfoid),
				guid, name, b.title, searchname, size, completion,
				postdate, categoryid, comments, grabs, c.cover
			FROM releases a, category b, consoleinfo c
			WHERE c.cover > 0
			AND a.categoryid BETWEEN 1000 AND 1999
			AND b.title = 'Console'
			AND a.consoleinfoid = c.id
			AND a.consoleinfoid != -2
			AND a.consoleinfoid != 0
			GROUP BY a.consoleinfoid
			ORDER BY a.postdate
			DESC LIMIT 35"
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
			"SELECT DISTINCT (a.gamesinfo_id),
				guid, name, b.title, searchname, size, completion,
				postdate, categoryid, comments, grabs, c.cover
			FROM releases a, category b, gamesinfo c
			WHERE c.cover > 0
			AND a.categoryid = 4050
			AND b.title = 'Games'
			AND a.gamesinfo_id = c.id
			AND a.gamesinfo_id != -2
			AND a.gamesinfo_id != 0
			GROUP BY a.gamesinfo_id
			ORDER BY a.postdate
			DESC LIMIT 35"
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
			"SELECT DISTINCT (a.musicinfoid),
				guid, name, b.title, searchname, size, completion,
				 postdate, categoryid, comments, grabs, c.cover
			FROM releases a, category b, musicinfo c
			WHERE c.cover > 0
			AND a.categoryid BETWEEN 3000 AND 3999
			AND a.categoryid != 3030
			AND b.title = 'Audio'
			AND a.musicinfoid = c.id
			AND a.musicinfoid != -2
			GROUP BY a.musicinfoid
			ORDER BY a.postdate
			DESC LIMIT 24"
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
			"SELECT DISTINCT (a.bookinfoid),
				guid, name, b.title, searchname, size, completion,
				postdate, categoryid, comments, grabs, url, c.cover, c.title as booktitle, c.author
			FROM releases a, category b, bookinfo c
			WHERE c.cover > 0
			AND (a.categoryid BETWEEN 8000 AND 8999 OR a.categoryid = 3030)
			AND (b.title = 'Books' OR b.title = 'Audiobook')
			AND a.bookinfoid = c.id
			AND a.bookinfoid != -2
			GROUP BY a.bookinfoid
			ORDER BY a.postdate
			DESC LIMIT 24"
		);
	}

}
