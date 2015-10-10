<?php
require_once './config.php';

use nzedb\Genres;

$page = new AdminPage();
$genres = new Genres(['Settings' => $page->settings]);

$page->title = "Music Genres";

$activeOnly = isset($_REQUEST['activeonly']);
$offset = isset($_REQUEST["offset"]) ? $_REQUEST["offset"] : 0;

$page->smarty->assign([
		'genrelist'         => $genres->getRange($offset, ITEMS_PER_PAGE, Genres::MUSIC_TYPE, $activeOnly),
		'pagertotalitems'   => $genres->getCount(Genres::MUSIC_TYPE, $activeOnly),
		'pageroffset'       => $offset,
		'pageritemsperpage' => ITEMS_PER_PAGE,
		'pagerquerysuffix'  => '',
		'pagerquerybase'    => WWW_TOP . "/musicgenre-list.php?" . ($activeOnly ? "activeonly=1&amp;" : '') . "offset="

	]
);
$page->smarty->assign('pager', $page->smarty->fetch('pager.tpl'));

$page->content = $page->smarty->fetch('musicgenre-list.tpl');
$page->render();
