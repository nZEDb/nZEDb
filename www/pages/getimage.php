<?php

use nzedb\TvRage;

// Page is accessible only to logged in users.
if (!$page->users->isLoggedIn()) {
	$page->show403();
}

if (!isset($_GET["type"]) || !isset($_GET["id"]) || !ctype_digit($_GET["id"])) {
	$page->show404();
}

// User requested a tvrage image.
if ($_GET["type"] == "tvrage") {

	$rage = new TvRage(['Settings' => $page->settings]);
	$r = $rage->getByID($_GET["id"]);
	if (!$r) {
		$page->show404();
	}

	$imgdata = $r["imgdata"];

	header("Content-type: image/jpeg");
	print $imgdata;
} else {
	$page->show404();
}
