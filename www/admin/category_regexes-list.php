<?php
require_once './config.php';

use app\models\CategoryRegexes;
use nzedb\Regexes;

$page = new AdminPage();
$page->title = "Category Regex List";

$group = ((isset($_REQUEST['group']) && !empty($_REQUEST['group'])) ? $_REQUEST['group'] : '');
$pageno = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 1);

$options = [];
if ($group != '') {
	$options += ['conditions' => ['group_regex' => ['LIKE' => "%$name%"]]];
}

$count = CategoryRegexes::find('count', $options);

if ($count > ITEMS_PER_PAGE) {
	$options += [
		'limit' => ITEMS_PER_PAGE,
		'order' => 'id',
		'page'  => $pageno,
	];
} else {
	$options += ['order' => 'id'];
}
$regex = CategoryRegexes::find('all', $options)->to('array');

$page->smarty->assign(
	[
		'group'				=> $group,
		'pagecurrent'		=> (int)$pageno,
		'pagemaximum'		=> (int)($count / ITEMS_PER_PAGE) + 1,
		'pager'				=> $page->smarty->fetch('pager.tpl'),
		'pagerquerybase'	=> (WWW_TOP . "/category_regexes-list.php?" . $group . "page="),
		'pagertotalitems'	=> $count,
		'regex'				=> $regex,
	]
);

$page->content = $page->smarty->fetch('category_regexes-list.tpl');
$page->render();
