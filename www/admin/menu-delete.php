<?php
require_once './config.php';
//require_once nZEDb_LIB . 'menu.php';
//require_once nZEDb_LIB . 'adminpage.php';

$page = new AdminPage();

if (isset($_GET['id']))
{
	$menu = new Menu();
	$menu->delete($_GET['id']);
}

$referrer = $_SERVER['HTTP_REFERER'];
header("Location: " . $referrer);

?>
