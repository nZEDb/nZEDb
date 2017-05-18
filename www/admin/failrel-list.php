<?php
require_once realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'smarty.php');

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
		'pagerquerybase'	=> WWW_TOP . "/failrel-list.php?offset=",
		'pagerquerysuffix'	=> '',
		'pagertotalitems'	=> $count,
	]
);
// Pager has to be set outside of main assign, or it will no recieve the scope of those variables.
$page->smarty->assign('pager', $page->smarty->fetch("paginate.tpl"));

$page->content = $page->smarty->fetch('failrel-list.tpl');
$page->render();
