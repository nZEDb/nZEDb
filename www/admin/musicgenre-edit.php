<?php

require_once("config.php");
require_once(WWW_DIR."/lib/adminpage.php");
require_once(WWW_DIR."/lib/genres.php");

$page = new AdminPage();
$genres = new Genres();
$id = 0;

// set the current action
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view';

switch($action) 
{
    case 'submit':
		$ret = $genres->update($_POST["id"], $_POST["disabled"]);
		header("Location:".WWW_TOP."/musicgenre-list.php");
		break;
    case 'view':
    default:

			if (isset($_GET["id"]))
			{
				$page->title = "Music Genre Edit";
				$id = $_GET["id"];
				
				$genre = $genres->getByID($id);

				$page->smarty->assign('genre', $genre);	
			}

      break;   
}

$page->smarty->assign('status_names', array( 'Yes', 'No', 'Disabled'));

$page->content = $page->smarty->fetch('musicgenre-edit.tpl');
$page->render();

?>
