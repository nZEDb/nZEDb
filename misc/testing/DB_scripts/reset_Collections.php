<?php
require_once(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."lib/releases.php");

//
//	This script resets all collections and is required.
//

if(isset($argv[1]) && $argv[1] == "true")
{

	$releases = new Releases(true);
	$releases->resetCollections();
}
else
{
	exit("This script resets all collections and is required.\nIf you are sure you want to run it, type php reset_Collections.php true\n");
}
?>
