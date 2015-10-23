<?php

use nzedb\Category;
use nzedb\Releases;

$page = new AdminPage(true);
$releases = new Releases(['Settings' => $page->settings]);
$category = new Category(['Settings' => $page->settings]);

// Set the current action.
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

// Request is for id, but guid is actually being provided
if (isset($_REQUEST['id']) && is_array($_REQUEST['id'])) {
	$id = $_REQUEST['id'];
	//Get info for first guid to populate form
	$rel = $releases->getByGuid($_REQUEST['id'][0]);
} else	{
	$id = $rel = '';
}

$page->smarty->assign('action', $action);
$page->smarty->assign('idArr', $id);

switch ($action) {
	case 'doedit':
	case 'edit':
		$success = false;
		if ($action == 'doedit') {
			$success = $releases->updateMulti(
						$_POST["id"],
						$_POST["category"],
						$_POST["grabs"],
						$_POST["videosid"],
						$_POST["episodesid"],
						$_POST["anidbid"],
						$_POST['imdbid']);
		}
		$page->smarty->assign('release', $rel);
		$page->smarty->assign('success', $success);
		$page->smarty->assign('from', (isset($_POST['from']) ? $_POST['from'] : ''));
		$page->smarty->assign('catlist', $category->getForSelect(false));
		$page->content = $page->smarty->fetch('ajax_release-edit.tpl');
		echo $page->content;

		break;
	case 'dodelete':
		$is_guid = true;
		if (is_array($_GET['id'])) {
			if (is_numeric($_GET['id'][0])) {
				$is_guid = false;
			}
		} else {
			if (is_numeric($_GET['id'])) {
				$is_guid = false;
			}
		}
		$releases->deleteMultiple($_GET['id'], $is_guid);
		break;
	default:
		$page->show404();
		break;
}
