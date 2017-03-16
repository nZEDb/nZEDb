<?php
require_once './config.php';

use nzedb\Genres;
use nzedb\Category;

$page = new AdminPage();
$page->title = "Music Genres";

$genres = new Genres(['Settings' => $page->settings]);

$activeOnly = isset($_REQUEST['activeonly']);
$offset = isset($_REQUEST["offset"]) ? $_REQUEST["offset"] : 0;

$page->smarty->assign('genrelist',
	$genres->getRange($offset, ITEMS_PER_PAGE, Category::MUSIC_ROOT, $activeOnly)
);

// TODO modelise.
$count = $genres->getCount(Category::MUSIC_ROOT, $activeOnly);

$pageno = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
$page->smarty->assign(
	[
		'pagecurrent'      => (int)$pageno,
		'pagemaximum'      => (int)($count / ITEMS_PER_PAGE) + 1,
		'pager'            => $page->smarty->fetch("pagination.tpl"),
		'pagerquerybase'   => WWW_TOP .
			"/musicgenre-list.php?" .
			($activeOnly ? "activeonly=1&amp;" : '') .
			"offset=",
		'pagerquerysuffix' => '',
		'pagertotalitems'  => $count,
	]
);

$page->content = $page->smarty->fetch('musicgenre-list.tpl');
$page->render();
