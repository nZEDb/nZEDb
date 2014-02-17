<?php
require dirname(__FILE__) . '/../../../www/config.php';
require_once nZEDb_LIB . 'Util.php';

$misc = nZEDb_MISC;
$s = new Sites();
$site = $s->get();
$db = new DB();
$level = $site->nzbsplitlevel;
$nzbpath = $site->nzbpath;

$row = $db->queryDirect("SELECT value FROM site WHERE setting = 'coverspath'");
if ($row) {
	Util::setCoversConstant($row[0]['value']);
} else {
	die("Unable to set Covers' constant!\n");
}

if (isWindows() === true) {
	exit("Curently this is only for linux.\n");
}

if (!isset($argv[1])) {
	exit("Usage php copy_from_newznab.php path_to_newznab_nzbs\n");
} else if (isset($argv[1]) && !file_exists($argv[1])) {
	exit("$argv[1]) is an invalid path\n");
} else {
	$from = $argv[1] . DS;
	echo "Copying nzbs from " . $from . "\n";
	system("cp -R " . $from . "* " . $nzbpath);
	echo "Copying covers from " . $from . '..' . DS . 'www' . DS . "covers\n";
	system("cp -R " . $from . "../www/covers/* " . nZEDb_COVERS);
	echo "Setting nzbstatus for all releases\n";
	$db->queryExec("UPDATE releases SET bitwise = (bitwise & ~256)|256");
	system("php " . $misc . "testing/DB/nzb-reorg.php " . $level . " " . $nzbpath);
}
