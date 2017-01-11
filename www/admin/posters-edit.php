<?php

require_once './config.php';


use nzedb\processing\ProcessReleasesMultiGroup;

$page = new AdminPage();
$relPosters = new ProcessReleasesMultiGroup(['Settings' => $page->settings]);

// Set the current action.
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view';

switch ($action) {
	case 'submit':
		if ($_POST['id'] == '') {
			// Add a new mgr poster.
			$relPosters->addPoster($_POST['poster']);
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
			$poster = [
				'id'     => $_GET['id'],
				'poster' => $_GET['poster']
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
