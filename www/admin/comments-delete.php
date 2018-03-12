<?php
require_once './config.php';

use nzedb\ReleaseComments;

$page = new AdminPage();

if (isset($_GET['id'])) {
	$relComments = new ReleaseComments($page->settings);
	$relComments->deleteComment($_GET['id']);
}

$referrer = $_SERVER['HTTP_REFERER'];
header('Location: ' . $referrer);
