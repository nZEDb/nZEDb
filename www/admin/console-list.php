<?php
require_once './config.php';

use nzedb\Console;

$page = new AdminPage();
$page->title = "Console List";

$con  = new Console(['Settings' => $page->settings]);

// TODO modelise.
$count = $con->getCount();

$consolelist = $con->getRange($offset, ITEMS_PER_PAGE);
$page->smarty->assign('consolelist', $consolelist);

$pageno = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
$page->smarty->assign(
	[
		'pagecurrent'		=> (int)$pageno,
		'pagemaximum'		=> (int)($count / ITEMS_PER_PAGE) + 1,
		'pager'				=> $page->smarty->fetch("paginate.tpl"),
		'pagerquerybase'	=> WWW_TOP . "/console-list.php?offset=",
		'pagerquerysuffix'	=> '',
		'pagertotalitems'	=> $count,
	]
);

$page->content = $page->smarty->fetch('console-list.tpl');
$page->render();
