<?php
require(dirname(__FILE__)."/config.php");
require_once(WWW_DIR."lib/releases.php");
require_once(WWW_DIR."lib/category.php");
require_once(WWW_DIR."lib/groups.php");
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/consoletools.php");

$groupName = isset($argv[3]) ? $argv[3] : "";
if (isset($argv[1]) && isset($argv[2]))
{
	$releases = new Releases();
	if ($argv[1] == 1 && $argv[2] == "true")
		$releases->processReleases(1, 1, $groupName, true);
	else if ($argv[1] == 1 && $argv[2] == "false")
		$releases->processReleases(1, 2, $groupName, true);
	else if ($argv[1] == 2 && $argv[2] == "true")
		$releases->processReleases(2, 1, $groupName, true);
	else if ($argv[1] == 2 && $argv[2] == "false")
		$releases->processReleases(2, 2, $groupName, true);
	else if ($argv[1] == 4 && ($argv[2] == "true" || $argv[2] == "false"))
	{
		echo "Moving all releases to other -> misc, this can take a while, be patient.\n";
		$releases->resetCategorize();
	}
	else if ($argv[1] == 5 && ($argv[2] == "true" || $argv[2] == "false"))
	{
		echo "Categorizing all non-categorized releases in other->misc using usenet subject. This can take a while, be patient.\n";
		$timestart = TIME();
		$relcount = $releases->categorizeRelease("name", "WHERE relnamestatus = 0 AND categoryID = 7010", true);
		$consoletools = new ConsoleTools();
		$time = $consoletools->convertTime(TIME() - $timestart);
		echo "\n"."Finished categorizing ".$relcount." releases in ".$time." seconds, using the usenet subject.\n";
	}
	else if ($argv[1] == 6 && $argv[2] == "true")
	{
		echo "Categorizing releases in all sections using the searchname. This can take a while, be patient.\n";
		$timestart = TIME();
		$relcount = $releases->categorizeRelease("searchname", "", true);
		$consoletools = new ConsoleTools();
		$time = $consoletools->convertTime(TIME() - $timestart);
		echo "\n"."Finished categorizing ".$relcount." releases in ".$time." seconds, using the search name.\n";
	}
	else if ($argv[1] == 6 && $argv[2] == "false")
	{
		echo "Categorizing releases in misc sections using the searchname. This can take a while, be patient.\n";
		$timestart = TIME();
		$relcount = $releases->categorizeRelease("searchname", "WHERE categoryID IN (1090, 2020, 3050, 5050, 6050, 7010)", true);
		$consoletools = new ConsoleTools();
		$time = $consoletools->convertTime(TIME() - $timestart);
		echo "\n"."Finished categorizing ".$relcount." releases in ".$time." seconds, using the search name.\n";
	}
	else
		exit("Wrong argument, type php update_releases.php to see a list of valid arguments.\n");
}
else
{
		exit("ERROR: You must supply arguments.\n\n"
			."php update_releases.php 1 true			...: Creates releases and attempts to categorize new releases\n"
			."php update_releases.php 2 true			...: Creates releases and leaves new releases in other -> misc\n"
			."\nYou must pass a second argument whether to post process or not, true or false\n"
			."You can pass a third optional argument, a group name (ex.: alt.binaries.multimedia).\n"
			."\nExtra commands::\n"
			."php update_releases.php 4 true			...: Puts all releases in other-> misc (also resets to look like they have never been categorized)\n"
			."php update_releases.php 5 true			...: Categorizes all releases in other-> misc (which have not been categorized already)\n"
			."php update_releases.php 6 false			...: Categorizes releases in misc sections using the search name\n"
			."php update_releases.php 6 true			...: Categorizes releases in all sections using the search name\n");
}

?>
