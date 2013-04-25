<?php
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/page.php");
require_once(WWW_DIR."/lib/binaries.php");
require_once(WWW_DIR."/lib/users.php");
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
require_once(WWW_DIR."/lib/groups.php");

class Releases
{
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
		$nzb = new NZB();
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
			$rel = $this->getById($identifier);
			$nzbpath = $nzb->getNZBPath($rel["guid"]);
			
			if ($rel && file_exists($nzbpath))
			{
				unlink($nzbpath);
			}
			
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
	
	public function searchadv($searchname, $usenetname, $postername, $groupname, $cat, $sizefrom, $sizeto, $hasnfo, $hascomments, $daysnew, $daysold, $offset=0, $limit=1000, $orderby='', $excludedcats=array())
	{
		$db = new DB();
		$groups = new Groups();
		
		if ($cat == "-1"){$catsrch .= ("");}
		else{$catsrch .= sprintf(" and (releases.categoryID = %d) ", $cat);}
		
		//
		// if the query starts with a ^ it indicates the search is looking for items which start with the term
		// still do the fulltext match, but mandate that all items returned must start with the provided word
		//
		if ($searchname == "-1"){$searchnamesql.= ("");}
		else
		{
			$words = explode(" ", $searchname);
			$searchnamesql = "";
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
							$searchnamesql.= sprintf(" and releases.searchname like %s", $db->escapeString(substr($word, 1)."%"));
						elseif (substr($word, 0, 2) == '--')
							$searcnamehsql.= sprintf(" and releases.searchname not like %s", $db->escapeString("%".substr($word, 2)."%"));
						else
							$searchnamesql.= sprintf(" and releases.searchname like %s", $db->escapeString("%".$word."%"));

						$intwordcount++;
					}
				}
			}
		}
		
		if ($usenetname == "-1"){$usenetnamesql.= ("");}
		else
		{
			$words = explode(" ", $usenetname);
			$usenetnamesql = "";
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
							$usenetnamesql.= sprintf(" and releases.name like %s", $db->escapeString(substr($word, 1)."%"));
						elseif (substr($word, 0, 2) == '--')
							$usenetnamesql.= sprintf(" and releases.name not like %s", $db->escapeString("%".substr($word, 2)."%"));
						else
							$usenetnamesql.= sprintf(" and releases.name like %s", $db->escapeString("%".$word."%"));

						$intwordcount++;
					}
				}
			}
		}
		
		if ($postername == "-1"){$posternamesql.= ("");}
		else
		{
			$words = explode(" ", $postername);
			$posternamesql = "";
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
							$posternamesql.= sprintf(" and releases.fromname like %s", $db->escapeString(substr($word, 1)."%"));
						elseif (substr($word, 0, 2) == '--')
							$posternamesql.= sprintf(" and releases.fromname not like %s", $db->escapeString("%".substr($word, 2)."%"));
						else
							$posternamesql.= sprintf(" and releases.fromname like %s", $db->escapeString("%".$word."%"));

						$intwordcount++;
					}
				}
			}
		}
		
		if ($groupname == "-1"){$groupIDsql.= ("");}
		else
		{
			$groupID = $groups->getIDByName($db->escapeString($groupname));
			$groupIDsql.= sprintf(" and releases.groupID = %d ", $groupID);
		}
		
		if ($sizefrom == "-1"){$sizefromsql.= ("");}
		if ($sizefrom == "1"){$sizefromsql.= (" and releases.size > 104857600 ");}
		if ($sizefrom == "2"){$sizefromsql.= (" and releases.size > 262144000 ");}
		if ($sizefrom == "3"){$sizefromsql.= (" and releases.size > 524288000 ");}
		if ($sizefrom == "4"){$sizefromsql.= (" and releases.size > 1073741824 ");}
		if ($sizefrom == "5"){$sizefromsql.= (" and releases.size > 2147483648 ");}
		if ($sizefrom == "6"){$sizefromsql.= (" and releases.size > 3221225472 ");}
		if ($sizefrom == "7"){$sizefromsql.= (" and releases.size > 4294967296 ");}
		if ($sizefrom == "8"){$sizefromsql.= (" and releases.size > 8589934592 ");}
		if ($sizefrom == "9"){$sizefromsql.= (" and releases.size > 17179869184 ");}
		if ($sizefrom == "10"){$sizefromsql.= (" and releases.size > 34359738368 ");}
		if ($sizefrom == "11"){$sizefromsql.= (" and releases.size > 68719476736 ");}
		
		if ($sizeto == "-1"){$sizetosql.= ("");}
		if ($sizeto == "1"){$sizetosql.= (" and releases.size < 104857600 ");}
		if ($sizeto == "2"){$sizetosql.= (" and releases.size < 262144000 ");}
		if ($sizeto == "3"){$sizetosql.= (" and releases.size < 524288000 ");}
		if ($sizeto == "4"){$sizetosql.= (" and releases.size < 1073741824 ");}
		if ($sizeto == "5"){$sizetosql.= (" and releases.size < 2147483648 ");}
		if ($sizeto == "6"){$sizetosql.= (" and releases.size < 3221225472 ");}
		if ($sizeto == "7"){$sizetosql.= (" and releases.size < 4294967296 ");}
		if ($sizeto == "8"){$sizetosql.= (" and releases.size < 8589934592 ");}
		if ($sizeto == "9"){$sizetosql.= (" and releases.size < 17179869184 ");}
		if ($sizeto == "10"){$sizetosql.= (" and releases.size < 34359738368 ");}
		if ($sizeto == "11"){$sizetosql.= (" and releases.size < 68719476736 ");}
		
		if ($hasnfo == "0"){$hasnfosql.= ("");}
		else{$hasnfosql.= (" and releases.nfostatus = 1 ");}
		
		if ($hascomments == "0"){$hascommentssql.= ("");}
		else{$hascommentssql.= (" and releases.comments > 0 ");}
		
		if ($daysnew == "-1"){$daysnewsql.= ("");}
		else{$daysnewsql.= sprintf(" and releases.postdate < now() - interval %d day ", $daysnew);}
		
		if ($daysold == "-1"){$daysoldsql.= ("");}
		else{$daysoldsql.= sprintf(" and releases.postdate > now() - interval %d day ", $daysold);}
		
		$exccatlist = "";
		if (count($excludedcats) > 0){$exccatlist = " and releases.categoryID not in (".implode(",", $excludedcats).")";}

		if ($orderby == "")
		{
			$order[0] = " postdate ";
			$order[1] = " desc ";
		}	
		else{$order = $this->getBrowseOrder($orderby);}

		$sql = sprintf("select releases.*, concat(cp.title, ' > ', c.title) as category_name, concat(cp.ID, ',', c.ID) as category_ids, groups.name as group_name, rn.ID as nfoID, re.releaseID as reID, cp.ID as categoryParentID from releases left outer join releasevideo re on re.releaseID = releases.ID left outer join releasenfo rn on rn.releaseID = releases.ID left outer join groups on groups.ID = releases.groupID left outer join category c on c.ID = releases.categoryID left outer join category cp on cp.ID = c.parentID where releases.passwordstatus <= (select value from site where setting='showpasswordedrelease') %s %s %s %s %s %s %s %s %s %s %s %s order by %s %s limit %d, %d ", $searchnamesql, $usenetnamesql, $posternamesql, $groupIDsql, $sizefromsql, $sizetosql, $hasnfosql, $hascommentssql, $catsrch, $daysnewsql, $daysoldsql, $exccatlist, $order[0], $order[1], $offset, $limit);
		$orderpos = strpos($sql, "order by");
		$wherepos = strpos($sql, "where");
		$sqlcount = "select count(releases.ID) as num from releases ".substr($sql, $wherepos,$orderpos-$wherepos);

		$countres = $db->queryOneRow($sqlcount);
		$res = $db->query($sql);
		if (count($res) > 0){$res[0]["_totalrows"] = $countres["num"];}
		
		return $res;
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
	
	public function searchsubject($search, $cat=array(-1), $offset=0, $limit=1000, $orderby='', $maxage=-1, $excludedcats=array())
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
						$searchsql.= sprintf(" and releases.name like %s", $db->escapeString(substr($word, 1)."%"));
					elseif (substr($word, 0, 2) == '--')
						$searchsql.= sprintf(" and releases.name not like %s", $db->escapeString("%".substr($word, 2)."%"));
					else
						$searchsql.= sprintf(" and releases.name like %s", $db->escapeString("%".$word."%"));

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
	
	
	public function processReleasesStage1($groupID)
	{
		$db = new DB();
		$n = "\n";
		
		echo "\033[1;33mStage 1 -> Try to find complete collections.\033[0m".$n;
		$stage1 = TIME();
		$where = (!empty($groupID)) ? " AND groupID = " . $groupID : "";
			
		//Look if we have all the files in a collection (which have the file count in the subject).
		$db->query("UPDATE collections 
					SET filecheck = 1 
					WHERE ID IN 
					(
						SELECT ID 
						FROM 
						(
							SELECT c.ID 
							FROM collections c LEFT JOIN binaries b ON b.collectionID = c.ID 
							WHERE c.totalFiles > 0 AND c.filecheck = 0 " . $where . "
							GROUP BY c.ID, c.totalFiles
							HAVING count(b.ID) >= c.totalFiles
						) as tmpTable
					)");
		
		//Check if we have all parts for a file. Set partcheck to 1.
		$db->query("UPDATE collections 
					SET filecheck = 2 
					WHERE ID IN 
					(
						SELECT ID 
						FROM 
						(
							SELECT c.ID
							FROM collections c LEFT JOIN binaries b ON b.collectionID = c.ID LEFT JOIN parts p ON p.binaryID = b.ID
							WHERE c.filecheck = 1 AND b.partcheck = 0 " . $where . "
							GROUP BY c.ID, c.totalFiles, b.ID, b.totalParts
							HAVING count(p.ID) >= b.totalParts
						) as tmpTable
					)");		
		
		//If a collection has not been updated in 2 hours, set filecheck to 2.
		$db->query("UPDATE collections c
					SET filecheck = 2, totalFiles = (SELECT COUNT(b.ID) FROM binaries b WHERE b.collectionID = c.ID)
					WHERE c.dateadded < (now() - interval 2 hour) AND c.filecheck != 2 " . $where);
	
        echo TIME() - $stage1." second(s).";
	}
	
	public function processReleasesStage2($groupID)
	{
		$db = new DB;
		$n = "\n";
		$where = (!empty($groupID)) ? " AND groupID = " . $groupID : "";
		
		//Get part and file size.
		echo $n."\033[1;33mStage 2 -> Get part and file sizes.\033[0m".$n;
		$stage2 = TIME();
		$db->query("UPDATE collections c
					SET filesize = (SELECT SUM(size) FROM parts p LEFT JOIN binaries b ON p.binaryID = b.ID WHERE b.collectionID = c.ID)
					WHERE c.filecheck = 2 AND c.filesize = 0 " . $where);

        echo TIME() - $stage2." second(s).";
	}
	
	public function processReleasesStage3($groupID)
	{
		$db = new DB;
		$n = "\n";
		$minsizecounts = 0;
		$maxsizecounts= 0;
		$minfilecounts = 0;
		
		echo $n."\033[1;33mStage 3 -> Delete collections smaller/larger than minimum size/file count from group/site setting.\033[0m".$n;
		$stage3 = TIME();
		
		if ($groupID == "")
		{
			$groups = new Groups();
			$groupIDs = $groups->getActiveIDs();
			
			foreach ($groupIDs as $groupID)
			{
				if($db->queryDirect("SELECT ID from collections where filecheck = 2 and filesize > 0"))
				{
					$db->query("UPDATE collections c 
								LEFT JOIN (
											SELECT g.ID, coalesce(g.minsizetoformrelease, s.minsizetoformrelease) as minsizetoformrelease 
											FROM groups g INNER JOIN ( SELECT value as minsizetoformrelease FROM site WHERE setting = 'minsizetoformrelease' ) s 
										) g ON g.ID = c.groupID
								SET c.filecheck = 4
								WHERE g.minsizetoformrelease != 0 AND c.filecheck = 2 AND c.filesize < g.minsizetoformrelease AND groupID = ".$groupID["ID"]);
						
					$minsizecount = $db->getAffectedRows();
					if ($minsizecount < 0)
						$minsizecount = 0;
					$minsizecounts = $minsizecount+$minsizecounts;

					$maxfilesizeres = $db->queryOneRow("select value from site where setting = maxsizetoformrelease");
					if ($maxfilesizeres["value"] != 0)
					{
						$db->query(sprintf("UPDATE collections SET filecheck = 4 WHERE filecheck = 2 AND groupID = %d AND filesize > %d ", $groupID["ID"], $maxfilesizeres["value"]));
				
						$maxsizecount = $db->getAffectedRows();
						if ($maxsizecount < 0)
							$maxsizecount = 0;
						$maxsizecounts = $maxsizecount+$maxsizecounts;
					}

					$db->query("UPDATE collections c 
								LEFT JOIN (
											SELECT g.ID, coalesce(g.minfilestoformrelease, s.minfilestoformrelease) as minfilestoformrelease 
											FROM groups g INNER JOIN ( SELECT value as minfilestoformrelease FROM site WHERE setting = 'minfilestoformrelease' ) s 
										) g ON g.ID = c.groupID
								SET c.filecheck = 4
								WHERE g.minfilestoformrelease != 0 AND c.filecheck = 2 AND c.totalFiles < g.minfilestoformrelease AND groupID = ".$groupID["ID"]);

					$minfilecount = $db->getAffectedRows();
					if ($minfilecount < 0)
						$minfilecount = 0;
					$minfilecounts = $minfilecount+$minfilecounts;
				}
			}
		}
        else
        {
			if($db->queryDirect("SELECT ID from collections where filecheck = 2 and filesize > 0"))
			{
				$db->query("UPDATE collections c 
							LEFT JOIN (
										SELECT g.ID, coalesce(g.minsizetoformrelease, s.minsizetoformrelease) as minsizetoformrelease 
										FROM groups g INNER JOIN ( SELECT value as minsizetoformrelease FROM site WHERE setting = 'minsizetoformrelease' ) s 
									) g ON g.ID = c.groupID
							SET c.filecheck = 4
							WHERE g.minsizetoformrelease != 0 AND c.filecheck = 2 AND c.filesize < g.minsizetoformrelease AND groupID = ".$groupID);
						
				$minsizecount = $db->getAffectedRows();
				if ($minsizecount < 0)
					$minsizecount = 0;
				$minsizecounts = $minsizecount+$minsizecounts;

				$maxfilesizeres = $db->queryOneRow("select value from site where setting = maxsizetoformrelease");
				if ($maxfilesizeres["value"] != 0)
				{
					$db->query(sprintf("UPDATE collections SET filecheck = 4 WHERE filecheck = 2 AND filesize > %d " . $where, $maxfilesizeres["value"]));
				
					$maxsizecount = $db->getAffectedRows();
					if ($maxsizecount < 0)
						$maxsizecount = 0;
					$maxsizecounts = $maxsizecount+$maxsizecounts;
				}

				$db->query("UPDATE collections c 
							LEFT JOIN (
										SELECT g.ID, coalesce(g.minfilestoformrelease, s.minfilestoformrelease) as minfilestoformrelease 
										FROM groups g INNER JOIN ( SELECT value as minfilestoformrelease FROM site WHERE setting = 'minfilestoformrelease' ) s 
									) g ON g.ID = c.groupID
							SET c.filecheck = 4
							WHERE g.minfilestoformrelease != 0 AND c.filecheck = 2 AND c.totalFiles < g.minfilestoformrelease AND groupID = ".$groupID);

				$minfilecount = $db->getAffectedRows();
				if ($minfilecount < 0)
					$minfilecount = 0;
				$minfilecounts = $minfilecount+$minfilecounts;
			}
		}
		
		$delcount = $minsizecounts+$maxsizecounts+$minfilecounts;
		if ($delcount > 0)
				echo "...Deleted ".$delcount." collections smaller/larger than group/site settings.".$n;
		echo TIME() - $stage3." second(s).";
	}
	
	public function processReleasesStage4($groupID)
	{
		$db = new DB;
		$page = new Page();
		$n = "\n";
		$retcount = 0;
		$where = (!empty($groupID)) ? " AND groupID = " . $groupID : "";

		// Create releases.
		echo $n."\033[1;33mStage 4 -> Create releases.\033[0m".$n;
		$stage4 = TIME();
		if($rescol = $db->queryDirect("SELECT * FROM collections WHERE filecheck = 2 AND filesize > 0 " . $where . " LIMIT 1000"))
		{
			while ($rowcol = $db->fetchAssoc($rescol))
			{
				$cleanArr = array('#', '@', '$', '%', '^', '§', '¨', '©', 'Ö');
				$cleanSearchName = str_replace($cleanArr, '', $rowcol['name']);
				$cleanRelName = str_replace($cleanArr, '', $rowcol['subject']);
				$relguid = md5(uniqid());
				if($db->queryInsert(sprintf("INSERT INTO releases (name, searchname, totalpart, groupID, adddate, guid, rageID, postdate, fromname, size, passwordstatus, categoryID, nfostatus) 
											VALUES (%s, %s, %d, %d, now(), %s, -1, %s, %s, %s, %d, 7010, -1)", 
											$db->escapeString($cleanRelName), $db->escapeString($cleanSearchName), $rowcol["totalFiles"], $rowcol["groupID"], $db->escapeString($relguid), 
											$db->escapeString($rowcol["date"]), $db->escapeString($rowcol["fromname"]), $db->escapeString($rowcol["filesize"]), ($page->site->checkpasswordedrar == "1" ? -1 : 0))))
				{
					$relid = $db->getInsertID();
					// Update collections table to say we inserted the release.
					$db->queryDirect(sprintf("UPDATE collections SET filecheck = 3, releaseID = %d WHERE ID = %d", $relid, $rowcol['ID']));
					$retcount ++;
					echo "Added release ".$cleanRelName.$n;
				}
				else
				{
					echo "\033[01;31mError Inserting Release: \033[0m" . $cleanRelName . ": " . $db->Error() . $n;
				}
			}
		}
		
		$timing = TIME() - $stage4;
        echo $retcount . " Releases added in " . $timing . " second(s).";
        return $retcount;
	}
	
	public function processReleasesStage4_loop($groupID)
	{
		$tot_retcount = 0;
		do
		{
			$retcount = $this->processReleasesStage4($groupID);
			$tot_retcount = $tot_retcount + $retcount;
		} while ($retcount > 0);
		
		return $tot_retcount;
	}
	
	/*
	 *	Adding this in to delete releases before NZB's are created. 
	 */
	public function processReleasesStage4dot5($groupID)
	{
		$db = new DB;
		$n = "\n";
		$minsizecount = 0;
		$maxsizecount = 0;
		$minfilecount = 0;
		
		echo $n."\033[1;33mStage 4.5 -> Delete releases smaller/larger than minimum size/file count from group/site setting.\033[0m".$n;
		$stage4dot5 = TIME();
		
		if ($groupID == "")
		{
			$groups = new Groups();
			$groupIDs = $groups->getActiveIDs();
			
			foreach ($groupIDs as $groupID)
			{
				if ($resrel = $db->query("SELECT r.ID FROM releases r LEFT JOIN 
							(SELECT g.ID, coalesce(g.minsizetoformrelease, s.minsizetoformrelease) 
							as minsizetoformrelease FROM groups g INNER JOIN ( SELECT value as minsizetoformrelease 
							FROM site WHERE setting = 'minsizetoformrelease' ) s ) g ON g.ID = r.groupID WHERE 
							g.minsizetoformrelease != 0 AND r.size < minsizetoformrelease AND groupID = ".$groupID["ID"]))
				{
					foreach ($resrel as $rowrel)
					{
						$this->delete($rowrel['ID']);
						$minsizecount ++;
					}
				}

				$maxfilesizeres = $db->queryOneRow("SELECT value FROM site WHERE setting = maxsizetoformrelease");
				if ($maxfilesizeres["value"] != 0)
				{
					if ($resrel = $db->query(sprintf("SELECT ID from releases where groupID = %d AND filesize > %d ", $groupID["ID"], $maxfilesizeres["value"])))
					{
						foreach ($resrel as $rowrel)
						{
							$this->delete($rowrel['ID']);
							$maxsizecount ++;
						}
					}
				}

				if ($resrel = $db->query("SELECT r.ID FROM releases r LEFT JOIN 
							(SELECT g.ID, coalesce(g.minfilestoformrelease, s.minfilestoformrelease) 
							as minfilestoformrelease FROM groups g INNER JOIN ( SELECT value as minfilestoformrelease 
							FROM site WHERE setting = 'minfilestoformrelease' ) s ) g ON g.ID = r.groupID WHERE 
							g.minfilestoformrelease != 0 AND r.totalpart < minfilestoformrelease AND groupID = ".$groupID["ID"]))
				{
					foreach ($resrel as $rowrel)
					{
						$this->delete($rowrel['ID']);
						$minfilecount ++;
					}
				}
			}
		}
        else
        {
			if ($resrel = $db->query("SELECT r.ID FROM releases r LEFT JOIN 
						(SELECT g.ID, coalesce(g.minsizetoformrelease, s.minsizetoformrelease) 
						as minsizetoformrelease FROM groups g INNER JOIN ( SELECT value as minsizetoformrelease 
						FROM site WHERE setting = 'minsizetoformrelease' ) s ) g ON g.ID = r.groupID WHERE 
						g.minsizetoformrelease != 0 AND r.size < minsizetoformrelease AND groupID = ".$groupID))
			{
				foreach ($resrel as $rowrel)
				{
					$this->delete($rowrel['ID']);
					$minsizecount ++;
				}
			}

			$maxfilesizeres = $db->queryOneRow("SELECT value FROM site WHERE setting = maxsizetoformrelease");
			if ($maxfilesizeres["value"] != 0)
			{
				if ($resrel = $db->query(sprintf("SELECT ID from releases where groupID = %d AND filesize > %d ", $groupID, $maxfilesizeres["value"])))
				{
					foreach ($resrel as $rowrel)
					{
						$this->delete($rowrel['ID']);
						$maxsizecount ++;
					}
				}
			}

			if ($resrel = $db->query("SELECT r.ID FROM releases r LEFT JOIN 
						(SELECT g.ID, coalesce(g.minfilestoformrelease, s.minfilestoformrelease) 
						as minfilestoformrelease FROM groups g INNER JOIN ( SELECT value as minfilestoformrelease 
						FROM site WHERE setting = 'minfilestoformrelease' ) s ) g ON g.ID = r.groupID WHERE 
						g.minfilestoformrelease != 0 AND r.totalpart < minfilestoformrelease AND groupID = ".$groupID))
			{
				foreach ($resrel as $rowrel)
				{
					$this->delete($rowrel['ID']);
					$minfilecount ++;
				}
			}
		}
		
		$delcount = $minsizecount+$maxsizecount+$minfilecount;
		if ($delcount > 0)
				echo "...Deleted ".$minsizecount+$maxsizecount+$minfilecount." releases smaller/larger than group/site settings.".$n;
		echo TIME() - $stage4dot5." second(s).";
	}
	
	public function processReleasesStage5($groupID)
	{
		$db = new DB;
		$nzb = new Nzb;
		$page = new Page;
		$n = "\n";
		$nzbcount = 0;
		$where = (!empty($groupID)) ? " AND groupID = " . $groupID : "";
		
		// Create NZB.
		echo $n."\033[1;33mStage 5 -> Create the NZB, mark collections as ready for deletion.\033[0m".$n;
		$stage5 = TIME();
		$start_nzbcount = $nzbcount;
		if($resrel = $db->queryDirect("SELECT ID, guid, name, categoryID FROM releases WHERE nzbstatus = 0 " . $where . " LIMIT 1000"))
		{
			while ($rowrel = $db->fetchAssoc($resrel))
			{
				$nzb->writeNZBforReleaseId($rowrel['ID'], $rowrel['guid'], $rowrel['name'], $rowrel['categoryID'], $nzb->getNZBPath($rowrel['guid'], $page->site->nzbpath, true));
				$db->queryDirect(sprintf("UPDATE releases SET nzbstatus = 1 WHERE ID = %d", $rowrel['ID']));
				$db->queryDirect(sprintf("UPDATE collections SET filecheck = 4 WHERE releaseID = %s", $rowrel['ID']));
				/*$db->queryDirect(sprintf("DELETE collections, binaries, parts
											FROM collections LEFT JOIN binaries ON collections.ID = binaries.collectionID LEFT JOIN parts on binaries.ID = parts.binaryID
											WHERE (collections.releaseID = %d)", $rowrel['ID']));*/
				echo ".";
				$nzbcount++;
				if ($nzbcount % 100 == 0)
					echo $n;
			}
		}
		
		$timing = TIME() - $stage5;
		echo $n . $nzbcount . " NZBs created in " . $timing ." second(s). ";
		return $nzbcount;
	}
	
	public function processReleasesStage5_loop($groupID)
	{
		$tot_nzbcount = 0;
		do
		{
			$nzbcount = $this->processReleasesStage5($groupID);
			$tot_nzbcount = $tot_nzbcount + $nzbcount;
		} while ($nzbcount > 0);
		
		return $tot_nzbcount;
	}
	
	public function processReleasesStage6($categorize, $postproc, $groupID)
	{
		$db = new DB;
		$cat = new Category;
		$n = "\n";
		$where = (!empty($groupID)) ? " AND groupID = " . $groupID : "";
		
		// Categorize releases.
		echo $n."\033[1;33mStage 6 -> Categorize and post process releases.\033[0m".$n;
		$stage6 = TIME();
		if ($categorize == 1)
		{
			$resrel = $db->queryDirect("SELECT ID, name, groupID FROM releases WHERE relnamestatus = 0 " . $where);
			while ($rowrel = $db->fetchAssoc($resrel))
			{
				$catId = $cat->determineCategory($rowrel["name"], $rowrel['groupID']);
				$db->queryDirect(sprintf("UPDATE releases SET categoryID = %d, relnamestatus = 1 WHERE ID = %d", $catId, $rowrel['ID']));
			}
		}
		if ($postproc == 1)
		{
			$postprocess = new PostProcess(true);
			$postprocess->processAll();
		}
		else
		{
			echo "Post-processing disabled.".$n;
		}
		echo TIME() - $stage6." second(s).";
	}
	
	public function processReleasesStage7($groupID)
	{
		$db = new DB;
		$page = new Page;
		$n = "\n";
		$remcount = 0;
		$passcount = 0;
		$dupecount = 0;
		$relsizecount = 0;
		$where = (!empty($groupID)) ? " AND collections.groupID = " . $groupID : "";
		
		// Delete old releases and finished collections.
		echo $n."\033[1;33mStage 7 -> Delete old releases, finished collections and passworded releases.\033[0m".$n;
		$stage7 = TIME();
		// Old collections that were missed somehow.
	
		$db->queryDirect("DELETE collections, binaries, parts
						  FROM collections LEFT JOIN binaries ON collections.ID = binaries.collectionID LEFT JOIN parts on binaries.ID = parts.binaryID
						  WHERE (collections.filecheck = 4 OR (collections.dateadded < (now() - interval 72 hour))) " . $where);
		$reccount = $db->getAffectedRows();
		
		$where = (!empty($groupID)) ? " AND groupID = " . $groupID : "";
		//Releases past retention.
		if($page->site->releaseretentiondays != 0)
		{
			$result = $db->query(sprintf("SELECT ID FROM releases WHERE postdate < now() - interval %d day " . $where, $page->site->releaseretentiondays)); 		
			foreach ($result as $row)
				$this->delete($row["ID"]);
				$remcount ++;
		}
		
		//Passworded releases.
		if($page->site->deletepasswordedrelease == 1)
		{
			echo "Determining any passworded releases to be deleted".$n;
			$result = $db->query("SELECT ID FROM releases WHERE passwordstatus > 0 " . $where); 		
			foreach ($result as $row)
			{
				$this->delete($row["ID"]);
				$passcount ++;
			}
		}
		
		//Crossposted releases.
		if($resrel = $db->query("SELECT ID FROM releases WHERE adddate > (now() - interval 2 hour) " . $where . " GROUP BY name HAVING count(name) > 1"))
		{
			foreach ($resrel as $rowrel)
			{
				$this->delete($rowrel['ID']);
				$dupecount ++;
			}
		}
		
		echo "Removed: ".$remcount." releases past retention, ".$passcount." passworded releases, ".$dupecount." crossposted releases, ".$reccount." records from parts/binaries/collections.".$n;
		
		echo TIME() - $stage7." second(s).".$n;        
	}

	public function processReleases($categorize, $postproc, $groupName)
	{
		$db = new DB();
		$groups = new Groups();
		$page = new Page();
		$n = "\n";
		$groupID = "";
		
		if (!empty($groupName))
		{
			$groupInfo = $groups->getByName($groupName);
			$groupID = $groupInfo['ID'];		
		}
		
		$this->processReleases = microtime(true);
		echo $n."Starting release update process (".date("Y-m-d H:i:s").")".$n;
		
		if (!file_exists($page->site->nzbpath))
		{
			echo "Bad or missing nzb directory - ".$page->site->nzbpath;
			return;
		}
		
		$this->processReleasesStage1($groupID);
		
		$this->processReleasesStage2($groupID);
		
		$this->processReleasesStage3($groupID);
		
		$releasesAdded = $this->processReleasesStage4_loop($groupID);
		
		$this->processReleasesStage4dot5($groupID);
		
		$this->processReleasesStage5_loop($groupID);
		
		$this->processReleasesStage6($categorize, $postproc, $groupID);
		
		$deletedCount = $this->processReleasesStage7($groupID);
		
		//Print amount of added releases and time it took.
		$timeUpdate = number_format(microtime(true) - $this->processReleases, 2);
		$where = (!empty($groupID)) ? " WHERE groupID = " . $groupID : "";

		$cremain = $db->queryOneRow("select count(ID) from collections " . $where);
		echo $n."Completed adding ".$releasesAdded." releases in ".$timeUpdate." second(s). ".array_shift($cremain)." collections waiting to be created (still incomplete or in queue for creation).".$n;
		return $releasesAdded;
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
