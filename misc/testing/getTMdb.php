<?php
//This script will update all records in the movieinfo table

define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../www/config.php");
require_once(FS_ROOT."/../../www/lib/framework/db.php");
require_once(FS_ROOT."/../../www/lib/TMDb.php");
require_once(FS_ROOT."/../../www/lib/site.php");

$s = new Sites();
$site = $s->get();
$tmdb = new TMDb($site->tmdbkey);
print_r(json_decode($tmdb->searchMovie("inception")));

?>
