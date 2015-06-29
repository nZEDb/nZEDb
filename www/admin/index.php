<?php

require_once realpath(__DIR__ . DIRECTORY_SEPARATOR . 'config.php');

require_once SMARTY_DIR . 'Autoloader.php';

Smarty_Autoloader::register();

require_once 'autoloader.php';

$page = new AdminPage();

$page->title   = "Admin Hangout";
$page->content = $page->smarty->fetch('index.tpl');
$page->render();

?>
