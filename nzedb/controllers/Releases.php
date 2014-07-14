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
	const PASSWD_NONE = 0; // No password.
	const PASSWD_POTENTIAL = 1; // Might have a password.
	const BAD_FILE = 2; // Possibly broken RAR/ZIP.
	const PASSWD_RAR = 10; // Definitely passworded.

	// Request ID.
	const REQID_NONE = -3; // The Request ID was not found locally or via web lookup.
	const REQID_ZERO = -2; // The Request ID was 0.
	const REQID_NOLL = -1; // Request ID was not found via local lookup.
	const REQID_UPROC = 0; // Release has not been processed.
	const REQID_FOUND = 1; // Request ID found and release was updated.

	// Collections file check status
	const COLLFC_DEFAULT = 0; // Collection has default filecheck status
	const COLLFC_COMPCOLL = 1; // Collection is a complete collection
	const COLLFC_COMPPART = 2; // Collection is a complete collection and has all parts available
	const COLLFC_SIZED = 3; // Collection has been calculated for total size
	const COLLFC_INSERTED = 4; // Collection has been inserted into releases
	const COLLFC_DELETE = 5; // Collection is ready for deletion
	const COLLFC_TEMPCOMP = 15; // Collection is complete and being checked for complete parts
	const COLLFC_ZEROPART = 16; // Collection has a 00/0XX designator (temporary)

	public $pdo;

	/**
	 * @param bool $echooutput
	 */
	public function __construct($echooutput = false)
	{
		$this->echooutput = ($echooutput && nZEDb_ECHOCLI);
		$this->pdo = new Settings();
		$this->groups = new Groups($this->pdo);
		$this->collectionsCleaning = new CollectionsCleaning();
		$this->releaseCleaning = new ReleaseCleaning();
		$this->consoleTools = new ConsoleTools();
		$this->stage5limit = ($this->pdo->getSetting('maxnzbsprocessed') != '') ? (int)$this->pdo->getSetting('maxnzbsprocessed') : 1000;
		$this->completion = ($this->pdo->getSetting('releasecompletion')!= '') ? (int)$this->pdo->getSetting('releasecompletion') : 0;
		$this->crosspostt = ($this->pdo->getSetting('crossposttime')!= '') ? (int)$this->pdo->getSetting('crossposttime') : 2;
		$this->updategrabs = ($this->pdo->getSetting('grabstatus') == '0') ? false : true;
		$this->requestids = $this->pdo->getSetting('lookup_reqids');
		$this->hashcheck = ($this->pdo->getSetting('hashcheck')!= '') ? (int)$this->pdo->getSetting('hashcheck') : 0;
		$this->delaytimet = ($this->pdo->getSetting('delaytime')!= '') ? (int)$this->pdo->getSetting('delaytime') : 2;
		$this->_tablePerGroup = ($this->pdo->getSetting('tablepergroup') == 0 ? false : true);
		$this->c = new ColorCLI();
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
	 * Delete a release or multiple releases.
	 *
	 * @param int|string $id
	 * @param bool       $isGuid
	 */
	public function delete($id, $isGuid = false)
	{
		if (!is_array($id)) {
			$id = array($id);
		}

		foreach ($id as $identifier) {
			if ($isGuid) {
				$rel = $this->getByGuid($identifier);
			} else {
				$rel = $this->getById($identifier);
			}
			$this->fastDelete($rel['id'], $rel['guid']);
		}
	}

	/**
	 * Deletes a single release, and all the corresponding files.
	 *
	 * @param int    $id   release id
	 * @param string $guid release guid
	 */
	public function fastDelete($id, $guid)
	{
		$nzb = new NZB();
		// Delete NZB from disk.
		$nzbpath = $nzb->getNZBPath($guid);
		if (is_file($nzbpath)) {
			@unlink($nzbpath);
		}

		// Delete images.
		$ri = new ReleaseImage();
		$ri->delete($guid);

		// Delete from DB.
		if ($this->pdo->dbSystem() === 'mysql') {
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
					WHERE r.id = %d',
					$id
				)
			);
		} else {
			$this->pdo->queryExec('DELETE FROM releasenfo WHERE releaseid = ' . $id);
			$this->pdo->queryExec('DELETE FROM releasecomment WHERE releaseid = ' . $id);
			$this->pdo->queryExec('DELETE FROM usercart WHERE releaseid = ' . $id);
			$this->pdo->queryExec('DELETE FROM releasefiles WHERE releaseid = ' . $id);
			$this->pdo->queryExec('DELETE FROM releaseaudio WHERE releaseid = ' . $id);
			$this->pdo->queryExec('DELETE FROM releasesubs WHERE releaseid = ' . $id);
			$this->pdo->queryExec('DELETE FROM releasevideo WHERE releaseid = ' . $id);
			$this->pdo->queryExec('DELETE FROM releaseextrafull WHERE releaseid = ' . $id);
			$this->pdo->queryExec('DELETE FROM releases WHERE id = ' . $id);
		}
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
					WHERE r.passwordstatus <= %d %s %s %s %s %s %s
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
		$sqlcount = 'SELECT COUNT(r.id) AS num FROM releases r INNER JOIN releasesearch rs ON rs.releaseid = r.id  ' . substr($sql, $wherepos, $orderpos - $wherepos);

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
				WHERE nzbstatus = 1 AND r.passwordstatus <= %d
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
		$nzb = new NZB();
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
							WHERE r.passwordstatus <= %d AND rageid = %d %s %s",
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

	// Sends releases back to other->misc.
	public function resetCategorize($where = '')
	{
		$this->pdo->queryExec('UPDATE releases SET categoryid = 7010, iscategorized = 0 ' . $where);
	}

	// Categorizes releases.
	// $type = name or searchname
	// Returns the quantity of categorized releases.
	public function categorizeRelease($type, $where = '', $echooutput = false)
	{
		$cat = new Categorize();
		$relcount = 0;
		$resrel = $this->pdo->queryDirect('SELECT id, ' . $type . ', group_id FROM releases ' . $where);
		$total = 0;
		if ($resrel !== false) {
			$total = $resrel->rowCount();
		}
		if ($total > 0) {
			foreach ($resrel as $rowrel) {
				$catId = $cat->determineCategory($rowrel[$type], $rowrel['group_id']);
				$this->pdo->queryExec(sprintf('UPDATE releases SET categoryid = %d, iscategorized = 1 WHERE id = %d', $catId, $rowrel['id']));
				$relcount++;
				if ($this->echooutput) {
					$this->consoleTools->overWritePrimary(
						'Categorizing: ' . $this->consoleTools->percentString($relcount, $total)
					);
				}
			}
		}
		if ($this->echooutput !== false && $relcount > 0) {
			echo "\n";
		}

		return $relcount;
	}

	public function processReleasesStage1($groupID)
	{
		$stage1 = TIME();
		// Set table names
		$group = $this->groups->getCBPTableNames($this->_tablePerGroup, $groupID);

		if ($this->echooutput) {
			$this->c->doEcho($this->c->header("Stage 1 -> Try to find complete collections."));
		}

		$where = (!empty($groupID)) ? ' AND c.group_id = ' . $groupID . ' ' : ' ';

		$start = microtime(true);
		// FIRST QUERY
		// Look if we have all the files in a collection (which have the file count in the subject). Set filecheck to 1.
		$this->pdo->queryExec(
			sprintf('
				UPDATE %s c INNER JOIN
					(SELECT c.id FROM %s c
					INNER JOIN %s b ON b.collectionid = c.id
					WHERE c.totalfiles > 0 AND c.filecheck = 0 %s
					GROUP BY b.collectionid, c.totalfiles, c.id
					HAVING COUNT(b.id) IN (c.totalfiles, c.totalfiles + 1)
					)
				r ON c.id = r.id SET filecheck = %d',
				$group['cname'],
				$group['cname'],
				$group['bname'],
				$where,
				self::COLLFC_COMPCOLL
			)
		);
		/* $this->pdo->queryExec(
			sprintf('
				UPDATE %s c SET filecheck = 1
				WHERE c.id IN
					(SELECT b.collectionid FROM %s b, %s c
					WHERE b.collectionid = c.id
					GROUP BY b.collectionid, c.totalfiles
					HAVING (COUNT(b.id) >= c.totalfiles-1)
					)
				AND c.totalfiles > 0 AND c.filecheck = %d %s',
				$group['cname'],
				$group['bname'],
				$group['cname'],
				self::COLLFC_COMPCOLL,
				$where
			)
		);
		*/
		$firstQuery = microtime(true);

		// Set filecheck to 16 if theres a file that starts with 0 (ex. [00/100]).
		// SECOND QUERY
		$this->pdo->queryExec(
			sprintf('
				UPDATE %s c INNER JOIN
					(SELECT c.id FROM %s c
					INNER JOIN %s b ON b.collectionid = c.id
					WHERE b.filenumber = 0
					AND c.totalfiles > 0
					AND c.filecheck = 1 %s
					GROUP BY c.id
					)
				r ON c.id = r.id SET c.filecheck = %d',
				$group['cname'],
				$group['cname'],
				$group['bname'],
				$where,
				self::COLLFC_ZEROPART
			)
		);
		$secondQuery = microtime(true);

		// Set filecheck to 15 on everything left over, so anything that starts with 1 (ex. [01/100]).
		// THIRD QUERY
		$this->pdo->queryExec(
			sprintf('
				UPDATE %s c
				SET filecheck = %d
				WHERE filecheck = %d %s',
				$group['cname'],
				self::COLLFC_TEMPCOMP,
				self::COLLFC_COMPCOLL,
				$where
			)
		);
		$thirdQuery = microtime(true);

		// If we have all the parts set partcheck to 1.
		// If filecheck 15, check if we have all the parts for a file then set partcheck.
		// FOURTH QUERY
		$this->pdo->queryExec(
			sprintf('
				UPDATE %s b INNER JOIN
					(SELECT b.id FROM %s b
					INNER JOIN %s c ON c.id = b.collectionid
					WHERE c.filecheck = %d AND b.partcheck = 0 %s
					AND b.currentparts = b.totalparts
					GROUP BY b.id, b.totalparts)
				r ON b.id = r.id SET b.partcheck = 1',
				$group['bname'],
				$group['bname'],
				$group['cname'],
				self::COLLFC_TEMPCOMP,
				$where
			)
		);
		$fourthQuery = microtime(true);

		// If filecheck 16, check if we have all the parts+1(because of the 0) then set partcheck.
		// FIFTH QUERY
		$this->pdo->queryExec(
			sprintf('
				UPDATE %s b INNER JOIN
					(SELECT b.id FROM %s b
					INNER JOIN %s c ON c.id = b.collectionid
					WHERE c.filecheck = %d AND b.partcheck = 0 %s
					AND b.currentparts >= (b.totalparts + 1)
					GROUP BY b.id, b.totalparts)
				r ON b.id = r.id SET b.partcheck = 1',
				$group['bname'],
				$group['bname'],
				$group['cname'],
				self::COLLFC_ZEROPART,
				$where
			)
		);
		$fifthQuery = microtime(true);

		// Set filecheck to 2 if partcheck = 1.
		// SIXTH QUERY
		$this->pdo->queryExec(
			sprintf('
				UPDATE %s c INNER JOIN
					(SELECT c.id FROM %s c
					INNER JOIN %s b ON c.id = b.collectionid
					WHERE b.partcheck = 1 AND c.filecheck IN (%d, %d) %s
					GROUP BY b.collectionid, c.totalfiles, c.id HAVING COUNT(b.id) >= c.totalfiles)
				r ON c.id = r.id SET filecheck = %d',
				$group['cname'],
				$group['cname'],
				$group['bname'],
				self::COLLFC_TEMPCOMP,
				self::COLLFC_ZEROPART,
				$where,
				self::COLLFC_COMPPART
			)
		);
		$sixthQuery = microtime(true);

		// Set filecheck to 1 if we don't have all the parts.
		// SEVENTH QUERY
		$this->pdo->queryExec(
			sprintf('
				UPDATE %s c
				SET filecheck = %d
				WHERE filecheck IN (%d, %d) %s',
				$group['cname'],
				self::COLLFC_COMPCOLL,
				self::COLLFC_TEMPCOMP,
				self::COLLFC_ZEROPART,
				$where
			)
		);
		$seventhQuery = microtime(true);

		// If a collection has not been updated in X hours, set filecheck to 2.
		// EIGHTH QUERY
		$query = $this->pdo->queryExec(
			sprintf("
				UPDATE %s c SET filecheck = %d, totalfiles = (SELECT COUNT(b.id) FROM %s b WHERE b.collectionid = c.id)
				WHERE c.dateadded < NOW() - INTERVAL '%d' HOUR
				AND c.filecheck IN (%d, %d, 10) %s",
				$group['cname'],
				self::COLLFC_COMPPART,
				$group['bname'],
				$this->delaytimet,
				self::COLLFC_DEFAULT,
				self::COLLFC_COMPCOLL,
				$where
			)
		);
		$eighthQuery = microtime(true);

		if ($query !== false && $this->echooutput) {
			$this->c->doEcho(
				$this->c->primary(
					$query->rowCount() + $query->rowCount() .
					" collections set to filecheck = 2 (complete)"
				)
			);
			$this->c->doEcho($this->c->primary($this->consoleTools->convertTime(TIME() - $stage1)), true);
		}

		if (nZEDb_DEBUG && nZEDb_LOGINFO) {
			echo (
				'1st query: ' . ($firstQuery - $start) . 's ' . PHP_EOL .
				'2nd query: ' . ($secondQuery - $firstQuery) . 's ' . PHP_EOL .
				'3rd query: ' . ($thirdQuery - $secondQuery) . 's ' . PHP_EOL .
				'4th query: ' . ($fourthQuery - $thirdQuery) . 's ' . PHP_EOL .
				'5th query: ' . ($fifthQuery - $fourthQuery) . 's ' . PHP_EOL .
				'6th query: ' . ($sixthQuery - $fifthQuery) . 's ' . PHP_EOL .
				'7th query: ' . ($seventhQuery - $sixthQuery) . 's ' . PHP_EOL .
				'8th query: ' . ($eighthQuery - $seventhQuery) . 's ' . PHP_EOL
			);
		}
	}

	public function processReleasesStage2($groupID)
	{
		$where = (!empty($groupID)) ? ' AND c.group_id = ' . $groupID : ' ';

		// Set table names
		$group = $this->groups->getCBPTableNames($this->_tablePerGroup, $groupID);

		if ($this->echooutput) {
			$this->c->doEcho($this->c->header("Stage 2 -> Get the size in bytes of the collection."));
		}

		$stage2 = TIME();
		// Get the total size in bytes of the collection for collections where filecheck = 2.
		$checked = $this->pdo->queryExec(
			sprintf(
				'UPDATE %s c
				SET filesize = (SELECT COALESCE(SUM(b.partsize), 0) FROM %s b WHERE b.collectionid = c.id),
				filecheck = %d
				WHERE c.filecheck = %d
				AND c.filesize = 0 %s',
				$group['cname'],
				$group['bname'],
				self::COLLFC_SIZED,
				self::COLLFC_COMPPART,
				$where
			)
		);
		if ($checked !== false && $this->echooutput) {
			$this->c->doEcho(
				$this->c->primary(
					$checked->rowCount() . " collections set to filecheck = 3(size calculated)"
				)
			);
			$this->c->doEcho($this->c->primary($this->consoleTools->convertTime(TIME() - $stage2)), true);
		}
	}

	public function processReleasesStage3($groupID)
	{
		$minsizecounts = $maxsizecounts = $minfilecounts = 0;

		// Set table names
		$group = $this->groups->getCBPTableNames($this->_tablePerGroup, $groupID);

		if ($this->echooutput) {
			$this->c->doEcho($this->c->header("Stage 3 -> Delete collections smaller/larger than minimum size/file count from group/site setting."));
		}
		$stage3 = TIME();

		if ($groupID == '') {
			$groupIDs = $this->groups->getActiveIDs();
			foreach ($groupIDs as $groupID) {
				$res = $this->pdo->query(
					'SELECT id FROM ' . $group['cname'] . ' WHERE filecheck = 3 AND filesize > 0 AND group_id = ' .
					$groupID['id']
				);
				if (count($res) > 0) {
					$minsizecount = 0;
					if ($this->pdo->dbSystem() === 'mysql') {
						$mscq = $this->pdo->queryExec(
							"UPDATE " . $group['cname'] .
							" c LEFT JOIN (SELECT g.id, COALESCE(g.minsizetoformrelease, s.minsizetoformrelease) AS minsizetoformrelease FROM groups g INNER JOIN ( SELECT value AS minsizetoformrelease FROM settings WHERE setting = 'minsizetoformrelease' ) s ) g ON g.id = c.group_id SET c.filecheck = 5 WHERE g.minsizetoformrelease != 0 AND c.filecheck = 3 AND c.filesize < g.minsizetoformrelease AND c.filesize > 0 AND group_id = " .
							$groupID['id']
						);
						if ($mscq !== false) {
							$minsizecount = $mscq->rowCount();
						}
					} else {
						$s = $this->pdo->queryOneRow(
							"SELECT GREATEST(s.value::integer, g.minsizetoformrelease::integer) as size FROM settings s, groups g WHERE s.setting = 'minsizetoformrelease' AND g.id = " .
							$groupID['id']
						);
						if ($s['size'] > 0) {
							$mscq = $this->pdo->queryExec(
								sprintf(
									'UPDATE ' . $group['cname'] .
									' SET filecheck = 5 WHERE filecheck = 3 AND filesize < %d AND filesize > 0 AND group_id = ' .
									$groupID['id'], $s['size']
								)
							);
							if ($mscq !== false) {
								$minsizecount = $mscq->rowCount();
							}
						}
					}
					if ($minsizecount < 1) {
						$minsizecount = 0;
					}
					$minsizecounts = $minsizecount + $minsizecounts;

					$maxfilesizeres = $this->pdo->queryOneRow("SELECT value FROM settings WHERE setting = 'maxsizetoformrelease'");
					if ($maxfilesizeres['value'] != 0) {
						$mascq = $this->pdo->queryExec(
							sprintf(
								'UPDATE ' . $group['cname'] .
								' SET filecheck = 5 WHERE filecheck = 3 AND group_id = %d AND filesize > %d ', $groupID['id'], $maxfilesizeres['value']
							)
						);
						$maxsizecount = 0;
						if ($mascq !== false) {
							$maxsizecount = $mascq->rowCount();
						}
						if ($maxsizecount < 1) {
							$maxsizecount = 0;
						}
						$maxsizecounts = $maxsizecount + $maxsizecounts;
					}

					$minfilecount = 0;
					if ($this->pdo->dbSystem() === 'mysql') {
						$mifcq = $this->pdo->queryExec(
							"UPDATE " . $group['cname'] .
							" c LEFT JOIN (SELECT g.id, COALESCE(g.minfilestoformrelease, s.minfilestoformrelease) AS minfilestoformrelease FROM groups g INNER JOIN ( SELECT value AS minfilestoformrelease FROM settings WHERE setting = 'minfilestoformrelease' ) s ) g ON g.id = c.group_id SET c.filecheck = 5 WHERE g.minfilestoformrelease != 0 AND c.filecheck = 3 AND c.totalfiles < g.minfilestoformrelease AND group_id = " .
							$groupID['id']
						);
						if ($mifcq !== false) {
							$minfilecount = $mifcq->rowCount();
						}
					} else {
						$f = $this->pdo->queryOneRow(
							"SELECT GREATEST(s.value::integer, g.minfilestoformrelease::integer) as files FROM settings s, groups g WHERE s.setting = 'minfilestoformrelease' AND g.id = " .
							$groupID['id']
						);
						if ($f['files'] > 0) {
							$mifcq = $this->pdo->queryExec(
								sprintf(
									'UPDATE ' . $group['cname'] .
									' SET filecheck = 5 WHERE filecheck = 3 AND filesize < %d AND filesize > 0 AND group_id = ' .
									$groupID['id'], $f['size']
								)
							);
							if ($mifcq !== false) {
								$minfilecount = $mifcq->rowCount();
							}
						}
					}
					if ($minfilecount < 1) {
						$minfilecount = 0;
					}
					$minfilecounts = $minfilecount + $minfilecounts;
				}
			}
		} else {
			$res = $this->pdo->queryDirect(
				'SELECT id FROM ' . $group['cname'] . ' WHERE filecheck = 3 AND filesize > 0 AND group_id = ' . $groupID
			);
			if ($res !== false && $res->rowCount() > 0) {
				$minsizecount = 0;
				if ($this->pdo->dbSystem() === 'mysql') {
					$mscq = $this->pdo->queryExec(
						"UPDATE " . $group['cname'] .
						" c LEFT JOIN (SELECT g.id, coalesce(g.minsizetoformrelease, s.minsizetoformrelease) AS minsizetoformrelease FROM groups g INNER JOIN ( SELECT value AS minsizetoformrelease FROM settings WHERE setting = 'minsizetoformrelease' ) s ) g ON g.id = c.group_id SET c.filecheck = 5 WHERE g.minsizetoformrelease != 0 AND c.filecheck = 3 AND c.filesize < g.minsizetoformrelease AND c.filesize > 0 AND group_id = " .
						$groupID
					);
					if ($mscq !== false) {
						$minsizecount = $mscq->rowCount();
					}
				} else {
					$s = $this->pdo->queryOneRow(
						"SELECT GREATEST(s.value::integer, g.minsizetoformrelease::integer) as size FROM settings s, groups g WHERE s.setting = 'minsizetoformrelease' AND g.id = " .
						$groupID
					);
					if ($s['size'] > 0) {
						$mscq = $this->pdo->queryExec(
							sprintf(
								'UPDATE ' . $group['cname'] .
								' SET filecheck = 5 WHERE filecheck = 3 AND filesize < %d AND filesize > 0 AND group_id = ' .
								$groupID, $s['size']
							)
						);
						if ($mscq !== false) {
							$minsizecount = $mscq->rowCount();
						}
					}
				}
				if ($minsizecount < 0) {
					$minsizecount = 0;
				}
				$minsizecounts = $minsizecount + $minsizecounts;

				$maxfilesizeres = $this->pdo->queryOneRow("SELECT value FROM settings WHERE setting = 'maxsizetoformrelease'");
				if ($maxfilesizeres['value'] != 0) {
					$mascq = $this->pdo->queryExec(
						sprintf(
							'UPDATE ' . $group['cname'] .
							' SET filecheck = 5 WHERE filecheck = 3 AND filesize > %d ', $maxfilesizeres['value']
						)
					);
					$maxsizecount = 0;
					if ($mascq !== false) {
						$maxsizecount = $mascq->rowCount();
					}
					if ($maxsizecount < 0) {
						$maxsizecount = 0;
					}
					$maxsizecounts = $maxsizecount + $maxsizecounts;
				}

				$minfilecount = 0;
				if ($this->pdo->dbSystem() === 'mysql') {
					$mifcq = $this->pdo->queryExec(
						"UPDATE " . $group['cname'] .
						" c LEFT JOIN (SELECT g.id, coalesce(g.minfilestoformrelease, s.minfilestoformrelease) AS minfilestoformrelease FROM groups g INNER JOIN ( SELECT value AS minfilestoformrelease FROM settings WHERE setting = 'minfilestoformrelease' ) s ) g ON g.id = c.group_id SET c.filecheck = 5 WHERE g.minfilestoformrelease != 0 AND c.filecheck = 3 AND c.totalfiles < g.minfilestoformrelease AND group_id = " .
						$groupID
					);
					if ($mifcq !== false) {
						$minfilecount = $mifcq->rowCount();
					}
				} else {
					$f = $this->pdo->queryOneRow(
						"SELECT GREATEST(s.value::integer, g.minfilestoformrelease::integer) as files FROM settings s, groups g WHERE s.setting = 'minfilestoformrelease' AND g.id = " .
						$groupID
					);
					if ($f['files'] > 0) {
						$mifcq = $this->pdo->queryExec(
							sprintf(
								'UPDATE ' . $group['cname'] .
								' SET filecheck = 5 WHERE filecheck = 3 AND filesize < %d AND filesize > 0 AND group_id = ' .
								$groupID, $f['files']
							)
						);
						if ($mifcq !== false) {
							$minfilecount = $mifcq->rowCount();
						}
					}
				}
				if ($minfilecount < 0) {
					$minfilecount = 0;
				}
				$minfilecounts = $minfilecount + $minfilecounts;
			}
		}

		$delcount = $minsizecounts + $maxsizecounts + $minfilecounts;
		if ($this->echooutput && $delcount > 0) {
			$this->c->doEcho(
				$this->c->primary(
					'Deleted ' .
					number_format($delcount) .
					" collections smaller/larger than group/site settings."
				)
			);
		}
		if ($this->echooutput) {
			$this->c->doEcho($this->c->primary($this->consoleTools->convertTime(TIME() - $stage3)), true);
		}
	}

	public function processReleasesStage4($groupID)
	{
		$categorize = new Categorize();
		$returnCount = $duplicate = 0;
		$where = (!empty($groupID)) ? ' group_id = ' . $groupID . ' AND ' : ' ';

		// Set table names
		$group = $this->groups->getCBPTableNames($this->_tablePerGroup, $groupID);

		if ($this->echooutput) {
			$this->c->doEcho($this->c->header("Stage 4 -> Create releases."));
		}

		$stage4 = TIME();

		$collections = $this->pdo->queryDirect(
			sprintf('
				SELECT %s.*, groups.name AS gname
				FROM %s
				INNER JOIN groups ON %s.group_id = groups.id
				WHERE %s %s.filecheck = %d
				AND filesize > 0 LIMIT %d',
				$group['cname'],
				$group['cname'],
				$group['cname'],
				$where,
				$group['cname'],
				self::COLLFC_SIZED,
				$this->stage5limit
			)
		);

		if ($collections !== false && $this->echooutput) {
			echo $this->c->primary($collections->rowCount() . " Collections ready to be converted to releases.");
		}

		if ($collections !== false && $collections->rowCount() > 0) {
			$preDB = new PreDb($this->echooutput);

			$checkPasswords = ($this->pdo->getSetting('checkpasswordedrar') == '1' ? -1 : 0);

			foreach ($collections as $collection) {

				$properName = true;
				$releaseID = $isReqID = false;
				$preID = null;

				$cleanRelName = str_replace(array('#', '@', '$', '%', '^', '§', '¨', '©', 'Ö'), '', $collection['subject']);

				$cleanerName = $this->releaseCleaning->releaseCleaner(
					$collection['subject'], $collection['fromname'], $collection['filesize'], $collection['gname']
				);

				$fromName = trim($collection['fromname'], "'");

				if (!is_array($cleanerName)) {
					$cleanName = $cleanerName;
				} else {

					$cleanName = $cleanerName['cleansubject'];
					$properName = $cleanerName['properlynamed'];

					if (isset($cleanerName['predb'])) {
						$preID = $cleanerName['predb'];
					}

					if (isset($cleanerName['requestid'])) {
						$isReqID = $cleanerName['requestid'];
					}
				}

				if ($preID === null && $cleanName != '') {
					// try to match the cleaned searchname to predb title or filename here
					$preMatch = $preDB->matchPre($cleanName);
					if ($preMatch !== false) {
						$cleanName = $preMatch['title'];
						$preID = $preMatch['preid'];
						$properName = true;
					}
				}

				$category = $categorize->determineCategory($cleanName, $collection['group_id']);

				$cleanRelName = utf8_encode($cleanRelName);
				$cleanName = utf8_encode($cleanName);
				$fromName = utf8_encode($fromName);

				// Look for duplicates, duplicates match on releases.name, releases.fromname and releases.size
				// A 1% variance in size is considered the same size when the subject and poster are the same
				$dupeCheck = $this->pdo->queryOneRow(
					sprintf('
						SELECT id, guid
						FROM releases
						WHERE name = %s
						AND fromname = %s
						AND size BETWEEN %s
						AND %s',
						$this->pdo->escapeString($cleanRelName),
						$this->pdo->escapeString($fromName),
						$this->pdo->escapeString($collection['filesize'] * .99),
						$this->pdo->escapeString($collection['filesize'] * 1.01)
					)
				);

				if (!$dupeCheck) {
					$query = 'INSERT INTO releases (';
					$query .= ($properName === true ? 'isrenamed, ' : '');
					$query .= ($preID !== null ? 'preid, ' : '');
					$query .= ($isReqID === true ? 'reqidstatus, ' : '');
					$query .= 'name, searchname, totalpart, group_id, adddate, guid, rageid, postdate, fromname, ';
					$query .= 'size, passwordstatus, haspreview, categoryid, nfostatus, iscategorized) VALUES (';
					$query .= ($properName === true ? '1, ' : '');
					$query .= ($preID !== null ? $preID . ', ' : '');
					$query .= ($isReqID == true ? '1, ' : '');
					$query .= '%s, %s, %d, %d, NOW(), %s, -1, %s, %s, %s, %d, -1, %d, -1, 1)';

					$this->pdo->ping(true);

					$releaseID = $this->pdo->queryInsert(
						sprintf(
							$query,
							$this->pdo->escapeString($cleanRelName),
							$this->pdo->escapeString($cleanName),
							$collection['totalfiles'],
							$collection['group_id'],
							$this->pdo->escapeString(sha1(uniqid('', true) . mt_rand())),
							$this->pdo->escapeString($collection['date']),
							$this->pdo->escapeString($fromName),
							$this->pdo->escapeString($collection['filesize']),
							$checkPasswords,
							$category
						)
					);
				}

				if ($releaseID) {
					// Update collections table to say we inserted the release.
					$this->pdo->queryExec(
						sprintf('
							UPDATE %s
							SET filecheck = 4, releaseid = %d
							WHERE id = %d',
							$group['cname'],
							$releaseID,
							$collection['id']
						)
					);

					$returnCount++;

					if ($this->echooutput) {
						echo $this->c->primary('Added release ' . $cleanName);
					}

				} else if ($releaseID === false) {
					$this->pdo->queryExec(
						sprintf('
							DELETE FROM %s
							WHERE collectionhash = %s',
							$group['cname'],
							$this->pdo->escapeString($collection['collectionhash'])
						)
					);
					$duplicate++;
				}
			}
		}

		if ($this->echooutput) {
			$this->c->doEcho(
				$this->c->primary(
					number_format($returnCount) .
					' Releases added and ' .
					number_format($duplicate) .
					' marked for deletion in ' .
					$this->consoleTools->convertTime(TIME() - $stage4)
				), true
			);
		}

		return $returnCount;
	}

	/*
	 * 	Adding this in to delete releases before NZB's are created.
	 */
	public function processReleasesStage4dot5($groupID)
	{
		$minsizecount = $maxsizecount = $minfilecount = $catminsizecount = 0;

		if ($this->echooutput) {
			echo $this->c->header("Stage 4.5 -> Delete releases smaller/larger than minimum size/file count from group/site setting.");
		}

		$stage4dot5 = TIME();
		// Delete smaller than min sizes
		$catresrel = $this->pdo->queryDirect('SELECT c.id AS id, CASE WHEN c.minsize = 0 THEN cp.minsize ELSE c.minsize END AS minsize FROM category c INNER JOIN category cp ON cp.id = c.parentid WHERE c.parentid IS NOT NULL');
		foreach ($catresrel as $catrowrel) {
			if ($catrowrel['minsize'] > 0) {
				$resrel = $this->pdo->queryDirect(
									sprintf('
										SELECT r.id, r.guid
										FROM releases r
										WHERE r.categoryid = %d
										AND r.size < %d',
										$catrowrel['id'],
										$catrowrel['minsize']
									)
				);
				foreach ($resrel as $rowrel) {
					$this->fastDelete($rowrel['id'], $rowrel['guid']);
					$catminsizecount++;
				}
			}
		}

		// Delete larger than max sizes
		if ($groupID == '') {
			$groupIDs = $this->groups->getActiveIDs();

			foreach ($groupIDs as $groupID) {
				if ($this->pdo->dbSystem() === 'mysql') {
					$resrel = $this->pdo->queryDirect(
										sprintf("
											SELECT r.id, r.guid FROM releases r LEFT JOIN
												(SELECT g.id, coalesce(g.minsizetoformrelease, s.minsizetoformrelease) AS minsizetoformrelease
												FROM groups g INNER JOIN
													(SELECT value as minsizetoformrelease FROM settings WHERE setting = 'minsizetoformrelease') s
												WHERE g.id = %s ) g ON g.id = r.group_id
											WHERE g.minsizetoformrelease != 0 AND r.size < minsizetoformrelease AND r.group_id = %s",
											$groupID['id'],
											$groupID['id']
										)
					);
				} else {
					$resrel = array();
					$s = $this->pdo->queryOneRow(
						"SELECT GREATEST(s.value::integer, g.minsizetoformrelease::integer) as size FROM settings s, groups g WHERE s.setting = 'minsizetoformrelease' AND g.id = " .
						$groupID['id']
					);
					if ($s['size'] > 0) {
						$resrel = $this->pdo->queryDirect(
											sprintf('
												SELECT id, guid
												FROM releases
												WHERE size < %d
												AND group_id = %d',
												$s['size'],
												$groupID['id']
											)
						);
					}
				}
				if ($resrel !== false && $resrel->rowCount() > 0) {
					foreach ($resrel as $rowrel) {
						$this->fastDelete($rowrel['id'], $rowrel['guid']);
						$minsizecount++;
					}
				}

				$maxfilesizeres = $this->pdo->queryOneRow("SELECT value FROM settings WHERE setting = 'maxsizetoformrelease'");
				if ($maxfilesizeres['value'] != 0) {
					$resrel = $this->pdo->queryDirect(
										sprintf('
											SELECT id, guid
											FROM releases
											WHERE group_id = %d
											AND size > %d',
											$groupID['id'],
											$maxfilesizeres['value']
										)
					);
					if ($resrel !== false && $resrel->rowCount() > 0) {
						foreach ($resrel as $rowrel) {
							$this->fastDelete($rowrel['id'], $rowrel['guid']);
							$maxsizecount++;
						}
					}
				}

				if ($this->pdo->dbSystem() === 'mysql') {
					$resrel = $this->pdo->queryDirect(
										sprintf("
											SELECT r.id, r.guid
											FROM releases r LEFT JOIN
												(SELECT g.id, coalesce(g.minfilestoformrelease, s.minfilestoformrelease) as minfilestoformrelease
												FROM groups g INNER JOIN
													(SELECT value as minfilestoformrelease FROM settings WHERE setting = 'minfilestoformrelease') s
												WHERE g.id = %d ) g ON g.id = r.group_id
											WHERE g.minfilestoformrelease != 0
											AND r.totalpart < minfilestoformrelease
											AND r.group_id = %d",
											$groupID['id'],
											$groupID['id']
										)
					);
				} else {
					$resrel = array();
					$f = $this->pdo->queryOneRow(
						"SELECT GREATEST(s.value::integer, g.minfilestoformrelease::integer) as files FROM settings s, groups g WHERE s.setting = 'minfilestoformrelease' AND g.id = " .
						$groupID['id']
					);
					if ($f['files'] > 0) {
						$resrel = $this->pdo->queryDirect(
											sprintf('
												SELECT id, guid
												FROM releases
												WHERE totalpart < %d
												AND group_id = %d',
												$f['files'],
												$groupID['id']
											)
						);
					}
				}
				if ($resrel !== false && $resrel->rowCount() > 0) {
					foreach ($resrel as $rowrel) {
						$this->fastDelete($rowrel['id'], $rowrel['guid']);
						$minfilecount++;
					}
				}
			}
		} else {
			if ($this->pdo->dbSystem() === 'mysql') {
				$resrel = $this->pdo->queryDirect(
									sprintf("
										SELECT r.id, r.guid
										FROM releases r LEFT JOIN
											(SELECT g.id, coalesce(g.minsizetoformrelease, s.minsizetoformrelease) AS minsizetoformrelease FROM groups g INNER JOIN
												(SELECT value AS minsizetoformrelease FROM settings WHERE setting = 'minsizetoformrelease' ) s
											WHERE g.id = %d ) g ON g.id = r.group_id
										WHERE g.minsizetoformrelease != 0
										AND r.size < minsizetoformrelease
										AND r.group_id = %d",
										$groupID,
										$groupID
									)
				);
			} else {
				$resrel = array();
				$s = $this->pdo->queryOneRow(
					"SELECT GREATEST(s.value::integer, g.minsizetoformrelease::integer) as size FROM settings s, groups g WHERE s.setting = 'minsizetoformrelease' AND g.id = " .
					$groupID
				);
				if ($s['size'] > 0) {
					$resrel = $this->pdo->queryDirect(
										sprintf('
											SELECT id, guid
											FROM releases
											WHERE size < %d
											AND group_id = %d',
											$s['size'],
											$groupID
										)
					);
				}
			}
			if ($resrel !== false && $resrel->rowCount() > 0) {
				foreach ($resrel as $rowrel) {
					$this->fastDelete($rowrel['id'], $rowrel['guid']);
					$minsizecount++;
				}
			}

			$maxfilesizeres = $this->pdo->queryOneRow("SELECT value FROM settings WHERE setting = 'maxsizetoformrelease'");
			if ($maxfilesizeres['value'] != 0) {
				$resrel = $this->pdo->queryDirect(sprintf('SELECT id, guid FROM releases WHERE group_id = %d AND size > %s', $groupID, $this->pdo->escapeString($maxfilesizeres['value'])));
				if ($resrel !== false && $resrel->rowCount() > 0) {
					foreach ($resrel as $rowrel) {
						$this->fastDelete($rowrel['id'], $rowrel['guid']);
						$maxsizecount++;
					}
				}
			}

			if ($this->pdo->dbSystem() === 'mysql') {
				$resrel = $this->pdo->queryDirect(
									sprintf("
										SELECT r.id, r.guid
										FROM releases r LEFT JOIN
											(SELECT g.id, coalesce(g.minfilestoformrelease, s.minfilestoformrelease) AS minfilestoformrelease
											FROM groups g INNER JOIN
												(SELECT value AS minfilestoformrelease FROM settings WHERE setting = 'minfilestoformrelease' ) s
											WHERE g.id = %d ) g ON g.id = r.group_id
										WHERE g.minfilestoformrelease != 0
										AND r.totalpart < minfilestoformrelease
										AND r.group_id = %d",
										$groupID,
										$groupID
									)
				);
			} else {
				$resrel = array();
				$f = $this->pdo->queryOneRow(
					"SELECT GREATEST(s.value::integer, g.minfilestoformrelease::integer) as files FROM settings s, groups g WHERE s.setting = 'minfilestoformrelease' AND g.id = " .
					$groupID
				);
				if ($f['files'] > 0) {
					$resrel = $this->pdo->queryDirect(sprintf('SELECT id, guid FROM releases WHERE totalpart < %d AND group_id = %d', $f['files'], $groupID));
				}
			}
			if ($resrel !== false && $resrel->rowCount() > 0) {
				foreach ($resrel as $rowrel) {
					$this->fastDelete($rowrel['id'], $rowrel['guid']);
					$minfilecount++;
				}
			}
		}

		$delcount = $minsizecount + $maxsizecount + $minfilecount + $catminsizecount;
		if ($this->echooutput && $delcount > 0) {
			$this->c->doEcho(
				$this->c->primary(
					'Deleted ' .
					number_format($delcount) .
					" releases smaller/larger than group/site settings."
				)
			);
		}
		if ($this->echooutput) {
			$this->c->doEcho($this->c->primary($this->consoleTools->convertTime(TIME() - $stage4dot5)), true);
		}
	}

	public function processReleasesStage5($groupID)
	{
		$stage5 = time();
		$where = (!empty($groupID)) ? ' r.group_id = ' . $groupID . ' AND ' : ' ';

		// Set table names
		$group = $this->groups->getCBPTableNames($this->_tablePerGroup, $groupID);

		// Create NZB.
		if ($this->echooutput) {
			$this->c->doEcho($this->c->header("Stage 5 -> Create the NZB, delete collections/binaries/parts."));
		}

		$releases = $this->pdo->queryDirect(
			sprintf("
				SELECT CONCAT(COALESCE(cp.title,'') , CASE WHEN cp.title IS NULL THEN '' ELSE ' > ' END , c.title) AS title,
					r.name, r.id, r.guid
				FROM releases r
				INNER JOIN category c ON r.categoryid = c.id
				INNER JOIN category cp ON cp.id = c.parentid
				WHERE %s nzbstatus = 0",
				$where
			)
		);

		$total = $deleted = $nzbCount = 0;
		if ($releases !== false) {
			$total = $releases->rowCount();
		}

		$releaseIDs = array();

		if ($total > 0) {
			$nzb = new NZB();
			// Init vars for writing the NZB's.
			$nzb->initiateForWrite($this->pdo, htmlspecialchars(date('F j, Y, g:i a O'), ENT_QUOTES, 'utf-8'), $groupID);
			foreach ($releases as $release) {
				$nzb_create = $nzb->writeNZBforReleaseId($release['id'], $release['guid'], $release['name'], $release['title']);

				if ($nzb_create !== false) {
					$releaseIDs[] = $release['id'];
					$nzbCount++;
					if ($this->echooutput) {
						$this->consoleTools->overWritePrimary(
							'Creating NZBs: ' . $this->consoleTools->percentString($nzbCount, $total)
						);
					}
				}
			}
			// Reset vars for next use.
			$nzb->cleanForWrite();
		}

		$nzbEnd = time();

		if ($nzbCount > 0) {
			if ($this->echooutput) {
				$this->c->doEcho(
					$this->c->primary(
						PHP_EOL . 'Deleting collections/binaries/parts, be patient.'
					)
				);
			}

			$deleteQuery = $this->pdo->queryExec(
				sprintf(
					'DELETE FROM %s WHERE releaseid IN (%s)',
					$group['cname'],
					implode(',', $releaseIDs)
				)
			);
			if ($deleteQuery !== false) {
				$deleted = $deleteQuery->rowCount();
			}
		}

		$deleteEnd = time();

		if ($this->echooutput) {
			$this->c->doEcho(
				$this->c->primary(
					number_format($nzbCount) . ' NZBs created in ' . ($nzbEnd - $stage5) . ' seconds.' . PHP_EOL .
					'Deleted ' . number_format($deleted) . ' collections in ' . ($deleteEnd - $nzbEnd) . ' seconds.' . PHP_EOL .
					'Total stage 5 time: ' . $this->c->primary($this->consoleTools->convertTime(time() - $stage5))
				)
			);
		}

		return $nzbCount;
	}

	/**
	 * Process RequestID's via Local lookup.
	 *
	 * @param int $groupID
	 * @param int $limit
	 */
	public function processReleasesStage5b($groupID, $limit = 5000)
	{
		$requestid = new RequestID($this->echooutput);
		$stage5b = TIME();
		if ($this->echooutput) {
			$this->c->doEcho($this->c->header("Stage 5b -> Request ID Local lookup -- limit $limit."));
		}
		$iFoundCnt = $requestid->lookupReqIDs($groupID, $limit, true);
		if ($this->echooutput) {
			$this->c->doEcho($this->c->primary(number_format($iFoundCnt) . ' Releases updated in ' . $this->consoleTools->convertTime(TIME() - $stage5b)), true);
		}
	}

	/**
	 * Process RequestID's via Web lookup.
	 *
	 * @param int $groupID
	 * @param int $limit
	 */
	public function processReleasesStage5c($groupID, $limit = 100)
	{
		if ($this->pdo->getSetting('lookup_reqids') == 1 || $this->pdo->getSetting('lookup_reqids') == 2) {
			$requestid = new RequestID($this->echooutput);
			$stage5c = TIME();

			if ($this->echooutput) {
				$this->c->doEcho($this->c->header("Stage 5c -> Request ID Web lookup -- limit $limit."));
			}
			$iFoundCnt = $requestid->lookupReqIDs($groupID, $limit, false);
			if ($this->echooutput) {
				$this->c->doEcho(PHP_EOL . $this->c->primary(number_format($iFoundCnt) . ' Releases updated in ' . $this->consoleTools->convertTime(TIME() - $stage5c)), true);
			}
		}
	}

	public function processReleasesStage6($categorize, $postproc, $groupID, $nntp)
	{
		$where = (!empty($groupID)) ? 'WHERE iscategorized = 0 AND group_id = ' . $groupID : 'WHERE iscategorized = 0';

		// Categorize releases.
		if ($this->echooutput) {
			echo $this->c->header("Stage 6 -> Categorize and post process releases.");
		}
		$stage6 = TIME();
		if ($categorize == 1) {
			$this->categorizeRelease('name', $where);
		}

		if ($postproc == 1) {
			$postprocess = new PostProcess($this->echooutput);
			$postprocess->processAll($nntp);
		} else {
			if ($this->echooutput) {
				$this->c->doEcho(
					$this->c->info(
						"\nPost-processing is not running inside the releases.php file.\n" .
						"If you are using tmux or screen they might have their own files running Post-processing."
					)
				);
			}
		}
		if ($this->echooutput) {
			$this->c->doEcho($this->c->primary($this->consoleTools->convertTime(TIME() - $stage6)), true);
		}
	}

	/**
	 * Delete old releases and finished collections.
	 *
	 * @param string|int $groupID (optional) ID of group.
	 * @void
	 * @access public
	 */
	public function processReleasesStage7a($groupID)
	{
		$stage7 = time();
		$deletedCount = 0;

		// Set table names
		$group = $this->groups->getCBPTableNames($this->_tablePerGroup, $groupID);

		if ($this->echooutput) {
			echo $this->c->header("Stage 7a -> Delete finished collections." . PHP_EOL);
			echo $this->c->primary('Deleting old collections/binaries/parts.');
		}

		$deleted = 0;
		// Old collections that were missed somehow.
		$deleteQuery = $this->pdo->queryExec(
			sprintf(
				'DELETE FROM %s WHERE dateadded < (NOW() - INTERVAL %d HOUR) %s',
				$group['cname'],
				$this->pdo->getSetting('partretentionhours'),
				(!empty($groupID) && $this->_tablePerGroup === false ? ' AND group_id = ' . $groupID : '')
			)
		);
		if ($deleteQuery !== false) {
			$deleted = $deleteQuery->rowCount();
			$deletedCount += $deleted;
		}
		$firstQuery = time();

		if ($this->echooutput) {
			echo $this->c->primary(
				'Finished deleting ' . $deleted . ' old collections/binaries/parts in ' .
				($firstQuery - $stage7) . ' seconds.' . PHP_EOL .
				'Deleting binaries/parts with no collections.'
			);
		}

		$deleted = 0;
		// Binaries/parts that somehow have no collection.
		$deleteQuery = $this->pdo->queryExec(
			sprintf(
				'DELETE %s, %s FROM %s, %s WHERE %s.collectionid = 0 AND %s.id = %s.binaryid',
				$group['bname'], $group['pname'], $group['bname'], $group['pname'],
				$group['bname'], $group['bname'], $group['pname']
			)
		);
		if ($deleteQuery !== false) {
			$deleted = $deleteQuery->rowCount();
			$deletedCount += $deleted;
		}
		$secondQuery = time();

		if ($this->echooutput) {
			echo $this->c->primary(
				'Finished deleting ' . $deleted . ' binaries/parts with no collections in ' .
				($secondQuery - $firstQuery) . ' seconds.' . PHP_EOL .
				'Deleting parts with no binaries.'
			);
		}

		$deleted = 0;
		// Parts that somehow have no binaries. Don't delete parts currently inserting, by checking the max ID.
		if (mt_rand(0, 100) <= 5) {
			$deleteQuery = $this->pdo->queryExec(
				sprintf(
					'DELETE FROM %s WHERE binaryid NOT IN (SELECT id FROM %s) %s',
					$group['pname'], $group['bname'], $this->stage7aMinMaxQueryFormulator($group['pname'], 40000)
				)
			);
			if ($deleteQuery !== false) {
				$deleted = $deleteQuery->rowCount();
				$deletedCount += $deleted;
			}
		}
		$thirdQuery = time();

		if ($this->echooutput) {
			echo $this->c->primary(
				'Finished deleting ' . $deleted . ' parts with no binaries in ' .
				($thirdQuery - $secondQuery) . ' seconds.' . PHP_EOL .
				'Deleting binaries with no collections.'
			);
		}

		$deleted = 0;
		// Binaries that somehow have no collection. Don't delete currently inserting binaries by checking the max id.
		$deleteQuery = $this->pdo->queryExec(
			sprintf(
				'DELETE FROM %s WHERE collectionid NOT IN (SELECT id FROM %s) %s',
				$group['bname'], $group['cname'], $this->stage7aMinMaxQueryFormulator($group['bname'], 20000)
			)
		);
		if ($deleteQuery !== false) {
			$deleted = $deleteQuery->rowCount();
			$deletedCount += $deleted;
		}
		$fourthQuery = time();

		if ($this->echooutput) {
			echo $this->c->primary(
				'Finished deleting ' . $deleted . ' binaries with no collections in ' .
				($fourthQuery - $thirdQuery) . ' seconds.' . PHP_EOL .
				'Deleting collections with no binaries.'
			);
		}

		$deleted = 0;
		// Collections that somehow have no binaries.
		$collectionIDs = $this->pdo->queryDirect(
			sprintf(
				'SELECT id FROM %s WHERE id NOT IN (SELECT collectionid FROM %s) %s',
				$group['cname'], $group['bname'], $this->stage7aMinMaxQueryFormulator($group['cname'], 10000)
			)
		);
		if ($collectionIDs !== false) {
			foreach ($collectionIDs as $collectionID) {
				$deleted++;
				$this->pdo->queryExec(sprintf('DELETE FROM %s WHERE id = %d', $group['cname'], $collectionID['id']));
			}
			$deletedCount += $deleted;
		}
		$fifthQuery = time();

		if ($this->echooutput) {
			echo $this->c->primary(
				'Finished deleting ' . $deleted . ' collections with no binaries in ' .
				($fifthQuery - $fourthQuery) . ' seconds.' . PHP_EOL .
				'Deleting collections that were missed on stage 5.'
			);
		}

		$deleted = 0;
		// Collections that were missing on stage 5.

		$collections = $this->pdo->queryDirect(
			sprintf('
				SELECT c.id
				FROM %s c
				INNER JOIN releases r ON r.id = c.releaseid
				WHERE r.nzbstatus = 1',
				$group['cname']
			)
		);

		if ($collections !== false && $collections->rowCount() > 0) {
			foreach($collections as $collection) {
				$deleted++;
				$this->pdo->queryExec(
					sprintf('
						DELETE FROM %s WHERE id = %d',
						$group['cname'], $collection['id']
					)
				);
			}
			$deletedCount += $deleted;
		}

		$sixthQuery = time();

		if ($this->echooutput) {
			$this->c->doEcho(
				$this->c->primary(
					'Finished deleting ' . $deleted . ' collections missed on stage 5 in ' .
					($sixthQuery - $fifthQuery) . ' seconds.' . PHP_EOL .
					'Removed ' .
					number_format($deletedCount) .
					' parts/binaries/collection rows in ' .
					$this->consoleTools->convertTime(($fifthQuery - $stage7)) . PHP_EOL
				)
			);
		}
	}

	/**
	 * Formulate part of a query to prevent deletion of currently inserting parts / binaries / collections.
	 *
	 * @param string $groupName
	 * @param int    $difference
	 *
	 * @return string
	 * @access private
	 */
	private function stage7aMinMaxQueryFormulator($groupName, $difference)
	{
		$minMaxId = $this->pdo->queryOneRow(sprintf('SELECT MIN(id) AS min, MAX(id) AS max FROM %s', $groupName));
		if ($minMaxId === false) {
			$minMaxId = '';
		} else {
			$minMaxId = ' AND id < ' . ((($minMaxId['max'] - $minMaxId['min']) >= $difference) ? ($minMaxId['max'] - $difference) : 1);
		}
		return $minMaxId;
	}

	// Queries that are not per group
	public function processReleasesStage7b()
	{
		$category = new Category();
		$genres = new Genres();
		$remcount = $reccount = $passcount = $dupecount = $relsizecount = $completioncount = $disabledcount = $disabledgenrecount = $miscothercount = $total = 0;

		// Delete old releases and finished collections.
		if ($this->echooutput) {
			$this->c->doEcho($this->c->header("Stage 7b -> Delete old releases and passworded releases."));
		}
		$stage7 = TIME();

		// Releases past retention.
		if ($this->pdo->getSetting('releaseretentiondays') != 0) {
			if ($this->pdo->dbSystem() === 'mysql') {
				$result = $this->pdo->queryDirect(sprintf('SELECT id, guid FROM releases WHERE postdate < (NOW() - INTERVAL %d DAY)', $this->pdo->getSetting('releaseretentiondays')));
			} else {
				$result = $this->pdo->queryDirect(sprintf("SELECT id, guid FROM releases WHERE postdate < (NOW() - INTERVAL '%d DAYS')", $this->pdo->getSetting('releaseretentiondays')));
			}
			if ($result !== false && $result->rowCount() > 0) {
				foreach ($result as $rowrel) {
					$this->fastDelete($rowrel['id'], $rowrel['guid']);
					$remcount++;
				}
			}
		}

		// Passworded releases.
		if ($this->pdo->getSetting('deletepasswordedrelease') == 1) {
			$result = $this->pdo->queryDirect(
				'SELECT id, guid FROM releases WHERE passwordstatus = ' . Releases::PASSWD_RAR
			);
			if ($result !== false && $result->rowCount() > 0) {
				foreach ($result as $rowrel) {
					$this->fastDelete($rowrel['id'], $rowrel['guid']);
					$passcount++;
				}
			}
		}

		// Possibly passworded releases.
		if ($this->pdo->getSetting('deletepossiblerelease') == 1) {
			$result = $this->pdo->queryDirect(
				'SELECT id, guid FROM releases WHERE passwordstatus = ' . Releases::PASSWD_POTENTIAL
			);
			if ($result !== false && $result->rowCount() > 0) {
				foreach ($result as $rowrel) {
					$this->fastDelete($rowrel['id'], $rowrel['guid']);
					$passcount++;
				}
			}
		}

		// Crossposted releases.
		do {
			if ($this->crosspostt != 0) {
				if ($this->pdo->dbSystem() === 'mysql') {
					$resrel = $this->pdo->queryDirect(sprintf('SELECT id, guid FROM releases WHERE adddate > (NOW() - INTERVAL %d HOUR) GROUP BY name HAVING COUNT(name) > 1', $this->crosspostt));
				} else {
					$resrel = $this->pdo->queryDirect(sprintf("SELECT id, guid FROM releases WHERE adddate > (NOW() - INTERVAL '%d HOURS') GROUP BY name, id HAVING COUNT(name) > 1", $this->crosspostt));
				}
				$total = 0;
				if ($resrel !== false) {
					$total = $resrel->rowCount();
				}
				if ($total > 0) {
					foreach ($resrel as $rowrel) {
						$this->fastDelete($rowrel['id'], $rowrel['guid']);
						$dupecount++;
					}
				}
			}
		} while ($total > 0);

		// Releases below completion %.
		if ($this->completion > 100) {
			$this->completion = 100;
			echo $this->c->error("\nYou have an invalid setting for completion.");
		}
		if ($this->completion > 0) {
			$resrel = $this->pdo->queryDirect(sprintf('SELECT id, guid FROM releases WHERE completion < %d AND completion > 0', $this->completion));
			if ($resrel !== false && $resrel->rowCount() > 0) {
				foreach ($resrel as $rowrel) {
					$this->fastDelete($rowrel['id'], $rowrel['guid']);
					$completioncount++;
				}
			}
		}

		// Disabled categories.
		$catlist = $category->getDisabledIDs();
		if (count($catlist) > 0) {
			foreach ($catlist as $cat) {
				$res = $this->pdo->queryDirect(sprintf('SELECT id, guid FROM releases WHERE categoryid = %d', $cat['id']));
				if ($res !== false && $res->rowCount() > 0) {
					foreach ($res as $rel) {
						$disabledcount++;
						$this->fastDelete($rel['id'], $rel['guid']);
					}
				}
			}
		}

		// Disabled music genres.
		$genrelist = $genres->getDisabledIDs();
		if (count($genrelist) > 0) {
			foreach ($genrelist as $genre) {
				$rels = $this->pdo->queryDirect(sprintf('SELECT id, guid FROM releases INNER JOIN (SELECT id AS mid FROM musicinfo WHERE musicinfo.genreid = %d) mi ON musicinfoid = mid', $genre['id']));
				if ($rels !== false && $rels->rowCount() > 0) {
					foreach ($rels as $rel) {
						$disabledgenrecount++;
						$this->fastDelete($rel['id'], $rel['guid']);
					}
				}
			}
		}

		// Misc other.
		if ($this->pdo->getSetting('miscotherretentionhours') > 0) {
			if ($this->pdo->dbSystem() === 'mysql') {
				$resrel = $this->pdo->queryDirect(sprintf('SELECT id, guid FROM releases WHERE categoryid = %d AND adddate <= NOW() - INTERVAL %d HOUR', CATEGORY::CAT_MISC, $this->pdo->getSetting('miscotherretentionhours')));
			} else {
				$resrel = $this->pdo->queryDirect(sprintf("SELECT id, guid FROM releases WHERE categoryid = %d AND adddate <= NOW() - INTERVAL '%d HOURS'", CATEGORY::CAT_MISC, $this->pdo->getSetting('miscotherretentionhours')));
			}
			if ($resrel !== false && $resrel->rowCount() > 0) {
				foreach ($resrel as $rowrel) {
					$this->fastDelete($rowrel['id'], $rowrel['guid']);
					$miscothercount++;
				}
			}
		}

		if ($this->echooutput && $this->completion > 0) {
			$this->c->doEcho(
				$this->c->primary(
					'Removed releases: ' .
					number_format($remcount) .
					' past retention, ' .
					number_format($passcount) .
					' passworded, ' .
					number_format($dupecount) .
					' crossposted, ' .
					number_format($disabledcount) .
					' from disabled categories, ' .
					number_format($disabledgenrecount) .
					' from disabled music genres, ' .
					number_format($miscothercount) .
					' from misc->other, ' .
					number_format($completioncount) .
					' under ' .
					$this->completion .
					'% completion.'
				)
			);
		} else if ($this->echooutput && $this->completion == 0) {
			$this->c->doEcho(
				$this->c->primary(
					'Removed releases: ' .
					number_format($remcount) .
					' past retention, ' .
					number_format($passcount) .
					' passworded, ' .
					number_format($dupecount) .
					' crossposted, ' .
					number_format($disabledcount) .
					' from disabled categories, ' .
					number_format($disabledgenrecount) .
					' from disabled music genres, ' .
					number_format($miscothercount) .
					' from misc->other'
				)
			);
		}

		if ($this->echooutput) {
			if ($reccount > 0) {
				$this->c->doEcho(
					$this->c->primary(
						"Removed " . number_format($reccount) . ' parts/binaries/collection rows.'
					)
				);
			}
			$this->c->doEcho($this->c->primary($this->consoleTools->convertTime(TIME() - $stage7)), true);
		}
	}

	public function processReleasesStage4567_loop($categorize, $postproc, $groupID, $nntp)
	{
		$DIR = nZEDb_MISC;
		$PYTHON = shell_exec('which python3 2>/dev/null');
		$PYTHON = (empty($PYTHON) ? 'python -OOu' : 'python3 -OOu');

		$tot_retcount = $tot_nzbcount = $loops = 0;
		do {
			$retcount = $this->processReleasesStage4($groupID);
			$tot_retcount = $tot_retcount + $retcount;

			$nzbcount = $this->processReleasesStage5($groupID);
			if ($this->requestids == '0') {
				$this->processReleasesStage5b($groupID);
			} else if ($this->requestids == '1') {
				$this->processReleasesStage5b($groupID);
				$this->processReleasesStage5c($groupID);
			} else if ($this->requestids == '2') {
				$stage8 = TIME();
				if ($this->echooutput) {
					$this->c->doEcho($this->c->header("Stage 5b-c -> Request ID Threaded lookup."));
				}
				passthru("$PYTHON ${DIR}update/python/requestid_threaded.py");
				if ($this->echooutput) {
					$this->c->doEcho(
						$this->c->primary(
							"\nReleases updated in " .
							$this->consoleTools->convertTime(TIME() - $stage8)
						)
					);
				}
			}

			$tot_nzbcount = $tot_nzbcount + $nzbcount;
			$this->processReleasesStage6($categorize, $postproc, $groupID, $nntp);
			$this->processReleasesStage7a($groupID);
			$loops++;
			// This loops as long as there were releases created or 3 loops, otherwise, you could loop indefinately
		} while (($nzbcount > 0 || $retcount > 0) && $loops < 3);

		return $tot_retcount;
	}

	public function processReleases($categorize, $postproc, $groupName, $nntp, $echooutput)
	{
		$this->echooutput = $echooutput;
		if ($this->hashcheck == 0) {
			exit($this->c->error("You must run update_binaries.php to update your collectionhash.\n"));
		}
		$groupID = '';

		if (!empty($groupName)) {
			$groupInfo = $this->groups->getByName($groupName);
			$groupID = $groupInfo['id'];
		}

		$processReleases = microtime(true);
		if ($this->echooutput) {
			$this->c->doEcho($this->c->header("Starting release update process (" . date('Y-m-d H:i:s') . ")"), true);
		}

		if (!file_exists($this->pdo->getSetting('nzbpath'))) {
			if ($this->echooutput) {
				$this->c->doEcho($this->c->error('Bad or missing nzb directory - ' . $this->pdo->getSetting('nzbpath')), true);
			}

			return 0;
		}

		$this->processReleasesStage1($groupID);
		$this->processReleasesStage2($groupID);
		$this->processReleasesStage3($groupID);
		$releasesAdded = $this->processReleasesStage4567_loop($categorize, $postproc, $groupID, $nntp);
		$this->processReleasesStage4dot5($groupID);
		$this->processReleasesStage7b();
		$where = (!empty($groupID)) ? ' WHERE group_id = ' . $groupID : '';

		//Print amount of added releases and time it took.
		if ($this->echooutput && $this->_tablePerGroup === false) {
			$countID = $this->pdo->queryOneRow('SELECT COUNT(id) FROM collections ' . $where);
			$this->c->doEcho(
				$this->c->primary(
					'Completed adding ' .
					number_format($releasesAdded) .
					' releases in ' .
					$this->consoleTools->convertTime(number_format(microtime(true) - $processReleases, 2)) .
					'. ' .
					number_format(array_shift($countID)) .
					' collections waiting to be created (still incomplete or in queue for creation)'
				), true
			);
		}

		return $releasesAdded;
	}

	// This resets collections, useful when the namecleaning class's collectioncleaner function changes.
	public function resetCollections()
	{
		$res = $this->pdo->queryDirect('SELECT b.id as bid, b.name as bname, c.* FROM binaries b LEFT JOIN collections c ON b.collectionid = c.id');
		if ($res !== false && $res->rowCount() > 0) {
			$timestart = TIME();
			if ($this->echooutput) {
				echo "Going to remake all the collections. This can be a long process, be patient. DO NOT STOP THIS SCRIPT!\n";
			}
			// Reset the collectionhash.
			$this->pdo->queryExec('UPDATE collections SET collectionhash = 0');
			$delcount = 0;
			$cIDS = array();
			foreach ($res as $row) {

				$groupName = $this->groups->getByNameByID($row['group_id']);
				$newSHA1 = sha1(
					$this->collectionsCleaning->collectionsCleaner(
						$row['bname'],
						$groupName .
						$row['fromname'] .
						$row['group_id'] .
						$row['totalfiles']
					)
				);
				$cres = $this->pdo->queryOneRow(sprintf('SELECT id FROM collections WHERE collectionhash = %s', $this->pdo->escapeString($newSHA1)));
				if (!$cres) {
					$cIDS[] = $row['id'];
					$csql = sprintf('INSERT INTO collections (subject, fromname, date, xref, group_id, totalfiles, collectionhash, filecheck, dateadded) VALUES (%s, %s, %s, %s, %d, %s, %s, 0, NOW())', $this->pdo->escapeString($row['bname']), $this->pdo->escapeString($row['fromname']), $this->pdo->escapeString($row['date']), $this->pdo->escapeString($row['xref']), $row['group_id'], $this->pdo->escapeString($row['totalfiles']), $this->pdo->escapeString($newSHA1));
					$collectionID = $this->pdo->queryInsert($csql);
					if ($this->echooutput) {
						$this->consoleTools->overWrite(
							'Recreated: ' . count($cIDS) . ' collections. Time:' .
							$this->consoleTools->convertTimer(TIME() - $timestart)
						);
					}
				} else {
					$collectionID = $cres['id'];
				}
				//Update the binaries with the new info.
				$this->pdo->queryExec(sprintf('UPDATE binaries SET collectionid = %d WHERE id = %d', $collectionID, $row['bid']));
			}
			//Remove the old collections.
			$delstart = TIME();
			if ($this->echooutput) {
				echo "\n";
			}
			$totalcIDS = count($cIDS);
			foreach ($cIDS as $cID) {
				$this->pdo->queryExec(sprintf('DELETE FROM collections WHERE id = %d', $cID));
				$delcount++;
				if ($this->echooutput) {
					$this->consoleTools->overWrite(
						'Deleting old collections:' . $this->consoleTools->percentString($delcount, $totalcIDS) .
						' Time:' . $this->consoleTools->convertTimer(TIME() - $delstart)
					);
				}
			}
			// Delete previous failed attempts.
			$this->pdo->queryExec('DELETE FROM collections WHERE collectionhash = "0"');

			if ($this->hashcheck == 0) {
				$this->pdo->queryExec("UPDATE settings SET value = 1 WHERE setting = 'hashcheck'");
			}
			if ($this->echooutput) {
				echo "\nRemade " . count($cIDS) . ' collections in ' .
					$this->consoleTools->convertTime(TIME() - $timestart) . "\n";
			}
		} else {
			$this->pdo->queryExec("UPDATE settings SET value = 1 WHERE setting = 'hashcheck'");
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
