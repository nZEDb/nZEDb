<?php
require_once './config.php';

$page = new AdminPage();
$game = new Games(['Settings' => $page->settings]);

$page->title = "Game List";

$gamecount = $game->getCount();

$offset = isset($_REQUEST["offset"]) ? $_REQUEST["offset"] : 0;
$page->smarty->assign('pagertotalitems',$gamecount);
$page->smarty->assign('pageroffset',$offset);
$page->smarty->assign('pageritemsperpage',ITEMS_PER_PAGE);
$page->smarty->assign('pagerquerybase', WWW_TOP."/game-list.php?offset=");
$pager = $page->smarty->fetch("pager.tpl");
$page->smarty->assign('pager', $pager);

$gamelist = $game->getRange($offset, ITEMS_PER_PAGE);

$page->smarty->assign('gamelist',$gamelist);

$page->content = $page->smarty->fetch('game-list.tpl');
$page->render();
