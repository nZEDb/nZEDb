<?php
require_once './config.php';

// Login check.
$admin = new AdminPage;

if (isset($_GET['site_id']) && isset($_GET['site_status'])) {
	$admin->settings->queryExec(sprintf('UPDATE sharing_sites SET enabled = %d WHERE id = %d', $_GET['site_status'], $_GET['site_id']));
	if ($_GET['site_status'] == 1) {
		print 'Activated site ' . $_GET['site_id'];
	} else {
		print 'Deactivated site ' . $_GET['site_id'];
	}
}

else if (isset($_GET['enabled_status'])) {
	$admin->settings->queryExec(sprintf('UPDATE sharing SET enabled = %d', $_GET['enabled_status']));
	if ($_GET['enabled_status'] == 1) {
		print 'Enabled sharing!';
	} else {
		print 'Disabled sharing!';
	}
}

else if (isset($_GET['posting_status'])) {
	$admin->settings->queryExec(sprintf('UPDATE sharing SET posting = %d', $_GET['posting_status']));
	if ($_GET['posting_status'] == 1) {
		print 'Enabled posting!';
	} else {
		print 'Disabled posting!';
	}
}

else if (isset($_GET['fetching_status'])) {
	$admin->settings->queryExec(sprintf('UPDATE sharing SET fetching = %d', $_GET['fetching_status']));
	if ($_GET['fetching_status'] == 1) {
		print 'Enabled fetching!';
	} else {
		print 'Disabled fetching!';
	}
}

else if (isset($_GET['auto_status'])) {
	$admin->settings->queryExec(sprintf('UPDATE sharing SET auto_enable = %d', $_GET['auto_status']));
	if ($_GET['auto_status'] == 1) {
		print 'Enabled automatic site enabling!';
	} else {
		print 'Disabled automatic site enabling!';
	}
}

else if (isset($_GET['hide_status'])) {
	$admin->settings->queryExec(sprintf('UPDATE sharing SET hide_users = %d', $_GET['hide_status']));
	if ($_GET['hide_status'] == 1) {
		print 'Enabled hiding of user names!';
	} else {
		print 'Disabled hiding of user names!';
	}
}

else if (isset($_GET['start_position'])) {
	$admin->settings->queryExec(sprintf('UPDATE sharing SET start_position = %d', $_GET['start_position']));
	if ($_GET['start_position'] == 1) {
		print 'Enabled fetching from start of group!';
	} else {
		print 'Disabled fetching from start of group!';
	}
}

else if (isset($_GET['toggle_all'])) {
	$admin->settings->queryExec(sprintf('UPDATE sharing_sites SET enabled = %d', $_GET['toggle_all']));
}

else if (isset($_GET['reset_settings'])) {
	$guid = $admin->settings->queryOneRow('SELECT site_guid FROM sharing');
	$guid = ($guid === false ? '' : $guid['site_guid']);
	(new Sharing(['Settings' => $admin->settings]))->initSettings($guid);
	print 'Re-initiated sharing settings!';
}

else if (isset($_GET['purge_site'])) {
	$guid = $admin->settings->queryOneRow(sprintf('SELECT site_guid FROM sharing_sites WHERE id = %d', $_GET['purge_site']));
	if ($guid === false) {
		print 'Error purging site ' . $_GET['purge_site'] . '!';
	} else {
		$ids = $admin->settings->query(sprintf('SELECT id FROM release_comments WHERE siteid = %s', $admin->settings->escapeString($guid['site_guid'])));
		$total = count($ids);
		if ($total > 0) {
			$rc = new ReleaseComments($admin->settings);
			foreach ($ids as $id) {
				$rc->deleteComment($id['id']);
			}
		}
		$admin->settings->queryExec(sprintf('UPDATE sharing_sites SET comments = 0 WHERE id = %d', $_GET['purge_site']));
		print 'Deleted ' . $total . ' comments for site ' . $_GET['purge_site'];
	}
}
