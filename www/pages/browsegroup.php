<?php

use app\models\Groups;

if (!$page->users->isLoggedIn()) {
	$page->show403();
}

$page->meta_title = 'Browse Groups';
$page->meta_keywords = 'browse,groups,description,details';
$page->meta_description = 'Browse groups';

$pageno = $_REQUEST['pageno'] ?? 1;

$grouplist = Groups::find('all',
	[
		'fields' => ['name', 'description', 'last_updated'],
		'limit'  => ITEMS_PER_PAGE,
		'order'  => ['name'],
		'page'   => $pageno,
	]
)->data();

$count = Groups::find('count', ['fields' => []]);

$page->smarty->assign(
	[
		//'lastSearch'       => $lastsearch,
		'pagecurrent'      => (int)$pageno,
		'pagerlast'        => (int)($count / ITEMS_PER_PAGE) + 1,
		'pagerquerybase'   => WWW_TOP . '/browsegroup?pageno=',
		'pagerquerysuffix' => '',
		'pagertotalitems'  => $count,
		'results'          => $grouplist,
		'tz'               => \lithium\data\Connections::config()['default']['object']->timezone(),
	]
);

// Pager must be set outside the main assignment, so it can receive the scope of those variables.
$page->smarty->assign('pager', $page->smarty->fetch('paginate.tpl'));

$page->content = $page->smarty->fetch('browsegroup.tpl');
$page->render();

?>
