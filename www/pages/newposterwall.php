<?php
if (!$users->isLoggedIn()) {
	$page->show403();
}

$releases = new Releases();
$contents = new Contents();

$getnewestmovies = $releases->getNewestMovies();
$page->smarty->assign('newestmovies', $getnewestmovies);

/* $getnewestconsole = $releases->getNewestConsole();
  $page->smarty->assign('newestconsole', $getnewestconsole);

  $getnewestmp3 = $releases->getnewestMP3s();
  $page->smarty->assign('newestmp3s', $getnewestmp3);

  $getnewestbooks = $releases->getNewestBooks();
  $page->smarty->assign('newestbooks', $getnewestbooks);

  $recent = $releases->getRecentlyAdded();
  $page->smarty->assign('recent', $recent);
 */
$page->content = $page->smarty->fetch('newposterwall.tpl');
$page->render();
