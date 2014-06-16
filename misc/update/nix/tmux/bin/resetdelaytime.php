<?php
require_once dirname(__FILE__) . '/../../../config.php';

use nzedb\db\DB;

$db = new DB();
$s = new Sites();
$site = $s->get();
$tablepergroup = (isset($site->tablepergroup)) ? $site->tablepergroup : 0;
$c = new ColorCLI();

//reset collections dateadded to now
echo $c->header("Resetting expired collections and nzbs dateadded to now. This could take a minute or two. Really.");
if ($tablepergroup == 1) {
	$sql = 'SHOW tables';
	$tables = $db->query($sql);
	$ran = 0;
	foreach ($tables as $row) {
		$tbl = $row['tables_in_' . DB_NAME];
		if (preg_match('/collections_\d+/', $tbl)) {
			$run = $db->queryExec('UPDATE ' . $tbl . ' SET dateadded = now()');
			$ran += $run->rowCount();
		}
	}
	echo $c->primary(number_format($ran) . " collections reset.");
} else {
	$run = $db->queryExec('update collections set dateadded = now()');
	echo $c->primary(number_format($run->rowCount()) . " collections reset.");
}
sleep(2);
