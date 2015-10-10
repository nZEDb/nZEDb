<?php
require_once './config.php';

use nzedb\Releases;

$page = new AdminPage(true);

if (isset($_GET['id'])) {
	$releases = new Releases(['Settings' => $page->settings]);
	$releases->deleteMultiple($_GET['id']);
}

if (isset($_GET['from'])) {
	$referrer = $_GET['from'];
} else {
	$referrer = $_SERVER['HTTP_REFERER'];
}
header("Location: " . $referrer);
