<?php
require_once dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;

$c = new ColorCLI();
if ($argc === 1 || !is_numeric($argv[1])) {
	exit($c->error("\nThis script will show table data, index and free space used. The argument needed is numeric.\n\n"
		. "php $argv[0] 1      ...: To show all tables with data + index space used greater than 1MB or free space greater than 1MB.\n"
		. "php $argv[0] .01    ...: To show all tables with data + index space used greater than .01MB or free space greater than .01MB.\n"));
}
passthru('clear');
$pdo = new Settings();
$data = $index = $total = $free = 0;

$table_data = "SELECT TABLE_NAME AS 'Table', TABLE_ROWS AS 'Rows', "
	. "ENGINE AS 'engine', "
	. "CREATE_OPTIONS AS 'format', "
	. "((DATA_LENGTH) / POWER(1024,2)) AS 'data', "
	. "((INDEX_LENGTH) / POWER(1024,2)) AS 'index', "
	. "((DATA_FREE) / POWER(1024,2)) AS 'free', "
	. "((DATA_LENGTH + INDEX_LENGTH) / POWER(1024,2)) AS 'total' "
	. "FROM information_schema.TABLES WHERE information_schema.TABLES.table_schema = '" . DB_NAME . "' "
	. "ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC";

$run = $pdo->queryDirect($table_data);

$mask = $c->headerOver("%-25.25s ") .  $c->primaryOver("%7.7s %10.10s %15.15s %15.15s %15.15s %15.15s\n");
printf($mask, 'Table Name', 'Engine', 'Row_Format', 'Data Size', 'Index Size', 'Free Space', 'Total Size');
printf($mask, '=========================', '=======', '==========', '===============', '===============', '===============', '===============');
foreach ($run as $table) {
	if ($table['total'] > $argv[1] || $table['free'] > $argv[1]) {
		printf($mask, $table['table'], $table['engine'], str_replace('row_format=', '', $table['format']), number_format($table['data'], 2) . " MB", number_format($table['index'], 2) . " MB", number_format($table['free'], 2) . " MB", number_format($table['total'], 2) . " MB");
	}
	$data += $table['data'];
	$index += $table['index'];
	$free += $table['free'];
	$total += $table['total'];
}
printf($mask, '=========================', '=======', '==========', '===============', '===============', '===============', '===============');
printf($mask, 'Table Name', 'Engine', 'Row_Format', 'Data Size', 'Index Size', 'Free Space', 'Total Size');
printf($mask, '', '', '', number_format($data, 2) . " MB", number_format($index, 2) . " MB", number_format($free	, 2) . " MB", number_format($total, 2) . " MB");

$myisam = $pdo->queryOneRow("SELECT CONCAT(ROUND(KBS/POWER(1024,IF(pw<0,0,IF(pw>3,0,pw)))+0.49999), "
	. "SUBSTR(' KMG',IF(pw<0,0,IF(pw>3,0,pw))+1,1)) recommended_key_buffer_size "
	. "FROM (SELECT SUM(index_length) KBS "
	. "FROM information_schema.tables "
	. "WHERE engine='MyISAM' AND table_schema NOT IN ('information_schema','mysql')) A, (SELECT 3 pw) B;", false);

$innodb = $pdo->queryOneRow("SELECT CONCAT(ROUND(KBS/POWER(1024,IF(pw<0,0,IF(pw>3,0,pw)))+0.49999), "
	. "SUBSTR(' KMG',IF(pw<0,0,IF(pw>3,0,pw))+1,1)) recommended_innodb_buffer_pool_size "
	. "FROM (SELECT SUM(index_length) KBS "
	. "FROM information_schema.tables "
	. "WHERE engine='InnoDB') A,(SELECT 3 pw) B;", false);

$a = $myisam['recommended_key_buffer_size'];
if ($myisam['recommended_key_buffer_size'] === null) {
	$a = '12M';
}
$b = $innodb['recommended_innodb_buffer_pool_size'];
if ($innodb['recommended_innodb_buffer_pool_size'] === null) {
	$b = '12M';
}

// Get current variables
$aa = $pdo->queryOneRow("SHOW VARIABLES WHERE Variable_name = 'key_buffer_size'", false);
$bb = $pdo->queryOneRow("SHOW VARIABLES WHERE Variable_name = 'innodb_buffer_pool_size'", false);

if ($aa['value'] >= 1073741824) {
	$current_a = $aa['value'] / 1024 / 1024 / 1024;
	$current_a .= "G";
} else {
	$current_a = $aa['value'] / 1024 / 1024;
	$current_a .= "M";
}
if ($bb['value'] >= 1073741824) {
	$current_b = $bb['value'] / 1024 / 1024 / 1024;
	$current_b .= "G";
} else {
	$current_b = $bb['value'] / 1024 / 1024;
	$current_b .= "M";
}

echo $c->headerOver("\n\nThe recommended minimums are:\n");
echo $c->primaryOver("MyISAM: key-buffer-size           = ") . $c->alternate($a);
echo $c->primaryOver("InnoDB: innodb_buffer_pool_size   = ") . $c->alternate($b);

echo $c->headerOver("\nYour current setting are:\n");
echo $c->primaryOver("MyISAM: key-buffer-size           = ") . $c->alternate($current_a);
echo $c->primaryOver("InnoDB: innodb_buffer_pool_size   = ") . $c->alternate($current_b);
