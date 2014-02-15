<?php
require_once './config.php';


$page = new AdminPage();

$page->title = "Admin Hangout";
$page->content = $page->smarty->fetch('index.tpl');
$page->render();

?>
