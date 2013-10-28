<?php
require_once(dirname(__FILE__)."/../../../config.php");
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/releases.php");
require_once(WWW_DIR."lib/groups.php");
require_once(WWW_DIR."lib/consoletools.php");
require_once(WWW_DIR."lib/binaries.php");
require_once(WWW_DIR."lib/backfill.php");
require_once(WWW_DIR."lib/postprocess.php");
require_once(WWW_DIR.'lib/nfo.php');

$pieces = explode("  ", $argv[1]);
$groupid = $pieces[0];
//sleep($pieces[1]*2);

$releases = new Releases(true);
$groups = new Groups();
$groupname = $groups->getByNameByID($groupid);
$group = $groups->getByName($groupname);
$consoletools = new ConsoleTools();
$binaries = new Binaries();
$backfill = new Backfill();
$db = new DB();


if ($releases->hashcheck == 0)
	exit("You must run update_binaries.php to update your collectionhash.\n");

if ($pieces[0] != "Stage7b")
{
	// Update Binaries per group
	$binaries->updateGroup($group, null);

	// Backfill per group
	$backfill->backfillPostAllGroups($groupname, 20000, "normal");

	// Update Releases per group
	try {
		$test = $db->prepare('SELECT * FROM '.$pieces[0].'_collections');
		$test->execute();
		$test1 = $db->prepare('SELECT * FROM '.$pieces[0].'_collections');
		$test1->execute();
		$test2 = $db->prepare('SELECT * FROM '.$pieces[0].'_collections');
		$test2->execute();
		// Don't even process the group if no collections
		if ($test->rowCount() == 0)
		{
			//$mask = "%-30.30s has %s collections, skipping.\n";
			//printf($mask, str_replace('alt.binaries', 'a.b', $groupname), number_format($test->rowCount()));
			exit();
		}
	} catch (PDOException $e) {
		//No collections available
		//exit($groupname." has no collections to process\n");
		exit();
	}

	// Runs function that are per group
	$releases->processReleasesStage1($groupid, false);
	$releases->processReleasesStage2($groupid, true);
	$releases->processReleasesStage3($groupid, true);
	$retcount = $releases->processReleasesStage4($groupid, true);
	$releases->processReleasesStage5($groupid, true);
	$releases->processReleasesStage7a($groupid, true);
//	$mask = "%-30.30s added %s releases.\n";
//	$first = number_format($retcount);
//	if($retcount > 0)
//		printf($mask, str_replace('alt.binaries', 'a.b', $groupname), $first);
	
	$postprocess = new PostProcess(true);
	$postprocess->processAdditional(null, null, null, $groupid);
	$nfopostprocess = new Nfo(true);
	$nfopostprocess->processNfoFiles(null, null, null, $groupid);
}
elseif ($pieces[0] == "Stage7b")
{
	// Runs functions that run on releases table after all others completed
	$releases->processReleasesStage4dot5($groupid='', true);
	$releases->processReleasesStage6($categorize=1, $postproc=0, $groupid='', true);
	$releases->processReleasesStage7b($groupid='', true);
	//echo "Deleted ".number_format($deleted)." collections/binaries/parts.\n";
}
