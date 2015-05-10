<?php
require_once './config.php';

use nzedb\TvRage;

$page = new AdminPage();

if (isset($_GET['id'])) {
	(new TvRage(['Settings' => $page->settings]))->delete($_GET['id']);
}

$referrer = $_SERVER['HTTP_REFERER'];
header("Location: " . $referrer);
