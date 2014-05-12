<?php
require_once './config.php';

$page = new AdminPage;
$users = new Users;

if (!$users->isLoggedIn()) {
	$page->show403();
}

if (isset($_GET['id'])) {
	$releases = new Releases();
	$release = $releases->getByGuid($_GET['id']);
	if ($release === false) {
		$page->show404();
	}

	$nzb = new NZB();
	$nzbPath = $nzb->getNZBPath($_GET['id']);
	if (!file_exists($nzbPath)) {
		$page->show404();
	}

	ob_start();
	@readgzfile($nzbPath);
	$nzbFile = ob_get_contents();
	ob_end_clean();

	$files = $nzb->nzbFileList($nzbFile);

	$page->smarty->assign('release', $release);
	$page->smarty->assign('files', $files);

	$page->title = "File List";
	$page->meta_title = "View Nzb file list";
	$page->meta_keywords = "view,nzb,file,list,description,details";
	$page->meta_description = "View Nzb File List";

	$page->content = $page->smarty->fetch('release-files.tpl');
	$page->render();
}