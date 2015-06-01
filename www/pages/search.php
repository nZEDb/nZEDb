<?php

use nzedb\Category;
use nzedb\Groups;
use nzedb\ReleaseSearch;
use nzedb\Releases;

if (!$page->users->isLoggedIn()) {
	$page->show403();
}

$groups      = new Groups(['Settings' => $page->settings]);
$releases = new Releases(['Groups' => $groups, 'Settings' => $page->settings]);

$page->meta_title       = "Search Nzbs";
$page->meta_keywords    = "search,nzb,description,details";
$page->meta_description = "Search for Nzbs";

$results    = [];

$searchType = "basic";
if (isset($_REQUEST["search_type"]) && $_REQUEST["search_type"] == "adv") {
	$searchType = "advanced";
}

$ordering = $releases->getBrowseOrdering();
$orderBy  = (isset($_REQUEST["ob"]) && in_array($_REQUEST['ob'], $ordering) ? $_REQUEST["ob"] : '');
$offset   = (isset($_REQUEST["offset"]) && ctype_digit($_REQUEST['offset'])) ? $_REQUEST["offset"] : 0;

$page->smarty->assign(
	[
		'subject' => '', 'search' => '', 'category' => [0], 'pagertotalitems' => 0,
		'pageritemsperpage' => 1, 'pageroffset' => 1, 'covgroup' => ''
	]
);

if ((isset($_REQUEST["id"]) || isset($_REQUEST["subject"])) && !isset($_REQUEST["searchadvr"]) && $searchType == "basic") {

	$searchString = '';
	switch (true) {
		case isset($_REQUEST["subject"]):
			$searchString = (string) $_REQUEST["subject"];
			$page->smarty->assign('subject', $searchString);
			break;
		case isset($_REQUEST["id"]):
			$searchString =(string) $_REQUEST["id"];
			$page->smarty->assign('search', $searchString);
			break;
	}

	$categoryID[] = -1;
	if (isset($_REQUEST['t'])) {
		$categoryID = explode(',', $_REQUEST['t']);
	}
	foreach ($releases->getBrowseOrdering() as $orderType) {
		$page->smarty->assign(
			"orderby$orderType",
			WWW_TOP . '/search/' . htmlentities($searchString) . '?t=' . (implode(',', $categoryID)) . "&amp;ob=$orderType"
		);
	}

	$results = $releases->search(
		$searchString, -1, -1, -1, -1, -1, 0, 0, -1, -1, $offset, ITEMS_PER_PAGE,
		$orderBy, -1, $page->userdata["categoryexclusions"], "basic", $categoryID
	);

	$page->smarty->assign(
		[
			'lastvisit' => $page->userdata['lastlogin'],
			'pagertotalitems' => (count($results) > 0 ? $results[0]['_totalrows'] : 0),
			'pageroffset' => $offset,
			'pageritemsperpage' => ITEMS_PER_PAGE,
			'pagerquerysuffix' => "#results",
			'pagerquerybase' =>
				WWW_TOP . "/search/" . htmlentities($searchString) . "?t=" .
				(implode(',', $categoryID)) . "&amp;ob=$orderBy&amp;offset=",
			'category' => $categoryID
		]
	);

}

$searchVars = [
	'searchadvr' => '', 'searchadvsubject' => '', 'searchadvposter' => '',
	'searchadvdaysnew' => '', 'searchadvdaysold' => '', 'searchadvgroups' => '',
	'searchadvcat' => '', 'searchadvsizefrom' => '', 'searchadvsizeto' => '',
	'searchadvhasnfo' => '', 'searchadvhascomments' => ''
];

foreach($searchVars as $searchVarKey => $searchVar) {
	$searchVars[$searchVarKey] = (isset($_REQUEST[$searchVarKey]) ? (string) $_REQUEST[$searchVarKey] : '');
}

$searchVars['selectedgroup'] = $searchVars['searchadvgroups'];
$searchVars['selectedcat'] = $searchVars['searchadvcat'];
$searchVars['selectedsizefrom'] = $searchVars['searchadvsizefrom'];
$searchVars['selectedsizeto'] = $searchVars['searchadvsizeto'];
foreach($searchVars as $searchVarKey => $searchVar) {
	$page->smarty->assign($searchVarKey, $searchVars[$searchVarKey]);
}

if (isset($_REQUEST["searchadvr"]) && !isset($_REQUEST["id"]) && !isset($_REQUEST["subject"]) && $searchType != "basic") {

	$orderByString = '';
	foreach ($searchVars as $searchVarKey => $searchVar) {
		$orderByString .= "&$searchVarKey=" . htmlentities($searchVar);
	}
	$orderByString = ltrim($orderByString, '&');

	foreach ($ordering as $orderType) {
		$page->smarty->assign(
			'orderby' . $orderType,
			WWW_TOP . "/search?$orderByString&search_type=adv&amp;ob=$orderType"
		);
	}

	$results = $releases->search(
		($searchVars['searchadvr'] == '' ? -1 : $searchVars['searchadvr']),
		($searchVars['searchadvsubject'] == '' ? -1 : $searchVars['searchadvsubject']),
		($searchVars['searchadvposter'] == '' ? -1 : $searchVars['searchadvposter']),
		$searchVars['searchadvgroups'], $searchVars['searchadvsizefrom'], $searchVars['searchadvsizeto'],
		$searchVars['searchadvhasnfo'], $searchVars['searchadvhascomments'],
		($searchVars['searchadvdaysnew'] == '' ? -1 : $searchVars['searchadvdaysnew']),
		($searchVars['searchadvdaysold'] == '' ? -1 : $searchVars['searchadvdaysold']),
		$offset, ITEMS_PER_PAGE, $orderBy, -1, $page->userdata["categoryexclusions"], "advanced",
		[($searchVars['searchadvcat'] == '' ? -1 : $searchVars['searchadvcat'])]
	);

	$page->smarty->assign(
		[
			'lastvisit' => $page->userdata['lastlogin'],
			'pagertotalitems' => (count($results) > 0 ? $results[0]['_totalrows'] : 0),
			'pageroffset' => $offset,
			'pageritemsperpage' => ITEMS_PER_PAGE,
			'pagerquerysuffix' => "#results",
			'pagerquerybase' => WWW_TOP . "/search?$orderByString&search_type=adv&amp;ob=$orderBy&amp;offset="
		]
	);
}

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

$page->smarty->assign(
	[
		'sizelist' => [
			-1 => '--Select--', 1  => '100MB', 2  => '250MB', 3  => '500MB', 4  => '1GB', 5  => '2GB',
			6  => '3GB', 7  => '4GB', 8  => '8GB', 9  => '16GB', 10 => '32GB', 11 => '64GB'
		],
		'results' => $results, 'sadvanced' => ($searchType != "basic"),
		'grouplist' => $groups->getGroupsForSelect(),
		'catlist' => (new Category(['Settings' => $page->settings]))->getForSelect(),
		'search_description' => $search_description,
		'pager' => $page->smarty->fetch("pager.tpl")
	]
);

$page->content = $page->smarty->fetch('search.tpl');
$page->render();
