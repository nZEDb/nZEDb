<?php

require_once './config.php';

// Login check.
$admin = new AdminPage;
$group  = new Groups();

if (isset($_GET['action']) && $_GET['action'] == "2")
{
	$id = (int)$_GET['group_id'];
	$group->delete($id);
	print "Group $id deleted.";
}
else if (isset($_GET['action']) && $_GET['action'] == "3")
{
	$id = (int)$_GET['group_id'];
	$group->reset($id);
	print "Group $id reset.";
}
else if (isset($_GET['action']) && $_GET['action'] == "4")
{
	$id = (int)$_GET['group_id'];
	$group->purge($id);
	print "Group $id purged.";
}
else if (isset($_GET['action']) && $_GET['action'] == "5")
{
	$group->resetall();
	print "All groups reset.";
}
else if (isset($_GET['action']) && $_GET['action'] == "6")
{
	$group->purgeall();
	print "All groups purged.";
}
else
{
	if (isset($_GET['group_id']))
	{
		$id = (int)$_GET['group_id'];
		if(isset($_GET['group_status']))
		{
			$status = isset($_GET['group_status']) ? (int)$_GET['group_status'] : 0;
			print $group->updateGroupStatus($id, $status);
		}
		if(isset($_GET['backfill_status']))
		{
			$status = isset($_GET['backfill_status']) ? (int)$_GET['backfill_status'] : 0;
			print $group->updateBackfillStatus($id, $status);
		}
	}
}
