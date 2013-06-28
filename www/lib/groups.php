<?php
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/category.php");
require_once(WWW_DIR."/lib/nntp.php");
require_once(WWW_DIR."/lib/site.php");
require_once(WWW_DIR."/lib/releases.php");
require_once(WWW_DIR."/lib/binaries.php");

class Groups
{	
	public function getAll()
	{			
		$db = new DB();
		
		return $db->query("SELECT groups.*, COALESCE(rel.num, 0) AS num_releases
							FROM groups
							LEFT OUTER JOIN
							(
							SELECT groupID, COUNT(ID) AS num FROM releases group by groupID
							) rel ON rel.groupID = groups.ID ORDER BY groups.name");
	}	
	
	public function getGroupsForSelect()
	{
		$db = new DB();
		$categories = $db->query("SELECT * FROM groups WHERE active = 1 ORDER BY name");
		$temp_array = array();
		
		$temp_array[-1] = "--Please Select--";
		
		foreach($categories as $category)
			$temp_array[$category["name"]] = $category["name"];

		return $temp_array;
	}	
	
	public function getByID($id)
	{			
		$db = new DB();
		return $db->queryOneRow(sprintf("select * from groups where ID = %d ", $id));		
	}	
	
	public function getActive()
	{			
		$db = new DB();
		return $db->query("SELECT * FROM groups WHERE active = 1 ORDER BY name");		
	}
	
	public function getActiveBackfill()
	{			
		$db = new DB();
		return $db->query("SELECT * FROM groups WHERE backfill = 1 ORDER BY name");		
	}
	
	public function getActiveByDateBackfill()
	{
		$db = new DB();
		return $db->query("SELECT * FROM groups WHERE backfill = 1 ORDER BY first_record_postdate DESC");
	}

	public function getActiveIDs()
	{			
		$db = new DB();
		return $db->query("SELECT ID FROM groups WHERE active = 1 ORDER BY name");
	}
	
	public function getByName($grp)
	{			
		$db = new DB();
		return $db->queryOneRow(sprintf("select * from groups where name = '%s' ", $grp));
	}
	
	public function getByNameByID($id)
	{			
		$db = new DB();
		$res = $db->queryOneRow(sprintf("select name from groups where ID = %d ", $id));
		return $res["name"];	
	}
	
	public function getIDByName($name)
	{		
		$db = new DB();
		$res = $db->queryOneRow(sprintf("select ID from groups where name = %s", $name));
		return $res["ID"];	
	}
	
	// Set the backfill to 0 when the group is backfilled to max.
	public function disableForPost($name)
	{
		$db = new DB();
		$db->queryOneRow(sprintf("UPDATE groups SET backfill = 0 WHERE name = %s", $db->escapeString($name)));
	}

	public function getCount($groupname="")
	{			
		$db = new DB();
		
		$grpsql = '';
		if ($groupname != "")
			$grpsql .= sprintf("and groups.name like %s ", $db->escapeString("%".$groupname."%"));
		
		$res = $db->queryOneRow(sprintf("select count(ID) as num from groups where 1=1 %s", $grpsql));
		return $res["num"];
	}
	
	public function getCountActive($groupname="")
	{			
		$db = new DB();
		
		$grpsql = '';
		if ($groupname != "")
			$grpsql .= sprintf("and groups.name like %s ", $db->escapeString("%".$groupname."%"));
		
		$res = $db->queryOneRow(sprintf("select count(ID) as num from groups where 1=1 %s and active = 1", $grpsql));		
		return $res["num"];
	}
	
	public function getCountInactive($groupname="")
	{			
		$db = new DB();
		
		$grpsql = '';
		if ($groupname != "")
			$grpsql .= sprintf("and groups.name like %s ", $db->escapeString("%".$groupname."%"));
		
		$res = $db->queryOneRow(sprintf("select count(ID) as num from groups where 1=1 %s and active = 0", $grpsql));		
		return $res["num"];
	}
	
	public function getRange($start, $num, $groupname="")
	{		
		$db = new DB();
		
		if ($start === false)
			$limit = "";
		else
			$limit = " LIMIT ".$start.",".$num;
		
		$grpsql = '';
		if ($groupname != "")
			$grpsql .= sprintf("and groups.name like %s ", $db->escapeString("%".$groupname."%"));
				
		$sql = sprintf("SELECT groups.*, COALESCE(rel.num, 0) AS num_releases
							FROM groups
							LEFT OUTER JOIN
							(
							SELECT groupID, COUNT(ID) AS num FROM releases group by groupID
							) rel ON rel.groupID = groups.ID WHERE 1=1 %s ORDER BY groups.name ".$limit, $grpsql);
		return $db->query($sql);		
	}
	
	public function getRangeActive($start, $num, $groupname="")
	{		
		$db = new DB();
		
		if ($start === false)
			$limit = "";
		else
			$limit = " LIMIT ".$start.",".$num;
		
		$grpsql = '';
		if ($groupname != "")
			$grpsql .= sprintf("and groups.name like %s ", $db->escapeString("%".$groupname."%"));
				
		$sql = sprintf("SELECT groups.*, COALESCE(rel.num, 0) AS num_releases
							FROM groups
							LEFT OUTER JOIN
							(
							SELECT groupID, COUNT(ID) AS num FROM releases group by groupID
							) rel ON rel.groupID = groups.ID WHERE 1=1 %s and active = 1 ORDER BY groups.name ".$limit, $grpsql);
		return $db->query($sql);		
	}
	
	public function getRangeInactive($start, $num, $groupname="")
	{		
		$db = new DB();
		
		if ($start === false)
			$limit = "";
		else
			$limit = " LIMIT ".$start.",".$num;
		
		$grpsql = '';
		if ($groupname != "")
			$grpsql .= sprintf("and groups.name like %s ", $db->escapeString("%".$groupname."%"));
				
		$sql = sprintf("SELECT groups.*, COALESCE(rel.num, 0) AS num_releases
							FROM groups
							LEFT OUTER JOIN
							(
							SELECT groupID, COUNT(ID) AS num FROM releases group by groupID
							) rel ON rel.groupID = groups.ID WHERE 1=1 %s and active = 0 ORDER BY groups.name ".$limit, $grpsql);
		return $db->query($sql);		
	}
	
	public function add($group)
	{			
		$db = new DB();
		
		if ($group["minfilestoformrelease"] == "" || $group["minfilestoformrelease"] == "0")
			$minfiles = 'null';
		else
			$minfiles = $group["minfilestoformrelease"] + 0;
		
		if ($group["minsizetoformrelease"] == "" || $group["minsizetoformrelease"] == "0")
			$minsizetoformrelease = 'null';
		else
			$minsizetoformrelease = $db->escapeString($group["minsizetoformrelease"]);

		$first = (isset($group["first_record"]) ? $group["first_record"] : "0");
		$last = (isset($group["last_record"]) ? $group["last_record"] : "0");
		
		$sql = sprintf("insert into groups (name, description, first_record, last_record, last_updated, active, minfilestoformrelease, minsizetoformrelease) values (%s, %s, %s, %s, null, %d, %s, %s) ",$db->escapeString($group["name"]), $db->escapeString($group["description"]), $db->escapeString($first), $db->escapeString($last), $group["active"], $minfiles, $minsizetoformrelease);		
		return $db->queryInsert($sql);
	}	
	
	public function delete($id)
	{			
		$db = new DB();
		return $db->query(sprintf("delete from groups where ID = %d", $id));		
	}	
	
	public function reset($id)
	{			
		$db = new DB();
		return $db->query(sprintf("update groups set backfill_target=0, first_record=0, first_record_postdate=null, last_record=0, last_record_postdate=null, active = 0, last_updated=null where ID = %d", $id));		
	}
	
	public function resetall()
	{			
		$db = new DB();
		return $db->query("update groups set backfill_target=0, first_record=0, first_record_postdate=null, last_record=0, last_record_postdate=null, last_updated=null, active = 0");		
	}
	
	public function purge($id)
	{	
		$db = new DB();
		$releases = new Releases();		
		$binaries = new Binaries();
		
		$this->reset($id);

		$rels = $db->query(sprintf("select ID from releases where groupID = %d", $id));
		foreach ($rels as $rel)
			$releases->delete($rel["ID"]);

		$cols = $db->query(sprintf("select ID from collections where groupID = %d", $id));
		foreach ($cols as $col)
			$binaries->delete($col["ID"]);
	}
	
	public function purgeall()
	{	
		$db = new DB();
		$releases = new Releases();		
		$binaries = new Binaries();
		
		$this->resetall();

		$rels = $db->query("select ID from releases");
		foreach ($rels as $rel)
			$releases->delete($rel["ID"]);

		$cols = $db->query("select ID from collections");
		foreach ($cols as $col)
			$binaries->delete($col["ID"]);
	}		
	
	public function update($group)
	{			
		$db = new DB();

		if ($group["minfilestoformrelease"] == "" || $group["minfilestoformrelease"] == "0")
			$minfiles = 'null';
		else
			$minfiles = $group["minfilestoformrelease"] + 0;
		
		if ($group["minsizetoformrelease"] == "" || $group["minsizetoformrelease"] == "0")
			$minsizetoformrelease = 'null';
		else
			$minsizetoformrelease = $db->escapeString($group["minsizetoformrelease"]);

		return $db->query(sprintf("update groups set name=%s, description = %s, backfill_target = %s , active=%d, backfill=%d, minfilestoformrelease=%s, minsizetoformrelease=%s where ID = %d ",$db->escapeString($group["name"]), $db->escapeString($group["description"]), $db->escapeString($group["backfill_target"]),$group["active"], $group["backfill"] , $minfiles, $minsizetoformrelease, $group["id"] ));		
	}	

	//
	// update the list of newsgroups and return an array of messages.
	//
	function addBulk($groupList, $active = 1) 
	{
		$ret = array();
	
		if ($groupList == "")
		{
			$ret[] = "No group list provided.";
		}
		else
		{
			$db = new DB();
			$nntp = new Nntp();
			$nntp->doConnect();
			$groups = $nntp->getGroups();
			$nntp->doQuit();
				
			$regfilter = "/(" . str_replace (array ('.','*'), array ('\.','.*?'), $groupList) . ")$/";

			foreach($groups AS $group) 
			{
				if (preg_match ($regfilter, $group['group']) > 0)
				{
					$res = $db->queryOneRow(sprintf("SELECT ID FROM groups WHERE name = %s ", $db->escapeString($group['group'])));
					if($res) 
					{
						
						$db->query(sprintf("UPDATE groups SET active = %d where ID = %d", $active, $res["ID"]));
						$ret[] = array ('group' => $group['group'], 'msg' => 'Updated');
					} 
					else 
					{
						$desc = "";
						$db->queryInsert(sprintf("INSERT INTO groups (name, description, active) VALUES (%s, %s, %d)", $db->escapeString($group['group']), $db->escapeString($desc), $active));
						$ret[] = array ('group' => $group['group'], 'msg' => 'Created');
					}
				}
			}
		}
		return $ret;
	}

	public function updateGroupStatus($id, $status = 0)
	{
		$db = new DB();
		$db->query(sprintf("UPDATE groups SET active = %d WHERE id = %d", $status, $id));
		$status = ($status == 0) ? 'deactivated' : 'activated';
		return "Group $id has been $status.";
	}
	
	public function updateBackfillStatus($id, $status = 0)
	{
		$db = new DB();
		$db->query(sprintf("UPDATE groups SET backfill = %d WHERE id = %d", $status, $id));
		$status = ($status == 0) ? 'deactivated' : 'activated';
		return "Group $id has been $status.";
	}
}
