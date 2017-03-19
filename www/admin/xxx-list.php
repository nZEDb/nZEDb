<?php
require_once realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'smarty.php');

use nzedb\XXX;

$page     = new AdminPage();
$page->title = "XXX Movie List";

$xxxmovie = new XXX(['Settings' => $page->settings]);

// TODO modelise.
$count = $xxxmovie->getCount();

$offset = isset($_REQUEST["offset"]) ? $_REQUEST["offset"] : 0;
$page->smarty->assign('pageroffset', $offset);

$xxxmovielist = $xxxmovie->getRange($offset, ITEMS_PER_PAGE);
foreach ($xxxmovielist as $key => $mov) {
	$xxxmovielist[$key]['hastrailer'] = (!empty($mov['trailers'])) ? 1 : 0;
}
$page->smarty->assign('xxxmovielist', $xxxmovielist);

$pageno = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
$page->smarty->assign(
	[
		'pagecurrent'      => (int)$pageno,
		'pagemaximum'      => (int)($count / ITEMS_PER_PAGE) + 1,
		'pager'            => $page->smarty->fetch("paginate.tpl"),
		'pagerquerybase'   => WWW_TOP . "/xxx-list.php?offset=",
		'pagerquerysuffix' => '',
		'pagertotalitems'  => $count,
	]
);

$page->content = $page->smarty->fetch('xxx-list.tpl');
$page->render();
