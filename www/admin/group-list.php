<?php
require_once './config.php';

use nzedb\Groups;

$page   = new AdminPage();
$groups = new Groups(['Settings' => $page->settings]);

$groupName = (isset($_REQUEST['groupname']) && !empty($_REQUEST['groupname']) ? $_REQUEST['groupname'] : '');
$offset    = (isset($_REQUEST['offset']) ? $_REQUEST['offset'] : 0);

$page->smarty->assign(
	[
		'groupname' => $groupName,
		'pagertotalitems' => $groups->getCount($groupName),
		'pageroffset' => $offset,
		'pageritemsperpage' => ITEMS_PER_PAGE,
		'pagerquerybase' =>
			WWW_TOP . "/group-list.php?" . (($groupName != '') ? "groupname=$groupName&amp;": '') . 'offset=',
		'pagerquerysuffix' => '',
		'grouplist' => $groups->getRange($offset, ITEMS_PER_PAGE, $groupName)
	]
);
$page->smarty->assign('pager', $page->smarty->fetch('pager.tpl'));

$page->title = 'Group List';
$page->content = $page->smarty->fetch('group-list.tpl');
$page->render();