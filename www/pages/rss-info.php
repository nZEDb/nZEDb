<?php

$page->title = "RSS Info";
$page->meta_title = "RSS Help Topics";
$page->meta_keywords = "view,nzb,api,details,help,json,rss,atom";
$page->meta_description = "View description of the site Nzb RSS.";

$firstShow = $page->settings->queryOneRow('SELECT id FROM videos ORDER BY id ASC');
$firstAni = $page->settings->queryOneRow('SELECT anidbid FROM releases ORDER BY anidbid ASC');

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