<?php
require_once './config.php';

$contents = new Contents();

$role = 0;
if ($page->userdata != null) {
	$role = $page->userdata["role"];
}

$contentid = 0;
if (isset($_GET["id"])) {
	$contentid = $_GET["id"];
}

if ($contentid == 0) {
	$content = $contents->getFrontPage();
} else {
	$content = $contents->getByID($contentid, $role);
}

if ($content == null) {
	$page->show404();
}

$page->smarty->assign('content', $content);
if ($contentid == 0) {
	$index = $contents->getIndex();
	$page->meta_title = $index->title;
	$page->meta_keywords = $index->metakeywords;
	$page->meta_description = $index->metadescription;
} else {
	$page->meta_title = $content->title;
	$page->meta_keywords = $content->metakeywords;
	$page->meta_description = $content->metadescription;
}

$page->content = $page->smarty->fetch('content.tpl');
$page->render();
