<?php
require_once './config.php';

use nzedb\Movie;

$page  = new AdminPage();
$movie = new Movie(['Settings' => $page->settings]);

$page->title = "Movie List";

$movcount = $movie->getCount();

$offset = isset($_REQUEST["offset"]) ? $_REQUEST["offset"] : 0;
$page->smarty->assign('pagertotalitems', $movcount);
$page->smarty->assign('pageroffset', $offset);
$page->smarty->assign('pageritemsperpage', ITEMS_PER_PAGE);
$page->smarty->assign('pagerquerybase', WWW_TOP . "/movie-list.php?offset=");
$pager = $page->smarty->fetch("pager.tpl");
$page->smarty->assign('pager', $pager);

$movielist = $movie->getRange($offset, ITEMS_PER_PAGE);
$page->smarty->assign('movielist', $movielist);

$page->content = $page->smarty->fetch('movie-list.tpl');
$page->render();
