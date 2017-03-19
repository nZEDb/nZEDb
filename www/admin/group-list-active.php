<?php
require_once realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'smarty.php');

use app\models\Groups;

$page = new AdminPage();
$page->title = "Group List";

$groupName = (isset($_REQUEST['groupname']) && !empty($_REQUEST['groupname']) ?
	$_REQUEST['groupname'] : '');
$search = $groupName != '' ? "groupname=$groupName&amp;" : '';

$count = Groups::findRangeCount($groupName, 1);

$pageno = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
$page->smarty->assign(
	[
		'grouplist'			=> Groups::findRange($pageno, ITEMS_PER_PAGE, $groupName, 1)->to('array'),
		'groupname'			=> $groupName,
		'pagecurrent'		=> (int)$pageno,
		'pagemaximum'		=> (int)($count / ITEMS_PER_PAGE) + 1,
		'pager'				=> $page->smarty->fetch("paginate.tpl"),
		'pagerquerybase'	=> WWW_TOP . "/group-list-active.php?" . $search . "offset=",
		'pagerquerysuffix'	=> '',
		'pagertotalitems'	=> $count,
	]
);

$page->content = $page->smarty->fetch('group-list.tpl');
$page->render();
