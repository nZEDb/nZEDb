<?php
// To troubleshoot what's actually on usenet.
require_once(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."lib/nntp.php");
require_once(WWW_DIR."lib/backfill.php");

if (!isset($argv[1]))
	exit("\nTest your nntp connection, get group information and postdate for specific article.\nTo run:\ntest-nntp.php groupname articlenumber.\n");

$nntp = new Nntp();
$nntp->doConnect();

if (isset($argv[2]) && is_numeric($argv[2]))
	$first = $argv[2];
else
	$first = '555313070';

if (isset($argv[1]))
    $group = $argv[1];
else
    $group = 'alt.binaries.teevee';

// Select a group.
$groupArr = $nntp->selectGroup($group);
print_r($groupArr);

// Insert actual local part numbers here.
$msg = $nntp->getOverview($first.'-'.$first,true,false);

// Print out the array of headers.
print_r($msg);


// get postdate for an article
$backfill = new Backfill();
$newdate = $backfill->postdate($nntp, $first, false, $group, true);

if ($newdate != false)
	echo "The posted date for ".$group.", article ".$first." is ".date('Y-m-d H:i:s', $newdate)."\n";
else
	echo "Server failed to return postdate\n";

$nntp->doQuit();
