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

if (!empty($_POST)) {
	if (!empty($_POST['sharing_name'])) {
		$site_name = trim($_POST['sharing_name']);
	} else {
		$site_name = $ourSite['site_name'];
	}
}

$page->smarty->assign(array('local' => $ourSite, 'sites' => $allSites));

$page->content = $page->smarty->fetch('sharing.tpl');
$page->render();