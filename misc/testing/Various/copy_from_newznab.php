<?php
require dirname(__FILE__) . '/../../../www/config.php';
require_once nZEDb_LIB . 'utility' . DS . 'CopyFileTree.php';

use nzedb\db\Settings;

$reorg = nZEDb_MISC . 'testing' . DS . 'NZBs' . DS . 'nzb-reorg.php';
$pdo = new Settings();
$level = $pdo->getSetting('nzbsplitlevel');
$nzbpath = $pdo->getSetting('nzbpath');

if (!isset($argv[1])) {
	exit("WARNING: Run convert_from_newznab.php BEFORE running this script.\nUsage php copy_from_newznab.php path_to_newznab_base\n");
} else if (isset($argv[1]) && !file_exists($argv[1])) {
	exit("$argv[1]) is an invalid path\n");
} else {
	$source = realpath($argv[1] . DS . 'nzbfiles');
	$files = new \nzedb\utility\CopyFileTree($argv[1], $nzbpath);
	echo "Copying nzbs from " . $argv[1] . "\n";
	$files->copy('*');

	$source = realpath($argv[1] . DS . 'www' . DS . 'covers'); // NN+ path, do not change.
	$files = new \nzedb\utility\CopyFileTree($source, nZEDb_COVERS);
	echo "Copying covers from $source\n";
	$files->copy('*');

	echo "Setting nzbstatus for all releases\n";
	$pdo->queryExec("UPDATE releases SET nzbstatus = 1");

	system("php $reorg $level $nzbpath");
}

?>
