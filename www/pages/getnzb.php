<?php
require_once(WWW_DIR."/lib/releases.php");
require_once(WWW_DIR."/lib/nzb.php");

$nzb = new NZB;
$rel = new Releases;
$uid = 0;

// Page is accessible only by the rss token, or logged in users.
if ($users->isLoggedIn())
{
	$uid = $users->currentUserId();
	$maxdls = $page->userdata["downloadrequests"];
}
else
{

	if ($page->site->registerstatus == Sites::REGISTER_STATUS_API_ONLY)
		$res = $users->getById(0);
	else
	{
		if ((!isset($_GET["i"]) || !isset($_GET["r"])))
			$page->show403();

		$res = $users->getByIdAndRssToken($_GET["i"], $_GET["r"]);
		if (!$res)
			$page->show403();
	}
	$uid = $res["ID"];
	$maxdls = $res["downloadrequests"];
}

// Remove any suffixed id with .nzb which is added to help weblogging programs see nzb traffic.
if (isset($_GET["id"]))
	$_GET["id"] = preg_replace("/\.nzb/i", "", $_GET["id"]);

// Check download limit on user role.
$dlrequests = $users->getDownloadRequests($uid);
if ($dlrequests['num'] > $maxdls)
	$page->show503();

// User requested a zip of guid,guid,guid releases.
if (isset($_GET["id"]) && isset($_GET["zip"]) && $_GET["zip"] == "1")
{
	$guids = explode(",", $_GET["id"]);
	if ($dlrequests['num']+sizeof($guids) > $maxdls)
		$page->show503();

	$zip = $rel->getZipped($guids);
	if (strlen($zip) > 0)
	{
		$users->incrementGrabs($uid, count($guids));
		foreach ($guids as $guid)
		{
			$rel->updateGrab($guid);
			$users->addDownloadRequest($uid);

			if (isset($_GET["del"]) && $_GET["del"]==1)
				$users->delCartByUserAndRelease($guid, $uid);
		}

		$filename = date("Ymdhis").".nzb.zip";
		header("Content-type: application/octet-stream");
		header("Content-disposition: attachment; filename=".$filename);
		echo $zip;
		die();
	}
	else
		$page->show404();
}

if (isset($_GET["id"]))
{
	$reldata = $rel->getByGuid($_GET["id"]);
	$nzbpath = $nzb->getNZBPath($_GET["id"], $page->site->nzbpath, false, $page->site->nzbsplitlevel);

	if (!file_exists($nzbpath))
		$page->show404();

	if ($reldata)
	{
		$rel->updateGrab($_GET["id"]);
		$users->addDownloadRequest($uid);
		$users->incrementGrabs($uid);
		if (isset($_GET["del"]) && $_GET["del"]==1)
			$users->delCartByUserAndRelease($_GET["id"], $uid);
	}
	else
		$page->show404();

	header("Content-type: application/x-nzb");
	header("X-DNZB-Name: ".$reldata["searchname"]);
	header("X-DNZB-Category: ".$reldata["category_name"]);
	header("X-DNZB-MoreInfo: "); //TODO:
	header("X-DNZB-NFO: "); //TODO:
	header("Content-Disposition: attachment; filename=".str_replace(array(',', ''), '_', $reldate['searchname']).".nzb");

	readgzfile($nzbpath);
}

?>
