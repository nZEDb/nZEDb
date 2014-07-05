<?php

use nzedb\db\Settings;

// Page is accessible only to logged in users.
if (!$users->isLoggedIn()) {
	$page->show403();
}

if (!isset($_GET["type"]) || !isset($_GET["id"]) || !ctype_digit($_GET["id"])) {
	$page->show404();
}

// User requested a tvrage image.
if ($_GET["type"] == "tvrage") {
	$pdo = new Settings();
	if ($pdo->dbSystem() === 'mysql') {
		$rage = new TvRage();
		$r = $rage->getByID($_GET["id"]);
		if (!$r) {
			$page->show404();
		}

		$imgdata = $r["imgdata"];
	} else if ($pdo->dbSystem() === 'pgsql') {
		$pdo = new Settings(); // Creates the nZEDb_COVERS constant
		$imgdata = @file_get_contents(nZEDb_COVERS . 'tvrage/' . $_GET['id'] . '.jpg');
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
