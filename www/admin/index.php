<?php
require_once './config.php';
require_once nZEDb_LIB . 'adminpage.php';

$page = new AdminPage();

$page->title = "Admin Hangout";
$page->content = $page->smarty->fetch('index.tpl');
$page->render();

?>
