<?php
if (!isset($argv[1]) && $argv[1] != 'true')
	exit("This script will download all tvrage shows and insert into the db.\nTo run:\nphp populate_tvrage.php true\n");

require_once(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."lib/framework/db.php");

$db = new DB();
$newnames = $updated = 0;

$tvshows = @simplexml_load_file('http://services.tvrage.com/feeds/show_list.php');
if ($tvshows !== false)
{
	foreach ($tvshows->show as $rage)
	{
		if (isset($rage->id) && isset($rage->name) && !empty($rage->id) && !empty($rage->name))
			$db->queryInsert(sprintf('INSERT INTO tvrage (rageid, releasetitle, country) VALUES (%s, %s, %s)', $db->escapeString($rage->id), $db->escapeString($rage->name), $db->escapeString($rage->country)));
	}
}
else
	exit("TVRage site has a hard limit of 400 concurrent api requests. At the moment, they have reached that limit. Please wait before retrying\n");

/*
echo "TVRage\n";
$titles = $db->queryDirect("SELECT releasetitle FROM tvrage");
foreach ($titles as $title)
{
	$result = $db->queryDirect(sprintf('SELECT r.name FROM releases r where %s IN (REPLACE(r.name, ".", " "))', $db->escapeString($title['releasetitle'])));
	var_dump($result);
}
*/
