<?php

require(dirname(__FILE__)."/config.php");
require_once(WWW_DIR."/lib/releases.php");
require_once(WWW_DIR."/lib/categorizer.php");
require_once(WWW_DIR."/lib/framework/db.php");

if (isset($argv[1]) && isset($argv[2]))
{
	$releases = new Releases;
	if ($argv[1] == 1 && $argv[2] == "true")
	{
		$releases->processReleases(1, 1);
	}
	else if ($argv[1] == 1 && $argv[2] == "false")
	{
		$releases->processReleases(1, 2);
	}
	else if ($argv[1] == 3 && $argv[2] == "true")
	{
		$releases->processReleases(3, 1);
	}
	else if ($argv[1] == 3 && $argv[2] == "false")
	{
		$releases->processReleases(3, 2);
	}
	else if ($argv[1] == 5 && $argv[2] == "true")
	{
		$releases->processReleases(5, 1);
	}
	else if ($argv[1] == 5 && $argv[2] == "false")
	{
		$releases->processReleases(5, 2);
	}
	else if ($argv[1] == 7 && ($argv[2] == "true" || $argv[2] == "false"))
	{
		$db = new Db();
		$db->queryDirect("UPDATE releases set categoryID = 7010 where categoryID <> 7010");
		echo "Moving all releases to other -> misc, this can take a while, be patient.\n";
	}
	else if ($argv[1] == 9 && ($argv[2] == 1 || $argv[2] == 2))
	{
		$db = new Db();
		$categorizer = new Categorizer();
		$relcount = 0;
		echo "Categorizing all releases in other-> misc using modified categorizer. This can take a while, be patient.\n";
		
		$relres = $db->queryDirect("SELECT searchname, ID from releases where categoryID = 7010");
		while ($relrow = mysql_fetch_assoc($relres))
		{
			$releaseID = $relrow['ID'];
			$relname = $relrow['searchname'];
			$catID = $categorizer->Categorize($relname);
			$db->queryDirect(sprintf("UPDATE releases set categoryID = %d where ID = %d", $catID, $releaseID));
			$relcount ++;
		} 
		echo "Finished categorizing ".$relcount." releases.\n";	
	}
	else
	{
		echo "Wrong argument, type php update_releases.php to see a list of valid arguments.\n";
		die;
	}
}
else
{
	echo "ERROR: You must supply an argument.\n".
			"php update_releases.php 1 true ...: Categorizes new releases using modified category.php (does a better job)\n".
			"php update_releases.php 3 true ...: Categorizes new releases using unmodified nnplus category.php\n".
			"php update_releases.php 5 true ...: Leaves new releases in other -> misc\n".
			"php update_releases.php 7 true ...: WARNING !! Moves ALL releases to other -> misc\n".
			"php update_releases.php 9 true ...: Categorizes releases in other -> misc using modified category.php\n".
			"\nYou have to pass a second argument wether to post process or not, true or false\n\n";
}

?>
