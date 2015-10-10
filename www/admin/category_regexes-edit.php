<?php
require_once './config.php';

use nzedb\Category;
use nzedb\Regexes;

$page    = new AdminPage();
$regexes = new Regexes(['Settings' => $page->settings, 'Table_Name' => 'category_regexes']);
$error = '';
$regex = ['id' => '', 'regex' => '', 'description' => '', 'group_regex' => '', 'ordinal' => '', 'category_id' => ''];

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
			$regex= $regexes->getRegexByID($_GET["id"]);
		} else {
			$page->title = "Category Regex Add";
			$regex += ['status' => 1];
		}

		break;
}

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

$page->smarty->assign([
		'regex'          => $regex,
		'error'          => $error,
		'status_ids'     => [Category::STATUS_ACTIVE, Category::STATUS_INACTIVE],
		'status_names'   => ['Yes', 'No'],
		'category_names' => $categories['category_names'],
		'category_ids'   => $categories['category_ids']
	]
);

$page->content = $page->smarty->fetch('category_regexes-edit.tpl');
$page->render();
