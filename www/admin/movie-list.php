<?php
require_once realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'smarty.php');

use nzedb\Movie;

$page  = new AdminPage();
$page->title = "Movie List";

$movie = new Movie(['Settings' => $page->settings]);

// TODO modelise.
$count = $movie->getCount();

$movietitle = (isset($_REQUEST['movietitle']) && !empty($_REQUEST['movietitle']) ?
	$_REQUEST['movietitle'] : '');
$page->smarty->assign('movietitle', $movietitle);
$offset = isset($_REQUEST["offset"]) ? $_REQUEST["offset"] : 0;
$page->smarty->assign('pageroffset', $offset);

$movielist = $movie->getRange($offset, ITEMS_PER_PAGE, $movietitle);
$page->smarty->assign('movielist', $movielist);

$pageno = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
$page->smarty->assign(
	[
		'pagecurrent'      => (int)$pageno,
		'pagemaximum'      => (int)($count / ITEMS_PER_PAGE) + 1,
		'pagerquerybase'   => WWW_TOP . "/movie-list.php?offset=",
		'pagerquerysuffix' => '',
		'pagertotalitems'  => $count,
	]
);
// Pager has to be set outside of main assign, or it will no recieve the scope of those variables.
$page->smarty->assign('pager', $page->smarty->fetch("paginate.tpl"));

$page->content = $page->smarty->fetch('movie-list.tpl');
$page->render();
