<?php
if (!$page->users->isLoggedIn()) {
	$page->show403();
}

$page->title = "My Movies";
$page->meta_title = "My Movies";
$page->meta_keywords = "couch,potato,movie,add";
$page->meta_description = "Manage Your Movies";

$page->content = $page->smarty->fetch('mymovies.tpl');
$page->render();