<?php
require_once './config.php';

use nzedb\XXX;

$page     = new AdminPage();
$xxxmovie = new XXX(['Settings' => $page->settings]);

$page->title = "XXX Movie List";

$xxxcount = $xxxmovie->getCount();

$offset = isset($_REQUEST["offset"]) ? $_REQUEST["offset"] : 0;
$page->smarty->assign('pagertotalitems', $xxxcount);
$page->smarty->assign('pageroffset', $offset);
$page->smarty->assign('pageritemsperpage', ITEMS_PER_PAGE);
$page->smarty->assign('pagerquerybase', WWW_TOP . "/xxx-list.php?offset=");
$pager = $page->smarty->fetch("pager.tpl");
$page->smarty->assign('pager', $pager);

$xxxmovielist = $xxxmovie->getRange($offset, ITEMS_PER_PAGE);
foreach ($xxxmovielist as $key => $mov) {
	$xxxmovielist[$key]['hastrailer'] = (!empty($mov['trailers'])) ? 1 : 0;
}
$page->smarty->assign('xxxmovielist', $xxxmovielist);

$page->content = $page->smarty->fetch('xxx-list.tpl');
$page->render();
