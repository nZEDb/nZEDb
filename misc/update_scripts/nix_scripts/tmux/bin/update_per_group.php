<?php
require_once dirname(__FILE__) . '/../../../config.php';
require_once nZEDb_LIB . 'framework/db.php';
require_once nZEDb_LIB . 'releases.php';
require_once nZEDb_LIB . 'groups.php';
require_once nZEDb_LIB . 'consoletools.php';
require_once nZEDb_LIB . 'binaries.php';
require_once nZEDb_LIB . 'backfill.php';
require_once nZEDb_LIB . 'postprocess.php';
require_once nZEDb_LIB . 'nfo.php';
require_once nZEDb_LIB . 'nntp.php';
require_once nZEDb_LIB . 'ColorCLI.php';
require_once nZEDb_LIB . 'site.php';

$c = new ColorCLI;
if (!isset($argv[1]))
	exit($c->error("This script is not intended to be run manually, it is called from update_threaded.py."));

$s = new Sites();
$site = $s->get();
$pieces = explode('  ', $argv[1]);
$groupid = $pieces[0];
$releases = new Releases(true);
$groups = new Groups();
$groupname = $groups->getByNameByID($groupid);
$group = $groups->getByName($groupname);
$consoletools = new ConsoleTools();
$binaries = new Binaries();
$backfill = new Backfill();
$db = new DB();

// Create the connection here and pass, this is for post processing, so check for alternate
$nntp = new Nntp();
if (($site->alternate_nntp == 1 ? $nntp->doConnect_A() : $nntp->doConnect()) === false)
	exit($c->error("Unable to connect to usenet."));
if ($site->nntpproxy === "1")
	usleep(500000);

if ($releases->hashcheck == 0)
	exit($c->error("You must run update_binaries.php to update your collectionhash."));

if ($pieces[0] != 'Stage7b')
{
	// Update Binaries per group
	$binaries->updateGroup($group, $nntp);

	// Backfill per group
	$backfill->backfillPostAllGroups($groupname, 20000, 'normal', $nntp);

	// Update Releases per group
	try {
		$test = $db->prepare('SELECT * FROM '.$pieces[0].'_collections');
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
	$postprocess->processAdditional(null, null, null, $groupid, $nntp);
	$nfopostprocess = new Nfo(true);
	$nfopostprocess->processNfoFiles(null, null, null, $groupid, $nntp);
	if ($site->nntpproxy != "1")
		$nntp->doQuit();
}
elseif ($pieces[0] == 'Stage7b')
{
	// Runs functions that run on releases table after all others completed
	$releases->processReleasesStage4dot5($groupid='', true);
	$releases->processReleasesStage6($categorize=1, $postproc=0, $groupid='', true);
	$releases->processReleasesStage7b($groupid='', true);
	//echo 'Deleted '.number_format($deleted)." collections/binaries/parts.\n";
}
?>
