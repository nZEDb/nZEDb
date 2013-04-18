<?php
//To troubleshoot what's actually on usenet.
require("../../www/config.php");
require_once(WWW_DIR."/lib/nzb.php");
$nntp = new nntp;
$nntp->doConnect();
$groupArr = $nntp->selectGroup('alt.binaries.warez'); //since local we need the groupname here
$msg = $nntp->getXOverview('132894081-132894081',true,false); //insert actual local part numbers here
print_r($msg); //print out the array
