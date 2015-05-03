<?php

use nzedb\Movie;

if (!$page->users->isLoggedIn()) {
	$page->show403();
}

$m = new Movie(['Settings' => $page->settings]);

if (!isset($_GET["id"])) {
	$_GET["id"] = 1;
}
$user = $page->users->getById($page->users->currentUserId());
$cpapi = $user['cp_api'];
$cpurl = $user['cp_url'];
$page->smarty->assign('cpapi', $cpapi);
$page->smarty->assign('cpurl', $cpurl);

$data = $m->getUpcoming($_GET["id"]);
//print_r(json_decode($data["info"])->movies);die();
if (!$data || $data["info"] == "") {
	$page->smarty->assign("nodata", "No upcoming data.");
} else {

	$data = json_decode($data["info"]);

	if (isset($data->error)) {
		$page->smarty->assign("nodata", $data->error);
	} else if (!isset($data->movies)) {
		$page->smarty->assign("nodata", 'Unspecified error.');
	} else {
		$page->smarty->assign('data', $data->movies);

		switch ($_GET["id"]) {
			case Movie::SRC_BOXOFFICE;
				$page->title = "Box Office";
				break;
			case Movie::SRC_INTHEATRE;
				$page->title = "In Theater";
				break;
			case Movie::SRC_OPENING;
				$page->title = "Opening";
				break;
			case Movie::SRC_UPCOMING;
				$page->title = "Upcoming";
				break;
			case Movie::SRC_DVD;
				$page->title = "DVD Releases";
				break;
		}
	}
	$page->meta_title = "View upcoming theatre releases";
	$page->meta_keywords = "view,series,theatre,dvd";
	$page->meta_description = "View upcoming theatre releases";
}

/**
 * extract just the cloudfront image url.
 *
 * @param string $imageURL    The url to change.
 *
 * @return string
 */
function replace_url($imageURL)
{
	return preg_replace(
		'/^.*?\/[\d]+x[\d]+\//',
		'https://',
		$imageURL
	);
}

$page->content = $page->smarty->fetch('upcoming.tpl');
$page->render();
