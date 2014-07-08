<?php
require_once './config.php';

use nzedb\db\Settings;

$page = new AdminPage();
$pdo = new Settings();

$tablelist = $pdo->optimise(true);

$page->title = "DB Table Optimise";
$page->smarty->assign('tablelist', $tablelist);
$page->content = $page->smarty->fetch('db-optimise.tpl');
$page->render();
