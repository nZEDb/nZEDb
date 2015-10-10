<?php
require_once './config.php';

use nzedb\Groups;

$page = new AdminPage();
$msgs = $error = false;

// Set the current action.
$action = (isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view');

switch ($action) {
	case 'submit':
		if (isset($_POST['groupfilter'])) {
			$groups = new Groups();
			$msgs   = $groups->addBulk($_POST['groupfilter'], $_POST['active'], $_POST['backfill']);
			if (is_string($msgs)) {
				$error = true;
			}
		}
		break;
	case 'view':
	default:
		$msgs = false;
		break;
}

$page->smarty->assign('error', $error);
$page->smarty->assign('groupmsglist', $msgs);
$page->smarty->assign('yesno_ids', [1, 0]);
$page->smarty->assign('yesno_names', ['Yes', 'No']);

$page->title   = 'Bulk Add Newsgroups';
$page->content = $page->smarty->fetch('group-bulk.tpl');
$page->render();
