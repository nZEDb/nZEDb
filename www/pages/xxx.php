<?php
if (!$page->users->isLoggedIn()) {
	$page->show403();
}

$movie = new XXX(['Settings' => $page->settings]);
$cat = new Category(['Settings' => $page->settings]);

$moviecats = $cat->getChildren(Category::CAT_PARENT_XXX);
$mtmp = array();
foreach ($moviecats as $mcat) {
	$mtmp[$mcat['id']] = $mcat;
}
$category = Category::CAT_PARENT_XXX;
if (isset($_REQUEST['t']) && array_key_exists($_REQUEST['t'], $mtmp)) {
	$category = $_REQUEST['t'] + 0;
}
$catarray = array();
$catarray[] = $category;

$page->smarty->assign('catlist', $mtmp);
$page->smarty->assign('category', $category);

$browsecount = $movie->getXXXCount($catarray, -1, $page->userdata['categoryexclusions']);

$offset = (isset($_REQUEST['offset']) && ctype_digit($_REQUEST['offset'])) ? $_REQUEST["offset"] : 0;
$ordering = $movie->getXXXOrdering();
$orderby = isset($_REQUEST['ob']) && in_array($_REQUEST['ob'], $ordering) ? $_REQUEST['ob'] : '';

$results = $movies = array();
$results = $movie->getXXXRange($catarray, $offset, ITEMS_PER_COVER_PAGE, $orderby, -1, $page->userdata['categoryexclusions']);
foreach ($results as $result) {
	$result['genre'] = $movie->makeFieldLinks($result, 'genre');
	$result['actors'] = $movie->makeFieldLinks($result, 'actors');
	$result['director'] = $movie->makeFieldLinks($result, 'director');
	$movies[] = $result;
}
$title = (isset($_REQUEST['title']) && !empty($_REQUEST['title'])) ? stripslashes($_REQUEST['title']) : '';
$page->smarty->assign('title', stripslashes($title));

$actors = (isset($_REQUEST['actors']) && !empty($_REQUEST['actors'])) ? stripslashes($_REQUEST['actors']) : '';
$page->smarty->assign('actors', $actors);

$director = (isset($_REQUEST['director']) && !empty($_REQUEST['director'])) ? stripslashes($_REQUEST['director']) : '';
$page->smarty->assign('director', $director);

$genres = (array)$movie->getAllGenres(true);
$genre = (isset($_REQUEST['genre']) && in_array($_REQUEST['genre'], $genres)) ? $_REQUEST['genre'] : '';
$page->smarty->assign('genres', $genres);
$page->smarty->assign('genre', $genre);

$browseby_link = '&amp;title=' . $title . '&amp;actors=' . $actors . '&amp;director=' . $director . '&amp;genre=' . $genre;

$page->smarty->assign('pagertotalitems', $browsecount);
$page->smarty->assign('pageroffset', $offset);
$page->smarty->assign('pageritemsperpage', ITEMS_PER_COVER_PAGE);
$page->smarty->assign('pagerquerybase', WWW_TOP . "/xxx?t=" . $category . $browseby_link . "&amp;ob=" . $orderby . "&amp;offset=");
$page->smarty->assign('pagerquerysuffix', "#results");

$pager = $page->smarty->fetch("pager.tpl");
$page->smarty->assign('pager', $pager);

if ($category == -1) {
	$page->smarty->assign("catname", "All");
} else {
	$cdata = $cat->getById($category);
	if ($cdata) {
		$page->smarty->assign('catname', $cdata['title']);
	} else {
		$page->show404();
	}
}

foreach ($ordering as $ordertype) {
	$page->smarty->assign('orderby' . $ordertype, WWW_TOP . "/xxx?t=" . $category . $browseby_link . "&amp;ob=" . $ordertype . "&amp;offset=0");
}

$page->smarty->assign('results', $movies);

$page->meta_title = "Browse XXX";
$page->meta_keywords = "browse,xxx,nzb,description,details";
$page->meta_description = "Browse for XXX Movies";

$page->content = $page->smarty->fetch('xxx.tpl');
$page->render();
