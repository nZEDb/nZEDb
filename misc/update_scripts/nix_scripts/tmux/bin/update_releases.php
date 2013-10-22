<?php
require_once(dirname(__FILE__)."/../../../config.php");
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/releases.php");
require_once(WWW_DIR."lib/groups.php");
require_once(WWW_DIR."lib/consoletools.php");
require_once(WWW_DIR."lib/binaries.php");

$pieces = explode("  ", $argv[1]);
$groupid = $pieces[0];
//sleep($pieces[1]*2);

$releases = new Releases(true);
$groups = new Groups();
$groupname = $groups->getByNameByID($groupid);
$group = $groups->getByName($groupname);
$consoletools = new ConsoleTools();
$binaries = new Binaries();
$db = new DB();

if ($releases->hashcheck == 0)
	exit("You must run update_binaries.php to update your collectionhash.\n");

try {
	$test = $db->prepare('SELECT * FROM '.$pieces[0].'_collections LIMIT 1');
	$test->execute();
} catch (PDOException $e) {
	//No collections available
	//exit($groupname." has no collections to process\n");
	exit();
}

//update_binaries per group
$releases->processReleases = microtime(true);
//echo "\n\nStarting release update process on ".$groupname." (".date("Y-m-d H:i:s").")\n";
$releases->processReleasesStage1($groupid);
$releases->processReleasesStage2($groupid);
$releases->processReleasesStage3($groupid);
$retcount = $releases->processReleasesStage4($groupid);
$releases->processReleasesStage4dot5($groupid);
$nzbcount = $releases->processReleasesStage5($groupid);

//too slow because of first update query, would loop of every release on each thread
//better to run as separate script
//$releases->processReleasesStage5b($groupid, $echooutput=true);

$releases->processReleasesStage6($categorize=1, $postproc=0, $groupid);
$releases->processReleasesStage7a($groupid);

$deletedCount = $releases->processReleasesStage7b($groupid);

$db = new DB();

$mask = "%-30.30s added %s releases and has %s collections waiting to be created (still incomplete or in queue for creation).\n";

$first = number_format($retcount);
$second = number_format(array_shift($db->queryOneRow('SELECT COUNT(id) FROM collections')));
printf($mask, str_replace('alt.binaries', 'a.b', $groupname), $first, $second);
