<?php

require_once("config.php");
require_once(WWW_DIR."/lib/adminpage.php");
require_once(WWW_DIR."/lib/genres.php");

#$page = new AdminPage();

$genres = new Genres();

#$page->title = "Music Genres";

$genrelist = $genres->getGenres(Genres::MUSIC_TYPE, false);

foreach ($genrelist as $genre) {
echo $genre."\n";
}
echo "hello\n";
#$page->smarty->assign('genrelist',$genrelist);	

#$page->content = $page->smarty->fetch('musicgenre-list.tpl');
#$page->render();

?>
