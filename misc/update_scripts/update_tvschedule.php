<?php
//run this once per day
require(dirname(__FILE__)."/config.php");
require_once(WWW_DIR."/lib/tvrage.php");

$tvrage = new TVRage(true);
$tvrage->updateSchedule();
exit ("Updated the TVRage schedule succesfully.\n");

?>
