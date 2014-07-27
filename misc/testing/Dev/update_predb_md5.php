<?php
//require_once dirname(__FILE__) . '/../../../www/config.php';
//
//use nzedb\db\Settings;
//
//$pdo = new Settings();
//$consoletools = new ConsoleTools();
//$predb = new PreDb();
//$c = new ColorCLI();
//
//exit($c->error("This script is deprecated.  Please use update_predb_hashes.php instead.\n\n"
//		//. "This script will recalculate and update the MD5 column for each pre.\n\n"
//		//. "php $argv[0] true      ...: To reset every predb MD5.\n"
//		));
//
//// Drop the unique index
//$has_index = $pdo->queryDirect("SHOW INDEXES IN predb WHERE Key_name = 'ix_predb_md5'");
//if ($has_index->rowCount() > 0) {
//	echo $c->info("Dropping index ix_predb_md5.");
//	$pdo->queryDirect("DROP index ix_predb_md5 ON predb");
//}
//
//$res = $pdo->queryDirect("SELECT id, title FROM predb");
//$total = $res->rowCount();
//$deleted = $count = 0;
//echo $c->header("Updating MD5's on ". number_format($total) . " preDB's.");
//foreach ($res as $row) {
//	$name = trim($row['title']);
//	$md5 = $pdo->escapeString(md5($name));
//	$title = $pdo->escapeString($name);
//
//	$pdo->queryDirect(sprintf("UPDATE predb SET title = %s, md5 = %s WHERE id = %d", $title, $md5, $row['id']));
//	$consoletools->overWriteHeader("Reset MD5s: " . $consoletools->percentString(++$count, $total));
//}
//
////Re-create the unique index, dropping dupes
//echo "\n" . $c->info("Creating index ix_predb_md5.");
//$pdo->queryDirect("ALTER IGNORE TABLE predb ADD CONSTRAINT ix_predb_md5 UNIQUE (md5)");
//echo $c->info("Updating Predb matches in releases.");
//
//$releases = $pdo->queryDirect("SELECT id, searchname FROM releases WHERE preid > 0");
//$newtotal = $releases->rowCount();
//$matched = $counter = 0;
//foreach ($releases as $release) {
//	$run = $predb->matchPre($release['searchname'], $release['id']);
//	if ($run === false) {
//		$pdo->queryExec(sprintf('UPDATE releases SET preid = 0 WHERE id = %d', $release['id']));
//	} else {
//		$matched++;
//	}
//	$consoletools->overWritePrimary("Matching Releases:  [" . number_format($matched) . "] " . $consoletools->percentString( ++$counter, $newtotal));
//}
//echo $c->header("\nDone.");
echo "This script is now defunct" . PHP_EOL;
