<?php
// Page is accessible only to logged in users.
if (!$users->isLoggedIn()) {
	$page->show403();
}

if (!isset($_GET["type"]) || !isset($_GET["id"]) || !ctype_digit($_GET["id"])) {
	$page->show404();
}

// User requested a tvrage image.
if ($_GET["type"] == "tvrage") {
	$db = new DB();
	if ($db->dbSystem() == 'mysql') {
		$rage = new TvRage();
		$r = $rage->getByID($_GET["id"]);
		if (!$r) {
			$page->show404();
		}

		$imgdata = $r["imgdata"];
	} else if ($db->dbSystem() == 'pgsql') {
		$imgdata = @file_get_contents(nZEDb_WWW . 'covers/tvrage/' . $_GET['id'] . '.jpg');
		if ($imgdata === false) {
			$page->show404();
		}
	}
	header("Content-type: image/jpeg");
	print $imgdata;
	die();
} else {
	$page->show404();
}
