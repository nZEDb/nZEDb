<?php
require_once './config.php';

use app\models\Groups as GroupInfo;
use nzedb\Groups;

$page   = new AdminPage();
$groups = new Groups(['Settings' => $page->settings]);

$groupName = (isset($_REQUEST['groupname']) && !empty($_REQUEST['groupname']) ? $_REQUEST['groupname'] : '');
$pageno = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);

$count = $groups->getCount($groupName, -1);

$page->smarty->assign(
	[
		'grouplist'			=> GroupInfo::findRange($pageno, ITEMS_PER_PAGE, $groupName),
		'groupname'			=> $groupName,
		'pagecurrent'		=> $pageno,
		'pagemaximum'		=> (int)($count / ITEMS_PER_PAGE) + 1,
		'pagertotalitems'	=> $count,
		'pagerquerybase'	=>
			WWW_TOP . "/group-list.php?" . (($groupName != '') ? "groupname=$groupName&amp;" : '') . 'page='
	]
);
$page->smarty->assign('pager', $page->smarty->fetch('pager.tpl'));

$page->title = 'Group List';
$page->content = $page->smarty->fetch('group-list.tpl');
$page->render();
