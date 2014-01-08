<?php

require_once dirname(__FILE__) . '/../../../www/config.php';
require_once nZEDb_LIB . 'framework/db.php';
require_once nZEDb_LIB . 'category.php';
require_once nZEDb_LIB . 'consoletools.php';
require_once nZEDb_LIB . 'ColorCLI.php';

$db = new DB();
$consoletools = new ConsoleTools();
$c = new ColorCLI();

if (!isset($argv[1]) || $argv[1] != 'true') {
	exit($c->error("\nThis script will recalculate and update the MD5 column for each pre.\n\n"
					. "php $argv[0] true      ...: To reset every predb MD5.\n"));
}

// Drop the unique index
$has_index = $db->queryDirect("SHOW INDEXES IN predb WHERE Key_name = 'ix_predb_md5'");
if ($has_index->rowCount() > 0) {
	echo $c->info("Dropping index ix_predb_md5.");
	$db->queryDirect("DROP index ix_predb_md5 ON predb");
}

$res = $db->queryDirect("SELECT id, title FROM predb");
$total = $res->rowCount();
$deleted = $count = 0;
echo $c->header("Updating MD5's on $total preDB's.");
foreach ($res as $row) {
	$name = trim($row['title']);
	$md5 = $db->escapeString(md5($name));
	$title = $db->escapeString($name);

	$db->queryDirect(sprintf("UPDATE predb SET title = %s, md5 = %s WHERE id = %d", $title, $md5, $row['id']));
	$consoletools->overWriteHeader("Reset MD5s: " . $consoletools->percentString(++$count, $total));
}

//Re-create the unique index, dropping dupes
echo $c->info("\nCreating index ix_predb_md5.");
$db->queryDirect("ALTER IGNORE TABLE predb ADD CONSTRAINT ix_predb_md5 UNIQUE (md5)");
echo $c->header("\nDone.");
