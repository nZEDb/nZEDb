<?php
require_once './config.php';



$page = new AdminPage(true);
$releases = new Releases();
$category = new Category();
$id = 0;

// Set the current action.
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view';

switch($action)
{
	case 'submit':
		$releases->update($_POST["id"], $_POST["name"], $_POST["searchname"], $_POST["fromname"], $_POST["category"], $_POST["totalpart"], $_POST["grabs"], $_POST["size"], $_POST["postdate"], $_POST["adddate"], $_POST["rageid"], $_POST["seriesfull"], $_POST["season"], $_POST["episode"], $_POST["imdbid"], $_POST["anidbid"]);
		if(isset($_POST['from']) && !empty($_POST['from']))
		{
			header("Location:".$_POST['from']);
			exit;
		}
		header("Location:".WWW_TOP."/release-list.php");
		break;

	case 'view':
	default:
		if (isset($_GET["id"]))
		{
			$page->title = "Release Edit";
			$id = $_GET["id"];
			$release = $releases->getByID($id);
			$page->smarty->assign('release', $release);
		}
		break;
}

$page->smarty->assign('yesno_ids', array(1,0));
$page->smarty->assign('yesno_names', array( 'Yes', 'No'));
$page->smarty->assign('catlist',$category->getForSelect(false));

$page->content = $page->smarty->fetch('release-edit.tpl');
$page->render();

?>
