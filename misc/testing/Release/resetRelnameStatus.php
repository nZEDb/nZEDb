<?php

require_once dirname(__FILE__) . '/../../../www/config.php';

exit('Needs to be rewritten');
$c = new ColorCLI();


if (!isset($argv[1])) {
	exit($c->error("\nThis script will set bitwise = 0 or all rename bits to unchecked or just specific bits.\n\n"
			. "php $argv[0] true           ...: To reset bitwise on all releases to 0.\n"
			. "php $argv[0] rename         ...: To reset bitwise on all releases for just rename bits (4, 8, 16, 32, 64, 128).\n"
			. "php $argv[0] 512            ...: To reset a specific bit.\n"));
}

$db = new DB();
if ($argv[1] === 'true') {
	$res = $db->queryExec('UPDATE releases SET bitwise = 0, iscategorized = 0, isrenamed = 0, nzbstatus = 0, ishashed = 0, isrequestid = 0');
} else if ($argv[1] === 'rename') {
	$res = $db->queryExec('UPDATE releases SET isrenamed = 0, bitwise = ((bitwise & ~248)|0)');
} else if (is_numeric($argv[1])) {
	$res = $db->queryExec('UPDATE releases SET bitwise = ((bitwise & ~' . $argv[1] . ')|0)');
}

if ($res->rowCount() > 0 && is_numeric($argv[1])) {
	echo $c->header('Succesfully reset the bitwise of ' . number_format($res->rowCount()) . ' releases to 0 for bit(s) ' . $argv[1] . '.');
} else if ($res->rowCount() > 0) {
	echo $c->header('Succesfully reset the bitwise of ' . number_format($res->rowCount()) . ' releases to un-renamed.');
} else {
	echo $c->header('No releases to be reset.');
}
