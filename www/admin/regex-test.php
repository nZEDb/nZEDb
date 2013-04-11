<?php

require_once("config.php");
require_once(WWW_DIR."/lib/adminpage.php");
require_once(WWW_DIR."/lib/releaseregex.php");
require_once(WWW_DIR."/lib/groups.php");
require_once(WWW_DIR."/lib/category.php");

$page = new AdminPage();
$reg = new ReleaseRegex();
$groups = new Groups();
$cat = new Category();
$id = 0;

// set the current action
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view';

$groupList = $groups->getActive();
$gid = $gname = array();
$gselected = isset($_REQUEST['groupname']) ? $_REQUEST['groupname'] : '0';
$gregex = (isset($_REQUEST['regex']) && !empty($_REQUEST['regex'])) ? $_REQUEST['regex'] : '/^(?P<name>.*)$/i';
$gunreleased = isset($_REQUEST['unreleased']) ? $_REQUEST['unreleased'] : '';
foreach($groupList as $group) 
{
	$gid[$group["ID"]] = $group["ID"];
	$gname[$group["ID"]] = $group["name"];
}
$page->smarty->assign('gid', $gid);
$page->smarty->assign('gname', $gname);
$page->smarty->assign('gselected', $gselected);
$page->smarty->assign('gregex', $gregex);
$page->smarty->assign('gunreleased', $gunreleased);

switch($action) 
{
    case 'submit':
    	if (isset($_REQUEST["regex"]))
		{
			$catList = $cat->getForSelect();
			$db = new Db();
			$unreleasedSql = ($gunreleased != '') ? ' and binaries.procstat NOT IN (4,5,6) and binaries.releaseID IS NULL' : '';
			$resbin = $db->queryDirect(sprintf("SELECT binaries.ID as binID, binaries.name as binName from binaries where binaries.groupID = %d%s order by dateadded", $gselected, $unreleasedSql));
			$matches = array();
			while ($rowbin = mysql_fetch_assoc($resbin)) 
			{
				if (preg_match ($gregex, $rowbin["binName"], $binmatch)) 
				{
					$binmatch = array_map("trim", $binmatch);
					
					if ((isset($binmatch['reqid']) && ctype_digit($binmatch['reqid'])) && (!isset($binmatch['name']) || empty($binmatch['name']))) {
						$binmatch['name'] = $binmatch['reqid'];
					}
					
					if (!isset($binmatch['name']) || empty($binmatch['name'])) {
						//echo "bad regex applied which didnt return right number of capture groups<br />";
					} else {
						$binmatch['count'] = (isset($matches[$binmatch['name']]['count'])) ? $matches[$binmatch['name']]['count']+1 : 1;
						$binmatch['bininfo'] = $rowbin;
						$binmatch['catname'] = $catList[$cat->determineCategory($gname[$gselected], $binmatch['name'])];
						$matches[$binmatch['name']] = $binmatch;
					}
				}
			}
			$offset = isset($_REQUEST["offset"]) ? $_REQUEST["offset"] : 0;
			$page->smarty->assign('pagertotalitems',sizeof($matches));
			$page->smarty->assign('pageroffset',$offset);
			$page->smarty->assign('pageritemsperpage',ITEMS_PER_PAGE);
			$page->smarty->assign('pagerquerybase', WWW_TOP."/regex-test.php?action=submit&groupname={$gselected}&regex=".urlencode($gregex)."&unreleased={$gunreleased}&offset=");
			$pager = $page->smarty->fetch($page->getCommonTemplate("pager.tpl"));
			$page->smarty->assign('pager', $pager);
			
			$matches = array_slice($matches, $offset, ITEMS_PER_PAGE);
						
			$page->smarty->assign('matches', $matches);
		}
	break;
}

$page->title = "Release Regex Test";

$page->content = $page->smarty->fetch('regex-test.tpl');
$page->render();

?>
