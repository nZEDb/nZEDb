<?php
require_once './config.php';

use nzedb\Games;

$page = new AdminPage();
$page->title = "Game List";

$game = new Games(['Settings' => $page->settings]);

// TODO modelise.
$count = $game->getCount();

$offset = isset($_REQUEST["offset"]) ? $_REQUEST["offset"] : 0;
$gamelist = $game->getRange($offset, ITEMS_PER_PAGE);

$page->smarty->assign('gamelist', $gamelist);

$pageno = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
$page->smarty->assign(
	[
		'pagecurrent'      => (int)$pageno,
		'pagemaximum'      => (int)($count / ITEMS_PER_PAGE) + 1,
		'pager'            => $page->smarty->fetch("paginate.tpl"),
		'pagerquerybase'   => WWW_TOP . "/game-list.php?offset=",
		'pagerquerysuffix' => '',
		'pagertotalitems'  => $count,
	]
);

$page->content = $page->smarty->fetch('game-list.tpl');
$page->render();
