<?php

require("config.php");
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/framework/namefixer.php");

$n = "\n";
$namefixer = new Namefixer;

if (isset($argv[1]))
{
	if ($argv[1] == 1)
	{
		$namerfixer->fixNamesWithNames(1);
	}
	else if ($argv[1] == 2)
	{
		$namerfixer->fixNamesWithNames(2);
	}
	else if ($argv[1] == 3)
	{
		$namerfixer->fixNamesWithNfo(1);
	}
	else if ($argv[1] == 4)
	{
		$namerfixer->fixNamesWithNfo(2);
	}
	else if ($argv[1] == 5)
	{
		$namerfixer->fixNamesWithFiles(1);
	}
	else if ($argv[1] == 6)
	{
		$namerfixer->fixNamesWithFiles(2);
	}
	else
	{
		echo "Wrong argument, type php update_releases.php to see a list of valid arguments.".$n;
		die;
	}
}
else
{
	echo "ERROR: You must supply an argument.\n".
			"php fixReleaseNames.php 1 ...: Fix release names, using the usenet subject in the past 24 hours - on all categories.".$n
			"php fixReleaseNames.php 2 ...: Fix release names, using the usenet subject - on all categories.".$n.
			"php fixReleaseNames.php 3 ...: Placeholder - fix release names in misc categories using NFO in the past 24 hours.".$n.
			"php fixReleaseNames.php 4 ...: Placeholder - fix release names in misc categories using NFO.".$n.
			"php fixReleaseNames.php 5 ...: Placeholder - fix release names in misc categories using File Name in the past 24 hours.".$n;
			"php fixReleaseNames.php 6 ...: Placeholder - fix release names in misc categories using File Name.".$n;
}

?>
