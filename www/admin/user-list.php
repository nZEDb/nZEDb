<?php
require_once './config.php';

$page = new AdminPage();

$page->title = "User List";

$usercount = $page->users->getCount();
$userroles = $page->users->getRoles();
$roles     = [];
foreach ($userroles as $r) {
	$roles[$r['id']] = $r['name'];
}

$offset = isset($_REQUEST["offset"]) ? $_REQUEST["offset"] : 0;
$ordering = $page->users->getBrowseOrdering();
$orderby = isset($_REQUEST["ob"]) && in_array($_REQUEST['ob'], $ordering) ? $_REQUEST["ob"] : '';

$usearch = $username = $email = $host = $role = '';
if (isset($_REQUEST["username"])) {
	$username = $_REQUEST["username"];
	$usearch .= '&amp;username=' . $username;
}

if (isset($_REQUEST["email"])) {
	$email = $_REQUEST["email"];
	$usearch .= '&amp;email=' . $email;
}

if (isset($_REQUEST["host"])) {
	$host = $_REQUEST["host"];
	$usearch .= '&amp;host=' . $host;
}

if (isset($_REQUEST["role"]) && array_key_exists($_REQUEST['role'], $roles)) {
	$role = $_REQUEST["role"];
	$usearch .= '&amp;role=' . $role;
}

$page->smarty->assign('username', $username);
$page->smarty->assign('email', $email);
$page->smarty->assign('host', $host);
$page->smarty->assign('role', $role);
$page->smarty->assign('role_ids', array_keys($roles));
$page->smarty->assign('role_names', $roles);

$page->smarty->assign('pagertotalitems', $usercount);
$page->smarty->assign('pageroffset', $offset);
$page->smarty->assign('pageritemsperpage', ITEMS_PER_PAGE);
$page->smarty->assign('pagerquerybase',
					  WWW_TOP . "/user-list.php?ob=" . $orderby . $usearch . "&amp;offset=");

$pager = $page->smarty->fetch("pager.tpl");
$page->smarty->assign('pager', $pager);

foreach ($ordering as $ordertype) {
	$page->smarty->assign('orderby' . $ordertype,
						  WWW_TOP . "/user-list.php?ob=" . $ordertype . "&amp;offset=0");
}

$userlist = $page->users->getRange($offset,
								   ITEMS_PER_PAGE,
								   $orderby,
								   $username,
								   $email,
								   $host,
								   $role,
								   true);
$page->smarty->assign('userlist', $userlist);

$page->content = $page->smarty->fetch('user-list.tpl');
$page->render();
