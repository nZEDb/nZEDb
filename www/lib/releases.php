<?php
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/page.php");
require_once(WWW_DIR."/lib/binaries.php");
require_once(WWW_DIR."/lib/users.php");
require_once(WWW_DIR."/lib/releaseregex.php");
require_once(WWW_DIR."/lib/category.php");
require_once(WWW_DIR."/lib/nzb.php");
require_once(WWW_DIR."/lib/nfo.php");
require_once(WWW_DIR."/lib/zipfile.php");
require_once(WWW_DIR."/lib/site.php");
require_once(WWW_DIR."/lib/util.php");
require_once(WWW_DIR."/lib/releasefiles.php");
require_once(WWW_DIR."/lib/releaseextra.php");
require_once(WWW_DIR."/lib/releaseimage.php");
require_once(WWW_DIR."/lib/releasecomments.php");
require_once(WWW_DIR."/lib/postprocess.php");

class Releases
{	
	//
	// initial binary state after being added from usenet
	const PROCSTAT_NEW = 0;

	//
	// after a binary has matched a releaseregex
	const PROCSTAT_TITLEMATCHED = 5;

	//
	// after a binary has been confirmed as having the right number of parts
	const PROCSTAT_READYTORELEASE = 1;
	
	//
	// after a binary has has been attempted to be matched for x days and 
	// still has the wrong number of parts
	const PROCSTAT_WRONGPARTS = 2;
	
	//
	// binary that has finished and successfully made it into a release
	const PROCSTAT_RELEASED = 4;
	
	//
	// binary that is identified as already being part of another release 
	//(with the same name posted in a similar date range)
	const PROCSTAT_DUPLICATE = 6;

	//
	// after a series of attempts to lookup the allfilled style reqid
	// to get a name, its given up
	const PROCSTAT_NOREQIDNAMELOOKUPFOUND = 7;

	//
	// the release is below the minimum size specified in site table
	const PROCSTAT_MINRELEASESIZE = 8;

	//
	// the release is inside a category that is disabled
	const PROCSTAT_CATDISABLED = 9;

	//
	// passworded indicator
	//
	const PASSWD_NONE = 0;
	const PASSWD_RAR = 1;
	const PASSWD_POTENTIAL = 2;	
	
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
	
	public function getBrowseCount($cat, $maxage=-1, $excludedcats=array(), $grp = "")
	{
		$db = new DB();

		$catsrch = "";
		if (count($cat) > 0 && $cat[0] != -1)
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
							$chlist.=", ".$child["ID"];

						if ($chlist != "-99")
								$catsrch .= " releases.categoryID in (".$chlist.") or ";
					}
					else
					{
						$catsrch .= sprintf(" releases.categoryID = %d or ", $category);
					}
				}
			}
			$catsrch.= "1=2 )";
		}			

		if ($maxage > 0)
			$maxage = sprintf(" and postdate > now() - interval %d day ", $maxage);
		else
			$maxage = "";		
		
		$grpsql = "";
		if ($grp != "")
			$grpsql = sprintf(" and groups.name = %s ", $db->escapeString($grp));
		
		$exccatlist = "";
		if (count($excludedcats) > 0)
			$exccatlist = " and categoryID not in (".implode(",", $excludedcats).")";
		
		$res = $db->queryOneRow(sprintf("select count(releases.ID) as num from releases left outer join groups on groups.ID = releases.groupID where releases.passwordstatus <= (select value from site where setting='showpasswordedrelease') %s %s %s %s", $catsrch, $maxage, $exccatlist, $grpsql));		
		return $res["num"];	
	}	
	
	public function getBrowseRange($cat, $start, $num, $orderby, $maxage=-1, $excludedcats=array(), $grp="")
	{		
		$db = new DB();
		
		if ($start === false)
			$limit = "";
		else
			$limit = " LIMIT ".$start.",".$num;
		
		$catsrch = "";
		if (count($cat) > 0 && $cat[0] != -1)
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
							$chlist.=", ".$child["ID"];

						if ($chlist != "-99")
								$catsrch .= " releases.categoryID in (".$chlist.") or ";
					}
					else
					{
						$catsrch .= sprintf(" releases.categoryID = %d or ", $category);
					}
				}
			}
			$catsrch.= "1=2 )";
		}	
		
		$maxagesql = "";
		if ($maxage > 0)
			$maxagesql = sprintf(" and postdate > now() - interval %d day ", $maxage);

		$grpsql = "";
		if ($grp != "")
			$grpsql = sprintf(" and groups.name = %s ", $db->escapeString($grp));

		$exccatlist = "";
		if (count($excludedcats) > 0)
			$exccatlist = " and releases.categoryID not in (".implode(",", $excludedcats).")";
			
		$order = $this->getBrowseOrder($orderby);
		return $db->query(sprintf(" SELECT releases.*, concat(cp.title, ' > ', c.title) as category_name, concat(cp.ID, ',', c.ID) as category_ids, groups.name as group_name, rn.ID as nfoID, re.releaseID as reID from releases left outer join groups on groups.ID = releases.groupID left outer join releasevideo re on re.releaseID = releases.ID left outer join releasenfo rn on rn.releaseID = releases.ID and rn.nfo is not null left outer join category c on c.ID = releases.categoryID left outer join category cp on cp.ID = c.parentID where releases.passwordstatus <= (select value from site where setting='showpasswordedrelease') %s %s %s %s order by %s %s".$limit, $catsrch, $maxagesql, $exccatlist, $grpsql, $order[0], $order[1]));		
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
		return $row["postdate"];	
	}

	public function getLatestUsenetPostDate()
	{
		$db = new DB();
		$row = $db->queryOneRow("SELECT DATE_FORMAT(max(postdate), '%d/%m/%Y') as postdate from releases");
		return $row["postdate"];	
	}

	public function getReleasedGroupsForSelect($blnIncludeAll = true)
	{
		$db = new DB();
		$groups = $db->query("select distinct groups.ID, groups.name from releases inner join groups on groups.ID = releases.groupID");
		$temp_array = array();
		
		if ($blnIncludeAll)
			$temp_array[-1] = "--All Groups--";
		
		foreach($groups as $group)
			$temp_array[$group["ID"]] = $group["name"];

		return $temp_array;
	}
	
	public function getRss($cat, $num, $uid=0, $rageid, $anidbid, $airdate=-1)
	{		
		$db = new DB();
		
		$limit = " LIMIT 0,".($num > 100 ? 100 : $num);

		$catsrch = "";
		$cartsrch = "";

		$catsrch = "";
		if (count($cat) > 0)
		{
			if ($cat[0] == -2)
			{
				$cartsrch = sprintf(" inner join usercart on usercart.userID = %d and usercart.releaseID = releases.ID ", $uid);
			}
			else
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
								$chlist.=", ".$child["ID"];
	
							if ($chlist != "-99")
									$catsrch .= " releases.categoryID in (".$chlist.") or ";
						}
						else
						{
							$catsrch .= sprintf(" releases.categoryID = %d or ", $category);
						}
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
		
		$usershows = $db->query(sprintf("select rageID, categoryID from userseries where userID = %d", $uid), true);
		$usql = '(1=2 ';
		foreach($usershows as $ushow)
		{
			$usql .= sprintf('or (releases.rageID = %d', $ushow['rageID']);
			if ($ushow['categoryID'] != '')
			{
				$catsArr = explode('|', $ushow['categoryID']);
				if (count($catsArr) > 1)
					$usql .= sprintf(' and releases.categoryID in (%s)', implode(',',$catsArr));
				else
					$usql .= sprintf(' and releases.categoryID = %d', $catsArr[0]);
			}
			$usql .= ') ';
		}
		$usql .= ') ';
		
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
		
		$usermovies = $db->query(sprintf("select imdbID, categoryID from usermovies where userID = %d", $uid), true);
		$usql = '(1=2 ';
		foreach($usermovies as $umov)
		{
			$usql .= sprintf('or (releases.imdbID = %d', $umov['imdbID']);
			if ($umov['categoryID'] != '')
			{
				$catsArr = explode('|', $umov['categoryID']);
				if (count($catsArr) > 1)
					$usql .= sprintf(' and releases.categoryID in (%s)', implode(',',$catsArr));
				else
					$usql .= sprintf(' and releases.categoryID = %d', $catsArr[0]);
			}
			$usql .= ') ';
		}
		$usql .= ') ';
		
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
		
		$exccatlist = "";
		if (count($excludedcats) > 0)
			$exccatlist = " and releases.categoryID not in (".implode(",", $excludedcats).")";
		
		$usql = '(1=2 ';
		foreach($usershows as $ushow)
		{
			$usql .= sprintf('or (releases.rageID = %d', $ushow['rageID']);
			if ($ushow['categoryID'] != '')
			{
				$catsArr = explode('|', $ushow['categoryID']);
				if (count($catsArr) > 1)
					$usql .= sprintf(' and releases.categoryID in (%s)', implode(',',$catsArr));
				else
					$usql .= sprintf(' and releases.categoryID = %d', $catsArr[0]);
			}
			$usql .= ') ';
		}
		$usql .= ') ';
		
		$maxagesql = "";
		if ($maxage > 0)
			$maxagesql = sprintf(" and releases.postdate > now() - interval %d day ", $maxage);

		$order = $this->getBrowseOrder($orderby);
		$sql = sprintf(" SELECT releases.*, concat(cp.title, '-', c.title) as category_name, concat(cp.ID, ',', c.ID) as category_ids, groups.name as group_name, rn.ID as nfoID, re.releaseID as reID from releases left outer join releasevideo re on re.releaseID = releases.ID left outer join groups on groups.ID = releases.groupID left outer join releasenfo rn on rn.releaseID = releases.ID and rn.nfo is not null left outer join category c on c.ID = releases.categoryID left outer join category cp on cp.ID = c.parentID where %s %s and releases.passwordstatus <= (select value from site where setting='showpasswordedrelease') %s order by %s %s".$limit, $usql, $exccatlist, $maxagesql, $order[0], $order[1]);
		return $db->query($sql, true);		
	}
	
	public function getShowsCount($usershows, $maxage=-1, $excludedcats=array())
	{		
		$db = new DB();
		
		$exccatlist = "";
		if (count($excludedcats) > 0)
			$exccatlist = " and releases.categoryID not in (".implode(",", $excludedcats).")";
		
		$usql = '(1=2 ';
		foreach($usershows as $ushow)
		{
			$usql .= sprintf('or (releases.rageID = %d', $ushow['rageID']);
			if ($ushow['categoryID'] != '')
			{
				$catsArr = explode('|', $ushow['categoryID']);
				if (count($catsArr) > 1)
					$usql .= sprintf(' and releases.categoryID in (%s)', implode(',',$catsArr));
				else
					$usql .= sprintf(' and releases.categoryID = %d', $catsArr[0]);
			}
			$usql .= ') ';
		}
		$usql .= ') ';
		
		$maxagesql = "";
		if ($maxage > 0)
			$maxagesql = sprintf(" and releases.postdate > now() - interval %d day ", $maxage);

		$res = $db->queryOneRow(sprintf(" SELECT count(releases.ID) as num from releases where %s %s and releases.passwordstatus <= (select value from site where setting='showpasswordedrelease') %s", $usql, $exccatlist, $maxagesql), true);		
		return $res["num"];
	}
	
	public function getCount()
	{			
		$db = new DB();
		$res = $db->queryOneRow("select count(ID) as num from releases");		
		return $res["num"];
	}
	
	public function delete($id, $isGuid=false)
	{			
		$db = new DB();
		$users = new Users();
		$s = new Sites();
		$nfo = new Nfo();
		$site = $s->get();
		$rf = new ReleaseFiles();
		$re = new ReleaseExtra();
		$rc = new ReleaseComments();
		$ri = new ReleaseImage();
		
		if (!is_array($id))
			$id = array($id);
				
		foreach($id as $identifier)
		{
			//
			// delete from disk.
			//
			$rel = ($isGuid) ? $this->getByGuid($identifier) : $this->getById($identifier);

			if ($rel && file_exists($site->nzbpath.$rel["guid"].".nzb.gz")) 
				unlink($site->nzbpath.$rel["guid"].".nzb.gz");
			
			$nfo->deleteReleaseNfo($rel['ID']);
			$rc->deleteCommentsForRelease($rel['ID']);
			$users->delCartForRelease($rel['ID']);
			$rf->delete($rel['ID']);
			$re->delete($rel['ID']);
			$re->deleteFull($rel['ID']);
			$ri->delete($rel['guid']);
			$db->query(sprintf("delete from releases where id = %d", $rel['ID']));
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
		foreach($update as $updk=>$updv) {
			if ($updv != '') 
				$updateSql[] = sprintf($updk.'=%s', $db->escapeString($updv));
		}
		
		if (sizeof($updateSql) < 1) {
			//echo 'no field set to be changed';
			return -1;
		}
		
		$updateGuids = array();
		foreach($guids as $guid) {
			$updateGuids[] = $db->escapeString($guid);
		}
		
		$sql = sprintf('update releases set '.implode(', ', $updateSql).' where guid in (%s)', implode(', ', $updateGuids));
		return $db->query($sql);
	}	

	public function searchadv($searchname, $filename, $poster, $group, $cat=array(-1), $sizefrom, $sizeto, $offset=0, $limit=1000, $orderby='', $maxage=-1, $excludedcats=array())
	{
		return array();
	}
	
	public function search($search, $cat=array(-1), $offset=0, $limit=1000, $orderby='', $maxage=-1, $excludedcats=array())
	{			
		$db = new DB();

		$catsrch = "";
		if (count($cat) > 0 && $cat[0] != -1)
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
							$chlist.=", ".$child["ID"];

						if ($chlist != "-99")
								$catsrch .= " releases.categoryID in (".$chlist.") or ";
					}
					else
					{
						$catsrch .= sprintf(" releases.categoryID = %d or ", $category);
					}
				}
			}
			$catsrch.= "1=2 )";
		}		
		
		//
		// if the query starts with a ^ it indicates the search is looking for items which start with the term
		// still do the fulltext match, but mandate that all items returned must start with the provided word
		//
		$words = explode(" ", $search);
		$searchsql = "";
		$intwordcount = 0;
		if (count($words) > 0)
		{
			foreach ($words as $word)
			{
				if ($word != "")
				{
					//
					// see if the first word had a caret, which indicates search must start with term
					//
					if ($intwordcount == 0 && (strpos($word, "^") === 0))
						$searchsql.= sprintf(" and releases.searchname like %s", $db->escapeString(substr($word, 1)."%"));
					elseif (substr($word, 0, 2) == '--')
						$searchsql.= sprintf(" and releases.searchname not like %s", $db->escapeString("%".substr($word, 2)."%"));
					else
						$searchsql.= sprintf(" and releases.searchname like %s", $db->escapeString("%".$word."%"));

					$intwordcount++;
				}
			}
		}
		
		if ($maxage > 0)
			$maxage = sprintf(" and postdate > now() - interval %d day ", $maxage);
		else
			$maxage = "";
		
		$exccatlist = "";
		if (count($excludedcats) > 0)
			$exccatlist = " and releases.categoryID not in (".implode(",", $excludedcats).")";

		if ($orderby == "")
		{
			$order[0] = " postdate ";
			$order[1] = " desc ";
		}	
		else
			$order = $this->getBrowseOrder($orderby);

		$sql = sprintf("select releases.*, concat(cp.title, ' > ', c.title) as category_name, concat(cp.ID, ',', c.ID) as category_ids, groups.name as group_name, rn.ID as nfoID, re.releaseID as reID, cp.ID as categoryParentID from releases left outer join releasevideo re on re.releaseID = releases.ID left outer join releasenfo rn on rn.releaseID = releases.ID left outer join groups on groups.ID = releases.groupID left outer join category c on c.ID = releases.categoryID left outer join category cp on cp.ID = c.parentID where releases.passwordstatus <= (select value from site where setting='showpasswordedrelease') %s %s %s %s order by %s %s limit %d, %d ", $searchsql, $catsrch, $maxage, $exccatlist, $order[0], $order[1], $offset, $limit);            
		$orderpos = strpos($sql, "order by");
		$wherepos = strpos($sql, "where");
		$sqlcount = "select count(releases.ID) as num from releases ".substr($sql, $wherepos,$orderpos-$wherepos);

		$countres = $db->queryOneRow($sqlcount);
		$res = $db->query($sql);
		if (count($res) > 0)
			$res[0]["_totalrows"] = $countres["num"];
		
		return $res;
	}	
	
	public function searchbyRageId($rageId, $series="", $episode="", $offset=0, $limit=100, $name="", $cat=array(-1), $maxage=-1)
	{			
		$db = new DB();
		
		if ($rageId != "-1")
			$rageId = sprintf(" and rageID = %d ", $rageId);
		else
			$rageId = "";

		if ($series != "")
		{
			//
			// Exclude four digit series, which will be the year 2010 etc
			//
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

		//
		// if the query starts with a ^ it indicates the search is looking for items which start with the term
		// still do the fulltext match, but mandate that all items returned must start with the provided word
		//
		$words = explode(" ", $name);
		$searchsql = "";
		$intwordcount = 0;
		if (count($words) > 0)
		{
			foreach ($words as $word)
			{
				if ($word != "")
				{			
					//
					// see if the first word had a caret, which indicates search must start with term
					//
					if ($intwordcount == 0 && (strpos($word, "^") === 0))
						$searchsql.= sprintf(" and releases.searchname like %s", $db->escapeString(substr($word, 1)."%"));
					elseif (substr($word, 0, 2) == '--')
						$searchsql.= sprintf(" and releases.searchname not like %s", $db->escapeString("%".substr($word, 2)."%"));
					else
						$searchsql.= sprintf(" and releases.searchname like %s", $db->escapeString("%".$word."%"));

					$intwordcount++;
				}
			}
		}

		$catsrch = "";
		if (count($cat) > 0 && $cat[0] != -1)
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
							$chlist.=", ".$child["ID"];

						if ($chlist != "-99")
								$catsrch .= " releases.categoryID in (".$chlist.") or ";
					}
					else
					{
						$catsrch .= sprintf(" releases.categoryID = %d or ", $category);
					}
				}
			}
			$catsrch.= "1=2 )";
		}		
		
		if ($maxage > 0)
			$maxage = sprintf(" and postdate > now() - interval %d day ", $maxage);
		else
			$maxage = "";		
		
		$sql = sprintf("select releases.*, concat(cp.title, ' > ', c.title) as category_name, concat(cp.ID, ',', c.ID) as category_ids, groups.name as group_name, rn.ID as nfoID, re.releaseID as reID from releases left outer join category c on c.ID = releases.categoryID left outer join groups on groups.ID = releases.groupID left outer join releasevideo re on re.releaseID = releases.ID left outer join releasenfo rn on rn.releaseID = releases.ID and rn.nfo is not null left outer join category cp on cp.ID = c.parentID where releases.passwordstatus <= (select value from site where setting='showpasswordedrelease') %s %s %s %s %s %s order by postdate desc limit %d, %d ", $rageId, $series, $episode, $searchsql, $catsrch, $maxage, $offset, $limit);            
		$orderpos = strpos($sql, "order by");
		$wherepos = strpos($sql, "where");
		$sqlcount = "select count(releases.ID) as num from releases ".substr($sql, $wherepos,$orderpos-$wherepos);

		$countres = $db->queryOneRow($sqlcount);
		$res = $db->query($sql);
		if (count($res) > 0)
			$res[0]["_totalrows"] = $countres["num"];
		
		return $res;
	}
	
	public function searchbyAnidbId($anidbID, $epno='', $offset=0, $limit=100, $name='', $cat=array(-1), $maxage=-1)
	{			
		$db = new DB();
		
		$anidbID = ($anidbID > -1) ? sprintf(" AND anidbID = %d ", $anidbID) : '';

		is_numeric($epno) ? $epno = sprintf(" AND releases.episode LIKE '%s' ", $db->escapeString('%'.$epno.'%')) : '';

		//
		// if the query starts with a ^ it indicates the search is looking for items which start with the term
		// still do the fulltext match, but mandate that all items returned must start with the provided word
		//
		$words = explode(" ", $name);
		$searchsql = "";
		$intwordcount = 0;
		if (count($words) > 0)
		{
			foreach ($words as $word)
			{
				if ($word != "")
				{			
					//
					// see if the first word had a caret, which indicates search must start with term
					//
					if ($intwordcount == 0 && (strpos($word, "^") === 0))
						$searchsql.= sprintf(" AND releases.searchname LIKE '%s' ", $db->escapeString(substr($word, 1)."%"));
					elseif (substr($word, 0, 2) == '--')
						$searchsql.= sprintf(" AND releases.searchname NOT LIKE '%s' ", $db->escapeString("%".substr($word, 2)."%"));
					else
						$searchsql.= sprintf(" AND releases.searchname LIKE '%s' ", $db->escapeString("%".$word."%"));

					$intwordcount++;
				}
			}
		}

		$catsrch = "";
		if (count($cat) > 0 && $cat[0] != -1)
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
							$chlist.=", ".$child["ID"];

						if ($chlist != "-99")
								$catsrch .= " releases.categoryID in (".$chlist.") or ";
					}
					else
					{
						$catsrch .= sprintf(" releases.categoryID = %d or ", $category);
					}
				}
			}
			$catsrch.= "1=2 )";
		}		

		$maxage = ($maxage > 0) ? sprintf(" and postdate > now() - interval %d day ", $maxage) : '';		
		
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
			$res[0]["_totalrows"] = $countres["num"];
		
		return $res;
	}
	
	public function searchbyImdbId($imdbId, $offset=0, $limit=100, $name="", $cat=array(-1), $maxage=-1)
	{			
		$db = new DB();
		
		if ($imdbId != "-1" && is_numeric($imdbId)) 
		{
			//pad id with zeros just in case
			$imdbId = str_pad($imdbId, 7, "0",STR_PAD_LEFT);
			$imdbId = sprintf(" and imdbID = %d ", $imdbId);
		} 
		else 
		{
			$imdbId = "";
		}

		//
		// if the query starts with a ^ it indicates the search is looking for items which start with the term
		// still do the fulltext match, but mandate that all items returned must start with the provided word
		//
		$words = explode(" ", $name);
		$searchsql = "";
		$intwordcount = 0;
		if (count($words) > 0)
		{
			foreach ($words as $word)
			{
				if ($word != "")
				{
					//
					// see if the first word had a caret, which indicates search must start with term
					//
					if ($intwordcount == 0 && (strpos($word, "^") === 0))
						$searchsql.= sprintf(" and releases.searchname like %s", $db->escapeString(substr($word, 1)."%"));
					elseif (substr($word, 0, 2) == '--')
						$searchsql.= sprintf(" and releases.searchname not like %s", $db->escapeString("%".substr($word, 2)."%"));
					else
						$searchsql.= sprintf(" and releases.searchname like %s", $db->escapeString("%".$word."%"));

					$intwordcount++;
				}
			}
		}
		
		$catsrch = "";
		if (count($cat) > 0 && $cat[0] != -1)
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
							$chlist.=", ".$child["ID"];

						if ($chlist != "-99")
								$catsrch .= " releases.categoryID in (".$chlist.") or ";
					}
					else
					{
						$catsrch .= sprintf(" releases.categoryID = %d or ", $category);
					}
				}
			}
			$catsrch.= "1=2 )";
		}		
		
		if ($maxage > 0)
			$maxage = sprintf(" and postdate > now() - interval %d day ", $maxage);
		else
			$maxage = "";		
		
		$sql = sprintf("select releases.*, concat(cp.title, ' > ', c.title) as category_name, concat(cp.ID, ',', c.ID) as category_ids, groups.name as group_name, rn.ID as nfoID from releases left outer join groups on groups.ID = releases.groupID left outer join category c on c.ID = releases.categoryID left outer join releasenfo rn on rn.releaseID = releases.ID and rn.nfo is not null left outer join category cp on cp.ID = c.parentID where releases.passwordstatus <= (select value from site where setting='showpasswordedrelease') %s %s %s %s order by postdate desc limit %d, %d ", $searchsql, $imdbId, $catsrch, $maxage, $offset, $limit);            
		$orderpos = strpos($sql, "order by");
		$wherepos = strpos($sql, "where");
		$sqlcount = "select count(releases.ID) as num from releases ".substr($sql, $wherepos,$orderpos-$wherepos);

		$countres = $db->queryOneRow($sqlcount);
		$res = $db->query($sql);
		if (count($res) > 0)
			$res[0]["_totalrows"] = $countres["num"];
		
		return $res;
	}			

	public function searchSimilar($currentid, $name, $limit=6, $excludedcats=array())
	{			
		$name = $this->getSimilarName($name);
		$results = $this->search($name, array(-1), 0, $limit, '', -1, $excludedcats);
		if (!$results)
			return $results;

		//
		// Get the category for the parent of this release
		//
		$currRow = $this->getById($currentid);
		$cat = new Category();
		$catrow = $cat->getById($currRow["categoryID"]);
		$parentCat = $catrow["parentID"];
		
		$ret = array();
		foreach ($results as $res)
			if ($res["ID"] != $currentid && $res["categoryParentID"] == $parentCat)
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
		} else {
			$gsql = sprintf('guid = %s', $db->escapeString($guid));
		}
		$sql = sprintf("select releases.*, concat(cp.title, ' > ', c.title) as category_name, concat(cp.ID, ',', c.ID) as category_ids, groups.name as group_name from releases left outer join groups on groups.ID = releases.groupID left outer join category c on c.ID = releases.categoryID left outer join category cp on cp.ID = c.parentID where %s ", $gsql);
		return (is_array($guid)) ? $db->query($sql) : $db->queryOneRow($sql);		
	}	

	//
	// writes a zip file of an array of release guids directly to the stream
	//
	public function getZipped($guids)
	{
		$s = new Sites();
		$nzb = new NZB;
		$site = $s->get();
		$zipfile = new zipfile();
		
		foreach ($guids as $guid)
		{
			$nzbpath = $nzb->getNZBPath($guid, $site->nzbpath);

			if (file_exists($nzbpath)) 
			{
				ob_start();
				@readgzfile($nzbpath);
				$nzbfile = ob_get_contents();
				ob_end_clean();

				$filename = $guid;
				$r = $this->getByGuid($guid);
				if ($r)
					$filename = $r["searchname"];
				
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
			//
			// Exclude four digit series, which will be the year 2010 etc
			//
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
		$ret = $res["num"];
		$res = $db->query(sprintf("update releases set rageID = -1, seriesfull = null, season = null, episode = null where rageID = %d", $rageid));		
		return $ret;
	}
	
	public function removeAnidbIdFromReleases($anidbID)
	{
		$db = new DB();
		$res = $db->queryOneRow(sprintf("SELECT count(ID) AS num FROM releases WHERE anidbID = %d", $anidbID));		
		$ret = $res["num"];
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
		$db = new DB();
		$db->queryOneRow(sprintf("update releases set grabs = grabs + 1 where guid = %s", $db->escapeString($guid)));		
	}	
	
	public function processReleases($categorize)
	{
		$db = new DB;
		$cat = new Category;
		$bin = new Binaries;
		$nzb = new Nzb;
		$nfo = new Nfo;
		$s = new Sites;
		$page = new Page;
		$groups = new Groups;
		$retcount = 0;
		$remcount = 0;
		$colcount = 0;
		$passcount = 0;
		$minsizecount = 0;
		$n = "\n";
		
		$this->processReleases = microtime(true);
		echo $n."Starting release update process (".date("Y-m-d H:i:s").")".$n;
		
		if (!file_exists($page->site->nzbpath))
		{
			echo "Bad or missing nzb directory - ".$page->site->nzbpath;
			return;
		}
		
		$groupCnt = $groups->getActiveIDs();
		
		echo "Stage 1 -> Go over groups to find complete collections.".$n;
		foreach($groupCnt AS $groupID)
		{
			$groupID = array_shift($groupID);
			//Look if we have all the files in a collection (which have the file count in the subject).
			if($rescol = $db->queryDirect(sprintf("SELECT ID, totalFiles from collections where groupID = %d and totalFiles > 0 and filecheck = 0 and filecheckdate < (now() - interval 10 minute) order by filecheckdate asc", $groupID)))
			{
				//See if all the files are present in the binaries table.
				while ($rowcol = mysql_fetch_assoc($rescol))
				{
					$colID = $rowcol['ID'];
					$colFileCnt = $rowcol['totalFiles'];
					$binFileCnt = $db->queryOneRow(sprintf("SELECT count(ID) from binaries where collectionID = %d", $colID));
					$binFileCnt = array_shift($binFileCnt);
					if($binFileCnt >= $colFileCnt)
					{
						$db->queryDirect(sprintf("UPDATE collections set filecheck = 1 where ID = %d", $colID));
					}
					else
					{
						$db->queryDirect(sprintf("UPDATE collections set filecheckdate = now() where ID = %d", $colID));
					}
				}
			}
			//Check if we have all parts for a file. Set partcheck to 1.
			if($rescol = $db->queryDirect(sprintf("SELECT ID, totalFiles from collections where groupID = %d and filecheck = 1 and dateadded > (now() - interval 4 hour) order by dateadded asc", $groupID)))
			{
				while ($rowcol = mysql_fetch_assoc($rescol))
				{
					$colID = $rowcol['ID'];
					if($resbin = $db->queryDirect(sprintf("SELECT ID, totalParts from binaries where collectionID = %d and totalParts > 0 and partcheck = 0 and partcheckdate < (now() - interval 10 minute) order by partcheckdate asc", $colID)))
					{
						while ($rowbins = mysql_fetch_assoc($resbin))
						{
							$binID = $rowbins['ID'];
							$binpartCnt = $rowbins['totalParts'];
							$partCnt = $db->queryOneRow(sprintf("SELECT count(ID) from parts where binaryID = %d", $binID));
							$partCnt = array_shift($partCnt);
							if($partCnt >= $binpartCnt)
							{
								$db->queryDirect(sprintf("UPDATE binaries set partcheck = 1 where ID = %d", $binID));
							}
							else
							{
								$db->queryDirect(sprintf("UPDATE binaries set partcheckdate = now() where ID = %d", $binID));
							}
						}
					}
					//Check if everything is complete. Set filecheck to 2.
					$colFileCnt = $rowcol['totalFiles'];
					if($binFileCnt = $db->queryOneRow(sprintf("SELECT count(ID) from binaries where partcheck = 1 and collectionID = %d", $colID)))
					{
						$binFileCnt = array_shift($binFileCnt);
						if($binFileCnt > 0)
						{
							if($binFileCnt >= $colFileCnt)
							{
								$db->queryDirect(sprintf("UPDATE collections set filecheck = 2 where ID = %d", $colID));
							}
						}
					}
				}
			}
			//If we didnt find all parts in the past 4 hours for releases with files, set filecheck to 2. Incomplete releases.
			if($rescol = $db->queryDirect(sprintf("SELECT ID from collections where groupID = %d and filecheck = 1 and dateadded < (now() - interval 4 hour) order by dateadded asc", $groupID)))
			{
				while ($rowcol = mysql_fetch_assoc($rescol))
				{
					$colID = $rowcol['ID'];
					$db->queryDirect(sprintf("UPDATE collections set filecheck = 2 where ID = %d", $colID));
				}
			}
			//Look when the collection (without files) was last updated, if more than 4 hours, mark as complete, (filecheck=2). Some might be incomplete releases.
			if($frescol = $db->queryDirect(sprintf("SELECT ID from collections where groupID = %d and filecheck = 0 and dateadded < (now() - interval 4 hour) order by dateadded asc", $groupID)))
			{
				while ($frowcol = mysql_fetch_assoc($frescol))
				{
					$colID = $frowcol['ID'];
					$db->queryDirect(sprintf("UPDATE collections set filecheck = 2 where ID = %d", $colID));
				}
			}
		}
		//Get part and file size.
		echo $n."Stage 2 -> Get part and file sizes.".$n;
		if($rescol = $db->queryDirect(sprintf("SELECT ID from collections where filecheck = 2 and filesize = 0 order by dateadded asc", $groupID)))
		{
			while ($rowcol = mysql_fetch_assoc($rescol))
			{
				$colID = $rowcol['ID'];
				//Update binaries size.
				$resbin = $db->queryDirect(sprintf("SELECT ID from binaries where collectionID = %d", $colID));
				while ($rowbin = mysql_fetch_assoc($resbin))
				{
					$binID = $rowbin['ID'];
					$respartsize = $db->queryOneRow(sprintf("SELECT sum(size) from parts where binaryID = %d", $binID));
					$respartsize = array_shift($respartsize);
					$db->queryDirect(sprintf("UPDATE binaries set partsize = %d where ID = %d", $respartsize, $binID));
				}
				//Update collection size.
				$resbinsize = $db->queryOneRow(sprintf("SELECT sum(partsize) from binaries where collectionID = %d", $colID));
				$resbinsize = array_shift($resbinsize);
				$db->queryDirect(sprintf("UPDATE collections set filesize = %d where ID = %d", $resbinsize, $colID));
			}
		}
		//Create releases.
		echo $n."Stage 3 -> Create releases.".$n;
		if($rescol = $db->queryDirect(sprintf("SELECT * from collections where filecheck = 2 and filesize > 0 order by dateadded asc", $groupID)))
		{
			while ($rowcol = mysql_fetch_assoc($rescol))
			{
				$colID = $rowcol['ID'];
				$cleanArr = array('#', '@', '$', '%', '^', '§', '¨', '©', 'Ö');
				$cleanRelName = str_replace($cleanArr, '', $rowcol['name']);
				$relguid = md5(uniqid());
				if($relID = $db->queryInsert(sprintf("insert into releases (name, searchname, totalpart, groupID, adddate, guid, rageID, postdate, fromname, size, passwordstatus, categoryID) values (%s, %s, %d, %d, now(), %s, -1, %s, %s, %s, %d, 7010)", 
							$db->escapeString($cleanRelName), $db->escapeString($cleanRelName), $rowcol["totalFiles"], $rowcol["groupID"], $db->escapeString($relguid), $db->escapeString($rowcol["date"]), $db->escapeString($rowcol["fromname"]), $db->escapeString($rowcol["filesize"]), ($page->site->checkpasswordedrar == "2" ? -1 : 0))));
				{
					//update collections table to say we inserted the release.
					$db->queryDirect(sprintf("UPDATE collections set filecheck = 3, releaseID = %d where ID = %d", $relID, $colID));
					$retcount ++;
					echo "Added release ".$cleanRelName.$n;
				}
			}
		}
		//Delete unwanted releases.
		echo $n."Stage 4 -> Delete releases smaller than minimum size site setting.".$n;
		foreach($groupCnt AS $groupID)
		{
			$groupID = array_shift($groupID);
			$minfilesizeres = $db->queryOneRow(sprintf("SELECT coalesce(g.minsizetoformrelease, s.minsizetoformrelease) as minsizetoformrelease FROM groups g inner join ( select value as minsizetoformrelease from site where setting = 'minsizetoformrelease' ) s where g.ID = %d", $groupID));			
			if ($minfilesizeres["minsizetoformrelease"] != 0)
			{
				$resrel = $db->queryDirect(sprintf("SELECT ID from releases where size < %d", $minfilesizeres["minsizetoformrelease"]));
				while ($rowrel = mysql_fetch_assoc($resrel))
				{
					$relID = $rowrel['ID'];
					$db->queryDirect(sprintf("delete from releases where ID = %d", $relID));
					$minsizecount ++;
				}
			}
		}
		echo "Deleted ".$minsizecount." releases smaller than group settings".$n;
		//Look for NFOs.
		echo $n."Stage 5 -> Mark releases that have an NFO.".$n;
		if($resrel = $db->queryDirect("SELECT ID, guid, name, categoryID from releases where nfostatus = 0 order by adddate asc"))
		{
			while ($rowrel = mysql_fetch_assoc($resrel))
			{
				$relid = $rowrel['ID'];
				$relnfo = $nfo->determineReleaseNfo($relid);
				if ($relnfo !== false) 
				{
					$nfo->addReleaseNfo($relid, $relnfo['ID']);
				}
				//Set nfostatus to 1, even if it fails.
				$db->queryDirect(sprintf("UPDATE releases set nfostatus = 1 where ID = %d", $relid));
			}
		}
		//Create NZB.
		echo $n."Stage 6 -> Create the NZB, mark collections as ready for deletion.".$n;
		if($resrel = $db->queryDirect("SELECT ID, guid, name, categoryID from releases where nzbstatus = 0 and nfostatus <> 0 order by adddate asc"))
		{
			while ($rowrel = mysql_fetch_assoc($resrel))
			{
				$relid = $rowrel['ID'];
				$relguid = $rowrel['guid'];
				$cleanRelName = $rowrel['name'];
				$catId = $rowrel['categoryID'];
				$rescol = $db->queryDirect(sprintf("SELECT ID from collections where releaseID = %d", $relid));
				while ($rowcol = mysql_fetch_assoc($rescol))
				{
					$colID = $rowcol['ID'];
					$nzb->writeNZBforReleaseId($relid, $relguid, $cleanRelName, $catId, $nzb->getNZBPath($relguid, $page->site->nzbpath, true));
					$db->queryDirect(sprintf("UPDATE releases set nzbstatus = 1 where ID = %d", $relid));
					$db->queryDirect(sprintf("UPDATE collections set filecheck = 4 where ID = %d", $colID));
				}
			}
		}
		//Categorize releases.
		echo $n."Stage 7 -> Categorize releases.".$n;
		if ($categorize == 1)
		{
			$resrel = $db->queryDirect(sprintf("SELECT ID, name, groupID from releases where relnamestatus = 0", $minfilesizeres["minsizetoformrelease"]));
			while ($rowrel = mysql_fetch_assoc($resrel))
			{
				$relID = $rowrel['ID'];
				$groupID = $rowrel['groupID'];
				$groupName = $groups->getByNameByID($groupID);
				$catId = $cat->determineCategory($groupName, $rowrel["name"]);
				$db->queryDirect(sprintf("UPDATE releases set categoryID = %d, relnamestatus = 1 where ID = %d", $catId, $relID));
			}
		}
		//Post processing
		echo $n."Stage 8 -> Post processing.".$n;
		$postprocess = new PostProcess(true);
		$postprocess->processAll();
		//Delete old releases and finished collections.
		echo $n."Stage 9 -> Delete old releases, finished collections and passworded releases.".$n;
		//Old collections that were missed somehow.
		if($frescol = $db->queryDirect(sprintf("SELECT ID from collections where dateadded < (now() - interval %d day) order by dateadded asc", $page->site->attemptgroupbindays)))
		{
			while ($frowcol = mysql_fetch_assoc($frescol))
			{
				$colID = $frowcol['ID'];
				$fresbin = $db->queryDirect(sprintf("SELECT ID from binaries where collectionID = %d", $colID));
				while ($frowbin = mysql_fetch_assoc($fresbin))
				{
					$binID = $frowbin['ID'];
					$db->queryDirect(sprintf("delete from parts where binaryID = %d", $binID));
				}
				$db->queryDirect(sprintf("delete from binaries where collectionID = %d", $colID));
				$db->queryDirect(sprintf("delete from collections where ID = %d", $colID));
				$colcount ++;
			}
		}
		//Finished collections.
		if($frescol = $db->queryDirect("SELECT ID from collections where filecheck = 4 order by dateadded asc"))
		{
			while ($frowcol = mysql_fetch_assoc($frescol))
			{
				$colID = $frowcol['ID'];
				$fresbin = $db->queryDirect(sprintf("SELECT ID from binaries where collectionID = %d", $colID));
				while ($frowbin = mysql_fetch_assoc($fresbin))
				{
					$binID = $frowbin['ID'];
					$db->queryDirect(sprintf("delete from parts where binaryID = %d", $binID));
				}
				$db->queryDirect(sprintf("delete from binaries where collectionID = %d", $colID));
				$db->queryDirect(sprintf("delete from collections where ID = %d", $colID));
				$colcount ++;
			}
		}
		//Releases past retention.
		if($page->site->releaseretentiondays != 0)
		{
			$result = $db->query(sprintf("select ID from releases where postdate < now() - interval %d day", $page->site->releaseretentiondays)); 		
			foreach ($result as $row)
				$this->delete($row["ID"]);
				$remcount ++;
		}
		// Delete any passworded releases
		if($page->site->deletepasswordedrelease == 1)
		{
			echo "Determining any passworded releases to be deleted".$n.$n;
			$result = $db->query("select ID from releases where passwordstatus > 0"); 		
			foreach ($result as $row)
			{
				$this->delete($row["ID"]);
				$passcount ++;
			}
		}
		//Print amount of added releases and time it took.
		$timeUpdate = number_format(microtime(true) - $this->processReleases, 2);
		echo "Removed: ".$colcount." collections, ".$remcount." releases past retention, ".$passcount." passworded releases".$n.$n;
		$cremain = $db->queryOneRow("select count(ID) from collections");
		$cremain = array_shift($cremain);
		echo "Completed creating ".$retcount." releases in ".$timeUpdate." seconds. ".$cremain." collections waiting to be created (still incomplete).".$n;
		return $retcount;	
	}	
	
	public function getReleaseNameForReqId($url, $nnid, $groupname, $reqid)
	{
		$url = str_ireplace("[GROUP]", urlencode($groupname), $url);
		$url = str_ireplace("[REQID]", urlencode($reqid), $url);

		if ($nnid != "")
			$nnid = "&newznabID=".$nnid;
		
		$xml = "";
		$arrXml = "";
		$xml = getUrl($url);
		
		if ($xml === false || preg_match('/no feed/i', $xml)) 
			return "no feed";
		else
		{		
			if ($xml != "")
			{
				$xmlObj = @simplexml_load_string($xml);
				$arrXml = objectsIntoArray($xmlObj);
	
				if (isset($arrXml["item"]) && is_array($arrXml["item"]) && is_array($arrXml["item"]["@attributes"]))
				{
					echo "found title for reqid ".$reqid." - ".$arrXml["item"]["@attributes"]["title"]."\n";
						
					return $arrXml["item"]["@attributes"]["title"];
				}
			}
		}

		echo "no title found for reqid ".$reqid."\n";

		return "";		
	}

	public function checkRegexesUptoDate($url, $rev, $nnid)
	{
		if ($url != "")
		{
			if ($nnid != "")
				$nnid = "?newznabID=".$nnid;
				
			$regfile = getUrl($url.$nnid);
			if ($regfile !== false && $regfile != "")
			{
				/*$Rev: 728 $*/
				if (preg_match('/\/\*\$Rev: (\d{3,4})/i', $regfile, $matches))
				{ 
					$serverrev = intval($matches[1]);
					if ($serverrev > $rev)
					{
						$db = new DB();
						$site = new Sites;
						
						$queries = explode(";", $regfile);
						$queries = array_map("trim", $queries);
						foreach($queries as $q) 
							$db->query($q);

						$site->updateLatestRegexRevision($serverrev);
						echo "updated regexes to revision ".$serverrev."\n";
					}
					else
					{
						echo "using latest regex revision ".$rev."\n";
					}
				}
				else
				{
					echo "Error Processing Regex File\n";
				}
			}
			else
			{
				echo "Error Regex File Does Not Exist or Unable to Connect\n";
			}
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

}
?>
