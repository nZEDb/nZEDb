<?php
require_once './config.php';

$page  = new AdminPage();
$movie = new Movie(['Settings' => $page->settings]);
$id    = 0;

$page->title = "Movie Add";

if (isset($_REQUEST['id']) && ctype_digit($_REQUEST['id']) && strlen($_REQUEST['id']) == 7) {
	$id       = $_REQUEST['id'];
	$movCheck = $movie->getMovieInfo($id);
	if (!$movCheck || (isset($_REQUEST['update']) && $_REQUEST['update'] == 1)) {
		if ($movie->updateMovieInfo($id)) {
			header("Location:" . WWW_TOP . "/movie-list.php");
			die();
		}
	}
}

$page->content = $page->smarty->fetch('movie-add.tpl');
$page->render();
