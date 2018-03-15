<?php
require_once './config.php';

use nzedb\AniDB;

$page  = new AdminPage();
$aniDB = new AniDB(['Settings' => $page->settings]);

// Set the current action.
$action = $_REQUEST['action'] ?? 'view';

switch ($action) {
	case 'submit':
		$aniDB->updateTitle(
			$_POST['anidbid'],
							$_POST['type'],
							$_POST['startdate'],
							$_POST['enddate'],
							$_POST['related'],
							$_POST['similar'],
							$_POST['creators'],
							$_POST['description'],
							$_POST['rating'],
							$_POST['categories'],
							$_POST['characters']
		);

		if (isset($_POST['from']) && !empty($_POST['from'])) {
			header('Location:' . $_POST['from']);
			exit;
		}

		header('Location:' . WWW_TOP . '/anidb-list.php');
		break;

	case 'view':
	default:
		if (isset($_GET['id'])) {
			$page->title   = 'AniDB Edit';
			$aniDbAPIArray = $aniDB->getAnimeInfo($_GET['id']);
			$page->smarty->assign('anime', $aniDbAPIArray);
		}
		break;
}

$page->title   = 'Edit AniDB Data';
$page->content = $page->smarty->fetch('anidb-edit.tpl');
$page->render();
