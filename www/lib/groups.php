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
		return $db->query("SELECT groups.*, COALESCE(rel.num, 0) AS num_releases FROM groups LEFT OUTER JOIN (SELECT groupid, COUNT(id) AS num FROM releases group by groupid) rel ON rel.groupid = groups.id ORDER BY groups.name");
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
		return $db->queryOneRow(sprintf("SELECT * FROM groups WHERE id = %d ", $id));
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
		return $db->query("SELECT id FROM groups WHERE active = 1 ORDER BY name");
	}

	public function getByName($grp)
	{
		$db = new DB();
		return $db->queryOneRow(sprintf("SELECT * FROM groups WHERE name = %s", $db->escapeString($name)));
	}

	public function getByNameByID($id)
	{
		$db = new DB();
		$res = $db->queryOneRow(sprintf("SELECT name FROM groups WHERE id = %d ", $id));
		return $res["name"];
	}

	public function getIDByName($name)
	{
		$db = new DB();
		$res = $db->queryOneRow(sprintf("SELECT id FROM groups WHERE name = %s", $db->escapeString($name)));
		return $res["id"];
	}

	// Set the backfill to 0 when the group is backfilled to max.
	public function disableForPost($name)
	{
		$db = new DB();
		$db->queryUpdate(sprintf("UPDATE groups SET backfill = 0 WHERE name = %s", $db->escapeString($name)));
	}

	public function getCount($groupname="")
	{
		$db = new DB();

		$grpsql = '';
		if ($groupname != "")
			$grpsql .= sprintf("AND groups.name LIKE %s ", $db->escapeString("%".$groupname."%"));

		$res = $db->queryOneRow(sprintf("SELECT COUNT(id) AS num FROM groups WHERE 1 = 1 %s", $grpsql));
		return $res["num"];
	}

	public function getCountActive($groupname="")
	{
		$db = new DB();

		$grpsql = '';
		if ($groupname != "")
			$grpsql .= sprintf("AND groups.name LIKE %s ", $db->escapeString("%".$groupname."%"));

		$res = $db->queryOneRow(sprintf("SELECT COUNT(id) AS num FROM groups WHERE 1 = 1 %s AND active = 1", $grpsql));
		return $res["num"];
	}

	public function getCountInactive($groupname="")
	{
		$db = new DB();

		$grpsql = '';
		if ($groupname != "")
			$grpsql .= sprintf("AND groups.name LIKE %s ", $db->escapeString("%".$groupname."%"));

		$res = $db->queryOneRow(sprintf("SELECT COUNT(id) AS num FROM groups WHERE 1 = 1 %s AND active = 0", $grpsql));
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
			$grpsql .= sprintf("AND groups.name LIKE %s ", $db->escapeString("%".$groupname."%"));

		$sql = sprintf("SELECT groups.*, COALESCE(rel.num, 0) AS num_releases FROM groups LEFT OUTER JOIN (SELECT groupid, COUNT(id) AS num FROM releases GROUP BY groupid) rel ON rel.groupid = groups.id WHERE 1 = 1 %s ORDER BY groups.name ".$limit, $grpsql);
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
			$grpsql .= sprintf("AND groups.name LIKE %s ", $db->escapeString("%".$groupname."%"));

		$sql = sprintf("SELECT groups.*, COALESCE(rel.num, 0) AS num_releases FROM groups LEFT OUTER JOIN (SELECT groupid, COUNT(id) AS num FROM releases group by groupid) rel ON rel.groupid = groups.id WHERE 1 = 1 %s AND active = 1 ORDER BY groups.name ".$limit, $grpsql);
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
			$grpsql .= sprintf("AND groups.name LIKE %s ", $db->escapeString("%".$groupname."%"));

		$sql = sprintf("SELECT groups.*, COALESCE(rel.num, 0) AS num_releases FROM groups LEFT OUTER JOIN (SELECT groupid, COUNT(id) AS num FROM releases group by groupid) rel ON rel.groupid = groups.id WHERE 1 = 1 %s AND active = 0 ORDER BY groups.name ".$limit, $grpsql);
		return $db->query($sql);
	}

	public function add($group)
	{
		$db = new DB();

		if ($group["minfilestoformrelease"] == "" || $group["minfilestoformrelease"] == "0")
			$minfiles = 'NULL';
		else
			$minfiles = $group["minfilestoformrelease"] + 0;

		if ($group["minsizetoformrelease"] == "" || $group["minsizetoformrelease"] == "0")
			$minsizetoformrelease = 'NULL';
		else
			$minsizetoformrelease = $db->escapeString($group["minsizetoformrelease"]);

		$first = (isset($group["first_record"]) ? $group["first_record"] : "0");
		$last = (isset($group["last_record"]) ? $group["last_record"] : "0");

		$sql = sprintf("INSERT INTO groups (name, description, first_record, last_record, last_updated, active, minfilestoformrelease, minsizetoformrelease) values (%s, %s, %s, %s, NULL, %d, %s, %s) ",$db->escapeString($group["name"]), $db->escapeString($group["description"]), $db->escapeString($first), $db->escapeString($last), $group["active"], $minfiles, $minsizetoformrelease);
		return $db->queryInsert($sql);
	}

	public function delete($id)
	{
		$db = new DB();
		return $db->queryDelete(sprintf("DELETE FROM groups WHERE id = %d", $id));
	}

	public function reset($id)
	{
		$db = new DB();
		return $db->queryUpdate(sprintf("UPDATE groups SET backfill_target = 0, first_record = 0, first_record_postdate = NULL, last_record = 0, last_record_postdate = NULL, active = 0, last_updated = NULL where id = %d", $id));
	}

	public function resetall()
	{
		$db = new DB();
		return $db->queryUpdate("UPDATE groups SET backfill_target = 0, first_record = 0, first_record_postdate = NULL, last_record = 0, last_record_postdate = NULL, last_updated = NULL, active = 0");
	}

	public function purge($id)
	{
		$db = new DB();
		$releases = new Releases();
		$binaries = new Binaries();

		$this->reset($id);

		$rels = $db->query(sprintf("SELECT id FROM releases WHERE groupid = %d", $id));
		foreach ($rels as $rel)
			$releases->delete($rel["id"]);

		$cols = $db->query(sprintf("SELECT id FROM collections WHERE groupid = %d", $id));
		foreach ($cols as $col)
			$binaries->delete($col["id"]);
	}

	public function purgeall()
	{
		$db = new DB();
		$releases = new Releases();
		$binaries = new Binaries();

		$this->resetall();

		$rels = $db->query("SELECT id FROM releases");
		foreach ($rels as $rel)
			$releases->delete($rel["id"]);

		$cols = $db->query("SELECT id FROM collections");
		foreach ($cols as $col)
			$binaries->delete($col["id"]);
	}

	public function update($group)
	{
		$db = new DB();

		if ($group["minfilestoformrelease"] == "" || $group["minfilestoformrelease"] == "0")
			$minfiles = 'NULL';
		else
			$minfiles = $group["minfilestoformrelease"] + 0;

		if ($group["minsizetoformrelease"] == "" || $group["minsizetoformrelease"] == "0")
			$minsizetoformrelease = 'NULL';
		else
			$minsizetoformrelease = $db->escapeString($group["minsizetoformrelease"]);

		return $db->queryUpdate(sprintf("UPDATE groups SET name = %s, description = %s, backfill_target = %s , active = %d, backfill = %d, minfilestoformrelease = %s, minsizetoformrelease = %s where id = %d ",$db->escapeString($group["name"]), $db->escapeString($group["description"]), $db->escapeString($group["backfill_target"]),$group["active"], $group["backfill"] , $minfiles, $minsizetoformrelease, $group["id"] ));
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
					$res = $db->queryOneRow(sprintf("SELECT id FROM groups WHERE name = %s ", $db->escapeString($group['group'])));
					if($res)
					{

						$db->queryUpdate(sprintf("UPDATE groups SET active = %d where id = %d", $active, $res["id"]));
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
		$db->queryUpdate(sprintf("UPDATE groups SET active = %d WHERE id = %d", $status, $id));
		$status = ($status == 0) ? 'deactivated' : 'activated';
		return "Group $id has been $status.";
	}

	public function updateBackfillStatus($id, $status = 0)
	{
		$db = new DB();
		$db->queryUpdate(sprintf("UPDATE groups SET backfill = %d WHERE id = %d", $status, $id));
		$status = ($status == 0) ? 'deactivated' : 'activated';
		return "Group $id has been $status.";
	}
}
