<?php
require_once './config.php';

use nzedb\Groups;

$page   = new AdminPage();
$page->title = "Group List";

$groups = new Groups(['Settings' => $page->settings]);

$gname = "";
if (isset($_REQUEST['groupname']) && !empty($_REQUEST['groupname'])) {
	$gname = $_REQUEST['groupname'];
}

// TODO modelise.
$count = $groups->getCount($gname, 1);

$offset    = isset($_REQUEST["offset"]) ? $_REQUEST["offset"] : 0;
$groupname = (isset($_REQUEST['groupname']) && !empty($_REQUEST['groupname'])) ?
	$_REQUEST['groupname'] : '';

$page->smarty->assign('groupname', $groupname);
$page->smarty->assign('pageroffset', $offset);

$groupsearch = ($gname != "") ? 'groupname=' . $gname . '&amp;' : '';

$grouplist = $groups->getRange($offset, ITEMS_PER_PAGE, $gname, 1);
$page->smarty->assign('grouplist', $grouplist);

$pageno = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
$page->smarty->assign(
	[
		'pagecurrent'      => (int)$pageno,
		'pagemaximum'      => (int)($count / ITEMS_PER_PAGE) + 1,
		'pager'            => $page->smarty->fetch("paginate.tpl"),
		'pagerquerybase'   => WWW_TOP . "/group-list-active.php?" . $groupsearch . "offset=",
		'pagerquerysuffix' => '',
		'pagertotalitems'  => $count,
	]
);

$page->content = $page->smarty->fetch('group-list.tpl');
$page->render();
