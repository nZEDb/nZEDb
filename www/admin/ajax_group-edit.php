<?php
require_once './config.php';

// Login check.
$admin = new AdminPage;
$group  = new Groups();

// session_write_close(); allows the admin to use the site while the ajax request is being processed.
if (isset($_GET['action']) && $_GET['action'] == "2") {
	$id = (int)$_GET['group_id'];
	session_write_close();
	$group->delete($id);
	print "Group $id deleted.";
} else if (isset($_GET['action']) && $_GET['action'] == "3") {
	$id = (int)$_GET['group_id'];
	session_write_close();
	$group->reset($id);
	print "Group $id reset.";
} else if (isset($_GET['action']) && $_GET['action'] == "4") {
	$id = (int)$_GET['group_id'];
	session_write_close();
	$group->purge($id);
	print "Group $id purged.";
} else if (isset($_GET['action']) && $_GET['action'] == "5") {
	$group->resetall();
	print "All groups reset.";
} else if (isset($_GET['action']) && $_GET['action'] == "6") {
	session_write_close();
	$group->purge();
	print "All groups purged.";
} else {
	if (isset($_GET['group_id'])) {
		$id = (int)$_GET['group_id'];
		if(isset($_GET['group_status'])) {
			$status = isset($_GET['group_status']) ? (int)$_GET['group_status'] : 0;
			print $group->updateGroupStatus($id, $status);
		}
		if(isset($_GET['backfill_status'])) {
			$status = isset($_GET['backfill_status']) ? (int)$_GET['backfill_status'] : 0;
			print $group->updateBackfillStatus($id, $status);
		}
	}
}