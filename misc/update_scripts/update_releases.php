<?php

require(dirname(__FILE__)."/config.php");
require_once(WWW_DIR."/lib/releases.php");
require_once(WWW_DIR."/lib/categorizer.php");
require_once(WWW_DIR."/lib/category.php");
require_once(WWW_DIR."/lib/groups.php");
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
	else if ($argv[1] == 2 && $argv[2] == "true")
	{
		$releases->processReleases(3, 1);
	}
	else if ($argv[1] == 2 && $argv[2] == "false")
	{
		$releases->processReleases(3, 2);
	}
	else if ($argv[1] == 3 && $argv[2] == "true")
	{
		$releases->processReleases(5, 1);
	}
	else if ($argv[1] == 3 && $argv[2] == "false")
	{
		$releases->processReleases(5, 2);
	}
	else if ($argv[1] == 4 && ($argv[2] == "true" || $argv[2] == "false"))
	{
		$db = new Db();
		$db->queryDirect("UPDATE releases set categoryID = 7010, relnamestatus = 0");
		echo "Moving all releases to other -> misc, this can take a while, be patient.\n";
	}
	else if ($argv[1] == 5 && ($argv[2] == "true" || $argv[2] == "false"))
	{
		$db = new Db();
		$categorizer = new Categorizer();
		$relcount = 0;
		echo "Categorizing all reseted using modified categorizer. This can take a while, be patient.\n";
		
		$relres = $db->queryDirect("SELECT name, ID, groupID from releases where categoryID = 7010 and relnamestatus = 0");
		while ($relrow = mysql_fetch_assoc($relres))
		{
			$catID = $categorizer->Categorize($relrow['name'], $relrow['groupID']);
			$db->queryDirect(sprintf("UPDATE releases set categoryID = %d, relnamestatus = 1 where ID = %d", $catID, $relrow['ID']));
			$relcount ++;
		} 
		echo "Finished categorizing ".$relcount." releases.\n";	
	}
	else if ($argv[1] == 6 && ($argv[2] == "true" || $argv[2] == "false"))
	{
		$db = new Db();
		$cat = new Category();
		$groups = new Groups;
		$relcount = 0;
		echo "Categorizing all releases in other-> misc using modified categorizer. This can take a while, be patient.\n";
		
		$relres = $db->queryDirect("SELECT searchname, ID, groupID from releases where categoryID = 7010 and relnamestatus = 0");
		while ($relrow = mysql_fetch_assoc($relres))
		{
			$groupName = $groups->getByNameByID($relrow['groupID']);
			$catID = $cat->determineCategory($groupName, $relrow["searchname"]);
			$db->queryDirect(sprintf("UPDATE releases set categoryID = %d, relnamestatus = 1 where ID = %d", $catID, $relrow['ID']));
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
			"php update_releases.php 2 true ...: Categorizes new releases using unmodified nnplus category.php\n".
			"php update_releases.php 3 true ...: Leaves new releases in other -> misc\n".
			"\nThe follow 3 options run by tem selves (does not create releases):\n".
			"php update_releases.php 4 true ...: WARNING !! Resets category status on all releases\n".
			"php update_releases.php 5 true ...: Categorizes all reseted releases using modified category.php\n".
			"php update_releases.php 6 true ...: Categorizes all reseted releases using stock category.php\n".
			"\nYou have to pass a second argument wether to post process or not, true or false\n\n";
}

?>
