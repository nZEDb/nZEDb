<?php
require_once './config.php';

use nzedb\ReleaseComments;

$page     = new AdminPage();
$releases = new ReleaseComments($page->settings);

$page->title = "Comments List";

$commentcount = $releases->getCommentCount();
$offset       = isset($_REQUEST["offset"]) ? $_REQUEST["offset"] : 0;
$page->smarty->assign('pagertotalitems', $commentcount);
$page->smarty->assign('pageroffset', $offset);
$page->smarty->assign('pageritemsperpage', ITEMS_PER_PAGE);
$page->smarty->assign('pagerquerybase', WWW_TOP . "/comments-list.php?offset=");
$pager = $page->smarty->fetch("pager.tpl");
$page->smarty->assign('pager', $pager);

$commentslist = $releases->getCommentsRange($offset, ITEMS_PER_PAGE);
$page->smarty->assign('commentslist', $commentslist);

$page->content = $page->smarty->fetch('comments-list.tpl');
$page->render();
