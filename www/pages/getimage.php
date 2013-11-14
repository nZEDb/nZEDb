<?php
// Page is accessible only to logged in users.
if (!$users->isLoggedIn())
	$page->show403();

if (!isset($_GET["type"]) || !isset($_GET["id"]) || !ctype_digit($_GET["id"]))
	$page->show404();

// User requested a tvrage image.
if ($_GET["type"] == "tvrage")
{
	require_once nZEDb_LIB . 'framework/db.php';
	$db = new DB;
	if ($db->dbSystem() == 'mysql')
	{
		require_once nZEDb_LIB . 'tvrage.php';
		$rage = new TvRage;
		$r = $rage->getByID($_GET["id"]);
		if (!$r)
			$page->show404();

		$imgdata = $r["imgdata"];
	}
	else if ($db->dbSystem() == 'pgsql')
	{
		$imgdata = @file_get_contents(WWW_DIR.'covers/tvrage/'.$_GET['id'].'.jpg');
		if ($imgdata === false)
			$page->show404();
	}
	header("Content-type: image/jpeg");
	print $imgdata;
	die();
}
else
	$page->show404();

?>
