<?php
// To troubleshoot what's actually on usenet.
require_once dirname(__FILE__) . '/../../../www/config.php';
require_once nZEDb_LIB . 'nntp.php';
require_once nZEDb_LIB . 'backfill.php';

if (!isset($argv[2]) || !is_numeric($argv[2]))
	exit("\nTest your nntp connection, get group information and postdate for specific article.\nTo run:\ntest-nntp.php groupname articlenumber.\n");

$nntp = new Nntp();
$nntp->doConnect();

$first = $argv[2];
$group = $argv[1];

// Select a group.
$groupArr = $nntp->selectGroup($group);
print_r($groupArr);

// Insert actual local part numbers here.
$msg = $nntp->getOverview($first.'-'.$first,true,false);

// Print out the array of headers.
print_r($msg);

// get postdate for an article
$backfill = new Backfill();
$newdate = $backfill->postdate($nntp, $first, false, $group, true, 'normal');

if ($newdate != false)
	echo "The posted date for ".$group.", article ".$first." is ".date('Y-m-d H:i:s', $newdate)."\n";
else
	echo "Server failed to return postdate\n";

$nntp->doQuit();
