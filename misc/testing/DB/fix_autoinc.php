<?php
require_once realpath(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'indexer.php');

use nzedb\db\Settings;

passthru('clear');
$pdo = new Settings();

if (!isset($argv[1]) || (isset($argv[1]) && $argv[1] !== 'true')) {
	exit($pdo->log->error("\nThis script resets all AUTOINC ids for each table id columns, it can be dangerous. Please BACKUP your database before running this script.\n"
					. "php fix_autoinc.php true      ...: To reset all table id columns.\n"));
}

echo $pdo->log->warning("This script resets all table id columns.");
echo $pdo->log->header("Have you backed up your database? Type 'BACKEDUP' to continue:  \n");
echo $pdo->log->warningOver("\n");
$line = fgets(STDIN);
if (trim($line) != 'BACKEDUP') {
	exit($pdo->log->error("This script is dangerous you must type BACKEDUP for it function."));
}

echo "\n";
echo $pdo->log->header("Thank you, continuing...\n\n");

$database = DB_NAME;

$count = 0;
$list = $pdo->query("SELECT TABLE_NAME, COLUMN_NAME, UPPER(COLUMN_TYPE), EXTRA FROM information_schema.columns WHERE table_schema = '" . $database . "'");
if (count($list) == 0) {
	echo $pdo->log->info("No table columns to rename");
} else {
	foreach ($list as $column) {
		if (strtolower($column['column_name']) === 'id' && strtolower($column['extra']) === 'auto_increment') {
			$extra = 'AUTO_INCREMENT';
			$placeholder = $pdo->query("SELECT MAX(id) AS id FROM " . $column['table_name']);
			if ($placeholder[0]['id'] != 0) {
				$number = $placeholder[0]['id'] + 1;
			} else {
				$number = 0;
			}
			echo $pdo->log->primary("ALTER TABLE " . $column['table_name'] . " AUTO_INCREMENT = " . $number . ";");
			$pdo->queryDirect("ALTER TABLE " . $column['table_name'] . " AUTO_INCREMENT = " . $number);
			$count++;
		}
	}
}
echo $pdo->log->header($count . " id columns reset");
