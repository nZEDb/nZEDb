<?php
require_once realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'smarty.php');

use nzedb\Regexes;

$page = new AdminPage();
$page->title = "Release Naming Regex List";

$regexes = new Regexes(['Settings' => $page->settings, 'Table_Name' => 'release_naming_regexes']);

// TODO modelise.
$count = $regexes->getCount($group);

$group = (isset($_REQUEST['group']) && !empty($_REQUEST['group']) ? $_REQUEST['group'] : '');
$offset = isset($_REQUEST["offset"]) ? $_REQUEST["offset"] : 0;
$regex  = $regexes->getRegex($group, ITEMS_PER_PAGE, $offset);

$page->smarty->assign([
	'group'             => $group,
	'pagertotalitems'   => $count,
	'pageritemsperpage' => ITEMS_PER_PAGE,
	'pagerquerybase'    => WWW_TOP . "/release_naming_regexes-list.php?" . $group . "offset=",
	'pagerquerysuffix'  => '',
	'regex'             => $regex,
	]
);
// Pager has to be set outside of main assign, or it will no recieve the scope of those variables.
$page->smarty->assign('pager', $page->smarty->fetch("paginate.tpl"));

$page->content = $page->smarty->fetch('release_naming_regexes-list.tpl');
$page->render();
