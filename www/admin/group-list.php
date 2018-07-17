<?php
require_once './config.php';

use app\models\Groups;

$page = new AdminPage();
$page->title = $page->meta_title = 'Group List';

$pageno = $_REQUEST['pageno'] ?? 1;

$groupname = (isset($_REQUEST['groupname']) && !empty($_REQUEST['groupname'])) ? $_REQUEST['groupname'] : '';

$conditions = empty($groupname) ? [] : ['name' => ['LIKE' => "%$groupname%"]];

$count = Groups::find('count', ['conditions' => $conditions]);
$grouplist = Groups::getRange($pageno, ITEMS_PER_PAGE, $groupname);

$groupsearch = empty($_REQUEST['groupname']) ? '' : '&amp;groupname=' . $_REQUEST['groupname'];

$page->smarty->assign(
	[
		'groupname'        => $groupname,
		'pagecurrent'      => (int)$pageno,
		'pagerlast'        => (int)($count / ITEMS_PER_PAGE) + 1,
		'pagerquerybase'   => WWW_TOP . '/group-list.php?pageno=',
		'pagerquerysuffix' => $groupsearch,
		'pagertotalitems'  => $count,
		'results'          => $grouplist,
		'tz'               => \lithium\data\Connections::config()['default']['object']->timezone(),
	]
);

// Pager must be set outside the main assignment, so it can receive the scope of those variables.
$page->smarty->assign('pager', $page->smarty->fetch('paginate.tpl'));

$page->content = $page->smarty->fetch('group-list.tpl');
$page->render();

?>
