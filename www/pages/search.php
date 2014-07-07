<?php

use nzedb\db\Settings;

if (!$users->isLoggedIn())
	$page->show403();

$releases = new Releases();
$grp = new Groups();
$c = new Category();
$pdo = new Settings();

$page->meta_title = "Search Nzbs";
$page->meta_keywords = "search,nzb,description,details";
$page->meta_description = "Search for Nzbs";

$results = array();
$searchtype = "basic";
$searchStr = "";

if (isset($_REQUEST["search_type"]) && $_REQUEST["search_type"] == "adv") {
	$searchtype = "advanced";
}

if (isset($_REQUEST["id"]) && !isset($_REQUEST["searchadvr"]) && !isset($_REQUEST["subject"])) {
	$offset = (isset($_REQUEST["offset"]) && ctype_digit($_REQUEST['offset'])) ? $_REQUEST["offset"] : 0;
	$ordering = $releases->getBrowseOrdering();
	$orderby = isset($_REQUEST["ob"]) && in_array($_REQUEST['ob'], $ordering) ? $_REQUEST["ob"] : '';

	if ($searchtype == "basic") {
		$searchStr = (string) $_REQUEST["id"];
		$categoryId = array();
		if (isset($_REQUEST["t"])) {
			$categoryId = explode(",", $_REQUEST["t"]);
		} else {
			$categoryId[] = -1;
		}

		foreach ($ordering as $ordertype) {
			$page->smarty->assign('orderby' . $ordertype, WWW_TOP . "/search/" . htmlentities($searchStr) . "?t=" . (implode(',', $categoryId)) . "&amp;ob=" . $ordertype);
		}

		$page->smarty->assign('category', $categoryId);
		$page->smarty->assign('pagerquerybase', WWW_TOP . "/search/" . htmlentities($searchStr) . "?t=" . (implode(',', $categoryId)) . "&amp;ob=" . $orderby . "&amp;offset=");
		$page->smarty->assign('search', $searchStr);
		if (isset ($_REQUEST['subject'])) {
			$page->smarty->assign('subject', $_REQUEST['subject']);
		}
		$results = $releases->search($searchStr, -1, -1, -1, $categoryId, -1, -1, 0, 0, -1, -1, $offset, ITEMS_PER_PAGE, $orderby, -1, $page->userdata["categoryexclusions"]);
	}

	$page->smarty->assign('lastvisit', $page->userdata['lastlogin']);
	if (sizeof($results) > 0) {
		$totalRows = $results[0]['_totalrows'];
	} else {
		$totalRows = 0;
	}

	$page->smarty->assign('pagertotalitems', $totalRows);
	$page->smarty->assign('pageroffset', $offset);
	$page->smarty->assign('pageritemsperpage', ITEMS_PER_PAGE);
	$page->smarty->assign('pagerquerysuffix', "#results");

	$pager = $page->smarty->fetch("pager.tpl");
	$page->smarty->assign('pager', $pager);
}

if (isset($_REQUEST["subject"]) && !isset($_REQUEST["searchadvr"]) && !isset($_REQUEST["id"])) {
	$offset = (isset($_REQUEST["offset"]) && ctype_digit($_REQUEST['offset'])) ? $_REQUEST["offset"] : 0;
	$ordering = $releases->getBrowseOrdering();
	$orderby = isset($_REQUEST["ob"]) && in_array($_REQUEST['ob'], $ordering) ? $_REQUEST["ob"] : '';

	if ($searchtype == "basic") {
		$searchStr = (string) $_REQUEST["subject"];

		$categoryId = array();
		if (isset($_REQUEST["t"])) {
			$categoryId = explode(",", $_REQUEST["t"]);
		} else {
			$categoryId[] = -1;
		}

		foreach ($ordering as $ordertype) {
			$page->smarty->assign('orderby' . $ordertype, WWW_TOP . "/search/" . htmlentities($searchStr) . "?t=" . (implode(',', $categoryId)) . "&amp;ob=" . $ordertype);
		}

		$page->smarty->assign('category', $categoryId);
		$page->smarty->assign('pagerquerybase', WWW_TOP . "/search/" . htmlentities($searchStr) . "?t=" . (implode(',', $categoryId)) . "&amp;ob=" . $orderby . "&amp;offset=");
		$page->smarty->assign('subject', $searchStr);
		$results = $releases->search($searchStr, -1, -1, -1, $categoryId, -1, -1, 0, 0, -1, -1, $offset, ITEMS_PER_PAGE, $orderby, -1, $page->userdata["categoryexclusions"]);
	}

	$page->smarty->assign('lastvisit', $page->userdata['lastlogin']);
	if (sizeof($results) > 0) {
		$totalRows = $results[0]['_totalrows'];
	} else {
		$totalRows = 0;
	}

	$page->smarty->assign('pagertotalitems', $totalRows);
	$page->smarty->assign('pageroffset', $offset);
	$page->smarty->assign('pageritemsperpage', ITEMS_PER_PAGE);
	$page->smarty->assign('pagerquerysuffix', "#results");

	$pager = $page->smarty->fetch("pager.tpl");
	$page->smarty->assign('pager', $pager);
}

if (isset($_REQUEST["searchadvr"]) && !isset($_REQUEST["id"]) && !isset($_REQUEST["subject"])) {
	$offset = (isset($_REQUEST["offset"]) && ctype_digit($_REQUEST['offset'])) ? $_REQUEST["offset"] : 0;
	$ordering = $releases->getBrowseOrdering();
	$orderby = isset($_REQUEST["ob"]) && in_array($_REQUEST['ob'], $ordering) ? $_REQUEST["ob"] : '';

	if ($searchtype !== "basic") {

		$searchSearchName = (string) $_REQUEST["searchadvr"];
		$searchUsenetName = (string) $_REQUEST["searchadvsubject"];
		$searchPoster = (string) $_REQUEST["searchadvposter"];
		$searchdaysnew = (string) $_REQUEST["searchadvdaysnew"];
		$searchdaysold = (string) $_REQUEST["searchadvdaysold"];
		$searchGroups = (string) $_REQUEST["searchadvgroups"];
		$searchCat = (string) $_REQUEST["searchadvcat"];
		$searchSizeFrom = (string) $_REQUEST["searchadvsizefrom"];
		$searchSizeTo = (string) $_REQUEST["searchadvsizeto"];
		$searchHasNFO = (string) $_REQUEST["searchadvhasnfo"];
		$searchHascomments = (string) $_REQUEST["searchadvhascomments"];

		$page->smarty->assign('searchadvr', $searchSearchName);
		$page->smarty->assign('searchadvsubject', $searchUsenetName);
		$page->smarty->assign('searchadvposter', $searchPoster);
		$page->smarty->assign('searchadvdaysnew', $searchdaysnew);
		$page->smarty->assign('searchadvdaysold', $searchdaysold);
		$page->smarty->assign('selectedgroup', $searchGroups);
		$page->smarty->assign('selectedcat', $searchCat);
		$page->smarty->assign('selectedsizefrom', $searchSizeFrom);
		$page->smarty->assign('selectedsizeto', $searchSizeTo);
		$page->smarty->assign('searchadvhasnfo', $searchHasNFO);
		$page->smarty->assign('searchadvhascomments', $searchHascomments);
		foreach ($ordering as $ordertype) {
			$page->smarty->assign('orderby' . $ordertype, WWW_TOP . "/search?searchadvr=" . htmlentities($searchSearchName) . "&searchadvsubject=" . htmlentities($searchUsenetName) . "&searchadvposter=" . htmlentities($searchPoster) . "&searchadvdaysnew=" . htmlentities($searchdaysnew) . "&searchadvdaysold=" . htmlentities($searchdaysold) . "&searchadvgroups=" . htmlentities($searchGroups) . "&searchadvcat=" . htmlentities($searchCat) . "&searchadvsizefrom=" . htmlentities($searchSizeFrom) . "&searchadvsizeto=" . htmlentities($searchSizeTo) . "&searchadvhasnfo=" . htmlentities($searchHasNFO) . "&searchadvhascomments=" . htmlentities($searchHascomments) . "&search_type=adv" . "&amp;ob=" . $ordertype);
		}

		$page->smarty->assign('pagerquerybase', WWW_TOP . "/search?searchadvr=" . htmlentities($searchSearchName) . "&searchadvsubject=" . htmlentities($searchUsenetName) . "&searchadvposter=" . htmlentities($searchPoster) . "&searchadvdaysnew=" . htmlentities($searchdaysnew) . "&searchadvdaysold=" . htmlentities($searchdaysold) . "&searchadvgroups=" . htmlentities($searchGroups) . "&searchadvcat=" . htmlentities($searchCat) . "&searchadvsizefrom=" . htmlentities($searchSizeFrom) . "&searchadvsizeto=" . htmlentities($searchSizeTo) . "&searchadvhasnfo=" . htmlentities($searchHasNFO) . "&searchadvhascomments=" . htmlentities($searchHascomments) . "&search_type=adv" . "&amp;ob=" . $orderby . "&amp;offset=");
		if ($_REQUEST["searchadvr"] == "") {
			$searchSearchName = -1;
		}
		if ($_REQUEST["searchadvsubject"] == "") {
			$searchUsenetName = -1;
		}
		if ($_REQUEST["searchadvposter"] == "") {
			$searchPoster = -1;
		}
		if ($_REQUEST["searchadvdaysnew"] == "") {
			$searchdaysnew = -1;
		}
		if ($_REQUEST["searchadvdaysold"] == "") {
			$searchdaysold = -1;
		}
		if ($_REQUEST["searchadvcat"] == "") {
			$searchCat = -1;
		}
		$results = $releases->search($searchSearchName, $searchUsenetName, $searchPoster, $searchGroups, $searchCat, $searchSizeFrom, $searchSizeTo, $searchHasNFO, $searchHascomments, $searchdaysnew, $searchdaysold, $offset, ITEMS_PER_PAGE, $orderby, -1, $page->userdata["categoryexclusions"], "advanced");
	}

	$page->smarty->assign('lastvisit', $page->userdata['lastlogin']);
	if (sizeof($results) > 0) {
		$totalRows = $results[0]['_totalrows'];
	} else {
		$totalRows = 0;
	}

	$page->smarty->assign('pagertotalitems', $totalRows);
	$page->smarty->assign('pageroffset', $offset);
	$page->smarty->assign('pageritemsperpage', ITEMS_PER_PAGE);
	$page->smarty->assign('pagerquerysuffix', "#results");

	$pager = $page->smarty->fetch("pager.tpl");
	$page->smarty->assign('pager', $pager);
}

$grouplist = $grp->getGroupsForSelect();
$page->smarty->assign('grouplist', $grouplist);

$catlist = $c->getForSelect();
$page->smarty->assign('catlist', $catlist);

$sizelist = array(-1 => '--Select--',
	1 => '100MB',
	2 => '250MB',
	3 => '500MB',
	4 => '1GB',
	5 => '2GB',
	6 => '3GB',
	7 => '4GB',
	8 => '8GB',
	9 => '16GB',
	10 => '32GB',
	11 => '64GB'
);

$page->smarty->assign('sizelist', $sizelist);
$page->smarty->assign('results', $results);
$page->smarty->assign('sadvanced', ($searchtype != "basic"));

$ft1 = $pdo->checkIndex('releases', 'ix_releases_name_searchname_ft');
$ft2 = $pdo->checkIndex('releases', 'ix_releases_name_ft');
$ft3 = $pdo->checkIndex('releases', 'ix_releases_searchname_ft');
if (isset($ft1['key_name']) || (isset($ft2['key_name']) && isset($ft3['key_name']))) {
	$page->smarty->assign('fulltext', true);
}

$page->content = $page->smarty->fetch('search.tpl');
$page->render();
