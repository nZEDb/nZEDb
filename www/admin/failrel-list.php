<?php
require_once './config.php';

use nzedb\DnzbFailures;

$page = new AdminPage();
$page->title = "Failed Releases List";

$failed = new DnzbFailures(['Settings' => $page->settings]);

// TODO modelise.
$count = $failed->getCount();

$frellist = $failed->getFailedRange($offset, ITEMS_PER_PAGE);
$page->smarty->assign('releaselist', $frellist);

$pageno = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
$page->smarty->assign(
	[
		'pagecurrent'		=> (int)$pageno,
		'pagemaximum'		=> (int)($count / ITEMS_PER_PAGE) + 1,
		'pager'				=> $page->smarty->fetch("paginate.tpl"),
		'pagerquerybase'	=> WWW_TOP . "/failrel-list.php?offset=",
		'pagerquerysuffix'	=> '',
		'pagertotalitems'	=> $count,
	]
);

$page->content = $page->smarty->fetch('failrel-list.tpl');
$page->render();
