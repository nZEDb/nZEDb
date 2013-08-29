<?php
define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../../www/config.php");
require_once(FS_ROOT."/../../../www/lib/rottentomato.php");
require_once(FS_ROOT."/../../../www/lib/site.php");

$s = new Sites();
$site = $s->get();

if (isset($site->rottentomatokey))
{
	$rt = new RottenTomato($site->rottentomatokey);
	print_r(json_decode($rt->searchMovie("inception")));
	exit("\nIf nothing was displayed above then there might be an error. If so, go to the following url: ".$rt->getURLtest()."\n");
}
else
	exit("No rotten tomato key.\n");
?>
