<?php
require_once './config.php';

use app\models\Groups;

$page = new AdminPage();
$page->title = 'Group List';

$count = Groups::findRangeCount($groupName, -1);

$groupName = (isset($_REQUEST['groupname']) && !empty($_REQUEST['groupname']) ? $_REQUEST['groupname'] : '');
$search = $groupName != '' ? "groupname=$groupName&amp;" : '';

$pageno = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
$page->smarty->assign(
	[
		'grouplist'			=> Groups::findRange($pageno, ITEMS_PER_PAGE, $groupName)->to('array'),
		'groupname'			=> $groupName,
		'pagecurrent'		=> (int)$pageno,
		'pagemaximum'		=> (int)($count / ITEMS_PER_PAGE) + 1,
		'pagertotalitems'	=> $count,
		'pagerquerybase'	=> WWW_TOP . "/group-list.php?" . $search . 'page='
	]
);
$pager = $page->smarty->fetch("pagination.tpl");
$page->smarty->assign('pager', $pager);

$page->content = $page->smarty->fetch('group-list.tpl');
$page->render();
