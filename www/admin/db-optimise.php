<?php
require_once './config.php';
//require_once nZEDb_LIB . 'adminpage.php';


$page = new AdminPage();
$db = new DB();

$tablelist = $db->optimise(true);

$page->title = "DB Table Optimise";
$page->smarty->assign('tablelist', $tablelist);
$page->content = $page->smarty->fetch('db-optimise.tpl');
$page->render();
