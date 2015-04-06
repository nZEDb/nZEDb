<?php
require_once realpath(__DIR__ . DIRECTORY_SEPARATOR . 'config.php');

$page = new AdminPage();

$page->title   = "Admin Hangout";
$page->content = $page->smarty->fetch('index.tpl');
$page->render();
