<?php

use nzedb\XXX;

if (!$page->users->isLoggedIn()) {
	$page->show403();
}

if (isset($_GET['modal']) && isset($_GET["id"]) && ctype_digit($_GET["id"])) {
	$movie = new XXX(['Settings' => $page->settings]);
	$mov = $movie->getXXXInfo($_GET['id']);

	if (!$mov) {
		$page->show404();
	}

	$mov['actors'] = $movie->makeFieldLinks($mov, 'actors');
	$mov['genre'] = $movie->makeFieldLinks($mov, 'genre');
	$mov['director'] = $movie->makeFieldLinks($mov, 'director');

	$page->smarty->assign(['movie' => $mov, 'modal' => true]);

	$page->title = "Info for " . $mov['title'];
	$page->meta_title = "";
	$page->meta_keywords = "";
	$page->meta_description = "";
	$page->smarty->registerPlugin('modifier', 'ss', 'stripslashes');

	if (isset($_GET['modal']))
	{
		$page->content = $page->smarty->fetch('viewxxx.tpl');
		$page->smarty->assign('modal', true);
		echo $page->content;
	}
	else
	{
		$page->content = $page->smarty->fetch('viewxxxfull.tpl');
		$page->render();
	}
} else {
	$page->render();
}
