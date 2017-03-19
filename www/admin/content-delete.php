<?php
require_once realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'smarty.php');

use nzedb\Contents;

$page = new AdminPage();

if (isset($_GET['id'])) {
	$contents = new Contents(['Settings' => $page->settings]);
	$contents->delete($_GET['id']);
}

$referrer = $_SERVER['HTTP_REFERER'];
header("Location: " . $referrer);
