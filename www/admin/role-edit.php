<?php
require_once './config.php';

$page = new AdminPage();

// Get the user roles.
$userRoles = $page->users->getRoles();
$roles = [];
foreach ($userRoles as $userRole) {
	$roles[$userRole['id']] = $userRole['name'];
}

switch ((isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view')) {
	case 'add':
		$page->title              = "User Roles Add";
		$role = [
			'id'               => '',
			'name'             => '',
			'apirequests'      => '',
			'downloadrequests' => '',
			'defaultinvites'   => '',
			'canpreview'       => 0
		];
		break;

	case 'submit':
		if ($_POST["id"] == "") {
			$ret = $page->users->addRole($_POST['name'], $_POST['apirequests'], $_POST['downloadrequests'],
				$_POST['defaultinvites'], $_POST['canpreview']
			);
			header("Location:" . WWW_TOP . "/role-list.php");
		} else {
			$ret = $page->users->updateRole($_POST['id'], $_POST['name'], $_POST['apirequests'],
				$_POST['downloadrequests'], $_POST['defaultinvites'], $_POST['isdefault'], $_POST['canpreview']
			);
			header("Location:" . WWW_TOP . "/role-list.php");
		}
		break;

	case 'view':
	default:
		if (isset($_GET["id"])) {
			$page->title = "User Roles Edit";
			$role = $page->users->getRoleByID($_GET["id"]);
		}
		break;
}

$page->smarty->assign('role', $role);
$page->smarty->assign('yesno_ids', [1, 0]);
$page->smarty->assign('yesno_names', ['Yes', 'No']);

$page->content = $page->smarty->fetch('role-edit.tpl');
$page->render();
