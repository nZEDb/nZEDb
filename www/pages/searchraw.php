<?php
require_once(WWW_DIR."/lib/binaries.php");

$binaries = new Binaries;

if (!$users->isLoggedIn())
	$page->show403();

$page->meta_title = "Search Binaries";
$page->meta_keywords = "search,binaries,binsearch,nzb,description,details";
$page->meta_description = "Search for Binaries";

$results = array();
$searchStr = '';

//TODO: bug here in javascript cludge to turn request in a get, rather than post is losing + signs in search query.
if (isset($_REQUEST["id"]))
{
	$searchStr = $_REQUEST["id"];
	$results = $binaries->search($searchStr, 1000, $page->userdata["categoryexclusions"]);
}

$page->smarty->assign('search', $searchStr);
$page->smarty->assign('results', $results);

$page->content = $page->smarty->fetch('searchraw.tpl');
$page->render();

?>
