<?php

// This script waits for ajax queries from the web.

if (!isset($_GET['action'])) {
	exit();
}

require_once './config.php';

// Make sure the user is an admin and logged in.
$admin = new AdminPage;

$settings = ['Settings' => $admin->settings];
switch ($_GET['action']) {
	case 'binary_blacklist_delete':
		$id = (int)$_GET['row_id'];
		(new Binaries($settings))->deleteBlacklist($id);
		print "Blacklist $id deleted.";
		break;

	case 'category_regex_delete':
		$id = (int)$_GET['row_id'];
		(new Regexes([
						 'Settings' => $admin->settings, 'Table_Name' => 'category_regexes'
					 ]))->deleteRegex($id);
		print "Regex $id deleted.";
		break;

	case 'collection_regex_delete':
		$id = (int)$_GET['row_id'];
		(new Regexes([
						 'Settings' => $admin->settings, 'Table_Name' => 'collection_regexes'
					 ]))->deleteRegex($id);
		print "Regex $id deleted.";
		break;

	case 'release_naming_regex_delete':
		$id = (int)$_GET['row_id'];
		(new Regexes([
						 'Settings' => $admin->settings, 'Table_Name' => 'release_naming_regexes'
					 ]))->deleteRegex($id);
		print "Regex $id deleted.";
		break;

	case 'group_edit_purge_all':
		session_write_close();
		(new Groups($settings))->purge();
		print "All groups purged.";
		break;

	case 'group_edit_reset_all':
		(new Groups($settings))->resetall();
		print "All groups reset.";
		break;

	case 'group_edit_purge_single':
		$id = (int)$_GET['group_id'];
		session_write_close();
		(new Groups($settings))->purge($id);
		print "Group $id purged.";
		break;

	case 'group_edit_reset_single':
		$id = (int)$_GET['group_id'];
		session_write_close();
		(new Groups($settings))->reset($id);
		print "Group $id reset.";
		break;

	case 'group_edit_delete_single':
		$id = (int)$_GET['group_id'];
		session_write_close();
		(new Groups($settings))->delete($id);
		print "Group $id deleted.";
		break;

	case 'toggle_group_active_status':
		print (new Groups($settings))->updateGroupStatus((int)$_GET['group_id'],
			(isset($_GET['group_status']) ? (int)$_GET['group_status'] : 0));
		break;

	case 'toggle_group_backfill_status':
		print (new Groups($settings))->updateBackfillStatus((int)$_GET['group_id'],
			(isset($_GET['backfill_status']) ? (int)$_GET['backfill_status'] : 0));
		break;

	case 'sharing_toggle_status':
		$admin->settings->queryExec(sprintf('UPDATE sharing_sites SET enabled = %d WHERE id = %d',
											$_GET['site_status'],
											$_GET['site_id']));
		print ($_GET['site_status'] == 1 ? 'Activated' : 'Deactivated') . ' site ' .
			  $_GET['site_id'];
		break;

	case 'sharing_toggle_enabled':
		$admin->settings->queryExec(sprintf('UPDATE sharing SET enabled = %d',
											$_GET['enabled_status']));
		print ($_GET['enabled_status'] == 1 ? 'Enabled' : 'Disabled') . ' sharing!';
		break;

	case 'sharing_start_position':
		$admin->settings->queryExec(sprintf('UPDATE sharing SET start_position = %d',
											$_GET['start_position']));
		print ($_GET['start_position'] == 1 ? 'Enabled' : 'Disabled') .
			  ' fetching from start of group!';
		break;

	case 'sharing_reset_settings':
		$guid = $admin->settings->queryOneRow('SELECT site_guid FROM sharing');
		$guid = ($guid === false ? '' : $guid['site_guid']);
		(new Sharing(['Settings' => $admin->settings]))->initSettings($guid);
		print 'Re-initiated sharing settings!';
		break;

	case 'sharing_purge_site':
		$guid = $admin->settings->queryOneRow(sprintf('SELECT site_guid FROM sharing_sites WHERE id = %d',
													  $_GET['purge_site']));
		if ($guid === false) {
			print 'Error purging site ' . $_GET['purge_site'] . '!';
		} else {
			$ids = $admin->settings->query(sprintf('SELECT id FROM release_comments WHERE siteid = %s',
												   $admin->settings->escapeString($guid['site_guid'])));
			$total = count($ids);
			if ($total > 0) {
				$rc = new ReleaseComments($admin->settings);
				foreach ($ids as $id) {
					$rc->deleteComment($id['id']);
				}
			}
			$admin->settings->queryExec(sprintf('UPDATE sharing_sites SET comments = 0 WHERE id = %d',
												$_GET['purge_site']));
			print 'Deleted ' . $total . ' comments for site ' . $_GET['purge_site'];
		}
		break;

	case 'sharing_toggle_posting':
		$admin->settings->queryExec(sprintf('UPDATE sharing SET posting = %d',
											$_GET['posting_status']));
		print ($_GET['posting_status'] == 1 ? 'Enabled' : 'Disabled') . ' posting!';
		break;

	case 'sharing_toggle_fetching':
		$admin->settings->queryExec(sprintf('UPDATE sharing SET fetching = %d',
											$_GET['fetching_status']));
		print ($_GET['fetching_status'] == 1 ? 'Enabled' : 'Disabled') . ' fetching!';
		break;

	case 'sharing_toggle_site_auto_enabling';
		$admin->settings->queryExec(sprintf('UPDATE sharing SET auto_enable = %d',
											$_GET['auto_status']));
		print ($_GET['auto_status'] == 1 ? 'Enabled' : 'Disabled') . ' automatic site enabling!';
		break;

	case 'sharing_toggle_hide_users':
		$admin->settings->queryExec(sprintf('UPDATE sharing SET hide_users = %d',
											$_GET['hide_status']));
		print ($_GET['hide_status'] == 1 ? 'Enabled' : 'Disabled') . ' hiding of user names!';
		break;

	case 'sharing_toggle_all_sites':
		$admin->settings->queryExec(sprintf('UPDATE sharing_sites SET enabled = %d',
											$_GET['toggle_all']));
		break;
}
