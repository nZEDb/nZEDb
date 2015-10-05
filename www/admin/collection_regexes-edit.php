<?php
require_once './config.php';

use nzedb\Category;
use nzedb\Regexes;

$page    = new AdminPage();
$regexes = new Regexes(['Settings' => $page->settings, 'Table_Name' => 'collection_regexes']);
$error = '';
$regex = ['id' => '', 'regex' => '', 'description' => '', 'group_regex' => '', 'ordinal' => ''];

switch ((isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view')) {
	case 'submit':
		if ($_POST["group_regex"] == '') {
			$error = "Group regex must not be empty!";
			break;
		}

		if ($_POST["regex"] == '') {
			$error = "Regex cannot be empty";
			break;
		}

		if ($_POST['description'] == '') {
			$_POST['description'] = '';
		}

		if (!is_numeric($_POST['ordinal']) || $_POST['ordinal'] < 0) {
			$error = "Ordinal must be a number, 0 or higher.";
			break;
		}

		if ($_POST["id"] == "") {
			$regexes->addRegex($_POST);
		} else {
			$regexes->updateRegex($_POST);
		}

		header("Location:" . WWW_TOP . "/collection_regexes-list.php");
		break;

	case 'view':
	default:
		if (isset($_GET["id"])) {
			$page->title = "Collections Regex Edit";
			$regex = $regexes->getRegexByID($_GET["id"]);
		} else {
			$page->title = "Collections Regex Add";
			$regex += ['status' => 1];
		}
		break;
}

$page->smarty->assign('regex', $regex);
$page->smarty->assign('error', $error);
$page->smarty->assign('status_ids', [Category::STATUS_ACTIVE, Category::STATUS_INACTIVE]);
$page->smarty->assign('status_names', ['Yes', 'No']);

$page->content = $page->smarty->fetch('collection_regexes-edit.tpl');
$page->render();
