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
			// Add a new mg poster.
			$poster = MultigroupPosters::create();
		} else {
			// Update an existing mg poster.
			$poster = MultigroupPosters::find($_POST['id']);
		}
		$poster->poster = $_POST['poster'];
		$poster->save();

		header("Location:" . WWW_TOP . "/posters-list.php");
		break;

	case 'view':
	default:
		if (!empty($_GET['id'])) {
			$page->title = "MultiGroup Poster Edit";
			// Note: explicitly setting default stuff below, which could be shortened to:
			// $entry = MultigroupPosters::find($_GET['id']);
			$poster = MultigroupPosters::find('first',
				[
					'conditions' =>
						[
							'id' => $_GET['id']
						],
					'fields' =>
						[
							'id',
							'poster'
						]
				]
			);
		} else {
			$page->title = "MultiGroup Poster Add";
			$poster = '';
		}
		$page->smarty->assign('poster', $poster);
		break;
}

$page->content = $page->smarty->fetch('posters-edit.tpl');
$page->render();
