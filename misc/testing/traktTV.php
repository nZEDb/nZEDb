<?php
define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../www/config.php");
require_once(FS_ROOT."/../../www/lib/trakttv.php");

/*if(isset($argv[1]) && $argv[1] == "showtitle" && isset($argv[2]) && !isset($argv[3]) && !isset($argv[4]))
{
	$trakttv = new Trakttv();
	$trakttv->traktTVsummary($argv[2]);
}
else if(isset($argv[1]) && $argv[1] == "movie" && isset($argv[2]) && !isset($argv[3]) && !isset($argv[4]))
{
	$trakttv = new Trakttv();
	$trakttv->traktMoviesummary($argv[2]);
}
else if(isset($argv[1]) && $argv[1] == "tvdb" && isset($argv[2]) && !isset($argv[3]) && !isset($argv[4]))
{
	$trakttv = new Trakttv();
	$trakttv->traktTVDBsummary($argv[2]);
}
else if(isset($argv[1]) && $argv[1] == "show" && isset($argv[2]) && isset($argv[3]) && isset($argv[4]))
{
	$trakttv = new Trakttv();
	$trakttv->traktTVSEsummary($argv[2], $argv[3], $argv[4]);
}
else
{
	exit("ERROR: Wrong set of arguments.\n\n".
		"php traktTV.php showtitle the-walking-dead\n".
		"php traktTV.php show the-walking-dead 1 3   	;; 1 = season 3 = episose\n".
		"php traktTV.php tvdb 153021			;; TVDB\n".
		"php traktTV.php movie tt1520211  		;; IMDB\n".
		"php traktTV.php movie 27115			;; TMDB\n".
		"php traktTV.php movie the-walking-dead\n\n".
		"The title must have - . or _ between the words.\n");
}*/


$trakttv = new Trakttv();
$tvarray = $trakttv->traktTVsummary($argv[1], "array");

print_r($tvarray);

?>
