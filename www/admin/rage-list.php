<?php
require_once './config.php';

use nzedb\TvRage;

$page   = new AdminPage();
$tvrage = new TvRage(['Settings' => $page->settings]);

$page->title = "TV Rage List";

$tname = "";
if (isset($_REQUEST['ragename']) && !empty($_REQUEST['ragename'])) {
	$tname = $_REQUEST['ragename'];
}

$ragecount = $tvrage->getCount($tname);

$offset  = isset($_REQUEST["offset"]) ? $_REQUEST["offset"] : 0;
$tsearch = ($tname != "") ? 'ragename=' . $tname . '&amp;' : '';

$page->smarty->assign('pagertotalitems', $ragecount);
$page->smarty->assign('pageroffset', $offset);
$page->smarty->assign('pageritemsperpage', ITEMS_PER_PAGE);
$page->smarty->assign('pagerquerybase', WWW_TOP . "/rage-list.php?" . $tsearch . "&offset=");
$pager = $page->smarty->fetch("pager.tpl");
$page->smarty->assign('pager', $pager);

$page->smarty->assign('ragename', $tname);

$tvragelist = $tvrage->getRange($offset, ITEMS_PER_PAGE, $tname);
$page->smarty->assign('tvragelist', $tvragelist);

$page->content = $page->smarty->fetch('rage-list.tpl');
$page->render();
