<?php
require_once realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'smarty.php');

use app\models\AnidbTitles;

$page  = new AdminPage();
$page->title = 'AniDB Titles';

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
		'pagerquerybase'	=> WWW_TOP . '/anidb-list.php?' . $search . '&page=',
		'pagerquerysuffix'	=> '',
		'pagertotalitems'	=> $count,
	]
);
// Pager has to be set outside of main assign, or it will no recieve the scope of those variables.
$page->smarty->assign('pager', $page->smarty->fetch("paginate.tpl"));

$page->content = $page->smarty->fetch('anidb-list.tpl');
$page->render();
