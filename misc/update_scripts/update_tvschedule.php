<?php
//run this once per day
require("config.php");
require_once(WWW_DIR."/lib/tvrage.php");

$tvrage = new TVRage(true);
$tvrage->updateSchedule();

?>