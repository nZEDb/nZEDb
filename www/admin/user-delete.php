<?php
require_once("config.php");
require_once(WWW_DIR."/lib/content.php");
require_once(WWW_DIR."/lib/adminpage.php");

$page = new AdminPage();

if (isset($_GET['id']))
{
	$users = new Users();
	$users->delete($_GET['id']);
}

$referrer = $_SERVER['HTTP_REFERER'];
header("Location: ".$referrer);

?>
