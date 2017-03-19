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
	'pager'				=> $page->smarty->fetch("paginate.tpl"),
	'pageritemsperpage' => ITEMS_PER_PAGE,
	'pagerquerybase'    => WWW_TOP . "/release_naming_regexes-list.php?" . $group . "offset=",
	'pagerquerysuffix'  => '',
	'regex'             => $regex,
	]
);

$page->content = $page->smarty->fetch('release_naming_regexes-list.tpl');
$page->render();
