<?php
// Run this once per day.
require_once realpath(dirname(__FILE__) . '/config.php');
require_once nZEDb_LIB . 'tvrage.php';

$tvrage = new TVRage(true);
$tvrage->updateSchedule();
?>
