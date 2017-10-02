<?php

use nzedb\Category;
use nzedb\Releases;

if (!$page->users->isLoggedIn()) {
	$page->show403();
}

$releases = new Releases(['Settings' => $page->settings]);

$category = -1;
if (isset($_REQUEST["t"])) {
	$category = $_REQUEST["t"];
}

$grp = -1;
if (isset($_REQUEST["g"])) {
	$grp = $_REQUEST["g"];
}

$catarray = array();
$catarray[] = $category;

$page->smarty->assign('category', $category);

$offset = (isset($_REQUEST["offset"]) && ctype_digit($_REQUEST['offset'])) ? $_REQUEST["offset"] : 0;
$ordering = $releases->getBrowseOrdering();
$orderby = isset($_REQUEST["ob"]) && in_array($_REQUEST['ob'], $ordering) ? $_REQUEST["ob"] : '';

$results = array();
$results = $releases->getBrowseRange($catarray, $offset, ITEMS_PER_PAGE, $orderby, -1, $page->userdata["categoryexclusions"], $grp);

$browsecount = isset($results[0]['_totalcount']) ? $results[0]['_totalcount'] : 0;


$page->smarty->assign('pagertotalitems', $browsecount);
$page->smarty->assign('pageroffset', $offset);
$page->smarty->assign('pageritemsperpage', ITEMS_PER_PAGE);
$page->smarty->assign('pagerquerybase', WWW_TOP . "/browse?t=" . $category . "&amp;g=" . $grp . "&amp;ob=" . $orderby . "&amp;offset=");
$page->smarty->assign('pagerquerysuffix', "#results");

$pager = $page->smarty->fetch("pager.tpl");
$page->smarty->assign('pager', $pager);

$covgroup = '';
if ($category == -1 && $grp == -1) {
	$page->smarty->assign("catname", "All");
} elseif ($category != -1 && $grp == -1) {
	$cat = new Category(['Settings' => $releases->pdo]);
	$cdata = $cat->getById($category);
	if ($cdata) {
		$page->smarty->assign('catname', $cdata["title"]);
		if ($cdata['parentid'] == Category::GAME_ROOT || $cdata['id'] == Category::GAME_ROOT) {
			$covgroup = 'console';
		} elseif ($cdata['parentid'] == Category::MOVIE_ROOT || $cdata['id'] == Category::MOVIE_ROOT) {
			$covgroup = 'movies';
		} elseif ($cdata['parentid'] == Category::XXX_ROOT || $cdata['id'] == Category::XXX_ROOT) {
			$covgroup = 'xxx';
		} elseif ($cdata['parentid'] == Category::PC_ROOT || $cdata['id'] == Category::PC_GAMES) {
			$covgroup = 'games';
		} elseif ($cdata['parentid'] == Category::MUSIC_ROOT || $cdata['id'] == Category::MUSIC_ROOT) {
			$covgroup = 'music';
		} elseif ($cdata['parentid'] == Category::BOOKS_ROOT || $cdata['id'] == Category::BOOKS_ROOT) {
			$covgroup = 'books';
		}
	} else {
		$page->show404();
	}
} elseif ($grp != -1) {
	$page->smarty->assign('catname', $grp);
}

$page->smarty->assign('covgroup', $covgroup);

foreach ($ordering as $ordertype) {
	$page->smarty->assign('orderby' . $ordertype, WWW_TOP . "/browse?t=" . $category . "&amp;g=" . $grp . "&amp;ob=" . $ordertype . "&amp;offset=0");
}

$page->smarty->assign('lastvisit', $page->userdata['lastlogin']);

$page->smarty->assign('results', $results);

$page->meta_title = "Browse Nzbs";
$page->meta_keywords = "browse,nzb,description,details";
$page->meta_description = "Browse for Nzbs";

$page->content = $page->smarty->fetch('browse.tpl');
$page->render();
