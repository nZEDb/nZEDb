<?php
require_once './config.php';
require_once nZEDb_WWW . 'pages/smartyTV.php';

$page = new AdminPage();

if (isset($_GET['id'])) {
	(new smartyTV(['Settings' => $page->settings]))->delete($_GET['id']);
}

$referrer = $_SERVER['HTTP_REFERER'];
header("Location: " . $referrer);
