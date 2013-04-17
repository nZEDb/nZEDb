<?php
require_once(WWW_DIR."/lib/music.php");

$music = new Music;

if (!$users->isLoggedIn())
	$page->show403();
	
if (isset($_GET["id"]) && ctype_digit($_GET["id"]))
{
	$mus = $music->getMusicInfo($_GET['id']);
	
	if (!$mus)
		$page->show404();
	
	//$mov['actors'] = $movie->makeFieldLinks($mov, 'actors');
	//$mov['genre'] = $movie->makeFieldLinks($mov, 'genre');
	//$mov['director'] = $movie->makeFieldLinks($mov, 'director');
	
	$page->smarty->assign('music', $mus);

	$page->title = "Info for ".$mus['title'];
	$page->meta_title = "";
	$page->meta_keywords = "";
	$page->meta_description = "";
	$page->smarty->registerPlugin('modifier', 'ss', 'stripslashes');
	
	$modal = false;
	if (isset($_GET['modal'])) 
	{
		$modal = true;
		$page->smarty->assign('modal', true);
	}
	
	$page->content = $page->smarty->fetch('viewmusic.tpl');

	if ($modal)
		echo $page->content;
	else
		$page->render();
}

?>
