<?php
require_once nZEDb_LIBS . 'ZipFile.php';

use nzedb\db\DB;
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

	// Request ID.
	const REQID_NONE   = -3; // The Request ID was not found.
	const REQID_ZERO   = -2; // The Request ID was 0.
	const REQID_BAD    = -1; // Request ID is in bad format?
	const REQID_UPROC  =  0; // Release has not been processed.
	const REQID_FOUND  =  1; // Request ID found and release was updated.

	/**
	 * @param bool $echooutput
	 */
	public function __construct($echooutput = false)
	{
		$this->echooutput = ($echooutput && nZEDb_ECHOCLI);
		$this->db = new DB();
		$this->s = new Sites();
		$this->site = $this->s->get();
		$this->groups = new Groups($this->db);
		$this->collectionsCleaning = new CollectionsCleaning();
		$this->releaseCleaning = new ReleaseCleaning();
		$this->consoleTools = new ConsoleTools();
		$this->stage5limit = (isset($this->site->maxnzbsprocessed)) ? (int)$this->site->maxnzbsprocessed : 1000;
		$this->completion = (isset($this->site->releasecompletion)) ? (int)$this->site->releasecompletion : 0;
		$this->crosspostt = (isset($this->site->crossposttime)) ? (int)$this->site->crossposttime : 2;
		$this->updategrabs = ($this->site->grabstatus == '0') ? false : true;
		$this->requestids = $this->site->lookup_reqids;
		$this->hashcheck = (isset($this->site->hashcheck)) ? (int)$this->site->hashcheck : 0;
		$this->delaytimet = (isset($this->site->delaytime)) ? (int)$this->site->delaytime : 2;
		$this->tablepergroup = (isset($this->site->tablepergroup)) ? (int)$this->site->tablepergroup : 0;
		$this->c = new ColorCLI();
	}

	/**
	 * @return array
	 */
	public function get()
	{
		return $this->db->query(
			'
						SELECT releases.*, g.name AS group_name, c.title AS category_name
						FROM releases
						INNER JOIN category c ON c.id = releases.categoryid
						INNER JOIN groups g ON g.id = releases.groupid
						WHERE nzbstatus = 1'
		);
	}

	/**
	 * @param $start
	 * @param $num
	 *
	 * @return array
	 */
	public function getRange($start, $num)
	{
		return $this->db->query(
			sprintf(
				"
								SELECT releases.*, CONCAT(cp.title, ' > ', c.title) AS category_name
								FROM releases
								INNER JOIN category c ON c.id = releases.categoryid
								INNER JOIN category cp ON cp.id = c.parentid
								WHERE nzbstatus = 1
								ORDER BY postdate DESC %s",
				($start === false ? '' : 'LIMIT ' . $num . ' OFFSET ' . $start)
			)
		);
	}

	/**
	 * Used for paginator.
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
			($this->db->dbSystem() === 'mysql' ? $maxage . ' DAY ' : "'" . $maxage . " DAYS' ")
			: ''
		);

		if ($grp != '') {
			$grpjoin = 'INNER JOIN groups ON groups.id = releases.groupid';
			$grpsql = sprintf(' AND groups.name = %s ', $this->db->escapeString($grp));
		}

		if (count($excludedcats) > 0) {
			$exccatlist = ' AND categoryid NOT IN (' . implode(',', $excludedcats) . ')';
		}

		$res = $this->db->queryOneRow(
			sprintf(
				'
								SELECT COUNT(releases.id) AS num
								FROM releases %s
								WHERE nzbstatus = 1
								AND releases.passwordstatus <= %d %s %s %s %s',
				$grpjoin, $this->showPasswords(), $catsrch, $maxagesql, $exccatlist, $grpsql
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
			($this->db->dbSystem() === 'mysql' ? $maxage . ' DAY ' : "'" . $maxage . " DAYS' ")
			: ''
		);

		if ($grp != '') {
			$grpsql = sprintf(' AND groups.name = %s ', $this->db->escapeString($grp));
		}

		if (count($excludedcats) > 0) {
			$exccatlist = ' AND releases.categoryid NOT IN (' . implode(',', $excludedcats) . ')';
		}

		$order = $this->getBrowseOrder($orderby);
		return $this->db->query(
			sprintf(
				"
								SELECT releases.*,
									CONCAT(cp.title, ' > ', c.title) AS category_name,
									CONCAT(cp.id, ',', c.id) AS category_ids,
									groups.name AS group_name,
									rn.id AS nfoid,
									re.releaseid AS reid
								FROM releases
								INNER JOIN groups ON groups.id = releases.groupid
								LEFT OUTER JOIN releasevideo re ON re.releaseid = releases.id
								LEFT OUTER JOIN releasenfo rn ON rn.releaseid = releases.id
									AND rn.nfo IS NOT NULL
								INNER JOIN category c ON c.id = releases.categoryid
								INNER JOIN category cp ON cp.id = c.parentid
								WHERE nzbstatus = 1
								AND releases.passwordstatus <= %d %s %s %s %s
								ORDER BY %s %s %s",
				$this->showPasswords(), $catsrch, $maxagesql, $exccatlist, $grpsql, $order[0], $order[1], $limit
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
		$res = $this->db->queryOneRow("SELECT value FROM settings WHERE setting = 'showpasswordedrelease'");
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
					$this->db->escapeString($dateparts[2] . '-' . $dateparts[1] . '-' . $dateparts[0] . ' 00:00:00')
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
					$this->db->escapeString($dateparts[2] . '-' . $dateparts[1] . '-' . $dateparts[0] . ' 23:59:59')
				);
			} else {
				$postto = '';
			}
		}

		if ($group != '' && $group != '-1') {
			$group = sprintf(' AND groupid = %d ', $group);
		} else {
			$group = '';
		}

		return $this->db->query(
			sprintf(
				"
								SELECT searchname, guid, groups.name AS gname, CONCAT(cp.title,'_',category.title) AS catName
								FROM releases
								INNER JOIN category ON releases.categoryid = category.id
								INNER JOIN groups ON releases.groupid = groups.id
								INNER JOIN category cp ON cp.id = category.parentid
								WHERE nzbstatus = 1 %s %s %s",
				$postfrom, $postto, $group
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
		$row = $this->db->queryOneRow(
			sprintf(
				"
								SELECT %s AS postdate FROM releases",
				($this->db->dbSystem() === 'mysql'
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
		$row = $this->db->queryOneRow(
			sprintf(
				"
								SELECT %s AS postdate FROM releases",
				($this->db->dbSystem() === 'mysql'
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
		$groups = $this->db->query('SELECT DISTINCT groups.id, groups.name FROM releases INNER JOIN groups on groups.id = releases.groupid');
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
		if ($this->db->dbSystem() === 'mysql') {
			$limit = ' LIMIT 0,' . ($num > 100 ? 100 : $num);
		} else {
			$limit = ' LIMIT ' . ($num > 100 ? 100 : $num) . ' OFFSET 0';
		}

		$catsrch = $cartsrch = '';
		if (count($cat) > 0) {
			if ($cat[0] == -2) {
				$cartsrch = sprintf(' INNER JOIN usercart ON usercart.userid = %d AND usercart.releaseid = releases.id ', $uid);
			} else if ($cat[0] != -1) {
				$catsrch = ' AND (';
				foreach ($cat as $category) {
					if ($category != -1) {
						$categ = new Category();
						if ($categ->isParent($category)) {
							$children = $categ->getChildren($category);
							$chlist = '-99';
							foreach ($children as $child) {
								$chlist .= ', ' . $child['id'];
							}

							if ($chlist != '-99') {
								$catsrch .= ' releases.categoryid IN (' . $chlist . ') OR ';
							}
						} else {
							$catsrch .= sprintf(' releases.categoryid = %d OR ', $category);
						}
					}
				}
				$catsrch .= '1=2 )';
			}
		}

		$rage = ($rageid > -1) ? sprintf(' AND releases.rageid = %d ', $rageid) : '';
		$anidb = ($anidbid > -1) ? sprintf(' AND releases.anidbid = %d ', $anidbid) : '';
		if ($this->db->dbSystem() === 'mysql') {
			$airdate = ($airdate >
				-1) ? sprintf(' AND releases.tvairdate >= DATE_SUB(CURDATE(), INTERVAL %d DAY) ', $airdate) : '';
		} else {
			$airdate = ($airdate >
				-1) ? sprintf(" AND releases.tvairdate >= (CURDATE() - INTERVAL '%d DAYS') ", $airdate) : '';
		}

		return $this->db->query(
			sprintf(
				"
								SELECT releases.*, m.cover, m.imdbid, m.rating, m.plot,
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
								FROM releases
								INNER JOIN category c ON c.id = releases.categoryid
								INNER JOIN category cp ON cp.id = c.parentid
								INNER JOIN groups g ON g.id = releases.groupid
								LEFT OUTER JOIN movieinfo m ON m.imdbid = releases.imdbid AND m.title != ''
								LEFT OUTER JOIN musicinfo mu ON mu.id = releases.musicinfoid
								LEFT OUTER JOIN genres mug ON mug.id = mu.genreid
								LEFT OUTER JOIN consoleinfo co ON co.id = releases.consoleinfoid
								LEFT OUTER JOIN genres cog ON cog.id = co.genreid %s
								WHERE releases.passwordstatus <= %d %s %s %s %s ORDER BY postdate DESC %s",
				$cartsrch, $this->showPasswords(), $catsrch, $rage, $anidb, $airdate, $limit
			)
		);
	}

	/**
	 * Get TV shows for RSS.
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
			$exccatlist = ' AND releases.categoryid NOT IN (' . implode(',', $excludedcats) . ')';
		}

		$usql = $this->uSQL($this->db->query(sprintf('SELECT rageid, categoryid FROM userseries WHERE userid = %d', $uid), true), 'rageid');
		if ($this->db->dbSystem() === 'mysql') {
			$airdate = ($airdate >
				-1) ? sprintf(' AND releases.tvairdate >= DATE_SUB(CURDATE(), INTERVAL %d DAY) ', $airdate) : '';
		} else {
			$airdate = ($airdate >
				-1) ? sprintf(" AND releases.tvairdate >= (CURDATE() - INTERVAL '%d DAYS') ", $airdate) : '';
		}
		$limit = ' LIMIT ' . ($num > 100 ? 100 : $num) . ' OFFSET 0';

		return $this->db->query(
			sprintf(
				"
								SELECT releases.*, tvr.rageid, tvr.releasetitle, g.name AS group_name,
									CONCAT(cp.title, '-', c.title) AS category_name,
									CONCAT(cp.id, ',', c.id) AS category_ids,
									COALESCE(cp.id,0) AS parentCategoryid
								FROM releases
								INNER JOIN category c ON c.id = releases.categoryid
								INNER JOIN category cp ON cp.id = c.parentid
								INNER JOIN groups g ON g.id = releases.groupid
								LEFT OUTER JOIN tvrage tvr ON tvr.rageid = releases.rageid
								WHERE %s %s %s
								AND releases.passwordstatus <= %d
								ORDER BY postdate DESC %s",
				$usql, $exccatlist, $airdate, $this->showPasswords(), $limit
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
			$exccatlist = ' AND releases.categoryid NOT IN (' . implode(',', $excludedcats) . ')';
		}

		$usql = $this->uSQL($this->db->query(sprintf('SELECT imdbid, categoryid FROM usermovies WHERE userid = %d', $uid), true), 'imdbid');
		$limit = ' LIMIT ' . ($num > 100 ? 100 : $num) . ' OFFSET 0';

		return $this->db->query(
			sprintf(
				"
								SELECT releases.*, mi.title AS releasetitle, g.name AS group_name,
									CONCAT(cp.title, '-', c.title) AS category_name,
									CONCAT(cp.id, ',', c.id) AS category_ids,
									COALESCE(cp.id,0) AS parentCategoryid
								FROM releases
								INNER JOIN category c ON c.id = releases.categoryid
								INNER JOIN category cp ON cp.id = c.parentid
								INNER JOIN groups g ON g.id = releases.groupid
								LEFT OUTER JOIN movieinfo mi ON mi.imdbid = releases.imdbid
								WHERE %s %s
								AND releases.passwordstatus <= %d
								ORDER BY postdate DESC %s",
				$usql, $exccatlist, $this->showPasswords(), $limit
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
			$exccatlist = ' AND releases.categoryid NOT IN (' . implode(',', $excludedcats) . ')';
		}

		$usql = $this->uSQL($usershows, 'rageid');

		if ($maxage > 0) {
			if ($this->db->dbSystem() === 'mysql') {
				$maxagesql = sprintf(' AND releases.postdate > NOW() - INTERVAL %d DAY ', $maxage);
			} else {
				$maxagesql = sprintf(" AND releases.postdate > NOW() - INTERVAL '%d DAYS' ", $maxage);
			}
		}

		$order = $this->getBrowseOrder($orderby);
		return $this->db->query(
			sprintf(
				"
								SELECT releases.*, CONCAT(cp.title, '-', c.title) AS category_name,
									CONCAT(cp.id, ',', c.id) AS category_ids, groups.name AS group_name,
									rn.id AS nfoid, re.releaseid AS reid
								FROM releases
								LEFT OUTER JOIN releasevideo re ON re.releaseid = releases.id
								INNER JOIN groups ON groups.id = releases.groupid
								LEFT OUTER JOIN releasenfo rn ON rn.releaseid = releases.id AND rn.nfo IS NOT NULL
								INNER JOIN category c ON c.id = releases.categoryid
								INNER JOIN category cp ON cp.id = c.parentid
								WHERE %s %s
								AND releases.passwordstatus <= %d %s
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
			$exccatlist = ' AND releases.categoryid NOT IN (' . implode(',', $excludedcats) . ')';
		}

		$usql = $this->uSQL($usershows, 'rageid');

		if ($maxage > 0) {
			if ($this->db->dbSystem() === 'mysql') {
				$maxagesql = sprintf(' AND releases.postdate > NOW() - INTERVAL %d DAY ', $maxage);
			} else {
				$maxagesql = sprintf(" AND releases.postdate > NOW() - INTERVAL '%d DAYS' ", $maxage);
			}
		}

		$res = $this->db->queryOneRow(
			sprintf(
				'
								SELECT COUNT(releases.id) AS num
								FROM releases
								WHERE %s %s
								AND releases.passwordstatus <= %d %s',
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
		$res = $this->db->queryOneRow('SELECT COUNT(id) AS num FROM releases');
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
		if ($this->db->dbSystem() === 'mysql') {
			$this->db->queryExec(
				sprintf(
					'DELETE
						releases, releasenfo, releasecomment, usercart, releasefiles,
						releaseaudio, releasesubs, releasevideo, releaseextrafull
					FROM releases
					LEFT OUTER JOIN releasenfo ON releasenfo.releaseid = releases.id
					LEFT OUTER JOIN releasecomment ON releasecomment.releaseid = releases.id
					LEFT OUTER JOIN usercart ON usercart.releaseid = releases.id
					LEFT OUTER JOIN releasefiles ON releasefiles.releaseid = releases.id
					LEFT OUTER JOIN releaseaudio ON releaseaudio.releaseid = releases.id
					LEFT OUTER JOIN releasesubs ON releasesubs.releaseid = releases.id
					LEFT OUTER JOIN releasevideo ON releasevideo.releaseid = releases.id
					LEFT OUTER JOIN releaseextrafull ON releaseextrafull.releaseid = releases.id
					WHERE releases.id = %d',
					$id
				)
			);
		} else {
			$this->db->queryExec('DELETE FROM releasenfo WHERE releaseid = ' . $id);
			$this->db->queryExec('DELETE FROM releasecomment WHERE releaseid = ' . $id);
			$this->db->queryExec('DELETE FROM usercart WHERE releaseid = ' . $id);
			$this->db->queryExec('DELETE FROM releasefiles WHERE releaseid = ' . $id);
			$this->db->queryExec('DELETE FROM releaseaudio WHERE releaseid = ' . $id);
			$this->db->queryExec('DELETE FROM releasesubs WHERE releaseid = ' . $id);
			$this->db->queryExec('DELETE FROM releasevideo WHERE releaseid = ' . $id);
			$this->db->queryExec('DELETE FROM releaseextrafull WHERE releaseid = ' . $id);
			$this->db->queryExec('DELETE FROM releases WHERE id = ' . $id);
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
	) {
		$this->db->queryExec(
			sprintf(
				'UPDATE releases
				SET name = %s, searchname = %s, fromname = %s, categoryid = %d,
				totalpart = %d, grabs = %d, size = %s, postdate = %s, adddate = %s, rageid = %d,
				seriesfull = %s, season = %s, episode = %s, imdbid = %d, anidbid = %d
				WHERE id = %d',
				$this->db->escapeString($name), $this->db->escapeString($searchname), $this->db->escapeString($fromname),
				$category, $parts, $grabs, $this->db->escapeString($size), $this->db->escapeString($posteddate),
				$this->db->escapeString($addeddate), $rageid, $this->db->escapeString($seriesfull),
				$this->db->escapeString($season), $this->db->escapeString($episode), $imdbid, $anidbid, $id
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
			'grabs' => $grabs,
			'rageid' => $rageid,
			'season' => $season,
			'imdbid' => $imdbid
		);

		$updateSql = array();
		foreach ($update as $updk => $updv) {
			if ($updv != '') {
				$updateSql[] = sprintf($updk . '=%s', $this->db->escapeString($updv));
			}
		}

		if (count($updateSql) < 1) {
			return -1;
		}

		$updateGuids = array();
		foreach ($guids as $guid) {
			$updateGuids[] = $this->db->escapeString($guid);
		}

		return $this->db->query(
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
			$usql .= sprintf('OR (releases.%s = %d', $type, $u[$type]);
			if ($u['categoryid'] != '') {
				$catsArr = explode('|', $u['categoryid']);
				if (count($catsArr) > 1) {
					$usql .= sprintf(' AND releases.categoryid IN (%s)', implode(',', $catsArr));
				} else {
					$usql .= sprintf(' AND releases.categoryid = %d', $catsArr[0]);
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
					$searchwords);
			}
			if ($searchwords === '') {
				$words = explode(' ', $search);
				$like = 'ILIKE';
				if ($this->db->dbSystem() === 'mysql') {
					$like = 'LIKE';
				}
				foreach ($words as $word) {
					if ($word != '') {
						$word = trim(rtrim(trim($word), '-'));
						if ($intwordcount == 0 && (strpos($word, '^') === 0)) {
							$searchsql .= sprintf(
								' AND releases.%s %s %s', $type, $like, $this->db->escapeString(
									substr($word, 1) . '%'
								)
							);
						} else if (substr($word, 0, 2) == '--') {
							$searchsql .= sprintf(
								' AND releases.%s NOT %s %s', $type, $like, $this->db->escapeString(
									'%' . substr($word, 2) . '%'
								)
							);
						} else {
							$searchsql .= sprintf(
								' AND releases.%s %s %s', $type, $like, $this->db->escapeString(
									'%' . $word . '%'
								)
							);
						}

						$intwordcount++;
					}
				}
			}
			return $searchsql;
		}
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
							$catsrch .= ' releases.categoryid IN (' . $chlist . ') OR ';
						}
					} else {
						$catsrch .= sprintf(' releases.categoryid = %d OR ', $category);
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
				$catsrch = sprintf(' AND (releases.categoryid = %d) ', $cat);
			}
		}

		$daysnewsql = $daysoldsql = $maxagesql = $groupIDsql = $parentcatsql = '';

		$searchnamesql = ($searchname != '-1' ? $this->searchSQL($searchname, 'searchname') : '');
		$usenetnamesql = ($usenetname != '-1' ? $this->searchSQL($usenetname, 'name') : '');
		$posternamesql = ($postername != '-1' ? $this->searchSQL($postername, 'fromname') : '');
		$hasnfosql = ($hasnfo != '0' ? ' AND releases.nfostatus = 1 ' : '');
		$hascommentssql = ($hascomments != '0' ? ' AND releases.comments > 0 ' : '');
		$exccatlist = (count($excludedcats) > 0 ?
			' AND releases.categoryid NOT IN (' . implode(',', $excludedcats) . ')' : '');

		if ($daysnew != '-1') {
			if ($this->db->dbSystem() === 'mysql') {
				$daysnewsql = sprintf(' AND releases.postdate < (NOW() - INTERVAL %d DAY) ', $daysnew);
			} else {
				$daysnewsql = sprintf(" AND releases.postdate < NOW() - INTERVAL '%d DAYS' ", $daysnew);
			}
		}

		if ($daysold != '-1') {
			if ($this->db->dbSystem() === 'mysql') {
				$daysoldsql = sprintf(' AND releases.postdate > (NOW() - INTERVAL %d DAY) ', $daysold);
			} else {
				$daysoldsql = sprintf(" AND releases.postdate > NOW() - INTERVAL '%d DAYS' ", $daysold);
			}
		}

		if ($maxage > 0) {
			if ($this->db->dbSystem() === 'mysql') {
				$maxagesql = sprintf(' AND releases.postdate > (NOW() - INTERVAL %d DAY) ', $maxage);
			} else {
				$maxagesql = sprintf(" AND releases.postdate > NOW() - INTERVAL '%d DAYS' ", $maxage);
			}
		}

		if ($groupname != '-1') {
			$groupID = $this->groups->getIDByName($groupname);
			$groupIDsql = sprintf(' AND releases.groupid = %d ', $groupID);
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
				$sizefromsql = ' AND releases.size > ' . (string)(104857600 * (int)$sizefrom) . ' ';
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
				$sizetosql = ' AND releases.size < ' . (string)(104857600 * (int)$sizeto) . ' ';
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
			"SELECT * FROM (SELECT releases.*, CONCAT(cp.title, ' > ', c.title) AS category_name,
			CONCAT(cp.id, ',', c.id) AS category_ids,
			groups.name AS group_name, rn.id AS nfoid,
			re.releaseid AS reid, cp.id AS categoryparentid
			FROM releases
			INNER JOIN releasesearch rs on rs.releaseid = releases.id
			LEFT OUTER JOIN releasevideo re ON re.releaseid = releases.id
			LEFT OUTER JOIN releasenfo rn ON rn.releaseid = releases.id
			INNER JOIN groups ON groups.id = releases.groupid
			INNER JOIN category c ON c.id = releases.categoryid
			INNER JOIN category cp ON cp.id = c.parentid
			WHERE releases.passwordstatus <= %d %s %s %s %s %s %s %s %s %s %s %s %s %s) r
			ORDER BY r.%s %s LIMIT %d OFFSET %d",
			$this->showPasswords(), $searchnamesql, $usenetnamesql, $maxagesql, $posternamesql, $groupIDsql, $sizefromsql,
			$sizetosql, $hasnfosql, $hascommentssql, $catsrch, $daysnewsql, $daysoldsql, $exccatlist, $order[0],
			$order[1], $limit, $offset
		);
		$wherepos = strpos($sql, 'WHERE');
		$countres = $this->db->queryOneRow(
			'SELECT COUNT(releases.id) AS num FROM releases inner join releasesearch rs on rs.releaseid = releases.id ' .
			substr($sql, $wherepos, strrpos($sql, ')') - $wherepos)
		);
		$res = $this->db->query($sql);
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

			$series = sprintf(' AND UPPER(releases.season) = UPPER(%s)', $this->db->escapeString($series));
		}

		if ($episode != '') {
			if (is_numeric($episode)) {
				$episode = sprintf('E%02d', $episode);
			}

			$like = 'ILIKE';
			if ($this->db->dbSystem() === 'mysql') {
				$like = 'LIKE';
			}
			$episode = sprintf(' AND releases.episode %s %s', $like, $this->db->escapeString('%' . $episode . '%'));
		}

		$searchsql = '';
		if ($name !== '') {
			$searchsql = $this->searchSQL($name, 'searchname');
		}
		$catsrch = $this->categorySQL($cat);

		if ($maxage > 0) {
			if ($this->db->dbSystem() === 'mysql') {
				$maxagesql = sprintf(' AND releases.postdate > NOW() - INTERVAL %d DAY ', $maxage);
			} else {
				$maxagesql = sprintf(" AND releases.postdate > NOW() - INTERVAL '%d DAYS' ", $maxage);
			}
		}
		$sql = sprintf("SELECT releases.*, concat(cp.title, ' > ', c.title) AS category_name, CONCAT(cp.id, ',', c.id) AS category_ids, groups.name AS group_name, rn.id AS nfoid, re.releaseid AS reid FROM releases INNER JOIN category c ON c.id = releases.categoryid INNER JOIN groups ON groups.id = releases.groupid INNER JOIN releasesearch rs on rs.releaseid = releases.id LEFT OUTER JOIN releasevideo re ON re.releaseid = releases.id LEFT OUTER JOIN releasenfo rn ON rn.releaseid = releases.id AND rn.nfo IS NOT NULL INNER JOIN category cp ON cp.id = c.parentid WHERE releases.passwordstatus <= %d %s %s %s %s %s %s ORDER BY postdate DESC LIMIT %d OFFSET %d", $this->showPasswords(), $rageIdsql, $series, $episode, $searchsql, $catsrch, $maxagesql, $limit, $offset);
		$orderpos = strpos($sql, 'ORDER BY');
		$wherepos = strpos($sql, 'WHERE');
		$sqlcount = 'SELECT COUNT(releases.id) AS num FROM releases inner join releasesearch rs on rs.releaseid = releases.id  ' . substr($sql, $wherepos, $orderpos - $wherepos);

		$countres = $this->db->queryOneRow($sqlcount);
		$res = $this->db->query($sql);
		if (count($res) > 0) {
			$res[0]['_totalrows'] = $countres['num'];
		}

		return $res;
	}

	public function searchbyAnidbId($anidbID, $epno = '', $offset = 0, $limit = 100, $name = '', $cat = array(-1), $maxage = -1)
	{
		$anidbID = ($anidbID > -1) ? sprintf(' AND anidbid = %d ', $anidbID) : '';

		$like = 'ILIKE';
		if ($this->db->dbSystem() === 'mysql') {
			$like = 'LIKE';
		}

		is_numeric($epno) ? $epno = sprintf(
			" AND releases.episode %s '%s' ", $like, $this->db->escapeString(
				'%' . $epno . '%'
			)
		) : '';

		$searchsql = '';
		if ($name !== '') {
			$searchsql = $this->searchSQL($name, 'searchname');
		}
		$catsrch = $this->categorySQL($cat);

		$maxagesql = '';
		if ($maxage > 0) {
			if ($this->db->dbSystem() === 'mysql') {
				$maxagesql = sprintf(' AND releases.postdate > NOW() - INTERVAL %d DAY ', $maxage);
			} else {
				$maxagesql = sprintf(" AND releases.postdate > NOW() - INTERVAL '%d DAYS' ", $maxage);
			}
		}

		$sql = sprintf("SELECT releases.*, CONCAT(cp.title, ' > ', c.title) AS category_name, CONCAT(cp.id, ',', c.id) AS category_ids, groups.name AS group_name, rn.id AS nfoid FROM releases INNER JOIN releasesearch rs on rs.releaseid = releases.id INNER JOIN category c ON c.id = releases.categoryid INNER JOIN groups ON groups.id = releases.groupid LEFT OUTER JOIN releasenfo rn ON rn.releaseid = releases.id and rn.nfo IS NOT NULL INNER JOIN category cp ON cp.id = c.parentid WHERE releases.passwordstatus <= %d %s %s %s %s %s ORDER BY postdate DESC LIMIT %d OFFSET %d", $this->showPasswords(), $anidbID, $epno, $searchsql, $catsrch, $maxage, $limit, $offset);
		$orderpos = strpos($sql, 'ORDER BY');
		$wherepos = strpos($sql, 'WHERE');
		$sqlcount = 'SELECT COUNT(releases.id) AS num FROM releases inner join releasesearch rs on rs.releaseid = releases.id ' . substr($sql, $wherepos, $orderpos - $wherepos);

		$countres = $this->db->queryOneRow($sqlcount);
		$res = $this->db->query($sql);
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
			if ($this->db->dbSystem() === 'mysql') {
				$maxage = sprintf(' AND releases.postdate > NOW() - INTERVAL %d DAY ', $maxage);
			} else {
				$maxage = sprintf(" AND releases.postdate > NOW() - INTERVAL '%d DAYS ", $maxage);
			}
		} else {
			$maxage = '';
		}

		$sql = sprintf("SELECT releases.*, concat(cp.title, ' > ', c.title) AS category_name, CONCAT(cp.id, ',', c.id) AS category_ids, groups.name AS group_name, rn.id AS nfoid FROM releases INNER JOIN groups ON groups.id = releases.groupid INNER JOIN category c ON c.id = releases.categoryid INNER JOIN releasesearch rs on rs.releaseid = releases.id LEFT OUTER JOIN releasenfo rn ON rn.releaseid = releases.id AND rn.nfo IS NOT NULL INNER JOIN category cp ON cp.id = c.parentid WHERE releases.passwordstatus <= %d %s %s %s %s ORDER BY postdate DESC LIMIT %d OFFSET %d", $this->showPasswords(), $searchsql, $imdbId, $catsrch, $maxage, $limit, $offset);
		$orderpos = strpos($sql, 'ORDER BY');
		$wherepos = strpos($sql, 'WHERE');
		$sqlcount = 'SELECT COUNT(releases.id) AS num FROM releases inner join releasesearch rs on rs.releaseid = releases.id ' . substr($sql, $wherepos, $orderpos - $wherepos);

		$countres = $this->db->queryOneRow($sqlcount);
		$res = $this->db->query($sql);
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
				$tmpguids[] = $this->db->escapeString($g);
			}
			$gsql = sprintf('guid IN (%s)', implode(',', $tmpguids));
		} else {
			$gsql = sprintf('guid = %s', $this->db->escapeString($guid));
		}
		$sql = sprintf("SELECT releases.*, CONCAT(cp.title, ' > ', c.title) AS category_name, CONCAT(cp.id, ',', c.id) AS category_ids, groups.name AS group_name FROM releases INNER JOIN groups ON groups.id = releases.groupid INNER JOIN category c ON c.id = releases.categoryid INNER JOIN category cp ON cp.id = c.parentid WHERE %s ", $gsql);
		return (is_array($guid)) ? $this->db->query($sql) : $this->db->queryOneRow($sql);
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

			$series = sprintf(' AND UPPER(releases.season) = UPPER(%s)', $this->db->escapeString($series));
		}

		if ($episode != '') {
			if (is_numeric($episode)) {
				$episode = sprintf('E%02d', $episode);
			}

			$episode = sprintf(' AND UPPER(releases.episode) = UPPER(%s)', $this->db->escapeString($episode));
		}
		return $this->db->queryOneRow(sprintf("SELECT releases.*, CONCAT(cp.title, ' > ', c.title) AS category_name, groups.name AS group_name FROM releases INNER JOIN groups ON groups.id = releases.groupid INNER JOIN category c ON c.id = releases.categoryid INNER JOIN category cp ON cp.id = c.parentid WHERE releases.passwordstatus <= %d AND rageid = %d %s %s", $this->showPasswords(), $rageid, $series, $episode));
	}

	public function removeRageIdFromReleases($rageid)
	{
		$res = $this->db->queryOneRow(sprintf('SELECT COUNT(id) AS num FROM releases WHERE rageid = %d', $rageid));
		$this->db->queryExec(sprintf('UPDATE releases SET rageid = -1, seriesfull = NULL, season = NULL, episode = NULL WHERE rageid = %d', $rageid));
		return $res['num'];
	}

	public function removeAnidbIdFromReleases($anidbID)
	{
		$res = $this->db->queryOneRow(sprintf('SELECT COUNT(id) AS num FROM releases WHERE anidbid = %d', $anidbID));
		$this->db->queryExec(sprintf('UPDATE releases SET anidbid = -1, episode = NULL, tvtitle = NULL, tvairdate = NULL WHERE anidbid = %d', $anidbID));
		return $res['num'];
	}

	public function getById($id)
	{
		return $this->db->queryOneRow(sprintf('SELECT releases.*, groups.name AS group_name FROM releases INNER JOIN groups ON groups.id = releases.groupid WHERE releases.id = %d ', $id));
	}

	public function getReleaseNfo($id, $incnfo = true)
	{
		if ($this->db->dbSystem() === 'mysql') {
			$uc = 'UNCOMPRESS(nfo)';
		} else {
			$uc = 'nfo';
		}
		$selnfo = ($incnfo) ? ", {$uc} AS nfo" : '';
		return $this->db->queryOneRow(
			sprintf(
				'SELECT id, releaseid' . $selnfo . ' FROM releasenfo WHERE releaseid = %d AND nfo IS NOT NULL', $id
			)
		);
	}

	public function updateGrab($guid)
	{
		if ($this->updategrabs) {
			$this->db->queryExec(sprintf('UPDATE releases SET grabs = grabs + 1 WHERE guid = %s', $this->db->escapeString($guid)));
		}
	}

	// Sends releases back to other->misc.
	public function resetCategorize($where = '')
	{
		$this->db->queryExec('UPDATE releases SET categoryid = 7010, iscategorized = 0 ' . $where);
	}

	// Categorizes releases.
	// $type = name or searchname
	// Returns the quantity of categorized releases.
	public function categorizeRelease($type, $where = '', $echooutput = false)
	{
		$cat = new Category();
		$relcount = 0;
		$resrel = $this->db->queryDirect('SELECT id, ' . $type . ', groupid FROM releases ' . $where);
		$total = 0;
		if ($resrel !== false) {
			$total = $resrel->rowCount();
		}
		if ($total > 0) {
			foreach ($resrel as $rowrel) {
				$catId = $cat->determineCategory($rowrel[$type], $rowrel['groupid']);
				$this->db->queryExec(sprintf('UPDATE releases SET categoryid = %d, iscategorized = 1 WHERE id = %d', $catId, $rowrel['id']));
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
		// Set table names
		if ($this->tablepergroup === 1) {
			if ($groupID == '') {
				exit($this->c->error("\nYou are using 'tablepergroup', you must use releases_threaded.py"));
			}
			if ($this->db->newtables($groupID) === false) {
				exit("There is a problem creating new parts/files tables for this group.\n");
			}
			$group['cname'] = 'collections_' . $groupID;
			$group['bname'] = 'binaries_' . $groupID;
			$group['pname'] = 'parts_' . $groupID;
		} else {
			$group['cname'] = 'collections';
			$group['bname'] = 'binaries';
			$group['pname'] = 'parts';
		}

		if ($this->echooutput) {
			$this->c->doEcho($this->c->header("Stage 1 -> Try to find complete collections."));
		}

		$stage1 = TIME();
		$where = (!empty($groupID)) ? ' AND c.groupid = ' . $groupID . ' ' : ' ';

		if ($this->db->dbSystem() === 'mysql') {
			// Look if we have all the files in a collection (which have the file count in the subject). Set filecheck to 1.
			$this->db->queryExec(
				'UPDATE ' . $group['cname'] . ' c INNER JOIN (SELECT c.id FROM ' . $group['cname'] . ' c INNER JOIN ' .
				$group['bname'] . ' b ON b.collectionid = c.id WHERE c.totalfiles > 0 AND c.filecheck = 0' . $where .
				'GROUP BY b.collectionid, c.totalfiles, c.id HAVING COUNT(b.id) IN (c.totalfiles, c.totalfiles + 1)) r ON c.id = r.id SET filecheck = 1'
			);
			//$this->db->queryExec('UPDATE '.$group['cname'].' c SET filecheck = 1 WHERE c.id IN (SELECT b.collectionid FROM '.$group['bname'].' b, '.$group['cname'].' c WHERE b.collectionid = c.id GROUP BY b.collectionid, c.totalfiles HAVING (COUNT(b.id) >= c.totalfiles-1)) AND c.totalfiles > 0 AND c.filecheck = 0'.$where);

			// Set filecheck to 16 if theres a file that starts with 0 (ex. [00/100]).
			$this->db->queryExec(
				'UPDATE ' . $group['cname'] . ' c INNER JOIN(SELECT c.id FROM ' . $group['cname'] . ' c INNER JOIN ' .
				$group['bname'] .
				' b ON b.collectionid = c.id WHERE b.filenumber = 0 AND c.totalfiles > 0 AND c.filecheck = 1' . $where .
				'GROUP BY c.id) r ON c.id = r.id SET c.filecheck = 16'
			);

			// Set filecheck to 15 on everything left over, so anything that starts with 1 (ex. [01/100]).
			$this->db->queryExec('UPDATE ' . $group['cname'] . ' c SET filecheck = 15 WHERE filecheck = 1' . $where);

			// If we have all the parts set partcheck to 1.
			// If filecheck 15, check if we have all the parts for a file then set partcheck.
			$this->db->queryExec(
				'UPDATE ' . $group['bname'] . ' b INNER JOIN(SELECT b.id FROM ' . $group['bname'] . ' b INNER JOIN ' .
				$group['pname'] . ' p ON p.binaryid = b.id INNER JOIN ' . $group['cname'] .
				' c ON c.id = b.collectionid WHERE c.filecheck = 15 AND b.partcheck = 0' . $where .
				'GROUP BY b.id, b.totalparts HAVING COUNT(p.id) = b.totalparts) r ON b.id = r.id SET b.partcheck = 1'
			);

			// If filecheck 16, check if we have all the parts+1(because of the 0) then set partcheck.
			$this->db->queryExec(
				'UPDATE ' . $group['bname'] . ' b INNER JOIN(SELECT b.id FROM ' . $group['bname'] . ' b INNER JOIN ' .
				$group['pname'] . ' p ON p.binaryid = b.id INNER JOIN ' . $group['cname'] .
				' c ON c.id = b.collectionid WHERE c.filecheck = 16 AND b.partcheck = 0' . $where .
				'GROUP BY b.id, b.totalparts HAVING COUNT(p.id) >= b.totalparts+1) r ON b.id = r.id SET b.partcheck = 1'
			);

			// Set filecheck to 2 if partcheck = 1.
			$this->db->queryExec(
				'UPDATE ' . $group['cname'] . ' c INNER JOIN(SELECT c.id FROM ' . $group['cname'] . ' c INNER JOIN ' .
				$group['bname'] . ' b ON c.id = b.collectionid WHERE b.partcheck = 1 AND c.filecheck IN (15, 16)' .
				$where .
				'GROUP BY b.collectionid, c.totalfiles, c.id HAVING COUNT(b.id) >= c.totalfiles) r ON c.id = r.id SET filecheck = 2'
			);

			// Set filecheck to 1 if we don't have all the parts.
			$this->db->queryExec(
				'UPDATE ' . $group['cname'] . ' c SET filecheck = 1 WHERE filecheck in (15, 16)' . $where
			);

			// If a collection has not been updated in X hours, set filecheck to 2.
			$query = $this->db->queryExec(
				sprintf(
					"UPDATE " . $group['cname'] . " c SET filecheck = 2, totalfiles = (SELECT COUNT(b.id) FROM " .
					$group['bname'] .
					" b WHERE b.collectionid = c.id) WHERE c.dateadded < NOW() - INTERVAL '%d' HOUR AND c.filecheck IN (0, 1, 10)" .
					$where, $this->delaytimet
				)
			);
		} else {
			// Look if we have all the files in a collection (which have the file count in the subject). Set filecheck to 1.
			$this->db->queryExec(
				'UPDATE ' . $group['cname'] . ' c SET filecheck = 1 FROM (SELECT c.id FROM ' . $group['cname'] .
				' c INNER JOIN ' . $group['bname'] .
				' b ON b.collectionid = c.id WHERE c.totalfiles > 0 AND c.filecheck = 0' . $where .
				'GROUP BY b.collectionid, c.totalfiles, c.id HAVING COUNT(b.id) IN (c.totalfiles, c.totalfiles + 1)) r WHERE c.id = r.id'
			);

			// Set filecheck to 16 if theres a file that starts with 0 (ex. [00/100]).
			$this->db->queryExec(
				'UPDATE ' . $group['cname'] . ' c SET filecheck = 16 FROM (SELECT c.id FROM ' . $group['cname'] .
				' c INNER JOIN ' . $group['bname'] .
				' b ON b.collectionid = c.id WHERE b.filenumber = 0 AND c.totalfiles > 0 AND c.filecheck = 1' . $where .
				'GROUP BY c.id) r WHERE c.id = r.id'
			);

			// Set filecheck to 15 on everything left over, so anything that starts with 1 (ex. [01/100]).
			$this->db->queryExec('UPDATE ' . $group['cname'] . ' c SET filecheck = 15 WHERE filecheck = 1' . $where);

			// If we have all the parts set partcheck to 1.
			// If filecheck 15, check if we have all the parts for a file then set partcheck.
			$this->db->queryExec(
				'UPDATE ' . $group['bname'] . ' b  SET partcheck = 1 FROM (SELECT b.id FROM ' . $group['bname'] .
				' b INNER JOIN ' . $group['pname'] . ' p ON p.binaryid = b.id INNER JOIN ' . $group['cname'] .
				' c ON c.id = b.collectionid WHERE c.filecheck = 15 AND b.partcheck = 0' . $where .
				'GROUP BY b.id, b.totalparts HAVING COUNT(p.id) = b.totalparts) r WHERE b.id = r.id'
			);

			// If filecheck 16, check if we have all the parts+1(because of the 0) then set partcheck.
			$this->db->queryExec(
				'UPDATE ' . $group['bname'] . ' b  SET partcheck = 1 FROM (SELECT b.id FROM ' . $group['bname'] .
				' b INNER JOIN ' . $group['pname'] . ' p ON p.binaryid = b.id INNER JOIN ' . $group['cname'] .
				' c ON c.id = b.collectionid WHERE c.filecheck = 16 AND b.partcheck = 0' . $where .
				'GROUP BY b.id, b.totalparts HAVING COUNT(p.id) >= b.totalparts+1) r WHERE b.id = r.id'
			);

			// Set filecheck to 2 if partcheck = 1.
			$this->db->queryExec(
				'UPDATE ' . $group['cname'] . ' c  SET filecheck = 2 FROM (SELECT c.id FROM ' . $group['cname'] .
				' c INNER JOIN ' . $group['bname'] .
				' b ON c.id = b.collectionid WHERE b.partcheck = 1 AND c.filecheck IN (15, 16)' . $where .
				'GROUP BY b.collectionid, c.totalfiles, c.id HAVING COUNT(b.id) >= c.totalfiles) r WHERE c.id = r.id'
			);

			// Set filecheck to 1 if we don't have all the parts.
			$this->db->queryExec(
				'UPDATE ' . $group['cname'] . ' c SET filecheck = 1 WHERE filecheck in (15, 16)' . $where
			);

			// If a collection has not been updated in X hours, set filecheck to 2.
			$query = $this->db->queryExec(
				sprintf(
					"UPDATE " . $group['cname'] . " c SET filecheck = 2, totalfiles = (SELECT COUNT(b.id) FROM " .
					$group['bname'] .
					" b WHERE b.collectionid = c.id) WHERE c.dateadded < NOW() - INTERVAL '%d' HOUR AND c.filecheck IN (0, 1, 10)" .
					$where, $this->delaytimet
				)
			);
		}

		if ($query !== false && $this->echooutput) {
			$this->c->doEcho(
				$this->c->primary(
					$query->rowCount() + $query->rowCount() .
					" collections set to filecheck = 2 (complete)"
				)
			);
			$this->c->doEcho($this->c->primary($this->consoleTools->convertTime(TIME() - $stage1)), true);
		}
	}

	public function processReleasesStage2($groupID)
	{
		$where = (!empty($groupID)) ? ' AND c.groupid = ' . $groupID : ' ';

		// Set table names
		if ($this->tablepergroup === 1) {
			if ($groupID == '') {
				exit($this->c->error("\nYou are using 'tablepergroup', you must use releases_threaded.py"));
			}
			$group['cname'] = 'collections_' . $groupID;
			$group['bname'] = 'binaries_' . $groupID;
			$group['pname'] = 'parts_' . $groupID;
		} else {
			$group['cname'] = 'collections';
			$group['bname'] = 'binaries';
			$group['pname'] = 'parts';
		}

		if ($this->echooutput) {
			$this->c->doEcho($this->c->header("Stage 2 -> Get the size in bytes of the collection."));
		}

		$stage2 = TIME();
		// Get the total size in bytes of the collection for collections where filecheck = 2.
		$checked = $this->db->queryExec(
			'UPDATE ' . $group['cname'] . ' c SET filesize =
									(SELECT SUM(p.size) FROM ' . $group['pname'] . ' p INNER JOIN ' . $group['bname'] . ' b ON p.binaryid = b.id WHERE b.collectionid = c.id HAVING count(p.id) > 0),
									filecheck = 3 WHERE c.filecheck = 2 AND c.filesize = 0' . $where
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
		if ($this->tablepergroup === 1) {
			if ($groupID == '') {
				exit($this->c->error("\nYou are using 'tablepergroup', you must use releases_threaded.py"));
			}
			$group['cname'] = 'collections_' . $groupID;
			$group['bname'] = 'binaries_' . $groupID;
			$group['pname'] = 'parts_' . $groupID;
		} else {
			$group['cname'] = 'collections';
			$group['bname'] = 'binaries';
			$group['pname'] = 'parts';
		}

		if ($this->echooutput) {
			$this->c->doEcho($this->c->header("Stage 3 -> Delete collections smaller/larger than minimum size/file count from group/site setting."));
		}
		$stage3 = TIME();

		if ($groupID == '') {
			$groupIDs = $this->groups->getActiveIDs();
			foreach ($groupIDs as $groupID) {
				$res = $this->db->query(
					'SELECT id FROM ' . $group['cname'] . ' WHERE filecheck = 3 AND filesize > 0 AND groupid = ' .
					$groupID['id']
				);
				if (count($res) > 0) {
					$minsizecount = 0;
					if ($this->db->dbSystem() === 'mysql') {
						$mscq = $this->db->queryExec(
							"UPDATE " . $group['cname'] .
							" c LEFT JOIN (SELECT g.id, COALESCE(g.minsizetoformrelease, s.minsizetoformrelease) AS minsizetoformrelease FROM groups g INNER JOIN ( SELECT value AS minsizetoformrelease FROM settings WHERE setting = 'minsizetoformrelease' ) s ) g ON g.id = c.groupid SET c.filecheck = 5 WHERE g.minsizetoformrelease != 0 AND c.filecheck = 3 AND c.filesize < g.minsizetoformrelease AND c.filesize > 0 AND groupid = " .
							$groupID['id']
						);
						if ($mscq !== false) {
							$minsizecount = $mscq->rowCount();
						}
					} else {
						$s = $this->db->queryOneRow(
							"SELECT GREATEST(s.value::integer, g.minsizetoformrelease::integer) as size FROM settings s, groups g WHERE s.setting = 'minsizetoformrelease' AND g.id = " .
							$groupID['id']
						);
						if ($s['size'] > 0) {
							$mscq = $this->db->queryExec(
								sprintf(
									'UPDATE ' . $group['cname'] .
									' SET filecheck = 5 WHERE filecheck = 3 AND filesize < %d AND filesize > 0 AND groupid = ' .
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

					$maxfilesizeres = $this->db->queryOneRow("SELECT value FROM settings WHERE setting = 'maxsizetoformrelease'");
					if ($maxfilesizeres['value'] != 0) {
						$mascq = $this->db->queryExec(
							sprintf(
								'UPDATE ' . $group['cname'] .
								' SET filecheck = 5 WHERE filecheck = 3 AND groupid = %d AND filesize > %d ', $groupID['id'], $maxfilesizeres['value']
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
					if ($this->db->dbSystem() === 'mysql') {
						$mifcq = $this->db->queryExec(
							"UPDATE " . $group['cname'] .
							" c LEFT JOIN (SELECT g.id, COALESCE(g.minfilestoformrelease, s.minfilestoformrelease) AS minfilestoformrelease FROM groups g INNER JOIN ( SELECT value AS minfilestoformrelease FROM settings WHERE setting = 'minfilestoformrelease' ) s ) g ON g.id = c.groupid SET c.filecheck = 5 WHERE g.minfilestoformrelease != 0 AND c.filecheck = 3 AND c.totalfiles < g.minfilestoformrelease AND groupid = " .
							$groupID['id']
						);
						if ($mifcq !== false) {
							$minfilecount = $mifcq->rowCount();
						}
					} else {
						$f = $this->db->queryOneRow(
							"SELECT GREATEST(s.value::integer, g.minfilestoformrelease::integer) as files FROM settings s, groups g WHERE s.setting = 'minfilestoformrelease' AND g.id = " .
							$groupID['id']
						);
						if ($f['files'] > 0) {
							$mifcq = $this->db->queryExec(
								sprintf(
									'UPDATE ' . $group['cname'] .
									' SET filecheck = 5 WHERE filecheck = 3 AND filesize < %d AND filesize > 0 AND groupid = ' .
									$groupID['id'], $s['size']
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
			$res = $this->db->queryDirect(
				'SELECT id FROM ' . $group['cname'] . ' WHERE filecheck = 3 AND filesize > 0 AND groupid = ' . $groupID
			);
			if ($res !== false && $res->rowCount() > 0) {
				$minsizecount = 0;
				if ($this->db->dbSystem() === 'mysql') {
					$mscq = $this->db->queryExec(
						"UPDATE " . $group['cname'] .
						" c LEFT JOIN (SELECT g.id, coalesce(g.minsizetoformrelease, s.minsizetoformrelease) AS minsizetoformrelease FROM groups g INNER JOIN ( SELECT value AS minsizetoformrelease FROM settings WHERE setting = 'minsizetoformrelease' ) s ) g ON g.id = c.groupid SET c.filecheck = 5 WHERE g.minsizetoformrelease != 0 AND c.filecheck = 3 AND c.filesize < g.minsizetoformrelease AND c.filesize > 0 AND groupid = " .
						$groupID
					);
					if ($mscq !== false) {
						$minsizecount = $mscq->rowCount();
					}
				} else {
					$s = $this->db->queryOneRow(
						"SELECT GREATEST(s.value::integer, g.minsizetoformrelease::integer) as size FROM settings s, groups g WHERE s.setting = 'minsizetoformrelease' AND g.id = " .
						$groupID
					);
					if ($s['size'] > 0) {
						$mscq = $this->db->queryExec(
							sprintf(
								'UPDATE ' . $group['cname'] .
								' SET filecheck = 5 WHERE filecheck = 3 AND filesize < %d AND filesize > 0 AND groupid = ' .
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

				$maxfilesizeres = $this->db->queryOneRow("SELECT value FROM settings WHERE setting = 'maxsizetoformrelease'");
				if ($maxfilesizeres['value'] != 0) {
					$mascq = $this->db->queryExec(
						sprintf(
							'UPDATE ' . $group['cname'] .
							' SET filecheck = 5 WHERE filecheck = 3 AND filesize > %d ', $maxfilesizeres['value']
						)
					);
					if ($mascq !== false) {
						$maxsizecount = $mascq->rowCount();
					}
					if ($maxsizecount < 0) {
						$maxsizecount = 0;
					}
					$maxsizecounts = $maxsizecount + $maxsizecounts;
				}

				$minfilecount = 0;
				if ($this->db->dbSystem() === 'mysql') {
					$mifcq = $this->db->queryExec(
						"UPDATE " . $group['cname'] .
						" c LEFT JOIN (SELECT g.id, coalesce(g.minfilestoformrelease, s.minfilestoformrelease) AS minfilestoformrelease FROM groups g INNER JOIN ( SELECT value AS minfilestoformrelease FROM settings WHERE setting = 'minfilestoformrelease' ) s ) g ON g.id = c.groupid SET c.filecheck = 5 WHERE g.minfilestoformrelease != 0 AND c.filecheck = 3 AND c.totalfiles < g.minfilestoformrelease AND groupid = " .
						$groupID
					);
					if ($mifcq !== false) {
						$minfilecount = $mifcq->rowCount();
					}
				} else {
					$f = $this->db->queryOneRow(
						"SELECT GREATEST(s.value::integer, g.minfilestoformrelease::integer) as files FROM settings s, groups g WHERE s.setting = 'minfilestoformrelease' AND g.id = " .
						$groupID
					);
					if ($f['files'] > 0) {
						$mifcq = $this->db->queryExec(
							sprintf(
								'UPDATE ' . $group['cname'] .
								' SET filecheck = 5 WHERE filecheck = 3 AND filesize < %d AND filesize > 0 AND groupid = ' .
								$groupID, $s['size']
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
		$categorize = new Category();
		$retcount = $duplicate = 0;
		$where = (!empty($groupID)) ? ' groupid = ' . $groupID . ' AND ' : ' ';

		// Set table names
		if ($this->tablepergroup === 1) {
			if ($groupID == '') {
				exit($this->c->error("\nYou are using 'tablepergroup', you must use releases_threaded.py"));
			}
			$group['cname'] = 'collections_' . $groupID;
			$group['bname'] = 'binaries_' . $groupID;
			$group['pname'] = 'parts_' . $groupID;
		} else {
			$group['cname'] = 'collections';
			$group['bname'] = 'binaries';
			$group['pname'] = 'parts';
		}

		if ($this->echooutput) {
			$this->c->doEcho($this->c->header("Stage 4 -> Create releases."));
		}
		$stage4 = TIME();
		$rescol = $this->db->queryDirect(
			'SELECT ' . $group['cname'] . '.*, groups.name AS gname FROM ' . $group['cname'] .
			' INNER JOIN groups ON ' . $group['cname'] . '.groupid = groups.id WHERE' . $where .
			'filecheck = 3 AND filesize > 0 LIMIT ' . $this->stage5limit
		);
		if ($rescol !== false && $this->echooutput) {
			echo $this->c->primary($rescol->rowCount() . " Collections ready to be converted to releases.");
		}

		if ($rescol !== false && $rescol->rowCount() > 0) {
			$predb = new PreDb($this->echooutput);
			foreach ($rescol as $rowcol) {
				$propername = true;
				$relid = false;
				$cleanRelName = str_replace(array('#', '@', '$', '%', '^', '§', '¨', '©', 'Ö'), '', $rowcol['subject']);
				$cleanerName = $this->releaseCleaning->releaseCleaner($rowcol['subject'], $rowcol['fromname'], $rowcol['filesize'], $rowcol['gname']);
				/* $ncarr = $this->collectionsCleaning->collectionsCleaner($subject, $rowcol['gname']);
				  $cleanerName = $ncarr['subject'];
				  $category = $ncarr['cat'];
				  $relstat = $ncar['rstatus']; */
				$fromname = trim($rowcol['fromname'], "'");
				if (!is_array($cleanerName)) {
					$cleanName = $cleanerName;
				} else {
					$cleanName = $cleanerName['cleansubject'];
					$propername = $cleanerName['properlynamed'];
				}
				$relguid = sha1(uniqid('', true) . mt_rand());

				$category = $categorize->determineCategory($cleanName, $rowcol['groupid']);
				$cleanRelName = utf8_encode($cleanRelName);
				$cleanName = utf8_encode($cleanName);
				$fromname = utf8_encode($fromname);

				// Look for duplicates, duplicates match on releases.name, releases.fromname and releases.size
				// A 1% variance in size is considered the same size when the subject and poster are the same
				$minsize = $rowcol['filesize'] * .99;
				$maxsize = $rowcol['filesize'] * 1.01;

				$dupecheck = $this->db->queryOneRow(sprintf('SELECT id, guid FROM releases WHERE name = %s AND fromname = %s AND size BETWEEN %s AND %s', $this->db->escapeString($cleanRelName), $this->db->escapeString($fromname), $this->db->escapeString($minsize), $this->db->escapeString($maxsize)));
				if (!$dupecheck) {
					if ($propername == true) {
						$relid = $this->db->queryInsert(
							sprintf(
								'INSERT INTO releases
									(name, searchname, totalpart, groupid, adddate, guid, rageid, postdate, fromname,
									size, passwordstatus, haspreview, categoryid, nfostatus, isrenamed, iscategorized)
								VALUES
									(%s, %s, %d, %d, NOW(), %s, -1, %s, %s, %s, %d, -1, %d, -1, 1, 1)',
								$this->db->escapeString($cleanRelName),
								$this->db->escapeString($cleanName),
								$rowcol['totalfiles'],
								$rowcol['groupid'],
								$this->db->escapeString($relguid),
								$this->db->escapeString($rowcol['date']),
								$this->db->escapeString($fromname),
								$this->db->escapeString($rowcol['filesize']),
								($this->site->checkpasswordedrar == '1' ? -1 : 0),
								$category
							)
						);
					} else {
						$relid = $this->db->queryInsert(
							sprintf(
								'INSERT INTO releases
									(name, searchname, totalpart, groupid, adddate, guid, rageid, postdate, fromname,
									size, passwordstatus, haspreview, categoryid, nfostatus, iscategorized)
								VALUES (%s, %s, %d, %d, NOW(), %s, -1, %s, %s, %s, %d, -1, %d, -1, 1)',
								$this->db->escapeString($cleanRelName),
								$this->db->escapeString($cleanName),
								$rowcol['totalfiles'],
								$rowcol['groupid'],
								$this->db->escapeString($relguid),
								$this->db->escapeString($rowcol['date']),
								$this->db->escapeString($fromname),
								$this->db->escapeString($rowcol['filesize']),
								($this->site->checkpasswordedrar == '1' ? -1 : 0),
								$category
							)
						);
					}
				}

				if ($relid) {
					// try to match to predb here
					$predb->matchPre($cleanRelName, $relid);

					// Update collections table to say we inserted the release.
					$this->db->queryExec(
						sprintf(
							'UPDATE ' . $group['cname'] .
							' SET filecheck = 4, releaseid = %d WHERE id = %d', $relid, $rowcol['id']
						)
					);
					$retcount++;
					if ($this->echooutput) {
						echo $this->c->primary('Added release ' . $cleanName);
					}
				} else if (isset($relid) && $relid == false) {
					$this->db->queryExec(
						sprintf(
							'UPDATE ' . $group['cname'] .
							' SET filecheck = 5 WHERE collectionhash = %s', $this->db->escapeString($rowcol['collectionhash'])
						)
					);
					$duplicate++;
				}
			}
		}

		if ($this->echooutput) {
			$this->c->doEcho(
				$this->c->primary(
					number_format($retcount) .
					' Releases added and ' .
					number_format($duplicate) .
					' marked for deletion in ' .
					$this->consoleTools->convertTime(TIME() - $stage4)
				), true
			);
		}
		return $retcount;
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
		$catresrel = $this->db->queryDirect('SELECT c.id AS id, CASE WHEN c.minsize = 0 THEN cp.minsize ELSE c.minsize END AS minsize FROM category c INNER JOIN category cp ON cp.id = c.parentid WHERE c.parentid IS NOT NULL');
		foreach ($catresrel as $catrowrel) {
			if ($catrowrel['minsize'] > 0) {
				//printf("SELECT r.id, r.guid FROM releases r WHERE r.categoryid = %d AND r.size < %d\n", $catrowrel['id'], $catrowrel['minsize']);
				$resrel = $this->db->queryDirect(sprintf('SELECT r.id, r.guid FROM releases r WHERE r.categoryid = %d AND r.size < %d', $catrowrel['id'], $catrowrel['minsize']));
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
				if ($this->db->dbSystem() === 'mysql') {
					$resrel = $this->db->queryDirect(sprintf("SELECT r.id, r.guid FROM releases r LEFT JOIN (SELECT g.id, coalesce(g.minsizetoformrelease, s.minsizetoformrelease) AS minsizetoformrelease FROM groups g INNER JOIN ( SELECT value as minsizetoformrelease FROM settings WHERE setting = 'minsizetoformrelease' ) s WHERE g.id = %s ) g ON g.id = r.groupid WHERE g.minsizetoformrelease != 0 AND r.size < minsizetoformrelease AND r.groupid = %s", $groupID['id'], $groupID['id']));
				} else {
					$resrel = array();
					$s = $this->db->queryOneRow(
						"SELECT GREATEST(s.value::integer, g.minsizetoformrelease::integer) as size FROM settings s, groups g WHERE s.setting = 'minsizetoformrelease' AND g.id = " .
						$groupID['id']
					);
					if ($s['size'] > 0) {
						$resrel = $this->db->queryDirect(sprintf('SELECT id, guid FROM releases WHERE size < %d AND groupid = %d', $s['size'], $groupID['id']));
					}
				}
				if ($resrel !== false && $resrel->rowCount() > 0) {
					foreach ($resrel as $rowrel) {
						$this->fastDelete($rowrel['id'], $rowrel['guid']);
						$minsizecount++;
					}
				}

				$maxfilesizeres = $this->db->queryOneRow("SELECT value FROM settings WHERE setting = 'maxsizetoformrelease'");
				if ($maxfilesizeres['value'] != 0) {
					$resrel = $this->db->queryDirect(sprintf('SELECT id, guid FROM releases WHERE groupid = %d AND size > %d', $groupID['id'], $maxfilesizeres['value']));
					if ($resrel !== false && $resrel->rowCount() > 0) {
						foreach ($resrel as $rowrel) {
							$this->fastDelete($rowrel['id'], $rowrel['guid']);
							$maxsizecount++;
						}
					}
				}

				if ($this->db->dbSystem() === 'mysql') {
					$resrel = $this->db->queryDirect(sprintf("SELECT r.id, r.guid FROM releases r LEFT JOIN (SELECT g.id, coalesce(g.minfilestoformrelease, s.minfilestoformrelease) as minfilestoformrelease FROM groups g INNER JOIN ( SELECT value as minfilestoformrelease FROM settings WHERE setting = 'minfilestoformrelease' ) s WHERE g.id = %d ) g ON g.id = r.groupid WHERE g.minfilestoformrelease != 0 AND r.totalpart < minfilestoformrelease AND r.groupid = %d", $groupID['id'], $groupID['id']));
				} else {
					$resrel = array();
					$f = $this->db->queryOneRow(
						"SELECT GREATEST(s.value::integer, g.minfilestoformrelease::integer) as files FROM settings s, groups g WHERE s.setting = 'minfilestoformrelease' AND g.id = " .
						$groupID['id']
					);
					if ($f['files'] > 0) {
						$resrel = $this->db->queryDirect(sprintf('SELECT id, guid FROM releases WHERE totalpart < %d AND groupid = %d', $f['files'], $groupID['id']));
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
			if ($this->db->dbSystem() === 'mysql') {
				$resrel = $this->db->queryDirect(sprintf("SELECT r.id, r.guid FROM releases r LEFT JOIN (SELECT g.id, coalesce(g.minsizetoformrelease, s.minsizetoformrelease) AS minsizetoformrelease FROM groups g INNER JOIN ( SELECT value AS minsizetoformrelease FROM settings WHERE setting = 'minsizetoformrelease' ) s WHERE g.id = %d ) g ON g.id = r.groupid WHERE g.minsizetoformrelease != 0 AND r.size < minsizetoformrelease AND r.groupid = %d", $groupID, $groupID));
			} else {
				$resrel = array();
				$s = $this->db->queryOneRow(
					"SELECT GREATEST(s.value::integer, g.minsizetoformrelease::integer) as size FROM settings s, groups g WHERE s.setting = 'minsizetoformrelease' AND g.id = " .
					$groupID
				);
				if ($s['size'] > 0) {
					$resrel = $this->db->queryDirect(sprintf('SELECT id, guid FROM releases WHERE size < %d AND groupid = %d', $s['size'], $groupID));
				}
			}
			if ($resrel !== false && $resrel->rowCount() > 0) {
				foreach ($resrel as $rowrel) {
					$this->fastDelete($rowrel['id'], $rowrel['guid']);
					$minsizecount++;
				}
			}

			$maxfilesizeres = $this->db->queryOneRow("SELECT value FROM settings WHERE setting = 'maxsizetoformrelease'");
			if ($maxfilesizeres['value'] != 0) {
				$resrel = $this->db->queryDirect(sprintf('SELECT id, guid FROM releases WHERE groupid = %d AND size > %s', $groupID, $this->db->escapeString($maxfilesizeres['value'])));
				if ($resrel !== false && $resrel->rowCount() > 0) {
					foreach ($resrel as $rowrel) {
						$this->fastDelete($rowrel['id'], $rowrel['guid']);
						$maxsizecount++;
					}
				}
			}

			if ($this->db->dbSystem() === 'mysql') {
				$resrel = $this->db->queryDirect(sprintf("SELECT r.id, r.guid FROM releases r LEFT JOIN (SELECT g.id, coalesce(g.minfilestoformrelease, s.minfilestoformrelease) AS minfilestoformrelease FROM groups g INNER JOIN ( SELECT value AS minfilestoformrelease FROM settings WHERE setting = 'minfilestoformrelease' ) s WHERE g.id = %d ) g ON g.id = r.groupid WHERE g.minfilestoformrelease != 0 AND r.totalpart < minfilestoformrelease AND r.groupid = %d", $groupID, $groupID));
			} else {
				$resrel = array();
				$f = $this->db->queryOneRow(
					"SELECT GREATEST(s.value::integer, g.minfilestoformrelease::integer) as files FROM settings s, groups g WHERE s.setting = 'minfilestoformrelease' AND g.id = " .
					$groupID
				);
				if ($f['files'] > 0) {
					$resrel = $this->db->queryDirect(sprintf('SELECT id, guid FROM releases WHERE totalpart < %d AND groupid = %d', $f['files'], $groupID));
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
		$nzbcount = $reccount = 0;
		$where = (!empty($groupID)) ? ' r.groupid = ' . $groupID . ' AND ' : ' ';

		// Set table names
		if ($this->tablepergroup === 1) {
			if ($groupID == '') {
				exit($this->c->error("\nYou are using 'tablepergroup', you must use releases_threaded.py"));
			}
			$group['cname'] = 'collections_' . $groupID;
			$group['bname'] = 'binaries_' . $groupID;
			$group['pname'] = 'parts_' . $groupID;
		} else {
			$group['cname'] = 'collections';
			$group['bname'] = 'binaries';
			$group['pname'] = 'parts';
		}

		// Create NZB.
		if ($this->echooutput) {
			$this->c->doEcho($this->c->header("Stage 5 -> Create the NZB, mark collections as ready for deletion."));
		}

		$stage5 = TIME();
		$resrel = $this->db->queryDirect(
			"SELECT CONCAT(COALESCE(cp.title,'') , CASE WHEN cp.title IS NULL THEN '' ELSE ' > ' END , c.title) AS title, r.name, r.id, r.guid FROM releases r INNER JOIN category c ON r.categoryid = c.id INNER JOIN category cp ON cp.id = c.parentid WHERE" .
			$where . "nzbstatus = 0"
		);
		$total = 0;
		if ($resrel !== false) {
			$total = $resrel->rowCount();
		}
		if ($total > 0) {
			$nzb = new NZB();
			// Init vars for writing the NZB's.
			$nzb->initiateForWrite($this->db, htmlspecialchars(date('F j, Y, g:i a O'), ENT_QUOTES, 'utf-8'), $groupID);
			foreach ($resrel as $rowrel) {
				$nzb_create = $nzb->writeNZBforReleaseId($rowrel['id'], $rowrel['guid'], $rowrel['name'], $rowrel['title']);
				if ($nzb_create !== false) {
					$this->db->queryExec(
						sprintf(
							'UPDATE %s SET filecheck = 5 WHERE releaseid = %s', $group['cname'], $rowrel['id']
						)
					);
					$nzbcount++;
					if ($this->echooutput) {
						echo $this->consoleTools->overWritePrimary(
							'Creating NZBs: ' . $this->consoleTools->percentString($nzbcount, $total)
						);
					}
				}
			}
			// Reset vars for next use.
			$nzb->cleanForWrite();
		}

		$timing = $this->c->primary($this->consoleTools->convertTime(TIME() - $stage5));
		if ($this->echooutput) {
			$this->c->doEcho(
				$this->c->primary(
					number_format($nzbcount) .
					' NZBs created in ' .
					$timing
				)
			);
		}
		return $nzbcount;
	}

	/**
	 * Process RequestID's.
	 *
	 * @param int $groupID
	 */
	public function processReleasesStage5b($groupID)
	{
		if ($this->site->lookup_reqids == 1 || $this->site->lookup_reqids == 2) {
			$category = new Category();
			$iFoundCnt = 0;
			$stage8 = TIME();

			if ($this->echooutput) {
				$this->c->doEcho($this->c->header("Stage 5b -> Request ID lookup. "));
			}

			// Look for records that potentially have requestID titles and have not been renamed by any other means
			$resRel = $this->db->queryDirect(
				sprintf("
					SELECT r.id, r.name, r.searchname, g.name AS groupname
					FROM releases r
					LEFT JOIN groups g ON r.groupid = g.id
					WHERE r.groupid = %d
					AND  nzbstatus = 1
					AND isrenamed = 0
					AND (isrequestid = 1 AND reqidstatus in (%d, %d) OR (reqidstatus = %d AND adddate > NOW() - INTERVAL %d HOUR))
					LIMIT 100",
					$groupID,
					self::REQID_UPROC,
					self::REQID_BAD,
					self::REQID_NONE,
					(isset($this->site->request_hours) ? (int)$this->site->request_hours : 1)
				)
			);

			if ($resRel !== false && $resRel->rowCount() > 0) {
				$newTitle = false;
				$web = (!empty($this->site->request_url) &&
						(nzedb\utility\getUrl($this->site->request_url) === false ? false : true));

				foreach ($resRel as $rowRel) {
					$newTitle = $local = false;

					// Try to get request id.
					if (preg_match('/\[\s*(\d+)\s*\]/', $rowRel['name'], $requestID)) {
						$requestID = (int)$requestID[1];
					} else {
						$requestID = 0;
					}

					if ($requestID === 0) {
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

						// Do a local lookup first.
						$run = $this->db->queryOneRow(
							sprintf("
								SELECT title
								FROM predb
								WHERE requestid = %d
								AND groupid = %d",
								$requestID, $groupID
							)
						);
						if ($run !== false) {
							$newTitle = $run['title'];
							$local = true;
							$iFoundCnt++;

						// Do a web lookup.
						} else if ($web !== false) {
							$xml = @simplexml_load_file(
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
								$newTitle = $xml->request[0]['name'];
								$iFoundCnt++;
							}
						}
					}

					if ($newTitle !== false) {

						$determinedCat = $category->determineCategory($newTitle, $groupID);
						$this->db->queryExec(
							sprintf('
								UPDATE releases
								SET reqidstatus = %d, isrenamed = 1, proc_files = 1, searchname = %s, categoryid = %d
								WHERE id = %d',
								self::REQID_FOUND,
								$this->db->escapeString($newTitle),
								$determinedCat,
								$rowRel['id']
							)
						);

						if ($this->echooutput) {
							echo $this->c->primary(
								"\n\nNew name:  $newTitle" .
								"\nOld name:  " . $rowRel['searchname'] .
								"\nNew cat:   " . $category->getNameByID($determinedCat) .
								"\nGroup:     " . $rowRel['groupname'] .
								"\nMethod:    " . ($local === true ? 'requestID local' : 'requestID web') .
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
						$this->consoleTools->convertTime(TIME() - $stage8)
					), true
				);
			}
		}
	}

	public function processReleasesStage6($categorize, $postproc, $groupID, $nntp)
	{
		$where = (!empty($groupID)) ? 'WHERE iscategorized = 0 AND groupid = ' . $groupID : 'WHERE iscategorized = 0';

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

	public function processReleasesStage7a($groupID)
	{
		$reccount = $delq = 0;
		$where = ' ';
		$where1 = '';

		// Set table names
		if ($this->tablepergroup === 1) {
			if ($groupID == '') {
				exit($this->c->error("\nYou are using 'tablepergroup', you must use releases_threaded.py"));
			}
			$group['cname'] = 'collections_' . $groupID;
			$group['bname'] = 'binaries_' . $groupID;
			$group['pname'] = 'parts_' . $groupID;
		} else {
			$group['cname'] = 'collections';
			$group['bname'] = 'binaries';
			$group['pname'] = 'parts';
			$where = (!empty($groupID)) ? ' ' . $group['cname'] . '.groupid = ' . $groupID . ' AND ' : ' ';
			$where1 = (!empty($groupID)) ? ' AND ' . $group['cname'] . '.groupid = ' . $groupID : '';
		}

		// Delete old releases and finished collections.
		if ($this->echooutput) {
			echo $this->c->header("Stage 7a -> Delete finished collections.");
		}
		$stage7 = TIME();

		// Completed releases and old collections that were missed somehow.
		if ($this->db->dbSystem() === 'mysql') {
			$delq = $this->db->queryExec(
				sprintf(
					'DELETE ' . $group['cname'] . ', ' . $group['bname'] . ', ' . $group['pname'] . ' FROM ' .
					$group['cname'] . ', ' . $group['bname'] . ', ' . $group['pname'] . ' WHERE' . $where .
					$group['cname'] . '.filecheck = 5 AND ' . $group['cname'] . '.id = ' . $group['bname'] .
					'.collectionid AND ' . $group['bname'] . '.id = ' . $group['pname'] . '.binaryid'
				)
			);
			if ($delq !== false) {
				$reccount += $delq->rowCount();
			}
		} else {
			$idr = $this->db->queryDirect('SELECT id FROM ' . $group['cname'] . ' WHERE filecheck = 5 ' . $where);
			if ($idr !== false && $idr->rowCount() > 0) {
				foreach ($idr as $id) {
					$delqa = $this->db->queryExec(
						sprintf(
							'DELETE FROM ' . $group['pname'] . ' WHERE EXISTS (SELECT id FROM ' . $group['bname'] .
							' WHERE ' . $group['bname'] . '.id = ' . $group['pname'] . '.binaryid AND ' .
							$group['bname'] . '.collectionid = %d)', $id['id']
						)
					);
					if ($delqa !== false) {
						$reccount += $delqa->rowCount();
					}
					$delqb = $this->db->queryExec(
						sprintf(
							'DELETE FROM ' . $group['bname'] . ' WHERE collectionid = %d', $id['id']
						)
					);
					if ($delqb !== false) {
						$reccount += $delqb->rowCount();
					}
				}
				$delqc = $this->db->queryExec('DELETE FROM ' . $group['cname'] . ' WHERE filecheck = 5 ' . $where);
				if ($delqc !== false) {
					$reccount += $delqc->rowCount();
				}
			}
		}

		// Old collections that were missed somehow.
		if ($this->db->dbSystem() === 'mysql') {
			$delq = $this->db->queryExec(
				sprintf(
					'DELETE ' . $group['cname'] . ', ' . $group['bname'] . ', ' . $group['pname'] . ' FROM ' .
					$group['cname'] . ', ' . $group['bname'] . ', ' . $group['pname'] . ' WHERE ' . $group['cname'] .
					'.dateadded < (NOW() - INTERVAL %d HOUR) AND ' . $group['cname'] . '.id = ' . $group['bname'] .
					'.collectionid AND ' . $group['bname'] . '.id = ' . $group['pname'] . '.binaryid' .
					$where1, $this->site->partretentionhours
				)
			);
			if ($delq !== false) {
				$reccount += $delq->rowCount();
			}
		} else {
			$idr = $this->db->queryDirect(
				sprintf(
					"SELECT id FROM " . $group['cname'] . " WHERE dateadded < (NOW() - INTERVAL '%d HOURS')" .
					$where1, $this->site->partretentionhours
				)
			);

			if ($idr !== false && $idr->rowCount() > 0) {
				foreach ($idr as $id) {
					$delqa = $this->db->queryExec(
						sprintf(
							'DELETE FROM ' . $group['pname'] . ' WHERE EXISTS (SELECT id FROM ' . $group['bname'] .
							' WHERE ' . $group['bname'] . '.id = ' . $group['pname'] . '.binaryid AND ' .
							$group['bname'] . '.collectionid = %d)', $id['id']
						)
					);
					if ($delqa !== false) {
						$reccount += $delqa->rowCount();
					}
					$delqb = $this->db->queryExec(
						sprintf(
							'DELETE FROM ' . $group['bname'] . ' WHERE collectionid = %d', $id['id']
						)
					);
					if ($delqb !== false) {
						$reccount += $delqb->rowCount();
					}
				}
			}
			$delqc = $this->db->queryExec(
				sprintf(
					"DELETE FROM " . $group['cname'] . " WHERE dateadded < (NOW() - INTERVAL '%d HOURS')" .
					$where1, $this->site->partretentionhours
				)
			);
			if ($delqc !== false) {
				$reccount += $delqc->rowCount();
			}
		}

		// Binaries/parts that somehow have no collection.
		if ($this->db->dbSystem() === 'mysql') {
			$delqd = $this->db->queryExec(
				'DELETE ' . $group['bname'] . ', ' . $group['pname'] . ' FROM ' . $group['bname'] . ', ' .
				$group['pname'] . ' WHERE ' . $group['bname'] . '.collectionid = 0 AND ' . $group['bname'] . '.id = ' .
				$group['pname'] . '.binaryid'
			);
			if ($delqd !== false) {
				$reccount += $delqd->rowCount();
			}
		} else {
			$delqe = $this->db->queryExec(
				'DELETE FROM ' . $group['pname'] . ' WHERE EXISTS (SELECT id FROM ' . $group['bname'] . ' WHERE ' .
				$group['bname'] . '.id = ' . $group['pname'] . '.binaryid AND ' . $group['bname'] . '.collectionid = 0)'
			);
			if ($delqe !== false) {
				$reccount += $delqe->rowCount();
			}
			$delqf = $this->db->queryExec('DELETE FROM ' . $group['bname'] . ' WHERE collectionid = 0');
			if ($delqf !== false) {
				$reccount += $delqf->rowCount();
			}
		}

		// Parts that somehow have no binaries.
		if (mt_rand(1, 100) % 3 == 0) {
			$delqg = $this->db->queryExec(
				'DELETE FROM ' . $group['pname'] . ' WHERE binaryid NOT IN (SELECT b.id FROM ' . $group['bname'] . ' b)'
			);
			if ($delqg !== false) {
				$reccount += $delqg->rowCount();
			}
		}

		// Binaries that somehow have no collection.
		$delqh = $this->db->queryExec(
			'DELETE FROM ' . $group['bname'] . ' WHERE collectionid NOT IN (SELECT c.id FROM ' . $group['cname'] . ' c)'
		);
		if ($delqh !== false) {
			$reccount += $delqh->rowCount();
		}

		// Collections that somehow have no binaries.
		$delqi = $this->db->queryExec(
			'DELETE FROM ' . $group['cname'] . ' WHERE ' . $group['cname'] . '.id NOT IN (SELECT ' . $group['bname'] .
			'.collectionid FROM ' . $group['bname'] . ') ' . $where1
		);
		if ($delqi !== false) {
			$reccount += $delqi->rowCount();
		}

		if ($this->echooutput) {
			$this->c->doEcho(
				$this->c->primary(
					'Removed ' .
					number_format($reccount) .
					' parts/binaries/collection rows in ' .
					$this->consoleTools->convertTime(TIME() - $stage7)
				)
			);
		}
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
		if ($this->site->releaseretentiondays != 0) {
			if ($this->db->dbSystem() === 'mysql') {
				$result = $this->db->queryDirect(sprintf('SELECT id, guid FROM releases WHERE postdate < (NOW() - INTERVAL %d DAY)', $this->site->releaseretentiondays));
			} else {
				$result = $this->db->queryDirect(sprintf("SELECT id, guid FROM releases WHERE postdate < (NOW() - INTERVAL '%d DAYS')", $this->site->releaseretentiondays));
			}
			if ($result !== false && $result->rowCount() > 0) {
				foreach ($result as $rowrel) {
					$this->fastDelete($rowrel['id'], $rowrel['guid']);
					$remcount++;
				}
			}
		}

		// Passworded releases.
		if ($this->site->deletepasswordedrelease == 1) {
			$result = $this->db->queryDirect(
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
		if ($this->site->deletepossiblerelease == 1) {
			$result = $this->db->queryDirect(
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
				if ($this->db->dbSystem() === 'mysql') {
					$resrel = $this->db->queryDirect(sprintf('SELECT id, guid FROM releases WHERE adddate > (NOW() - INTERVAL %d HOUR) GROUP BY name HAVING COUNT(name) > 1', $this->crosspostt));
				} else {
					$resrel = $this->db->queryDirect(sprintf("SELECT id, guid FROM releases WHERE adddate > (NOW() - INTERVAL '%d HOURS') GROUP BY name, id HAVING COUNT(name) > 1", $this->crosspostt));
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
			$resrel = $this->db->queryDirect(sprintf('SELECT id, guid FROM releases WHERE completion < %d AND completion > 0', $this->completion));
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
				$res = $this->db->queryDirect(sprintf('SELECT id, guid FROM releases WHERE categoryid = %d', $cat['id']));
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
				$rels = $this->db->queryDirect(sprintf('SELECT id, guid FROM releases INNER JOIN (SELECT id AS mid FROM musicinfo WHERE musicinfo.genreid = %d) mi ON releases.musicinfoid = mid', $genre['id']));
				if ($rels !== false && $rels->rowCount() > 0) {
					foreach ($rels as $rel) {
						$disabledgenrecount++;
						$this->fastDelete($rel['id'], $rel['guid']);
					}
				}
			}
		}

		// Misc other.
		if ($this->site->miscotherretentionhours > 0) {
			if ($this->db->dbSystem() === 'mysql') {
				$resrel = $this->db->queryDirect(sprintf('SELECT id, guid FROM releases WHERE categoryid = %d AND adddate <= NOW() - INTERVAL %d HOUR', CATEGORY::CAT_MISC, $this->site->miscotherretentionhours));
			} else {
				$resrel = $this->db->queryDirect(sprintf("SELECT id, guid FROM releases WHERE categoryid = %d AND adddate <= NOW() - INTERVAL '%d HOURS'", CATEGORY::CAT_MISC, $this->site->miscotherretentionhours));
			}
			if ($resrel !== false && $resrel->rowCount() > 0) {
				foreach ($resrel as $rowrel) {
					$this->fastDelete($rowrel['id'], $rowrel['guid']);
					$miscothercount++;
				}
			}
		}

		if ($this->db->dbSystem() === 'mysql') {
			$this->db->queryExec(sprintf('DELETE FROM nzbs WHERE dateadded < (NOW() - INTERVAL %d HOUR)', $this->site->partretentionhours));
		} else {
			$this->db->queryExec(sprintf("DELETE FROM nzbs WHERE dateadded < (NOW() - INTERVAL '%d HOURS')", $this->site->partretentionhours));
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
			if ($this->requestids == '1') {
				$this->processReleasesStage5b($groupID);
			} else if ($this->requestids == '2') {
				$stage8 = TIME();
				if ($this->echooutput) {
					$this->c->doEcho($this->c->header("Stage 5b -> Request ID Threaded lookup."));
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

		$this->processReleases = microtime(true);
		if ($this->echooutput) {
			$this->c->doEcho($this->c->header("Starting release update process (" . date('Y-m-d H:i:s') . ")"), true);
		}

		if (!file_exists($this->site->nzbpath)) {
			if ($this->echooutput) {
				$this->c->doEcho($this->c->error('Bad or missing nzb directory - ' . $this->site->nzbpath), true);
			}
			return;
		}

		$this->processReleasesStage1($groupID);
		$this->processReleasesStage2($groupID);
		$this->processReleasesStage3($groupID);
		$releasesAdded = $this->processReleasesStage4567_loop($categorize, $postproc, $groupID, $nntp);
		$this->processReleasesStage4dot5($groupID);
		$this->processReleasesStage7b();
		$where = (!empty($groupID)) ? ' WHERE groupid = ' . $groupID : '';

		//Print amount of added releases and time it took.
		if ($this->echooutput && $this->tablepergroup == 0) {
			$countID = $this->db->queryOneRow('SELECT COUNT(id) FROM collections ' . $where);
			$this->c->doEcho(
				$this->c->primary(
					'Completed adding ' .
					number_format($releasesAdded) .
					' releases in ' .
					$this->consoleTools->convertTime(number_format(microtime(true) - $this->processReleases, 2)) .
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
		$res = $this->db->queryDirect('SELECT b.id as bid, b.name as bname, c.* FROM binaries b LEFT JOIN collections c ON b.collectionid = c.id');
		if ($res !== false && $res->rowCount() > 0) {
			$timestart = TIME();
			if ($this->echooutput) {
				echo "Going to remake all the collections. This can be a long process, be patient. DO NOT STOP THIS SCRIPT!\n";
			}
			// Reset the collectionhash.
			$this->db->queryExec('UPDATE collections SET collectionhash = 0');
			$delcount = 0;
			$cIDS = array();
			foreach ($res as $row) {

				$groupName = $this->groups->getByNameByID($row['groupid']);
				$newSHA1 = sha1(
						$this->collectionsCleaning->collectionsCleaner(
							$row['bname'],
							$groupName .
							$row['fromname'] .
							$row['groupid'] .
							$row['totalfiles']
						)
				);
				$cres = $this->db->queryOneRow(sprintf('SELECT id FROM collections WHERE collectionhash = %s', $this->db->escapeString($newSHA1)));
				if (!$cres) {
					$cIDS[] = $row['id'];
					$csql = sprintf('INSERT INTO collections (subject, fromname, date, xref, groupid, totalfiles, collectionhash, filecheck, dateadded) VALUES (%s, %s, %s, %s, %d, %s, %s, 0, NOW())', $this->db->escapeString($row['bname']), $this->db->escapeString($row['fromname']), $this->db->escapeString($row['date']), $this->db->escapeString($row['xref']), $row['groupid'], $this->db->escapeString($row['totalfiles']), $this->db->escapeString($newSHA1));
					$collectionID = $this->db->queryInsert($csql);
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
				$this->db->queryExec(sprintf('UPDATE binaries SET collectionid = %d WHERE id = %d', $collectionID, $row['bid']));
			}
			//Remove the old collections.
			$delstart = TIME();
			if ($this->echooutput) {
				echo "\n";
			}
			$totalcIDS = count($cIDS);
			foreach ($cIDS as $cID) {
				$this->db->queryExec(sprintf('DELETE FROM collections WHERE id = %d', $cID));
				$delcount++;
				if ($this->echooutput) {
					$this->consoleTools->overWrite(
						'Deleting old collections:' . $this->consoleTools->percentString($delcount, $totalcIDS) .
						' Time:' . $this->consoleTools->convertTimer(TIME() - $delstart)
					);
				}
			}
			// Delete previous failed attempts.
			$this->db->queryExec('DELETE FROM collections WHERE collectionhash = "0"');

			if ($this->hashcheck == 0) {
				$this->db->queryExec("UPDATE settings SET value = 1 WHERE setting = 'hashcheck'");
			}
			if ($this->echooutput) {
				echo "\nRemade " . count($cIDS) . ' collections in ' .
					$this->consoleTools->convertTime(TIME() - $timestart) . "\n";
			}
		} else {
			$this->db->queryExec("UPDATE settings SET value = 1 WHERE setting = 'hashcheck'");
		}
	}

	public function getTopDownloads()
	{
		return $this->db->query('SELECT id, searchname, guid, adddate, SUM(grabs) AS grabs FROM releases WHERE grabs > 0 GROUP BY id, searchname, adddate HAVING SUM(grabs) > 0 ORDER BY grabs DESC LIMIT 10');
	}

	public function getTopComments()
	{
		return $this->db->query('SELECT id, guid, searchname, adddate, SUM(comments) AS comments FROM releases WHERE comments > 0 GROUP BY id, searchname, adddate HAVING SUM(comments) > 0 ORDER BY comments DESC LIMIT 10');
	}

	public function getRecentlyAdded()
	{
		if ($this->db->dbSystem() === 'mysql') {
			return $this->db->query("SELECT CONCAT(cp.title, ' > ', category.title) AS title, COUNT(*) AS count FROM category INNER JOIN category cp on cp.id = category.parentid INNER JOIN releases ON releases.categoryid = category.id WHERE releases.adddate > NOW() - INTERVAL 1 WEEK GROUP BY concat(cp.title, ' > ', category.title) ORDER BY COUNT(*) DESC");
		} else {
			return $this->db->query("SELECT CONCAT(cp.title, ' > ', category.title) AS title, COUNT(*) AS count FROM category INNER JOIN category cp on cp.id = category.parentid INNER JOIN releases ON releases.categoryid = category.id WHERE releases.adddate > NOW() - INTERVAL '1 WEEK' GROUP BY concat(cp.title, ' > ', category.title) ORDER BY COUNT(*) DESC");
		}
	}

	/**
	 * Get all newest movies with coves for poster wall.
	 * @return array
	 */
	public function getNewestMovies()
	{
		return $this->db->query(
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
	 * Get all newest games with covers for poster wall.
	 * @return array
	 */
	public function getNewestConsole()
	{
		return $this->db->query(
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
	 * Get all newest music with covers for poster wall.
	 * @return array
	 */
	public function getNewestMP3s()
	{
		return $this->db->query(
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
	 * @return array
	 */
	public function getNewestBooks()
	{
		return $this->db->query(
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
