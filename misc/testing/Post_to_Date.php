<?php

require("../../www/config.php");
require_once(WWW_DIR."/lib/backfill.php");
$nntp = new nntp;
$nntp->doConnect();
$backfill = new Backfill();
$data = $nntp->selectGroup("alt.binaries.nintendo.ds");
$output = $backfill->postdate($nntp,"7434768");
echo date('r',$output)."\n";
print_r($data);
$nntp->doQuit();
?>
