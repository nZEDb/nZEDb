<?php

define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../www/config.php");
require_once(FS_ROOT."/../../www/lib/framework/db.php");
require_once(FS_ROOT."/../../www/lib/binaries.php");
require_once(FS_ROOT."/../../www/lib/page.php");

$db = new DB();
$binaries = new Binaries();
$page = new Page;

if (!isset($argv[1]))
	exit("ERROR: You must supply a path as the first argument.\n");

$filestoprocess = Array();
$strTerminator = "\n";
$path = $argv[1];
$usenzbname = (isset($argv[2]) && $argv[2] == 'true') ? true : false;
	
if (substr($path, strlen($path) - 1) != '/')
	$path = $path."/";

$color_skipped = 190;
$color_blacklist = 11;
$color_group = 1;
$color_write_error = 9;

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
			$subject = $firstname['0'];
			//File and part count.
			$cleanerName = preg_replace('/\[\d+(\/|(\s|_)of(\s|_)|\-)\d+\]|\(\d+(\/|\sof\s|\-)\d+\)|File\s\d+\sof\s\d{1,4}|\-\s\d{1,3}\/\d{1,3}\s\-|\d{1,3}\/\d{1,3}\]\s\-|\s\d{2,3}(\\|\/)\d{2,3}\s/i', '', $subject);
			//Size.
			$cleanerName = preg_replace('/\d{1,3}(\.|,)\d{1,3}\s(K|M|G)B|\d{1,}(K|M|G)B|\d{1,}\sbytes|(\-\s)?\d{1,}(\.|,)?\d{1,}\s(g|k|m)?B\s\-(\syenc)?/i', '', $cleanerName);
			//Extensions.
			$cleanerName = preg_replace('/(\.part(\d{1,5})?)?\.(7z|\d{3}(?=(\s|"))|avi|idx|jpg|mp4|nfo|nzb|par\s?2|pdf|rar|rev|r\d\d|sfv|srs|srr|sub|txt|vol.+(par2)|zip)"?|\d{2,3}\.pdf|yEnc|\.part\d{1,4}\./i', '', $cleanerName);
			//Unwanted stuff.
			$cleanerName = preg_replace('/SECTIONED brings you|usenet\-space\-cowboys\.info|<.+https:\/\/secretusenet\.com>|> USC <|\[\d{1,}\]\-\[FULL\].+#a\.b[\w.#!@$%^&*\(\){}\|\\:"\';<>,?~` ]+\]|brothers\-of\-usenet\.info(\/\.net)?|Partner von SSL\-News\.info|AutoRarPar\d{1,5}/i', '', $cleanerName);
			//Removes some characters.
			$cleanerName = preg_replace('/<|>|"|=|\[|\]|\(|\)|\{|\}/i', '', $cleanerName);
			//Replaces some characters with 1 space.
			$cleanerName = preg_replace('/\.|\_|\-/i', ' ', $cleanerName);
			//Replace multiple spaces with 1 space
			$cleanerName = preg_replace('/\s\s+/i', ' ', $cleanerName);

			// make a fake message object to use to check the blacklist
			$msg = array("Subject" => $firstname['0'], "From" => $fromname, "Message-ID" => "");

			// if the release is in our DB already then don't bother importing it
			if ($usenzbname and $skipCheck !== true)
			{
				$usename = str_replace('.nzb', '', basename($nzbFile));
				$dupeCheckSql = sprintf("SELECT * FROM releases WHERE name = %s AND postdate - interval 10 hour <= %s AND postdate + interval 10 hour > %s",
					$db->escapeString($usename), $db->escapeString($date), $db->escapeString($date));
				$res = $db->queryOneRow($dupeCheckSql);
					
				// only check one binary per nzb, they should all be in the same release anyway
				$skipCheck = true;
				
				// if the release is in the DB already then just skip this whole procedure
				if ($res !== false)
				{
					echo "\033[38;5;".$color_skipped."mSkipping ".$cleanerName.", it already exists in your database.\n\033[0m";
					flush();
					$importfailed = true;
					break;
				}
			}
			if (!$usenzbname && $skipCheck !== true)
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
					echo "\033[38;5;".$color_skipped."mSkipping ".$cleanerName.", it already exists in your database.\n\033[0m";
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
						$usename = str_replace('.nzb', '', basename($nzbFile));
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
					$errorMessage = "\033[38;5;".$color_blacklist."mSubject is blacklisted: ".$cleanerName."\033[0m";
				}
				else
				{
					$errorMessage = "\033[38;5;".$color_group."mNo group found for ".$cleanerName." (one of ".implode(', ', $groupArr)." are missing\033[0m";
				}
				$importfailed = true;
				echo $errorMessage."\n";
				break;
			}
		}		
		if (!$importfailed)
		{
			$relguid = md5(uniqid());
			$nzb = new NZB();
		
			if($relID = $db->queryInsert(sprintf("insert into releases (name, searchname, totalpart, groupID, adddate, guid, rageID, postdate, fromname, size, passwordstatus, categoryID, nfostatus) values (%s, %s, %d, %d, now(), %s, -1, %s, %s, %s, %d, 7010, -1)", $db->escapeString($subject), $db->escapeString($cleanerName), $totalFiles, $groupID, $db->escapeString($relguid), $db->escapeString($postdate['0']), $db->escapeString($postername['0']), ($page->site->checkpasswordedrar == "1" ? -1 : 0), $db->escapeString($totalsize))));
			{
				if($nzb->copyNZBforImport($relguid, $nzbFile))
				{
					echo "Imported #".$nzbCount." successfully. ";
					echo "Name: ".$cleanerName."\n";
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
					echo "\033[38;5;".$color_write_error."mFailed copying NZB, deleting release from DB.\n\033[0m";
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
