<?php
// To troubleshoot what's actually on usenet.
require("../../../www/config.php");
require_once(WWW_DIR."/lib/nzb.php");

$nntp = new Nntp();
$nntp->doConnect();

// Select a group.
$groupArr = $nntp->selectGroup('alt.binaries.teevee');
print_r($groupArr);
// Insert actual local part numbers here.
$msg = $nntp->getXOverview('132894081-132894081',true,false);
// Print out the array of headers.
print_r($msg);
$nntp->doQuit();
