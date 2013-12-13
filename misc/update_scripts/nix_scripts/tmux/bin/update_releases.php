<?php
require_once dirname(__FILE__) . '/../../../config.php';
require_once nZEDb_LIB . 'framework/db.php';
require_once nZEDb_LIB . 'releases.php';
require_once nZEDb_LIB . 'groups.php';
require_once nZEDb_LIB . 'binaries.php';
require_once nZEDb_LIB . 'ColorCLI.php';

$c = new ColorCLI;
if (!isset($argv[1]))
	exit($c->error("This script is not intended to be run manually, it is called from releases_threaded.py."));

$pieces = explode('  ', $argv[1]);
$groupid = $pieces[0];

$releases = new Releases(true);
$groups = new Groups();
$groupname = $groups->getByNameByID($groupid);
$group = $groups->getByName($groupname);
$binaries = new Binaries();
$db = new DB();

if ($releases->hashcheck == 0)
	exit($c->error("You must run update_binaries.php to update your collectionhash."));

if ($pieces[0] != 'Stage7b')
{
	try {
		$test = $db->prepare('SELECT * FROM collections_'.$pieces[0]);
		$test->execute();
		// Don't even process the group if no collections
		if ($test->rowCount() == 0)
		{
			//$mask = "%-30.30s has %s collections, skipping.\n";
			//printf($mask, str_replace('alt.binaries', 'a.b', $groupname), number_format($test->rowCount()));
			exit();
		}
	} catch (PDOException $e) {
		//No collections available
		//exit($groupname." has no collections table to process.\n");
		exit();
	}

	// Runs function that are per group
	$releases->processReleasesStage1($groupid, false);
	$releases->processReleasesStage2($groupid, true);
	$releases->processReleasesStage3($groupid, true);
	$retcount = $releases->processReleasesStage4($groupid, true);
	$releases->processReleasesStage5($groupid, true);
	$releases->processReleasesStage5b($groupid, true);
	$releases->processReleasesStage7a($groupid, true);
//	$mask = "%-30.30s added %s releases.\n";
//	$first = number_format($retcount);
//	if($retcount > 0)
//		printf($mask, str_replace('alt.binaries', 'a.b', $groupname), $first);
}
else if ($pieces[0] == 'Stage7b')
{
	// Runs functions that run on releases table after all others completed
	$releases->processReleasesStage4dot5($groupid='', true);
	$releases->processReleasesStage5b($groupid='', true);
	$releases->processReleasesStage6($categorize=1, $postproc=0, $groupid='', true, null);
	$releases->processReleasesStage7b($groupid='', true);
	//echo 'Deleted '.number_format($deleted)." collections/binaries/parts.\n";
}
