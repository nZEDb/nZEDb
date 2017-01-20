<?php
require_once './config.php';

use app\models\Groups as GroupInfo;
use nzedb\Groups;

$page   = new AdminPage();
$groups = new Groups(['Settings' => $page->settings]);

$groupName = (isset($_REQUEST['groupname']) && !empty($_REQUEST['groupname']) ? $_REQUEST['groupname'] : '');
$offset = (isset($_REQUEST['offset']) ? $_REQUEST['offset'] : 0);
$pageno = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);

$page->smarty->assign(
	[
		'pagecurrent'		=> $pageno,
		'pagemaximum'		=> (int)($groups->getCount($groupName, -1) / ITEMS_PER_PAGE) + 1,
		'groupname' 		=> $groupName,
		'pagertotalitems'	=> $groups->getCount($groupName, -1),
		'pageroffset'		=> $offset,
		'pageritemsperpage'	=> ITEMS_PER_PAGE,
		'pagerquerybase'	=>
			WWW_TOP . "/group-list.php?" . (($groupName != '') ? "groupname=$groupName&amp;" : '') . 'page=',
		'pagerquerysuffix'	=> '',
		'grouplist'			=> GroupInfo::findRange($pageno, ITEMS_PER_PAGE, $groupName)
	]
);
$page->smarty->assign('pager', $page->smarty->fetch('pager.tpl'));

$page->title = 'Group List';
$page->content = $page->smarty->fetch('group-list.tpl');
$page->render();
