<?php
require_once './config.php';

use nzedb\Groups;

$page   = new AdminPage();
$groups = new Groups(['Settings' => $page->settings]);
$id     = 0;

// Set the current action.
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view';

switch ($action) {
	case 'submit':
		if ($_POST["id"] == "") {
			// Add a new group.
			$groups->add($_POST);
		} else {
			// Update an existing group.
			$groups->update($_POST);
		}
		header("Location:" . WWW_TOP . "/group-list.php");
		break;

	case 'view':
	default:
		if (isset($_GET["id"])) {
			$page->title = "Newsgroup Edit";
			$id          = $_GET["id"];
			$group       = $groups->getByID($id);
		} else {
			$page->title                    = "Newsgroup Add";
			$group                          = [
				'id' => '', 'name' => '', 'description' => '', 'minfilestoformrelease' => 0, 'active' => 0, 'backfill' => 0,
				'minsizetoformrelease' => 0, 'first_record' => 0, 'last_record' => 0, 'backfill_target' => 0
			];
		}
		$page->smarty->assign('group', $group);
		break;
}

$page->smarty->assign('yesno_ids', [1, 0]);
$page->smarty->assign('yesno_names', ['Yes', 'No']);

$page->content = $page->smarty->fetch('group-edit.tpl');
$page->render();
