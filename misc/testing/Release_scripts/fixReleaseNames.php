<?php
/*
 * This script attemps to clean release names using the NFO, file name and release name.
 * A good way to use this script is to use it in this order: php fixReleaseNames.php 3 true other yes
 * php fixReleaseNames.php 5 true other yes
 * If you used the 4th argument yes, but you want to reset the status,
 * there is another script called resetRelnameStatus.php
 */

define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../../www/config.php");
require_once(FS_ROOT."/../../../www/lib/namefixer.php");
require_once(FS_ROOT."/../../../www/lib/predb.php");

$n = "\n";
$namefixer = new Namefixer();
$predb = new Predb(true);

if (isset($argv[1]) && isset($argv[2]) && isset($argv[3]) && isset($argv[4]))
{
	$update = ($argv[2] == "true") ? 1 : 2;
	$other = ($argv[3] == "other") ? 1 : 2;
	$setStatus = ($argv[4] == "yes") ? 1 : 2;
				
	switch ($argv[1])
	{
		case 1:
			$predb->parseTitles(1,$update,$other,$setStatus);
			break;
		case 2:
			$predb->parseTitles(2,$update,$other,$setStatus);
			break;
		case 3:
			$namefixer->fixNamesWithNfo(1,$update,$other,$setStatus);
			break;
		case 4:
			$namefixer->fixNamesWithNfo(2,$update,$other,$setStatus);
			break;
		case 5:
			$namefixer->fixNamesWithFiles(1,$update,$other,$setStatus);
			break;
		case 6:
			$namefixer->fixNamesWithFiles(2,$update,$other,$setStatus);
			break;
		default :
			exit("ERROR: Wrong argument, type php fixReleaseNames.php to see a list of valid arguments.".$n);
			break;			
	}
}
else
{
	exit("ERROR: You must supply 4 arguments.".$n.
			"php fixReleaseNames.php 1 false other no ...: Fix release names, using the usenet subject in the past 3 hours with predb information.".$n.
			"php fixReleaseNames.php 2 false other no ...: Fix release names, using the usenet subject with predb information.".$n.
			"php fixReleaseNames.php 3 false other no ...: Fix release names using NFO in the past 6 hours.".$n.
			"php fixReleaseNames.php 4 false other no ...: Fix release names using NFO.".$n.
			"php fixReleaseNames.php 5 false other no ...: Fix release names in misc categories using File Name in the past 6 hours.".$n.
			"php fixReleaseNames.php 6 false other no ...: Fix release names in misc categories using File Name.".$n.
			"The 2nd argument false will display the results, but not change the name, type true to have the names changed.".$n.
			"The 3rd argument other will only do against other categories, to do against all categories use all.".$n.
			"The 4th argument yes will set the release as checked, so the next time you run it will not be processed, to not set as checked type no.".$n.$n);
}

?>
