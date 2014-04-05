<?php
require_once './config.php';
$page = new AdminPage();
$page->title = 'Sharing Settings';

use nzedb\db\DB;
$db = new DB();
$allSites = $db->query('SELECT * FROM sharing_sites');
if (count($allSites) === 0) {
	$allSites = false;
}

$ourSite = $db->queryOneRow('SELECT * FROM sharing');

$page->smarty->assign(array('local' => $ourSite, 'sites' => $allSites));

$page->content = $page->smarty->fetch('sharing.tpl');
$page->render();