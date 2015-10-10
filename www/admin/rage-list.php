<?php
require_once './config.php';

use nzedb\TvRage;

$page   = new AdminPage();
$tvRage = new TvRage(['Settings' => $page->settings]);

$page->title = "TV Rage List";

$tvRageName = (isset($_REQUEST['ragename']) && !empty($_REQUEST['ragename']) ? $_REQUEST['ragename'] : '');
$offset = isset($_REQUEST["offset"]) ? $_REQUEST["offset"] : 0;

$page->smarty->assign([
		'ragename'          => $tvRageName,
		'tvragelist'        => $tvRage->getRange($offset, ITEMS_PER_PAGE, $tvRageName),
		'pagertotalitems'   => $tvRage->getCount($tvRageName),
		'pageroffset'       => $offset,
		'pageritemsperpage' => ITEMS_PER_PAGE,
		'pagerquerysuffix'  => '',
		'pagerquerybase'    => (WWW_TOP . "/rage-list.php?" .
			($tvRageName != '' ? 'ragename=' . $tvRageName . '&amp;' : '') . "&offset="
		)
	]
);
$page->smarty->assign('pager', $page->smarty->fetch("pager.tpl"));

$page->content = $page->smarty->fetch('rage-list.tpl');
$page->render();
