<?php
require_once(WWW_DIR."lib/framework/db.php");

class ReleaseFiles
{	
	public function get($id)
	{
		$db = new DB();
		return $db->query(sprintf("select * from releasefiles where releaseID = %d  order by releasefiles.name ", $id));	
	}
	
	public function getByGuid($guid)
	{
		$db = new DB();
		return $db->query(sprintf("select releasefiles.* from releasefiles inner join releases r on r.ID = releasefiles.releaseID where r.guid = %s order by releasefiles.name ", $db->escapeString($guid)));	
	}	
	
	public function delete($id)
	{
		$db = new DB();
		return $db->query(sprintf("delete from releasefiles where releaseID = %d", $id));	
	}

	public function add($id, $name, $size, $createddate, $passworded)
	{
		$db = new DB();
		$sql = sprintf("INSERT INTO releasefiles  (releaseID,   name,   size,   createddate,   passworded)
						VALUES (%d, %s, %s, from_unixtime(%d), %d)", $id, $db->escapeString($name), $db->escapeString($size), $createddate, $passworded );	
		return $db->queryInsert($sql);	
	}

}
?>
