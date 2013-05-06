<?php

require(dirname(__FILE__)."/config.php");
require_once(WWW_DIR."/lib/releases.php");
require_once(WWW_DIR."/lib/category.php");
require_once(WWW_DIR."/lib/groups.php");
require_once(WWW_DIR."/lib/framework/db.php");

$groupName = isset($argv[3]) ? $argv[3] : "";
if (isset($argv[1]) && isset($argv[2]))
{
	$releases = new Releases;
	if ($argv[1] == 1 && $argv[2] == "true")
	{
		$releases->processReleases(1, 1, $groupName);
	}
	else if ($argv[1] == 1 && $argv[2] == "false")
	{
		$releases->processReleases(1, 2, $groupName);
	}
	else if ($argv[1] == 2 && $argv[2] == "true")
	{
		$releases->processReleases(2, 1, $groupName);
	}
	else if ($argv[1] == 2 && $argv[2] == "false")
	{
		$releases->processReleases(2, 2, $groupName);
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
		$cat = new Category();
		$relcount = 0;
		echo "Categorizing all non-categorized releases in other->misc using usenet subject. This can take a while, be patient.\n";
		
		$relres = $db->queryDirect("SELECT name, ID, groupID from releases where categoryID = 7010 and relnamestatus = 0");
		while ($relrow = $db->fetchAssoc($relres))
		{
			$catID = $cat->determineCategory($relrow['name'], $relrow['groupID']);
			$db->queryDirect(sprintf("UPDATE releases set categoryID = %d, relnamestatus = 1 where ID = %d", $catID, $relrow['ID']));
			$relcount ++;
		} 
		echo "Finished categorizing ".$relcount." releases using the usenet subject.\n";
	}
	else if ($argv[1] == 6 && ($argv[2] == "true" || $argv[2] == "false"))
	{
		$db = new Db();
		$cat = new Category();
		$relcount = 0;
		echo "Categorizing releases in misc sections using the searchname. This can take a while, be patient.\n";
		
		$relres = $db->queryDirect("SELECT searchname, ID, groupID from releases where categoryID in (1090, 2020, 3050, 5050, 6050, 7010)");
		while ($relrow = $db->fetchAssoc($relres))
		{
			$catID = $cat->determineCategory($relrow['searchname'], $relrow['groupID']);
			$db->queryDirect(sprintf("UPDATE releases set categoryID = %d, relnamestatus = 1 where ID = %d", $catID, $relrow['ID']));
			$relcount ++;
		} 
		echo "Finished categorizing ".$relcount." releases using the search name.\n";	
	}
	else
	{
		exit("Wrong argument, type php update_releases.php to see a list of valid arguments.\n");
	}
}
else
{
		exit("ERROR: You must supply arguments.\n\n".
			"php update_releases.php 1 true			...: Creates releases and attempts to categorize new releases\n".
			"php update_releases.php 2 true			...: Creates releases and leaves new releases in other -> misc\n".
			"\nThe following 2 does not create releases:\n".
			"php update_releases.php 4 true			...: Puts all releases in other-> misc (also resets to look like they have never been categorized)\n".
			"php update_releases.php 5 true			...: Categorizes all releases in other-> misc (which have not been categorized already)\n".
			"php update_releases.php 6 true			...: Categorizes releases in misc sections using the search name\n".
			"\nYou must to pass a second argument wether to post process or not, true or false\n".
			"You can pass a third argument, a group name.\n");
}

?>
