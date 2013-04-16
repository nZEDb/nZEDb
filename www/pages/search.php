<?php
require_once(WWW_DIR."/lib/releases.php");
require_once(WWW_DIR."/lib/groups.php");
require_once(WWW_DIR."/lib/category.php");

$releases = new Releases;
$grp = new Groups;
$c = new Category;

if (!$users->isLoggedIn())
	$page->show403();

$page->meta_title = "Search Nzbs";
$page->meta_keywords = "search,nzb,description,details";
$page->meta_description = "Search for Nzbs";

$results = array();
$searchtype = "basic";
$searchStr = "";

//print_r($_REQUEST);

if (isset($_REQUEST["search_type"]) && $_REQUEST["search_type"] == "adv")
	$searchtype = "advanced";

if (isset($_REQUEST["id"]) || isset($_REQUEST["searchadvr"]) && ($_REQUEST["subject"] == "" && $_REQUEST["tvtitle"] == "" && $_REQUEST["poster"] == ""))
{
	$offset = (isset($_REQUEST["offset"]) && ctype_digit($_REQUEST['offset'])) ? $_REQUEST["offset"] : 0;
	$ordering = $releases->getBrowseOrdering();
	$orderby = isset($_REQUEST["ob"]) && in_array($_REQUEST['ob'], $ordering) ? $_REQUEST["ob"] : '';

	if ($searchtype == "basic")
	{
		$searchStr = (string) $_REQUEST["id"];
	
		$categoryId = array();
		if (isset($_REQUEST["t"]))
			$categoryId = explode(",",$_REQUEST["t"]);
		else
			$categoryId[] = -1;
	
		foreach($ordering as $ordertype) 
		{
			$page->smarty->assign('orderby'.$ordertype, WWW_TOP."/search/".htmlentities($searchStr)."?t=".(implode(',',$categoryId))."&amp;ob=".$ordertype);
		}
		
		$page->smarty->assign('category', $categoryId);
		$page->smarty->assign('pagerquerybase', WWW_TOP."/search/".htmlentities($searchStr)."?t=".(implode(',',$categoryId))."&amp;ob=".$orderby."&amp;offset=");
		$page->smarty->assign('search', $searchStr);

		$results = $releases->search($searchStr, $categoryId, $offset, ITEMS_PER_PAGE, $orderby, -1, $page->userdata["categoryexclusions"]);

	}
	/*else
	{
		
		$searchReleaseName = (string) $_REQUEST["searchadvr"];
		$searchFileName = (string) $_REQUEST["searchadvf"];
		$searchPoster = (string) $_REQUEST["searchadvposter"];
		$searchGroups = (string) $_REQUEST["searchadvgroups"];
		$searchCat = (string) $_REQUEST["searchadvcat"];
		$searchSizeFrom = (string) $_REQUEST["searchadvsizefrom"];
		$searchSizeTo = (string) $_REQUEST["searchadvsizeto"];
		
		$page->smarty->assign('searchadvr', $searchReleaseName);
		$page->smarty->assign('searchadvf', $searchFileName);
		$page->smarty->assign('searchadvposter', $searchPoster);
		$page->smarty->assign('selectedgroup', $searchGroups);
		$page->smarty->assign('selectedcat', $searchCat);
		$page->smarty->assign('selectedsizefrom', $searchSizeFrom);
		$page->smarty->assign('selectedsizeto', $searchSizeTo);

		$results = $releases->searchadv($searchStr, $categoryId, $offset, ITEMS_PER_PAGE, $orderby, -1, $page->userdata["categoryexclusions"]);  
		
		
		$searchStr = (string) $_REQUEST["searchadvr"];
	
		$categoryId = array();
		if (isset($_REQUEST["searchadvcat"]))
			$categoryId = explode(",",$_REQUEST["searchadvcat"]);
		else
			$categoryId[] = -1;
	
		foreach($ordering as $ordertype) 
		{
			$page->smarty->assign('orderby'.$ordertype, WWW_TOP."/search/".htmlentities($searchStr)."?t=".(implode(',',$categoryId))."&amp;ob=".$ordertype);
		}
		
		$page->smarty->assign('selectedcat', $categoryId);
		$page->smarty->assign('pagerquerybase', WWW_TOP."/search/".htmlentities($searchStr)."?t=".(implode(',',$categoryId))."&amp;ob=".$orderby."&amp;offset=");
		$page->smarty->assign('searchadvr', $searchStr);

		$results = $releases->searchadv($searchStr, $categoryId, $offset, ITEMS_PER_PAGE, $orderby, -1, $page->userdata["categoryexclusions"]);
		
		//$results = $releases->searchadv($searchReleaseName, $searchFileName, $searchPoster, $searchGroups, $searchCat, $searchSizeFrom, $searchSizeTo, $offset, ITEMS_PER_PAGE, $orderby, -1, $page->userdata["categoryexclusions"]);
		
	}*/
	
	$page->smarty->assign('lastvisit', $page->userdata['lastlogin']);
	if (sizeof($results) > 0)
		$totalRows = $results[0]['_totalrows'];
	else
		$totalRows = 0;

	$page->smarty->assign('pagertotalitems',$totalRows);
	$page->smarty->assign('pageroffset',$offset);
	$page->smarty->assign('pageritemsperpage',ITEMS_PER_PAGE);
	$page->smarty->assign('pagerquerysuffix', "#results");
	
	$pager = $page->smarty->fetch($page->getCommonTemplate("pager.tpl"));
	$page->smarty->assign('pager', $pager);

}

if (isset($_REQUEST["subject"]) && ($_REQUEST["id"] == "" && $_REQUEST["tvtitle"] == "" && $_REQUEST["poster"] == ""))
{
	$offset = (isset($_REQUEST["offset"]) && ctype_digit($_REQUEST['offset'])) ? $_REQUEST["offset"] : 0;
	$ordering = $releases->getBrowseOrdering();
	$orderby = isset($_REQUEST["ob"]) && in_array($_REQUEST['ob'], $ordering) ? $_REQUEST["ob"] : '';

	if ($searchtype == "basic")
	{
		$searchStr = (string) $_REQUEST["subject"];
	
		$categoryId = array();
		if (isset($_REQUEST["t"]))
			$categoryId = explode(",",$_REQUEST["t"]);
		else
			$categoryId[] = -1;
	
		foreach($ordering as $ordertype) 
		{
			$page->smarty->assign('orderby'.$ordertype, WWW_TOP."/search/".htmlentities($searchStr)."?t=".(implode(',',$categoryId))."&amp;ob=".$ordertype);
		}
		
		$page->smarty->assign('category', $categoryId);
		$page->smarty->assign('pagerquerybase', WWW_TOP."/search/".htmlentities($searchStr)."?t=".(implode(',',$categoryId))."&amp;ob=".$orderby."&amp;offset=");
		$page->smarty->assign('subject', $searchStr);

		$results = $releases->searchsubject($searchStr, $categoryId, $offset, ITEMS_PER_PAGE, $orderby, -1, $page->userdata["categoryexclusions"]);

	}
	$page->smarty->assign('lastvisit', $page->userdata['lastlogin']);
	if (sizeof($results) > 0)
		$totalRows = $results[0]['_totalrows'];
	else
		$totalRows = 0;

	$page->smarty->assign('pagertotalitems',$totalRows);
	$page->smarty->assign('pageroffset',$offset);
	$page->smarty->assign('pageritemsperpage',ITEMS_PER_PAGE);
	$page->smarty->assign('pagerquerysuffix', "#results");
	
	$pager = $page->smarty->fetch($page->getCommonTemplate("pager.tpl"));
	$page->smarty->assign('pager', $pager);
}
	
if (isset($_REQUEST["tvtitle"]) && ($_REQUEST["subject"] == "" && $_REQUEST["id"] == "" && $_REQUEST["poster"] == ""))
{
	$offset = (isset($_REQUEST["offset"]) && ctype_digit($_REQUEST['offset'])) ? $_REQUEST["offset"] : 0;
	$ordering = $releases->getBrowseOrdering();
	$orderby = isset($_REQUEST["ob"]) && in_array($_REQUEST['ob'], $ordering) ? $_REQUEST["ob"] : '';

	if ($searchtype == "basic")
	{
		$searchStr = (string) $_REQUEST["tvtitle"];
	
		$categoryId = array();
		if (isset($_REQUEST["t"]))
			$categoryId = explode(",",$_REQUEST["t"]);
		else
			$categoryId[] = -1;
	
		foreach($ordering as $ordertype) 
		{
			$page->smarty->assign('orderby'.$ordertype, WWW_TOP."/search/".htmlentities($searchStr)."?t=".(implode(',',$categoryId))."&amp;ob=".$ordertype);
		}
		
		$page->smarty->assign('category', $categoryId);
		$page->smarty->assign('pagerquerybase', WWW_TOP."/search/".htmlentities($searchStr)."?t=".(implode(',',$categoryId))."&amp;ob=".$orderby."&amp;offset=");
		$page->smarty->assign('tvtitle', $searchStr);

		$results = $releases->searchtvtitle($searchStr, $categoryId, $offset, ITEMS_PER_PAGE, $orderby, -1, $page->userdata["categoryexclusions"]);

	}
	$page->smarty->assign('lastvisit', $page->userdata['lastlogin']);
	if (sizeof($results) > 0)
		$totalRows = $results[0]['_totalrows'];
	else
		$totalRows = 0;

	$page->smarty->assign('pagertotalitems',$totalRows);
	$page->smarty->assign('pageroffset',$offset);
	$page->smarty->assign('pageritemsperpage',ITEMS_PER_PAGE);
	$page->smarty->assign('pagerquerysuffix', "#results");
	
	$pager = $page->smarty->fetch($page->getCommonTemplate("pager.tpl"));
	$page->smarty->assign('pager', $pager);
}	

if (isset($_REQUEST["poster"]) && ($_REQUEST["subject"] == "" && $_REQUEST["tvtitle"] == "" && $_REQUEST["id"] == ""))
{
	$offset = (isset($_REQUEST["offset"]) && ctype_digit($_REQUEST['offset'])) ? $_REQUEST["offset"] : 0;
	$ordering = $releases->getBrowseOrdering();
	$orderby = isset($_REQUEST["ob"]) && in_array($_REQUEST['ob'], $ordering) ? $_REQUEST["ob"] : '';

	if ($searchtype == "basic")
	{
		$searchStr = (string) $_REQUEST["poster"];
	
		$categoryId = array();
		if (isset($_REQUEST["t"]))
			$categoryId = explode(",",$_REQUEST["t"]);
		else
			$categoryId[] = -1;
	
		foreach($ordering as $ordertype) 
		{
			$page->smarty->assign('orderby'.$ordertype, WWW_TOP."/search/".htmlentities($searchStr)."?t=".(implode(',',$categoryId))."&amp;ob=".$ordertype);
		}
		
		$page->smarty->assign('category', $categoryId);
		$page->smarty->assign('pagerquerybase', WWW_TOP."/search/".htmlentities($searchStr)."?t=".(implode(',',$categoryId))."&amp;ob=".$orderby."&amp;offset=");
		$page->smarty->assign('poster', $searchStr);

		$results = $releases->searchposter($searchStr, $categoryId, $offset, ITEMS_PER_PAGE, $orderby, -1, $page->userdata["categoryexclusions"]);

	}
	$page->smarty->assign('lastvisit', $page->userdata['lastlogin']);
	if (sizeof($results) > 0)
		$totalRows = $results[0]['_totalrows'];
	else
		$totalRows = 0;

	$page->smarty->assign('pagertotalitems',$totalRows);
	$page->smarty->assign('pageroffset',$offset);
	$page->smarty->assign('pageritemsperpage',ITEMS_PER_PAGE);
	$page->smarty->assign('pagerquerysuffix', "#results");
	
	$pager = $page->smarty->fetch($page->getCommonTemplate("pager.tpl"));
	$page->smarty->assign('pager', $pager);
}

$grouplist = $grp->getGroupsForSelect();
$page->smarty->assign('grouplist', $grouplist);	

$catlist = $c->getForSelect();
$page->smarty->assign('catlist', $catlist);	

$sizelist = array( -1 => '--Select--',
					0 => '0 bytes',
					0.5 => '500 mb',
					1 => '1 gb',
					2 => '2 gb', 
					3	=> '3 gb',			
					4	=> '4 gb'			
					) ;

$page->smarty->assign('sizelist', $sizelist);
$page->smarty->assign('results', $results);
$page->smarty->assign('sadvanced', ($searchtype != "basic"));

$page->content = $page->smarty->fetch('search.tpl');
$page->render();

?>
