<?php
// Run this once per day.
require_once dirname(__FILE__) . '/config.php';
//require_once nZEDb_LIB . 'tvrage.php';

$tvrage = new TvRage(true);
$tvrage->updateSchedule();
?>
