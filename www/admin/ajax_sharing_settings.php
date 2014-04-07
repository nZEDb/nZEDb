<?php

require_once './config.php';

// Login check.
$admin = new AdminPage;
use nzedb\db\DB;

$db = new DB();

if (isset($_GET['site_id']) && isset($_GET['site_status'])) {
	$db->queryExec(sprintf('UPDATE sharing_sites SET enabled = %d WHERE id = %d', $_GET['site_status'], $_GET['site_id']));
	if ($_GET['site_status'] == 1) {
		print 'Activated site ' . $_GET['site_id'];
	} else {
		print 'Deactivated site ' . $_GET['site_id'];
	}
}

if (isset($_GET['enabled_status'])) {
	$db->queryExec(sprintf('UPDATE sharing SET enabled = %d', $_GET['enabled_status']));
	if ($_GET['enabled_status'] == 1) {
		print 'Enabled sharing!';
	} else {
		print 'Disabled sharing!';
	}
}

if (isset($_GET['posting_status'])) {
	$db->queryExec(sprintf('UPDATE sharing SET posting = %d', $_GET['posting_status']));
	if ($_GET['posting_status'] == 1) {
		print 'Enabled posting!';
	} else {
		print 'Disabled posting!';
	}
}

if (isset($_GET['fetching_status'])) {
	$db->queryExec(sprintf('UPDATE sharing SET fetching = %d', $_GET['fetching_status']));
	if ($_GET['fetching_status'] == 1) {
		print 'Enabled fetching!';
	} else {
		print 'Disabled fetching!';
	}
}

if (isset($_GET['auto_status'])) {
	$db->queryExec(sprintf('UPDATE sharing SET auto_enable = %d', $_GET['auto_status']));
	if ($_GET['auto_status'] == 1) {
		print 'Enabled automatic site enabling!';
	} else {
		print 'Disabled automatic site enabling!';
	}
}

if (isset($_GET['hide_status'])) {
	$db->queryExec(sprintf('UPDATE sharing SET hide_users = %d', $_GET['hide_status']));
	if ($_GET['hide_status'] == 1) {
		print 'Enabled hiding of user names!';
	} else {
		print 'Disabled hiding of user names!';
	}
}

if (isset($_GET['toggle_all'])) {
	$db->queryExec(sprintf('UPDATE sharing_sites SET enabled = %d', $_GET['toggle_all']));
}