<?php

require_once realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'smarty.php');
require_once nZEDb_ROOT . 'app' . DS . 'config' . DS . 'bootstrap' . DS . 'libraries.php';

$page = new AdminPage();

$page->title   = "Admin Hangout";
$page->content = $page->smarty->fetch('index.tpl');
$page->render();

?>
