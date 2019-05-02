<?php
require_once './config.php';

use Cake\ORM\TableRegistry;

$page = new AdminPage();

$page->title = 'Binary Black/Whitelist List';

$bbl = TableRegistry::getTableLocator()->get('Binaryblacklist');
$query = $bbl->find('all')
	->select([
		'id',
		'optype',
		'status',
		'description',
		'groupname',
		'regex',
		'msgcol',
		'last_activity'
	])
	->order('groupname');

$page->smarty->assign('binlist', $query->all());

$page->content = $page->smarty->fetch('binaryblacklist-list.tpl');
$page->render();

?>
