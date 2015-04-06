<?php
require_once './config.php';

$page     = new AdminPage();
$releases = new Releases(['Settings' => $page->settings]);

$page->title = "Release List";

$releaseCount = $releases->getCount();

$offset = isset($_REQUEST["offset"]) ? $_REQUEST["offset"] : 0;
$page->smarty->assign('pagertotalitems', $releaseCount);
$page->smarty->assign('pageroffset', $offset);
$page->smarty->assign('pageritemsperpage', ITEMS_PER_PAGE);
$page->smarty->assign('pagerquerybase', WWW_TOP . "/release-list.php?offset=");
$pager = $page->smarty->fetch("pager.tpl");
$page->smarty->assign('pager', $pager);

$releaseList = $releases->getRange($offset, ITEMS_PER_PAGE);
$page->smarty->assign('releaselist', $releaseList);

$page->content = $page->smarty->fetch('release-list.tpl');
$page->render();
