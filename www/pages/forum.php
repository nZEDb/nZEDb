<?php

use nzedb\Forum;

if (!$page->users->isLoggedIn()) {
	$page->show403();
}

$forum = new Forum(['Settings' => $page->settings]);

if ($page->isPostBack()) {
	$forum->add(0, $page->users->currentUserId(), $_POST["addSubject"], $_POST["addMessage"]);
	header("Location:" . WWW_TOP . "/forum");
	exit();
}

$offset = (isset($_REQUEST["offset"]) && ctype_digit($_REQUEST['offset'])) ? $_REQUEST["offset"] : 0;

$results = array();
$results = $forum->getBrowseRange($offset, ITEMS_PER_PAGE);

$page->smarty->assign('pagertotalitems', $forum->getBrowseCount());
$page->smarty->assign('pageroffset', $offset);
$page->smarty->assign('pageritemsperpage', ITEMS_PER_PAGE);
$page->smarty->assign('pagerquerybase', WWW_TOP . "/forum?offset=");
$page->smarty->assign('pagerquerysuffix', "#results");
$page->smarty->assign('privateprofiles', ($page->settings->getSetting('privateprofiles') == 1) ? true : false);

$pager = $page->smarty->fetch("pager.tpl");
$page->smarty->assign('pager', $pager);
$page->smarty->assign('results', $results);

$page->meta_title = "Forum";
$page->meta_keywords = "forum,chat,posts";
$page->meta_description = "Forum";

$page->content = $page->smarty->fetch('forum.tpl');
$page->render();
