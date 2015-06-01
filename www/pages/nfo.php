<?php

use nzedb\Releases;
use nzedb\utility\Utility;

if (!$page->users->isLoggedIn()) {
	$page->show403();
}

$releases = new Releases(['Settings' => $page->settings]);

if (isset($_GET["id"])) {
	$rel = $releases->getByGuid($_GET["id"]);

	if (!$rel) {
		$page->show404();
	}

	$nfo = $releases->getReleaseNfo($rel['id']);
	$nfo['nfoUTF'] = Utility::cp437toUTF($nfo['nfo']);

	$page->smarty->assign('rel', $rel);
	$page->smarty->assign('nfo', $nfo);

	$page->title = "NFO File";
	$page->meta_title = "View Nfo";
	$page->meta_keywords = "view,nzb,nfo,description,details";
	$page->meta_description = "View Nfo File";

	$modal = false;
	if (isset($_GET['modal'])) {
		$modal = true;
		$page->smarty->assign('modal', true);
	}

	$page->content = $page->smarty->fetch('viewnfo.tpl');

	if ($modal) {
		echo $page->content;
	} else {
		$page->trimWhiteSpace = false;
		$page->render();
	}
}
