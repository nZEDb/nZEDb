<?php

require_once realpath('config.php');

require_once nZEDb_WWW . 'autoloader.php';

$page = new AdminPage();

$page->title   = "Admin Hangout";
$page->content = $page->smarty->fetch('index.tpl');
$page->render();

?>
