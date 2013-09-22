<?php
// Page is accessible only to logged in users.
if (!$users->isLoggedIn())
	$page->show403();

if (!isset($_GET["type"]) || !isset($_GET["id"]) || !ctype_digit($_GET["id"]))
	$page->show404();

// User requested a tvrage image.
if ($_GET["type"] == "tvrage")
{
	require_once(WWW_DIR."/lib/framework/db.php");
	$db = new DB;
	if ($db->dbSystem() == 'mysql')
	{
		require_once(WWW_DIR."/lib/tvrage.php");
		$rage = new TvRage;
		$r = $rage->getByID($_GET["id"]);
		if (!$r)
			$page->show404();

		$imgdata = $r["imgdata"];
	}
	else if ($db->dbSystem() == 'pgsql')
	{
		$path = WWW_DIR.'covers/tvrage/'.$_GET['id'].'.jpg';
		if (!file_exists($path))
			$page->show404();

		$imgdata = file_get_contents($path);
		$imagedata = @imagecreatefromstring($data);
	}
	header("Content-type: image/jpeg");
	print $imgdata;
	die();
}
else
	$page->show404();

?>
