<?php

use app\models\Settings;
use nzedb\Releases;
use nzedb\NZB;
use nzedb\db\DB;
use nzedb\utility\Misc;

$uid = 0;

// Page is accessible only by the rss token, or logged in users.
if ($page->users->isLoggedIn()) {
	$uid = $page->users->currentUserId();
	$maxDownloads = $page->userdata["downloadrequests"];
	$rssToken = $page->userdata['rsstoken'];
	if ($page->users->isDisabled($page->userdata['username'])) {
		Misc::showApiError(101);
	}
} else {
	if (Settings::value('..registerstatus') == Settings::REGISTER_STATUS_API_ONLY) {
		$res = $page->users->getById(0);
	} else {
		if ((!isset($_GET["i"]) || !isset($_GET["r"]))) {
			Misc::showApiError(200);
		}

		$res = $page->users->getByIdAndRssToken($_GET["i"], $_GET["r"]);
		if (!$res) {
			Misc::showApiError(100);
		}
	}
	$uid = $res["id"];
	$rssToken = $res['rsstoken'];
	$maxDownloads = $res["downloadrequests"];
	if ($page->users->isDisabled($res['username'])) {
		Misc::showApiError(101);
	}
}

// Check download limit on user role.
$requests = $page->users->getDownloadRequests($uid);
if ($requests > $maxDownloads) {
	Misc::showApiError(429);
}

if (!isset($_GET['id'])) {
	Misc::showApiError(400, 'parameter id is required');
}

// Remove any suffixed id with .nzb which is added to help weblogging programs see nzb traffic.
$_GET['id'] = str_ireplace('.nzb', '', $_GET['id']);

$rel = new Releases(['Settings' => $page->settings]);
// User requested a zip of guid,guid,guid releases.
if (isset($_GET["zip"]) && $_GET["zip"] == "1") {
	$guids = explode(",", $_GET["id"]);
	if ($requests['num'] + sizeof($guids) > $maxDownloads) {
		Misc::showApiError(501);
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
		header("Content-disposition: attachment; filename=" . date("Ymdhis") . ".nzb.zip");
		exit($zip);
	} else {
		$page->show404();
	}
}

$nzbPath = (new NZB($page->settings))->getNZBPath($_GET["id"]);
if (!file_exists($nzbPath)) {
	Misc::showApiError(300, 'NZB file not found!');
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
	Misc::showApiError(300, 'Release not found!');
}

// Start reading output buffer.
ob_start();
// De-gzip the NZB and store it in the output buffer.
readgzfile($nzbPath);

$cleanName = str_replace(array(',', ' ', '/'), '_', $relData["searchname"]);

// Set the NZB file name.
header("Content-Disposition: attachment; filename=" . $cleanName . ".nzb");
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
} else if (!empty($relData['tvdb']) && $relData['tvdb'] > 0) {
	header("X-DNZB-MoreInfo: http://www.thetvdb.com/?tab=series&id=" . $relData['tvdb']);
}
header("X-DNZB-Name: " . $cleanName);
if ($relData['nfostatus'] == 1) {
	header("X-DNZB-NFO: " . $page->serverurl . 'nfo/' . $_GET["id"]);
}
header("X-DNZB-RCode: 200");
header("X-DNZB-RText: OK, NZB content follows.");

// Print buffer and flush it.
ob_end_flush();
