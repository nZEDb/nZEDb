<?php
require_once dirname(__FILE__) . '/../../../config.php';
require_once nZEDb_LIB . 'postprocess.php';
require_once nZEDb_LIB . 'ColorCLI.php';

$c = new ColorCLI;
if (!isset($argv[1]))
	exit($c->error("This script is not intended to be run manually, it is called from update_threaded.py."));
else if (isset($argv[1]) && is_numeric($argv[1]))
{
	sleep($argv[1] - 1);
	$postprocess = new PostProcess(true);
	$postprocess->processBooks($argv[1]);
	$postprocess->processMusic($argv[1]);
	$postprocess->processGames($argv[1]);
}
?>
