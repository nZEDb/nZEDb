<?php

require_once("config.php");
require_once(WWW_DIR."/lib/adminpage.php");
require_once(WWW_DIR."/lib/framework/db.php");

$page = new AdminPage();

$db = new DB;
$tablelist = $db->optimise();

$page->title = "DB Table Optimise";
$page->smarty->assign('tablelist',$tablelist);	
$page->content = $page->smarty->fetch('db-optimise.tpl');
$page->render();

?>
