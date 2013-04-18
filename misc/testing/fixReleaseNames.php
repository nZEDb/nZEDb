<?php

define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../www/config.php");
require_once(FS_ROOT."/../../www/lib/framework/db.php");
require_once(FS_ROOT."/../../www/lib/namefixer.php");

$n = "\n";
$namefixer = new Namefixer;

if (isset($argv[1]) && isset($argv[2]) && isset($argv[3]))
{
	if ($argv[2] == "true")
	{
		if ($argv[3] == "other")
		{
			if ($argv[1] == 1)
			{
				$namefixer->fixNamesWithNames(1,1,1);
			}
			else if ($argv[1] == 2)
			{
				$namefixer->fixNamesWithNames(2,1,1);
			}
			else if ($argv[1] == 3)
			{
				$namefixer->fixNamesWithNfo(1,1,1);
			}
			else if ($argv[1] == 4)
			{
				$namefixer->fixNamesWithNfo(2,1,1);
			}
			else if ($argv[1] == 5)
			{
				$namefixer->fixNamesWithFiles(1,1,1);
			}
			else if ($argv[1] == 6)
			{
				$namefixer->fixNamesWithFiles(2,1,1);
			}
			else
			{
				exit("ERROR: Wrong argument, type php update_releases.php to see a list of valid arguments.".$n);
			}
		}
		else if ($argv[3] == "all")
		{
			if ($argv[1] == 1)
			{
				$namefixer->fixNamesWithNames(1,1,2);
			}
			else if ($argv[1] == 2)
			{
				$namefixer->fixNamesWithNames(2,1,2);
			}
			else if ($argv[1] == 3)
			{
				$namefixer->fixNamesWithNfo(1,1,2);
			}
			else if ($argv[1] == 4)
			{
				$namefixer->fixNamesWithNfo(2,1,2);
			}
			else if ($argv[1] == 5)
			{
				$namefixer->fixNamesWithFiles(1,1,2);
			}
			else if ($argv[1] == 6)
			{
				$namefixer->fixNamesWithFiles(2,1,2);
			}
			else
			{
				exit("ERROR: Wrong argument, type php update_releases.php to see a list of valid arguments.".$n);
			}
		}
		else
		{
			exit("ERROR: Wrong argument, type php update_releases.php to see a list of valid arguments.".$n);
		}
	}
	else if ($argv[2] == "false")
	{
		if ($argv[3] == "other")
		{
			if ($argv[1] == 1)
			{
				$namefixer->fixNamesWithNames(1,2,2);
			}
			else if ($argv[1] == 2)
			{
				$namefixer->fixNamesWithNames(2,2,2);
			}
			else if ($argv[1] == 3)
			{
				$namefixer->fixNamesWithNfo(1,2,2);
			}
			else if ($argv[1] == 4)
			{
				$namefixer->fixNamesWithNfo(2,2,2);
			}
			else if ($argv[1] == 5)
			{
				$namefixer->fixNamesWithFiles(1,2,2);
			}
			else if ($argv[1] == 6)
			{
				$namefixer->fixNamesWithFiles(2,2,2);
			}
			else
			{
				exit("ERROR: Wrong argument, type php update_releases.php to see a list of valid arguments.".$n);
			}
		}
		else if ($argv[3] == "all")
		{
			if ($argv[1] == 1)
			{
				$namefixer->fixNamesWithNames(1,2,2);
			}
			else if ($argv[1] == 2)
			{
				$namefixer->fixNamesWithNames(2,2,2);
			}
			else if ($argv[1] == 3)
			{
				$namefixer->fixNamesWithNfo(1,2,2);
			}
			else if ($argv[1] == 4)
			{
				$namefixer->fixNamesWithNfo(2,2,2);
			}
			else if ($argv[1] == 5)
			{
				$namefixer->fixNamesWithFiles(1,2,2);
			}
			else if ($argv[1] == 6)
			{
				$namefixer->fixNamesWithFiles(2,2,2);
			}
			else
			{
				exit("ERROR: Wrong argument, type php update_releases.php to see a list of valid arguments.".$n);
			}
		}
		else
		{
			exit("ERROR: Wrong argument, type php update_releases.php to see a list of valid arguments.".$n);
		}
	}
	else
	{
		exit("ERROR: Wrong argument, type php update_releases.php to see a list of valid arguments.".$n);
	}
}
else
{
	exit("ERROR: You must supply 2 arguments.".$n.
			"php fixReleaseNames.php 1 false other ...: Fix release names, using the usenet subject in the past 24 hours.".$n.
			"php fixReleaseNames.php 2 false other ...: Fix release names, using the usenet subject.".$n.
			"php fixReleaseNames.php 3 false other ...: Fix release names using NFO in the past 24 hours.".$n.
			"php fixReleaseNames.php 4 false other ...: Fix release names using NFO.".$n.
			"php fixReleaseNames.php 5 false other ...: Fix release names in misc categories using File Name in the past 24 hours.".$n.
			"php fixReleaseNames.php 6 false other ...: Fix release names in misc categories using File Name.".$n.
			"The 2nd argument false will display the results, but not change the name, type true to have the names changed.".$n.
			"The 3rd argument other will only do against other categories, to do against all categories use all.".$n.$n);
}

?>
