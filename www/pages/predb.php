<?php
require_once(WWW_DIR."/lib/predb.php");

$predb = new Predb;
if (!$users->isLoggedIn())
	$page->show403();

$offset = (isset($_REQUEST["offset"]) && ctype_digit($_REQUEST['offset'])) ? $_REQUEST["offset"] : 0;

$pres = $predb->getAll($offset, ITEMS_PER_PAGE);
	
$page->smarty->assign('pagertotalitems',$predb->getCount());
$page->smarty->assign('pageroffset',$offset);
$page->smarty->assign('pageritemsperpage',ITEMS_PER_PAGE);
$page->smarty->assign('pagerquerybase', WWW_TOP."/predb/&amp;offset=");
$page->smarty->assign('pagerquerysuffix', "#results");
	
$pager = $page->smarty->fetch("pager.tpl");
$page->smarty->assign('pager', $pager);

$page->smarty->assign('results',  $pres);

$page->title = "Browse PreDB";
$page->meta_title = "View PreDB info";
$page->meta_keywords = "view,predb,info,description,details";
$page->meta_description = "View PreDB info";
	
$page->content = $page->smarty->fetch('predb.tpl');
$page->render();
