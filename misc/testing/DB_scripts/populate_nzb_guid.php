<?php

define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../../www/config.php");
require_once(FS_ROOT."/../../../www/lib/framework/db.php");
require_once(FS_ROOT."/../../../www/lib/releases.php");
require_once(FS_ROOT."/../../../www/lib/nzb.php");
require_once(FS_ROOT."/../../../www/lib/site.php");
require_once(FS_ROOT."/../../../www/lib/consoletools.php");

$db = new Db;
$s = new Sites();
$consoletools = new ConsoleTools();
$site = $s->get();
$timestart = TIME();
$relcount = 0;

//
//	This script removes all releases and nzb files based on poster, searchname, name or guid
//

if ($argv[1] != "true")
{
	usage();
}

$relrecs = $db->query(sprintf("SELECT ID, guid FROM releases where nzb_guid is null order by ID desc"));

echo "\nUpdating ".sizeof($relrecs)." releases\n";
$releases = new Releases();
$nzb = new NZB();
$reccnt = 0;
foreach ($relrecs as $relrec)
{
	$reccnt++;
	if (file_exists($nzbpath = $nzb->NZBPath($relrec['guid'])))
	{
		$nzbpath = 'compress.zlib://'.$nzbpath;
		$nzbfile = simplexml_load_file($nzbpath);
		
		$binary_names = array();
		foreach($nzbfile->file as $file)
		{
			$binary_names[] = $file["subject"];
		}
		if (count($binary_names) == 0)
			continue;
		
		asort($binary_names);
		$segment = "";
		foreach($nzbfile->file as $file)
		{
			if ($file["subject"] == $binary_names[0])
			{
				$segment = $file->segments->segment; 
				$nzb_guid = md5($segment);
				
				$db->query("UPDATE releases set nzb_guid = " . $db->escapestring($nzb_guid) . " WHERE ID = " . $relrec["ID"]);
				$relcount++;
				$consoletools->overWrite("Updating:".$consoletools->percentString($reccnt,sizeof($relrecs))." Time:".$consoletools->convertTimer(TIME() - $timestart));
				break;
			}
		}
	}
}

if ($relcount > 0)
	echo "\n";
echo "Updated ".$relcount." release(s). This script ran for ";
echo $consoletools->convertTime(TIME() - $timestart);
echo ".\n";

function usage()
{
	exit("This script updates all releases with the guid from the nzb file.  To start the process run php populate_nzb_guid.php true\n");
}