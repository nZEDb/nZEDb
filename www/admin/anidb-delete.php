<?php
require_once './config.php';

use nzedb\AniDB;

$page = new AdminPage();

if (isset($_GET['id'])) {
	$aniDB = new AniDB(['Settings' => $page->settings]);
	$aniDB->deleteTitle($_GET['id']);
}

$referrer = $_SERVER['HTTP_REFERER'];
header('Location: ' . $referrer);
