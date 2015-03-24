<?php
require_once './config.php';

$page = new AdminPage();
$genres = new Genres(['Settings' => $page->settings]);

$page->title = "Music Genres";

$activeOnly = isset($_REQUEST['activeonly']);

$count = $genres->getCount(Genres::MUSIC_TYPE, $activeOnly);

$offset = isset($_REQUEST["offset"]) ? $_REQUEST["offset"] : 0;

$page->smarty->assign('pagertotalitems', $count);
$page->smarty->assign('pageroffset', $offset);
$page->smarty->assign('pageritemsperpage', ITEMS_PER_PAGE);

if ($activeOnly) {
	$activeOnlySearch = "activeonly=1&amp;";
} else {
	$activeOnlySearch = "";
}

$page->smarty->assign('pagerquerybase',
					  WWW_TOP . "/musicgenre-list.php?" . $activeOnlySearch . "offset=");

$pager = $page->smarty->fetch('pager.tpl');
$page->smarty->assign('pager', $pager);

$genrelist = $genres->getRange($offset, ITEMS_PER_PAGE, Genres::MUSIC_TYPE, $activeOnly);

$page->smarty->assign('genrelist', $genrelist);

$page->content = $page->smarty->fetch('musicgenre-list.tpl');
$page->render();
