<?php
require_once './config.php';


$page = new AdminPage();
$AniDB = new AniDB(['Settings' => $page->settings]);

$page->title = 'AniDB Titles';

$aname = '';
if (isset($_REQUEST['animetitle']) && !empty($_REQUEST['animetitle'])) {
	$aname = $_REQUEST['animetitle'];
}

$animecount = $AniDB->getAnimeCount($aname);

$offset = isset($_REQUEST['offset']) ? $_REQUEST['offset'] : 0;
$asearch = ($aname != '') ? 'animetitle='.$aname.'&amp;' : '';

$page->smarty->assign('pagertotalitems',$animecount);
$page->smarty->assign('pageroffset',$offset);
$page->smarty->assign('pageritemsperpage',ITEMS_PER_PAGE);
$page->smarty->assign('pagerquerybase', WWW_TOP.'/anidb-list.php?' . $asearch . '&offset=');
$pager = $page->smarty->fetch('pager.tpl');
$page->smarty->assign('pager', $pager);

$page->smarty->assign('animetitle',$aname);

$anidblist = $AniDB->getAnimeRange($offset, ITEMS_PER_PAGE, $aname);
$page->smarty->assign('anidblist',$anidblist);

$page->content = $page->smarty->fetch('anidb-list.tpl');
$page->render();
