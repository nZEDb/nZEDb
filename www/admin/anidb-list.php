<?php
require_once './config.php';

use app\models\AnidbTitles;
use nzedb\AniDB;

$page  = new AdminPage();
$AniDB = new AniDB(['Settings' => $page->settings]);

$page->title = 'AniDB Titles';

$name = '';

if (isset($_REQUEST['animetitle']) && !empty($_REQUEST['animetitle'])) {
	$name = $_REQUEST['animetitle'];
}

$options = $name == '' ? [] : ['conditions' => ['title' => ['LIKE' => "%$name%"]]];
$count = AnidbTitles::find('count', $options);
$pageno = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
$pager = $page->smarty->fetch('pager.tpl');
$search = ($name != '') ? 'animetitle=' . $name . '&amp;' : '';
$page->smarty->assign(
	[
		'anidblist'			=> (AnidbTitles::findRange($pageno, ITEMS_PER_PAGE, $name)),
		'animetitle'		=> $name,
		'pagecurrent'		=> (int)$pageno,
		'pagemaximum'		=> (int)($count / ITEMS_PER_PAGE) + 1,
		'pager'				=> $pager,
		'pagertotalitems'	=> $count,
		'pagerquerybase'	=> WWW_TOP . '/anidb-list.php?' . $search . '&page='
	]
);


$page->content = $page->smarty->fetch('anidb-list.tpl');
$page->render();
