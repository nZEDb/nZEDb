<?php
require_once './config.php';


$page = new AdminPage();
$groups = new Groups();

$gname = "";
if (isset($_REQUEST['groupname']) && !empty($_REQUEST['groupname']))
	$gname = $_REQUEST['groupname'];

$groupcount = $groups->getCountInactive($gname);

$offset = isset($_REQUEST["offset"]) ? $_REQUEST["offset"] : 0;
$groupname = (isset($_REQUEST['groupname']) && !empty($_REQUEST['groupname'])) ? $_REQUEST['groupname'] : '';

$page->smarty->assign('groupname',$groupname);
$page->smarty->assign('pagertotalitems',$groupcount);
$page->smarty->assign('pageroffset',$offset);
$page->smarty->assign('pageritemsperpage',ITEMS_PER_PAGE);

$groupsearch = ($gname != "") ? 'groupname='.$gname.'&amp;' : '';
$page->smarty->assign('pagerquerybase', WWW_TOP."/group-list-inactive.php?".$groupsearch."offset=");
$pager = $page->smarty->fetch("pager.tpl");
$page->smarty->assign('pager', $pager);

$grouplist = $groups->getRangeInactive($offset, ITEMS_PER_PAGE, $gname);

$page->smarty->assign('grouplist',$grouplist);

$page->title = "Group List";

$page->content = $page->smarty->fetch('group-list.tpl');
$page->render();

?>
