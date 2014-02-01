<?php
if (!$users->isLoggedIn()) {
	$page->show403();
}

$id = $_GET["id"] + 0;

$forum = new Forum();
if ($page->isPostBack()) {
	$forum->add($id, $users->currentUserId(), "", $_POST["addReply"]);
	header("Location:" . WWW_TOP . "/forumpost/" . $id . "#last");
	die();
}

$results = $forum->getPosts($id);
if (count($results) == 0) {
	header("Location:" . WWW_TOP . "/forum");
	die();
}

$page->meta_title = "Forum Post";
$page->meta_keywords = "view,forum,post,thread";
$page->meta_description = "View forum post";

$page->smarty->assign('results', $results);

$page->content = $page->smarty->fetch('forumpost.tpl');
$page->render();
