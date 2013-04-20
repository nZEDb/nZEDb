<?php
define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../www/config.php");
require_once(FS_ROOT."/../../www/lib/trakttv.php");

if(isset($argv[1]) && isset($argv[2]) && isset($argv[3]))
{
	$trakttv = new Trakttv();
	$trakttv->trakTVlookup($argv[1], $argv[2], $argv[3]);
}
else
{
	exit("Proper usage: php traktTV.php the-walking-dead 1 1\n".
		"The episode title must have - , 1 1 is the season and episode.\n");
}
?>
