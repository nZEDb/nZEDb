<?php
if (!$users->isLoggedIn()) {
	$page->show403();
}

$sab = new SABnzbd($page);

if (empty($sab->url)) {
	$page->show404();
}

if (empty($sab->apikey)) {
	$page->show404();
}

if (isset($_REQUEST["del"])) {
	$sab->delFromQueue($_REQUEST['del']);
}

if (isset($_REQUEST["pause"])) {
	$sab->pauseFromQueue($_REQUEST['pause']);
}

if (isset($_REQUEST["resume"])) {
	$sab->resumeFromQueue($_REQUEST['resume']);
}

if (isset($_REQUEST["pall"])) {
	$sab->pauseAll($_REQUEST['pall']);
}

if (isset($_REQUEST["rall"])) {
	$sab->resumeAll($_REQUEST['rall']);
}

$page->smarty->assign('sabserver', $sab->url);
$page->title = "Your Download Queue";
$page->meta_title = "View Sabnzbd Queue";
$page->meta_keywords = "view,sabznbd,queue";
$page->meta_description = "View Sabnzbd Queue";

$page->content = $page->smarty->fetch('viewqueue.tpl');
$page->render();
