<?php
require_once realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'smarty.php');

use nzedb\Menu;

$page = new AdminPage();

if (isset($_GET['id'])) {
	$menu = new Menu($page->settings);
	$menu->delete($_GET['id']);
}

$referrer = $_SERVER['HTTP_REFERER'];
header("Location: " . $referrer);
