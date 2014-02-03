<?php
require_once './config.php';
//require_once nZEDb_LIB . 'adminpage.php';




$page = new AdminPage;
$users = new Users;

if (!$users->isLoggedIn())
	$page->show403();

if (isset($_GET["id"]))
{
	$releases = new Releases;
	$rel = $releases->getByGuid($_GET["id"]);
	if (!$rel)
		$page->show404();

	$binaries = new Binaries;
	$data = $binaries->getForReleaseId($rel["id"]);

	$page->smarty->assign('rel', $rel);
	$page->smarty->assign('binaries', $data);

	$page->title = "File List";
	$page->meta_title = "View Nzb file list";
	$page->meta_keywords = "view,nzb,file,list,description,details";
	$page->meta_description = "View Nzb File List";

	$page->content = $page->smarty->fetch('release-files.tpl');
	$page->render();
}

?>
