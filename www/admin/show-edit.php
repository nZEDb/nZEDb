<?php
require_once './config.php';

use nzedb\processing\tv\TV;
use nzedb\Videos;

$page   = new AdminPage();
$tv = new TV(['Settings' => $page->settings]);
$video = new Videos(['Settings' => $page->settings]);

$show = [
	'id' => '', 'description' => '', 'releasetitle' => '', 'genre' => '',
	'rageid' => '', 'country' => '', 'imgdata' => ''
];

switch ((isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view')) {
	case 'submit':
		if ($_POST["id"] == '') {
			$tv->add($_POST["id"], $_POST["title"], $_POST["summary"],
				 $_POST['countries_id']
			);
		} else {
			$tv->update($_POST["id"], $_POST["title"],
				$_POST["summary"], $_POST['countries_id']
			);
		}

		if (isset($_POST['from']) && !empty($_POST['from'])) {
			header("Location:" . $_POST['from']);
			exit;
		}

		header("Location:" . WWW_TOP . "/show-list.php");
		break;

	case 'view':
	default:
		if (isset($_GET["id"])) {
			$page->title = "TV Show Edit";
			$show = $video->getByVideoID($_GET["id"]);
		}
		break;
}

$page->smarty->assign('show', $show);

$page->title   = "Add/Edit TV Rage Show Data";
$page->content = $page->smarty->fetch('show-edit.tpl');
$page->render();