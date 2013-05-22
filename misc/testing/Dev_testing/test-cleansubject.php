<?php
require("../../../www/config.php");
require_once(WWW_DIR."lib/namecleaning.php");

if(!isset($argv[1]))
	exit('You must start the script like this : php test-cleansubject.php true'."\n");
else
{
	echo "Please input a name now.\n";
	$name = trim(fgets(fopen("php://stdin","r")));
	$namecleaner = new NameCleaning();
	echo $namecleaner->collectionsCleaner($name)."\n";
}

?>
