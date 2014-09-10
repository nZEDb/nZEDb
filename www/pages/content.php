<?php
require_once './config.php';

$contents = new Contents(['Settings' => $page->settings]);

$role = 0;
if ($page->userdata != null) {
	$role = $page->userdata["role"];
}

$contentid = 0;
if (isset($_GET["id"])) {
	$contentid = $_GET["id"];
}

$request = false;
if (isset($_REQUEST['page'])) {
	$request = $_REQUEST['page'];
}

if ($contentid == 0 && $request == 'content') {
	$content = $contents->getAllButFront();
	$page->smarty->assign('front', false);
	$page->meta_title = 'Contents page';
	$page->meta_keywords = 'contents';
	$page->meta_description = 'This is the contents page.';
} else if ($contentid != 0 && $request !== false) {
	$content = array($contents->getByID($contentid, $role));
	$page->smarty->assign('front', false);
	$page->meta_title = 'Contents page';
	$page->meta_keywords = 'contents';
	$page->meta_description = 'This is the contents page.';
} else {
	$content = $contents->getFrontPage();
	$index = $contents->getIndex();
	$page->smarty->assign('front', true);
	$page->meta_title = $index->title;
	$page->meta_keywords = $index->metakeywords;
	$page->meta_description = $index->metadescription;
}

if ($content == null) {
	$page->show404();
}

$page->smarty->assign('content', $content);


$page->content = $page->smarty->fetch('content.tpl');
$page->render();