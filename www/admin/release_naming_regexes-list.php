<?php
require_once './config.php';

$page = new AdminPage();
$rc = new ReleaseCleaning($page->settings);

$page->title = "Release Naming Regex List";

$group = '';
if (isset($_REQUEST['group']) && !empty($_REQUEST['group'])) {
	$group = $_REQUEST['group'];
}

$offset = isset($_REQUEST["offset"]) ? $_REQUEST["offset"] : 0;
$regex = $rc->getRegex($group, ITEMS_PER_PAGE, $offset);
$page->smarty->assign('regex', $regex);

$count = $rc->getCount($group);
$page->smarty->assign('pagertotalitems', $count);
$page->smarty->assign('pageroffset', $offset);
$page->smarty->assign('pageritemsperpage', ITEMS_PER_PAGE);

$page->smarty->assign('pagerquerybase', WWW_TOP . "/release_naming_regexes-list.php?" . $group . "offset=");
$page->smarty->assign('pager', $page->smarty->fetch("pager.tpl"));

$page->content = $page->smarty->fetch('release_naming_regexes-list.tpl');
$page->render();