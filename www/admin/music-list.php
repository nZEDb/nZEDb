<?php
require_once './config.php';

use nzedb\Music;

$page = new AdminPage();
$m    = new Music(['Settings' => $page->settings]);

$page->title = "Music List";

$mcount = $m->getCount();

$offset = isset($_REQUEST["offset"]) ? $_REQUEST["offset"] : 0;
$page->smarty->assign('pagertotalitems', $mcount);
$page->smarty->assign('pageroffset', $offset);
$page->smarty->assign('pageritemsperpage', ITEMS_PER_PAGE);
$page->smarty->assign('pagerquerybase', WWW_TOP . "/music-list.php?offset=");
$pager = $page->smarty->fetch("pager.tpl");
$page->smarty->assign('pager', $pager);

$musiclist = $m->getRange($offset, ITEMS_PER_PAGE);

$page->smarty->assign('musiclist', $musiclist);

$page->content = $page->smarty->fetch('music-list.tpl');
$page->render();
