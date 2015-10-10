<?php
require_once './config.php';

use nzedb\AniDB;

$page = new AdminPage();

if (isset($_GET['id'])) {
	$AniDB = new AniDB(['Settings' => $page->settings]);
	$AniDB->deleteTitle($_GET['id']);
}

$referrer = $_SERVER['HTTP_REFERER'];
header("Location: " . $referrer);
