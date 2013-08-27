<?php
// Get the article header, convert the date to unixtime with postdate, then back to date.
require("../../../www/config.php");
require_once(WWW_DIR."/lib/backfill.php");

$nntp = new Nntp();
$nntp->doConnect();

$backfill = new Backfill();
$data = $nntp->selectGroup("alt.binaries.teevee");

$output = $backfill->postdate($nntp,"7434768");
echo date('r',$output)."\n";
print_r($data);
$nntp->doQuit();
?>
