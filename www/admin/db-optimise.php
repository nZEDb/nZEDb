<?php
require_once './config.php';
$page = new AdminPage();

$tablelist = $page->settings->optimise(true);

$page->title = "DB Table Optimise";
$page->smarty->assign('tablelist', $tablelist);
$page->content = $page->smarty->fetch('db-optimise.tpl');
$page->render();