<?php
require_once './config.php';

use app\models\Groups;

$page = new AdminPage();

$count = Groups::findRangeCount($groupName, -1);
$groupName = (isset($_REQUEST['groupname']) && !empty($_REQUEST['groupname']) ? $_REQUEST['groupname'] : '');
$pageno = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
$search = $groupName != '' ? "groupname=$groupName&amp;" : '';
$page->smarty->assign(
	[
		'grouplist'			=> Groups::findRange($pageno, ITEMS_PER_PAGE, $groupName)->to('array'),
		'groupname'			=> $groupName,
		'pagecurrent'		=> (int)$pageno,
		'pagemaximum'		=> (int)($count / ITEMS_PER_PAGE) + 1,
		'pager'				=> $page->smarty->fetch('pager.tpl'),
		'pagertotalitems'	=> $count,
		'pagerquerybase'	=> WWW_TOP . "/group-list.php?" . $search . 'page='
	]
);

$page->title = 'Group List';
$page->content = $page->smarty->fetch('group-list.tpl');
$page->render();
