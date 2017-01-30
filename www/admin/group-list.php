<?php
require_once './config.php';

use app\models\Groups as GroupInfo;
use nzedb\Groups;

$page   = new AdminPage();
$groups = new Groups(['Settings' => $page->settings]);

$count = $groups->getCount($groupName, -1);
$groupName = (isset($_REQUEST['groupname']) && !empty($_REQUEST['groupname']) ? $_REQUEST['groupname'] : '');
$pageno = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
$search = $groupName != '' ? "groupname=$groupName&amp;" : '';
$page->smarty->assign(
	[
		'grouplist'			=> GroupInfo::findRange($pageno, ITEMS_PER_PAGE, $groupName)->to('array'),
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
