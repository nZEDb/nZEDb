<?php
// Run this once per day.
require_once dirname(__FILE__) . '/config.php';

use nzedb\TvRage;

(new TvRage(['Echo' => true]))->updateSchedule();
