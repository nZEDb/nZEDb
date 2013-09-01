<?php
require_once(dirname(__FILE__)."/../../../config.php");
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/releases.php");
require_once(WWW_DIR."lib/groups.php");
require_once(WWW_DIR."lib/consoletools.php");

$pieces = explode("  ", $argv[1]);
$groupid = $pieces[0];
//sleep($pieces[1]);

$releases = new Releases(true);
$groups = new Groups();
$groupname = $groups->getByNameByID($groupid);
$consoletools = new ConsoleTools();

if ($pieces[0] != "Stage7b")
{
	if ($releases->hashcheck == 0)
		exit("You must run update_binaries.php to update your collectionhash.\n");

	$releases->processReleases = microtime(true);
	echo "\n\nStarting release update process on ".$groupname." (".date("Y-m-d H:i:s").")\n";
	$releases->processReleasesStage1($groupid, $echooutput=true);
	$releases->processReleasesStage2($groupid, $echooutput=true);
	$releases->processReleasesStage3($groupid, $echooutput=true);
	$retcount = $releases->processReleasesStage4($groupid, $echooutput=true);
	$releases->processReleasesStage4dot5($groupid, $echooutput=true);
	$nzbcount = $releases->processReleasesStage5($groupid, $echooutput=true);

	//too slow because of first update query, would loop of every release on each thread
	//better to run as separate script
	//$releases->processReleasesStage5b($groupid, $echooutput=true);

	$releases->processReleasesStage6($categorize=1, $postproc=0, $groupid, $echooutput=true);
	$releases->processReleasesStage7a($groupid, $echooutput=true);
}
elseif ($pieces[0] == "Stage7b")
{
	$deletedCount = $releases->processReleasesStage7b($groupid="", $echooutput=true);

	$db = new DB();
	echo number_format(array_shift($db->queryOneRow("SELECT COUNT(id) FROM collections")))." collections waiting to be created (still incomplete or in queue for creation).\n";
}
