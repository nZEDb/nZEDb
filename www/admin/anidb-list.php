<?php
require_once './config.php';

use app\models\AnidbTitles;
use nzedb\AniDB;

$page  = new AdminPage();
$page->title = 'AniDB Titles';

$AniDB = new AniDB(['Settings' => $page->settings]);

$name = '';

if (isset($_REQUEST['animetitle']) && !empty($_REQUEST['animetitle'])) {
	$name = $_REQUEST['animetitle'];
}
$search = ($name != '') ? 'animetitle=' . $name . '&amp;' : '';

$options = $name == '' ? [] : ['conditions' => ['title' => ['LIKE' => "%$name%"]]];
$count = AnidbTitles::find('count', $options);

$pageno = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
$page->smarty->assign(
	[
		'anidblist'			=> AnidbTitles::findRange($pageno, ITEMS_PER_PAGE, $name),
		'animetitle'		=> $name,
		'pagecurrent'		=> (int)$pageno,
		'pagemaximum'		=> (int)($count / ITEMS_PER_PAGE) + 1,
		'pager'				=> $page->smarty->fetch("pagination.tpl"),
		'pagerquerybase'	=> WWW_TOP . '/anidb-list.php?' . $search . '&page=',
		'pagerquerysuffix'	=> '',
		'pagertotalitems'	=> $count,
	]
);

$page->content = $page->smarty->fetch('anidb-list.tpl');
$page->render();
