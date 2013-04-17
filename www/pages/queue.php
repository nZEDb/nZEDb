<?php
require_once(WWW_DIR."/lib/sabnzbd.php");

if (!$users->isLoggedIn())
	$page->show403();

$sab = new SABnzbd($page);

if (empty($sab->url))
	$page->show404();

if (empty($sab->apikey))
	$page->show404();

if (isset($_REQUEST["del"]))
	$sab->delFromQueue($_REQUEST['del']);

$page->smarty->assign('sabserver',$sab->url);	
$page->title = "Your Download Queue";
$page->meta_title = "View Sabnzbd Queue";
$page->meta_keywords = "view,sabznbd,queue";
$page->meta_description = "View Sabnzbd Queue";

$page->content = $page->smarty->fetch('viewqueue.tpl');
$page->render();

?>