<?php
require_once './config.php';

use nzedb\Releases;
use nzedb\NZB;
use nzedb\utility\Misc;

$page = new AdminPage;

if (!$page->users->isLoggedIn()) {
	$page->show403();
}

if (isset($_GET['id'])) {
	$releases = new Releases(['Settings' => $page->settings]);
	$release  = $releases->getByGuid($_GET['id']);
	if ($release === false) {
		$page->show404();
	}

	$nzb     = new NZB($page->settings);
	$nzbPath = $nzb->getNZBPath($_GET['id']);
	if (!file_exists($nzbPath)) {
		$page->show404();
	}

	$nzbFile = Misc::unzipGzipFile($nzbPath);

	$files = $nzb->nzbFileList($nzbFile);

	$page->smarty->assign('release', $release);
	$page->smarty->assign('files', $files);

	$page->title            = "File List";
	$page->meta_title       = "View Nzb file list";
	$page->meta_keywords    = "view,nzb,file,list,description,details";
	$page->meta_description = "View Nzb File List";

	$page->content = $page->smarty->fetch('release-files.tpl');
	$page->render();
}
