<?php
require_once realpath(dirname(dirname(dirname(dirname(dirname(__DIR__))))) . DIRECTORY_SEPARATOR . 'indexer.php');

use nzedb\db\Settings;

$pdo = new Settings();
$tablepergroup = $pdo->getSetting('tablepergroup');
$tablepergroup = (isset($tablepergroup)) ? $tablepergroup : 0;

//reset collections dateadded to now
echo $pdo->log->header("Resetting expired collections and nzbs dateadded to now. This could take a minute or two. Really.");
if ($tablepergroup == 1) {
	$sql = 'SHOW tables';
	$tables = $pdo->query($sql);
	$ran = 0;
	foreach ($tables as $row) {
		$tbl = $row['tables_in_' . DB_NAME];
		if (preg_match('/collections_\d+/', $tbl)) {
			$run = $pdo->queryExec('UPDATE ' . $tbl . ' SET dateadded = now()');
			$ran += $run->rowCount();
		}
	}
	echo $pdo->log->primary(number_format($ran) . " collections reset.");
} else {
	$run = $pdo->queryExec('update collections set dateadded = now()');
	echo $pdo->log->primary(number_format($run->rowCount()) . " collections reset.");
}
sleep(2);
