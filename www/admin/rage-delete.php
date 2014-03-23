<?php
require_once './config.php';


$page = new AdminPage();

if (isset($_GET['id']))
{
	$tvrage = new TvRage();
	$tvrage->delete($_GET['id']);
}

$referrer = $_SERVER['HTTP_REFERER'];
header("Location: " . $referrer);

?>
