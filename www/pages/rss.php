<?php

use nzedb\Category;
use nzedb\Releases;
use nzedb\db\Settings;

$category = new Category(['Settings' => $page->settings]);
$releases = new Releases(['Settings' => $page->settings]);

// If no content id provided then show user the rss selection page.
if (!isset($_GET["t"]) && !isset($_GET["rage"]) && !isset($_GET["anidb"])) {
	// User has to either be logged in, or using rsskey.
	if (!$page->users->isLoggedIn()) {
		if ($page->settings->getSetting('registerstatus') != Settings::REGISTER_STATUS_API_ONLY) {
			header('X-nZEDb: ERROR: You must be logged in or provide a valid User ID and API key!');
			$page->show403();
		} else {
			header("Location: " . $page->settings->getSetting('code'));
		}
	}

	$page->title = "Rss Feeds";
	$page->meta_title = "Rss Nzb Feeds";
	$page->meta_keywords = "view,nzb,description,details,rss,atom";
	$page->meta_description = "View available Rss Nzb feeds.";

	$page->smarty->assign([
			'categorylist'       => $category->get(true, $page->userdata["categoryexclusions"]),
			'parentcategorylist' => $category->getForMenu($page->userdata["categoryexclusions"])
		]
	);

	$page->content = $page->smarty->fetch('rssdesc.tpl');
	$page->render();
} else {
	$rssToken = $uid = -1;
	// User requested a feed, ensure either logged in or passing a valid token.
	if ($page->users->isLoggedIn()) {
		$uid = $page->userdata["id"];
		$rssToken = $page->userdata["rsstoken"];
		$maxRequests = $page->userdata['apirequests'];
	} else {
		if ($page->settings->getSetting('registerstatus') == Settings::REGISTER_STATUS_API_ONLY) {
			$res = $page->users->getById(0);
		} else {
			if (!isset($_GET["i"]) || !isset($_GET["r"])) {
				header('X-nZEDb: ERROR: Both the User ID and API key are required for viewing the RSS!');
				$page->show403();
			}

			$res = $page->users->getByIdAndRssToken($_GET["i"], $_GET["r"]);
		}

		if (!$res) {
			header('X-nZEDb: ERROR: Invalid API key or User ID!');
			$page->show403();
		}

		$uid = $res["id"];
		$rssToken = $res['rsstoken'];
		$maxRequests = $res['apirequests'];
	}

	if ($page->users->getApiRequests($uid) > $maxRequests) {
		header('X-nZEDb: ERROR: You have reached your daily limit for API requests!');
		$page->show503();
	} else {
		$page->users->addApiRequest($uid, $_SERVER['REQUEST_URI']);
	}
	// Valid or logged in user, get them the requested feed.

	$userRage = $userAnidb = -1;
	if (isset($_GET["rage"])) {
		$userRage = ($_GET["rage"] == 0 ? -1 : $_GET["rage"] + 0);
	} elseif (isset($_GET["anidb"])) {
		$userAnidb = ($_GET["anidb"] == 0 ? -1 : $_GET["anidb"] + 0);
	}

	$userCat = (isset($_GET['t']) ? ($_GET['t'] == 0 ? -1 : $_GET['t']) : -1);
	$userNum = (isset($_GET["num"]) && is_numeric($_GET['num']) ? abs($_GET['num']) : 100);
	$userAirDate = (isset($_GET["airdate"]) && is_numeric($_GET['airdate']) ? abs($_GET["airdate"]) : -1);

	$page->smarty->assign([
			'dl'       => (isset($_GET['dl']) && $_GET['dl'] == '1' ? '1' : '0'),
			'del'      => (isset($_GET['del']) && $_GET['del'] == '1' ? '1' : '0'),
			'uid'      => $uid,
			'rsstoken' => $rssToken
		]
	);

	if ($userCat == -3) {
		$relData = $releases->getShowsRss($userNum, $uid, $page->users->getCategoryExclusion($uid), $userAirDate);
	} elseif ($userCat == -4) {
		$relData = $releases->getMyMoviesRss($userNum, $uid, $page->users->getCategoryExclusion($uid));
	} else {
		$relData = $releases->getRss(explode(',', $userCat), $userNum, $userRage, $userAnidb, $uid, $userAirDate);
	}

	$page->smarty->assign('releases', $relData);
	header("Content-type: text/xml");
	echo trim($page->smarty->fetch('rss.tpl'));
}
