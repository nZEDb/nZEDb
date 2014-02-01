<?php
if (!$users->isLoggedIn()) {
	$page->show403();
}

$releases = new Releases();
$nzb = new NZB();

if (isset($_GET["id"])) {
	$rel = $releases->getByGuid($_GET["id"]);
	if (!$rel) {
		$page->show404();
	}

	$nzbpath = $nzb->getNZBPath($_GET["id"], $page->site->nzbpath, false, $page->site->nzbsplitlevel);

	if (!file_exists($nzbpath)) {
		$page->show404();
	}

	ob_start();
	@readgzfile($nzbpath);
	$nzbfile = ob_get_contents();
	ob_end_clean();

	$ret = $nzb->nzbFileList($nzbfile);

	$offset = (isset($_REQUEST["offset"]) && ctype_digit($_REQUEST['offset'])) ? $_REQUEST["offset"] : 0;

	$page->smarty->assign('pagertotalitems', sizeof($ret));
	$page->smarty->assign('pageroffset', $offset);
	$page->smarty->assign('pageritemsperpage', ITEMS_PER_PAGE);
	$page->smarty->assign('pagerquerybase', WWW_TOP . "/filelist/" . $_GET["id"] . "/&amp;offset=");
	$page->smarty->assign('pagerquerysuffix', "#results");

	$pager = $page->smarty->fetch("pager.tpl");
	$page->smarty->assign('pager', $pager);

	$page->smarty->assign('rel', $rel);
	$page->smarty->assign('files', array_slice($ret, $offset, ITEMS_PER_PAGE));

	$page->title = "File List";
	$page->meta_title = "View Nzb file list";
	$page->meta_keywords = "view,nzb,file,list,description,details";
	$page->meta_description = "View Nzb File List";

	$page->content = $page->smarty->fetch('viewfilelist.tpl');
	$page->render();
}
