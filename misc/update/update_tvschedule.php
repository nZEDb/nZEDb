<?php
// Run this once per day.
require_once realpath(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'indexer.php');

use nzedb\TvRage;

(new TvRage(['Echo' => true]))->updateSchedule();
