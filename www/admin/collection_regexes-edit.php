<?php
require_once './config.php';

$page = new AdminPage();
$cc = new CollectionsCleaning(['Settings' => $page->settings]);

// Set the current action.
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view';

switch($action) {
	case 'submit':
		if ($_POST["group_regex"] == "") {
			$page->smarty->assign('error', "Group regex must not be empty!");
			break;
		}

		if ($_POST["regex"] == "") {
			$page->smarty->assign('error', "Regex cannot be empty");
			break;
		}

		if ($_POST['description'] == '') {
			$_POST['description'] = '';
		}

		if (!is_numeric($_POST['ordinal']) || $_POST['ordinal'] < 0) {
			$page->smarty->assign('error', "Ordinal must be a number, 0 or higher.");
			break;
		}

		if ($_POST["id"] == "") {
			$cc->addRegex($_POST);
		} else {
			$cc->updateRegex($_POST);
		}

		header("Location:".WWW_TOP."/collection_regexes-list.php");
		break;

	case 'view':
	default:
		if (isset($_GET["id"])) {
			$page->title = "Collections Regex Edit";
			$id = $_GET["id"];
			$r = $cc->getRegexByID($id);
		} else {
			$page->title = "Collections Regex Add";
			$r = ['status' => 1];
		}
		$page->smarty->assign('regex', $r);
		break;
}

$page->smarty->assign('status_ids', array(Category::STATUS_ACTIVE,Category::STATUS_INACTIVE));
$page->smarty->assign('status_names', array( 'Yes', 'No'));

$page->content = $page->smarty->fetch('collection_regexes-edit.tpl');
$page->render();