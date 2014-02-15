<?php
// This script resets all collections and is required when changes are made to namecleaning.
require_once dirname(__FILE__) . '/../../../www/config.php';


if(isset($argv[1]) && $argv[1] == "true")
{
	$releases = new Releases(true);
	$releases->resetCollections();
}
else
	exit("This script resets all collections and is required if a change was made to namecleaning.php\nIf you are sure you want to run it, type php reset_Collections.php true\n");
?>
