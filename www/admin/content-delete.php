<?php
require_once './config.php';
//require_once nZEDb_LIB . 'content.php';
//require_once nZEDb_LIB . 'adminpage.php';

$page = new AdminPage();

if (isset($_GET['id']))
{
	$contents = new Contents();
	$contents->delete($_GET['id']);
}

$referrer = $_SERVER['HTTP_REFERER'];
header("Location: " . $referrer);

?>
