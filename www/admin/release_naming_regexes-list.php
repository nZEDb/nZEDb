<?php
require_once './config.php';

use nzedb\Regexes;

$page = new AdminPage();
$regexes = new Regexes(['Settings' => $page->settings, 'Table_Name' => 'release_naming_regexes']);

$page->title = "Release Naming Regex List";

$group = (isset($_REQUEST['group']) && !empty($_REQUEST['group']) ? $_REQUEST['group'] : '');
$offset = isset($_REQUEST["offset"]) ? $_REQUEST["offset"] : 0;
$regex  = $regexes->getRegex($group, ITEMS_PER_PAGE, $offset);

$page->smarty->assign([
		'group'             => $group,
		'regex'             => $regex,
		'pagertotalitems'   => $regexes->getCount($group),
		'pageroffset'       => $offset,
		'pageritemsperpage' => ITEMS_PER_PAGE,
		'pagerquerysuffix'  => '',
		'pagerquerybase'    => WWW_TOP . "/release_naming_regexes-list.php?" . $group . "offset="
	]
);
$page->smarty->assign('pager', $page->smarty->fetch("pager.tpl"));

$page->content = $page->smarty->fetch('release_naming_regexes-list.tpl');
$page->render();
