<?php
require_once realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'smarty.php');

$page = new AdminPage();

if (isset($_GET['id'])) {
	$page->users->delete($_GET['id']);
}

$referrer = $_SERVER['HTTP_REFERER'];
header("Location: " . $referrer);
