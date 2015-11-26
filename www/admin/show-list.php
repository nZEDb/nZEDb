<?php
require_once './config.php';

use nzedb\Videos;

$page   = new AdminPage();
$tv = new Videos(['Settings' => $page->settings]);

$page->title = "TV Shows List";

$tvshowname = (isset($_REQUEST['showname']) && !empty($_REQUEST['showname']) ? $_REQUEST['showname'] : '');
$offset = isset($_REQUEST["offset"]) ? $_REQUEST["offset"] : 0;

$page->smarty->assign([
		'showname'          => $tvshowname,
		'tvshowlist'        => $tv->getRange($offset, ITEMS_PER_PAGE, $tvshowname),
		'pagertotalitems'   => $tv->getCount($tvshowname),
		'pageroffset'       => $offset,
		'pageritemsperpage' => ITEMS_PER_PAGE,
		'pagerquerysuffix'  => '',
		'pagerquerybase'    => (WWW_TOP . "/show-list.php?" .
		($tvshowname != '' ? 'showname=' . $tvshowname . '&amp;' : '') . "&offset="
		)
	]
);
$page->smarty->assign('pager', $page->smarty->fetch("pager.tpl"));

$page->content = $page->smarty->fetch('show-list.tpl');
$page->render();
