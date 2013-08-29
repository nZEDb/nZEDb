<?php
// To troubleshoot what's actually on usenet.
require("../../../www/config.php");
require_once(WWW_DIR."/lib/nzb.php");

if (!isset($argv[1]))
	exit("Tests your usenet connection, edit the script first then run it with true.");

$nntp = new Nntp();
$nntp->doConnect();

// Select a group.
$groupArr = $nntp->selectGroup('alt.binaries.teevee');
print_r($groupArr);
// Insert actual local part numbers here.
$msg = $nntp->getOverview('132894081-132894081',true,false);
// Print out the array of headers.
print_r($msg);
$nntp->doQuit();
