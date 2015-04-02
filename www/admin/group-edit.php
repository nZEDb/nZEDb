<?php
require_once './config.php';

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
			$group                          = [];
			$group["active"]                = "0";
			$group["backfill"]              = "0";
			$group["minfilestoformrelease"] = "0";
			$group["minsizetoformrelease"]  = "0";
			$group["first_record"]          = "0";
			$group["last_record"]           = "0";
			$group["backfill_target"]       = "0";
		}
		$page->smarty->assign('group', $group);
		break;
}

$page->smarty->assign('yesno_ids', [1, 0]);
$page->smarty->assign('yesno_names', ['Yes', 'No']);

$page->content = $page->smarty->fetch('group-edit.tpl');
$page->render();
