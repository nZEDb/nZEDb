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
		return $db->query("select releases.*, g.name as group_name, c.title as category_name  from releases left outer join category c on c.ID = releases.categoryID left outer join groups g on g.ID = releases.groupID");
	}

	public function getRange($start, $num)
	{
		$db = new DB();

		if ($start === false)
			$limit = "";
		else
			$limit = " LIMIT ".$start.",".$num;

		return $db->query(" SELECT releases.*, concat(cp.title, ' > ', c.title) as category_name from releases left outer join category c on c.ID = releases.categoryID left outer join category cp on cp.ID = c.parentID order by postdate desc".$limit);
	}

	// Used for paginator.
	public function getBrowseCount($cat, $maxage=-1, $excludedcats=array(), $grp = "")
	{
		$db = new DB();

		$catsrch = $this->categorySQL($cat);

		$maxagesql = $exccatlist = $grpsql = "";
		if ($maxage > 0)
			$maxagesql = sprintf(" and postdate > now() - interval %d day ", $maxage);

		if ($grp != "")
			$grpsql = sprintf(" and groups.name = %s ", $db->escapeString($grp));

		if (count($excludedcats) > 0)
			$exccatlist = " and categoryID not in (".implode(",", $excludedcats).")";

		$res = $db->queryOneRow(sprintf("SELECT COUNT(releases.ID) AS num FROM releases LEFT OUTER JOIN groups ON groups.ID = releases.groupID WHERE releases.passwordstatus <= (SELECT value FROM site WHERE setting='showpasswordedrelease') %s %s %s %s", $catsrch, $maxagesql, $exccatlist, $grpsql));
		return $res['num'];
	}

	// Used for browse results.
	public function getBrowseRange($cat, $start, $num, $orderby, $maxage=-1, $excludedcats=array(), $grp="")
	{
		$db = new DB();

		if ($start === false)
			$limit = "";
		else
			$limit = " LIMIT ".$start.",".$num;

		$catsrch = $this->categorySQL($cat);

		$maxagesql = $grpsql = $exccatlist = "";
		if ($maxage > 0)
			$maxagesql = sprintf(" AND postdate > NOW() - INTERVAL %d DAY ", $maxage);

		if ($grp != "")
			$grpsql = sprintf(" AND groups.name = %s ", $db->escapeString($grp));

		if (count($excludedcats) > 0)
			$exccatlist = " AND releases.categoryID NOT IN (".implode(",", $excludedcats).")";

		$order = $this->getBrowseOrder($orderby);
		return $db->query(sprintf("SELECT releases.*, CONCAT(cp.title, ' > ', c.title) as category_name, CONCAT(cp.ID, ',', c.ID) AS category_ids, groups.name AS group_name, rn.ID AS nfoID, re.releaseID AS reID FROM releases LEFT OUTER JOIN groups ON groups.ID = releases.groupID LEFT OUTER JOIN releasevideo re ON re.releaseID = releases.ID LEFT OUTER JOIN releasenfo rn ON rn.releaseID = releases.ID AND rn.nfo IS NOT NULL LEFT OUTER JOIN category c ON c.ID = releases.categoryID LEFT OUTER JOIN category cp ON cp.ID = c.parentID WHERE releases.passwordstatus <= (SELECT value FROM site WHERE setting='showpasswordedrelease') %s %s %s %s ORDER BY %s %s".$limit, $catsrch, $maxagesql, $exccatlist, $grpsql, $order[0], $order[1]), true);
	}

	public function getBrowseOrder($orderby)
	{
		$order = ($orderby == '') ? 'posted_desc' : $orderby;
		$orderArr = explode("_", $order);
		switch($orderArr[0]) {
			case 'cat':
				$orderfield = 'categoryID';
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
				$postfrom = sprintf(" and postdate > %s ", $db->escapeString($dateparts[2]."-".$dateparts[1]."-".$dateparts[0]." 00:00:00"));
			else
				$postfrom = "";
		}

		if ($postto != "")
		{
			$dateparts = explode("/", $postto);
			if (count($dateparts) == 3)
				$postto = sprintf(" and postdate < %s ", $db->escapeString($dateparts[2]."-".$dateparts[1]."-".$dateparts[0]." 23:59:59"));
			else
				$postto = "";
		}

		if ($group != "" && $group != "-1")
			$group = sprintf(" and groupID = %d ", $group);
		else
			$group = "";

		return $db->query(sprintf("SELECT searchname, guid, CONCAT(cp.title,'_',category.title) as catName FROM releases INNER JOIN category ON releases.categoryID = category.ID LEFT OUTER JOIN category cp ON cp.ID = category.parentID where 1 = 1 %s %s %s", $postfrom, $postto, $group));
	}

	public function getEarliestUsenetPostDate()
	{
		$db = new DB();
		$row = $db->queryOneRow("SELECT DATE_FORMAT(min(postdate), '%d/%m/%Y') as postdate from releases");
		return $row['postdate'];
	}

	public function getLatestUsenetPostDate()
	{
		$db = new DB();
		$row = $db->queryOneRow("SELECT DATE_FORMAT(max(postdate), '%d/%m/%Y') as postdate from releases");
		return $row['postdate'];
	}

	public function getReleasedGroupsForSelect($blnIncludeAll = true)
	{
		$db = new DB();
		$groups = $db->query("select distinct groups.ID, groups.name from releases inner join groups on groups.ID = releases.groupID");
		$temp_array = array();

		if ($blnIncludeAll)
			$temp_array[-1] = "--All Groups--";

		foreach($groups as $group)
			$temp_array[$group['ID']] = $group['name'];

		return $temp_array;
	}

	public function getRss($cat, $num, $uid=0, $rageid, $anidbid, $airdate=-1)
	{
		$db = new DB();

		$limit = " LIMIT 0,".($num > 100 ? 100 : $num);

		$catsrch = $cartsrch = "";
		if (count($cat) > 0)
		{
			if ($cat[0] == -2)
				$cartsrch = sprintf(" inner join usercart on usercart.userID = %d and usercart.releaseID = releases.ID ", $uid);
			elseif ($cat[0] != -1)
			{
				$catsrch = " and (";
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
								$chlist.=", ".$child['ID'];

							if ($chlist != "-99")
								$catsrch .= " releases.categoryID in (".$chlist.") or ";
						}
						else
							$catsrch .= sprintf(" releases.categoryID = %d or ", $category);
					}
				}
				$catsrch.= "1=2 )";
			}
		}


		$rage = ($rageid > -1) ? sprintf(" and releases.rageID = %d ", $rageid) : '';
		$anidb = ($anidbid > -1) ? sprintf(" and releases.anidbID = %d ", $anidbid) : '';
		$airdate = ($airdate > -1) ? sprintf(" and releases.tvairdate >= DATE_SUB(CURDATE(), INTERVAL %d DAY) ", $airdate) : '';

		$sql = sprintf(" SELECT releases.*, m.cover, m.imdbID, m.rating, m.plot, m.year, m.genre, m.director, m.actors, g.name as group_name, concat(cp.title, ' > ', c.title) as category_name, concat(cp.ID, ',', c.ID) as category_ids, coalesce(cp.ID,0) as parentCategoryID, mu.title as mu_title, mu.url as mu_url, mu.artist as mu_artist, mu.publisher as mu_publisher, mu.releasedate as mu_releasedate, mu.review as mu_review, mu.tracks as mu_tracks, mu.cover as mu_cover, mug.title as mu_genre, co.title as co_title, co.url as co_url, co.publisher as co_publisher, co.releasedate as co_releasedate, co.review as co_review, co.cover as co_cover, cog.title as co_genre  from releases left outer join category c on c.ID = releases.categoryID left outer join category cp on cp.ID = c.parentID left outer join groups g on g.ID = releases.groupID left outer join movieinfo m on m.imdbID = releases.imdbID and m.title != '' left outer join musicinfo mu on mu.ID = releases.musicinfoID left outer join genres mug on mug.ID = mu.genreID left outer join consoleinfo co on co.ID = releases.consoleinfoID left outer join genres cog on cog.ID = co.genreID %s where releases.passwordstatus <= (select value from site where setting='showpasswordedrelease') %s %s %s %s order by postdate desc %s" ,$cartsrch, $catsrch, $rage, $anidb, $airdate, $limit);
		return $db->query($sql);
	}

	public function getShowsRss($num, $uid=0, $excludedcats=array(), $airdate=-1)
	{
		$db = new DB();

		$exccatlist = "";
		if (count($excludedcats) > 0)
			$exccatlist = " and releases.categoryID not in (".implode(",", $excludedcats).")";

		$usql = $this->uSQL($db->query(sprintf("select rageID, categoryID from userseries where userID = %d", $uid), true), 'rageID');

		$airdate = ($airdate > -1) ? sprintf(" and releases.tvairdate >= DATE_SUB(CURDATE(), INTERVAL %d DAY) ", $airdate) : '';

		$limit = " LIMIT 0,".($num > 100 ? 100 : $num);

		$sql = sprintf(" SELECT releases.*, tvr.rageID, tvr.releasetitle, g.name as group_name, concat(cp.title, '-', c.title) as category_name, concat(cp.ID, ',', c.ID) as category_ids, coalesce(cp.ID,0) as parentCategoryID
						FROM releases
						left outer join category c on c.ID = releases.categoryID
						left outer join category cp on cp.ID = c.parentID
						left outer join groups g on g.ID = releases.groupID
						left outer join tvrage tvr on tvr.rageID = releases.rageID
						where %s %s %s
						and releases.passwordstatus <= (select value from site where setting='showpasswordedrelease')
						order by postdate desc %s" , $usql, $exccatlist, $airdate, $limit);
		return $db->query($sql);
	}

	public function getMyMoviesRss($num, $uid=0, $excludedcats=array())
	{
		$db = new DB();

		$exccatlist = "";
		if (count($excludedcats) > 0)
			$exccatlist = " and releases.categoryID not in (".implode(",", $excludedcats).")";

		$usql = $this->uSQL($db->query(sprintf("select imdbID, categoryID from usermovies where userID = %d", $uid), true), 'imdbID');

		$limit = " LIMIT 0,".($num > 100 ? 100 : $num);

		$sql = sprintf(" SELECT releases.*, mi.title as releasetitle, g.name as group_name, concat(cp.title, '-', c.title) as category_name, concat(cp.ID, ',', c.ID) as category_ids, coalesce(cp.ID,0) as parentCategoryID
						FROM releases
						left outer join category c on c.ID = releases.categoryID
						left outer join category cp on cp.ID = c.parentID
						left outer join groups g on g.ID = releases.groupID
						left outer join movieinfo mi on mi.imdbID = releases.imdbID
						where %s %s
						and releases.passwordstatus <= (select value from site where setting='showpasswordedrelease')
						order by postdate desc %s" , $usql, $exccatlist, $limit);
		return $db->query($sql);
	}


	public function getShowsRange($usershows, $start, $num, $orderby, $maxage=-1, $excludedcats=array())
	{
		$db = new DB();

		if ($start === false)
			$limit = "";
		else
			$limit = " LIMIT ".$start.",".$num;

		$exccatlist = $maxagesql = "";
		if (count($excludedcats) > 0)
			$exccatlist = " and releases.categoryID not in (".implode(",", $excludedcats).")";

		$usql = $this->uSQL($usershows, 'rageID');

		if ($maxage > 0)
			$maxagesql = sprintf(" and releases.postdate > now() - interval %d day ", $maxage);

		$order = $this->getBrowseOrder($orderby);
		$sql = sprintf(" SELECT releases.*, concat(cp.title, '-', c.title) as category_name, concat(cp.ID, ',', c.ID) as category_ids, groups.name as group_name, rn.ID as nfoID, re.releaseID as reID from releases left outer join releasevideo re on re.releaseID = releases.ID left outer join groups on groups.ID = releases.groupID left outer join releasenfo rn on rn.releaseID = releases.ID and rn.nfo is not null left outer join category c on c.ID = releases.categoryID left outer join category cp on cp.ID = c.parentID where %s %s and releases.passwordstatus <= (select value from site where setting='showpasswordedrelease') %s order by %s %s".$limit, $usql, $exccatlist, $maxagesql, $order[0], $order[1]);
		return $db->query($sql, true);
	}

	public function getShowsCount($usershows, $maxage=-1, $excludedcats=array())
	{
		$db = new DB();

		$exccatlist = $maxagesql = "";
		if (count($excludedcats) > 0)
			$exccatlist = " and releases.categoryID not in (".implode(",", $excludedcats).")";

		$usql = $this->uSQL($usershows, 'rageID');

		if ($maxage > 0)
			$maxagesql = sprintf(" and releases.postdate > now() - interval %d day ", $maxage);

		$res = $db->queryOneRow(sprintf(" SELECT count(releases.ID) as num from releases where %s %s and releases.passwordstatus <= (select value from site where setting='showpasswordedrelease') %s", $usql, $exccatlist, $maxagesql), true);
		return $res['num'];
	}

	public function getCount()
	{
		$db = new DB();
		$res = $db->queryOneRow("select count(ID) as num from releases");
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
			$this->fastDelete($rel['ID'], $rel['guid'], $this->site);
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
		$db->query("delete releases, releasenfo, releasecomment, usercart, releasefiles, releaseaudio, releasesubs, releasevideo, releaseextrafull
							from releases
								LEFT OUTER JOIN releasenfo on releasenfo.releaseID = releases.ID
								LEFT OUTER JOIN releasecomment on releasecomment.releaseID = releases.ID
								LEFT OUTER JOIN usercart on usercart.releaseID = releases.ID
								LEFT OUTER JOIN releasefiles on releasefiles.releaseID = releases.ID
								LEFT OUTER JOIN releaseaudio on releaseaudio.releaseID = releases.ID
								LEFT OUTER JOIN releasesubs on releasesubs.releaseID = releases.ID
								LEFT OUTER JOIN releasevideo on releasevideo.releaseID = releases.ID
								LEFT OUTER JOIN releaseextrafull on releaseextrafull.releaseID = releases.ID
							where releases.ID = ".$id);

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
			$this->fastDelete($rel['ID'], $rel['guid'], $this->site);
		}
	}

	public function update($id, $name, $searchname, $fromname, $category, $parts, $grabs, $size, $posteddate, $addeddate, $rageid, $seriesfull, $season, $episode, $imdbid, $anidbid)
	{
		$db = new DB();

		$db->query(sprintf("update releases set name=%s, searchname=%s, fromname=%s, categoryID=%d, totalpart=%d, grabs=%d, size=%s, postdate=%s, adddate=%s, rageID=%d, seriesfull=%s, season=%s, episode=%s, imdbID=%d, anidbID=%d where id = %d",
			$db->escapeString($name), $db->escapeString($searchname), $db->escapeString($fromname), $category, $parts, $grabs, $db->escapeString($size), $db->escapeString($posteddate), $db->escapeString($addeddate), $rageid, $db->escapeString($seriesfull), $db->escapeString($season), $db->escapeString($episode), $imdbid, $anidbid, $id));
	}

	public function updatemulti($guids, $category, $grabs, $rageid, $season, $imdbid)
	{
		if (!is_array($guids) || sizeof($guids) < 1)
			return false;

		$update = array(
			'categoryID'=>(($category == '-1') ? '' : $category),
			'grabs'=>$grabs,
			'rageID'=>$rageid,
			'season'=>$season,
			'imdbID'=>$imdbid
		);

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

		$sql = sprintf('update releases set '.implode(', ', $updateSql).' where guid in (%s)', implode(', ', $updateGuids));
		return $db->query($sql);
	}

	// Creates part of a query for some functions.
	public function uSQL($userquery, $type)
	{
		$usql = '(1=2 ';
		foreach($userquery as $u)
		{
			$usql .= sprintf('or (releases.%s = %d', $type, $u[$type]);
			if ($u['categoryID'] != '')
			{
				$catsArr = explode('|', $u['categoryID']);
				if (count($catsArr) > 1)
					$usql .= sprintf(' and releases.categoryID in (%s)', implode(',',$catsArr));
				else
					$usql .= sprintf(' and releases.categoryID = %d', $catsArr[0]);
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
						$searchsql .= sprintf(" AND releases.%s LIKE %s", $type, $db->escapeString(substr($word, 1)."%"));
					elseif (substr($word, 0, 2) == '--')
						$searchsql .= sprintf(" AND releases.%s NOT LIKE %s", $type, $db->escapeString("%".substr($word, 2)."%"));
					else
						$searchsql .= sprintf(" AND releases.%s LIKE %s", $type, $db->escapeString("%".$word."%"));

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
			$catsrch = " and (";
			foreach ($cat as $category)
			{
				if ($category != -1)
				{
					if ($categ->isParent($category))
					{
						$children = $categ->getChildren($category);
						$chlist = "-99";
						foreach ($children as $child)
							$chlist.=", ".$child['ID'];

						if ($chlist != "-99")
								$catsrch .= " releases.categoryID in (".$chlist.") or ";
					}
					else
						$catsrch .= sprintf(" releases.categoryID = %d or ", $category);
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
				$catsrch = sprintf(" and (releases.categoryID = %d) ", $cat);
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
			$groupIDsql = sprintf(" and releases.groupID = %d ", $groupID);
		}

		if ($sizefrom == "-1"){$sizefromsql= ("");}
		elseif ($sizefrom == "1"){$sizefromsql= (" and releases.size > 104857600 ");}
		elseif ($sizefrom == "2"){$sizefromsql= (" and releases.size > 262144000 ");}
		elseif ($sizefrom == "3"){$sizefromsql= (" and releases.size > 524288000 ");}
		elseif ($sizefrom == "4"){$sizefromsql= (" and releases.size > 1073741824 ");}
		elseif ($sizefrom == "5"){$sizefromsql= (" and releases.size > 2147483648 ");}
		elseif ($sizefrom == "6"){$sizefromsql= (" and releases.size > 3221225472 ");}
		elseif ($sizefrom == "7"){$sizefromsql= (" and releases.size > 4294967296 ");}
		elseif ($sizefrom == "8"){$sizefromsql= (" and releases.size > 8589934592 ");}
		elseif ($sizefrom == "9"){$sizefromsql= (" and releases.size > 17179869184 ");}
		elseif ($sizefrom == "10"){$sizefromsql= (" and releases.size > 34359738368 ");}
		elseif ($sizefrom == "11"){$sizefromsql= (" and releases.size > 68719476736 ");}

		if ($sizeto == "-1"){$sizetosql= ("");}
		elseif ($sizeto == "1"){$sizetosql= (" and releases.size < 104857600 ");}
		elseif ($sizeto == "2"){$sizetosql= (" and releases.size < 262144000 ");}
		elseif ($sizeto == "3"){$sizetosql= (" and releases.size < 524288000 ");}
		elseif ($sizeto == "4"){$sizetosql= (" and releases.size < 1073741824 ");}
		elseif ($sizeto == "5"){$sizetosql= (" and releases.size < 2147483648 ");}
		elseif ($sizeto == "6"){$sizetosql= (" and releases.size < 3221225472 ");}
		elseif ($sizeto == "7"){$sizetosql= (" and releases.size < 4294967296 ");}
		elseif ($sizeto == "8"){$sizetosql= (" and releases.size < 8589934592 ");}
		elseif ($sizeto == "9"){$sizetosql= (" and releases.size < 17179869184 ");}
		elseif ($sizeto == "10"){$sizetosql= (" and releases.size < 34359738368 ");}
		elseif ($sizeto == "11"){$sizetosql= (" and releases.size < 68719476736 ");}

		if ($hasnfo != "0")
			$hasnfosql= " and releases.nfostatus = 1 ";

		if ($hascomments != "0")
			$hascommentssql = " and releases.comments > 0 ";

		if ($daysnew != "-1")
			$daysnewsql= sprintf(" and releases.postdate < now() - interval %d day ", $daysnew);

		if ($daysold != "-1")
			$daysoldsql= sprintf(" and releases.postdate > now() - interval %d day ", $daysold);

		if ($maxage > 0)
			$maxagesql = sprintf(" and postdate > now() - interval %d day ", $maxage);

		if (count($excludedcats) > 0)
			$exccatlist = " and releases.categoryID not in (".implode(",", $excludedcats).")";

		if ($orderby == "")
		{
			$order[0] = " postdate ";
			$order[1] = " desc ";
		}
		else
			$order = $this->getBrowseOrder($orderby);

		$sql = sprintf("SELECT releases.*, CONCAT(cp.title, ' > ', c.title) AS category_name, CONCAT(cp.ID, ',', c.ID) AS category_ids, groups.name AS group_name, rn.ID AS nfoID, re.releaseID AS reID, cp.ID AS categoryParentID FROM releases LEFT OUTER JOIN releasevideo re ON re.releaseID = releases.ID LEFT OUTER JOIN releasenfo rn ON rn.releaseID = releases.ID LEFT OUTER JOIN groups ON groups.ID = releases.groupID LEFT OUTER JOIN category c ON c.ID = releases.categoryID LEFT OUTER JOIN category cp ON cp.ID = c.parentID WHERE releases.passwordstatus <= (SELECT VALUE FROM site WHERE setting='showpasswordedrelease') %s %s %s %s %s %s %s %s %s %s %s %s %s ORDER BY %s %s LIMIT %d, %d ", $searchnamesql, $usenetnamesql, $maxagesql, $posternamesql, $groupIDsql, $sizefromsql, $sizetosql, $hasnfosql, $hascommentssql, $catsrch, $daysnewsql, $daysoldsql, $exccatlist, $order[0], $order[1], $offset, $limit);
		$wherepos = strpos($sql, "WHERE");
		$countres = $db->queryOneRow("SELECT COUNT(releases.ID) AS num FROM releases ".substr($sql, $wherepos, strpos($sql, "ORDER BY")-$wherepos));
		$res = $db->query($sql);
		if (count($res) > 0){$res[0]['_totalrows'] = $countres['num'];}

		return $res;
	}

	public function searchbyRageId($rageId, $series="", $episode="", $offset=0, $limit=100, $name="", $cat=array(-1), $maxage=-1)
	{
		$db = new DB();

		$rageIdsql = $maxagesql = "";

		if ($rageId != "-1")
			$rageIdsql = sprintf(" and rageID = %d ", $rageId);

		if ($series != "")
		{
			// Exclude four digit series, which will be the year 2010 etc.
			if (is_numeric($series) && strlen($series) != 4)
				$series = sprintf('S%02d', $series);

			$series = sprintf(" and upper(releases.season) = upper(%s)", $db->escapeString($series));
		}

		if ($episode != "")
		{
			if (is_numeric($episode))
				$episode = sprintf('E%02d', $episode);

			$episode = sprintf(" and releases.episode like %s", $db->escapeString('%'.$episode.'%'));
		}

		$searchsql = $this->searchSQL($name, $db, "searchname");
		$catsrch = $this->categorySQL($cat);

		if ($maxage > 0)
			$maxagesql = sprintf(" and releases.postdate > now() - interval %d day ", $maxage);

		$sql = sprintf("SELECT releases.*, concat(cp.title, ' > ', c.title) as category_name, concat(cp.ID, ',', c.ID) as category_ids, groups.name as group_name, rn.ID as nfoID, re.releaseID as reID from releases left outer join category c on c.ID = releases.categoryID left outer join groups on groups.ID = releases.groupID left outer join releasevideo re on re.releaseID = releases.ID left outer join releasenfo rn on rn.releaseID = releases.ID and rn.nfo is not null left outer join category cp on cp.ID = c.parentID where releases.passwordstatus <= (select value from site where setting='showpasswordedrelease') %s %s %s %s %s %s order by postdate desc limit %d, %d ", $rageIdsql, $series, $episode, $searchsql, $catsrch, $maxagesql, $offset, $limit);
		$orderpos = strpos($sql, "order by");
		$wherepos = strpos($sql, "where");
		$sqlcount = "select count(releases.ID) as num from releases ".substr($sql, $wherepos,$orderpos-$wherepos);

		$countres = $db->queryOneRow($sqlcount);
		$res = $db->query($sql);
		if (count($res) > 0)
			$res[0]['_totalrows'] = $countres['num'];

		return $res;
	}

	public function searchbyAnidbId($anidbID, $epno='', $offset=0, $limit=100, $name='', $cat=array(-1), $maxage=-1)
	{
		$db = new DB();

		$anidbID = ($anidbID > -1) ? sprintf(" AND anidbID = %d ", $anidbID) : '';

		is_numeric($epno) ? $epno = sprintf(" AND releases.episode LIKE '%s' ", $db->escapeString('%'.$epno.'%')) : '';

		$searchsql = $this->searchSQL($name, $db, "searchname");
		$catsrch = $this->categorySQL($cat);

		$maxage = ($maxage > 0) ? sprintf(" and releases.postdate > now() - interval %d day ", $maxage) : '';

		$sql = sprintf("SELECT releases.*, concat(cp.title, ' > ', c.title)
			AS category_name, concat(cp.ID, ',', c.ID) AS category_ids, groups.name AS group_name, rn.ID AS nfoID
			FROM releases LEFT OUTER JOIN category c ON c.ID = releases.categoryID LEFT OUTER JOIN groups ON groups.ID = releases.groupID
			LEFT OUTER JOIN releasenfo rn ON rn.releaseID = releases.ID and rn.nfo IS NOT NULL LEFT OUTER JOIN category cp ON cp.ID = c.parentID
			WHERE releases.passwordstatus <= (select value from site where setting='showpasswordedrelease') %s %s %s %s %s ORDER BY postdate desc LIMIT %d, %d ",
			$anidbID, $epno, $searchsql, $catsrch, $maxage, $offset, $limit);
		$orderpos = strpos($sql, "ORDER BY");
		$wherepos = strpos($sql, "WHERE");
		$sqlcount = "SELECT count(releases.ID) AS num FROM releases ".substr($sql, $wherepos,$orderpos-$wherepos);

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
			$imdbId = sprintf(" and imdbID = %d ", $imdbId);
		}
		else
			$imdbId = "";

		$searchsql = $this->searchSQL($name, $db, "searchname");
		$catsrch = $this->categorySQL($cat);

		if ($maxage > 0)
			$maxage = sprintf(" and releases.postdate > now() - interval %d day ", $maxage);
		else
			$maxage = "";

		$sql = sprintf("SELECT releases.*, concat(cp.title, ' > ', c.title) as category_name, concat(cp.ID, ',', c.ID) as category_ids, groups.name as group_name, rn.ID as nfoID from releases left outer join groups on groups.ID = releases.groupID left outer join category c on c.ID = releases.categoryID left outer join releasenfo rn on rn.releaseID = releases.ID and rn.nfo is not null left outer join category cp on cp.ID = c.parentID where releases.passwordstatus <= (select value from site where setting='showpasswordedrelease') %s %s %s %s order by postdate desc limit %d, %d ", $searchsql, $imdbId, $catsrch, $maxage, $offset, $limit);
		$orderpos = strpos($sql, "order by");
		$wherepos = strpos($sql, "where");
		$sqlcount = "select count(releases.ID) as num from releases ".substr($sql, $wherepos,$orderpos-$wherepos);

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
		$catrow = $cat->getById($currRow['categoryID']);
		$parentCat = $catrow['parentID'];

		$ret = array();
		foreach ($results as $res)
			if ($res['ID'] != $currentid && $res['categoryParentID'] == $parentCat)
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
			$gsql = sprintf('guid in (%s)', implode(',',$tmpguids));
		}
		else
			$gsql = sprintf('guid = %s', $db->escapeString($guid));
		$sql = sprintf("SELECT releases.*, CONCAT(cp.title, ' > ', c.title) as category_name, CONCAT(cp.ID, ',', c.ID) AS category_ids, groups.name AS group_name FROM releases LEFT OUTER JOIN groups ON groups.ID = releases.groupID LEFT OUTER JOIN category c ON c.ID = releases.categoryID LEFT OUTER JOIN category cp ON cp.ID = c.parentID WHERE %s ", $gsql);
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

			$series = sprintf(" and upper(releases.season) = upper(%s)", $db->escapeString($series));
		}

		if ($episode != "")
		{
			if (is_numeric($episode))
				$episode = sprintf('E%02d', $episode);

			$episode = sprintf(" and upper(releases.episode) = upper(%s)", $db->escapeString($episode));
		}

		return $db->queryOneRow(sprintf("select releases.*, concat(cp.title, ' > ', c.title) as category_name, groups.name as group_name from releases left outer join groups on groups.ID = releases.groupID  left outer join category c on c.ID = releases.categoryID left outer join category cp on cp.ID = c.parentID where releases.passwordstatus <= (select value from site where setting='showpasswordedrelease') and rageID = %d %s %s", $rageid, $series, $episode));
	}

	public function removeRageIdFromReleases($rageid)
	{
		$db = new DB();
		$res = $db->queryOneRow(sprintf("select count(ID) as num from releases where rageID = %d", $rageid));
		$ret = $res['num'];
		$res = $db->query(sprintf("update releases set rageID = -1, seriesfull = null, season = null, episode = null where rageID = %d", $rageid));
		return $ret;
	}

	public function removeAnidbIdFromReleases($anidbID)
	{
		$db = new DB();
		$res = $db->queryOneRow(sprintf("SELECT count(ID) AS num FROM releases WHERE anidbID = %d", $anidbID));
		$ret = $res['num'];
		$res = $db->query(sprintf("UPDATE releases SET anidbID = -1, episode = null, tvtitle = null, tvairdate = null where anidbID = %d", $anidbID));
		return $ret;
	}

	public function getById($id)
	{
		$db = new DB();
		return $db->queryOneRow(sprintf("select releases.*, groups.name as group_name from releases left outer join groups on groups.ID = releases.groupID where releases.ID = %d ", $id));
	}

	public function getReleaseNfo($id, $incnfo=true)
	{
		$db = new DB();
		$selnfo = ($incnfo) ? ', uncompress(nfo) as nfo' : '';
		return $db->queryOneRow(sprintf("SELECT ID, releaseID".$selnfo." FROM releasenfo where releaseID = %d AND nfo IS NOT NULL", $id));
	}

	public function updateGrab($guid)
	{
		if ($this->updategrabs)
		{
			$db = new DB();
			$db->queryOneRow(sprintf("update releases set grabs = grabs + 1 where guid = %s", $db->escapeString($guid)));
		}
	}

	// Sends releases back to other->misc.
	public function resetCategorize($where="")
	{
		$db = new DB();
		$db->queryDirect("UPDATE releases set categoryID = 7010, relnamestatus = 0 ".$where);
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

		$resrel = $db->queryDirect("SELECT ID, ".$type.", groupID FROM releases ".$where);
		while ($rowrel = $db->fetchAssoc($resrel))
		{
			$catId = $cat->determineCategory($rowrel[$type], $rowrel['groupID']);
			$db->queryDirect(sprintf("UPDATE releases SET categoryID = %d, relnamestatus = 1 WHERE ID = %d", $catId, $rowrel['ID']));
			$relcount ++;
			if ($this->echooutput)
				$consoletools->overWrite("Categorizing:".$consoletools->percentString($relcount,mysqli_num_rows($resrel)));
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
		$where = (!empty($groupID)) ? " AND groupID = ".$groupID : "";

		// Look if we have all the files in a collection (which have the file count in the subject). Set filecheck to 1.
		$db->query("UPDATE collections c SET c.filecheck = 1 WHERE c.ID IN (SELECT b.collectionID FROM binaries b WHERE b.collectionID = c.ID GROUP BY b.collectionID, c.totalFiles HAVING count(b.ID)
						in (c.totalFiles, c.totalFiles + 1)) AND c.totalFiles > 0 AND c.filecheck = 0 ".$where);

		// Set filecheck to 16 if theres a file that starts with 0 (ex. [00/100]).
		$db->query("UPDATE collections c SET filecheck = 16 WHERE c.ID IN (SELECT b.collectionID FROM binaries b WHERE b.collectionID = c.ID AND b.filenumber = 0 ".$where."
						GROUP BY b.collectionID) AND c.totalFiles > 0 AND c.filecheck = 1");

		// Set filecheck to 15 on everything left over, so anything that starts with 1 (ex. [01/100]).
		$db->query("UPDATE collections set filecheck = 15 where filecheck = 1");

		// If we have all the parts set partcheck to 1.
		if (empty($groupID))
		{
			// If filecheck 15, check if we have all the parts for a file then set partcheck.
			$db->query("UPDATE binaries b SET partcheck = 1 WHERE b.ID IN (SELECT p.binaryID FROM parts p, collections c WHERE p.binaryID = b.ID AND c.filecheck = 15 AND c.id = b.collectionID
							GROUP BY p.binaryID HAVING count(p.ID) = b.totalParts) AND b.partcheck = 0");

			// If filecheck 16, check if we have all the parts+1(because of the 0) then set partcheck.
			$db->query("UPDATE binaries b SET partcheck = 1 WHERE b.ID IN (SELECT p.binaryID FROM parts p, collections c WHERE p.binaryID = b.ID AND c.filecheck = 16 AND c.id = b.collectionID
							GROUP BY p.binaryID HAVING count(p.ID) >= b.totalParts+1) AND b.partcheck = 0");
		}
		else
		{
			$db->query("UPDATE binaries b SET partcheck = 1 WHERE b.ID IN (SELECT p.binaryID FROM parts p ,collections c WHERE p.binaryID = b.ID AND c.filecheck = 15 AND c.id = b.collectionID and
							c.groupID = ".$groupID." GROUP BY p.binaryID HAVING count(p.ID) = b.totalParts ) AND b.partcheck = 0");
			$db->query("UPDATE binaries b SET partcheck = 1 WHERE b.ID IN (SELECT p.binaryID FROM parts p ,collections c WHERE p.binaryID = b.ID AND c.filecheck = 16 AND c.id = b.collectionID and
							c.groupID = ".$groupID." GROUP BY p.binaryID HAVING count(p.ID) >= b.totalParts+1 ) AND b.partcheck = 0");
		}

		// Set filecheck to 2 if partcheck = 1.
		$db->query("UPDATE collections c SET filecheck = 2 WHERE c.ID IN (SELECT b.collectionID FROM binaries b WHERE c.ID = b.collectionID AND b.partcheck = 1 GROUP BY b.collectionID
						HAVING count(b.ID) >= c.totalFiles) AND c.filecheck in (15, 16) ".$where);

		// Set filecheck to 1 if we don't have all the parts.
		$db->query("UPDATE collections SET filecheck = 1 WHERE filecheck in (15, 16) ".$where);

		// If a collection has not been updated in 2 hours, set filecheck to 2.
		$db->query(sprintf("UPDATE collections c SET filecheck = 2, totalFiles = (SELECT COUNT(b.ID) FROM binaries b WHERE b.collectionID = c.ID) WHERE c.dateadded < (now() - interval %d hour) AND c.filecheck
						in (0, 1, 10) ".$where, $this->delaytimet));

		if ($this->echooutput)
			echo $consoletools->convertTime(TIME() - $stage1);
	}

	public function processReleasesStage2($groupID, $echooutput=false)
	{
		$db = new DB();
		$consoletools = new ConsoleTools();
		$where = (!empty($groupID)) ? " AND groupID = " . $groupID : "";

		if ($this->echooutput)
			echo "\n\033[1;33mStage 2 -> Get the size in bytes of the collection.\033[0m\n";
		$stage2 = TIME();
		// Get the total size in bytes of the collection for collections where filecheck = 2.
		$db->query("UPDATE collections c SET filesize = (SELECT SUM(size) FROM parts p LEFT JOIN binaries b ON p.binaryID = b.ID WHERE b.collectionID = c.ID), c.filecheck = 3 WHERE
						c.filecheck = 2 AND c.filesize = 0 ".$where);

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
				if($db->queryDirect("SELECT ID from collections where filecheck = 3 and filesize > 0"))
				{
					$db->query("UPDATE collections c LEFT JOIN (SELECT g.ID, coalesce(g.minsizetoformrelease, s.minsizetoformrelease) as minsizetoformrelease FROM groups g INNER JOIN
									( SELECT value as minsizetoformrelease FROM site WHERE setting = 'minsizetoformrelease' ) s ) g ON g.ID = c.groupID SET c.filecheck = 5 WHERE g.minsizetoformrelease != 0
									AND c.filecheck = 3 AND c.filesize < g.minsizetoformrelease and c.filesize > 0 AND groupID = ".$groupID['ID']);

					$minsizecount = $db->getAffectedRows();
					if ($minsizecount < 0)
						$minsizecount = 0;
					$minsizecounts = $minsizecount+$minsizecounts;

					$maxfilesizeres = $db->queryOneRow("select value from site where setting = maxsizetoformrelease");
					if ($maxfilesizeres['value'] != 0)
					{
						$db->query(sprintf("UPDATE collections SET filecheck = 5 WHERE filecheck = 3 AND groupID = %d AND filesize > %d ", $groupID['ID'], $maxfilesizeres['value']));

						$maxsizecount = $db->getAffectedRows();
						if ($maxsizecount < 0)
							$maxsizecount = 0;
						$maxsizecounts = $maxsizecount+$maxsizecounts;
					}

					$db->query("UPDATE collections c LEFT JOIN (SELECT g.ID, coalesce(g.minfilestoformrelease, s.minfilestoformrelease) as minfilestoformrelease FROM groups g INNER JOIN
									( SELECT value as minfilestoformrelease FROM site WHERE setting = 'minfilestoformrelease' ) s ) g ON g.ID = c.groupID SET c.filecheck = 5
									WHERE g.minfilestoformrelease != 0 AND c.filecheck = 3 AND c.totalFiles < g.minfilestoformrelease AND groupID = ".$groupID['ID']);

					$minfilecount = $db->getAffectedRows();
					if ($minfilecount < 0)
						$minfilecount = 0;
					$minfilecounts = $minfilecount+$minfilecounts;
				}
			}
		}
		else
		{
			if($db->queryDirect("SELECT ID from collections where filecheck = 3 and filesize > 0"))
			{
				$db->query("UPDATE collections c LEFT JOIN (SELECT g.ID, coalesce(g.minsizetoformrelease, s.minsizetoformrelease) as minsizetoformrelease FROM groups g INNER JOIN
								( SELECT value as minsizetoformrelease FROM site WHERE setting = 'minsizetoformrelease' ) s ) g ON g.ID = c.groupID SET c.filecheck = 5
								WHERE g.minsizetoformrelease != 0 AND c.filecheck = 3 AND c.filesize < g.minsizetoformrelease and c.filesize > 0 AND groupID = ".$groupID);

				$minsizecount = $db->getAffectedRows();
				if ($minsizecount < 0)
					$minsizecount = 0;
				$minsizecounts = $minsizecount+$minsizecounts;

				$maxfilesizeres = $db->queryOneRow("select value from site where setting = maxsizetoformrelease");
				if ($maxfilesizeres['value'] != 0)
				{
					$db->query(sprintf("UPDATE collections SET filecheck = 5 WHERE filecheck = 3 AND filesize > %d " . $where, $maxfilesizeres['value']));

					$maxsizecount = $db->getAffectedRows();
					if ($maxsizecount < 0)
						$maxsizecount = 0;
					$maxsizecounts = $maxsizecount+$maxsizecounts;
				}

				$db->query("UPDATE collections c LEFT JOIN (SELECT g.ID, coalesce(g.minfilestoformrelease, s.minfilestoformrelease) as minfilestoformrelease FROM groups g INNER JOIN
								( SELECT value as minfilestoformrelease FROM site WHERE setting = 'minfilestoformrelease' ) s ) g ON g.ID = c.groupID SET c.filecheck = 5
								WHERE g.minfilestoformrelease != 0 AND c.filecheck = 3 AND c.totalFiles < g.minfilestoformrelease AND groupID = ".$groupID);

				$minfilecount = $db->getAffectedRows();
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
		$where = (!empty($groupID)) ? " AND groupID = " . $groupID : "";

		if ($this->echooutput)
			echo "\n\033[1;33mStage 4 -> Create releases.\033[0m\n";
		$stage4 = TIME();
		if($rescol = $db->queryDirect("SELECT * FROM collections WHERE filecheck = 3 AND filesize > 0 " . $where . " LIMIT ".$this->stage5limit))
		{
			$namecleaning = new nameCleaning();
			$predb = new  Predb();
			$page = new Page();

			while ($rowcol = $db->fetchAssoc($rescol))
			{
				$cleanArr = array('#', '@', '$', '%', '^', '§', '¨', '©', 'Ö');
				$cleanRelName = str_replace($cleanArr, '', $rowcol['subject']);
				$cleanerName = $namecleaning->releaseCleaner($rowcol['subject'], $rowcol['groupID']);
				$relguid = sha1(uniqid().mt_rand());
				if($db->queryInsert(sprintf("INSERT IGNORE INTO releases (name, searchname, totalpart, groupID, adddate, guid, rageID, postdate, fromname, size, passwordstatus, haspreview, categoryID, nfostatus)
											VALUES (%s, %s, %d, %d, now(), %s, -1, %s, %s, %s, %d, -1, 7010, -1)",
											$db->escapeString($cleanRelName), $db->escapeString($cleanerName), $rowcol['totalFiles'], $rowcol['groupID'], $db->escapeString($relguid),
											$db->escapeString($rowcol['date']), $db->escapeString($rowcol['fromname']), $db->escapeString($rowcol['filesize']), ($page->site->checkpasswordedrar == "1" ? -1 : 0))))
				{
					$relid = $db->getInsertID();
					$predb->matchPre($cleanRelName, $relid);
					// Update collections table to say we inserted the release.
					$db->queryDirect(sprintf("UPDATE collections SET filecheck = 4, releaseID = %d WHERE ID = %d", $relid, $rowcol['ID']));
					$retcount ++;
					if ($this->echooutput)
						echo "Added release ".$cleanRelName."\n";
				}
				else
				{
					if ($this->echooutput)
						echo "\033[01;31mError Inserting Release: \033[0m".$cleanerName.": ".$db->Error()."\n";
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

		$catresrel = $db->query("select c.ID as ID, CASE WHEN c.minsize = 0 THEN cp.minsize ELSE c.minsize END as minsize from category c left outer join category cp on cp.ID = c.parentID where c.parentID is not null");

		foreach ($catresrel as $catrowrel) {
			$resrel = $db->query(sprintf("SELECT r.ID, r.guid from releases r where r.categoryID = %d AND r.size < %d", $catrowrel['ID'], $catrowrel['minsize']));
			foreach ($resrel as $rowrel)
			{
				$this->fastDelete($rowrel['ID'], $rowrel['guid'], $this->site);
				$catminsizecount ++;
			}
		}

		if ($groupID == "")
		{
			$groups = new Groups();
			$groupIDs = $groups->getActiveIDs();

			foreach ($groupIDs as $groupID)
			{
				if ($resrel = $db->query(sprintf("SELECT r.ID, r.guid FROM releases r LEFT JOIN
							(SELECT g.ID, coalesce(g.minsizetoformrelease, s.minsizetoformrelease)
							as minsizetoformrelease FROM groups g INNER JOIN ( SELECT value as minsizetoformrelease
							FROM site WHERE setting = 'minsizetoformrelease' ) s WHERE g.ID = %s ) g ON g.ID = r.groupID WHERE
							g.minsizetoformrelease != 0 AND r.size < minsizetoformrelease AND r.groupID = %s", $groupID['ID'], $groupID['ID'])))
				{
					foreach ($resrel as $rowrel)
					{
						$this->fastDelete($rowrel['ID'], $rowrel['guid'], $this->site);
						$minsizecount ++;
					}
				}

				$maxfilesizeres = $db->queryOneRow("SELECT value FROM site WHERE setting = maxsizetoformrelease");
				if ($maxfilesizeres['value'] != 0)
				{
					if ($resrel = $db->query(sprintf("SELECT ID, guid from releases where groupID = %d AND filesize > %d", $groupID['ID'], $maxfilesizeres['value'])))
					{
						foreach ($resrel as $rowrel)
						{
							$this->fastDelete($rowrel['ID'], $rowrel['guid'], $this->site);
							$maxsizecount ++;
						}
					}
				}

				if ($resrel = $db->query(sprintf("SELECT r.ID, r.guid FROM releases r LEFT JOIN
							(SELECT g.ID, coalesce(g.minfilestoformrelease, s.minfilestoformrelease)
							as minfilestoformrelease FROM groups g INNER JOIN ( SELECT value as minfilestoformrelease
							FROM site WHERE setting = 'minfilestoformrelease' ) s WHERE g.ID = %s ) g ON g.ID = r.groupID WHERE
							g.minfilestoformrelease != 0 AND r.totalpart < minfilestoformrelease AND r.groupID = %s", $groupID['ID'], $groupID['ID'])))
				{
					foreach ($resrel as $rowrel)
					{
						$this->fastDelete($rowrel['ID'], $rowrel['guid'], $this->site);
						$minfilecount ++;
					}
				}
			}
		}
		else
		{
			if ($resrel = $db->query(sprintf("SELECT r.ID, r.guid FROM releases r LEFT JOIN
						(SELECT g.ID, coalesce(g.minsizetoformrelease, s.minsizetoformrelease)
						as minsizetoformrelease FROM groups g INNER JOIN ( SELECT value as minsizetoformrelease
						FROM site WHERE setting = 'minsizetoformrelease' ) s WHERE g.ID = %s ) g ON g.ID = r.groupID WHERE
						g.minsizetoformrelease != 0 AND r.size < minsizetoformrelease AND r.groupID = %s", $groupID, $groupID)))
			{
				foreach ($resrel as $rowrel)
				{
					$this->fastDelete($rowrel['ID'], $rowrel['guid'], $this->site);
					$minsizecount ++;
				}
			}

			$maxfilesizeres = $db->queryOneRow("SELECT value FROM site WHERE setting = maxsizetoformrelease");
			if ($maxfilesizeres['value'] != 0)
			{
				if ($resrel = $db->query(sprintf("SELECT ID, guid from releases where groupID = %d AND filesize > %d", $groupID, $maxfilesizeres['value'])))
				{
					foreach ($resrel as $rowrel)
					{
						$this->fastDelete($rowrel['ID'], $rowrel['guid'], $this->site);
						$maxsizecount ++;
					}
				}
			}

			if ($resrel = $db->query(sprintf("SELECT r.ID, r.guid FROM releases r LEFT JOIN
						(SELECT g.ID, coalesce(g.minfilestoformrelease, s.minfilestoformrelease)
						as minfilestoformrelease FROM groups g INNER JOIN ( SELECT value as minfilestoformrelease
						FROM site WHERE setting = 'minfilestoformrelease' ) s WHERE g.ID = %s ) g ON g.ID = r.groupID WHERE
						g.minfilestoformrelease != 0 AND r.totalpart < minfilestoformrelease AND r.groupID = %s", $groupID, $groupID)))
			{
				foreach ($resrel as $rowrel)
				{
					$this->fastDelete($rowrel['ID'], $rowrel['guid'], $this->site);
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
		$where = (!empty($groupID)) ? " AND groupID = " . $groupID : "";

		// Create NZB.
		if ($this->echooutput)
			echo "\n\033[1;33mStage 5 -> Create the NZB, mark collections as ready for deletion.\033[0m\n";
		$stage5 = TIME();
		if($resrel = $db->queryDirect("SELECT ID, guid, name, categoryID FROM releases WHERE nzbstatus = 0 " . $where . " LIMIT ".$this->stage5limit))
		{
			while ($rowrel = $db->fetchAssoc($resrel))
			{
				$nzb_guid = $nzb->writeNZBforReleaseId($rowrel['ID'], $rowrel['guid'], $rowrel['name'], $rowrel['categoryID'], $nzb->getNZBPath($rowrel['guid'], $nzbpath, true, $nzbsplitlevel), false, $version, $cat);
				if($nzb_guid != false)
				{
					$db->queryDirect(sprintf("UPDATE releases SET nzbstatus = 1, nzb_guid = %s WHERE ID = %d", $db->escapestring(md5($nzb_guid)), $rowrel['ID']));
					$db->queryDirect(sprintf("UPDATE collections SET filecheck = 5 WHERE releaseID = %s", $rowrel['ID']));
					$nzbcount++;
					if ($this->echooutput)
						$consoletools->overWrite("Creating NZBs:".$consoletools->percentString($nzbcount,mysqli_num_rows($resrel)));
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
			$where = (!empty($groupID)) ? " AND groupID = ".$groupID : "";
			$stage8 = TIME();

			if ($this->echooutput)
				echo "\n\033[1;33mStage 5b -> Request ID lookup.\033[0m";

			// Mark records that don't have regex titles.
			$db->query( "UPDATE releases SET reqidstatus = -1 WHERE reqidstatus = 0 AND nzbstatus = 1 AND relnamestatus = 1 AND name REGEXP '^\\[[[:digit:]]+\\]' = 0 " . $where);

			// Look for records that potentially have regex titles.
			$resrel = $db->queryDirect( "SELECT r.ID, r.name, g.name groupName " .
										"FROM releases r LEFT JOIN groups g ON r.groupID = g.ID " .
										"WHERE relnamestatus = 1 AND nzbstatus = 1 AND reqidstatus = 0 AND r.name REGEXP '^\\[[[:digit:]]+\\]' = 1 " . $where);

			while ($rowrel = $db->fetchAssoc($resrel))
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
					$db->query("UPDATE releases SET reqidstatus = 1, searchname = ". $db->escapeString($newTitle)." WHERE ID = ". $rowrel['ID']);

					if ($this->echooutput)
						echo "\nUpdated requestID " . $requestID . " to release name: ".$newTitle."\n";
				}
				else
				{
					$db->query("UPDATE releases SET reqidstatus = -2 WHERE ID = " . $rowrel['ID']);
					if ($this->echooutput)
						echo ".";
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
		$where = (!empty($groupID)) ? "WHERE relnamestatus = 0 AND groupID = " . $groupID : "WHERE relnamestatus = 0";

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

		$where = (!empty($groupID)) ? " AND collections.groupID = " . $groupID : "";

		// Delete old releases and finished collections.
		if ($this->echooutput)
			echo $n."\033[1;33mStage 7a -> Delete finished collections.\033[0m".$n;
		$stage7 = TIME();

		// Completed releases and old collections that were missed somehow.
		$db->queryDirect(sprintf("DELETE collections, binaries, parts
						  FROM collections INNER JOIN binaries ON collections.ID = binaries.collectionID INNER JOIN parts on binaries.ID = parts.binaryID
						  WHERE collections.filecheck = 5 " . $where));
		$reccount = $db->getAffectedRows();

		if ($this->echooutput)
				echo "Removed ".number_format($reccount)." parts/binaries/collection rows in ".$consoletools->convertTime(TIME() - $stage7).".";
	}

	public function processReleasesStage7b($groupID, $echooutput=false)
	{
		$db = new DB();
		$page = new Page();
		$category = new Category();
		$genres = new Genres();
		$consoletools = new ConsoleTools();
		$remcount = $passcount = $passcount = $dupecount = $relsizecount = $completioncount = $disabledcount = $disabledgenrecount = $miscothercount = 0;

		$where = (!empty($groupID)) ? " AND collections.groupID = " . $groupID : "";

		// Delete old releases and finished collections.
		if ($this->echooutput)
			echo "\n\033[1;33mStage 7b -> Delete old releases and passworded releases.\033[0m\n";
		$stage7 = TIME();

		// old collections that were missed somehow.
		$db->queryDirect(sprintf("DELETE collections, binaries, parts
						  FROM collections INNER JOIN binaries ON collections.ID = binaries.collectionID INNER JOIN parts on binaries.ID = parts.binaryID
						  WHERE collections.dateadded < (now() - interval %d hour) " . $where, $page->site->partretentionhours));
		$reccount = $db->getAffectedRows();

		// Binaries/parts that somehow have no collection.
		$db->queryDirect("DELETE binaries, parts FROM binaries LEFT JOIN parts ON binaries.ID = parts.binaryID WHERE binaries.collectionID = 0 " . $where);

		// Parts that somehow have no binaries.
		$db->queryDirect("DELETE FROM parts WHERE `binaryID` NOT IN (SELECT b.id FROM binaries b) " . $where);

		// Binaries that somehow have no collection.
		$db->queryDirect("DELETE FROM `binaries` WHERE `collectionID` NOT IN (SELECT c.`ID` FROM `collections` c) " . $where);

		// Collections that somehow have no binaries.
		$db->queryDirect("DELETE FROM collections WHERE collections.ID NOT IN ( SELECT binaries.collectionID FROM binaries) " . $where);

		$where = (!empty($groupID)) ? " AND groupID = " . $groupID : "";
		// Releases past retention.
		if($page->site->releaseretentiondays != 0)
		{
			$result = $db->query(sprintf("SELECT ID, guid FROM releases WHERE postdate < (now() - interval %d day)", $page->site->releaseretentiondays));
			foreach ($result as $rowrel)
			{
				$this->fastDelete($rowrel['ID'], $rowrel['guid'], $this->site);
				$remcount ++;
			}
		}

		// Passworded releases.
		if($page->site->deletepasswordedrelease == 1)
		{
			$result = $db->query("SELECT ID, guid FROM releases WHERE passwordstatus = ".Releases::PASSWD_RAR);
			foreach ($result as $rowrel)
			{
				$this->fastDelete($rowrel['ID'], $rowrel['guid'], $this->site);
				$passcount ++;
			}
		}

		// Possibly passworded releases.
		if($page->site->deletepossiblerelease == 1)
		{
			$result = $db->query("SELECT ID, guid FROM releases WHERE passwordstatus = ".Releases::PASSWD_POTENTIAL);
			foreach ($result as $rowrel)
			{
				$this->fastDelete($rowrel['ID'], $rowrel['guid'], $this->site);
				$passcount ++;
			}
		}

		// Crossposted releases.
		if($resrel = $db->query(sprintf("SELECT ID, guid FROM releases WHERE adddate > (now() - interval %d hour) GROUP BY name HAVING count(name) > 1", $this->crosspostt)))
		{
			foreach ($resrel as $rowrel)
			{
				$this->fastDelete($rowrel['ID'], $rowrel['guid'], $this->site);
				$dupecount ++;
			}
		}

		// Releases below completion %.
		if($this->completion > 0)
		{
			if($resrel = $db->query(sprintf("SELECT ID, guid FROM releases WHERE completion < %d and completion > 0", $this->completion)))
			{
				foreach ($resrel as $rowrel)
				{
					$this->fastDelete($rowrel['ID'], $rowrel['guid'], $this->site);
					$completioncount ++;
				}
			}
		}

		// Disabled categories.
		if ($catlist = $category->getDisabledIDs())
		{
			while ($cat = mysqli_fetch_assoc($catlist))
			{
				if ($rels = $db->query(sprintf("select ID, guid from releases where categoryID = %d", $cat['ID'])))
				{
					foreach ($rels as $rel)
					{
						$disabledcount++;
						$this->fastDelete($rel['ID'], $rel['guid'], $this->site);
					}
				}
			}
		}

		// Disabled music genres.
		if ($genrelist = $genres->getDisabledIDs())
		{
			foreach ($genrelist as $genre)
			{
				$rels = $db->query(sprintf("select ID, guid from releases inner join (select ID as mid from musicinfo where musicinfo.genreID = %d) mi on releases.musicinfoID = mid", $genre['ID']));
				foreach ($rels as $rel)
				{
					$disabledgenrecount++;
					$this->fastDelete($rel['ID'], $rel['guid'], $this->site);
				}
			}
		}

		// Misc other.
		if ($page->site->miscotherretentionhours > 0) {
			$sql = sprintf("select ID, guid from releases where categoryID = %d AND adddate <= NOW() - INTERVAL %d HOUR", CATEGORY::CAT_MISC, $page->site->miscotherretentionhours);

			if ($resrel = $db->query($sql)) {
				foreach ($resrel as $rowrel)
				{
					$this->fastDelete($rowrel['ID'], $rowrel['guid'], $this->site);
					$miscothercount ++;
				}
			}

		}

		$db->queryDirect(sprintf("DELETE nzbs WHERE dateadded < (now() - interval %d hour)", $page->site->partretentionhours));

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
		if($res = $db->queryDirect("SELECT b.ID as bID, b.name as bname, c.* FROM binaries b LEFT JOIN collections c ON b.collectionID = c.ID"))
		{
			if (mysqli_num_rows($res) > 0)
			{
				$timestart = TIME();
				if ($this->echooutput)
					echo "Going to remake all the collections. This can be a long process, be patient. DO NOT STOP THIS SCRIPT!\n";
				// Reset the collectionhash.
				$db->query("UPDATE collections SET collectionhash = 0");
				$delcount = 0;
				$cIDS = array();
				while ($row = mysqli_fetch_assoc($res))
				{
					$nofiles = true;
					if ($row['totalFiles'] > 0)
						$nofiles = false;
					$newSHA1 = sha1($namecleaner->collectionsCleaner($row['bname'], $row['groupID'], $nofiles).$row['fromname'].$row['groupID'].$row['totalFiles']);
					$cres = $db->queryOneRow(sprintf("SELECT ID FROM collections WHERE collectionhash = %s", $db->escapeString($newSHA1)));
					if(!$cres)
					{
						$cIDS[] = $row['ID'];
						$csql = sprintf("INSERT IGNORE INTO collections (subject, fromname, date, xref, groupID, totalFiles, collectionhash, filecheck, dateadded) VALUES (%s, %s, %s, %s, %d, %s, %s, 0, now())", $db->escapeString($row['bname']), $db->escapeString($row['fromname']), $db->escapeString($row['date']), $db->escapeString($row['xref']), $row['groupID'], $db->escapeString($row['totalFiles']), $db->escapeString($newSHA1));
						$collectionID = $db->queryInsert($csql);
						if ($this->echooutput)
							$consoletools->overWrite("Recreated: ".count($cIDS)." collections. Time:".$consoletools->convertTimer(TIME() - $timestart));
					}
					else
						$collectionID = $cres['ID'];
					//Update the binaries with the new info.
					$db->query(sprintf("UPDATE binaries SET collectionID = %d where ID = %d", $collectionID, $row['bID']));
				}
				//Remove the old collections.
				$delstart = TIME();
				if ($this->echooutput)
					echo "\n";
				foreach ($cIDS as $cID)
				{
					$db->query(sprintf("DELETE FROM collections WHERE ID = %d", $cID));
					$delcount++;
					if ($this->echooutput)
						$consoletools->overWrite("Deleting old collections:".$consoletools->percentString($delcount,sizeof($cIDS))." Time:".$consoletools->convertTimer(TIME() - $delstart));
				}
				// Delete previous failed attempts.
				$db->query('DELETE FROM collections where collectionhash = "0"');

				if ($this->hashcheck == 0)
					$db->query('UPDATE site SET value = "1" where setting = "hashcheck"');
				if ($this->echooutput)
					echo "\nRemade ".count($cIDS)." collections in ".$consoletools->convertTime(TIME() - $timestart)."\n";
			}
			else
				$db->query('UPDATE site SET value = "1" where setting = "hashcheck"');
		}
	}

	public function getTopDownloads()
	{
		$db = new DB();
		return $db->query("SELECT ID, searchname, guid, adddate, SUM(grabs) as grabs FROM releases
							GROUP BY ID, searchname, adddate
							HAVING SUM(grabs) > 0
							ORDER BY grabs DESC
							LIMIT 10");
	}

	public function getTopComments()
	{
		$db = new DB();
		return $db->query("SELECT ID, guid, searchname, adddate, SUM(comments) as comments FROM releases
							GROUP BY ID, searchname, adddate
							HAVING SUM(comments) > 0
							ORDER BY comments DESC
							LIMIT 10");
	}

	public function getRecentlyAdded()
	{
		$db = new DB();
		return $db->query("SELECT concat(cp.title, ' > ', category.title) as title, COUNT(*) AS count
							FROM category
							left outer join category cp on cp.ID = category.parentID
							INNER JOIN releases ON releases.categoryID = category.ID
							WHERE releases.adddate > NOW() - INTERVAL 1 WEEK
							GROUP BY concat(cp.title, ' > ', category.title)
							ORDER BY COUNT(*) DESC");
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
