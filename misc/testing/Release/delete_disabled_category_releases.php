<?php
/* Deletes releases in categories you have disabled here : http://localhost/admin/category-list.php */
require dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;

$c = new ColorCLI();

if (isset($argv[1]) && $argv[1] == "true") {

	$timestart = TIME();
	$pdo = new Settings();
	$releases = new Releases(['Settings' => $pdo]);
	$category = new Category(['Settings' => $pdo]);
	$nzb = new NZB($pdo);
	$catlist = $category->getDisabledIDs();
	$relsdeleted = 0;
	if (count($catlist > 0)) {
		foreach ($catlist as $cat) {
			if ($rels = $pdo->query(sprintf("SELECT guid FROM releases WHERE categoryid = %d", $cat['id']))) {
				foreach ($rels as $rel) {
					$relsdeleted++;
					$releases->deleteSingle($rel['guid'], $nzb);
				}
			}
		}
	}
	$time = TIME() - $timestart;
	if ($relsdeleted > 0) {
		echo $c->header($relsdeleted . " releases deleted in " . $time . " seconds.");
	} else {
		exit($c->info("No releases to delete."));
	}
} else {
	exit($c->error("\nDeletes releases in categories you have disabled here : http://localhost/admin/category-list.php\n"
			. "php $argv[0] true    ...: run this script.\n"));
}
