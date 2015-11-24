<?php

require_once './config.php';

if (!isset($_GET['id'])) {
	header('Location: ' . WWW_TOP . '/musicgenre-list.php');
	exit();
}

use nzedb\Genres;

$page   = new AdminPage();
$genres = new Genres(['Settings' => $page->settings]);
$genre = ['id' => '', 'title' => '', 'disabled' => ''];

switch ((isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view')) {
	case 'submit':
		$ret = $genres->update($_POST["id"], $_POST["disabled"]);
		header("Location:" . WWW_TOP . "/musicgenre-list.php");
		break;

	case 'view':
	default:
		if (isset($_GET["id"])) {
			$page->title = "Music Genre Edit";
			$genre = $genres->getByID($_GET["id"]);
		}
		break;
}

$page->smarty->assign('genre', $genre);
$page->smarty->assign('status_ids', [Genres::STATUS_ENABLED, Genres::STATUS_DISABLED]);
$page->smarty->assign('status_names', ['No', 'Yes']);

$page->content = $page->smarty->fetch('musicgenre-edit.tpl');
$page->render();
