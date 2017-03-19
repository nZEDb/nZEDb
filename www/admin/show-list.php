<?php
require_once realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'smarty.php');

use nzedb\Videos;

$page   = new AdminPage();
$tv = new Videos(['Settings' => $page->settings]);

$page->title = "TV Shows List";

$tvshowname = (isset($_REQUEST['showname']) && !empty($_REQUEST['showname']) ? $_REQUEST['showname'] : '');
$offset = isset($_REQUEST["offset"]) ? $_REQUEST["offset"] : 0;

$page->smarty->assign([
		'showname'          => $tvshowname,
		'tvshowlist'        => $tv->getRange($offset, ITEMS_PER_PAGE, $tvshowname),
		'pageroffset'       => $offset,
	]
);

// TODO modelise.
$count = $tv->getCount($tvshowname);

$showName = $tvshowname != '' ? 'showname=' . $tvshowname . '&amp;' : '';

$pageno = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
$page->smarty->assign(
	[
		'pagecurrent'      => (int)$pageno,
		'pagemaximum'      => (int)($count / ITEMS_PER_PAGE) + 1,
		'pager'            => $page->smarty->fetch("paginate.tpl"),
		'pagerquerybase'   => WWW_TOP . "/show-list.php?" . $showName . "&offset=",
		'pagerquerysuffix' => '',
		'pagertotalitems'  => $count,
	]
);

$page->content = $page->smarty->fetch('show-list.tpl');
$page->render();
