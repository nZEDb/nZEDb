<?php

require_once dirname(__FILE__) . '/../../../www/config.php';
$c = new ColorCLI();

if (!isset($argv[1]) || $argv[1] != 'true') {
	exit($c->error("\nThis script will download all tvrage shows and insert into the db.\n\n"
			. "php $argv[0] true    ...: To run.\n"));
}

$db = new DB();
$newnames = $updated = 0;

$tvshows = @simplexml_load_file('http://services.tvrage.com/feeds/show_list.php');
if ($tvshows !== false) {
	foreach ($tvshows->show as $rage) {
		if (isset($rage->id) && isset($rage->name) && !empty($rage->id) && !empty($rage->name)) {
			$db->queryInsert(sprintf('INSERT INTO tvrage (rageid, releasetitle, country) VALUES (%s, %s, %s)', $db->escapeString($rage->id), $db->escapeString($rage->name), $db->escapeString($rage->country)));
		}
	}
} else {
	exit($c->info("TVRage site has a hard limit of 400 concurrent api requests. At the moment, they have reached that limit. Please wait before retrying\n"));
}

echo $c->info("To fill out the newly populated TvRage table\n"
	. "php misc/testing/PostProc/updateTvRage.php\n");
