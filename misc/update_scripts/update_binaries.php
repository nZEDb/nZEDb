<?php

require("config.php");
require_once(WWW_DIR."/lib/binaries.php");

$binaries = new Binaries;
$binaries->updateAllGroups();

?>
