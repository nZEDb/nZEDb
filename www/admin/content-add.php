<?php
require_once './config.php';

use nzedb\Content;
use nzedb\Contents;
use nzedb\Users;

$page     = new AdminPage();
$contents = new Contents(['Settings' => $page->settings]);
$id       = 0;

// Set the current action.
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view';

switch ($action) {
	case 'add':
		$page->title          = "Content Add";
		$content              = new Content();
		$content->showinmenu  = "1";
		$content->status      = "1";
		$content->contenttype = "2";
		$page->smarty->assign('content', $content);
		break;

	case 'submit':
		// Validate and add or update.
		$returnid = 0;
		if (!isset($_POST["id"]) || $_POST["id"] == "") {
			$returnid = $contents->add($_POST);
		} else {
			$content  = $contents->update($_POST);
			$returnid = $content->id;
		}
		header("Location:content-add.php?id=" . $returnid);
		break;

	case 'view':
	default:
		if (isset($_GET["id"])) {
			$page->title = "Content Edit";
			$id          = $_GET["id"];

			$content = $contents->getByID($id, Users::ROLE_ADMIN);
			$page->smarty->assign('content', $content);
		}
		break;
}

$page->smarty->assign('status_ids', [1, 0]);
$page->smarty->assign('status_names', ['Enabled', 'Disabled']);

$page->smarty->assign('yesno_ids', [1, 0]);
$page->smarty->assign('yesno_names', ['Yes', 'No']);

$contenttypelist = ["1" => "Useful Link", "2" => "Article", "3" => "Homepage"];
$page->smarty->assign('contenttypelist', $contenttypelist);

$rolelist = ["0" => "Everyone", "1" => "Logged in Users", "2" => "Admins"];
$page->smarty->assign('rolelist', $rolelist);

$page->content = $page->smarty->fetch('content-add.tpl');
$page->render();
