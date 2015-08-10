<?php

use nzedb\Releases;
use nzedb\NZB;
$uid = 0;

use nzedb\db\Settings;

// Page is accessible only by the rss token, or logged in users.
if ($page->users->isLoggedIn()) {
	$uid = $page->users->currentUserId();
	$maxDownloads = $page->userdata["downloadrequests"];
	$rssToken = $page->userdata['rsstoken'];
} else {
	if ($page->settings->getSetting('registerstatus') == Settings::REGISTER_STATUS_API_ONLY) {
		$res = $page->users->getById(0);
	} else {
		if ((!isset($_GET["i"]) || !isset($_GET["r"]))) {
			header("X-DNZB-RCode: 400");
			header("X-DNZB-RText: Bad request, please supply all parameters!");
			$page->show403();
		}

		$res = $page->users->getByIdAndRssToken($_GET["i"], $_GET["r"]);
		if (!$res) {
			header("X-DNZB-RCode: 401");
			header("X-DNZB-RText: Unauthorised, wrong user ID or rss key!");
			$page->show403();
		}
	}
	$uid = $res["id"];
	$rssToken = $res['rsstoken'];
	$maxDownloads = $res["downloadrequests"];
}

// Check download limit on user role.
$requests = $page->users->getDownloadRequests($uid);
if ($requests > $maxDownloads) {
	header("X-DNZB-RCode: 503");
	header("X-DNZB-RText: User has exceeded maximum downloads for the day!");
	$page->show503();
}

if (!isset($_GET['id'])) {
	header("X-DNZB-RCode: 400");
	header("X-DNZB-RText: Bad request! (parameter id is required)");
	$page->show403();
}

// Remove any suffixed id with .nzb which is added to help weblogging programs see nzb traffic.
$_GET['id'] = str_ireplace('.nzb', '', $_GET['id']);

$rel = new Releases(['Settings' => $page->settings]);
// User requested a zip of guid,guid,guid releases.
if (isset($_GET["zip"]) && $_GET["zip"] == "1") {
	$guids = explode(",", $_GET["id"]);
	if ($requests['num'] + sizeof($guids) > $maxDownloads) {
		header("X-DNZB-RCode: 503");
		header("X-DNZB-RText: User has exceeded maximum downloads for the day!");
		$page->show503();
	}

	$zip = $rel->getZipped($guids);
	if (strlen($zip) > 0) {
		$page->users->incrementGrabs($uid, count($guids));
		foreach ($guids as $guid) {
			$rel->updateGrab($guid);
			$page->users->addDownloadRequest($uid);

			if (isset($_GET["del"]) && $_GET["del"] == 1) {
				$page->users->delCartByUserAndRelease($guid, $uid);
			}
		}

		header("Content-type: application/octet-stream");
		header("Content-disposition: attachment; filename=" .  date("Ymdhis") . ".nzb.zip");
		exit($zip);
	} else {
		$page->show404();
	}
}

$nzbPath = (new NZB($page->settings))->getNZBPath($_GET["id"]);
if (!file_exists($nzbPath)) {
	header("X-DNZB-RCode: 404");
	header("X-DNZB-RText: NZB file not found!");
	$page->show404();
}

$relData = $rel->getByGuid($_GET["id"]);
if ($relData) {
	$rel->updateGrab($_GET["id"]);
	$page->users->addDownloadRequest($uid);
	$page->users->incrementGrabs($uid);
	if (isset($_GET["del"]) && $_GET["del"] == 1) {
		$page->users->delCartByUserAndRelease($_GET["id"], $uid);
	}
} else {
	header("X-DNZB-RCode: 404");
	header("X-DNZB-RText: Release not found!");
	$page->show404();
}

// Start reading output buffer.
ob_start();
// De-gzip the NZB and store it in the output buffer.
readgzfile($nzbPath);

// Set the NZB file name.
header("Content-Disposition: attachment; filename=" . str_replace(array(',', ' '), '_', $relData["searchname"]) . ".nzb");
// Get the size of the NZB file.
header("Content-Length: " . ob_get_length());
header("Content-Type: application/x-nzb");
header("Expires: " . date('r', time() + 31536000));
// Set X-DNZB header data.
header("X-DNZB-Failure: " . $page->serverurl . 'failed/' . '?guid=' . $_GET['id'] . '&userid=' . $uid . '&rsstoken=' . $rssToken);
header("X-DNZB-Category: " . $relData["category_name"]);
header("X-DNZB-Details: " . $page->serverurl . 'details/' . $_GET["id"]);
if (!empty($relData['imdbid']) && $relData['imdbid'] > 0) {
	header("X-DNZB-MoreInfo: http://www.imdb.com/title/tt" . $relData['imdbid']);
} else if (!empty($relData['rageid']) && $relData['rageid'] > 0) {
	header("X-DNZB-MoreInfo: http://www.tvrage.com/shows/id-" . $relData['rageid']);
}
header("X-DNZB-Name: " . $relData["searchname"]);
if ($relData['nfostatus'] == 1) {
	header("X-DNZB-NFO: " . $page->serverurl . 'nfo/' . $_GET["id"]);
}
header("X-DNZB-RCode: 200");
header("X-DNZB-RText: OK, NZB content follows.");

// Print buffer and flush it.
ob_end_flush();
