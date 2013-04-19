<?php
//run this once per day
require(dirname(__FILE__)."/config.php");
require_once(WWW_DIR."/lib/movie.php");

$m = new Movie(true);
$m->updateUpcoming();
exit ("Updated upcoming movies succesfully.\n");

?>
