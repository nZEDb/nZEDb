<?php

use nzedb\Category;
use nzedb\Releases;

if (!$page->users->isLoggedIn()) {
	$page->show403();
}

$releases = new Releases(['Settings' => $page->settings]);

$category = -1;
if (isset($_REQUEST["t"]) && ctype_digit($_REQUEST["t"])) {
	$category = $_REQUEST["t"];
}

$grp = "";
if (isset($_REQUEST["g"])) {
	$grp = $_REQUEST["g"];
}

$catarray = array();
$catarray[] = $category;

$page->smarty->assign('category', $category);
$browsecount = $releases->getBrowseCount($catarray, -1, $page->userdata["categoryexclusions"], $grp);

$offset = (isset($_REQUEST["offset"]) && ctype_digit($_REQUEST['offset'])) ? $_REQUEST["offset"] : 0;
$ordering = $releases->getBrowseOrdering();
$orderby = isset($_REQUEST["ob"]) && in_array($_REQUEST['ob'], $ordering) ? $_REQUEST["ob"] : '';

$results = array();
$results = $releases->getBrowseRange($catarray, $offset, ITEMS_PER_PAGE, $orderby, -1, $page->userdata["categoryexclusions"], $grp);

$page->smarty->assign('pagertotalitems', $browsecount);
$page->smarty->assign('pageroffset', $offset);
$page->smarty->assign('pageritemsperpage', ITEMS_PER_PAGE);
$page->smarty->assign('pagerquerybase', WWW_TOP . "/browse?t=" . $category . "&amp;g=" . $grp . "&amp;ob=" . $orderby . "&amp;offset=");
$page->smarty->assign('pagerquerysuffix', "#results");

$pager = $page->smarty->fetch("pager.tpl");
$page->smarty->assign('pager', $pager);

$covgroup = '';
if ($category == -1 && $grp == "") {
	$page->smarty->assign("catname", "All");
} elseif ($category != -1 && $grp == "") {
	$cat = new Category(['Settings' => $releases->pdo]);
	$cdata = $cat->getById($category);
	if ($cdata) {
		$page->smarty->assign('catname', $cdata["title"]);
		if ($cdata['parentid'] == Category::CAT_PARENT_GAME || $cdata['id'] == Category::CAT_PARENT_GAME) {
			$covgroup = 'console';
		} elseif ($cdata['parentid'] == Category::CAT_PARENT_MOVIE || $cdata['id'] == Category::CAT_PARENT_MOVIE) {
			$covgroup = 'movies';
		} elseif ($cdata['parentid'] == Category::CAT_PARENT_XXX || $cdata['id'] == Category::CAT_PARENT_XXX) {
			$covgroup = 'xxx';
		} elseif ($cdata['parentid'] == Category::CAT_PARENT_PC || $cdata['id'] == Category::CAT_PC_GAMES) {
			$covgroup = 'games';
		} elseif ($cdata['parentid'] == Category::CAT_PARENT_MUSIC || $cdata['id'] == Category::CAT_PARENT_MUSIC) {
			$covgroup = 'music';
		} elseif ($cdata['parentid'] == Category::CAT_PARENT_BOOKS || $cdata['id'] == Category::CAT_PARENT_BOOKS) {
			$covgroup = 'books';
		}
	} else {
		$page->show404();
	}
} elseif ($grp != "") {
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
