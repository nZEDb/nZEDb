<?php

require_once("config.php");
require_once(WWW_DIR."/lib/adminpage.php");
require_once(WWW_DIR."/lib/category.php");

$page = new AdminPage();
$category = new Category();
$id = 0;

// set the current action
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view';

switch($action) 
{
    case 'submit':
		$ret = $category->update($_POST["id"], $_POST["status"], $_POST["description"], $_POST["disablepreview"]);
		header("Location:".WWW_TOP."/category-list.php");
		break;
    case 'view':
    default:

			if (isset($_GET["id"]))
			{
				$page->title = "Category Edit";
				$id = $_GET["id"];
				
				$cat = $category->getByID($id);

				$page->smarty->assign('category', $cat);	
			}

      break;   
}

$page->smarty->assign('status_ids', array(Category::STATUS_ACTIVE,Category::STATUS_INACTIVE,Category::STATUS_DISABLED));
$page->smarty->assign('status_names', array( 'Yes', 'No', 'Disabled'));

$page->content = $page->smarty->fetch('category-edit.tpl');
$page->render();

?>
