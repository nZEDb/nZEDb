<?php
require_once(WWW_DIR."/lib/framework/db.php");

class ReleaseRegex
{	

	public function get($activeonly=true, $groupname="-1", $blnIncludeReleaseCount=false, $userReleaseRegex=false)
	{			
		$db = new DB();
		
		$where = "";
		if ($activeonly)
			$where.= " and releaseregex.status = 1";
		
		if ($groupname=="all")
			$where.= " and releaseregex.groupname is null";
		elseif ($groupname!="-1")
			$where.= sprintf(" and releaseregex.groupname = %s", $db->escapeString($groupname));
      
		if ($userReleaseRegex)
		{
		  $where .= ' AND releaseregex.ID >= 100000';
		}

		$relcountjoin="";
		$relcountcol="";
		if ($blnIncludeReleaseCount)
		{
			$relcountcol = " , coalesce(x.count, 0) as num_releases, coalesce(x.adddate, 'n/a') as max_releasedate ";
			$relcountjoin = " left outer join (  select regexID, max(adddate) adddate, count(ID) as count from releases group by regexID) x on x.regexID = releaseregex.ID ";
		}
		
		return $db->query("SELECT releaseregex.ID, releaseregex.categoryID, category.title as categoryTitle, releaseregex.status, releaseregex.description, releaseregex.groupname AS groupname, releaseregex.regex, 
												groups.ID AS groupID, releaseregex.ordinal ".$relcountcol."
												FROM releaseregex 
												left outer JOIN groups ON groups.name = releaseregex.groupname 
												left outer join category on category.ID = releaseregex.categoryID
												".$relcountjoin."
												where 1=1 ".$where."
												ORDER BY groupname LIKE '%*' ASC, coalesce(groupname,'zzz') DESC, ordinal ASC");		
	}

	public function getGroupsForSelect()
	{
		
		$db = new DB();
		$categories = $db->query("SELECT distinct coalesce(groupname,'all') as groupname from releaseregex order by groupname ");
		$temp_array = array();
		
		$temp_array[-1] = "--Please Select--";
		
		foreach($categories as $category)
			$temp_array[$category["groupname"]] = $category["groupname"];

		return $temp_array;
	}


	public function getByID($id)
	{			
		$db = new DB();
		return $db->queryOneRow(sprintf("select * from releaseregex where ID = %d ", $id));		
	}

	public function delete($id)
	{			
		$db = new DB();
		return $db->query(sprintf("delete from releaseregex where ID = %d", $id));		
	}		
	
	public function update($regex)
	{			
		$db = new DB();
		
		$groupname = $regex["groupname"];
		if ($groupname == "")
			$groupname = "null";
		else
			$groupname = sprintf("%s", $db->escapeString($regex["groupname"]));
			
		$catid = $regex["category"];
		if ($catid == "-1")
			$catid = "null";
		else
			$catid = sprintf("%d", $regex["category"]);

		$db->query(sprintf("update releaseregex set groupname=%s, regex=%s, ordinal=%d, status=%d, description=%s, categoryID=%s where ID = %d ", 
			$groupname, $db->escapeString($regex["regex"]), $regex["ordinal"], $regex["status"], $db->escapeString($regex["description"]), $catid, $regex["id"]));	
	}
	
	public function add($regex)
	{			
		$db = new DB();
		
		$groupname = $regex["groupname"];
		if ($groupname == "")
			$groupname = "null";
		else
			$groupname = sprintf("%s", $db->escapeString($regex["groupname"]));

		$catid = $regex["category"];
		if ($catid == "-1")
			$catid = "null";
		else
			$catid = sprintf("%d", $regex["category"]);
			
		return $db->queryInsert(sprintf("insert into releaseregex (groupname, regex, ordinal, status, description, categoryID) values (%s, %s, %d, %d, %s, %s) ", 
			$groupname, $db->escapeString($regex["regex"]), $regex["ordinal"], $regex["status"], $db->escapeString($regex["description"]), $catid));	
		
	}	
}
?>
