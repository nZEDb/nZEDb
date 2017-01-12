<?php
require_once './config.php';

use nzedb\db\DB;

$page   = new AdminPage();
$pdo = new DB();
$posterslist = $pdo->query(sprintf('SELECT * FROM multigroup_posters'));


$poster = (isset($_REQUEST['poster']) && !empty($_REQUEST['poster']) ? $_REQUEST['poster'] : '');

$page->smarty->assign(
	[
		'poster' => $poster,
		'posterslist' => $posterslist
	]
);

$page->title = 'MultiGroup Posters List';
$page->content = $page->smarty->fetch('posters-list.tpl');
$page->render();
