<?php

use nzedb\DnzbFailures;
use nzedb\db\Settings;

// Page is accessible only by the rss token, or logged in users.
if ($page->users->isLoggedIn()) {
	$uid = $page->users->currentUserId();
	$rssToken = $page->userdata['rsstoken'];
} else {
	if ($page->settings->getSetting('registerstatus') == Settings::REGISTER_STATUS_API_ONLY) {
		if (!isset($_GET["rsstoken"])) {
			header("X-DNZB-RCode: 400");
			header("X-DNZB-RText: Bad request, please supply all parameters!");
			$page->show403();
		} else {
			$res = $page->users->getByRssToken($_GET["rsstoken"]);
		}
	} else {
		if (!isset($_GET["userid"]) || !isset($_GET["rsstoken"])) {
			header("X-DNZB-RCode: 400");
			header("X-DNZB-RText: Bad request, please supply all parameters!");
			$page->show403();
		} else {
			$res = $page->users->getByIdAndRssToken($_GET["userid"], $_GET["rsstoken"]);
		}
	}
	if (!isset($res)) {
		header("X-DNZB-RCode: 401");
		header("X-DNZB-RText: Unauthorised, wrong user ID or rss key!");
		$page->show403();
	} else {
		$uid = $res['id'];
		$rssToken = $res['rsstoken'];
	}
}

if (isset($_GET['guid']) && isset($uid) && is_numeric($uid) && isset($rssToken)) {

	$alt = (new DnzbFailures(['Settings' => $page->settings]))->getAlternate($_GET['guid'], $uid);
	if ($alt === false) {
		header("X-DNZB-RCode: 404");
		header("X-DNZB-RText: No NZB found for alternate match.");
		$page->show404();
	} else {
		header('Location: ' . $page->serverurl . 'getnzb/' . $alt['guid'] . '&i=' . $uid . '&r=' . $rssToken);
	}
}