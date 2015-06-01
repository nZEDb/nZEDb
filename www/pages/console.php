<?php

use nzedb\Category;
use nzedb\Console;
use nzedb\Genres;

if (!$page->users->isLoggedIn()) {
	$page->show403();
}

$console = new Console(['Settings' => $page->settings]);
$cat = new Category(['Settings' => $page->settings]);
$gen = new Genres(['Settings' => $page->settings]);

$concats = $cat->getChildren(Category::CAT_PARENT_GAME);
$ctmp = array();
foreach ($concats as $ccat) {
	$ctmp[$ccat['id']] = $ccat;
}
$category = Category::CAT_PARENT_GAME;
if (isset($_REQUEST["t"]) && array_key_exists($_REQUEST['t'], $ctmp)) {
	$category = $_REQUEST["t"] + 0;
}

$catarray = array();
$catarray[] = $category;

$page->smarty->assign('catlist', $ctmp);
$page->smarty->assign('category', $category);

$browsecount = $console->getConsoleCount($catarray, -1, $page->userdata["categoryexclusions"]);

$offset = (isset($_REQUEST["offset"]) && ctype_digit($_REQUEST['offset'])) ? $_REQUEST["offset"] : 0;
$ordering = $console->getConsoleOrdering();
$orderby = isset($_REQUEST["ob"]) && in_array($_REQUEST['ob'], $ordering) ? $_REQUEST["ob"] : '';

$results = $consoles = array();
$results = $console->getConsoleRange($catarray, $offset, ITEMS_PER_COVER_PAGE, $orderby, $page->userdata["categoryexclusions"]);

$maxwords = 50;
foreach ($results as $result) {
	if (!empty($result['review'])) {
		$words = explode(' ', $result['review']);
		if (sizeof($words) > $maxwords) {
			$newwords = array_slice($words, 0, $maxwords);
			$result['review'] = implode(' ', $newwords) . '...';
		}
	}
	$consoles[] = $result;
}

$platform = (isset($_REQUEST['platform']) && !empty($_REQUEST['platform'])) ? stripslashes($_REQUEST['platform']) : '';
$page->smarty->assign('platform', $platform);

$title = (isset($_REQUEST['title']) && !empty($_REQUEST['title'])) ? stripslashes($_REQUEST['title']) : '';
$page->smarty->assign('title', $title);

$genres = $gen->getGenres(Genres::CONSOLE_TYPE, true);
$tmpgnr = array();
foreach ($genres as $gn) {
	$tmpgnr[$gn['id']] = $gn['title'];
}
$genre = (isset($_REQUEST['genre']) && array_key_exists($_REQUEST['genre'], $tmpgnr)) ? $_REQUEST['genre'] : '';
$page->smarty->assign('genres', $genres);
$page->smarty->assign('genre', $genre);

$browseby_link = '&amp;title=' . $title . '&amp;platform=' . $platform;

$page->smarty->assign('pagertotalitems', $browsecount);
$page->smarty->assign('pageroffset', $offset);
$page->smarty->assign('pageritemsperpage', ITEMS_PER_COVER_PAGE);
$page->smarty->assign('pagerquerybase', WWW_TOP . "/console?t=" . $category . $browseby_link . "&amp;ob=" . $orderby . "&amp;offset=");
$page->smarty->assign('pagerquerysuffix', "#results");

$pager = $page->smarty->fetch("pager.tpl");
$page->smarty->assign('pager', $pager);

if ($category == -1) {
	$page->smarty->assign("catname", "All");
} else {
	$cdata = $cat->getById($category);
	if ($cdata) {
		$page->smarty->assign('catname', $cdata["title"]);
	} else {
		$page->show404();
	}
}

foreach ($ordering as $ordertype) {
	$page->smarty->assign('orderby' . $ordertype, WWW_TOP . "/console?t=" . $category . $browseby_link . "&amp;ob=" . $ordertype . "&amp;offset=0");
}

$page->smarty->assign('results', $consoles);

$page->meta_title = "Browse Console";
$page->meta_keywords = "browse,nzb,console,games,description,details";
$page->meta_description = "Browse for Games";

$page->content = $page->smarty->fetch('console.tpl');
$page->render();
