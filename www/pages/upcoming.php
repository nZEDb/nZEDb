<?php
if (!$users->isLoggedIn())
	$page->show403();

require_once(WWW_DIR."/lib/movie.php");
$m = new Movie();
	
if (!isset($_GET["id"]))
	$_GET["id"] = 1;

$data = $m->getUpcoming($_GET["id"]);
//print_r(json_decode($data["info"])->movies);die();
if ($data["info"] == "")
	$page->smarty->assign("nodata","No upcoming data.");
else
{

	$page->smarty->assign('data', json_decode($data["info"])->movies);

	switch ($_GET["id"])
	{
		case Movie::SRC_BOXOFFICE;
			$page->title = "Box Office";
			break;
		case Movie::SRC_INTHEATRE;
			$page->title = "In Theatre";
			break;
		case Movie::SRC_OPENING;
			$page->title = "Opening";
			break;
		case Movie::SRC_UPCOMING;
			$page->title = "Upcoming";
			break;
		case Movie::SRC_DVD;
			$page->title = "DVD Releases";
			break;
	}
	$page->meta_title = "View upcoming theatre releases";
	$page->meta_keywords = "view,series,theatre,dvd";
	$page->meta_description = "View upcoming theatre releases";
	
}
$page->content = $page->smarty->fetch('upcoming.tpl');
$page->render();

?>
