<?php
require_once realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'smarty.php');

$page = new AdminPage();
$page->title = "User List";

$roles = [];
foreach ($page->users->getRoles() as $userRole) {
	$roles[$userRole['id']] = $userRole['name'];
}

$offset = isset($_REQUEST["offset"]) ? $_REQUEST["offset"] : 0;
$ordering = $page->users->getBrowseOrdering();
$orderBy = isset($_REQUEST["ob"]) && in_array($_REQUEST['ob'], $ordering) ? $_REQUEST["ob"] : '';

$variables = ['username' => '', 'email' => '', 'host' => '', 'role' => ''];
$uSearch = '';
foreach ($variables as $key => $variable) {
	checkREQUEST($key);
}

// TODO modelise.
$page->smarty->assign([
		'username'          => $variables['username'],
		'email'             => $variables['email'],
		'host'              => $variables['host'],
		'role'              => $variables['role'],
		'role_ids'          => array_keys($roles),
		'role_names'        => $roles,
		'pageroffset'       => $offset,
		'userlist' => $page->users->getRange(
			$offset, ITEMS_PER_PAGE, $orderBy, $variables['username'],
			$variables['email'], $variables['host'], $variables['role'], true
		)
	]
);

foreach ($ordering as $orderType) {
	$page->smarty->assign('orderby' . $orderType, WWW_TOP . "/user-list.php?ob=" . $orderType . "&amp;offset=0");
}
$count = $page->users->getCount();

$pageno = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
$page->smarty->assign(
	[
		'pagecurrent'      => (int)$pageno,
		'pagemaximum'      => (int)($count / ITEMS_PER_PAGE) + 1,
		'pagerquerybase'   => WWW_TOP . "/user-list.php?ob=" . $orderBy . $uSearch . "&amp;offset=",
		'pagerquerysuffix' => '',
		'pagertotalitems'  => $count,
	]
);
// Pager has to be set outside of main assign, or it will no recieve the scope of those variables.
$page->smarty->assign('pager', $page->smarty->fetch("paginate.tpl"));

$page->content = $page->smarty->fetch('user-list.tpl');
$page->render();

function checkREQUEST($param) {
	global $uSearch, $variables;
	if (isset($_REQUEST[$param])) {
		$variables[$param] = $_REQUEST[$param];
		$uSearch .= "&amp;$param=" . $_REQUEST[$param];
	}
}
