<?php
require_once './config.php';

use app\models\ReleaseComments;

$page = new AdminPage();
$page->title = "Comments List";

$count = ReleaseComments::find('count', []);
$pageno = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);

$page->smarty->assign(
	[
		'commentslist'    => ReleaseComments::findRange($pageno, ITEMS_PER_PAGE)->to('array'),
		'pagecurrent'     => (int)$pageno,
		'pagemaximum'     => (int)($count / ITEMS_PER_PAGE) + 1,
		'pagertotalitems' => $count,
		'pagerquerybase'  => WWW_TOP . "/comments-list.php?page="
	]
);
$pager = $page->smarty->fetch("pager.tpl");
$page->smarty->assign('pager', $pager);

$page->content = $page->smarty->fetch('comments-list.tpl');
$page->render();
