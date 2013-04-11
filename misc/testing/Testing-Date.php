<?php

require("../../www/config.php");
require_once(WWW_DIR."/lib/backfill.php");
$nntp = new nntp;
$nntp->doConnect();
$backfill = new Backfill();
print_r($backfill->daytopost($nntp,"alt.binaries.teevee", 100));
$nntp->doQuit();
?>
