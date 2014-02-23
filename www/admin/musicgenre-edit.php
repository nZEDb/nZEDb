<?php
require_once './config.php';


$page = new AdminPage();
$genres = new Genres();
$id = 0;

// Set the current action.
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

$page->smarty->assign('status_ids', array(Genres::STATUS_ENABLED,Genres::STATUS_DISABLED));
$page->smarty->assign('status_names', array( 'No', 'Yes'));

$page->content = $page->smarty->fetch('musicgenre-edit.tpl');
$page->render();

?>
