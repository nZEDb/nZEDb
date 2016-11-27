<?php

use nzedb\Forum;

if (!$page->users->isLoggedIn()) {
	$page->show403();
}
$forum = new Forum();
$id = $_GET['id'] + 0;


if (isset($id) && !empty($_POST['addMessage'])) {
	$parent = $forum->getPost($id);
	$forum->editPost($id, $_POST['addMessage'], $page->users->currentUserId());
	if($parent['parentid'] != 0) {
		header("Location:" . WWW_TOP . "/forumpost/" . $parent['parentid'] . "#last");
	} else {
		header("Location:" . WWW_TOP . "/forumpost/" . $id);
	}
}

$result = $forum->getPost($id);

$page->meta_title = "Edit forum Post";
$page->meta_keywords = "edit, view,forum,post,thread";
$page->meta_description = "Edit forum post";

$page->smarty->assign('result', $result);

$page->content = $page->smarty->fetch('post_edit.tpl');
$page->render();
