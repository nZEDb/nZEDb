<?php
require_once(WWW_DIR."/lib/tvrage.php");

//
// page is accessible only to logged in users.
//
if (!$users->isLoggedIn())
	$page->show403();

if (!isset($_GET["type"]) || !isset($_GET["id"]) || !ctype_digit($_GET["id"]))
	$page->show404();
	
//
// user requested a tvrage image.
//
if ($_GET["type"] == "tvrage")
{
	$rage = new TvRage;
	$r = $rage->getByID($_GET["id"]);
	if (!$r)
		$page->show404();
	
	header("Content-type: image/jpeg");
    print $r["imgdata"];
	die();
}
else
{
	$page->show404();
}		
