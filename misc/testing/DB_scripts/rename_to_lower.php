<?php

require_once dirname(__FILE__) . '/../../../www/config.php';
require_once nZEDb_WWW . 'config.php';
require_once nZEDb_LIB . 'framework/db.php';
require_once nZEDb_LIB . 'ColorCLI.php';

$c = new ColorCLI();
if ($argc == 1 || $argv[1] != 'true') {
	exit($c->error("\nThis script will rename every table column to lowercase that is not already lowercase.\nTo run:\nphp $argv[0] true\n"));
}

$db = new Db();
$database = DB_NAME;

$count = 0;
$list = $db->query("SELECT TABLE_NAME, COLUMN_NAME, UPPER(COLUMN_TYPE), EXTRA FROM information_schema.columns WHERE table_schema = '" . $database . "'");
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
                $db->queryDirect("ALTER TABLE " . $column['table_name'] . " CHANGE " . $column['column_name'] . " " . strtolower($column['column_name']) . " " . $column['upper(column_type)'] . " " . $extra);
                $count++;
        }
		if (strtolower($column['column_name']) === 'id' && strtolower($column['extra']) !== 'auto_increment') {
        		echo $c->header("Renaming Table " . $column['table_name'] . " Column " . $column['column_name']);
                $extra = 'AUTO_INCREMENT';
                $db->queryDirect("ALTER IGNORE TABLE " . $column['table_name'] . " CHANGE " . $column['column_name'] . " " . strtolower($column['column_name']) . " " . $column['upper(column_type)'] . " " . $extra);
                $count++;
        }
    }
}
if ($count == 0) {
	echo $c->info("All table column names are already lowercase");
} else {
	echo $c->header($count ." colums renamed");
}
