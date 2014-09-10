<?php

// Run this once per day.
require_once dirname(__FILE__) . '/config.php';

$m = new \Movie(['Echo' => true]);
$m->updateUpcoming();
