<?php
define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../../www/config.php");
require_once(FS_ROOT."/../../../www/lib/framework/db.php");
require_once(FS_ROOT."/../../../www/lib/releases.php");

$releases = new Releases;

$db = new Db;

$shows = $db->query("select name from releases where categoryID IN (select ID from category where parentID = 5000) limit 0, 50");
			
foreach ($shows as $show) {
	$res = $releases->parseNameEpSeason($show['name']);
	$res['release'] = $show['name'];
	
	echo "<pre>";
	print_r($res);
	echo "</pre>";
}
?>
