<?php
// Run this once per day.
require_once dirname(__FILE__) . '/config.php';

(new \TvRage(['Echo' => true]))->updateSchedule();
