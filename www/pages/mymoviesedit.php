<?php
if (!$users->isLoggedIn()) {
	$page->show403();
}

$um = new UserMovies();
if (isset($_REQUEST["del"])) {
	$um->delMovie($users->currentUserId(), $_REQUEST["del"]);
}

$cat = new Category();
$tmpcats = $cat->getChildren(Category::CAT_PARENT_MOVIE, true, $page->userdata["categoryexclusions"]);
$categories = array();
foreach ($tmpcats as $c) {
	$categories[$c['id']] = $c['title'];
}

$movies = $um->getMovies($users->currentUserId());
$results = array();
foreach ($movies as $mov => $m) {
	$movcats = explode('|', $m['categoryid']);
	if (is_array($movcats) && sizeof($movcats) > 0) {
		$catarr = array();
		foreach ($movcats as $movcat) {
			if (!empty($movcat)) {
				$catarr[] = $categories[$movcat];
			}
		}
		$m['categoryNames'] = implode(', ', $catarr);
	} else {
		$m['categoryNames'] = '';
	}

	$results[$mov] = $m;
}
$page->smarty->assign('movies', $results);

$page->title = "Edit My Movies";
$page->meta_title = "Edit My Movies";
$page->meta_keywords = "couch,potato,movie,add";
$page->meta_description = "Manage Your Movies";

$page->content = $page->smarty->fetch('mymoviesedit.tpl');
$page->render();
