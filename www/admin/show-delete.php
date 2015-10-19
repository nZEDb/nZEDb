<?php
require_once './config.php';

use nzedb\processing\tv\TV;

$page = new AdminPage();

if (isset($_GET['id'])) {
	(new TV(['Settings' => $page->settings]))->delete($_GET['id']);
}

$referrer = $_SERVER['HTTP_REFERER'];
header("Location: " . $referrer);
