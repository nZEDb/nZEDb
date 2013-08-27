<?php
define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../../www/config.php");
require_once(FS_ROOT."/../../../www/lib/framework/db.php");
require_once(FS_ROOT."/../../../www/lib/releases.php");

$releases = new Releases();

$db = new Db();

$shows = $db->query("SELECT name FROM releases WHERE categoryid IN (SELECT id FROM category WHERE parentid = 5000) LIMIT 50 OFFSET 0");
			
foreach ($shows as $show)
{
	$res = $releases->parseNameEpSeason($show['name']);
	$res['release'] = $show['name'];
	
	echo "<pre>";
	print_r($res);
	echo "</pre>";
}
?>
