<?php
require_once './config.php';

use nzedb\Tmux;

$page = new AdminPage();
$tmux = new Tmux();
$id   = 0;

// Set the current action.
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view';

switch ($action) {
	case 'submit':
		$error = "";
		$ret = $tmux->update($_POST);
		$page->title = "Tmux Settings Edit";
		$settings = $tmux->get();
		$page->smarty->assign('ftmux', $settings);
		break;

	case 'view':
	default:
		$page->title = "Tmux Settings Edit";
		$settings    = $tmux->get();
		$page->smarty->assign('ftmux', $settings);
		break;
}

$page->smarty->assign('yesno_ids', [1, 0]);
$page->smarty->assign('yesno_names', ['yes', 'no']);

$page->smarty->assign('backfill_ids', [0, 4, 2, 1]);
$page->smarty->assign('backfill_names', ['Disabled', 'Safe', 'Group', 'All']);
$page->smarty->assign('backfill_group_ids', [1, 2, 3, 4, 5, 6]);
$page->smarty->assign('backfill_group',
					  [
						  'Newest', 'Oldest', 'Alphabetical', 'Alphabetical - Reverse',
						  'Most Posts', 'Fewest Posts'
					  ]);
$page->smarty->assign('backfill_days', ['Days per Group', 'Safe Backfill day']);
$page->smarty->assign('backfill_days_ids', [1, 2]);
$page->smarty->assign('dehash_ids', [0, 1, 2, 3]);
$page->smarty->assign('dehash_names', ['Disabled', 'Decrypt Hashes', 'Predb', 'All']);
$page->smarty->assign('import_ids', [0, 1, 2]);
$page->smarty->assign('import_names',
					  ['Disabled', 'Import - Do Not Use Filenames', 'Import - Use Filenames']);
$page->smarty->assign('releases_ids', [0, 1, 2]);
$page->smarty->assign('releases_names',
					  ['Disabled', 'Update Releases', 'Update Releases Threaded']);
$page->smarty->assign('post_ids', [0, 1, 2, 3]);
$page->smarty->assign('post_names',
					  ['Disabled', 'PostProcess Additional', 'PostProcess NFOs', 'All']);
$page->smarty->assign('fix_crap_radio_names', ['Disabled', 'All (except wmv_all)', 'Custom']);
$page->smarty->assign('fix_crap_check_names',
					  [
						  'blacklist', 'blfiles', 'codec', 'executable', 'gibberish', 'hashed',
						  'huge', 'installbin', 'passworded', 'passwordurl',
						  'sample', 'scr', 'short', 'size', 'wmv_all'
					  ]);
$page->smarty->assign('sequential_ids', [0, 1, 2]);
$page->smarty->assign('sequential_names', ['Disabled', 'Basic Sequential', 'Complete Sequential']);
$page->smarty->assign('binaries_ids', [0, 1, 2]);
$page->smarty->assign('binaries_names',
					  ['Disabled', 'Simple Threaded Update', 'Complete Threaded Update']);
$page->smarty->assign('post_non_ids', [0, 1, 2]);
$page->smarty->assign('post_non_names',
					  ['Disabled', 'All Available Releases', 'Properly Renamed Releases']);

$page->content = $page->smarty->fetch('tmux-edit.tpl');
$page->render();
