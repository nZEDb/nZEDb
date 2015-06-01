<?php
require_once './config.php';

use nzedb\Menu;

$page = new AdminPage();
$menu = new Menu($page->settings);
$id   = 0;

// Get the user roles.
$userroles = $page->users->getRoles();
$roles     = [];
foreach ($userroles as $r) {
	$roles[$r['id']] = $r['name'];
}

// set the current action
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view';

switch ($action) {
	case 'submit':
		if ($_POST["id"] == "") {
			$menu->add($_POST);
		} else {
			$ret = $menu->update($_POST);
		}

		header("Location:" . WWW_TOP . "/menu-list.php");
		break;

	case 'view':
	default:
		$menuRow = [
			'id' => '', 'title' => '', 'href' => '', 'tooltip' => '',
			'menueval' => '', 'role' => 0, 'ordinal' => 0, 'newwindow' => 0
		];
		if (isset($_GET["id"])) {

			$id          = $_GET["id"];
			$menuRow     = $menu->getByID($id);
		}
		$page->title = "Menu Edit";
		$page->smarty->assign('menu', $menuRow);
		break;
}

$page->smarty->assign('yesno_ids', [1, 0]);
$page->smarty->assign('yesno_names', ['Yes', 'No']);

$page->smarty->assign('role_ids', array_keys($roles));
$page->smarty->assign('role_names', $roles);

$page->content = $page->smarty->fetch('menu-edit.tpl');
$page->render();
