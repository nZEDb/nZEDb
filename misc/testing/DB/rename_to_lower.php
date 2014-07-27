<?php
require_once dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;

passthru('clear');


$c = new ColorCLI();

if (!isset($argv[1]) || (isset($argv[1]) && $argv[1] !== 'true')) {
	exit($c->error("\nThis script renames all table columns to lowercase, it can be dangerous. Please BACKUP your database before running this script.\n"
					. "php $argv[0] true      ...: To rename all table columns to lowercase.\n"));
}

echo $c->warning("This script renames all table colums to lowercase.");
echo $c->header("Have you backed up your database? Type 'BACKEDUP' to continue:  \n");
echo $c->warningOver("\n");
$line = fgets(STDIN);
if (trim($line) != 'BACKEDUP') {
	exit($c->error("This script is dangerous you must type BACKEDUP for it function."));
}

echo "\n";
echo $c->header("Thank you, continuing...\n\n");


if ($argc == 1 || $argv[1] != 'true') {
	exit($c->error("\nThis script will rename every table column to lowercase that is not already lowercase.\nTo run:\nphp $argv[0] true\n"));
}

$pdo = new Settings();
$database = DB_NAME;

$count = 0;
$list = $pdo->query("SELECT TABLE_NAME, COLUMN_NAME, UPPER(COLUMN_TYPE), EXTRA FROM information_schema.columns WHERE table_schema = '" . $database . "'");
if (count($list) == 0) {
	echo $c->info("No table columns to rename");
} else {
	foreach ($list as $column) {
		if ($column['column_name'] !== strtolower($column['column_name'])) {
			echo $c->header("Renaming Table " . $column['table_name'] . " Column " . $column['column_name']);
			if (isset($column['extra'])) {
				$extra = strtoupper($column['extra']);
			} else {
				$extra = '';
			}
			$pdo->queryDirect("ALTER TABLE " . $column['table_name'] . " CHANGE " . $column['column_name'] . " " . strtolower($column['column_name']) . " " . $column['upper(column_type)'] . " " . $extra);
			$count++;
		}
		if (strtolower($column['column_name']) === 'id' && strtolower($column['extra']) !== 'auto_increment') {
			echo $c->header("Renaming Table " . $column['table_name'] . " Column " . $column['column_name']);
			$extra = 'AUTO_INCREMENT';
			$placeholder = $pdo->queryDirect("SELECT MAX(id) FROM " . $column['table_name']);
			$pdo->queryDirect("ALTER IGNORE TABLE " . $column['table_name'] . " CHANGE " . $column['column_name'] . " " . strtolower($column['column_name']) . " " . $column['upper(column_type)'] . " " . $extra);
			$pdo->queryDirect("ALTER IGNORE TABLE " . $column['table_name'] . " AUTO_INCREMENT = " . $placeholder + 1);
			$count++;
		}
	}
}
if ($count == 0) {
	echo $c->info("All table column names are already lowercase");
} else {
	echo $c->header($count . " colums renamed");
}
