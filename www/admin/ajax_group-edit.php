<?php
require_once("config.php");
require_once(WWW_DIR."/lib/adminpage.php");
require_once(WWW_DIR."/lib/groups.php");

// login check
$admin = new AdminPage;
$group  = new Groups();

if (isset($_GET['action']) && $_GET['action'] == "2")
{
		$id     = (int)$_GET['group_id'];
		$group->delete($id);	
		print "Group $id deleted.";
}
elseif (isset($_GET['action']) && $_GET['action'] == "3")
{
		$id     = (int)$_GET['group_id'];
		$group->reset($id);	
		print "Group $id reset.";
}
elseif (isset($_GET['action']) && $_GET['action'] == "4")
{
		$id     = (int)$_GET['group_id'];
		$group->purge($id);	
		print "Group $id purged.";
}
else
{
	if (isset($_GET['group_id']))
	{
		$id     = (int)$_GET['group_id'];
		$status = isset($_GET['group_status']) ? (int)$_GET['group_status'] : 0;
		print $group->updateGroupStatus($id, $status);
	}	
}


