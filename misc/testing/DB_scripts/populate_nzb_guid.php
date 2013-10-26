<?php
// This script updates all releases with the guid from the nzb file.

define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../../www/config.php");
require_once(FS_ROOT."/../../../www/lib/framework/db.php");
require_once(FS_ROOT."/../../../www/lib/releases.php");
require_once(FS_ROOT."/../../../www/lib/nzb.php");
require_once(FS_ROOT."/../../../www/lib/site.php");
require_once(FS_ROOT."/../../../www/lib/consoletools.php");

if (isset($argv[1]))
	create_guids($argv[1]);
else
	exit("This script updates all releases with the guid (md5 hash of the first message-id) from the nzb file.\nTo start the process run php populate_nzb_guid.php true\nTo delete invalid nzbs and releases, run php populate_nzb_guid.php true delete\n");

function create_guids($live)
{
	$db = new Db;
	$s = new Sites();
	$consoletools = new ConsoleTools();
	$site = $s->get();
	$timestart = TIME();
	$relcount = 0;

	if ($live == "true")
		$relrecs = $db->query(sprintf("SELECT id, guid FROM releases WHERE nzb_guid IS NULL AND nzbstatus = 1 ORDER BY id DESC"));
	elseif ($live == "limited")
		$relrecs = $db->query(sprintf("SELECT id, guid FROM releases WHERE nzb_guid IS NULL AND nzbstatus = 1 ORDER BY id DESC LIMIT 10000"));

	if ($relrecs > 0)
	{
		echo "\nUpdating ".sizeof($relrecs)." release guids\n";
		$releases = new Releases();
		$nzb = new NZB();
		$reccnt = 0;
		foreach ($relrecs as $relrec)
		{
			$reccnt++;
			if (file_exists($nzbpath = $nzb->NZBPath($relrec['guid'])))
			{
				$nzbpath = 'compress.zlib://'.$nzbpath;
				$nzbfile = @simplexml_load_file($nzbpath);
				if (!$nzbfile)
				{
					if ($argv[2] == 'delete')
					{
						echo $nzb->NZBPath($relrec['guid'])." is not a valid xml, deleting release.\n";
						$releases->fastDelete($relrec['id'], $relrec['guid'], $site);
					}
					continue;
				}
				$binary_names = array();
				foreach($nzbfile->file as $file)
				{
					$binary_names[] = $file["subject"];
				}
				if (count($binary_names) == 0)
				{
					if ($argv[2] == 'delete')
					{
						echo $nzb->NZBPath($relrec['guid'])." has no binaries, deleting release.\n";
						$releases->fastDelete($relrec['id'], $relrec['guid'], $site);
					}
					continue;
				}

				asort($binary_names);
				$segment = "";
				foreach($nzbfile->file as $file)
				{
					if ($file["subject"] == $binary_names[0])
					{
						$segment = $file->segments->segment;
						$nzb_guid = md5($segment);

						$db->queryExec("UPDATE releases set nzb_guid = ".$db->escapestring($nzb_guid)." WHERE id = ".$relrec["id"]);
						$relcount++;
						$consoletools->overWrite("Updating: ".$consoletools->percentString($reccnt,sizeof($relrecs))." Time:".$consoletools->convertTimer(TIME() - $timestart));
						break;
					}
				}
			}
			else
			{
				if ($argv[2] == 'delete')
				{
					echo $nzb->$relrec['guid']." does not have an nzb, deleting.\n";
					$releases->fastDelete($relrec['id'], $relrec['guid'], $site);
				}
			}
		}

		if ($relcount > 0)
			echo "\n";
		echo "Updated ".$relcount." release(s). This script ran for ";
		echo $consoletools->convertTime(TIME() - $timestart);
		exit(".\n");
	}
	else
		exit("No releases are missing the guid.\n");
}
