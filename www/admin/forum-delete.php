<?php
require_once realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'smarty.php');

use nzedb\Forum;

$page = new AdminPage();

if (isset($_GET['id'])) {
	(new Forum(['Settings' => $page->settings]))->deletePost($_GET['id']);
}

if (isset($_GET['from'])) {
	$referrer = $_GET['from'];
} else {
	$referrer = $_SERVER['HTTP_REFERER'];
}
header("Location: " . $referrer);
