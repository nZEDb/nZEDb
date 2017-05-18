<?php
require_once realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'smarty.php');

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
		'pagerquerybase'   => WWW_TOP . "/game-list.php?offset=",
		'pagerquerysuffix' => '',
		'pagertotalitems'  => $count,
	]
);
// Pager has to be set outside of main assign, or it will no recieve the scope of those variables.
$page->smarty->assign('pager', $page->smarty->fetch("paginate.tpl"));

$page->content = $page->smarty->fetch('game-list.tpl');
$page->render();
