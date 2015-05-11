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

	$categorylist = $category->get(true, $page->userdata["categoryexclusions"]);
	$page->smarty->assign('categorylist', $categorylist);

	$parentcategorylist = $category->getForMenu($page->userdata["categoryexclusions"]);
	$page->smarty->assign('parentcategorylist', $parentcategorylist);

	$page->content = $page->smarty->fetch('rssdesc.tpl');
	$page->render();
} else {
	$rsstoken = $uid = -1;
	// User requested a feed, ensure either logged in or passing a valid token.
	if ($page->users->isLoggedIn()) {
		$uid = $page->userdata["id"];
		$rsstoken = $page->userdata["rsstoken"];
		$maxrequests = $page->userdata['apirequests'];
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
		$rsstoken = $res['rsstoken'];
		$maxrequests = $res['apirequests'];
	}

	$apirequests = $page->users->getApiRequests($uid);
	if ($apirequests['num'] > $maxrequests) {
		header('X-nZEDb: ERROR: You have reached your daily limit for API requests!');
		$page->show503();
	} else {
		$page->users->addApiRequest($uid, $_SERVER['REQUEST_URI']);
	}

	// Valid or logged in user, get them the requested feed.
	if (isset($_GET["dl"]) && $_GET["dl"] == "1") {
		$page->smarty->assign("dl", "1");
	}

	$usercat = -1;
	if (isset($_GET["t"])) {
		$usercat = ($_GET["t"] == 0 ? -1 : $_GET["t"]);
	}

	$userrage = $useranidb = $userseries = -1;
	if (isset($_GET["rage"])) {
		$userrage = ($_GET["rage"] == 0 ? -1 : $_GET["rage"] + 0);
	} elseif (isset($_GET["anidb"])) {
		$useranidb = ($_GET["anidb"] == 0 ? -1 : $_GET["anidb"] + 0);
	}

	$usernum = 100;
	if (isset($_GET["num"])) {
		$usernum = $_GET["num"] + 0;
	}

	if (isset($_GET["del"]) && $_GET["del"] == "1") {
		$page->smarty->assign("del", "1");
	}

	$userairdate = -1;
	if (isset($_GET["airdate"])) {
		$userairdate = $_GET["airdate"] + 0;
	}

	$page->smarty->assign('uid', $uid);
	$page->smarty->assign('rsstoken', $rsstoken);

	if ($usercat == -3) {
		$catexclusions = $page->users->getCategoryExclusion($uid);
		$reldata = $releases->getShowsRss($usernum, $uid, $catexclusions, $userairdate);
	} elseif ($usercat == -4) {
		$catexclusions = $page->users->getCategoryExclusion($uid);
		$reldata = $releases->getMyMoviesRss($usernum, $uid, $catexclusions);
	} else {
		$reldata = $releases->getRss(explode(",", $usercat),
									 $usernum,
									 $userrage,
									 $useranidb,
									 $uid,
									 $userairdate);
	}

	$page->smarty->assign('releases', $reldata);
	header("Content-type: text/xml");
	echo trim($page->smarty->fetch('rss.tpl'));
}
