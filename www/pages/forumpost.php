<?php

use nzedb\Forum;

if (!$page->users->isLoggedIn()) {
	$page->show403();
}

$id = $_GET["id"] + 0;

$forum = new Forum(['Settings' => $page->settings]);
if ($page->isPostBack()) {
	$forum->add($id, $page->users->currentUserId(), "", $_POST["addReply"]);
	header("Location:" . WWW_TOP . "/forumpost/" . $id . "#last");
	exit();
}

$results = $forum->getPosts($id);
if (count($results) == 0) {
	header("Location:" . WWW_TOP . "/forum");
	exit();
}

$page->meta_title = "Forum Post";
$page->meta_keywords = "view,forum,post,thread";
$page->meta_description = "View forum post";

$page->smarty->assign('results', $results);
$page->smarty->assign('privateprofiles', ($page->settings->getSetting('privateprofiles') == 1) ? true : false);

$page->content = $page->smarty->fetch('forumpost.tpl');
$page->render();
