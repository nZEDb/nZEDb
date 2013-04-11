<?php

require_once("config.php");
require_once(WWW_DIR."/lib/adminpage.php");
require_once(WWW_DIR."/lib/console.php");
require_once(WWW_DIR."/lib/genres.php");

$page = new AdminPage();
$console = new Console();
$gen = new Genres();
$id = 0;

// set the current action
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view';

if (isset($_REQUEST["id"]))
{
	$id = $_REQUEST["id"];
	$con = $console->getConsoleInfo($id);
	
	if (!$con) {
		$page->show404();
	}
	
	switch($action) 
	{
	    case 'submit':
	    	$coverLoc = WWW_DIR."covers/console/".$id.'.jpg';
	    	
			if($_FILES['cover']['size'] > 0)
			{
				$tmpName = $_FILES['cover']['tmp_name'];
				$file_info = getimagesize($tmpName);
				if(!empty($file_info))
				{
					move_uploaded_file($_FILES['cover']['tmp_name'], $coverLoc);
				}
			}
			
			$_POST['cover'] = (file_exists($coverLoc)) ? 1 : 0;
			$_POST['salesrank'] = (empty($_POST['salesrank']) || !ctype_digit($_POST['salesrank'])) ? "null" : $_POST['salesrank'];
			$_POST['releasedate'] = (empty($_POST['releasedate']) || !strtotime($_POST['releasedate'])) ? $con['releasedate'] : date("Y-m-d H:i:s", strtotime($_POST['releasedate']));
			
			$console->update($id, $_POST["title"], $_POST['asin'], $_POST['url'], $_POST["salesrank"], $_POST["platform"], $_POST["publisher"], $_POST["releasedate"], $_POST["esrb"], $_POST["cover"], $_POST["genre"]);

			header("Location:".WWW_TOP."/console-list.php");
	        die();
	    break;
	    case 'view':
	    default:
			$page->title = "Console Edit";
			$page->smarty->assign('console', $con);
			$page->smarty->assign('genres', $gen->getGenres(Genres::CONSOLE_TYPE));
		break;   
	}
}

$page->content = $page->smarty->fetch('console-edit.tpl');
$page->render();

?>
