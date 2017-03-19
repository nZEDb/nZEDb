<?php
require_once realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'smarty.php');

use nzedb\Releases;

$page     = new AdminPage();
$page->title = "Release List";

$releases = new Releases(['Settings' => $page->settings]);

// TODO modelise.
$count = $releases->getCount();

$offset = isset($_REQUEST["offset"]) ? $_REQUEST["offset"] : 0;

$releaseList = $releases->getRange($offset, ITEMS_PER_PAGE);
$page->smarty->assign('releaselist', $releaseList);

$pageno = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
$page->smarty->assign(
	[
		'pagecurrent'      => (int)$pageno,
		'pagemaximum'      => (int)($count / ITEMS_PER_PAGE) + 1,
		'pager'            => $page->smarty->fetch("paginate.tpl"),
		'pagerquerybase'   => WWW_TOP . "/release-list.php?offset=",
		'pagerquerysuffix' => '',
		'pagertotalitems'  => $count,
	]
);

$page->content = $page->smarty->fetch('release-list.tpl');
$page->render();
