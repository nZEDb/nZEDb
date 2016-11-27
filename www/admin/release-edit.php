<?php
require_once './config.php';

use nzedb\Category;
use nzedb\Releases;

$page = new AdminPage(true);
$releases = new Releases(['Settings' => $page->settings]);
$category = new Category(['Settings' => $page->settings]);
$id = 0;

// Set the current action.
$action = (isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view');
$from = (isset($_REQUEST['from']) ? $_REQUEST['from'] : WWW_TOP . "/release-list.php");

switch ($action) {
	case 'submit':
		$releases->update($_POST["id"],
						  $_POST["name"],
						  $_POST["searchname"],
						  $_POST["fromname"],
						  $_POST["category"],
						  $_POST["totalpart"],
						  $_POST["grabs"],
						  $_POST["size"],
						  $_POST["postdate"],
						  $_POST["adddate"],
						  $_POST["videos_id"],
						  $_POST["tv_episodes_id"],
						  $_POST["imdbid"],
						  $_POST["anidbid"]);
		header("Location:" . $from);
		break;

	case 'view':
	default:
		if (isset($_GET["id"])) {
			$page->title = "Release Edit";
			$id          = $_GET["id"];
			$release     = $releases->getById($id);
			$page->smarty->assign('release', $release);
		}
		break;
}

$page->smarty->assign('from', $from);
$page->smarty->assign('yesno_ids', [1, 0]);
$page->smarty->assign('yesno_names', ['Yes', 'No']);
$page->smarty->assign('catlist', $category->getForSelect(false));

$page->content = $page->smarty->fetch('release-edit.tpl');
$page->render();
