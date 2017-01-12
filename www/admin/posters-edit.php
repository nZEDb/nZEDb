<?php

require_once './config.php';

use app\models\MultigroupPosters;
use nzedb\processing\ProcessReleasesMultiGroup;

$page = new AdminPage();
$relPosters = new ProcessReleasesMultiGroup(['Settings' => $page->settings]);

// Set the current action.
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view';

switch ($action) {
	case 'submit':
		if ($_POST['id'] == '') {
			// Add a new mgr poster.
			//$relPosters->addPoster($_POST['poster']);
			$poster = MultigroupPosters::create(['poster' => $_POST['poster']]);
			$poster->save();
		} else {
			// Update an existing mgr poster.
			$relPosters->updatePoster($_POST['id'], $_POST['poster']);
		}
		header("Location:" . WWW_TOP . "/posters-list.php");
		break;

	case 'view':
	default:
		if (!empty($_GET['id'])) {
			$page->title = "MGR Poster Edit";
			// Note: explicitly setting default stuff below, which could be shorted to:
			// $entry = MultigroupPosters::find($_GET['id']);
			$entry = MultigroupPosters::find('first',
				[
					'conditions' => ['id' => $_GET['id']],
					'fields' => ['id', 'poster']
				]);
			$poster = [
				'id'     => $entry->id,
				'poster' => $entry->poster,
			];
		} else {
			$page->title = "MGR Poster Add";
			$poster = [
				'id'     => '',
				'poster' => ''
			];
		}
		$page->smarty->assign('poster', $poster);
		break;
}

$page->content = $page->smarty->fetch('posters-edit.tpl');
$page->render();
