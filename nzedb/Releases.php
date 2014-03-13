<?php

require_once nZEDb_LIBS . 'ZipFile.php';
require_once nZEDb_LIB . 'Util.php';

/**
 * Class Releases
 */
class Releases
{
	/* RAR/ZIP Passworded indicator. */

	const PASSWD_NONE = 0;  // No password.
	const PASSWD_POTENTIAL = 1; // Might have a password.
	const BAD_FILE = 2;   // Possibly broken RAR/ZIP.
	const PASSWD_RAR = 10;  // Definitely passworded.

	/**
	 * @param bool $echooutput
	 */
	function __construct($echooutput = false)
	{
		$this->echooutput = ($echooutput && nZEDb_ECHOCLI);
		$this->db = new DB();
		$this->s = new Sites();
		$this->site = $this->s->get();
		$this->groups = new Groups($this->db);
		$this->collectionsCleaning = new CollectionsCleaning();
		$this->releaseCleaning = new ReleaseCleaning();
		$this->consoleTools = new ConsoleTools();
		$this->stage5limit = (isset($this->site->maxnzbsprocessed)) ? $this->site->maxnzbsprocessed : 1000;
		$this->completion = (isset($this->site->releasecompletion)) ? $this->site->releasecompletion : 0;
		$this->crosspostt = (isset($this->site->crossposttime)) ? $this->site->crossposttime : 2;
		$this->updategrabs = ($this->site->grabstatus == '0') ? false : true;
		$this->requestids = $this->site->lookup_reqids;
		$this->hashcheck = (isset($this->site->hashcheck)) ? $this->site->hashcheck : 0;
		$this->delaytimet = (isset($this->site->delaytime)) ? $this->site->delaytime : 2;
		$this->debug = ($this->site->debuginfo == '0') ? false : true;
		$this->tablepergroup = (isset($this->site->tablepergroup)) ? $this->site->tablepergroup : 0;
		$this->c = new ColorCLI();
	}

	/**
	 * @return array
	 */
	public function get()
	{
		$db = $this->db;
		return $db->query('SELECT releases.*, g.name AS group_name, c.title AS category_name FROM releases LEFT OUTER JOIN category c on c.id = releases.categoryid LEFT OUTER JOIN groups g on g.id = releases.groupid WHERE nzbstatus = 1');
	}

	/**
	 * @param $start
	 * @param $num
	 *
	 * @return array
	 */
	public function getRange($start, $num)
	{
		$db = $this->db;

		if ($start === false) {
			$limit = '';
		} else {
			$limit = ' LIMIT ' . $num . ' OFFSET ' . $start;
		}

		return $db->query("SELECT releases.*, CONCAT(cp.title, ' > ', c.title) AS category_name FROM releases LEFT OUTER JOIN category c on c.id = releases.categoryid LEFT OUTER JOIN category cp on cp.id = c.parentid WHERE nzbstatus = 1 ORDER BY postdate DESC" . $limit);
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
		$db = $this->db;

		$catsrch = $this->categorySQL($cat);

		$maxagesql = $exccatlist = $grpjoin = $grpsql = '';
		if ($maxage > 0) {
			if ($db->dbSystem() == 'mysql') {
				$maxagesql = sprintf(' AND postdate > NOW() - INTERVAL %d DAY ', $maxage);
			} else {
				$maxagesql = sprintf(" AND postdate > NOW() - INTERVAL '%d DAYS' ", $maxage);
			}
		}

		if ($grp != '') {
			$grpjoin = 'LEFT OUTER JOIN groups ON groups.id = releases.groupid';
			$grpsql = sprintf(' AND groups.name = %s ', $db->escapeString($grp));
		}

		if (count($excludedcats) > 0) {
			$exccatlist = ' AND categoryid NOT IN (' . implode(',', $excludedcats) . ')';
		}

		$res = $db->queryOneRow(sprintf('SELECT COUNT(releases.id) AS num FROM releases %s WHERE nzbstatus = 1 AND releases.passwordstatus <= %d %s %s %s %s', $grpjoin, $this->showPasswords(), $catsrch, $maxagesql, $exccatlist, $grpsql));
		return $res['num'];
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
		$db = $this->db;

		if ($start === false) {
			$limit = '';
		} else {
			$limit = ' LIMIT ' . $num . ' OFFSET ' . $start;
		}

		$catsrch = $this->categorySQL($cat);

		$maxagesql = $grpsql = $exccatlist = '';
		if ($maxage > 0) {
			if ($db->dbSystem() == 'mysql') {
				$maxagesql = sprintf(' AND postdate > NOW() - INTERVAL %d DAY ', $maxage);
			} else {
				$maxagesql = sprintf(" AND postdate > NOW() - INTERVAL '%d DAYS' ", $maxage);
			}
		}

		if ($grp != '') {
			$grpsql = sprintf(' AND groups.name = %s ', $db->escapeString($grp));
		}

		if (count($excludedcats) > 0) {
			$exccatlist = ' AND releases.categoryid NOT IN (' . implode(',', $excludedcats) . ')';
		}

		$order = $this->getBrowseOrder($orderby);
		return $db->query(sprintf("SELECT releases.*, CONCAT(cp.title, ' > ', c.title) AS category_name, CONCAT(cp.id, ',', c.id) AS category_ids, groups.name AS group_name, rn.id AS nfoid, re.releaseid AS reid FROM releases LEFT OUTER JOIN groups ON groups.id = releases.groupid LEFT OUTER JOIN releasevideo re ON re.releaseid = releases.id LEFT OUTER JOIN releasenfo rn ON rn.releaseid = releases.id AND rn.nfo IS NOT NULL LEFT OUTER JOIN category c ON c.id = releases.categoryid LEFT OUTER JOIN category cp ON cp.id = c.parentid WHERE nzbstatus = 1 AND releases.passwordstatus <= %d %s %s %s %s ORDER BY %s %s %s", $this->showPasswords(), $catsrch, $maxagesql, $exccatlist, $grpsql, $order[0], $order[1], $limit), true);
	}

	// Return site setting for hiding/showing passworded releases.
	public function showPasswords()
	{
		$db = $this->db;
		$res = $db->queryOneRow("SELECT value FROM site WHERE setting = 'showpasswordedrelease'");
		return $res['value'];
	}

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

	public function getBrowseOrdering()
	{
		return array('name_asc', 'name_desc', 'cat_asc', 'cat_desc', 'posted_asc', 'posted_desc', 'size_asc', 'size_desc', 'files_asc', 'files_desc', 'stats_asc', 'stats_desc');
	}

	public function getForExport($postfrom, $postto, $group)
	{
		$db = $this->db;
		if ($postfrom != '') {
			$dateparts = explode('/', $postfrom);
			if (count($dateparts) == 3) {
				$postfrom = sprintf(' AND postdate > %s ', $db->escapeString($dateparts[2] . '-' . $dateparts[1] . '-' . $dateparts[0] . ' 00:00:00'));
			} else {
				$postfrom = '';
			}
		}

		if ($postto != '') {
			$dateparts = explode('/', $postto);
			if (count($dateparts) == 3) {
				$postto = sprintf(' AND postdate < %s ', $db->escapeString($dateparts[2] . '-' . $dateparts[1] . '-' . $dateparts[0] . ' 23:59:59'));
			} else {
				$postto = '';
			}
		}

		if ($group != '' && $group != '-1') {
			$group = sprintf(' AND groupid = %d ', $group);
		} else {
			$group = '';
		}

		return $db->query(sprintf("SELECT searchname, guid, CONCAT(cp.title,'_',category.title) AS catName FROM releases INNER JOIN category ON releases.categoryid = category.id LEFT OUTER JOIN category cp ON cp.id = category.parentid WHERE nzbstatus = 1 %s %s %s", $postfrom, $postto, $group));
	}

	public function getEarliestUsenetPostDate()
	{
		$db = $this->db;
		if ($db->dbSystem() == 'mysql') {
			$row = $db->queryOneRow("SELECT DATE_FORMAT(min(postdate), '%d/%m/%Y') AS postdate FROM releases");
		} else {
			$row = $db->queryOneRow("SELECT to_char(min(postdate), 'dd/mm/yyyy') AS postdate FROM releases");
		}
		return $row['postdate'];
	}

	public function getLatestUsenetPostDate()
	{
		$db = $this->db;
		if ($db->dbSystem() == 'mysql') {
			$row = $db->queryOneRow("SELECT DATE_FORMAT(max(postdate), '%d/%m/%Y') AS postdate FROM releases");
		} else {
			$row = $db->queryOneRow("SELECT to_char(max(postdate), 'dd/mm/yyyy') AS postdate FROM releases");
		}
		return $row['postdate'];
	}

	public function getReleasedGroupsForSelect($blnIncludeAll = true)
	{
		$db = $this->db;
		$groups = $db->query('SELECT DISTINCT groups.id, groups.name FROM releases INNER JOIN groups on groups.id = releases.groupid');
		$temp_array = array();

		if ($blnIncludeAll) {
			$temp_array[-1] = '--All Groups--';
		}

		foreach ($groups as $group) {
			$temp_array[$group['id']] = $group['name'];
		}

		return $temp_array;
	}

	public function getRss($cat, $num, $uid = 0, $rageid, $anidbid, $airdate = -1)
	{
		$db = $this->db;

		if ($db->dbSystem() == 'mysql') {
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
		if ($db->dbSystem() == 'mysql') {
			$airdate = ($airdate > -1) ? sprintf(' AND releases.tvairdate >= DATE_SUB(CURDATE(), INTERVAL %d DAY) ', $airdate) : '';
		} else {
			$airdate = ($airdate > -1) ? sprintf(" AND releases.tvairdate >= (CURDATE() - INTERVAL '%d DAYS') ", $airdate) : '';
		}

		$sql = sprintf("SELECT releases.*, m.cover, m.imdbid, m.rating, m.plot, m.year, m.genre, m.director, m.actors, g.name as group_name, CONCAT(cp.title, ' > ', c.title) AS category_name, concat(cp.id, ',', c.id) AS category_ids, COALESCE(cp.id,0) AS parentCategoryid, mu.title AS mu_title, mu.url AS mu_url, mu.artist AS mu_artist, mu.publisher AS mu_publisher, mu.releasedate AS mu_releasedate, mu.review AS mu_review, mu.tracks AS mu_tracks, mu.cover AS mu_cover, mug.title AS mu_genre, co.title AS co_title, co.url AS co_url, co.publisher AS co_publisher, co.releasedate AS co_releasedate, co.review AS co_review, co.cover AS co_cover, cog.title AS co_genre FROM releases LEFT OUTER JOIN category c ON c.id = releases.categoryid LEFT OUTER JOIN category cp ON cp.id = c.parentid
						LEFT OUTER JOIN groups g ON g.id = releases.groupid
						LEFT OUTER JOIN movieinfo m ON m.imdbid = releases.imdbid AND m.title != ''
						LEFT OUTER JOIN musicinfo mu ON mu.id = releases.musicinfoid
						LEFT OUTER JOIN genres mug ON mug.id = mu.genreid
						LEFT OUTER JOIN consoleinfo co ON co.id = releases.consoleinfoid
						LEFT OUTER JOIN genres cog ON cog.id = co.genreid %s
						WHERE releases.passwordstatus <= %d %s %s %s %s ORDER BY postdate DESC %s", $cartsrch, $this->showPasswords(), $catsrch, $rage, $anidb, $airdate, $limit);
		return $db->query($sql);
	}

	public function getShowsRss($num, $uid = 0, $excludedcats = array(), $airdate = -1)
	{
		$db = $this->db;

		$exccatlist = '';
		if (count($excludedcats) > 0) {
			$exccatlist = ' AND releases.categoryid NOT IN (' . implode(',', $excludedcats) . ')';
		}

		$usql = $this->uSQL($db->query(sprintf('SELECT rageid, categoryid FROM userseries WHERE userid = %d', $uid), true), 'rageid');
		if ($db->dbSystem() == 'mysql') {
			$airdate = ($airdate > -1) ? sprintf(' AND releases.tvairdate >= DATE_SUB(CURDATE(), INTERVAL %d DAY) ', $airdate) : '';
		} else {
			$airdate = ($airdate > -1) ? sprintf(" AND releases.tvairdate >= (CURDATE() - INTERVAL '%d DAYS') ", $airdate) : '';
		}
		$limit = ' LIMIT ' . ($num > 100 ? 100 : $num) . ' OFFSET 0';

		$sql = sprintf("SELECT releases.*, tvr.rageid, tvr.releasetitle, g.name AS group_name, CONCAT(cp.title, '-', c.title) AS category_name, CONCAT(cp.id, ',', c.id) AS category_ids, COALESCE(cp.id,0)
						AS parentCategoryid FROM releases LEFT OUTER JOIN category c ON c.id = releases.categoryid LEFT OUTER JOIN category cp ON cp.id = c.parentid LEFT OUTER JOIN groups g ON g.id = releases.groupid
						LEFT OUTER JOIN tvrage tvr ON tvr.rageid = releases.rageid WHERE %s %s %s AND releases.passwordstatus <= %d ORDER BY postdate DESC %s", $usql, $exccatlist, $airdate, $this->showPasswords(), $limit);
		return $db->query($sql);
	}

	public function getMyMoviesRss($num, $uid = 0, $excludedcats = array())
	{
		$db = $this->db;

		$exccatlist = '';
		if (count($excludedcats) > 0) {
			$exccatlist = ' AND releases.categoryid NOT IN (' . implode(',', $excludedcats) . ')';
		}

		$usql = $this->uSQL($db->query(sprintf('SELECT imdbid, categoryid FROM usermovies WHERE userid = %d', $uid), true), 'imdbid');
		$limit = ' LIMIT ' . ($num > 100 ? 100 : $num) . ' OFFSET 0';

		$sql = sprintf("SELECT releases.*, mi.title AS releasetitle, g.name AS group_name, concat(cp.title, '-', c.title) AS category_name, CONCAT(cp.id, ',', c.id) AS category_ids, COALESCE(cp.id,0) AS parentCategoryid FROM releases LEFT OUTER JOIN category c ON c.id = releases.categoryid LEFT OUTER JOIN category cp ON cp.id = c.parentid LEFT OUTER JOIN groups g ON g.id = releases.groupid LEFT OUTER JOIN movieinfo mi ON mi.imdbid = releases.imdbid WHERE %s %s AND releases.passwordstatus <= %d ORDER BY postdate DESC %s", $usql, $exccatlist, $this->showPasswords(), $limit);
		return $db->query($sql);
	}

	public function getShowsRange($usershows, $start, $num, $orderby, $maxage = -1, $excludedcats = array())
	{
		$db = $this->db;

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
			if ($db->dbSystem() == 'mysql') {
				$maxagesql = sprintf(' AND releases.postdate > NOW() - INTERVAL %d DAY ', $maxage);
			} else {
				$maxagesql = sprintf(" AND releases.postdate > NOW() - INTERVAL '%d DAYS' ", $maxage);
			}
		}

		$order = $this->getBrowseOrder($orderby);
		$sql = sprintf("SELECT releases.*, CONCAT(cp.title, '-', c.title) AS category_name, CONCAT(cp.id, ',', c.id) AS category_ids, groups.name as group_name, rn.id as nfoid, re.releaseid as reid FROM releases LEFT OUTER JOIN releasevideo re ON re.releaseid = releases.id LEFT OUTER JOIN groups ON groups.id = releases.groupid LEFT OUTER JOIN releasenfo rn ON rn.releaseid = releases.id AND rn.nfo IS NOT NULL LEFT OUTER JOIN category c ON c.id = releases.categoryid LEFT OUTER JOIN category cp ON cp.id = c.parentid WHERE %s %s AND releases.passwordstatus <= %d %s ORDER BY %s %s %s", $usql, $exccatlist, $this->showPasswords(), $maxagesql, $order[0], $order[1], $limit);
		return $db->query($sql, true);
	}

	public function getShowsCount($usershows, $maxage = -1, $excludedcats = array())
	{
		$db = $this->db;

		$exccatlist = $maxagesql = '';
		if (count($excludedcats) > 0) {
			$exccatlist = ' AND releases.categoryid NOT IN (' . implode(',', $excludedcats) . ')';
		}

		$usql = $this->uSQL($usershows, 'rageid');

		if ($maxage > 0) {
			if ($db->dbSystem() == 'mysq') {
				$maxagesql = sprintf(' AND releases.postdate > NOW() - INTERVAL %d DAY ', $maxage);
			} else {
				$maxagesql = sprintf(" AND releases.postdate > NOW() - INTERVAL '%d DAYS' ", $maxage);
			}
		}

		$res = $db->queryOneRow(sprintf('SELECT COUNT(releases.id) AS num FROM releases WHERE %s %s AND releases.passwordstatus <= %d %s', $usql, $exccatlist, $this->showPasswords(), $maxagesql), true);
		return $res['num'];
	}

	public function getCount()
	{
		$db = $this->db;
		$res = $db->queryOneRow('SELECT COUNT(id) AS num FROM releases');
		return $res['num'];
	}

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
			$this->fastDelete($rel['id'], $rel['guid'], $this->site);
		}
	}

	// For most scripts needing to delete a release.
	public function fastDelete($id, $guid, $site)
	{
		$db = $this->db;
		$nzb = new NZB();
		$ri = new ReleaseImage();

		// Delete from disk.
		$nzbpath = $nzb->getNZBPath($guid, $site->nzbpath, false, $site->nzbsplitlevel);
		if (file_exists($nzbpath)) {
			unlink($nzbpath);
		}

		if (isset($id)) {
			// Delete from DB.
			if ($db->dbSystem() == 'mysql') {
				$db->queryExec('DELETE releases, releasenfo, releasecomment, usercart, releasefiles, releaseaudio, releasesubs, releasevideo, releaseextrafull FROM releases LEFT OUTER JOIN releasenfo ON releasenfo.releaseid = releases.id LEFT OUTER JOIN releasecomment ON releasecomment.releaseid = releases.id LEFT OUTER JOIN usercart ON usercart.releaseid = releases.id LEFT OUTER JOIN releasefiles ON releasefiles.releaseid = releases.id LEFT OUTER JOIN releaseaudio ON releaseaudio.releaseid = releases.id LEFT OUTER JOIN releasesubs ON releasesubs.releaseid = releases.id LEFT OUTER JOIN releasevideo ON releasevideo.releaseid = releases.id LEFT OUTER JOIN releaseextrafull ON releaseextrafull.releaseid = releases.id WHERE releases.id = ' . $id);
			} else {
				$db->queryExec('DELETE FROM releasenfo WHERE releaseid = ' . $id);
				$db->queryExec('DELETE FROM releasecomment WHERE releaseid = ' . $id);
				$db->queryExec('DELETE FROM usercart WHERE releaseid = ' . $id);
				$db->queryExec('DELETE FROM releasefiles WHERE releaseid = ' . $id);
				$db->queryExec('DELETE FROM releaseaudio WHERE releaseid = ' . $id);
				$db->queryExec('DELETE FROM releasesubs WHERE releaseid = ' . $id);
				$db->queryExec('DELETE FROM releasevideo WHERE releaseid = ' . $id);
				$db->queryExec('DELETE FROM releaseextrafull WHERE releaseid = ' . $id);
				$db->queryExec('DELETE FROM releases WHERE id = ' . $id);
			}
		}

		// This deletes a file so not in the query.
		if (isset($guid)) {
			$ri->delete($guid);
		}
	}

	// For the site delete button.
	public function deleteSite($id, $isGuid = false)
	{
		if (!is_array($id)) {
			$id = array($id);
		}

		foreach ($id as $identifier) {
			if ($isGuid !== false) {
				$rel = $this->getById($identifier);
			} else {
				$rel = $this->getByGuid($identifier);
			}
			$this->fastDelete($rel['id'], $rel['guid'], $this->site);
		}
	}

	public function update($id, $name, $searchname, $fromname, $category, $parts, $grabs, $size, $posteddate, $addeddate, $rageid, $seriesfull, $season, $episode, $imdbid, $anidbid)
	{
		$db = $this->db;
		$db->queryExec(sprintf('UPDATE releases SET name = %s, searchname = %s, fromname = %s, categoryid = %d, totalpart = %d, grabs = %d, size = %s, postdate = %s, adddate = %s, rageid = %d, seriesfull = %s, season = %s, episode = %s, imdbid = %d, anidbid = %d WHERE id = %d', $db->escapeString($name), $db->escapeString($searchname), $db->escapeString($fromname), $category, $parts, $grabs, $db->escapeString($size), $db->escapeString($posteddate), $db->escapeString($addeddate), $rageid, $db->escapeString($seriesfull), $db->escapeString($season), $db->escapeString($episode), $imdbid, $anidbid, $id));
	}

	public function updatemulti($guids, $category, $grabs, $rageid, $season, $imdbid)
	{
		if (!is_array($guids) || sizeof($guids) < 1) {
			return false;
		}

		$update = array('categoryid' => (($category == '-1') ? '' : $category), 'grabs' => $grabs, 'rageid' => $rageid, 'season' => $season, 'imdbid' => $imdbid);

		$db = $this->db;
		$updateSql = array();
		foreach ($update as $updk => $updv) {
			if ($updv != '') {
				$updateSql[] = sprintf($updk . '=%s', $db->escapeString($updv));
			}
		}

		if (sizeof($updateSql) < 1) {
			return -1;
		}

		$updateGuids = array();
		foreach ($guids as $guid) {
			$updateGuids[] = $db->escapeString($guid);
		}

		$sql = sprintf('UPDATE releases SET ' . implode(', ', $updateSql) . ' WHERE guid IN (%s)', implode(', ', $updateGuids));
		return $db->query($sql);
	}

	// Creates part of a query for some functions.
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
		return $usql .= ') ';
	}

	// Creates part of a query for searches based on the type of search.
	public function searchSQL($search, $db, $type)
	{
		// If the query starts with a ^ it indicates the search is looking for items which start with the term
		// still do the fulltext match, but mandate that all items returned must start with the provided word.
		$words = explode(' ', $search);

		//only used to get a count of words
		$searchwords = $searchsql = '';
		$intwordcount = 0;
		$ft = $db->queryDirect("SHOW INDEX FROM releases WHERE key_name = 'ix_releases_name_searchname_ft'");
		if ($ft->rowCount() !== 2) {
			if ($type === 'name') {
				$ft = $db->queryDirect("SHOW INDEX FROM releases WHERE key_name = 'ix_releases_name_ft'");
			} else if ($type === 'searchname') {
				$ft = $db->queryDirect("SHOW INDEX FROM releases WHERE key_name = 'ix_releases_searchname_ft'");
			}
		}


		if (count($words) > 0) {
			if ($ft->rowCount() !== 0) {
				//at least 1 term needs to be mandatory
				if (!preg_match('/[+|!]/', $search)) {
					$search = '+' . $search;
					$words = explode(' ', $search);
				}
				foreach ($words as $word) {
					$word = trim(rtrim(trim($word), '-'));
					$word = str_replace('!', '+', $word);
					if ($word !== '' && $word !== '-') {
						$searchwords .= sprintf('%s ', $word);
					}
				}
				$searchwords = trim($searchwords);
				if ($ft->rowCount() === 2 && $searchwords != '') {
					$searchsql .= sprintf(" AND MATCH(releases.name, releases.searchname) AGAINST('%s' IN BOOLEAN MODE)", $searchwords);
				} else {
					$searchsql .= sprintf(" AND MATCH(releases.%s) AGAINST('%s' IN BOOLEAN MODE)", $type, $searchwords);
				}
			}
			if ($searchwords === '') {
				$words = explode(' ', $search);
				$like = 'ILIKE';
				if ($db->dbSystem() == 'mysql') {
					$like = 'LIKE';
				}
				foreach ($words as $word) {
					if ($word != '') {
						$word = trim(rtrim(trim($word), '-'));
						if ($intwordcount == 0 && (strpos($word, '^') === 0)) {
							$searchsql .= sprintf(' AND releases.%s %s %s', $type, $like, $db->escapeString(substr($word, 1) . '%'));
						} else if (substr($word, 0, 2) == '--') {
							$searchsql .= sprintf(' AND releases.%s NOT %s %s', $type, $like, $db->escapeString('%' . substr($word, 2) . '%'));
						} else {
							$searchsql .= sprintf(' AND releases.%s %s %s', $type, $like, $db->escapeString('%' . $word . '%'));
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
							$chlist.=', ' . $child['id'];
						}

						if ($chlist != '-99') {
							$catsrch .= ' releases.categoryid IN (' . $chlist . ') OR ';
						}
					} else {
						$catsrch .= sprintf(' releases.categoryid = %d OR ', $category);
					}
				}
			}
			$catsrch.= '1=2 )';
		}
		return $catsrch;
	}

	// Function for searching on the site (by subject, searchname or advanced).
	public function search($searchname, $usenetname, $postername, $groupname, $cat = array(-1), $sizefrom, $sizeto, $hasnfo, $hascomments, $daysnew, $daysold, $offset = 0, $limit = 1000, $orderby = '', $maxage = -1, $excludedcats = array(), $type = 'basic')
	{
		$db = $this->db;

		if ($type !== 'advanced') {
			$catsrch = $this->categorySQL($cat);
		} else {
			$catsrch = '';
			if ($cat != '-1') {
				$catsrch = sprintf(' AND (releases.categoryid = %d) ', $cat);
			}
		}

		$hasnfosql = $hascommentssql = $daysnewsql = $daysoldsql = $maxagesql = $exccatlist = $searchnamesql = $usenetnamesql = $posternamesql = $groupIDsql = '';

		if ($searchname != '-1') {
			$searchnamesql = $this->searchSQL($searchname, $db, 'searchname');
		}

		if ($usenetname != '-1') {
			$usenetnamesql = $this->searchSQL($usenetname, $db, 'name');
		}

		if ($postername != '-1') {
			$posternamesql = $this->searchSQL($postername, $db, 'fromname');
		}

		if ($groupname != '-1') {
			$groupID = $this->groups->getIDByName($db->escapeString($groupname));
			$groupIDsql = sprintf(' AND releases.groupid = %d ', $groupID);
		}

		if ($sizefrom == '-1') {
			$sizefromsql = ('');
		} else if ($sizefrom == '1') {
			$sizefromsql = (' AND releases.size > 104857600 ');
		} else if ($sizefrom == '2') {
			$sizefromsql = (' AND releases.size > 262144000 ');
		} else if ($sizefrom == '3') {
			$sizefromsql = (' AND releases.size > 524288000 ');
		} else if ($sizefrom == '4') {
			$sizefromsql = (' AND releases.size > 1073741824 ');
		} else if ($sizefrom == '5') {
			$sizefromsql = (' AND releases.size > 2147483648 ');
		} else if ($sizefrom == '6') {
			$sizefromsql = (' AND releases.size > 3221225472 ');
		} else if ($sizefrom == '7') {
			$sizefromsql = (' AND releases.size > 4294967296 ');
		} else if ($sizefrom == '8') {
			$sizefromsql = (' AND releases.size > 8589934592 ');
		} else if ($sizefrom == '9') {
			$sizefromsql = (' AND releases.size > 17179869184 ');
		} else if ($sizefrom == '10') {
			$sizefromsql = (' AND releases.size > 34359738368 ');
		} else if ($sizefrom == '11') {
			$sizefromsql = (' AND releases.size > 68719476736 ');
		}

		if ($sizeto == '-1') {
			$sizetosql = ('');
		} else if ($sizeto == '1') {
			$sizetosql = (' AND releases.size < 104857600 ');
		} else if ($sizeto == '2') {
			$sizetosql = (' AND releases.size < 262144000 ');
		} else if ($sizeto == '3') {
			$sizetosql = (' AND releases.size < 524288000 ');
		} else if ($sizeto == '4') {
			$sizetosql = (' AND releases.size < 1073741824 ');
		} else if ($sizeto == '5') {
			$sizetosql = (' AND releases.size < 2147483648 ');
		} else if ($sizeto == '6') {
			$sizetosql = (' AND releases.size < 3221225472 ');
		} else if ($sizeto == '7') {
			$sizetosql = (' AND releases.size < 4294967296 ');
		} else if ($sizeto == '8') {
			$sizetosql = (' AND releases.size < 8589934592 ');
		} else if ($sizeto == '9') {
			$sizetosql = (' AND releases.size < 17179869184 ');
		} else if ($sizeto == '10') {
			$sizetosql = (' AND releases.size < 34359738368 ');
		} else if ($sizeto == '11') {
			$sizetosql = (' AND releases.size < 68719476736 ');
		}

		if ($hasnfo != '0') {
			$hasnfosql = ' AND releases.nfostatus = 1 ';
		}

		if ($hascomments != '0') {
			$hascommentssql = ' AND releases.comments > 0 ';
		}

		if ($daysnew != '-1') {
			if ($db->dbSystem() == 'mysql') {
				$daysnewsql = sprintf(' AND releases.postdate < NOW() - INTERVAL %d DAY ', $daysnew);
			} else {
				$daysnewsql = sprintf(" AND releases.postdate < NOW() - INTERVAL '%d DAYS ", $daysnew);
			}
		}

		if ($daysold != '-1') {
			if ($db->dbSystem() == 'mysql') {
				$daysoldsql = sprintf(' AND releases.postdate > NOW() - INTERVAL %d DAY ', $daysold);
			} else {
				$daysoldsql = sprintf(" AND releases.postdate > NOW() - INTERVAL '%d DAYS' ", $daysold);
			}
		}

		if ($maxage > 0) {
			if ($db->dbSystem() == 'mysql') {
				$maxagesql = sprintf(' AND releases.postdate > NOW() - INTERVAL %d DAY ', $maxage);
			} else {
				$maxagesql = sprintf(" AND releases.postdate > NOW() - INTERVAL '%d DAYS ", $maxage);
			}
		}

		if (count($excludedcats) > 0) {
			$exccatlist = ' AND releases.categoryid NOT IN (' . implode(',', $excludedcats) . ')';
		}

		if ($orderby == '') {
			$order[0] = ' postdate ';
			$order[1] = ' desc ';
		} else {
			$order = $this->getBrowseOrder($orderby);
		}

		$sql = sprintf("SELECT releases.*, CONCAT(cp.title, ' > ', c.title) AS category_name, CONCAT(cp.id, ',', c.id) AS category_ids, groups.name AS group_name, rn.id AS nfoid, re.releaseid AS reid, cp.id AS categoryparentid FROM releases LEFT OUTER JOIN releasevideo re ON re.releaseid = releases.id LEFT OUTER JOIN releasenfo rn ON rn.releaseid = releases.id LEFT OUTER JOIN groups ON groups.id = releases.groupid LEFT OUTER JOIN category c ON c.id = releases.categoryid LEFT OUTER JOIN category cp ON cp.id = c.parentid WHERE releases.passwordstatus <= %d %s %s %s %s %s %s %s %s %s %s %s %s %s ORDER BY %s %s LIMIT %d OFFSET %d", $this->showPasswords(), $searchnamesql, $usenetnamesql, $maxagesql, $posternamesql, $groupIDsql, $sizefromsql, $sizetosql, $hasnfosql, $hascommentssql, $catsrch, $daysnewsql, $daysoldsql, $exccatlist, $order[0], $order[1], $limit, $offset);
		$wherepos = strpos($sql, 'WHERE');
		$countres = $db->queryOneRow('SELECT COUNT(releases.id) AS num FROM releases ' . substr($sql, $wherepos, strpos($sql, 'ORDER BY') - $wherepos));
		$res = $db->query($sql);
		if (count($res) > 0) {
			$res[0]['_totalrows'] = $countres['num'];
		}

		return $res;
	}

	public function searchbyRageId($rageId, $series = '', $episode = '', $offset = 0, $limit = 100, $name = '', $cat = array(-1), $maxage = -1)
	{
		$db = $this->db;

		$rageIdsql = $maxagesql = '';

		if ($rageId != '-1') {
			$rageIdsql = sprintf(' AND rageid = %d ', $rageId);
		}

		if ($series != '') {
			// Exclude four digit series, which will be the year 2010 etc.
			if (is_numeric($series) && strlen($series) != 4) {
				$series = sprintf('S%02d', $series);
			}

			$series = sprintf(' AND UPPER(releases.season) = UPPER(%s)', $db->escapeString($series));
		}

		if ($episode != '') {
			if (is_numeric($episode)) {
				$episode = sprintf('E%02d', $episode);
			}

			$like = 'ILIKE';
			if ($db->dbSystem() == 'mysql') {
				$like = 'LIKE';
			}
			$episode = sprintf(' AND releases.episode %s %s', $like, $db->escapeString('%' . $episode . '%'));
		}

		$searchsql = '';
		if ($name !== '') {
			$searchsql = $this->searchSQL($name, $db, 'searchname');
		}
		$catsrch = $this->categorySQL($cat);

		if ($maxage > 0) {
			if ($db->dbSystem() == 'mysql') {
				$maxagesql = sprintf(' AND releases.postdate > NOW() - INTERVAL %d DAY ', $maxage);
			} else {
				$maxagesql = sprintf(" AND releases.postdate > NOW() - INTERVAL '%d DAYS' ", $maxage);
			}
		}
		$sql = sprintf("SELECT releases.*, concat(cp.title, ' > ', c.title) AS category_name, CONCAT(cp.id, ',', c.id) AS category_ids, groups.name AS group_name, rn.id AS nfoid, re.releaseid AS reid FROM releases LEFT OUTER JOIN category c ON c.id = releases.categoryid LEFT OUTER JOIN groups ON groups.id = releases.groupid LEFT OUTER JOIN releasevideo re ON re.releaseid = releases.id LEFT OUTER JOIN releasenfo rn ON rn.releaseid = releases.id AND rn.nfo IS NOT NULL LEFT OUTER JOIN category cp ON cp.id = c.parentid WHERE releases.passwordstatus <= %d %s %s %s %s %s %s ORDER BY postdate DESC LIMIT %d OFFSET %d", $this->showPasswords(), $rageIdsql, $series, $episode, $searchsql, $catsrch, $maxagesql, $limit, $offset);
		$orderpos = strpos($sql, 'ORDER BY');
		$wherepos = strpos($sql, 'WHERE');
		$sqlcount = 'SELECT COUNT(releases.id) AS num FROM releases ' . substr($sql, $wherepos, $orderpos - $wherepos);

		$countres = $db->queryOneRow($sqlcount);
		$res = $db->query($sql);
		if (count($res) > 0) {
			$res[0]['_totalrows'] = $countres['num'];
		}

		return $res;
	}

	public function searchbyAnidbId($anidbID, $epno = '', $offset = 0, $limit = 100, $name = '', $cat = array(-1), $maxage = -1)
	{
		$db = $this->db;

		$anidbID = ($anidbID > -1) ? sprintf(' AND anidbid = %d ', $anidbID) : '';

		$like = 'ILIKE';
		if ($db->dbSystem() == 'mysql') {
			$like = 'LIKE';
		}

		is_numeric($epno) ? $epno = sprintf(" AND releases.episode %s '%s' ", $like, $db->escapeString('%' . $epno . '%')) : '';

		$searchsql = '';
		if ($name !== '') {
			$searchsql = $this->searchSQL($name, $db, 'searchname');
		}
		$catsrch = $this->categorySQL($cat);

		$maxagesql = '';
		if ($maxage > 0) {
			if ($db->dbSystem() == 'mysql') {
				$maxagesql = sprintf(' AND releases.postdate > NOW() - INTERVAL %d DAY ', $maxage);
			} else {
				$maxagesql = sprintf(" AND releases.postdate > NOW() - INTERVAL '%d DAYS' ", $maxage);
			}
		}

		$sql = sprintf("SELECT releases.*, CONCAT(cp.title, ' > ', c.title) AS category_name, CONCAT(cp.id, ',', c.id) AS category_ids, groups.name AS group_name, rn.id AS nfoid FROM releases LEFT OUTER JOIN category c ON c.id = releases.categoryid LEFT OUTER JOIN groups ON groups.id = releases.groupid LEFT OUTER JOIN releasenfo rn ON rn.releaseid = releases.id and rn.nfo IS NOT NULL LEFT OUTER JOIN category cp ON cp.id = c.parentid WHERE releases.passwordstatus <= %d %s %s %s %s %s ORDER BY postdate DESC LIMIT %d OFFSET %d", $this->showPasswords(), $anidbID, $epno, $searchsql, $catsrch, $maxage, $limit, $offset);
		$orderpos = strpos($sql, 'ORDER BY');
		$wherepos = strpos($sql, 'WHERE');
		$sqlcount = 'SELECT COUNT(releases.id) AS num FROM releases ' . substr($sql, $wherepos, $orderpos - $wherepos);

		$countres = $db->queryOneRow($sqlcount);
		$res = $db->query($sql);
		if (count($res) > 0) {
			$res[0]['_totalrows'] = $countres['num'];
		}

		return $res;
	}

	public function searchbyImdbId($imdbId, $offset = 0, $limit = 100, $name = '', $cat = array(-1), $maxage = -1)
	{
		$db = $this->db;

		if ($imdbId != '-1' && is_numeric($imdbId)) {
			// Pad ID with zeros just in case.
			$imdbId = str_pad($imdbId, 7, '0', STR_PAD_LEFT);
			$imdbId = sprintf(' AND imdbid = %d ', $imdbId);
		} else {
			$imdbId = '';
		}

		$searchsql = '';
		if ($name !== '') {
			$searchsql = $this->searchSQL($name, $db, 'searchname');
		}
		$catsrch = $this->categorySQL($cat);

		if ($maxage > 0) {
			if ($db->dbSystem() == 'mysql') {
				$maxage = sprintf(' AND releases.postdate > NOW() - INTERVAL %d DAY ', $maxage);
			} else {
				$maxage = sprintf(" AND releases.postdate > NOW() - INTERVAL '%d DAYS ", $maxage);
			}
		} else {
			$maxage = '';
		}

		$sql = sprintf("SELECT releases.*, concat(cp.title, ' > ', c.title) AS category_name, CONCAT(cp.id, ',', c.id) AS category_ids, groups.name AS group_name, rn.id AS nfoid FROM releases LEFT OUTER JOIN groups ON groups.id = releases.groupid LEFT OUTER JOIN category c ON c.id = releases.categoryid LEFT OUTER JOIN releasenfo rn ON rn.releaseid = releases.id AND rn.nfo IS NOT NULL LEFT OUTER JOIN category cp ON cp.id = c.parentid WHERE releases.passwordstatus <= %d %s %s %s %s ORDER BY postdate DESC LIMIT %d OFFSET %d", $this->showPasswords(), $searchsql, $imdbId, $catsrch, $maxage, $limit, $offset);
		$orderpos = strpos($sql, 'ORDER BY');
		$wherepos = strpos($sql, 'WHERE');
		$sqlcount = 'SELECT COUNT(releases.id) AS num FROM releases ' . substr($sql, $wherepos, $orderpos - $wherepos);

		$countres = $db->queryOneRow($sqlcount);
		$res = $db->query($sql);
		if (count($res) > 0) {
			$res[0]['_totalrows'] = $countres['num'];
		}

		return $res;
	}

	public function searchSimilar($currentid, $name, $limit = 6, $excludedcats = array())
	{
		$name = $this->getSimilarName($name);
		$results = $this->search($name, -1, -1, -1, array(-1), -1, -1, 0, 0, -1, -1, 0, $limit, '', -1, $excludedcats);
		if (!$results) {
			return $results;
		}

		// Get the category for the parent of this release.
		$currRow = $this->getById($currentid);
		$cat = new Category();
		$catrow = $cat->getById($currRow['categoryid']);
		$parentCat = $catrow['parentid'];

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
		$db = $this->db;
		if (is_array($guid)) {
			$tmpguids = array();
			foreach ($guid as $g) {
				$tmpguids[] = $db->escapeString($g);
			}
			$gsql = sprintf('guid IN (%s)', implode(',', $tmpguids));
		} else {
			$gsql = sprintf('guid = %s', $db->escapeString($guid));
		}
		$sql = sprintf("SELECT releases.*, CONCAT(cp.title, ' > ', c.title) AS category_name, CONCAT(cp.id, ',', c.id) AS category_ids, groups.name AS group_name FROM releases LEFT OUTER JOIN groups ON groups.id = releases.groupid LEFT OUTER JOIN category c ON c.id = releases.categoryid LEFT OUTER JOIN category cp ON cp.id = c.parentid WHERE %s ", $gsql);
		return (is_array($guid)) ? $db->query($sql) : $db->queryOneRow($sql);
	}

	// Writes a zip file of an array of release guids directly to the stream.
	public function getZipped($guids)
	{
		$nzb = new NZB();
		$zipfile = new ZipFile();

		foreach ($guids as $guid) {
			$nzbpath = $nzb->getNZBPath($guid, $this->site->nzbpath, false, $this->site->nzbsplitlevel);

			if (file_exists($nzbpath)) {
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
		$db = $this->db;

		if ($series != '') {
			// Exclude four digit series, which will be the year 2010 etc.
			if (is_numeric($series) && strlen($series) != 4) {
				$series = sprintf('S%02d', $series);
			}

			$series = sprintf(' AND UPPER(releases.season) = UPPER(%s)', $db->escapeString($series));
		}

		if ($episode != '') {
			if (is_numeric($episode)) {
				$episode = sprintf('E%02d', $episode);
			}

			$episode = sprintf(' AND UPPER(releases.episode) = UPPER(%s)', $db->escapeString($episode));
		}
		return $db->queryOneRow(sprintf("SELECT releases.*, CONCAT(cp.title, ' > ', c.title) AS category_name, groups.name AS group_name FROM releases LEFT OUTER JOIN groups ON groups.id = releases.groupid LEFT OUTER JOIN category c ON c.id = releases.categoryid LEFT OUTER JOIN category cp ON cp.id = c.parentid WHERE releases.passwordstatus <= %d AND rageid = %d %s %s", $this->showPasswords(), $rageid, $series, $episode));
	}

	public function removeRageIdFromReleases($rageid)
	{
		$db = $this->db;
		$res = $db->queryOneRow(sprintf('SELECT COUNT(id) AS num FROM releases WHERE rageid = %d', $rageid));
		$ret = $res['num'];
		$res = $db->queryExec(sprintf('UPDATE releases SET rageid = -1, seriesfull = NULL, season = NULL, episode = NULL WHERE rageid = %d', $rageid));
		return $ret;
	}

	public function removeAnidbIdFromReleases($anidbID)
	{
		$db = $this->db;
		$res = $db->queryOneRow(sprintf('SELECT COUNT(id) AS num FROM releases WHERE anidbid = %d', $anidbID));
		$ret = $res['num'];
		$res = $db->queryExec(sprintf('UPDATE releases SET anidbid = -1, episode = NULL, tvtitle = NULL, tvairdate = NULL WHERE anidbid = %d', $anidbID));
		return $ret;
	}

	public function getById($id)
	{
		$db = $this->db;
		return $db->queryOneRow(sprintf('SELECT releases.*, groups.name AS group_name FROM releases LEFT OUTER JOIN groups ON groups.id = releases.groupid WHERE releases.id = %d ', $id));
	}

	public function getReleaseNfo($id, $incnfo = true)
	{
		$db = $this->db;
		if ($db->dbSystem() == 'mysql') {
			$uc = 'UNCOMPRESS(nfo)';
		} else {
			$uc = 'nfo';
		}
		$selnfo = ($incnfo) ? ", {$uc} AS nfo" : '';
		return $db->queryOneRow(sprintf('SELECT id, releaseid' . $selnfo . ' FROM releasenfo WHERE releaseid = %d AND nfo IS NOT NULL', $id));
	}

	public function updateGrab($guid)
	{
		if ($this->updategrabs) {
			$db = $this->db;
			$db->queryExec(sprintf('UPDATE releases SET grabs = grabs + 1 WHERE guid = %s', $db->escapeString($guid)));
		}
	}

	// Sends releases back to other->misc.
	public function resetCategorize($where = '')
	{
		$db = $this->db;
		$db->queryExec('UPDATE releases SET categoryid = 7010, iscategorized = 0 ' . $where);
	}

	// Categorizes releases.
	// $type = name or searchname
	// Returns the quantity of categorized releases.
	public function categorizeRelease($type, $where = '', $echooutput = false)
	{
		$db = $this->db;
		$cat = new Category();
		$relcount = 0;
		$resrel = $db->queryDirect('SELECT id, ' . $type . ', groupid FROM releases ' . $where);
		$total = $resrel->rowCount();
		if (count($resrel) > 0) {
			foreach ($resrel as $rowrel) {
				$catId = $cat->determineCategory($rowrel[$type], $rowrel['groupid']);
				$db->queryExec(sprintf('UPDATE releases SET categoryid = %d, iscategorized = 1 WHERE id = %d', $catId, $rowrel['id']));
				$relcount ++;
				if ($this->echooutput) {
					$this->consoleTools->overWritePrimary('Categorizing: ' . $this->consoleTools->percentString($relcount, $total));
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
		$db = $this->db;

		// Set table names
		if ($this->tablepergroup == 1) {
			if ($groupID == '') {
				exit($this->c->error("\nYou are using 'tablepergroup', you must use releases_threaded.py"));
			}
			if ($db->newtables($groupID) === false) {
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

		// Look if we have all the files in a collection (which have the file count in the subject). Set filecheck to 1.
		$db->queryExec('UPDATE ' . $group['cname'] . ' c INNER JOIN (SELECT c.id FROM ' . $group['cname'] . ' c INNER JOIN ' . $group['bname'] . ' b ON b.collectionid = c.id WHERE c.totalfiles > 0 AND c.filecheck = 0' . $where . 'GROUP BY b.collectionid, c.totalfiles, c.id HAVING COUNT(b.id) IN (c.totalfiles, c.totalfiles + 1)) r ON c.id = r.id SET filecheck = 1');
		//$db->queryExec('UPDATE '.$group['cname'].' c SET filecheck = 1 WHERE c.id IN (SELECT b.collectionid FROM '.$group['bname'].' b, '.$group['cname'].' c WHERE b.collectionid = c.id GROUP BY b.collectionid, c.totalfiles HAVING (COUNT(b.id) >= c.totalfiles-1)) AND c.totalfiles > 0 AND c.filecheck = 0'.$where);
		// Set filecheck to 16 if theres a file that starts with 0 (ex. [00/100]).
		$db->queryExec('UPDATE ' . $group['cname'] . ' c INNER JOIN(SELECT c.id FROM ' . $group['cname'] . ' c INNER JOIN ' . $group['bname'] . ' b ON b.collectionid = c.id WHERE b.filenumber = 0 AND c.totalfiles > 0 AND c.filecheck = 1' . $where . 'GROUP BY c.id) r ON c.id = r.id SET c.filecheck = 16');

		// Set filecheck to 15 on everything left over, so anything that starts with 1 (ex. [01/100]).
		$db->queryExec('UPDATE ' . $group['cname'] . ' c SET filecheck = 15 WHERE filecheck = 1' . $where);

		// If we have all the parts set partcheck to 1.
		// If filecheck 15, check if we have all the parts for a file then set partcheck.
		$db->queryExec('UPDATE ' . $group['bname'] . ' b INNER JOIN(SELECT b.id FROM ' . $group['bname'] . ' b INNER JOIN ' . $group['pname'] . ' p ON p.binaryid = b.id INNER JOIN ' . $group['cname'] . ' c ON c.id = b.collectionid WHERE c.filecheck = 15 AND b.partcheck = 0' . $where . 'GROUP BY b.id, b.totalparts HAVING COUNT(p.id) = b.totalparts) r ON b.id = r.id SET b.partcheck = 1');

		// If filecheck 16, check if we have all the parts+1(because of the 0) then set partcheck.
		$db->queryExec('UPDATE ' . $group['bname'] . ' b INNER JOIN(SELECT b.id FROM ' . $group['bname'] . ' b INNER JOIN ' . $group['pname'] . ' p ON p.binaryid = b.id INNER JOIN ' . $group['cname'] . ' c ON c.id = b.collectionid WHERE c.filecheck = 16 AND b.partcheck = 0' . $where . 'GROUP BY b.id, b.totalparts HAVING COUNT(p.id) >= b.totalparts+1) r ON b.id = r.id SET b.partcheck = 1');

		// Set filecheck to 2 if partcheck = 1.
		$db->queryExec('UPDATE ' . $group['cname'] . ' c INNER JOIN(SELECT c.id FROM ' . $group['cname'] . ' c INNER JOIN ' . $group['bname'] . ' b ON c.id = b.collectionid WHERE b.partcheck = 1 AND c.filecheck IN (15, 16)' . $where . 'GROUP BY b.collectionid, c.totalfiles, c.id HAVING COUNT(b.id) >= c.totalfiles) r ON c.id = r.id SET filecheck = 2');

		// Set filecheck to 1 if we don't have all the parts.
		$db->queryExec('UPDATE ' . $group['cname'] . ' c SET filecheck = 1 WHERE filecheck in (15, 16)' . $where);

		// If a collection has not been updated in X hours, set filecheck to 2.
		if ($db->dbSystem() == 'mysql') {
			$query = $db->queryDirect(sprintf("UPDATE " . $group['cname'] . " c SET filecheck = 2, totalfiles = (SELECT COUNT(b.id) FROM " . $group['bname'] . " b WHERE b.collectionid = c.id) WHERE c.dateadded < NOW() - INTERVAL '%d' HOUR AND c.filecheck IN (0, 1, 10)" . $where, $this->delaytimet));
		} else {
			$query = $db->queryDirect(sprintf("UPDATE " . $group['cname'] . " c SET filecheck = 2, totalfiles = (SELECT COUNT(b.id) FROM " . $group['bname'] . " b WHERE b.collectionid = c.id) WHERE c.dateadded < NOW() - INTERVAL '%d HOURS' AND c.filecheck IN (0, 1, 10)" . $where, $this->delaytimet));
		}

		if ($this->echooutput) {
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
		$db = $this->db;
		$where = (!empty($groupID)) ? ' AND c.groupid = ' . $groupID : ' ';

		// Set table names
		if ($this->tablepergroup == 1) {
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
		$checked = $db->queryDirect('UPDATE ' . $group['cname'] . ' c SET filesize =
									IFNULL((SELECT SUM(p.size) FROM ' . $group['pname'] . ' p LEFT JOIN ' . $group['bname'] . ' b ON p.binaryid = b.id WHERE b.collectionid = c.id), 0),
									filecheck = 3 WHERE c.filecheck = 2 AND c.filesize = 0' . $where);
		if ($this->echooutput) {
			$this->c->doEcho($this->c->primary($checked->rowCount() . " collections set to filecheck = 3(size calculated)"));
			$this->c->doEcho($this->c->primary($this->consoleTools->convertTime(TIME() - $stage2)), true);
		}
	}

	public function processReleasesStage3($groupID)
	{
		$db = $this->db;
		$minsizecounts = $maxsizecounts = $minfilecounts = 0;

		// Set table names
		if ($this->tablepergroup == 1) {
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
				$res = $db->query('SELECT id FROM ' . $group['cname'] . ' WHERE filecheck = 3 AND filesize > 0 AND groupid = ' . $groupID['id']);
				if (count($res) > 0) {
					$minsizecount = 0;
					if ($db->dbSystem() == 'mysql') {
						$mscq = $db->queryDirect("UPDATE " . $group['cname'] . " c LEFT JOIN (SELECT g.id, COALESCE(g.minsizetoformrelease, s.minsizetoformrelease) AS minsizetoformrelease FROM groups g INNER JOIN ( SELECT value AS minsizetoformrelease FROM site WHERE setting = 'minsizetoformrelease' ) s ) g ON g.id = c.groupid SET c.filecheck = 5 WHERE g.minsizetoformrelease != 0 AND c.filecheck = 3 AND c.filesize < g.minsizetoformrelease AND c.filesize > 0 AND groupid = " . $groupID['id']);
						$minsizecount = $mscq->rowCount();
					} else {
						$s = $db->queryOneRow("SELECT GREATEST(s.value::integer, g.minsizetoformrelease::integer) as size FROM site s, groups g WHERE s.setting = 'minsizetoformrelease' AND g.id = " . $groupID['id']);
						if ($s['size'] > 0) {
							$mscq = $db->queryDirect(sprintf('UPDATE ' . $group['cname'] . ' SET filecheck = 5 WHERE filecheck = 3 AND filesize < %d AND filesize > 0 AND groupid = ' . $groupID['id'], $s['size']));
							$minsizecount = $mscq->rowCount();
						}
					}
					if ($minsizecount < 1) {
						$minsizecount = 0;
					}
					$minsizecounts = $minsizecount + $minsizecounts;

					$maxfilesizeres = $db->queryOneRow("SELECT value FROM site WHERE setting = 'maxsizetoformrelease'");
					if ($maxfilesizeres['value'] != 0) {
						$mascq = $db->queryDirect(sprintf('UPDATE ' . $group['cname'] . ' SET filecheck = 5 WHERE filecheck = 3 AND groupid = %d AND filesize > %d ', $groupID['id'], $maxfilesizeres['value']));
						$maxsizecount = $mascq->rowCount();
						if ($maxsizecount < 1) {
							$maxsizecount = 0;
						}
						$maxsizecounts = $maxsizecount + $maxsizecounts;
					}

					$minfilecount = 0;
					if ($db->dbSystem() == 'mysql') {
						$mifcq = $db->queryDirect("UPDATE " . $group['cname'] . " c LEFT JOIN (SELECT g.id, COALESCE(g.minfilestoformrelease, s.minfilestoformrelease) AS minfilestoformrelease FROM groups g INNER JOIN ( SELECT value AS minfilestoformrelease FROM site WHERE setting = 'minfilestoformrelease' ) s ) g ON g.id = c.groupid SET c.filecheck = 5 WHERE g.minfilestoformrelease != 0 AND c.filecheck = 3 AND c.totalfiles < g.minfilestoformrelease AND groupid = " . $groupID['id']);
						$minfilecount = $mifcq->rowCount();
					} else {
						$f = $db->queryOneRow("SELECT GREATEST(s.value::integer, g.minfilestoformrelease::integer) as files FROM site s, groups g WHERE s.setting = 'minfilestoformrelease' AND g.id = " . $groupID['id']);
						if ($f['files'] > 0) {
							$mifcq = $db->queryDirect(sprintf('UPDATE ' . $group['cname'] . ' SET filecheck = 5 WHERE filecheck = 3 AND filesize < %d AND filesize > 0 AND groupid = ' . $groupID['id'], $s['size']));
							$minfilecount = $mifcq->rowCount();
						}
					}
					if ($minfilecount < 1) {
						$minfilecount = 0;
					}
					$minfilecounts = $minfilecount + $minfilecounts;
				}
			}
		} else {
			$res = $db->queryDirect('SELECT id FROM ' . $group['cname'] . ' WHERE filecheck = 3 AND filesize > 0 AND groupid = ' . $groupID);
			if ($res->rowCount() > 0) {
				$minsizecount = 0;
				if ($db->dbSystem() == 'mysql') {
					$mscq = $db->queryDirect("UPDATE " . $group['cname'] . " c LEFT JOIN (SELECT g.id, coalesce(g.minsizetoformrelease, s.minsizetoformrelease) AS minsizetoformrelease FROM groups g INNER JOIN ( SELECT value AS minsizetoformrelease FROM site WHERE setting = 'minsizetoformrelease' ) s ) g ON g.id = c.groupid SET c.filecheck = 5 WHERE g.minsizetoformrelease != 0 AND c.filecheck = 3 AND c.filesize < g.minsizetoformrelease AND c.filesize > 0 AND groupid = " . $groupID);
					$minsizecount = $mscq->rowCount();
				} else {
					$s = $db->queryOneRow("SELECT GREATEST(s.value::integer, g.minsizetoformrelease::integer) as size FROM site s, groups g WHERE s.setting = 'minsizetoformrelease' AND g.id = " . $groupID);
					if ($s['size'] > 0) {
						$mscq = $db->queryDirect(sprintf('UPDATE ' . $group['cname'] . ' SET filecheck = 5 WHERE filecheck = 3 AND filesize < %d AND filesize > 0 AND groupid = ' . $groupID, $s['size']));
						$minsizecount = $mscq->rowCount();
					}
				}
				if ($minsizecount < 0) {
					$minsizecount = 0;
				}
				$minsizecounts = $minsizecount + $minsizecounts;

				$maxfilesizeres = $db->queryOneRow("SELECT value FROM site WHERE setting = 'maxsizetoformrelease'");
				if ($maxfilesizeres['value'] != 0) {
					$mascq = $db->queryDirect(sprintf('UPDATE ' . $group['cname'] . ' SET filecheck = 5 WHERE filecheck = 3 AND filesize > %d ', $maxfilesizeres['value']));
					$maxsizecount = $mascq->rowCount();
					if ($maxsizecount < 0) {
						$maxsizecount = 0;
					}
					$maxsizecounts = $maxsizecount + $maxsizecounts;
				}

				$minfilecount = 0;
				if ($db->dbSystem() == 'mysql') {
					$mifcq = $db->queryDirect("UPDATE " . $group['cname'] . " c LEFT JOIN (SELECT g.id, coalesce(g.minfilestoformrelease, s.minfilestoformrelease) AS minfilestoformrelease FROM groups g INNER JOIN ( SELECT value AS minfilestoformrelease FROM site WHERE setting = 'minfilestoformrelease' ) s ) g ON g.id = c.groupid SET c.filecheck = 5 WHERE g.minfilestoformrelease != 0 AND c.filecheck = 3 AND c.totalfiles < g.minfilestoformrelease AND groupid = " . $groupID);
					$minfilecount = $mifcq->rowCount();
				} else {
					$f = $db->queryOneRow("SELECT GREATEST(s.value::integer, g.minfilestoformrelease::integer) as files FROM site s, groups g WHERE s.setting = 'minfilestoformrelease' AND g.id = " . $groupID);
					if ($f['files'] > 0) {
						$mifcq = $db->queryDirect(sprintf('UPDATE ' . $group['cname'] . ' SET filecheck = 5 WHERE filecheck = 3 AND filesize < %d AND filesize > 0 AND groupid = ' . $groupID, $s['size']));
						$minfilecount = $mifcq->rowCount();
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
		$db = $this->db;
		$categorize = new Category();
		$retcount = $duplicate = 0;
		$where = (!empty($groupID)) ? ' groupid = ' . $groupID . ' AND ' : ' ';

		// Set table names
		if ($this->tablepergroup == 1) {
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
		$rescol = $db->queryDirect('SELECT ' . $group['cname'] . '.*, groups.name AS gname FROM ' . $group['cname'] . ' INNER JOIN groups ON ' . $group['cname'] . '.groupid = groups.id WHERE' . $where . 'filecheck = 3 AND filesize > 0 LIMIT ' . $this->stage5limit);
		if ($this->echooutput) {
			echo $this->c->primary($rescol->rowCount() . " Collections ready to be converted to releases.");
		}

		if ($rescol->rowCount() > 0) {
			$predb = new PreDb($this->echooutput);
			foreach ($rescol as $rowcol) {
				$propername = true;
				$relid = false;
				$cleanRelName = str_replace(array('#', '@', '$', '%', '^', '§', '¨', '©', 'Ö'), '', $rowcol['subject']);
				$cleanerName = $this->releaseCleaning->releaseCleaner($rowcol['subject'], $rowcol['gname']);
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

				$dupecheck = $this->db->queryOneRow(sprintf('SELECT id, guid FROM releases WHERE name = %s AND fromname = %s AND size BETWEEN %s AND %s', $db->escapeString($cleanRelName), $this->db->escapeString($fromname), $db->escapeString($minsize), $db->escapeString($maxsize)));
				if (!$dupecheck) {
					if ($propername == true) {
						$relid = $db->queryInsert(sprintf('INSERT INTO releases (name, searchname, totalpart, groupid, adddate, guid, rageid, postdate, fromname, size, passwordstatus, haspreview, categoryid, nfostatus, isrenamed, iscategorized) VALUES (%s, %s, %d, %d, NOW(), %s, -1, %s, %s, %s, %d, -1, %d, -1, 1, 1)', $db->escapeString($cleanRelName), $db->escapeString($cleanName), $rowcol['totalfiles'], $rowcol['groupid'], $db->escapeString($relguid), $db->escapeString($rowcol['date']), $db->escapeString($fromname), $this->db->escapeString($rowcol['filesize']), ($this->site->checkpasswordedrar == '1' ? -1 : 0), $category));
					} else {
						$relid = $db->queryInsert(sprintf('INSERT INTO releases (name, searchname, totalpart, groupid, adddate, guid, rageid, postdate, fromname, size, passwordstatus, haspreview, categoryid, nfostatus, iscategorized) VALUES (%s, %s, %d, %d, NOW(), %s, -1, %s, %s, %s, %d, -1, %d, -1, 1)', $db->escapeString($cleanRelName), $db->escapeString($cleanName), $rowcol['totalfiles'], $rowcol['groupid'], $db->escapeString($relguid), $db->escapeString($rowcol['date']), $db->escapeString($fromname), $this->db->escapeString($rowcol['filesize']), ($this->site->checkpasswordedrar == '1' ? -1 : 0), $category));
					}
				}

				if ($relid) {
					// try to match to predb here
					$predb->matchPre($cleanRelName, $relid);

					// Update collections table to say we inserted the release.
					$db->queryExec(sprintf('UPDATE ' . $group['cname'] . ' SET filecheck = 4, releaseid = %d WHERE id = %d', $relid, $rowcol['id']));
					$retcount ++;
					if ($this->echooutput) {
						echo $this->c->primary('Added release ' . $cleanName);
					}
				} else if (isset($relid) && $relid == false) {
					$db->queryExec(sprintf('UPDATE ' . $group['cname'] . ' SET filecheck = 5 WHERE collectionhash = %s', $db->escapeString($rowcol['collectionhash'])));
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
		$db = $this->db;
		$minsizecount = $maxsizecount = $minfilecount = $catminsizecount = 0;

		if ($this->echooutput) {
			echo $this->c->header("Stage 4.5 -> Delete releases smaller/larger than minimum size/file count from group/site setting.");
		}

		$stage4dot5 = TIME();
		// Delete smaller than min sizes
		$catresrel = $db->queryDirect('SELECT c.id AS id, CASE WHEN c.minsize = 0 THEN cp.minsize ELSE c.minsize END AS minsize FROM category c LEFT OUTER JOIN category cp ON cp.id = c.parentid WHERE c.parentid IS NOT NULL');
		foreach ($catresrel as $catrowrel) {
			if ($catrowrel['minsize'] > 0) {
				//printf("SELECT r.id, r.guid FROM releases r WHERE r.categoryid = %d AND r.size < %d\n", $catrowrel['id'], $catrowrel['minsize']);
				$resrel = $db->queryDirect(sprintf('SELECT r.id, r.guid FROM releases r WHERE r.categoryid = %d AND r.size < %d', $catrowrel['id'], $catrowrel['minsize']));
				foreach ($resrel as $rowrel) {
					$this->fastDelete($rowrel['id'], $rowrel['guid'], $this->site);
					$catminsizecount ++;
				}
			}
		}

		// Delete larger than max sizes
		if ($groupID == '') {
			$groupIDs = $this->groups->getActiveIDs();

			foreach ($groupIDs as $groupID) {
				if ($db->dbSystem() == 'mysql') {
					$resrel = $db->queryDirect(sprintf("SELECT r.id, r.guid FROM releases r LEFT JOIN (SELECT g.id, coalesce(g.minsizetoformrelease, s.minsizetoformrelease) AS minsizetoformrelease FROM groups g INNER JOIN ( SELECT value as minsizetoformrelease FROM site WHERE setting = 'minsizetoformrelease' ) s WHERE g.id = %s ) g ON g.id = r.groupid WHERE g.minsizetoformrelease != 0 AND r.size < minsizetoformrelease AND r.groupid = %s", $groupID['id'], $groupID['id']));
				} else {
					$resrel = array();
					$s = $db->queryOneRow("SELECT GREATEST(s.value::integer, g.minsizetoformrelease::integer) as size FROM site s, groups g WHERE s.setting = 'minsizetoformrelease' AND g.id = " . $groupID['id']);
					if ($s['size'] > 0) {
						$resrel = $db->queryDirect(sprintf('SELECT id, guid FROM releases WHERE size < %d AND groupid = %d', $s['size'], $groupID['id']));
					}
				}
				if ($resrel->rowCount() > 0) {
					foreach ($resrel as $rowrel) {
						$this->fastDelete($rowrel['id'], $rowrel['guid'], $this->site);
						$minsizecount ++;
					}
				}

				$maxfilesizeres = $db->queryOneRow("SELECT value FROM site WHERE setting = 'maxsizetoformrelease'");
				if ($maxfilesizeres['value'] != 0) {
					$resrel = $db->queryDirect(sprintf('SELECT id, guid FROM releases WHERE groupid = %d AND size > %d', $groupID['id'], $maxfilesizeres['value']));
					if ($resrel->rowCount() > 0) {
						foreach ($resrel as $rowrel) {
							$this->fastDelete($rowrel['id'], $rowrel['guid'], $this->site);
							$maxsizecount ++;
						}
					}
				}

				if ($db->dbSystem() == 'mysql') {
					$resrel = $db->queryDirect(sprintf("SELECT r.id, r.guid FROM releases r LEFT JOIN (SELECT g.id, coalesce(g.minfilestoformrelease, s.minfilestoformrelease) as minfilestoformrelease FROM groups g INNER JOIN ( SELECT value as minfilestoformrelease FROM site WHERE setting = 'minfilestoformrelease' ) s WHERE g.id = %d ) g ON g.id = r.groupid WHERE g.minfilestoformrelease != 0 AND r.totalpart < minfilestoformrelease AND r.groupid = %d", $groupID['id'], $groupID['id']));
				} else {
					$resrel = array();
					$f = $db->queryOneRow("SELECT GREATEST(s.value::integer, g.minfilestoformrelease::integer) as files FROM site s, groups g WHERE s.setting = 'minfilestoformrelease' AND g.id = " . $groupID['id']);
					if ($f['files'] > 0) {
						$resrel = $db->queryDirect(sprintf('SELECT id, guid FROM releases WHERE totalpart < %d AND groupid = %d', $f['files'], $groupID['id']));
					}
				}
				if ($resrel->rowCount() > 0) {
					foreach ($resrel as $rowrel) {
						$this->fastDelete($rowrel['id'], $rowrel['guid'], $this->site);
						$minfilecount ++;
					}
				}
			}
		} else {
			if ($db->dbSystem() == 'mysql') {
				$resrel = $db->queryDirect(sprintf("SELECT r.id, r.guid FROM releases r LEFT JOIN (SELECT g.id, coalesce(g.minsizetoformrelease, s.minsizetoformrelease) AS minsizetoformrelease FROM groups g INNER JOIN ( SELECT value AS minsizetoformrelease FROM site WHERE setting = 'minsizetoformrelease' ) s WHERE g.id = %d ) g ON g.id = r.groupid WHERE g.minsizetoformrelease != 0 AND r.size < minsizetoformrelease AND r.groupid = %d", $groupID, $groupID));
			} else {
				$resrel = array();
				$s = $db->queryOneRow("SELECT GREATEST(s.value::integer, g.minsizetoformrelease::integer) as size FROM site s, groups g WHERE s.setting = 'minsizetoformrelease' AND g.id = " . $groupID);
				if ($s['size'] > 0) {
					$resrel = $db->queryDirect(sprintf('SELECT id, guid FROM releases WHERE size < %d AND groupid = %d', $s['size'], $groupID));
				}
			}
			if ($resrel->rowCount() > 0) {
				foreach ($resrel as $rowrel) {
					$this->fastDelete($rowrel['id'], $rowrel['guid'], $this->site);
					$minsizecount ++;
				}
			}

			$maxfilesizeres = $db->queryOneRow("SELECT value FROM site WHERE setting = 'maxsizetoformrelease'");
			if ($maxfilesizeres['value'] != 0) {
				$resrel = $db->queryDirect(sprintf('SELECT id, guid FROM releases WHERE groupid = %d AND size > %s', $groupID, $db->escapeString($maxfilesizeres['value'])));
				if ($resrel->rowCount() > 0) {
					foreach ($resrel as $rowrel) {
						$this->fastDelete($rowrel['id'], $rowrel['guid'], $this->site);
						$maxsizecount ++;
					}
				}
			}

			if ($db->dbSystem() == 'mysql') {
				$resrel = $db->queryDirect(sprintf("SELECT r.id, r.guid FROM releases r LEFT JOIN (SELECT g.id, coalesce(g.minfilestoformrelease, s.minfilestoformrelease) AS minfilestoformrelease FROM groups g INNER JOIN ( SELECT value AS minfilestoformrelease FROM site WHERE setting = 'minfilestoformrelease' ) s WHERE g.id = %d ) g ON g.id = r.groupid WHERE g.minfilestoformrelease != 0 AND r.totalpart < minfilestoformrelease AND r.groupid = %d", $groupID, $groupID));
			} else {
				$resrel = array();
				$f = $db->queryOneRow("SELECT GREATEST(s.value::integer, g.minfilestoformrelease::integer) as files FROM site s, groups g WHERE s.setting = 'minfilestoformrelease' AND g.id = " . $groupID);
				if ($f['files'] > 0) {
					$resrel = $db->queryDirect(sprintf('SELECT id, guid FROM releases WHERE totalpart < %d AND groupid = %d', $f['files'], $groupID));
				}
			}
			if ($resrel->rowCount() > 0) {
				foreach ($resrel as $rowrel) {
					$this->fastDelete($rowrel['id'], $rowrel['guid'], $this->site);
					$minfilecount ++;
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
		$db = $this->db;
		$nzbcount = $reccount = 0;
		$where = (!empty($groupID)) ? ' r.groupid = ' . $groupID . ' AND ' : ' ';

		// Set table names
		if ($this->tablepergroup == 1) {
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
		$resrel = $db->queryDirect("SELECT CONCAT(COALESCE(cp.title,'') , CASE WHEN cp.title IS NULL THEN '' ELSE ' > ' END , c.title) AS title, r.name, r.id, r.guid FROM releases r INNER JOIN category c ON r.categoryid = c.id INNER JOIN category cp ON cp.id = c.parentid WHERE" . $where . "nzbstatus = 0");
		$total = $resrel->rowCount();
		if ($total > 0) {
			$nzb = new NZB();
			$version = $this->s->version();
			$nzbsplitlevel = $this->site->nzbsplitlevel;
			$nzbpath = $this->site->nzbpath;
			$date = htmlspecialchars(date('F j, Y, g:i a O'), ENT_QUOTES, 'utf-8');
			$this->consoleTools = new ConsoleTools();
			foreach ($resrel as $rowrel) {
				$nzb_create = $nzb->writeNZBforReleaseId($rowrel['id'], $rowrel['guid'], $rowrel['name'], $nzb->getNZBPath($rowrel['guid'], $nzbpath, true, $nzbsplitlevel), $db, $version, $date, $rowrel['title'], $groupID);
				if ($nzb_create != false) {
					$db->queryExec(sprintf('UPDATE ' . $group['cname'] . ' SET filecheck = 5 WHERE releaseid = %s', $rowrel['id']));
					$db->queryExec(sprintf('UPDATE releases SET nzbstatus = 1 WHERE id = %d', $rowrel['id']));
					$nzbcount++;
					if ($this->echooutput) {
						echo $this->consoleTools->overWritePrimary('Creating NZBs: ' . $this->consoleTools->percentString($nzbcount, $total));
					}
				}
			}
		}

		$timing = $this->c->primary($this->consoleTools->convertTime(TIME() - $stage5));
		if ($this->echooutput) {
			$this->c->doEcho(
				$this->c->primary(
					number_format($nzbcount) .
					' NZBs created in ' .
					$timing)
			);
		}
		return $nzbcount;
	}

	public function processReleasesStage5b($groupID)
	{
		if ($this->site->lookup_reqids == 1 || $this->site->lookup_reqids == 2) {
			$db = $this->db;
			$category = new Category();
			$iFoundcnt = 0;
			$where = (!empty($groupID)) ? ' groupid = ' . $groupID . ' AND ' : ' ';
			$stage8 = TIME();

			$hours = (isset($this->site->request_hours)) ? $this->site->request_hours : 1;

			if ($this->echooutput) {
				$this->c->doEcho($this->c->header("Stage 5b -> Request ID lookup. "));
			}

			// Look for records that potentially have requestID titles and have not been renamed by any other means
			$resrel = $db->queryDirect("SELECT r.id, r.name, r.searchname, g.name AS groupname FROM releases r LEFT JOIN groups g ON r.groupid = g.id WHERE" . $where . "nzbstatus = 1 AND isrenamed = 0 AND (isrequestid = 1 AND reqidstatus in (0, -1) OR (reqidstatus = -3 AND adddate > NOW() - INTERVAL " . $hours . " HOUR)) LIMIT 100");

			if ($resrel->rowCount() > 0) {
				$bFound = false;
				$web = true;

				if (!getUrl('http://predb_irc.nzedb.com/')) {
					$web = false;
				}

				foreach ($resrel as $rowrel) {
					// Try to get reqid.
					$requestIDtmp = explode(']', substr($rowrel['name'], 1));
					$bFound = false;
					$newTitle = '';
					$updated = 0;

					if (count($requestIDtmp) >= 1) {
						$requestID = (int) $requestIDtmp[0];
						if ($requestID != 0) {
							// Do a local lookup first
							$newTitle = $this->localLookup($requestID, $rowrel['groupname']);
							if ($newTitle != false && $newTitle != '') {
								$bFound = true;
								$local = true;
								$iFoundcnt++;
							} else if ($web) {
								$newTitle = $this->getReleaseNameFromRequestID($this->site, $requestID, $rowrel['groupname']);
								if ($newTitle != false && $newTitle != '') {
									if (strtolower($newTitle) != strtolower($rowrel['searchname'])) {
										$bFound = true;
										$local = false;
										$iFoundcnt++;
									}
								}
							}
						} else {
							$db->queryExec(sprintf('UPDATE releases SET reqidstatus = -2 WHERE id = %d', $rowrel['id']));
						}
					}
					if ($bFound) {
						if (empty($groupID)) {
							$groupID = $this->groups->getIDByName($rowrel['groupname']);
						}
						$determinedcat = $category->determineCategory($newTitle, $groupID);
						$db->queryExec(sprintf('UPDATE releases SET reqidstatus = 1, isrenamed = 1, proc_files = 1, searchname = %s, categoryid = %d WHERE id = %d', $db->escapeString($newTitle), $determinedcat, $rowrel['id']));
						$newcatname = $category->getNameByID($determinedcat);
						$method = ($local === true) ? 'requestID local' : 'requestID web';
						if ($this->echooutput) {
							echo $this->c->primary("\n\n" . 'New name:  ' . $newTitle .
								"\nOld name:  " . $rowrel['searchname'] .
								"\nNew cat:   " . $newcatname .
								"\nGroup:     " . $rowrel['groupname'] .
								"\nMethod:    " . $method . "\n" .
								'ReleaseID: ' . $rowrel['id']);
						}
						$updated++;
					} else {
						$db->queryExec('UPDATE releases SET reqidstatus = -3 WHERE id = ' . $rowrel['id']);
					}
				}
				if ($this->echooutput && $bFound) {
					echo "\n";
				}
			}

			if ($this->echooutput) {
				$this->c->doEcho(
					$this->c->primary(
						number_format($iFoundcnt) .
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
		$db = $this->db;
		$reccount = $delq = 0;

		// Set table names
		if ($this->tablepergroup == 1) {
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

		$where = (!empty($groupID)) ? ' ' . $group['cname'] . '.groupid = ' . $groupID . ' AND ' : ' ';
		$where1 = (!empty($groupID)) ? ' AND ' . $group['cname'] . '.groupid = ' . $groupID : '';

		// Delete old releases and finished collections.
		if ($this->echooutput) {
			echo $this->c->header("Stage 7a -> Delete finished collections.");
		}
		$stage7 = TIME();

		// Completed releases and old collections that were missed somehow.
		if ($db->dbSystem() == 'mysql') {
			$delq = $db->queryDirect(sprintf('DELETE ' . $group['cname'] . ', ' . $group['bname'] . ', ' . $group['pname'] . ' FROM ' . $group['cname'] . ', ' . $group['bname'] . ', ' . $group['pname'] . ' WHERE' . $where . $group['cname'] . '.filecheck = 5 AND ' . $group['cname'] . '.id = ' . $group['bname'] . '.collectionid AND ' . $group['bname'] . '.id = ' . $group['pname'] . '.binaryid'));
			$reccount += $delq->rowCount();
		} else {
			$idr = $db->queryDirect('SELECT id FROM ' . $group['cname'] . ' WHERE filecheck = 5 ' . $where);
			if ($idr->rowCount() > 0) {
				foreach ($idr as $id) {
					$delqa = $db->queryDirect(sprintf('DELETE FROM ' . $group['pname'] . ' WHERE EXISTS (SELECT id FROM ' . $group['bname'] . ' WHERE ' . $group['bname'] . '.id = ' . $group['pname'] . '.binaryid AND ' . $group['bname'] . '.collectionid = %d)', $id['id']));
					$reccount += $delqa->rowCount();
					$delqb = $db->queryDirect(sprintf('DELETE FROM ' . $group['bname'] . ' WHERE collectionid = %d', $id['id']));
					$reccount += $delqb->rowCount();
				}
				$delqc = $db->queryDirect('DELETE FROM ' . $group['cname'] . ' WHERE filecheck = 5 ' . $where);
				$reccount += $delqc->rowCount();
			}
		}

		// Old collections that were missed somehow.
		if ($db->dbSystem() == 'mysql') {
			$delq = $db->queryDirect(sprintf('DELETE ' . $group['cname'] . ', ' . $group['bname'] . ', ' . $group['pname'] . ' FROM ' . $group['cname'] . ', ' . $group['bname'] . ', ' . $group['pname'] . ' WHERE ' . $group['cname'] . '.dateadded < (NOW() - INTERVAL %d HOUR) AND '. $group['cname'] . '.id = ' . $group['bname'] . '.collectionid AND ' . $group['bname'] . '.id = ' . $group['pname'] . '.binaryid' . $where1, $this->site->partretentionhours));
			$reccount += $delq->rowCount();
		} else {
			$idr = $db->queryDirect(sprintf("SELECT id FROM " . $group['cname'] . " WHERE dateadded < (NOW() - INTERVAL '%d HOURS')" . $where1, $this->site->partretentionhours));
			if ($idr->rowCount() > 0) {
				foreach ($idr as $id) {
					$delqa = $db->queryDirect(sprintf('DELETE FROM ' . $group['pname'] . ' WHERE EXISTS (SELECT id FROM ' . $group['bname'] . ' WHERE ' . $group['bname'] . '.id = ' . $group['pname'] . '.binaryid AND ' . $group['bname'] . '.collectionid = %d)', $id['id']));
					$reccount += $delqa->rowCount();
					$delqb = $db->queryDirect(sprintf('DELETE FROM ' . $group['bname'] . ' WHERE collectionid = %d', $id['id']));
					$reccount += $delqb->rowCount();
				}
			}
			$delqc = $db->queryDirect(sprintf("DELETE FROM " . $group['cname'] . " WHERE dateadded < (NOW() - INTERVAL '%d HOURS')" . $where1, $this->site->partretentionhours));
			$reccount += $delqc->rowCount();
		}

		// Binaries/parts that somehow have no collection.
		if ($db->dbSystem() == 'mysql') {
			$delqd = $db->queryDirect('DELETE ' . $group['bname'] . ', ' . $group['pname'] . ' FROM ' . $group['bname'] . ', ' . $group['pname'] . ' WHERE ' . $group['bname'] . '.collectionid = 0 AND ' . $group['bname'] . '.id = ' . $group['pname'] . '.binaryid');
			$reccount += $delqd->rowCount();
		} else {
			$delqe = $db->queryDirect('DELETE FROM ' . $group['pname'] . ' WHERE EXISTS (SELECT id FROM ' . $group['bname'] . ' WHERE ' . $group['bname'] . '.id = ' . $group['pname'] . '.binaryid AND ' . $group['bname'] . '.collectionid = 0)');
			$reccount += $delqe->rowCount();
			$delqf = $db->queryDirect('DELETE FROM ' . $group['bname'] . ' WHERE collectionid = 0');
			$reccount += $delqf->rowCount();
		}

		// Parts that somehow have no binaries.
		if (mt_rand(1, 100) % 3 == 0) {
			$delqg = $db->queryDirect('DELETE FROM ' . $group['pname'] . ' WHERE binaryid NOT IN (SELECT b.id FROM ' . $group['bname'] . ' b)');
			$reccount += $delqg->rowCount();
		}

		// Binaries that somehow have no collection.
		$delqh = $db->queryDirect('DELETE FROM ' . $group['bname'] . ' WHERE collectionid NOT IN (SELECT c.id FROM ' . $group['cname'] . ' c)');
		$reccount += $delqh->rowCount();

		// Collections that somehow have no binaries.
		$delqi = $db->queryDirect('DELETE FROM ' . $group['cname'] . ' WHERE ' . $group['cname'] . '.id NOT IN (SELECT ' . $group['bname'] . '.collectionid FROM ' . $group['bname'] . ') ' . $where1);
		$reccount += $delqi->rowCount();

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
		$db = $this->db;

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
			if ($db->dbSystem() == 'mysql') {
				$result = $db->queryDirect(sprintf('SELECT id, guid FROM releases WHERE postdate < (NOW() - INTERVAL %d DAY)', $this->site->releaseretentiondays));
			} else {
				$result = $db->queryDirect(sprintf("SELECT id, guid FROM releases WHERE postdate < (NOW() - INTERVAL '%d DAYS')", $this->site->releaseretentiondays));
			}
			if ($result->rowCount() > 0) {
				foreach ($result as $rowrel) {
					$this->fastDelete($rowrel['id'], $rowrel['guid'], $this->site);
					$remcount ++;
				}
			}
		}

		// Passworded releases.
		if ($this->site->deletepasswordedrelease == 1) {
			$result = $db->queryDirect('SELECT id, guid FROM releases WHERE passwordstatus = ' . Releases::PASSWD_RAR);
			if ($result->rowCount() > 0) {
				foreach ($result as $rowrel) {
					$this->fastDelete($rowrel['id'], $rowrel['guid'], $this->site);
					$passcount ++;
				}
			}
		}

		// Possibly passworded releases.
		if ($this->site->deletepossiblerelease == 1) {
			$result = $db->queryDirect('SELECT id, guid FROM releases WHERE passwordstatus = ' . Releases::PASSWD_POTENTIAL);
			if ($result->rowCount() > 0) {
				foreach ($result as $rowrel) {
					$this->fastDelete($rowrel['id'], $rowrel['guid'], $this->site);
					$passcount ++;
				}
			}
		}

		// Crossposted releases.
		do {
			if ($this->crosspostt != 0) {
				if ($db->dbSystem() == 'mysql') {
					$resrel = $db->queryDirect(sprintf('SELECT id, guid FROM releases WHERE adddate > (NOW() - INTERVAL %d HOUR) GROUP BY name HAVING COUNT(name) > 1', $this->crosspostt));
				} else {
					$resrel = $db->queryDriect(sprintf("SELECT id, guid FROM releases WHERE adddate > (NOW() - INTERVAL '%d HOURS') GROUP BY name HAVING COUNT(name) > 1", $this->crosspostt));
				}
				$total = $resrel->rowCount();
				if ($total > 0) {
					foreach ($resrel as $rowrel) {
						$this->fastDelete($rowrel['id'], $rowrel['guid'], $this->site);
						$dupecount ++;
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
			$resrel = $db->queryDirect(sprintf('SELECT id, guid FROM releases WHERE completion < %d AND completion > 0', $this->completion));
			if ($resrel->rowCount() > 0) {
				foreach ($resrel as $rowrel) {
					$this->fastDelete($rowrel['id'], $rowrel['guid'], $this->site);
					$completioncount ++;
				}
			}
		}

		// Disabled categories.
		$catlist = $category->getDisabledIDs();
		if (count($catlist) > 0) {
			foreach ($catlist as $cat) {
				$res = $db->queryDirect(sprintf('SELECT id, guid FROM releases WHERE categoryid = %d', $cat['id']));
				if ($res->rowCount() > 0) {
					foreach ($res as $rel) {
						$disabledcount++;
						$this->fastDelete($rel['id'], $rel['guid'], $this->site);
					}
				}
			}
		}

		// Disabled music genres.
		$genrelist = $genres->getDisabledIDs();
		if (count($genrelist) > 0) {
			foreach ($genrelist as $genre) {
				$rels = $db->queryDirect(sprintf('SELECT id, guid FROM releases INNER JOIN (SELECT id AS mid FROM musicinfo WHERE musicinfo.genreid = %d) mi ON releases.musicinfoid = mid', $genre['id']));
				if ($rels->rowCount() > 0) {
					foreach ($rels as $rel) {
						$disabledgenrecount++;
						$this->fastDelete($rel['id'], $rel['guid'], $this->site);
					}
				}
			}
		}

		// Misc other.
		if ($this->site->miscotherretentionhours > 0) {
			if ($db->dbSystem() == 'mysql') {
				$resrel = $db->queryDirect(sprintf('SELECT id, guid FROM releases WHERE categoryid = %d AND adddate <= NOW() - INTERVAL %d HOUR', CATEGORY::CAT_MISC, $this->site->miscotherretentionhours));
			} else {
				$resrel = $db->queryDirect(sprintf("SELECT id, guid FROM releases WHERE categoryid = %d AND adddate <= NOW() - INTERVAL '%d HOURS'", CATEGORY::CAT_MISC, $this->site->miscotherretentionhours));
			}
			if ($resrel->rowCount() > 0) {
				foreach ($resrel as $rowrel) {
					$this->fastDelete($rowrel['id'], $rowrel['guid'], $this->site);
					$miscothercount ++;
				}
			}
		}

		if ($db->dbSystem() == 'mysql') {
			$db->queryExec(sprintf('DELETE FROM nzbs WHERE dateadded < (NOW() - INTERVAL %d HOUR)', $this->site->partretentionhours));
		} else {
			$db->queryExec(sprintf("DELETE FROM nzbs WHERE dateadded < (NOW() - INTERVAL '%d HOURS')", $this->site->partretentionhours));
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
				$this->c->doEcho($this->c->primary("Removed " . number_format($reccount) . ' parts/binaries/collection rows.'));
			}
			$this->c->doEcho($this->c->primary($this->consoleTools->convertTime(TIME() - $stage7)), true);
		}
	}

	public function processReleasesStage4567_loop($categorize, $postproc, $groupID, $nntp)
	{
		$DIR = nZEDb_MISC;
		if ($this->command_exist('python3')) {
			$PYTHON = 'python3 -OOu';
		} else {
			$PYTHON = 'python -OOu';
		}

		$tot_retcount = $tot_nzbcount = $loops = 0;
		do {
			$retcount = $this->processReleasesStage4($groupID);
			$tot_retcount = $tot_retcount + $retcount;
			//$this->processReleasesStage4dot5($groupID, $echooutput=false);
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
		$db = $this->db;

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
			$consoletools = new ConsoleTools();
			$countID = $db->queryOneRow('SELECT COUNT(id) FROM collections ' . $where);
			$this->c->doEcho(
				$this->c->primary(
					'Completed adding ' .
					number_format($releasesAdded) .
					' releases in ' .
					$consoletools->convertTime(number_format(microtime(true) - $this->processReleases, 2)) .
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
		$db = $this->db;
		$res = $db->queryDirect('SELECT b.id as bid, b.name as bname, c.* FROM binaries b LEFT JOIN collections c ON b.collectionid = c.id');
		if ($res->rowCount() > 0) {
			$timestart = TIME();
			if ($this->echooutput) {
				echo "Going to remake all the collections. This can be a long process, be patient. DO NOT STOP THIS SCRIPT!\n";
			}
			// Reset the collectionhash.
			$db->queryExec('UPDATE collections SET collectionhash = 0');
			$delcount = 0;
			$cIDS = array();
			foreach ($res as $row) {
				$nofiles = true;
				if ($row['totalfiles'] > 0) {
					$nofiles = false;
				}

				$groupName = $this->groups->getByNameByID($row['groupid']);
				/* $ncarr = $this->collectionsCleaning->collectionsCleaner($row['bname'], $groupName, $nofiles);
				  $newSHA1 = sha1($ncarr['hash']).$row['fromname'].$row['groupid'].$row['totalfiles']); */
				$newSHA1 = sha1($this->collectionsCleaning->collectionsCleaner($row['bname'], $groupName, $nofiles) . $row['fromname'] . $row['groupid'] . $row['totalfiles']);
				$cres = $db->queryOneRow(sprintf('SELECT id FROM collections WHERE collectionhash = %s', $db->escapeString($newSHA1)));
				if (!$cres) {
					$cIDS[] = $row['id'];
					$csql = sprintf('INSERT INTO collections (subject, fromname, date, xref, groupid, totalfiles, collectionhash, filecheck, dateadded) VALUES (%s, %s, %s, %s, %d, %s, %s, 0, NOW())', $db->escapeString($row['bname']), $db->escapeString($row['fromname']), $db->escapeString($row['date']), $db->escapeString($row['xref']), $row['groupid'], $db->escapeString($row['totalfiles']), $db->escapeString($newSHA1));
					$collectionID = $db->queryInsert($csql);
					if ($this->echooutput) {
						$this->consoleTools->overWrite('Recreated: ' . count($cIDS) . ' collections. Time:' . $this->consoleTools->convertTimer(TIME() - $timestart));
					}
				} else {
					$collectionID = $cres['id'];
				}
				//Update the binaries with the new info.
				$db->queryExec(sprintf('UPDATE binaries SET collectionid = %d WHERE id = %d', $collectionID, $row['bid']));
			}
			//Remove the old collections.
			$delstart = TIME();
			if ($this->echooutput) {
				echo "\n";
			}
			$totalcIDS = count($cIDS);
			foreach ($cIDS as $cID) {
				$db->queryExec(sprintf('DELETE FROM collections WHERE id = %d', $cID));
				$delcount++;
				if ($this->echooutput) {
					$this->consoleTools->overWrite('Deleting old collections:' . $this->consoleTools->percentString($delcount, $totalcIDS) . ' Time:' . $this->consoleTools->convertTimer(TIME() - $delstart));
				}
			}
			// Delete previous failed attempts.
			$db->queryExec('DELETE FROM collections WHERE collectionhash = "0"');

			if ($this->hashcheck == 0) {
				$db->queryExec("UPDATE site SET value = 1 WHERE setting = 'hashcheck'");
			}
			if ($this->echooutput) {
				echo "\nRemade " . count($cIDS) . ' collections in ' . $this->consoleTools->convertTime(TIME() - $timestart) . "\n";
			}
		} else {
			$db->queryExec("UPDATE site SET value = 1 WHERE setting = 'hashcheck'");
		}
	}

	public function getTopDownloads()
	{
		$db = $this->db;
		return $db->query('SELECT id, searchname, guid, adddate, SUM(grabs) AS grabs FROM releases GROUP BY id, searchname, adddate HAVING SUM(grabs) > 0 ORDER BY grabs DESC LIMIT 10');
	}

	public function getTopComments()
	{
		$db = $this->db;
		return $db->query('SELECT id, guid, searchname, adddate, SUM(comments) AS comments FROM releases GROUP BY id, searchname, adddate HAVING SUM(comments) > 0 ORDER BY comments DESC LIMIT 10');
	}

	public function getRecentlyAdded()
	{
		$db = $this->db;
		if ($db->dbSystem() == 'mysql') {
			return $db->query("SELECT CONCAT(cp.title, ' > ', category.title) AS title, COUNT(*) AS count FROM category LEFT OUTER JOIN category cp on cp.id = category.parentid INNER JOIN releases ON releases.categoryid = category.id WHERE releases.adddate > NOW() - INTERVAL 1 WEEK GROUP BY concat(cp.title, ' > ', category.title) ORDER BY COUNT(*) DESC");
		} else {
			return $db->query("SELECT CONCAT(cp.title, ' > ', category.title) AS title, COUNT(*) AS count FROM category LEFT OUTER JOIN category cp on cp.id = category.parentid INNER JOIN releases ON releases.categoryid = category.id WHERE releases.adddate > NOW() - INTERVAL '1 WEEK' GROUP BY concat(cp.title, ' > ', category.title) ORDER BY COUNT(*) DESC");
		}
	}

	public function getReleaseNameFromRequestID($site, $requestID, $groupName)
	{
		if ($site->request_url == '') {
			return '';
		}

		// Build Request URL
		$req_url = str_ireplace('[GROUP_NM]', urlencode($groupName), $site->request_url);
		$req_url = str_ireplace('[REQUEST_ID]', urlencode($requestID), $req_url);

		$xml = @simplexml_load_file($req_url);

		if (($xml == false) || (count($xml) == 0)) {
			return '';
		}

		$request = $xml->request[0];

		return (!isset($request) || !isset($request['name'])) ? '' : $request['name'];
	}

	function localLookup($requestID, $groupName)
	{
		$db = $this->db;
		$groupid = $this->groups->getIDByName($groupName);
		$run = $db->queryOneRow(sprintf("SELECT title FROM predb WHERE requestid = %d AND groupid = %d", $requestID, $groupid));
		if (isset($run['title'])) {
			return $run['title'];
		}
	}

	public function command_exist($cmd)
	{
		$returnVal = shell_exec("which {$cmd} 2>/dev/null");
		return (empty($returnVal) ? false : true);
	}

	public function getNewestMovies()
	{
		$db = new DB();
		return $db->query("SELECT DISTINCT (a.imdbID), guid, name, b.title, searchname, size, completion, postdate, "
				. "categoryid, comments, grabs, c.cover FROM releases a, category b, movieinfo c "
				. "WHERE b.title = 'Movies'  AND a.imdbid = c.imdbid AND a.imdbid !='NULL' AND a.imdbid != 0 AND c.cover = 1 "
				. "GROUP BY a.imdbid ORDER BY a.postdate DESC LIMIT 20");
	}

	public function getNewestConsole()
	{
		$db = new DB();
		return $db->query("SELECT DISTINCT (a.consoleinfoid), guid, name, b.title, searchname, size, completion, postdate, categoryid, comments, grabs, c.cover FROM releases a, category b, consoleinfo c WHERE b.title = 'Console' and a.consoleinfoid = c.id and a.consoleinfoid != -2 and a.consoleinfoid != 0 GROUP BY a.consoleinfoid ORDER BY a.postdate DESC LIMIT 12");
	}

	public function getNewestMP3s()
	{
		$db = new DB();
		return $db->query("SELECT DISTINCT (a.musicinfoid), guid, name, b.title, searchname, size, completion, postdate, categoryid, comments, grabs, c.cover FROM releases a, category b, musicinfo c WHERE b.title = 'Audio' and a.musicinfoid = c.id and a.musicinfoid != -2 GROUP BY a.musicinfoid ORDER BY a.postdate DESC LIMIT 10");
	}

	public function getNewestBooks()
	{
		$db = new DB();
		return $db->query("SELECT DISTINCT (a.bookinfoid), guid, name, b.title, searchname, size, completion, postdate, categoryid, comments, grabs, c.cover FROM releases a, category b, bookinfo c WHERE b.title = 'Books' and a.bookinfoid = c.id and a.bookinfoid != -2 GROUP BY a.bookinfoid ORDER BY a.postdate DESC LIMIT 12");
	}

}
