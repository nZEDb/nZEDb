<?php
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/page.php");
require_once(WWW_DIR."lib/binaries.php");
require_once(WWW_DIR."lib/users.php");
require_once(WWW_DIR."lib/category.php");
require_once(WWW_DIR."lib/consoletools.php");
require_once(WWW_DIR."lib/nzb.php");
require_once(WWW_DIR."lib/nfo.php");
require_once(WWW_DIR."lib/zipfile.php");
require_once(WWW_DIR."lib/site.php");
require_once(WWW_DIR."lib/util.php");
require_once(WWW_DIR."lib/releasefiles.php");
require_once(WWW_DIR."lib/releaseextra.php");
require_once(WWW_DIR."lib/releaseimage.php");
require_once(WWW_DIR."lib/releasecomments.php");
require_once(WWW_DIR."lib/postprocess.php");
require_once(WWW_DIR."lib/groups.php");
require_once(WWW_DIR."lib/namecleaning.php");
require_once(WWW_DIR."lib/predb.php");


class Releases
{
	/* RAR/ZIP Passworded indicator. */
	// No password.
	const PASSWD_NONE = 0;
	// Might have a password.
	const PASSWD_POTENTIAL = 1;
	// Possibly broken RAR/ZIP.
	const BAD_FILE = 2;
	// Definately passworded.
	const PASSWD_RAR = 10;

	function Releases($echooutput=false)
	{
		$this->echooutput = $echooutput;
		$s = new Sites();
		$this->site = $s->get();
		$this->stage5limit = (!empty($this->site->maxnzbsprocessed)) ? $this->site->maxnzbsprocessed : 1000;
		$this->completion = (!empty($this->site->releasecompletion)) ? $this->site->releasecompletion : 0;
		$this->crosspostt = (!empty($this->site->crossposttime)) ? $this->site->crossposttime : 2;
		$this->updategrabs = ($this->site->grabstatus == "0") ? false : true;
		$this->requestids = $this->site->lookup_reqids;
		$this->hashcheck = (!empty($this->site->hashcheck)) ? $this->site->hashcheck : 0;
		$this->delaytimet = (!empty($this->site->delaytime)) ? $this->site->delaytime : 2;
		$this->debug = ($this->site->debuginfo == "0") ? false : true;
	}

	public function get()
	{
		$db = new DB();
		return $db->query("SELECT releases.*, g.name AS group_name, c.title AS category_name FROM releases LEFT OUTER JOIN category c on c.id = releases.categoryid LEFT OUTER JOIN groups g on g.id = releases.groupid");
	}

	public function getRange($start, $num)
	{
		$db = new DB();

		if ($start === false)
			$limit = "";
		else
			$limit = " LIMIT ".$num." OFFSET ".$start;

		return $db->query("SELECT releases.*, CONCAT(cp.title, ' > ', c.title) AS category_name FROM releases LEFT OUTER JOIN category c on c.id = releases.categoryid LEFT OUTER JOIN category cp on cp.id = c.parentid ORDER BY postdate DESC".$limit);
	}

	// Used for paginator.
	public function getBrowseCount($cat, $maxage=-1, $excludedcats=array(), $grp = "")
	{
		$db = new DB();

		$catsrch = $this->categorySQL($cat);

		$maxagesql = $exccatlist = $grpsql = "";
		if ($maxage > 0)
			$maxagesql = sprintf(" AND postdate > NOW() - INTERVAL %d DAY ", $maxage);

		if ($grp != "")
			$grpsql = sprintf(" AND groups.name = %s ", $db->escapeString($grp));

		if (count($excludedcats) > 0)
			$exccatlist = " AND categoryid NOT IN (".implode(",", $excludedcats).")";

		$res = $db->queryOneRow(sprintf("SELECT COUNT(releases.id) AS num FROM releases LEFT OUTER JOIN groups ON groups.id = releases.groupid WHERE releases.passwordstatus <= %d %s %s %s %s", $this->showPasswords(), $catsrch, $maxagesql, $exccatlist, $grpsql));
		return $res['num'];
	}

	// Used for browse results.
	public function getBrowseRange($cat, $start, $num, $orderby, $maxage=-1, $excludedcats=array(), $grp="")
	{
		$db = new DB();

		if ($start === false)
			$limit = "";
		else
			$limit = " LIMIT ".$num." OFFSET ".$start;

		$catsrch = $this->categorySQL($cat);

		$maxagesql = $grpsql = $exccatlist = "";
		if ($maxage > 0)
			$maxagesql = sprintf(" AND postdate > NOW() - INTERVAL %d DAY ", $maxage);

		if ($grp != "")
			$grpsql = sprintf(" AND groups.name = %s ", $db->escapeString($grp));

		if (count($excludedcats) > 0)
			$exccatlist = " AND releases.categoryid NOT IN (".implode(",", $excludedcats).")";

		$order = $this->getBrowseOrder($orderby);
		return $db->query(sprintf("SELECT releases.*, CONCAT(cp.title, ' > ', c.title) AS category_name, CONCAT(cp.id, ',', c.id) AS category_ids, groups.name AS group_name, rn.id AS nfoid, re.releaseid AS reid FROM releases LEFT OUTER JOIN groups ON groups.id = releases.groupid LEFT OUTER JOIN releasevideo re ON re.releaseid = releases.id LEFT OUTER JOIN releasenfo rn ON rn.releaseid = releases.id AND rn.nfo IS NOT NULL LEFT OUTER JOIN category c ON c.id = releases.categoryid LEFT OUTER JOIN category cp ON cp.id = c.parentid WHERE releases.passwordstatus <= %d %s %s %s %s ORDER BY %s %s %s", $this->showPasswords(), $catsrch, $maxagesql, $exccatlist, $grpsql, $order[0], $order[1], $limit), true);
	}

	// Return site setting for hiding/showing passworded releases.
	public function showPasswords()
	{
		$db = new DB();
		$res = $db->queryOneRow("SELECT value FROM site WHERE setting = 'showpasswordedrelease'");
		return $res["value"];
	}

	public function getBrowseOrder($orderby)
	{
		$order = ($orderby == '') ? 'posted_desc' : $orderby;
		$orderArr = explode("_", $order);
		switch($orderArr[0]) {
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
		$db = new DB();
		if ($postfrom != "")
		{
			$dateparts = explode("/", $postfrom);
			if (count($dateparts) == 3)
				$postfrom = sprintf(" AND postdate > %s ", $db->escapeString($dateparts[2]."-".$dateparts[1]."-".$dateparts[0]." 00:00:00"));
			else
				$postfrom = "";
		}

		if ($postto != "")
		{
			$dateparts = explode("/", $postto);
			if (count($dateparts) == 3)
				$postto = sprintf(" AND postdate < %s ", $db->escapeString($dateparts[2]."-".$dateparts[1]."-".$dateparts[0]." 23:59:59"));
			else
				$postto = "";
		}

		if ($group != "" && $group != "-1")
			$group = sprintf(" and groupID = %d ", $group);
		else
			$group = "";

		return $db->query(sprintf("SELECT searchname, guid, CONCAT(cp.title,'_',category.title) AS catName FROM releases INNER JOIN category ON releases.categoryid = category.id LEFT OUTER JOIN category cp ON cp.id = category.parentid where 1 = 1 %s %s %s", $postfrom, $postto, $group));
	}

	public function getEarliestUsenetPostDate()
	{
		$db = new DB();
		$row = $db->queryOneRow("SELECT DATE_FORMAT(min(postdate), '%d/%m/%Y') AS postdate FROM releases");
		return $row['postdate'];
	}

	public function getLatestUsenetPostDate()
	{
		$db = new DB();
		$row = $db->queryOneRow("SELECT DATE_FORMAT(max(postdate), '%d/%m/%Y') AS postdate FROM releases");
		return $row['postdate'];
	}

	public function getReleasedGroupsForSelect($blnIncludeAll = true)
	{
		$db = new DB();
		$groups = $db->query("SELECT DISTINCT groups.id, groups.name FROM releases INNER JOIN groups on groups.id = releases.groupid");
		$temp_array = array();

		if ($blnIncludeAll)
			$temp_array[-1] = "--All Groups--";

		foreach($groups as $group)
			$temp_array[$group['id']] = $group['name'];

		return $temp_array;
	}

	public function getRss($cat, $num, $uid=0, $rageid, $anidbid, $airdate=-1)
	{
		$db = new DB();

		$limit = " LIMIT ".($num > 100 ? 100 : $num)." OFFSET 0";

		$catsrch = $cartsrch = "";
		if (count($cat) > 0)
		{
			if ($cat[0] == -2)
				$cartsrch = sprintf(" INNER JOIN usercart ON usercart.userid = %d AND usercart.releaseid = releases.id ", $uid);
			elseif ($cat[0] != -1)
			{
				$catsrch = " AND (";
				foreach ($cat as $category)
				{
					if ($category != -1)
					{
						$categ = new Category();
						if ($categ->isParent($category))
						{
							$children = $categ->getChildren($category);
							$chlist = "-99";
							foreach ($children as $child)
								$chlist.=", ".$child['id'];

							if ($chlist != "-99")
								$catsrch .= " releases.categoryid IN (".$chlist.") OR ";
						}
						else
							$catsrch .= sprintf(" releases.categoryid = %d OR ", $category);
					}
				}
				$catsrch.= "1=2 )";
			}
		}


		$rage = ($rageid > -1) ? sprintf(" AND releases.rageid = %d ", $rageid) : '';
		$anidb = ($anidbid > -1) ? sprintf(" AND releases.anidbid = %d ", $anidbid) : '';
		$airdate = ($airdate > -1) ? sprintf(" AND releases.tvairdate >= DATE_SUB(CURDATE(), INTERVAL %d DAY) ", $airdate) : '';

		$sql = sprintf(" SELECT releases.*, m.cover, m.imdbid, m.rating, m.plot, m.year, m.genre, m.director, m.actors, g.name as group_name, CONCAT(cp.title, ' > ', c.title) AS category_name, concat(cp.id, ',', c.id) AS category_ids, COALESCE(cp.id,0) AS parentCategoryid, mu.title AS mu_title, mu.url AS mu_url, mu.artist AS mu_artist, mu.publisher AS mu_publisher, mu.releasedate AS mu_releasedate, mu.review AS mu_review, mu.tracks AS mu_tracks, mu.cover AS mu_cover, mug.title AS mu_genre, co.title AS co_title, co.url AS co_url, co.publisher AS co_publisher, co.releasedate AS co_releasedate, co.review AS co_review, co.cover AS co_cover, cog.title AS co_genre FROM releases LEFT OUTER JOIN category c ON c.id = releases.categoryid LEFT OUTER JOIN category cp ON cp.id = c.parentid LEFT OUTER JOIN groups g ON g.id = releases.groupid LEFT OUTER JOIN movieinfo m ON m.imdbid = releases.imdbid AND m.title != '' LEFT OUTER JOIN musicinfo mu ON mu.id = releases.musicinfoid LEFT OUTER JOIN genres mug ON mug.id = mu.genreid LEFT OUTER JOIN consoleinfo co ON co.id = releases.consoleinfoid LEFT OUTER JOIN genres cog ON cog.id = co.genreid %s WHERE releases.passwordstatus <= %d %s %s %s %s ORDER BY postdate DESC %s", $this->showPasswords(), $cartsrch, $catsrch, $rage, $anidb, $airdate, $limit);
		return $db->query($sql);
	}

	public function getShowsRss($num, $uid=0, $excludedcats=array(), $airdate=-1)
	{
		$db = new DB();

		$exccatlist = "";
		if (count($excludedcats) > 0)
			$exccatlist = " AND releases.categoryid NOT IN (".implode(",", $excludedcats).")";

		$usql = $this->uSQL($db->query(sprintf("SELECT rageid, categoryid FROM userseries WHERE userid = %d", $uid), true), 'rageid');
		$airdate = ($airdate > -1) ? sprintf(" AND releases.tvairdate >= DATE_SUB(CURDATE(), INTERVAL %d DAY) ", $airdate) : '';
		$limit = " LIMIT ".($num > 100 ? 100 : $num)." OFFSET 0";

		$sql = sprintf(" SELECT releases.*, tvr.rageid, tvr.releasetitle, g.name AS group_name, CONCAT(cp.title, '-', c.title) AS category_name, CONCAT(cp.id, ',', c.id) AS category_ids, COALESCE(cp.id,0) AS parentCategoryid FROM releases LEFT OUTER JOIN category c ON c.id = releases.categoryid LEFT OUTER JOIN category cp ON cp.id = c.parentid LEFT OUTER JOIN groups g ON g.id = releases.groupid LEFT OUTER JOIN tvrage tvr ON tvr.rageid = releases.rageid WHERE %s %s %s AND releases.passwordstatus <= %d ORDER BY postdate DESC %s", $usql, $exccatlist, $airdate, $this->showPasswords(), $limit);
		return $db->query($sql);
	}

	public function getMyMoviesRss($num, $uid=0, $excludedcats=array())
	{
		$db = new DB();

		$exccatlist = "";
		if (count($excludedcats) > 0)
			$exccatlist = " AND releases.categoryid NOT IN (".implode(",", $excludedcats).")";

		$usql = $this->uSQL($db->query(sprintf("SELECT imdbid, categoryid FROM usermovies WHERE userid = %d", $uid), true), 'imdbid');
		$limit = " LIMIT ".($num > 100 ? 100 : $num)." OFFSET 0";

		$sql = sprintf("SELECT releases.*, mi.title AS releasetitle, g.name AS group_name, concat(cp.title, '-', c.title) AS category_name, CONCAT(cp.id, ',', c.id) AS category_ids, COALESCE(cp.id,0) AS parentCategoryid FROM releases LEFT OUTER JOIN category c ON c.id = releases.categoryid LEFT OUTER JOIN category cp ON cp.id = c.parentid LEFT OUTER JOIN groups g ON g.id = releases.groupid LEFT OUTER JOIN movieinfo mi ON mi.imdbid = releases.imdbid WHERE %s %s AND releases.passwordstatus <= %d ORDER BY postdate DESC %s", $usql, $exccatlist, $this->showPasswords(), $limit);
		return $db->query($sql);
	}


	public function getShowsRange($usershows, $start, $num, $orderby, $maxage=-1, $excludedcats=array())
	{
		$db = new DB();

		if ($start === false)
			$limit = "";
		else
			$limit = " LIMIT ".$num." OFFSET ".$start;

		$exccatlist = $maxagesql = "";
		if (count($excludedcats) > 0)
			$exccatlist = " AND releases.categoryid NOT IN (".implode(",", $excludedcats).")";

		$usql = $this->uSQL($usershows, 'rageid');

		if ($maxage > 0)
			$maxagesql = sprintf(" AND releases.postdate > NOW() - INTERVAL %d DAY ", $maxage);

		$order = $this->getBrowseOrder($orderby);
		$sql = sprintf("SELECT releases.*, CONCAT(cp.title, '-', c.title) AS category_name, CONCAT(cp.id, ',', c.id) AS category_ids, groups.name as group_name, rn.id as nfoid, re.releaseid as reid FROM releases LEFT OUTER JOIN releasevideo re ON re.releaseid = releases.id LEFT OUTER JOIN groups ON groups.id = releases.groupid LEFT OUTER JOIN releasenfo rn ON rn.releaseid = releases.id AND rn.nfo IS NOT NULL LEFT OUTER JOIN category c ON c.id = releases.categoryid LEFT OUTER JOIN category cp ON cp.id = c.parentid WHERE %s %s AND releases.passwordstatus <= %d %s ORDER BY %s %s %s", $usql, $this->showPasswords(), $exccatlist, $maxagesql, $order[0], $order[1], $limit);
		return $db->query($sql, true);
	}

	public function getShowsCount($usershows, $maxage=-1, $excludedcats=array())
	{
		$db = new DB();

		$exccatlist = $maxagesql = "";
		if (count($excludedcats) > 0)
			$exccatlist = " AND releases.categoryid NOT IN (".implode(",", $excludedcats).")";

		$usql = $this->uSQL($usershows, 'rageid');

		if ($maxage > 0)
			$maxagesql = sprintf(" AND releases.postdate > NOW() - INTERVAL %d DAY ", $maxage);

		$res = $db->queryOneRow(sprintf("SELECT COUNT(releases.id) AS num FROM releases WHERE %s %s AND releases.passwordstatus <= %d %s", $usql, $exccatlist, $this->showPasswords(), $maxagesql), true);
		return $res['num'];
	}

	public function getCount()
	{
		$db = new DB();
		$res = $db->queryOneRow("SELECT COUNT(id) AS num FROM releases");
		return $res['num'];
	}

	public function delete($id, $isGuid=false)
	{
		$db = new DB();
		$nzb = new NZB();
		$s = new Sites();
		$site = $s->get();

		$ri = new ReleaseImage();

		if (!is_array($id))
			$id = array($id);

		foreach($id as $identifier)
		{
			$rel = $this->getById($identifier);
			$this->fastDelete($rel['id'], $rel['guid'], $this->site);
		}
	}

	// For most scripts needing to delete a release.
	public function fastDelete($id, $guid, $site)
	{
		$db = new DB();
		$nzb = new NZB();
		$ri = new ReleaseImage();

		// Delete from disk.
		$nzbpath = $nzb->getNZBPath($guid, $site->nzbpath, false, $site->nzbsplitlevel);
		if (file_exists($nzbpath))
			unlink($nzbpath);

		// Delete from DB.
		$db->queryDelete("DELETE releases, releasenfo, releasecomment, usercart, releasefiles, releaseaudio, releasesubs, releasevideo, releaseextrafull from releases LEFT OUTER JOIN releasenfo ON releasenfo.releaseid = releases.id LEFT OUTER JOIN releasecomment ON releasecomment.releaseid = releases.id LEFT OUTER JOIN usercart ON usercart.releaseid = releases.id LEFT OUTER JOIN releasefiles ON releasefiles.releaseid = releases.id LEFT OUTER JOIN releaseaudio ON releaseaudio.releaseid = releases.id LEFT OUTER JOIN releasesubs ON releasesubs.releaseid = releases.id LEFT OUTER JOIN releasevideo ON releasevideo.releaseid = releases.id LEFT OUTER JOIN releaseextrafull ON releaseextrafull.releaseid = releases.id WHERE releases.id = ".$id);

		// This deletes a file so not in the query.
		$ri->delete($guid);
	}

	// For the site delete button.
	public function deleteSite($id, $isGuid=false)
	{
		if (!is_array($id))
			$id = array($id);

		foreach($id as $identifier)
		{
			if ($isGuid !== false)
				$rel = $this->getById($identifier);
			else
				$rel = $this->getByGuid($identifier);
			$this->fastDelete($rel['id'], $rel['guid'], $this->site);
		}
	}

	public function update($id, $name, $searchname, $fromname, $category, $parts, $grabs, $size, $posteddate, $addeddate, $rageid, $seriesfull, $season, $episode, $imdbid, $anidbid)
	{
		$db = new DB();
		$db->queryUpdate(sprintf("UPDATE releases SET name = %s, searchname = %s, fromname = %s, categoryid = %d, totalpart = %d, grabs = %d, size = %s, postdate = %s, adddate = %s, rageid = %d, seriesfull = %s, season = %s, episode = %s, imdbid = %d, anidbid = %d WHERE id = %d", $db->escapeString($name), $db->escapeString($searchname), $db->escapeString($fromname), $category, $parts, $grabs, $db->escapeString($size), $db->escapeString($posteddate), $db->escapeString($addeddate), $rageid, $db->escapeString($seriesfull), $db->escapeString($season), $db->escapeString($episode), $imdbid, $anidbid, $id));
	}

	public function updatemulti($guids, $category, $grabs, $rageid, $season, $imdbid)
	{
		if (!is_array($guids) || sizeof($guids) < 1)
			return false;

		$update = array('categoryid'=>(($category == '-1') ? '' : $category), 'grabs'=>$grabs, 'rageid'=>$rageid, 'season'=>$season, 'imdbid'=>$imdbid);

		$db = new DB();
		$updateSql = array();
		foreach($update as $updk=>$updv)
		{
			if ($updv != '')
				$updateSql[] = sprintf($updk.'=%s', $db->escapeString($updv));
		}

		if (sizeof($updateSql) < 1)
			return -1;

		$updateGuids = array();
		foreach($guids as $guid) {
			$updateGuids[] = $db->escapeString($guid);
		}

		$sql = sprintf('UPDATE releases SET '.implode(', ', $updateSql).' WHERE guid IN (%s)', implode(', ', $updateGuids));
		return $db->query($sql);
	}

	// Creates part of a query for some functions.
	public function uSQL($userquery, $type)
	{
		$usql = '(1=2 ';
		foreach($userquery as $u)
		{
			$usql .= sprintf('OR (releases.%s = %d', $type, $u[$type]);
			if ($u['categoryid'] != '')
			{
				$catsArr = explode('|', $u['categoryid']);
				if (count($catsArr) > 1)
					$usql .= sprintf(' AND releases.categoryid IN (%s)', implode(',',$catsArr));
				else
					$usql .= sprintf(' AND releases.categoryid = %d', $catsArr[0]);
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
		$words = explode(" ", $search);
		$searchsql = "";
		$intwordcount = 0;
		if (count($words) > 0)
		{
			foreach ($words as $word)
			{
			if ($word != "")
				{
					if ($intwordcount == 0 && (strpos($word, "^") === 0))
						$searchsql = sprintf(" AND releases.%s LIKE %s", $type, $db->escapeString(substr($word, 1)."%"));
					elseif (substr($word, 0, 2) == '--')
						$searchsql = sprintf(" AND releases.%s NOT LIKE %s", $type, $db->escapeString("%".substr($word, 2)."%"));
					else
						$searchsql = sprintf(" AND releases.%s LIKE %s", $type, $db->escapeString("%".$word."%"));

					$intwordcount++;
				}
			}
		}
		return $searchsql;
	}

	// Creates part of a query for searches requiring the categoryID's.
	public function categorySQL($cat)
	{
		$catsrch = "";
		if (count($cat) > 0 && $cat[0] != -1)
		{
			$categ = new Category();
			$catsrch = " AND (";
			foreach ($cat as $category)
			{
				if ($category != -1)
				{
					if ($categ->isParent($category))
					{
						$children = $categ->getChildren($category);
						$chlist = "-99";
						foreach ($children as $child)
							$chlist.=", ".$child['id'];

						if ($chlist != "-99")
								$catsrch .= " releases.categoryid in (".$chlist.") OR ";
					}
					else
						$catsrch .= sprintf(" releases.categoryid = %d OR ", $category);
				}
			}
			$catsrch.= "1=2 )";
		}
		return $catsrch;
	}

	// Function for searching on the site (by subject, searchname or advanced).
	public function search($searchname, $usenetname, $postername, $groupname, $cat=array(-1), $sizefrom, $sizeto, $hasnfo, $hascomments, $daysnew, $daysold, $offset=0, $limit=1000, $orderby='', $maxage=-1, $excludedcats=array(), $type="basic")
	{
		$db = new DB();
		$groups = new Groups();

		if ($type !== "advanced")
			$catsrch = $this->categorySQL($cat);
		else
		{
			$catsrch = "";
			if ($cat != "-1")
				$catsrch = sprintf(" AND (releases.categoryid = %d) ", $cat);
		}

		$hasnfosql = $hascommentssql = $daysnewsql = $daysoldsql = $maxagesql = $exccatlist = $searchnamesql = $usenetnamesql = $posternamesql = $groupIDsql = "";

		if ($searchname != "-1")
			$searchnamesql = $this->searchSQL($searchname, $db, 'searchname');

		if ($usenetname != "-1")
			$usenetnamesql = $this->searchSQL($usenetname, $db, "name");

		if ($postername != "-1")
			$posternamesql = $this->searchSQL($postername, $db, "fromname");

		if ($groupname != "-1")
		{
			$groupID = $groups->getIDByName($db->escapeString($groupname));
			$groupIDsql = sprintf(" AND releases.groupid = %d ", $groupID);
		}

		if ($sizefrom == "-1"){$sizefromsql= ("");}
		elseif ($sizefrom == "1"){$sizefromsql= (" AND releases.size > 104857600 ");}
		elseif ($sizefrom == "2"){$sizefromsql= (" AND releases.size > 262144000 ");}
		elseif ($sizefrom == "3"){$sizefromsql= (" AND releases.size > 524288000 ");}
		elseif ($sizefrom == "4"){$sizefromsql= (" AND releases.size > 1073741824 ");}
		elseif ($sizefrom == "5"){$sizefromsql= (" AND releases.size > 2147483648 ");}
		elseif ($sizefrom == "6"){$sizefromsql= (" AND releases.size > 3221225472 ");}
		elseif ($sizefrom == "7"){$sizefromsql= (" AND releases.size > 4294967296 ");}
		elseif ($sizefrom == "8"){$sizefromsql= (" AND releases.size > 8589934592 ");}
		elseif ($sizefrom == "9"){$sizefromsql= (" AND releases.size > 17179869184 ");}
		elseif ($sizefrom == "10"){$sizefromsql= (" AND releases.size > 34359738368 ");}
		elseif ($sizefrom == "11"){$sizefromsql= (" AND releases.size > 68719476736 ");}

		if ($sizeto == "-1"){$sizetosql= ("");}
		elseif ($sizeto == "1"){$sizetosql= (" AND releases.size < 104857600 ");}
		elseif ($sizeto == "2"){$sizetosql= (" AND releases.size < 262144000 ");}
		elseif ($sizeto == "3"){$sizetosql= (" AND releases.size < 524288000 ");}
		elseif ($sizeto == "4"){$sizetosql= (" AND releases.size < 1073741824 ");}
		elseif ($sizeto == "5"){$sizetosql= (" AND releases.size < 2147483648 ");}
		elseif ($sizeto == "6"){$sizetosql= (" AND releases.size < 3221225472 ");}
		elseif ($sizeto == "7"){$sizetosql= (" AND releases.size < 4294967296 ");}
		elseif ($sizeto == "8"){$sizetosql= (" AND releases.size < 8589934592 ");}
		elseif ($sizeto == "9"){$sizetosql= (" AND releases.size < 17179869184 ");}
		elseif ($sizeto == "10"){$sizetosql= (" AND releases.size < 34359738368 ");}
		elseif ($sizeto == "11"){$sizetosql= (" AND releases.size < 68719476736 ");}

		if ($hasnfo != "0")
			$hasnfosql= " AND releases.nfostatus = 1 ";

		if ($hascomments != "0")
			$hascommentssql = " AND releases.comments > 0 ";

		if ($daysnew != "-1")
			$daysnewsql= sprintf(" AND releases.postdate < NOW() - INTERVAL %d DAY ", $daysnew);

		if ($daysold != "-1")
			$daysoldsql= sprintf(" AND releases.postdate > NOW() - INTERVAL %d DAY ", $daysold);

		if ($maxage > 0)
			$maxagesql = sprintf(" AND releases.postdate > NOW() - INTERVAL %d DAY ", $maxage);

		if (count($excludedcats) > 0)
			$exccatlist = " AND releases.categoryid NOT IN (".implode(",", $excludedcats).")";

		if ($orderby == "")
		{
			$order[0] = " postdate ";
			$order[1] = " desc ";
		}
		else
			$order = $this->getBrowseOrder($orderby);

		$sql = sprintf("SELECT releases.*, CONCAT(cp.title, ' > ', c.title) AS category_name, CONCAT(cp.id, ',', c.id) AS category_ids, groups.name AS group_name, rn.id AS nfoid, re.releaseid AS reid, cp.id AS categoryParentid FROM releases LEFT OUTER JOIN releasevideo re ON re.releaseid = releases.id LEFT OUTER JOIN releasenfo rn ON rn.releaseid = releases.id LEFT OUTER JOIN groups ON groups.id = releases.groupid LEFT OUTER JOIN category c ON c.id = releases.categoryid LEFT OUTER JOIN category cp ON cp.id = c.parentid WHERE releases.passwordstatus <= %d %s %s %s %s %s %s %s %s %s %s %s %s %s ORDER BY %s %s LIMIT %d OFFSET %d", $this->showPasswords(), $searchnamesql, $usenetnamesql, $maxagesql, $posternamesql, $groupIDsql, $sizefromsql, $sizetosql, $hasnfosql, $hascommentssql, $catsrch, $daysnewsql, $daysoldsql, $exccatlist, $order[0], $order[1], $limit, $offset);
		$wherepos = strpos($sql, "WHERE");
		$countres = $db->queryOneRow("SELECT COUNT(releases.id) AS num FROM releases ".substr($sql, $wherepos, strpos($sql, "ORDER BY")-$wherepos));
		$res = $db->query($sql);
		if (count($res) > 0)
			$res[0]['_totalrows'] = $countres['num'];

		return $res;
	}

	public function searchbyRageId($rageId, $series="", $episode="", $offset=0, $limit=100, $name="", $cat=array(-1), $maxage=-1)
	{
		$db = new DB();

		$rageIdsql = $maxagesql = "";

		if ($rageId != "-1")
			$rageIdsql = sprintf(" AND rageid = %d ", $rageId);

		if ($series != "")
		{
			// Exclude four digit series, which will be the year 2010 etc.
			if (is_numeric($series) && strlen($series) != 4)
				$series = sprintf('S%02d', $series);

			$series = sprintf(" AND UPPER(releases.season) = UPPER(%s)", $db->escapeString($series));
		}

		if ($episode != "")
		{
			if (is_numeric($episode))
				$episode = sprintf('E%02d', $episode);

			$episode = sprintf(" AND releases.episode LIKE %s", $db->escapeString('%'.$episode.'%'));
		}

		$searchql = $this->searchSQL($name, $db, "searchname");
		$catsrch = $this->categorySQL($cat);

		if ($maxage > 0)
			$maxagesql = sprintf(" AND releases.postdate > NOW() - INTERVAL %d DAY ", $maxage);

		$sql = sprintf("SELECT releases.*, concat(cp.title, ' > ', c.title) AS category_name, CONCAT(cp.id, ',', c.id) AS category_ids, groups.name AS group_name, rn.id AS nfoid, re.releaseid AS reid FROM releases LEFT OUTER JOIN category c ON c.id = releases.categoryid LEFT OUTER JOIN groups ON groups.id = releases.groupid LEFT OUTER JOIN releasevideo re ON re.releaseid = releases.id LEFT OUTER JOIN releasenfo rn ON rn.releaseid = releases.id AND rn.nfo IS NOT NULL LEFT OUTER JOIN category cp ON cp.id = c.parentid WHERE releases.passwordstatus <= %d %s %s %s %s %s %s ORDER BY postdate DESC LIMIT %d OFFSET %d", $this->showPasswords(), $rageIdsql, $series, $episode, $searchsql, $catsrch, $maxagesql, $limit, $offset);
		$orderpos = strpos($sql, "ORDER BY");
		$wherepos = strpos($sql, "WHERE");
		$sqlcount = "SELECT COUNT(releases.id) AS num FROM releases ".substr($sql, $wherepos,$orderpos-$wherepos);

		$countres = $db->queryOneRow($sqlcount);
		$res = $db->query($sql);
		if (count($res) > 0)
			$res[0]['_totalrows'] = $countres['num'];

		return $res;
	}

	public function searchbyAnidbId($anidbID, $epno='', $offset=0, $limit=100, $name='', $cat=array(-1), $maxage=-1)
	{
		$db = new DB();

		$anidbID = ($anidbID > -1) ? sprintf(" AND anidbid = %d ", $anidbID) : '';

		is_numeric($epno) ? $epno = sprintf(" AND releases.episode LIKE '%s' ", $db->escapeString('%'.$epno.'%')) : '';

		$searchql = $this->searchSQL($name, $db, "searchname");
		$catsrch = $this->categorySQL($cat);

		$maxage = ($maxage > 0) ? sprintf(" AND postdate > now() - INTERVAL %d DAY ", $maxage) : '';

		$sql = sprintf("SELECT releases.*, CONCAT(cp.title, ' > ', c.title) AS category_name, CONCAT(cp.id, ',', c.id) AS category_ids, groups.name AS group_name, rn.id AS nfoid FROM releases LEFT OUTER JOIN category c ON c.id = releases.categoryid LEFT OUTER JOIN groups ON groups.id = releases.groupid LEFT OUTER JOIN releasenfo rn ON rn.releaseid = releases.id and rn.nfo IS NOT NULL LEFT OUTER JOIN category cp ON cp.id = c.parentid WHERE releases.passwordstatus <= %d %s %s %s %s %s ORDER BY postdate DESC LIMIT %d OFFSET %d", $this->showPasswords(), $anidbID, $epno, $searchsql, $catsrch, $maxage, $limit, $offset);
		$orderpos = strpos($sql, "ORDER BY");
		$wherepos = strpos($sql, "WHERE");
		$sqlcount = "SELECT COUNT(releases.id) AS num FROM releases ".substr($sql, $wherepos,$orderpos-$wherepos);

		$countres = $db->queryOneRow($sqlcount);
		$res = $db->query($sql);
		if (count($res) > 0)
			$res[0]['_totalrows'] = $countres['num'];

		return $res;
	}

	public function searchbyImdbId($imdbId, $offset=0, $limit=100, $name="", $cat=array(-1), $maxage=-1)
	{
		$db = new DB();

		if ($imdbId != "-1" && is_numeric($imdbId))
		{
			// Pad ID with zeros just in case.
			$imdbId = str_pad($imdbId, 7, "0",STR_PAD_LEFT);
			$imdbId = sprintf(" AND imdbid = %d ", $imdbId);
		}
		else
			$imdbId = "";

		$searchql = $this->searchSQL($name, $db, "searchname");
		$catsrch = $this->categorySQL($cat);

		if ($maxage > 0)
			$maxage = sprintf(" AND postdate > NOW() - INTERVAL %d DAY ", $maxage);
		else
			$maxage = "";

		$sql = sprintf("SELECT releases.*, concat(cp.title, ' > ', c.title) AS category_name, CONCAT(cp.id, ',', c.id) AS category_ids, groups.name AS group_name, rn.id AS nfoid FROM releases LEFT OUTER JOIN groups ON groups.id = releases.groupid LEFT OUTER JOIN category c ON c.id = releases.categoryid LEFT OUTER JOIN releasenfo rn ON rn.releaseid = releases.id AND rn.nfo IS NOT NULL LEFT OUTER JOIN category cp ON cp.id = c.parentid WHERE releases.passwordstatus <= %d %s %s %s %s ORDER BY postdate DESC LIMIT %d OFFSET %d", $this->showPasswords(), $searchsql, $imdbId, $catsrch, $maxage, $limit, $offset);
		$orderpos = strpos($sql, "ORDER BY");
		$wherepos = strpos($sql, "WHERE");
		$sqlcount = "SELECT COUNT(releases.id) AS num FROM releases ".substr($sql, $wherepos,$orderpos-$wherepos);

		$countres = $db->queryOneRow($sqlcount);
		$res = $db->query($sql);
		if (count($res) > 0)
			$res[0]['_totalrows'] = $countres['num'];

		return $res;
	}

	public function searchSimilar($currentid, $name, $limit=6, $excludedcats=array())
	{
		$name = $this->getSimilarName($name);
		$results = $this->search($name, -1, -1, -1, array(-1), -1, -1, 0, 0, -1, -1, 0, $limit, '', -1, $excludedcats);
		if (!$results)
			return $results;

		// Get the category for the parent of this release.
		$currRow = $this->getById($currentid);
		$cat = new Category();
		$catrow = $cat->getById($currRow['categoryid']);
		$parentCat = $catrow['parentid'];

		$ret = array();
		foreach ($results as $res)
			if ($res['id'] != $currentid && $res['categoryParentid'] == $parentCat)
				$ret[] = $res;

		return $ret;
	}

	public function getSimilarName($name)
	{
		$words = str_word_count(str_replace(array(".","_"), " ", $name), 2);
		$firstwords = array_slice($words, 0, 2);
		return implode(' ', $firstwords);
	}

	public function getByGuid($guid)
	{
		$db = new DB();
		if (is_array($guid))
		{
			$tmpguids = array();
			foreach($guid as $g)
				$tmpguids[] = $db->escapeString($g);
			$gsql = sprintf('guid IN (%s)', implode(',',$tmpguids));
		}
		else
			$gsql = sprintf('guid = %s', $db->escapeString($guid));
		$sql = sprintf("SELECT releases.*, CONCAT(cp.title, ' > ', c.title) AS category_name, CONCAT(cp.id, ',', c.id) AS category_ids, groups.name AS group_name FROM releases LEFT OUTER JOIN groups ON groups.is = releases.groupid LEFT OUTER JOIN category c ON c.id = releases.categoryid LEFT OUTER JOIN category cp ON cp.id = c.parentid WHERE %s ", $gsql);
		return (is_array($guid)) ? $db->query($sql) : $db->queryOneRow($sql);
	}

	// Writes a zip file of an array of release guids directly to the stream.
	public function getZipped($guids)
	{
		$nzb = new NZB();
		$zipfile = new zipfile();

		foreach ($guids as $guid)
		{
			$nzbpath = $nzb->getNZBPath($guid, $this->site->nzbpath, false, $this->site->nzbsplitlevel);

			if (file_exists($nzbpath))
			{
				ob_start();
				@readgzfile($nzbpath);
				$nzbfile = ob_get_contents();
				ob_end_clean();

				$filename = $guid;
				$r = $this->getByGuid($guid);
				if ($r)
					$filename = $r['searchname'];

				$zipfile->addFile($nzbfile, $filename.".nzb");
			}
		}

		return $zipfile->file();
	}

	public function getbyRageId($rageid, $series = "", $episode = "")
	{
		$db = new DB();

		if ($series != "")
		{
			// Exclude four digit series, which will be the year 2010 etc.
			if (is_numeric($series) && strlen($series) != 4)
				$series = sprintf('S%02d', $series);

			$series = sprintf(" AND UPPER(releases.season) = UPPER(%s)", $db->escapeString($series));
		}

		if ($episode != "")
		{
			if (is_numeric($episode))
				$episode = sprintf('E%02d', $episode);

			$episode = sprintf(" AND UPPER(releases.episode) = UPPER(%s)", $db->escapeString($episode));
		}
		return $db->queryOneRow(sprintf("SELECT releases.*, CONCAT(cp.title, ' > ', c.title) AS category_name, groups.name AS group_name FROM releases LEFT OUTER JOIN groups ON groups.id = releases.groupid LEFT OUTER JOIN category c ON c.id = releases.categoryid LEFT OUTER JOIN category cp ON cp.id = c.parentid WHERE releases.passwordstatus <= %d AND rageid = %d %s %s", $this->showPasswords(), $rageid, $series, $episode));
	}

	public function removeRageIdFromReleases($rageid)
	{
		$db = new DB();
		$res = $db->queryOneRow(sprintf("SELECT COUNT (id) AS num FROM releases WHERE rageid = %d", $rageid));
		$ret = $res['num'];
		$res = $db->queryUpdate(sprintf("UPDATE releases SET rageid = -1, seriesfull = NULL, season = NULL, episode = NULL WHERE rageid = %d", $rageid));
		return $ret;
	}

	public function removeAnidbIdFromReleases($anidbID)
	{
		$db = new DB();
		$res = $db->queryOneRow(sprintf("SELECT COUNT(id) AS num FROM releases WHERE anidbid = %d", $anidbID));
		$ret = $res['num'];
		$res = $db->queryUpdate(sprintf("UPDATE releases SET anidbid = -1, episode = NULL, tvtitle = NULL, tvairdate = NULL WHERE anidbid = %d", $anidbID));
		return $ret;
	}

	public function getById($id)
	{
		$db = new DB();
		return $db->queryOneRow(sprintf("SELECT releases.*, groups.name AS group_name FROM releases LEFT OUTER JOIN groups ON groups.id = releases.groupid WHERE releases.id = %d ", $id));
	}

	public function getReleaseNfo($id, $incnfo=true)
	{
		$db = new DB();
		$selnfo = ($incnfo) ? ', UNCOMPRESS(nfo) AS nfo' : '';
		return $db->queryOneRow(sprintf("SELECT id, releaseid".$selnfo." FROM releasenfo WHERE releaseid = %d AND nfo IS NOT NULL", $id));
	}

	public function updateGrab($guid)
	{
		if ($this->updategrabs)
		{
			$db = new DB();
			$db->queryUpdate(sprintf("UPDATE releases SET grabs = grabs + 1 WHERE guid = %s", $db->escapeString($guid)));
		}
	}

	// Sends releases back to other->misc.
	public function resetCategorize($where="")
	{
		$db = new DB();
		$db->queryUpdate("UPDATE releases SET categoryid = 7010, relnamestatus = 0 ".$where);
	}

	// Categorizes releases.
	// $type = name or searchname
	// Returns the quantity of categorized releases.
	public function categorizeRelease($type, $where="", $echooutput=false)
	{
		$db = new DB();
		$cat = new Category();
		$consoletools = new consoleTools();
		$relcount = 0;

		$resrel = $db->query("SELECT id, ".$type.", groupid FROM releases ".$where);
		if (count($resrel) > 0)
		{
			foreach ($resrel as $rowrel)
			{
				$catId = $cat->determineCategory($rowrel[$type], $rowrel['groupid']);
				$db->queryUpdate(sprintf("UPDATE releases SET categoryid = %d, relnamestatus = 1 WHERE id = %d", $catId, $rowrel['id']));
				$relcount ++;
				if ($this->echooutput)
					$consoletools->overWrite("Categorizing:".$consoletools->percentString($relcount,count($resrel)));
			}
		}
		if ($this->echooutput !== false && $relcount > 0)
			echo "\n";
		return $relcount;
	}

	public function processReleasesStage1($groupID, $echooutput=false)
	{
		$db = new DB();
		$consoletools = new ConsoleTools();

		if ($this->echooutput)
			echo "\033[1;33mStage 1 -> Try to find complete collections.\033[0m\n";
		$stage1 = TIME();
		$where = (!empty($groupID)) ? " AND groupid = ".$groupID : "";

		// Look if we have all the files in a collection (which have the file count in the subject). Set filecheck to 1.
		$db->queryUpdate("UPDATE collections c SET filecheck = 1 WHERE c.id IN (SELECT b.collectionid FROM binaries b WHERE b.collectionid = c.id GROUP BY b.collectionid, c.totalfiles HAVING COUNT(b.id) IN (c.totalfiles, c.totalfiles + 1)) AND c.totalfiles > 0 AND c.filecheck = 0 ".$where);
		// Set filecheck to 16 if theres a file that starts with 0 (ex. [00/100]).
		$db->queryUpdate("UPDATE collections c SET filecheck = 16 WHERE c.id IN (SELECT b.collectionid FROM binaries b WHERE b.collectionid = c.id AND b.filenumber = 0 ".$where." GROUP BY b.collectionid) AND c.totalfiles > 0 AND c.filecheck = 1");
		// Set filecheck to 15 on everything left over, so anything that starts with 1 (ex. [01/100]).
		$db->queryUpdate("UPDATE collections SET filecheck = 15 WHERE filecheck = 1");

		// If we have all the parts set partcheck to 1.
		if (empty($groupID))
		{
			// If filecheck 15, check if we have all the parts for a file then set partcheck.
			$db->queryUpdate("UPDATE binaries b SET partcheck = 1 WHERE b.id IN (SELECT p.binaryid FROM parts p, collections c WHERE p.binaryid = b.id AND c.filecheck = 15 AND c.id = b.collectionid GROUP BY p.binaryid HAVING COUNT(p.id) = b.totalparts) AND b.partcheck = 0");
			// If filecheck 16, check if we have all the parts+1(because of the 0) then set partcheck.
			$db->queryUpdate("UPDATE binaries b SET partcheck = 1 WHERE b.id IN (SELECT p.binaryid FROM parts p, collections c WHERE p.binaryid = b.id AND c.filecheck = 16 AND c.id = b.collectionid GROUP BY p.binaryid HAVING COUNT(p.id) >= b.totalparts+1) AND b.partcheck = 0");
		}
		else
		{
			// Same as the if but for a specific group.
			$db->queryUpdate("UPDATE binaries b SET partcheck = 1 WHERE b.id IN (SELECT p.binaryid FROM parts p ,collections c WHERE p.binaryid = b.id AND c.filecheck = 15 AND c.id = b.collectionid AND c.groupid = ".$groupID." GROUP BY p.binaryid HAVING COUNT(p.id) = b.totalparts ) AND b.partcheck = 0");
			$db->queryUpdate("UPDATE binaries b SET partcheck = 1 WHERE b.id IN (SELECT p.binaryid FROM parts p ,collections c WHERE p.binaryid = b.id AND c.filecheck = 16 AND c.id = b.collectionid AND c.groupid = ".$groupID." GROUP BY p.binaryid HAVING COUNT(p.id) >= b.totalparts+1 ) AND b.partcheck = 0");
		}

		// Set filecheck to 2 if partcheck = 1.
		$db->queryUpdate("UPDATE collections c SET filecheck = 2 WHERE c.id IN (SELECT b.collectionid FROM binaries b WHERE c.id = b.collectionid AND b.partcheck = 1 GROUP BY b.collectionid HAVING COUNT(b.id) >= c.totalfiles) AND c.filecheck IN (15, 16) ".$where);
		// Set filecheck to 1 if we don't have all the parts.
		$db->queryUpdate("UPDATE collections SET filecheck = 1 WHERE filecheck in (15, 16) ".$where);
		// If a collection has not been updated in 2 hours, set filecheck to 2.
		$db->queryUpdate(sprintf("UPDATE collections c SET filecheck = 2, totalfiles = (SELECT COUNT(b.id) FROM binaries b WHERE b.collectionid = c.id) WHERE c.dateadded < NOW() - INTERVAL '%d' HOUR AND c.filecheck IN (0, 1, 10) ".$where, $this->delaytimet));

		if ($this->echooutput)
			echo $consoletools->convertTime(TIME() - $stage1);
	}

	public function processReleasesStage2($groupID, $echooutput=false)
	{
		$db = new DB();
		$consoletools = new ConsoleTools();
		$where = (!empty($groupID)) ? " AND groupid = " . $groupID : "";

		if ($this->echooutput)
			echo "\n\033[1;33mStage 2 -> Get the size in bytes of the collection.\033[0m\n";
		$stage2 = TIME();
		// Get the total size in bytes of the collection for collections where filecheck = 2.
		$db->queryUpdate("UPDATE collections c SET filesize = (SELECT SUM(size) FROM parts p LEFT JOIN binaries b ON p.binaryid = b.id WHERE b.collectionid = c.id), filecheck = 3 WHERE c.filecheck = 2 AND c.filesize = 0 ".$where);
		if ($this->echooutput)
			echo $consoletools->convertTime(TIME() - $stage2);
	}

	public function processReleasesStage3($groupID, $echooutput=false)
	{
		$db = new DB();
		$consoletools = new ConsoleTools();
		$minsizecounts = $maxsizecounts = $minfilecounts = 0;

		if ($this->echooutput)
			echo "\n\033[1;33mStage 3 -> Delete collections smaller/larger than minimum size/file count from group/site setting.\033[0m\n";
		$stage3 = TIME();

		if ($groupID == "")
		{
			$groups = new Groups();
			$groupIDs = $groups->getActiveIDs();

			foreach ($groupIDs as $groupID)
			{
				$res = $db->query("SELECT id FROM collections WHERE filecheck = 3 AND filesize > 0");
				if (count($res) > 0)
				{
					$mscq = $db->prepare("UPDATE collections c LEFT JOIN (SELECT g.id, COALESCE(g.minsizetoformrelease, s.minsizetoformrelease) AS minsizetoformrelease FROM groups g INNER JOIN ( SELECT value AS minsizetoformrelease FROM site WHERE setting = 'minsizetoformrelease' ) s ) g ON g.id = c.groupid SET c.filecheck = 5 WHERE g.minsizetoformrelease != 0 AND c.filecheck = 3 AND c.filesize < g.minsizetoformrelease AND c.filesize > 0 AND groupid = ".$groupID['id']);
					$mscq->execute();
					$minsizecount = $mscq->rowCount();
					if ($minsizecount < 1)
						$minsizecount = 0;
					$minsizecounts = $minsizecount+$minsizecounts;

					$maxfilesizeres = $db->queryOneRow("SELECT value FROM site WHERE setting = 'maxsizetoformrelease'");
					if ($maxfilesizeres['value'] != 0)
					{
						$mascq = $db->prepare(sprintf("UPDATE collections SET filecheck = 5 WHERE filecheck = 3 AND groupid = %d AND filesize > %d ", $groupID['id'], $maxfilesizeres['value']));
						$mascq->execute();
						$maxsizecount = $mascq->rowCount();
						if ($maxsizecount < 1)
							$maxsizecount = 0;
						$maxsizecounts = $maxsizecount+$maxsizecounts;
					}

					$mifcq = $db->prepare("UPDATE collections c LEFT JOIN (SELECT g.id, COALESCE(g.minfilestoformrelease, s.minfilestoformrelease) AS minfilestoformrelease FROM groups g INNER JOIN ( SELECT value AS minfilestoformrelease FROM site WHERE setting = 'minfilestoformrelease' ) s ) g ON g.id = c.groupid SET c.filecheck = 5 WHERE g.minfilestoformrelease != 0 AND c.filecheck = 3 AND c.totalFiles < g.minfilestoformrelease AND groupid = ".$groupID['id']);
					$mifcq->execute();
					$minfilecount = $mifcq->rowCount();
					if ($minfilecount < 1)
						$minfilecount = 0;
					$minfilecounts = $minfilecount+$minfilecounts;
				}
			}
		}
		else
		{
			$res = $db->query("SELECT id FROM collections WHERE filecheck = 3 AND filesize > 0");
			if(count($res) > 0)
			{
				$mscq = $db->prepare("UPDATE collections c LEFT JOIN (SELECT g.id, coalesce(g.minsizetoformrelease, s.minsizetoformrelease) AS minsizetoformrelease FROM groups g INNER JOIN ( SELECT value AS minsizetoformrelease FROM site WHERE setting = 'minsizetoformrelease' ) s ) g ON g.id = c.groupid SET c.filecheck = 5 WHERE g.minsizetoformrelease != 0 AND c.filecheck = 3 AND c.filesize < g.minsizetoformrelease AND c.filesize > 0 AND groupid = ".$groupID);
				$mscq->execute();
				$minsizecount = $mscq->rowCount();
				if ($minsizecount < 0)
					$minsizecount = 0;
				$minsizecounts = $minsizecount+$minsizecounts;

				$maxfilesizeres = $db->queryOneRow("SELECT value FROM site WHERE setting = maxsizetoformrelease");
				if ($maxfilesizeres['value'] != 0)
				{
					$mascq = $db->prepare(sprintf("UPDATE collections SET filecheck = 5 WHERE filecheck = 3 AND filesize > %d " . $where, $maxfilesizeres['value']));
					$mascq->execute();
					$maxsizecount = $mascq->rowCount();
					if ($maxsizecount < 0)
						$maxsizecount = 0;
					$maxsizecounts = $maxsizecount+$maxsizecounts;
				}

				$mifcq = $db->prepare("UPDATE collections c LEFT JOIN (SELECT g.id, coalesce(g.minfilestoformrelease, s.minfilestoformrelease) AS minfilestoformrelease FROM groups g INNER JOIN ( SELECT value AS minfilestoformrelease FROM site WHERE setting = 'minfilestoformrelease' ) s ) g ON g.id = c.groupid SET c.filecheck = 5 WHERE g.minfilestoformrelease != 0 AND c.filecheck = 3 AND c.totalFiles < g.minfilestoformrelease AND groupid = ".$groupID);
				$mifcq->execute();
				$minfilecount = $mifcq->rowCount();
				if ($minfilecount < 0)
					$minfilecount = 0;
				$minfilecounts = $minfilecount+$minfilecounts;
			}
		}

		$delcount = $minsizecounts+$maxsizecounts+$minfilecounts;
		if ($this->echooutput && $delcount > 0)
				echo "Deleted ".$delcount." collections smaller/larger than group/site settings.\n";
		if ($this->echooutput)
			echo $consoletools->convertTime(TIME() - $stage3);
	}

	public function processReleasesStage4($groupID, $echooutput=false)
	{
		$db = new DB();
		$consoletools = new ConsoleTools();
		$retcount = 0;
		$where = (!empty($groupID)) ? " AND groupid = " . $groupID : "";

		if ($this->echooutput)
			echo "\n\033[1;33mStage 4 -> Create releases.\033[0m\n";
		$stage4 = TIME();
		$rescol = $db->query("SELECT * FROM collections WHERE filecheck = 3 AND filesize > 0 " . $where . " LIMIT ".$this->stage5limit);
		if(count($rescol) > 0)
		{
			$namecleaning = new nameCleaning();
			$predb = new  Predb();
			$page = new Page();

			foreach ($rescol as $rowcol)
			{
				$cleanArr = array('#', '@', '$', '%', '^', '§', '¨', '©', 'Ö');
				$cleanRelName = str_replace($cleanArr, '', $rowcol['subject']);
				$cleanerName = $namecleaning->releaseCleaner($rowcol['subject'], $rowcol['groupid']);
				$relguid = sha1(uniqid().mt_rand());
				try {
					$relid = $db->queryInsert(sprintf("INSERT INTO releases (name, searchname, totalpart, groupid, adddate, guid, rageid, postdate, fromname, size, passwordstatus, haspreview, categoryid, nfostatus) VALUES (%s, %s, %d, %d, NOW(), %s, -1, %s, %s, %s, %d, -1, 7010, -1)", $db->escapeString($cleanRelName), $db->escapeString($cleanerName), $rowcol['totalfiles'], $rowcol['groupid'], $db->escapeString($relguid), $db->escapeString($rowcol['date']), $db->escapeString($rowcol['fromname']), $db->escapeString($rowcol['filesize']), ($page->site->checkpasswordedrar == "1" ? -1 : 0)));
				} catch (PDOException $err) {
					if ($this->echooutput)
						echo "\033[01;31m.".$err."\n";
				}
				if (!isset($error))
				{
					$predb->matchPre($cleanRelName, $relid);
					// Update collections table to say we inserted the release.
					$db->queryUpdate(sprintf("UPDATE collections SET filecheck = 4, releaseid = %d WHERE id = %d", $relid, $rowcol['id']));
					$retcount ++;
					if ($this->echooutput)
						echo "Added release ".$cleanerName."\n";
				}
			}
		}

		if ($this->echooutput)
			echo $retcount." Releases added in ".$consoletools->convertTime(TIME() - $stage4).".";
		return $retcount;
	}

	/*
	 *	Adding this in to delete releases before NZB's are created.
	 */
	public function processReleasesStage4dot5($groupID, $echooutput=false)
	{
		$db = new DB();
		$consoletools = new ConsoleTools();
		$minsizecount = $maxsizecount = $minfilecount = $catminsizecount = 0;

		if ($this->echooutput)
			echo "\n\033[1;33mStage 4.5 -> Delete releases smaller/larger than minimum size/file count from group/site setting.\033[0m\n";
		$stage4dot5 = TIME();

		$catresrel = $db->query("select c.id as id, CASE WHEN c.minsize = 0 THEN cp.minsize ELSE c.minsize END AS minsize FROM category c LEFT OUTER JOIN category cp ON cp.id = c.parentid WHERE c.parentid IS NOT NULL");
		foreach ($catresrel as $catrowrel)
		{
			$resrel = $db->query(sprintf("SELECT r.id, r.guid FROM releases r WHERE r.categoryid = %d AND r.size < %d", $catrowrel['id'], $catrowrel['minsize']));
			foreach ($resrel as $rowrel)
			{
				$this->fastDelete($rowrel['id'], $rowrel['guid'], $this->site);
				$catminsizecount ++;
			}
		}

		if ($groupID == "")
		{
			$groups = new Groups();
			$groupIDs = $groups->getActiveIDs();

			foreach ($groupIDs as $groupID)
			{
				$resrel = $db->query(sprintf("SELECT r.id, r.guid FROM releases r LEFT JOIN (SELECT g.id, coalesce(g.minsizetoformrelease, s.minsizetoformrelease) AS minsizetoformrelease FROM groups g INNER JOIN ( SELECT value as minsizetoformrelease FROM site WHERE setting = 'minsizetoformrelease' ) s WHERE g.id = %s ) g ON g.id = r.groupid WHERE g.minsizetoformrelease != 0 AND r.size < minsizetoformrelease AND r.groupid = %s", $groupID['id'], $groupID['id']));
				if (count($resrel) > 0)
				{
					foreach ($resrel as $rowrel)
					{
						$this->fastDelete($rowrel['id'], $rowrel['guid'], $this->site);
						$minsizecount ++;
					}
				}

				$maxfilesizeres = $db->queryOneRow("SELECT value FROM site WHERE setting = 'maxsizetoformrelease'");
				if ($maxfilesizeres['value'] != 0)
				{
					$resrel = $db->query(sprintf("SELECT id, guid FROM releases WHERE groupid = %d AND filesize > %d", $groupID['id'], $maxfilesizeres['value']));
					if (count($resrel) > 0)
					{
						foreach ($resrel as $rowrel)
						{
							$this->fastDelete($rowrel['id'], $rowrel['guid'], $this->site);
							$maxsizecount ++;
						}
					}
				}

				$resrel = $db->query(sprintf("SELECT r.id, r.guid FROM releases r LEFT JOIN (SELECT g.id, coalesce(g.minfilestoformrelease, s.minfilestoformrelease) as minfilestoformrelease FROM groups g INNER JOIN ( SELECT value as minfilestoformrelease FROM site WHERE setting = 'minfilestoformrelease' ) s WHERE g.id = %s ) g ON g.id = r.groupid WHERE g.minfilestoformrelease != 0 AND r.totalpart < minfilestoformrelease AND r.groupid = %s", $groupID['id'], $groupID['id']));
				if (count($resrel) > 0)
				{
					foreach ($resrel as $rowrel)
					{
						$this->fastDelete($rowrel['id'], $rowrel['guid'], $this->site);
						$minfilecount ++;
					}
				}
			}
		}
		else
		{
			$resrel = $db->query(sprintf("SELECT r.id, r.guid FROM releases r LEFT JOIN (SELECT g.id, coalesce(g.minsizetoformrelease, s.minsizetoformrelease) AS minsizetoformrelease FROM groups g INNER JOIN ( SELECT value AS minsizetoformrelease FROM site WHERE setting = 'minsizetoformrelease' ) s WHERE g.id = %s ) g ON g.id = r.groupid WHERE g.minsizetoformrelease != 0 AND r.size < minsizetoformrelease AND r.groupid = %s", $groupID, $groupID));
			if (count($resrel) > 0)
			{
				foreach ($resrel as $rowrel)
				{
					$this->fastDelete($rowrel['id'], $rowrel['guid'], $this->site);
					$minsizecount ++;
				}
			}

			$maxfilesizeres = $db->queryOneRow("SELECT value FROM site WHERE setting = maxsizetoformrelease");
			if ($maxfilesizeres['value'] != 0)
			{
				$resrel = $db->query(sprintf("SELECT id, guid FROM releases WHERE groupid = %d AND filesize > %d", $groupID, $maxfilesizeres['value']));
				if (count($resrel) > 0)
				{
					foreach ($resrel as $rowrel)
					{
						$this->fastDelete($rowrel['id'], $rowrel['guid'], $this->site);
						$maxsizecount ++;
					}
				}
			}
			
			$resrel = $db->query(sprintf("SELECT r.id, r.guid FROM releases r LEFT JOIN (SELECT g.id, coalesce(g.minfilestoformrelease, s.minfilestoformrelease) AS minfilestoformrelease FROM groups g INNER JOIN ( SELECT value AS minfilestoformrelease FROM site WHERE setting = 'minfilestoformrelease' ) s WHERE g.id = %s ) g ON g.id = r.groupid WHERE g.minfilestoformrelease != 0 AND r.totalpart < minfilestoformrelease AND r.groupid = %s", $groupID, $groupID));
			if (count($resrel) > 0)
			{
				foreach ($resrel as $rowrel)
				{
					$this->fastDelete($rowrel['id'], $rowrel['guid'], $this->site);
					$minfilecount ++;
				}
			}
		}

		$delcount = $minsizecount+$maxsizecount+$minfilecount+$catminsizecount;
		if ($this->echooutput && $delcount > 0)
				echo "Deleted ".$delcount." releases smaller/larger than group/site settings.\n";
		if ($this->echooutput)
			echo $consoletools->convertTime(TIME() - $stage4dot5);
	}

	public function processReleasesStage5($groupID, $echooutput=false)
	{
		$db = new DB();
		$nzb = new Nzb();
		$page = new Page();
		$cat = new Category();
		$s = new Sites();
		$version = $s->version();
		$site = $s->get();
		$nzbsplitlevel = $site->nzbsplitlevel;
		$nzbpath = $site->nzbpath;
		$consoletools = new ConsoleTools();
		$nzbcount = 0;
		$where = (!empty($groupID)) ? " AND groupid = " . $groupID : "";

		// Create NZB.
		if ($this->echooutput)
			echo "\n\033[1;33mStage 5 -> Create the NZB, mark collections as ready for deletion.\033[0m\n";
		$stage5 = TIME();
		$resrel = $db->query("SELECT id, guid, name, categoryid FROM releases WHERE nzbstatus = 0 ".$where." LIMIT ".$this->stage5limit);
		if (count($resrel) > 0)
		{
			foreach ($resrel as $rowrel)
			{
				$nzb_guid = $nzb->writeNZBforReleaseId($rowrel['id'], $rowrel['guid'], $rowrel['name'], $rowrel['categoryid'], $nzb->getNZBPath($rowrel['guid'], $nzbpath, true, $nzbsplitlevel), false, $version, $cat);
				if($nzb_guid != false)
				{
					$db->queryUpdate(sprintf("UPDATE releases SET nzbstatus = 1, nzb_guid = %s WHERE id = %d", $db->escapestring(md5($nzb_guid)), $rowrel['id']));
					$db->queryUpdate(sprintf("UPDATE collections SET filecheck = 5 WHERE releaseid = %s", $rowrel['id']));
					$nzbcount++;
					if ($this->echooutput)
						$consoletools->overWrite("Creating NZBs:".$consoletools->percentString($nzbcount,count($resrel)));
				}
			}
		}

		$timing = $consoletools->convertTime(TIME() - $stage5);
		if ($this->echooutput && $nzbcount > 0)
			echo "\n".$nzbcount." NZBs created in ". $timing.".";
		elseif ($this->echooutput)
			echo $timing;
		return $nzbcount;
	}

	public function processReleasesStage5b($groupID, $echooutput=true)
	{
		$page = new Page();
		if ($page->site->lookup_reqids == 1)
		{
			$db = new DB();
			$consoletools = new consoleTools();
			$iFoundcnt = 0;
			$where = (!empty($groupID)) ? " AND groupid = ".$groupID : "";
			$stage8 = TIME();

			if ($this->echooutput)
				echo "\n\033[1;33mStage 5b -> Request ID lookup.\033[0m";

			// Mark records that don't have regex titles.
			$db->queryUpdate("UPDATE releases SET reqidstatus = -1 WHERE reqidstatus = 0 AND nzbstatus = 1 AND relnamestatus = 1 AND name REGEXP '^\\[[[:digit:]]+\\]' = 0 ".$where);

			// Look for records that potentially have regex titles.
			$resrel = $db->query( "SELECT r.id, r.name, g.name groupName FROM releases r LEFT JOIN groups g ON r.groupID = g.ID WHERE relnamestatus = 1 AND nzbstatus = 1 AND reqidstatus = 0 AND r.name REGEXP '^\\[[[:digit:]]+\\]' = 1 " . $where);
			if (count($resrel) > 0)
			{
				foreach ($resrel as $rowrel)
				{
					// Try to get reqid.
					$requestIDtmp = explode("]", substr($rowrel['name'], 1));
					$bFound = false;
					$newTitle = "";

					if (count($requestIDtmp) >= 1)
					{
						$requestID = (int) $requestIDtmp[0];
						if ($requestID != 0)
						{
							$newTitle = $this->getReleaseNameFromRequestID($page->site, $requestID, $rowrel['groupName']);
							if ($newTitle != false && $newTitle != "")
							{
								$bFound = true;
								$iFoundcnt++;
							}
						}
					}

					if ($bFound)
					{
						$db->queryUpdate("UPDATE releases SET reqidstatus = 1, searchname = ".$db->escapeString($newTitle)." WHERE id = ".$rowrel['id']);

						if ($this->echooutput)
							echo "\nUpdated requestID ".$requestID." to release name: ".$newTitle."\n";
					}
					else
					{
						$db->queryUpdate("UPDATE releases SET reqidstatus = -2 WHERE id = " . $rowrel['id']);
						if ($this->echooutput)
							echo ".";
					}
				}
			}

			if ($this->echooutput)
				echo $iFoundcnt." Releases updated in ".$consoletools->convertTime(TIME() - $stage8).".";
		}
	}

	public function processReleasesStage6($categorize, $postproc, $groupID, $echooutput=false)
	{
		$db = new DB();
		$consoletools = new ConsoleTools();
		$where = (!empty($groupID)) ? "WHERE relnamestatus = 0 AND groupid = ".$groupID : "WHERE relnamestatus = 0";

		// Categorize releases.
		if ($this->echooutput)
			echo "\n\033[1;33mStage 6 -> Categorize and post process releases.\033[0m\n";
		$stage6 = TIME();
		if ($categorize == 1)
			$this->categorizeRelease("name", $where);

		if ($postproc == 1)
		{
			$postprocess = new PostProcess(true);
			$postprocess->processAll();
		}
		else
		{
			if ($this->echooutput)
				echo "Post-processing is not running inside the releases.php file.\nIf you are using tmux or screen they might have their own files running Post-processing.\n";
		}
		if ($this->echooutput)
			echo $consoletools->convertTime(TIME() - $stage6).".";
	}

	public function processReleasesStage7a($groupID, $echooutput=false)
	{
		$db = new DB();
		$page = new Page();
		$category = new Category();
		$genres = new Genres();
		$consoletools = new ConsoleTools();
		$n = "\n";
		$remcount = $passcount = $passcount = $dupecount = $relsizecount = $completioncount = $disabledcount = $disabledgenrecount = $miscothercount = 0;

		$where = (!empty($groupID)) ? " AND collections.groupid = ".$groupID : "";

		// Delete old releases and finished collections.
		if ($this->echooutput)
			echo $n."\033[1;33mStage 7a -> Delete finished collections.\033[0m".$n;
		$stage7 = TIME();

		// Completed releases and old collections that were missed somehow.
		$delq = $db->prepare(sprintf("DELETE collections, binaries, parts FROM collections INNER JOIN binaries ON collections.id = binaries.collectionid INNER JOIN parts on binaries.id = parts.binaryid WHERE collections.filecheck = 5 ".$where));
		$delq->execute();
		if ($this->echooutput)
				echo "Removed ".$delq->rowCount()." parts/binaries/collection rows in ".$consoletools->convertTime(TIME() - $stage7).".";
	}

	public function processReleasesStage7b($groupID, $echooutput=false)
	{
		$db = new DB();
		$page = new Page();
		$category = new Category();
		$genres = new Genres();
		$consoletools = new ConsoleTools();
		$remcount = $passcount = $passcount = $dupecount = $relsizecount = $completioncount = $disabledcount = $disabledgenrecount = $miscothercount = 0;

		$where = (!empty($groupID)) ? " AND collections.groupid = ".$groupID : "";

		// Delete old releases and finished collections.
		if ($this->echooutput)
			echo "\n\033[1;33mStage 7b -> Delete old releases and passworded releases.\033[0m\n";
		$stage7 = TIME();

		// old collections that were missed somehow.
		$delq = $db->prepare(sprintf("DELETE collections, binaries, parts FROM collections INNER JOIN binaries ON collections.id = binaries.collectionID INNER JOIN parts on binaries.ID = parts.binaryid WHERE collections.dateadded < (NOW() - INTERVAL %d HOUR) " . $where, $page->site->partretentionhours));
		$delq->execute();
		$reccount = $delq->rowCount();

		// Binaries/parts that somehow have no collection.
		$db->queryDelete("DELETE binaries, parts FROM binaries LEFT JOIN parts ON binaries.id = parts.binaryid WHERE binaries.collectionid = 0 ".$where);
		// Parts that somehow have no binaries.
		$db->queryDelete("DELETE FROM parts WHERE binaryid NOT IN (SELECT b.id FROM binaries b) ".$where);
		// Binaries that somehow have no collection.
		$db->queryDelete("DELETE FROM binaries WHERE collectionid NOT IN (SELECT c.id FROM collections c) ".$where);
		// Collections that somehow have no binaries.
		$db->queryDelete("DELETE FROM collections WHERE collections.id NOT IN ( SELECT binaries.collectionid FROM binaries) ".$where);

		// Releases past retention.
		if($page->site->releaseretentiondays != 0)
		{
			$result = $db->query(sprintf("SELECT id, guid FROM releases WHERE postdate < (NOW() - INTERVAL %d DAY)", $page->site->releaseretentiondays));
			foreach ($result as $rowrel)
			{
				$this->fastDelete($rowrel['id'], $rowrel['guid'], $this->site);
				$remcount ++;
			}
		}

		// Passworded releases.
		if($page->site->deletepasswordedrelease == 1)
		{
			$result = $db->query("SELECT id, guid FROM releases WHERE passwordstatus = ".Releases::PASSWD_RAR);
			if (count($result) > 0)
			{
				foreach ($result as $rowrel)
				{
					$this->fastDelete($rowrel['id'], $rowrel['guid'], $this->site);
					$passcount ++;
				}
			}
		}

		// Possibly passworded releases.
		if($page->site->deletepossiblerelease == 1)
		{
			$result = $db->query("SELECT id, guid FROM releases WHERE passwordstatus = ".Releases::PASSWD_POTENTIAL);
			if (count($result) > 0)
			{
				foreach ($result as $rowrel)
				{
					$this->fastDelete($rowrel['id'], $rowrel['guid'], $this->site);
					$passcount ++;
				}
			}
		}

		// Crossposted releases.
		$resrel = $db->query(sprintf("SELECT id, guid FROM releases WHERE adddate > (NOW() - INTERVAL %d HOUR) GROUP BY name HAVING COUNT(name) > 1", $this->crosspostt));
		if(count($resrel) > 0)
		{
			foreach ($resrel as $rowrel)
			{
				$this->fastDelete($rowrel['id'], $rowrel['guid'], $this->site);
				$dupecount ++;
			}
		}

		// Releases below completion %.
		if($this->completion > 0)
		{
			$resrel = $db->query(sprintf("SELECT id, guid FROM releases WHERE completion < %d AND completion > 0", $this->completion));
			if(count($resrel) > 0)
			{
				foreach ($resrel as $rowrel)
				{
					$this->fastDelete($rowrel['id'], $rowrel['guid'], $this->site);
					$completioncount ++;
				}
			}
		}

		// Disabled categories.
		if ($catlist = $category->getDisabledIDs())
		{
			foreach ($catlist as $cat)
			{
				$res = $db->query(sprintf("SELECT id, guid FROM releases WHERE categoryid = %d", $cat['id']));
				if (count($res) > 0)
				{
					foreach ($res as $rel)
					{
						$disabledcount++;
						$this->fastDelete($rel['id'], $rel['guid'], $this->site);
					}
				}
			}
		}

		// Disabled music genres.
		if ($genrelist = $genres->getDisabledIDs())
		{
			foreach ($genrelist as $genre)
			{
				$rels = $db->query(sprintf("SELECT id, guid FROM releases INNER JOIN (SELECT id AS mid FROM musicinfo WHERE musicinfo.genreid = %d) mi ON releases.musicinfoid = mid", $genre['id']));
				if (count($rels) > 0)
				{
					foreach ($rels as $rel)
					{
						$disabledgenrecount++;
						$this->fastDelete($rel['id'], $rel['guid'], $this->site);
					}
				}
			}
		}

		// Misc other.
		if ($page->site->miscotherretentionhours > 0)
		{
			$resrel = $db->query(sprintf("SELECT id, guid FROM releases WHERE categoryid = %d AND adddate <= NOW() - INTERVAL %d HOUR", CATEGORY::CAT_MISC, $page->site->miscotherretentionhours));
			if (count($resrel) > 0 )
			{
				foreach ($resrel as $rowrel)
				{
					$this->fastDelete($rowrel['id'], $rowrel['guid'], $this->site);
					$miscothercount ++;
				}
			}
		}

		$db->queryDelete(sprintf("DELETE FROM nzbs WHERE dateadded < (NOW() - INTERVAL %d HOUR)", $page->site->partretentionhours));

		echo "Removed releases : ".number_format($remcount)." past retention, ".number_format($passcount)." passworded, ".number_format($dupecount)." crossposted, ".number_format($disabledcount)." from disabled categoteries, ".number_format($disabledgenrecount)." from disabled music genres, ".number_format($miscothercount)." from misc->other";
		if ($this->echooutput && $this->completion > 0)
			echo ", ".number_format($completioncount)." under ".$this->completion."% completion. Removed ".number_format($reccount)." parts/binaries/collection rows.\n";
		else
			if ($this->echooutput)
				echo ". \nRemoved ".number_format($reccount)." parts/binaries/collection rows.\n";

		if ($this->echooutput)
			echo $consoletools->convertTime(TIME() - $stage7).".\n";
	}

	public function processReleasesStage4567_loop($categorize, $postproc, $groupID, $echooutput=false)
	{
		$DIR = MISC_DIR;
		if ($this->command_exist("python3"))
			$PYTHON = "python3 -OO";
		else
			$PYTHON = "python -OO";

		$tot_retcount = $tot_nzbcount = $loops = 0;
		do
		{
			$retcount = $this->processReleasesStage4($groupID);
			$tot_retcount = $tot_retcount + $retcount;
			//$this->processReleasesStage4dot5($groupID, $echooutput=false);
			$nzbcount = $this->processReleasesStage5($groupID);
			if ($this->requestids == "1")
				$this->processReleasesStage5b($groupID, $echooutput);
			elseif ($this->requestids == "2")
			{
				$consoletools = new ConsoleTools();
				$stage8 = TIME();
				if ($this->echooutput)
					echo "\n\033[1;33mStage 5b -> Request ID Threaded lookup.\033[0m\n";
				passthru("$PYTHON ${DIR}update_scripts/threaded_scripts/requestid_threaded.py");
				if ($this->echooutput)
					echo "\nReleases updated in ".$consoletools->convertTime(TIME() - $stage8).".";
			}

			$tot_nzbcount = $tot_nzbcount + $nzbcount;
			$this->processReleasesStage6($categorize, $postproc, $groupID, $echooutput=false);
			$this->processReleasesStage7a($groupID, $echooutput=false);
			$loops++;
		// This loops as long as there were releases created or 3 loops, otherwise, you could loop indefinately
		} while (($nzbcount > 0 || $retcount > 0) && $loops < 3);

		return $tot_retcount;
	}

	public function processReleases($categorize, $postproc, $groupName, $echooutput=false)
	{
		$this->echooutput = $echooutput;
		if ($this->hashcheck == 0)
			exit("You must run update_binaries.php to update your collectionhash.\n");
		$db = new DB();
		$groups = new Groups();
		$page = new Page();
		$consoletools = new ConsoleTools();
		$groupID = "";

		if (!empty($groupName))
		{
			$groupInfo = $groups->getByName($groupName);
			$groupID = $groupInfo['ID'];
		}

		$this->processReleases = microtime(true);
		if ($this->echooutput)
			echo "\nStarting release update process (".date("Y-m-d H:i:s").")\n";

		if (!file_exists($page->site->nzbpath))
		{
			if ($this->echooutput)
				echo "Bad or missing nzb directory - ".$page->site->nzbpath;
			return;
		}

		$this->processReleasesStage1($groupID, $echooutput=false);
		$this->processReleasesStage2($groupID, $echooutput=false);
		$this->processReleasesStage3($groupID, $echooutput=false);
		$releasesAdded = $this->processReleasesStage4567_loop($categorize, $postproc, $groupID, $echooutput=false);
		$this->processReleasesStage4dot5($groupID, $echooutput=false);
		$deletedCount = $this->processReleasesStage7b($groupID, $echooutput=false);

		$where = (!empty($groupID)) ? " WHERE groupID = " . $groupID : "";

		//Print amount of added releases and time it took.
		if ($this->echooutput)
			echo "Completed adding ".number_format($releasesAdded)." releases in ".$consoletools->convertTime(number_format(microtime(true) - $this->processReleases, 2)).". ".number_format(array_shift($db->queryOneRow("select count(ID) from collections " . $where)))." collections waiting to be created (still incomplete or in queue for creation).\n";
		return $releasesAdded;
	}

	// This resets collections, useful when the namecleaning class's collectioncleaner function changes.
	public function resetCollections()
	{
		$db = new DB();
		$namecleaner = new nameCleaning();
		$consoletools = new ConsoleTools();
		$res = $db->query("SELECT b.id as bid, b.name as bname, c.* FROM binaries b LEFT JOIN collections c ON b.collectionid = c.id");
		if(count($res) > 0)
		{
			$timestart = TIME();
			if ($this->echooutput)
				echo "Going to remake all the collections. This can be a long process, be patient. DO NOT STOP THIS SCRIPT!\n";
			// Reset the collectionhash.
			$db->queryUpdate("UPDATE collections SET collectionhash = 0");
			$delcount = 0;
			$cIDS = array();
			foreach ($res as $row)
			{
				$nofiles = true;
				if ($row['totalfiles'] > 0)
					$nofiles = false;
				$newSHA1 = sha1($namecleaner->collectionsCleaner($row['bname'], $row['groupid'], $nofiles).$row['fromname'].$row['groupid'].$row['totalfiles']);
				$cres = $db->queryOneRow(sprintf("SELECT id FROM collections WHERE collectionhash = %s", $db->escapeString($newSHA1)));
				if(!$cres)
				{
					$cIDS[] = $row['id'];
					$csql = sprintf("INSERT INTO collections (subject, fromname, date, xref, groupID, totalFiles, collectionhash, filecheck, dateadded) VALUES (%s, %s, %s, %s, %d, %s, %s, 0, NOW())", $db->escapeString($row['bname']), $db->escapeString($row['fromname']), $db->escapeString($row['date']), $db->escapeString($row['xref']), $row['groupid'], $db->escapeString($row['totalfiles']), $db->escapeString($newSHA1));
					$collectionID = $db->queryInsert($csql);
					if ($this->echooutput)
						$consoletools->overWrite("Recreated: ".count($cIDS)." collections. Time:".$consoletools->convertTimer(TIME() - $timestart));
				}
				else
					$collectionID = $cres['id'];
				//Update the binaries with the new info.
				$db->queryUpdate(sprintf("UPDATE binaries SET collectionid = %d WHERE id = %d", $collectionID, $row['bid']));
			}
			//Remove the old collections.
			$delstart = TIME();
			if ($this->echooutput)
				echo "\n";
			foreach ($cIDS as $cID)
			{
				$db->queryDelete(sprintf("DELETE FROM collections WHERE id = %d", $cID));
				$delcount++;
				if ($this->echooutput)
					$consoletools->overWrite("Deleting old collections:".$consoletools->percentString($delcount,sizeof($cIDS))." Time:".$consoletools->convertTimer(TIME() - $delstart));
			}
			// Delete previous failed attempts.
			$db->queryDelete('DELETE FROM collections WHERE collectionhash = "0"');

			if ($this->hashcheck == 0)
				$db->query("UPDATE site SET value = '1' WHERE setting = 'hashcheck'");
			if ($this->echooutput)
				echo "\nRemade ".count($cIDS)." collections in ".$consoletools->convertTime(TIME() - $timestart)."\n";
		}
		else
			$db->query("UPDATE site SET value = '1' WHERE setting = 'hashcheck'");
	}

	public function getTopDownloads()
	{
		$db = new DB();
		return $db->query("SELECT id, searchname, guid, adddate, SUM(grabs) AS grabs FROM releases GROUP BY id, searchname, adddate HAVING SUM(grabs) > 0 ORDER BY grabs DESC LIMIT 10");
	}

	public function getTopComments()
	{
		$db = new DB();
		return $db->query("SELECT id, guid, searchname, adddate, SUM(comments) AS comments FROM releases GROUP BY id, searchname, adddate HAVING SUM(comments) > 0 ORDER BY comments DESC LIMIT 10");
	}

	public function getRecentlyAdded()
	{
		$db = new DB();
		return $db->query("SELECT CONCAT(cp.title, ' > ', category.title) AS title, COUNT(*) AS count FROM category LEFT OUTER JOIN category cp on cp.id = category.parentid INNER JOIN releases ON releases.categoryid = category.id WHERE releases.adddate > NOW() - INTERVAL 1 WEEK GROUP BY concat(cp.title, ' > ', category.title) ORDER BY COUNT(*) DESC");
	}

	public function getReleaseNameFromRequestID($site, $requestID, $groupName)
	{
		if ($site->request_url == "")
			return "";

		// Build Request URL
		$req_url = str_ireplace("[GROUP_NM]", urlencode($groupName), $site->request_url);
		$req_url = str_ireplace("[REQUEST_ID]", urlencode($requestID), $req_url);

		$xml = simplexml_load_file($req_url);

		if (($xml == false) || (count($xml) == 0))
			return "";

		$request = $xml->request[0];

		return (!isset($request) || !isset($request["name"])) ? "" : $request['name'];
	}

	public function command_exist($cmd)
	{
		$returnVal = shell_exec("which $cmd 2>/dev/null");
		return (empty($returnVal) ? false : true);
	}

}
