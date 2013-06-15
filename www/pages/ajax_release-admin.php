<?php
require_once(WWW_DIR."/lib/adminpage.php");
require_once(WWW_DIR."/lib/releases.php");
require_once(WWW_DIR."/lib/category.php");

$page = new AdminPage(true);
$releases = new Releases();
$category = new Category();

// set the current action
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$id = (isset($_REQUEST['id']) && is_array($_REQUEST['id'])) ? $_REQUEST['id'] : '';

$page->smarty->assign('action', $action);
$page->smarty->assign('idArr', $id);

switch($action) 
{
	case 'doedit':
	case 'edit':
		$success = false;
		if ($action == 'doedit')
		{
			$upd = $releases->updatemulti($_REQUEST["id"], $_REQUEST["category"], $_REQUEST["grabs"], $_REQUEST["rageID"], $_REQUEST["season"], $_REQUEST['imdbID']);
			if ($upd !== false) {
				$success = true;
			} else {
				
			}
		}
		$page->smarty->assign('success', $success);
		$page->smarty->assign('from',(isset($_REQUEST['from'])?$_REQUEST['from']:''));
		$page->smarty->assign('catlist',$category->getForSelect());
		$page->content = $page->smarty->fetch('ajax_release-edit.tpl');
		echo $page->content;
		
	break;
	case 'dodelete':
		$releases->deleteSite($_REQUEST["id"]);
	break;
	default:
		$page->show404();
	break;
}
