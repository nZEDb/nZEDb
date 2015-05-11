<?php
require_once './config.php';

use nzedb\Regexes;

$page = new AdminPage();
$regexes = new Regexes(['Settings' => $page->settings, 'Table_Name' => 'collection_regexes']);

$page->title = "Collections Regex List";

$group = '';
if (isset($_REQUEST['group']) && !empty($_REQUEST['group'])) {
	$group = $_REQUEST['group'];
}

$offset = isset($_REQUEST["offset"]) ? $_REQUEST["offset"] : 0;
$regex  = $regexes->getRegex($group, ITEMS_PER_PAGE, $offset);
$page->smarty->assign('regex', $regex);

$count = $regexes->getCount($group);
$page->smarty->assign('pagertotalitems', $count);
$page->smarty->assign('pageroffset', $offset);
$page->smarty->assign('pageritemsperpage', ITEMS_PER_PAGE);

$page->smarty->assign('pagerquerybase',
					  WWW_TOP . "/collection_regexes-list.php?" . $group . "offset=");
$page->smarty->assign('pager', $page->smarty->fetch("pager.tpl"));

$page->content = $page->smarty->fetch('collection_regexes-list.tpl');
$page->render();
