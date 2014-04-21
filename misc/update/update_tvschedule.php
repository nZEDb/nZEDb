<?php
// Run this once per day.
require_once dirname(__FILE__) . '/config.php';

$tvrage = new TvRage(true);
$tvrage->updateSchedule();
