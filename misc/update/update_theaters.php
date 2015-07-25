<?php

// Run this once per day.
require_once realpath(dirname(dirname(__DIR__)) . 'indexer.php');

use nzedb\Movie;

$m = new Movie(['Echo' => true]);
$m->updateUpcoming();
