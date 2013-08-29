<?php
// Run this once per day.
require(dirname(__FILE__)."/config.php");
require_once(WWW_DIR."/lib/tvrage.php");

$tvrage = new TVRage(true);
$tvrage->updateSchedule();
?>
