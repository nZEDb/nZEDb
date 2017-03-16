<?php
require_once './config.php';

use nzedb\Groups;

$page   = new AdminPage();
$page->title = "Group List";

$groups = new Groups(['Settings' => $page->settings]);

$groupname = (isset($_REQUEST['groupname']) && !empty($_REQUEST['groupname'])) ?
	$_REQUEST['groupname'] : '';

// TODO modelise.
$count = $groups->getCount($gname, 0);

$offset    = isset($_REQUEST["offset"]) ? $_REQUEST["offset"] : 0;

$page->smarty->assign('groupname', $gname);
$page->smarty->assign('pageroffset', $offset);

$groupsearch = ($gname != "") ? 'groupname=' . $gname . '&amp;' : '';

$grouplist = $groups->getRange($offset, ITEMS_PER_PAGE, $gname, 0);
$page->smarty->assign('grouplist', $grouplist);

$pageno = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
$page->smarty->assign(
	[
		'pagecurrent'      => (int)$pageno,
		'pagemaximum'      => (int)($count / ITEMS_PER_PAGE) + 1,
		'pager'            => $page->smarty->fetch("pagination.tpl"),
		'pagerquerybase'   => WWW_TOP . "/group-list-inactive.php?" . $groupsearch . "offset=",
		'pagerquerysuffix' => '',
		'pagertotalitems'  => $count,
	]
);

$page->content = $page->smarty->fetch('group-list.tpl');
$page->render();
