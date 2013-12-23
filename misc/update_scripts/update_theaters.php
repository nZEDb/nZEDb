<?php
// Run this once per day.
require_once dirname(__FILE__) . '/config.php';
require_once nZEDb_LIB . 'movie.php';

$m = new Movie(true);
$m->updateUpcoming();
?>
