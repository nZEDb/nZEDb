<?php

require_once("config.php");
require_once(WWW_DIR."/lib/adminpage.php");
require_once(WWW_DIR."/lib/releases.php");

$page = new AdminPage();

$releases = new Releases();
$num = (isset($_GET["id"])) ? $releases->removeAnidbIdFromReleases($_GET["id"]) : 0;

$page->smarty->assign('numtv',$num);	

$page->title = "Remove anidbID from Releases";
$page->content = $page->smarty->fetch('anidb-remove.tpl');
$page->render();

?>
