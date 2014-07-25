<?php
require_once './config.php';

$page = new AdminPage();

if (isset($_GET['id'])) {
	(new Users(['Settings' => $page->settings]))->deleteRole($_GET['id']);
}

$referrer = $_SERVER['HTTP_REFERER'];
header("Location: " . $referrer);
