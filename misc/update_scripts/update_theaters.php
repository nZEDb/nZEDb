<?php
//run this once per day
require("config.php");
require_once(WWW_DIR."/lib/movie.php");

$m = new Movie(true);
$m->updateUpcoming();

?>