<?php
require_once './config.php';

use nzedb\Music;

$page = new AdminPage();
$page->title = "Music List";

$music    = new Music(['Settings' => $page->settings]);

// TODO modelise.
$count = $music->getCount();

$offset = isset($_REQUEST["offset"]) ? $_REQUEST["offset"] : 0;
$page->smarty->assign('pageroffset', $offset);

$musiclist = $music->getRange($offset, ITEMS_PER_PAGE);

$page->smarty->assign('musiclist', $musiclist);

$pageno = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
$page->smarty->assign(
	[
		'pagecurrent'      => (int)$pageno,
		'pagemaximum'      => (int)($count / ITEMS_PER_PAGE) + 1,
		'pager'            => $page->smarty->fetch("pagination.tpl"),
		'pagerquerybase'   => WWW_TOP . "/music-list.php?offset=",
		'pagerquerysuffix' => '',
		'pagertotalitems'  => $count,
	]
);

$page->content = $page->smarty->fetch('music-list.tpl');
$page->render();
