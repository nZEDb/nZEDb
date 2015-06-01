<?php
require_once './config.php';

use nzedb\Category;
use nzedb\Regexes;

$page    = new AdminPage();
$regexes = new Regexes(['Settings' => $page->settings, 'Table_Name' => 'category_regexes']);

// Set the current action.
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view';

switch ($action) {
	case 'submit':
		if ($_POST["group_regex"] == '') {
			$page->smarty->assign('error', "Group regex must not be empty!");
			break;
		}

		if ($_POST["regex"] == '') {
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

		if ($_POST["id"] == '') {
			$regexes->addRegex($_POST);
		} else {
			$regexes->updateRegex($_POST);
		}

		header("Location:" . WWW_TOP . "/category_regexes-list.php");
		break;

	case 'view':
	default:
		if (isset($_GET["id"])) {
			$page->title = "Category Regex Edit";
			$id          = $_GET["id"];
			$r           = $regexes->getRegexByID($id);
		} else {
			$page->title = "Category Regex Add";
			$r           = ['status' => 1];
		}
		$page->smarty->assign('regex', $r);
		break;
}

$page->smarty->assign('status_ids', [Category::STATUS_ACTIVE, Category::STATUS_INACTIVE]);
$page->smarty->assign('status_names', ['Yes', 'No']);

$categories_db = $page->settings->queryDirect(
	'SELECT c.id, c.title, cp.title AS parent_title
	FROM category c
	INNER JOIN category cp ON c.parentid = cp.id
	WHERE c.parentid IS NOT NULL
	ORDER BY c.id ASC'
);
$categories = ['category_names', 'category_ids'];
if ($categories_db) {
	foreach ($categories_db as $category_db) {
		$categories['category_names'][] =
			$category_db['parent_title'] . ' ' . $category_db['title'] . ': ' . $category_db['id'];
		$categories['category_ids'][]   = $category_db['id'];
	}
}
$page->smarty->assign('category_names', $categories['category_names']);
$page->smarty->assign('category_ids', $categories['category_ids']);

$page->content = $page->smarty->fetch('category_regexes-edit.tpl');
$page->render();
