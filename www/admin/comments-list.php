<?php
require_once realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'smarty.php');

use app\models\ReleaseComments;

$page = new AdminPage();
$page->title = "Comments List";

$count = ReleaseComments::find('count', []);

$pageno = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1);
$page->smarty->assign(
	[
		'commentslist'		=> ReleaseComments::findRange($pageno, ITEMS_PER_PAGE)->to('array'),
		'pagecurrent'		=> (int)$pageno,
		'pagemaximum'		=> (int)($count / ITEMS_PER_PAGE) + 1,
		'pager'				=> $page->smarty->fetch("paginate.tpl"),
		'pagerquerybase'	=> WWW_TOP . "/comments-list.php?page=",
		'pagerquerysuffix'	=> '',
		'pagertotalitems'	=> $count,
	]
);

$page->content = $page->smarty->fetch('comments-list.tpl');
$page->render();
