<?php
require_once(WWW_DIR."/lib/releases.php");
require_once(WWW_DIR."/lib/category.php");

$releases = new Releases;

if (!$users->isLoggedIn())
	$page->show403();

$category = -1;
if (isset($_REQUEST["t"]) && ctype_digit($_REQUEST["t"]))
	$category = $_REQUEST["t"];

$grp = "";
if (isset($_REQUEST["g"]))
	$grp = $_REQUEST["g"];

$catarray = array();
$catarray[] = $category;	

$page->smarty->assign('category', $category);
$browsecount = $releases->getBrowseCount($catarray, -1, $page->userdata["categoryexclusions"], $grp);

$offset = (isset($_REQUEST["offset"]) && ctype_digit($_REQUEST['offset'])) ? $_REQUEST["offset"] : 0;
$ordering = $releases->getBrowseOrdering();
$orderby = isset($_REQUEST["ob"]) && in_array($_REQUEST['ob'], $ordering) ? $_REQUEST["ob"] : '';

$results = array();
$results = $releases->getBrowseRange($catarray, $offset, ITEMS_PER_PAGE, $orderby, -1, $page->userdata["categoryexclusions"], $grp);

$page->smarty->assign('pagertotalitems',$browsecount);
$page->smarty->assign('pageroffset',$offset);
$page->smarty->assign('pageritemsperpage',ITEMS_PER_PAGE);
$page->smarty->assign('pagerquerybase', WWW_TOP."/browse?t=".$category."&amp;g=".$grp."&amp;ob=".$orderby."&amp;offset=");
$page->smarty->assign('pagerquerysuffix', "#results");

$pager = $page->smarty->fetch($page->getCommonTemplate("pager.tpl"));
$page->smarty->assign('pager', $pager);

$section = '';
if ($category == -1 && $grp == "")
	$page->smarty->assign("catname","All");			
elseif ($category != -1 && $grp == "")
{
	$cat = new Category();
	$cdata = $cat->getById($category);
	
	if ($cdata) {
		$page->smarty->assign('catname',$cdata["title"]);
		if ($cdata['parentID'] == Category::CAT_PARENT_GAME || $cdata['ID'] == Category::CAT_PARENT_GAME)
			$section = 'console';
		elseif ($cdata['parentID'] == Category::CAT_PARENT_MOVIE || $cdata['ID'] == Category::CAT_PARENT_MOVIE)
			$section = 'movies';
		elseif ($cdata['parentID'] == Category::CAT_PARENT_MUSIC || $cdata['ID'] == Category::CAT_PARENT_MUSIC)
			$section = 'music';
		elseif ($cdata['parentID'] == Category::CAT_PARENT_BOOKS || $cdata['ID'] == Category::CAT_PARENT_BOOKS)
			$section = 'books';
	} else {
		$page->show404();
	}
}
elseif ($grp != "")
{
	$page->smarty->assign('catname',$grp);			
}
$page->smarty->assign('section',$section);

foreach($ordering as $ordertype) 
	$page->smarty->assign('orderby'.$ordertype, WWW_TOP."/browse?t=".$category."&amp;g=".$grp."&amp;ob=".$ordertype."&amp;offset=0");	

$page->smarty->assign('lastvisit',$page->userdata['lastlogin']);

$page->smarty->assign('results',$results);		

$page->meta_title = "Browse Nzbs";
$page->meta_keywords = "browse,nzb,description,details";
$page->meta_description = "Browse for Nzbs";
	
$page->content = $page->smarty->fetch('browse.tpl');
$page->render();

?>
