<?php

define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../www/config.php");
require_once(FS_ROOT."/../../www/lib/framework/db.php");
require_once(FS_ROOT."/../../www/lib/binaries.php");

$db = new DB();
$binaries = new Binaries();

$filestoprocess = Array();
$strTerminator = "\n";
$path = $argv[1];
$usenzbname = (isset($argv[2]) && $argv[2] == 'true') ? true : false;
	
if (substr($path, strlen($path) - 1) != '/')
	$path = $path."/";

$groups = $db->query("SELECT ID, name FROM groups");
foreach ($groups as $group)
	$siteGroups[$group["name"]] = $group["ID"];

if (!isset($groups) || count($groups) == 0)
{
	echo "no groups specified\n";	
}
else
{
	$nzbCount = 0;
	
	//
	// read from the path, if no files submitted via the browser
	//		
	if (count($filestoprocess) == 0)
		$filestoprocess = glob($path."*.nzb"); 
	$start=date('Y-m-d H:i:s');
		
	foreach($filestoprocess as $nzbFile) 
	{
		$isBlackListed = FALSE;
		$importfailed = false;
		$nzb = file_get_contents($nzbFile);
			
		$xml = @simplexml_load_string($nzb);
		if (!$xml || strtolower($xml->getName()) != 'nzb') 
		{
			continue;
		}

		$skipCheck = false;
		$i=0;
		$firstname = [];
		$postername = [];
		$postdate = [];
		$totalFiles = 0;
		$totalsize = 0;
			
		foreach($xml->file as $file) 
		{
			//file info
			$groupID = -1;
			$name = (string)$file->attributes()->subject;
			$firstname[] = $name;
			$fromname = (string)$file->attributes()->poster;
			$postername[] = $fromname;
			$unixdate = (string)$file->attributes()->date;
			$totalFiles++;		
			$date = date("Y-m-d H:i:s", (string)$file->attributes()->date);
			$postdate[] = $date;

			// make a fake message object to use to check the blacklist
			$msg = array("Subject" => $name, "From" => $fromname, "Message-ID" => "");

			// if the release is in our DB already then don't bother importing it
			if ($usenzbname and $skipCheck !== true)
			{
				$usename = str_replace('.nzb', '', ($viabrowser ? $browserpostednames[$nzbFile] : basename($nzbFile)));
				$dupeCheckSql = sprintf("SELECT * FROM releases WHERE name = %s AND postdate - interval 5 hour <= %s AND postdate + interval 5 hour > %s",
					$db->escapeString($usename), $db->escapeString($date), $db->escapeString($date));
				$res = $db->queryOneRow($dupeCheckSql);
					
				// only check one binary per nzb, they should all be in the same release anyway
				$skipCheck = true;
				
				// if the release is in the DB already then just skip this whole procedure
				if ($res !== false)
				{
					echo ("skipping ".$usename.", it already exists in your database\n");
					flush();			
					$importfailed = true;
					break;
				}
			}
			if ($skipCheck !== true)
			{
				$usename = $db->escapeString($name);
				$dupeCheckSql = sprintf("SELECT name FROM releases WHERE name = %s AND postdate - interval 10 hour <= %s AND postdate + interval 10 hour > %s",
					$db->escapeString($firstname['0']), $db->escapeString($date), $db->escapeString($date));
				$res = $db->queryOneRow($dupeCheckSql);
					
				// only check one binary per nzb, they should all be in the same release anyway
				$skipCheck = true;
				
				// if the release is in the DB already then just skip this whole procedure
				if ($res !== false)
				{
					echo ("skipping ".$usename.", it already exists in your database\n");
					flush();			
					$importfailed = true;
					break;
				}
			}
			//groups
			$groupArr = array();
			foreach($file->groups->group as $group) 
			{
				$group = (string)$group;
				if (array_key_exists($group, $siteGroups)) 
				{
					$groupID = $siteGroups[$group];
				}
				$groupArr[] = $group;
				
				if ($binaries->isBlacklisted($msg, $group))
				{
					$isBlackListed = TRUE;
				}
			}
			if ($groupID != -1 && !$isBlackListed)
			{
				if ($usenzbname) 
				{
						$usename = str_replace('.nzb', '', ($viabrowser ? $browserpostednames[$nzbFile] : basename($nzbFile)));
				}
				if (count($file->segments->segment) > 0)
				{
					foreach($file->segments->segment as $segment) 
					{
						$size = $segment->attributes()->bytes;
						$totalsize = $totalsize+$size;
					}
				}
			}
			else
			{
				if ($isBlackListed)
				{
					$errorMessage = "blacklisted binaries found in ".$name;
				}
				else
				{
					$errorMessage = "no group found for ".$name." (one of ".implode(', ', $groupArr)." are missing";
				}
				$importfailed = true;
				echo ($errorMessage."\n");
				break;
			}
		}		
		if (!$importfailed)
		{
		$relguid = md5(uniqid());
		$nzb = new NZB();
		
			if($relID = $db->queryInsert(sprintf("insert into releases (name, searchname, totalpart, groupID, adddate, guid, rageID, postdate, fromname, size, passwordstatus, categoryID, nfostatus) values (%s, %s, %d, %d, now(), %s, -1, %s, %s, %s, -1, 7010, -1)", $db->escapeString($firstname['0']), $db->escapeString($firstname['0']), $totalFiles, $groupID, $db->escapeString($relguid), $db->escapeString($postdate['0']), $db->escapeString($postername['0']), $db->escapeString($totalsize))));
			{
				if($nzb->copyNZBforImport($relguid, $nzbFile))
				{
					echo "Imported NZB successfully. ";
					echo "Subject: ".$firstname['0']."\n";
					/*echo "Poster: ".$postername['0']."\n";
					echo "Added to usenet: ".$postdate['0']."\n";
					echo "Amount of files: ".$totalFiles."\n";
					echo "Release GUID: ".$relguid."\n";
					echo "GroupID: ".$groupID."\n";
					echo "Release size: ".number_format($totalsize / 1048576, 2)." MB"."\n\n";*/
				}
				else
				{
					$db->queryOneRow(sprintf("delete from releases where postdate = %s and size = %d", $db->escapeString($postdate['0']), $db->escapeString($totalsize)));
					echo "Failed copying NZB, deleting release from DB.\n";
					$importfailed = true;
				}
			}
			$nzbCount++;
			@unlink($nzbFile);
		}
	}
}
$seconds = strtotime(date('Y-m-d H:i:s')) - strtotime($start);
echo 'Processed '.$nzbCount.' nzbs in '.$seconds.' second(s)'."\n";
die();

?>
