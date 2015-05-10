<?php
require_once './config.php';

use nzedb\Regexes;

$page = new AdminPage();
$regexes = new Regexes(['Settings' => $page->settings, 'Table_Name' => 'category_regexes']);

$page->title = "Category Regex List";

$group = ((isset($_REQUEST['group']) && !empty($_REQUEST['group'])) ? $_REQUEST['group'] : '');
$offset = (isset($_REQUEST["offset"]) ? $_REQUEST["offset"] : 0);
$regex = $regexes->getRegex($group, ITEMS_PER_PAGE, $offset);
$count = $regexes->getCount($group);

$page->smarty->assign([
		'pagertotalitems'   => $count,
		'pageroffset'       => $offset,
		'pageritemsperpage' => ITEMS_PER_PAGE,
		'regex'             => $regex,
		'pagerquerybase'    => (WWW_TOP . "/category_regexes-list.php?" . $group . "offset="),
		'pager'             => $page->smarty->fetch("pager.tpl")
	]
);

$page->content = $page->smarty->fetch('category_regexes-list.tpl');
$page->render();
