<?php

require_once realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'smarty.php');

$page = new AdminPage();

$page->title   = "Admin Hangout";
$page->content = $page->smarty->fetch('index.tpl');
$page->render();

?>
