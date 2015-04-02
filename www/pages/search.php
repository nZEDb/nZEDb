<?php

if (!$page->users->isLoggedIn()) {
	$page->show403();
}

$grp      = new Groups(['Settings' => $page->settings]);
$releases = new Releases(['Groups' => $grp, 'Settings' => $page->settings]);
$c        = new Category(['Settings' => $page->settings]);

$page->meta_title       = "Search Nzbs";
$page->meta_keywords    = "search,nzb,description,details";
$page->meta_description = "Search for Nzbs";

$results    = [];
$searchtype = "basic";
$searchStr  = "";

if (isset($_REQUEST["search_type"]) && $_REQUEST["search_type"] == "adv") {
	$searchtype = "advanced";
}

if (isset($_REQUEST["id"]) && !isset($_REQUEST["searchadvr"]) && !isset($_REQUEST["subject"])) {
	$offset   = (isset($_REQUEST["offset"]) && ctype_digit($_REQUEST['offset'])) ?
		$_REQUEST["offset"] : 0;
	$ordering = $releases->getBrowseOrdering();
	$orderby  = isset($_REQUEST["ob"]) && in_array($_REQUEST['ob'], $ordering) ? $_REQUEST["ob"] : '';

	if ($searchtype == "basic") {
		$searchStr  = (string)$_REQUEST["id"];
		$categoryId = [];
		if (isset($_REQUEST["t"])) {
			$categoryId = explode(",", $_REQUEST["t"]);
		} else {
			$categoryId[] = -1;
		}

		foreach ($ordering as $ordertype) {
			$page->smarty->assign('orderby' . $ordertype,
								  WWW_TOP . "/search/" . htmlentities($searchStr) . "?t=" .
								  (implode(',', $categoryId)) . "&amp;ob=" . $ordertype);
		}

		$page->smarty->assign('category', $categoryId);
		$page->smarty->assign('pagerquerybase',
							  WWW_TOP . "/search/" . htmlentities($searchStr) . "?t=" .
							  (implode(',', $categoryId)) . "&amp;ob=" . $orderby . "&amp;offset=");
		$page->smarty->assign('search', $searchStr);
		if (isset ($_REQUEST['subject'])) {
			$page->smarty->assign('subject', $_REQUEST['subject']);
		}
		$results = $releases->search($searchStr,
									 -1,
									 -1,
									 -1,
									 -1,
									 -1,
									 0,
									 0,
									 -1,
									 -1,
									 $offset,
									 ITEMS_PER_PAGE,
									 $orderby,
									 -1,
									 $page->userdata["categoryexclusions"],
									 $categoryId);
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
	$offset   = (isset($_REQUEST["offset"]) && ctype_digit($_REQUEST['offset'])) ?
		$_REQUEST["offset"] : 0;
	$ordering = $releases->getBrowseOrdering();
	$orderby  = isset($_REQUEST["ob"]) && in_array($_REQUEST['ob'], $ordering) ? $_REQUEST["ob"] : '';

	if ($searchtype == "basic") {
		$searchStr = (string)$_REQUEST["subject"];

		$categoryId = [];
		if (isset($_REQUEST["t"])) {
			$categoryId = explode(",", $_REQUEST["t"]);
		} else {
			$categoryId[] = -1;
		}

		foreach ($ordering as $ordertype) {
			$page->smarty->assign('orderby' . $ordertype,
								  WWW_TOP . "/search/" . htmlentities($searchStr) . "?t=" .
								  (implode(',', $categoryId)) . "&amp;ob=" . $ordertype);
		}

		$page->smarty->assign('category', $categoryId);
		$page->smarty->assign('pagerquerybase',
							  WWW_TOP . "/search/" . htmlentities($searchStr) . "?t=" .
							  (implode(',', $categoryId)) . "&amp;ob=" . $orderby . "&amp;offset=");
		$page->smarty->assign('subject', $searchStr);
		$results = $releases->search($searchStr,
									 -1,
									 -1,
									 -1,
									 -1,
									 -1,
									 0,
									 0,
									 -1,
									 -1,
									 $offset,
									 ITEMS_PER_PAGE,
									 $orderby,
									 -1,
									 $page->userdata["categoryexclusions"],
									 $categoryId);
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
	$offset   = (isset($_REQUEST["offset"]) && ctype_digit($_REQUEST['offset'])) ?
		$_REQUEST["offset"] : 0;
	$ordering = $releases->getBrowseOrdering();
	$orderby  = isset($_REQUEST["ob"]) && in_array($_REQUEST['ob'], $ordering) ? $_REQUEST["ob"] : '';

	if ($searchtype !== "basic") {

		$searchSearchName  = (string)$_REQUEST["searchadvr"];
		$searchUsenetName  = (string)$_REQUEST["searchadvsubject"];
		$searchPoster      = (string)$_REQUEST["searchadvposter"];
		$searchdaysnew     = (string)$_REQUEST["searchadvdaysnew"];
		$searchdaysold     = (string)$_REQUEST["searchadvdaysold"];
		$searchGroups      = (string)$_REQUEST["searchadvgroups"];
		$searchCat         = (string)$_REQUEST["searchadvcat"];
		$searchSizeFrom    = (string)$_REQUEST["searchadvsizefrom"];
		$searchSizeTo      = (string)$_REQUEST["searchadvsizeto"];
		$searchHasNFO      = (string)$_REQUEST["searchadvhasnfo"];
		$searchHascomments = (string)$_REQUEST["searchadvhascomments"];

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
			$page->smarty->assign('orderby' . $ordertype,
								  WWW_TOP . "/search?searchadvr=" .
								  htmlentities($searchSearchName) . "&searchadvsubject=" .
								  htmlentities($searchUsenetName) . "&searchadvposter=" .
								  htmlentities($searchPoster) . "&searchadvdaysnew=" .
								  htmlentities($searchdaysnew) . "&searchadvdaysold=" .
								  htmlentities($searchdaysold) . "&searchadvgroups=" .
								  htmlentities($searchGroups) . "&searchadvcat=" .
								  htmlentities($searchCat) . "&searchadvsizefrom=" .
								  htmlentities($searchSizeFrom) . "&searchadvsizeto=" .
								  htmlentities($searchSizeTo) . "&searchadvhasnfo=" .
								  htmlentities($searchHasNFO) . "&searchadvhascomments=" .
								  htmlentities($searchHascomments) . "&search_type=adv" .
								  "&amp;ob=" . $ordertype);
		}

		$page->smarty->assign('pagerquerybase',
							  WWW_TOP . "/search?searchadvr=" . htmlentities($searchSearchName) .
							  "&searchadvsubject=" . htmlentities($searchUsenetName) .
							  "&searchadvposter=" . htmlentities($searchPoster) .
							  "&searchadvdaysnew=" . htmlentities($searchdaysnew) .
							  "&searchadvdaysold=" . htmlentities($searchdaysold) .
							  "&searchadvgroups=" . htmlentities($searchGroups) . "&searchadvcat=" .
							  htmlentities($searchCat) . "&searchadvsizefrom=" .
							  htmlentities($searchSizeFrom) . "&searchadvsizeto=" .
							  htmlentities($searchSizeTo) . "&searchadvhasnfo=" .
							  htmlentities($searchHasNFO) . "&searchadvhascomments=" .
							  htmlentities($searchHascomments) . "&search_type=adv" . "&amp;ob=" .
							  $orderby . "&amp;offset=");
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
		$results = $releases->search($searchSearchName,
									 $searchUsenetName,
									 $searchPoster,
									 $searchGroups,
									 $searchSizeFrom,
									 $searchSizeTo,
									 $searchHasNFO,
									 $searchHascomments,
									 $searchdaysnew,
									 $searchdaysold,
									 $offset,
									 ITEMS_PER_PAGE,
									 $orderby,
									 -1,
									 $page->userdata["categoryexclusions"],
									 "advanced",
									 [$searchCat]);
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

$sizelist = [
	-1 => '--Select--',
	1  => '100MB',
	2  => '250MB',
	3  => '500MB',
	4  => '1GB',
	5  => '2GB',
	6  => '3GB',
	7  => '4GB',
	8  => '8GB',
	9  => '16GB',
	10 => '32GB',
	11 => '64GB'
];

$page->smarty->assign('sizelist', $sizelist);
$page->smarty->assign('results', $results);
$page->smarty->assign('sadvanced', ($searchtype != "basic"));

$ft1 = $page->settings->checkIndex('releases', 'ix_releases_name_searchname_ft');
$ft2 = $page->settings->checkIndex('releases', 'ix_releases_name_ft');
$ft3 = $page->settings->checkIndex('releases', 'ix_releases_searchname_ft');
switch (nZEDb_RELEASE_SEARCH_TYPE) {
	case ReleaseSearch::FULLTEXT:
		$search_description =
			'MySQL Full Text Search Rules:<br />
A leading exclamation point(! in place of +) indicates that this word must be present in each row that is returned.<br />
A leading minus sign indicates that this word must not be present in any of the rows that are returned.<br />
By default (when neither + nor - is specified) the word is optional, but the rows that contain it are rated higher.<br />
See <a target="_blank" href=\'http://dev.mysql.com/doc/refman/5.0/en/fulltext-boolean.html\'>docs</a> for more operators.';
		break;
	case ReleaseSearch::LIKE:
		$search_description = 'Include ^ to indicate search must start with term, -- to exclude words.';
		break;
	case ReleaseSearch::SPHINX:
	default:
		$search_description =
			'Sphinx Search Rules:<br />
The search is case insensitive.<br />
All words must be separated by spaces.
Do not seperate words using . or _ or -, sphinx will match a space against those automatically.<br />
Putting | between words makes any of those words optional.<br />
Putting << between words makes the word on the left have to be before the word on the right.<br />
Putting - or ! in front of a word makes that word excluded. Do not add a space between the - or ! and the word.<br />
Quoting all the words using " will look for an exact match.<br />
Putting ^ at the start will limit searches to releases that start with that word.<br />
Putting $ at the end will limit searches to releases that end with that word.<br />
Putting a * after a word will do a partial word search. ie: fish* will match fishing.<br />
If your search is only words seperated by spaces, all those words will be mandatory, the order of the words is not important.<br />
You can enclose words using paranthesis. ie: (^game*|^dex*)s03*(x264<&lt;nogrp$)<br />
You can combine some of these rules, but not all.<br />';
		break;
}
$page->smarty->assign('search_description', $search_description);

$page->content = $page->smarty->fetch('search.tpl');
$page->render();
