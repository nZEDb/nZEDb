<?php

use nzedb\http\RSS;

$rss = new RSS(['Settings' => $page->settings]);

$page->title = "RSS Info";
$page->meta_title = "RSS Help Topics";
$page->meta_keywords = "view,nzb,api,details,help,json,rss,atom";
$page->meta_description = "View description of the site Nzb RSS.";

$firstShow = $rss->getFirstInstance('videos_id', 'releases', 'id');
$firstAni = $rss->getFirstInstance('anidbid', 'releases', 'id');

if (isset($firstShow['id'])) {
	$page->smarty->assign('show', $firstShow['id']);
} else {
	$page->smarty->assign('show', -1);
}

if (isset($firstAni['anidb'])) {
	$page->smarty->assign('anidb', $firstAni['id']);
} else {
	$page->smarty->assign('anidb', -1);
}

$page->content = $page->smarty->fetch('rssdesc.tpl');
$page->render();