<?php
// This script updates all releases with the guid from the nzb file.

require_once dirname(__FILE__) . '/../../../www/config.php';
require_once nZEDb_LIB . 'framework/db.php';
require_once nZEDb_LIB . 'releases.php';
require_once nZEDb_LIB . 'nzb.php';
require_once nZEDb_LIB . 'site.php';
require_once nZEDb_LIB . 'consoletools.php';

if (isset($argv[1]))
{
	$del = false;
	if (isset($argv[2]))
		$del = $argv[2];
	create_guids($argv[1], $del);
}
else
	exit("This script updates all releases with the guid (md5 hash of the first message-id) from the nzb file.\nTo start the process run php populate_nzb_guid.php true\nTo delete invalid nzbs and releases, run php populate_nzb_guid.php true delete\n");

function create_guids($live, $delete = false)
{
	$db = new Db;
	$s = new Sites();
	$consoletools = new ConsoleTools();
	$site = $s->get();
	$timestart = TIME();
	$relcount = 0;

	if ($live == "true")
	{
		$relrecs = $db->prepare(sprintf("SELECT id, guid FROM releases WHERE nzb_guid IS NULL AND (bitwise & 256) = 256 ORDER BY id DESC"));
		$relrecs->execute();
	}
	else if ($live == "limited")
	{
		$relrecs = $db->prepare(sprintf("SELECT id, guid FROM releases WHERE nzb_guid IS NULL AND (bitwise & 256) = 256 ORDER BY id DESC LIMIT 10000"));
		$relrecs->execute();
	}
	$total = $relrecs->rowCount();
	if ($total > 0)
	{
		echo "\nUpdating ".$total." release guids\n";
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
					if (isset($delete) && $delete == 'delete')
					{
						echo "\n".$nzb->NZBPath($relrec['guid'])." is not a valid xml, deleting release.\n";
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
					if (isset($delete) && $delete == 'delete')
					{
						echo "\n".$nzb->NZBPath($relrec['guid'])." has no binaries, deleting release.\n";
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
						$consoletools->overWrite("Updating: ".$consoletools->percentString($reccnt,$total)." Time:".$consoletools->convertTimer(TIME() - $timestart));
						break;
					}
				}
			}
			else
			{
				if (isset($delete) && $delete == 'delete')
				{
					echo "\n".$nzb->NZBPath($relrec['guid'])." does not have an nzb, deleting.\n";
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
	{
		echo 'Query time: '.$consoletools->convertTime(TIME() - $timestart);
		exit("\nNo releases are missing the guid.\n");
	}
}
