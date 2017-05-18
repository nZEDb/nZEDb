<?php
require_once realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'smarty.php');

use app\models\Groups;

$page = new AdminPage();
$page->title = 'Group List';

$groupName = (isset($_REQUEST['groupname']) && !empty($_REQUEST['groupname']) ?
	$_REQUEST['groupname'] : '');
$search = $groupName != '' ? "groupname=$groupName&amp;" : '';

$count = Groups::findRangeCount($groupName, -1);

$pageno = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
$page->smarty->assign(
	[
		'grouplist'			=> Groups::findRange($pageno, ITEMS_PER_PAGE, $groupName)->to('array'),
		'groupname'			=> $groupName,
		'pagecurrent'		=> (int)$pageno,
		'pagemaximum'		=> (int)($count / ITEMS_PER_PAGE) + 1,
		'pagerquerybase'	=> WWW_TOP . "/group-list.php?" . $search . 'page=',
		'pagerquerysuffix'	=> '',
		'pagertotalitems'	=> $count,
	]
);
// Pager has to be set outside of main assign, or it will no recieve the scope of those variables.
$page->smarty->assign('pager', $page->smarty->fetch("paginate.tpl"));

$page->content = $page->smarty->fetch('group-list.tpl');
$page->render();
