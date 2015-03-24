<?php
require_once './config.php';

$page = new AdminPage();
$con  = new Console(['Settings' => $page->settings]);

$page->title = "Console List";

$concount = $con->getCount();

$offset = isset($_REQUEST["offset"]) ? $_REQUEST["offset"] : 0;
$page->smarty->assign('pagertotalitems', $concount);
$page->smarty->assign('pageroffset', $offset);
$page->smarty->assign('pageritemsperpage', ITEMS_PER_PAGE);
$page->smarty->assign('pagerquerybase', WWW_TOP . "/console-list.php?offset=");
$pager = $page->smarty->fetch("pager.tpl");
$page->smarty->assign('pager', $pager);

$consolelist = $con->getRange($offset, ITEMS_PER_PAGE);

$page->smarty->assign('consolelist', $consolelist);

$page->content = $page->smarty->fetch('console-list.tpl');
$page->render();
