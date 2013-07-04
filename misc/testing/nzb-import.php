<?php

define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../www/config.php");
require_once(FS_ROOT."/../../www/lib/framework/db.php");
require_once(FS_ROOT."/../../www/lib/binaries.php");
require_once(FS_ROOT."/../../www/lib/page.php");
require_once(FS_ROOT."/../../www/lib/category.php");
require_once(FS_ROOT."/../../www/lib/namecleaning.php");

$db = new DB();
$binaries = new Binaries();
$page = new Page();
$n = "\n";

if (!isset($argv[1]))
	exit("ERROR: You must supply a path as the first argument.".$n);

if (!isset($argv[2]))
{
	$pieces = explode(" ", $argv[1]);
	$usenzbname = (isset($pieces[1]) && $pieces[1] == 'true') ? true : false;
	$path = $pieces[0];
}
else
{
	$path = $argv[1];
	$usenzbname = (isset($argv[2]) && $argv[2] == 'true') ? true : false;
}

$filestoprocess = Array();

if (substr($path, strlen($path) - 1) != '/')
	$path = $path."/";

$color_skipped = 190;
$color_blacklist = 11;
$color_group = 1;
$color_write_error = 9;

function categorize()
{
	$db = new DB();
	$cat = new Category();
	$relres = $db->queryDirect("SELECT name, ID, groupID from releases where categoryID = 7010 and relnamestatus = 0");
	while ($relrow = $db->fetchAssoc($relres))
	{
		$catID = $cat->determineCategory($relrow['name'], $relrow['groupID']);
		$db->queryDirect(sprintf("UPDATE releases set categoryID = %d, relnamestatus = 1 where ID = %d", $catID, $relrow['ID']));
	}
}

function relativeTime($_time) {
	$d[0] = array(1,"sec");
	$d[1] = array(60,"min");
	$d[2] = array(3600,"hr");
	$d[3] = array(86400,"day");
	$d[4] = array(31104000,"yr");

	$w = array();

	$return = "";
	$now = TIME();
	$diff = ($now-$_time);
	$secondsLeft = $diff;

	for($i=4;$i>-1;$i--)
	{
		$w[$i] = intval($secondsLeft/$d[$i][0]);
		$secondsLeft -= ($w[$i]*$d[$i][0]);
		if($w[$i]!=0)
		{
			//$return.= abs($w[$i]). " " . $d[$i][1] . (($w[$i]>1)?'s':'') ." ";
			$return.= $w[$i]. " " . $d[$i][1] . (($w[$i]>1)?'s':'') ." ";
		}
	}

	//$return .= ($diff>0)?"ago":"left";
	return $return;
}

$groups = $db->query("SELECT ID, name FROM groups");
foreach ($groups as $group)
	$siteGroups[$group["name"]] = $group["ID"];

$data = array();
//$filenames = array();;

if (!isset($groups) || count($groups) == 0)
{
	echo "no groups specified".$n;
}
else
{
	$nzbCount = 0;
	$time = TIME();

	//iterate over all nzb files in all folders and subfolders
	if(!file_exists($path))
		return;
	$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
	foreach($objects as $filestoprocess => $nzbFile){
		if(!$nzbFile->getExtension() == "nzb")
		{
			continue;
		}
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
		$firstname = array();
		$postername = array();
		$postdate = array();
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
			$date = date("Y-m-d H:i:s", (string)($file->attributes()->date));
			$postdate[] = $date;
			$subject = $firstname['0'];
			$namecleaning = new nameCleaning();

			// make a fake message object to use to check the blacklist
			$msg = array("Subject" => $firstname['0'], "From" => $fromname, "Message-ID" => "");

			// if the release is in our DB already then don't bother importing it
			if ($usenzbname && $skipCheck !== true)
			{
				$usename = str_replace('.nzb', '', basename($nzbFile));
				$cleanerName = $usename;
				$dupeCheckSql = sprintf("SELECT * FROM releases WHERE name = %s AND postdate - interval 10 hour <= %s AND postdate + interval 10 hour > %s",
					$db->escapeString($usename), $db->escapeString($date), $db->escapeString($date));
				$res = $db->queryOneRow($dupeCheckSql);

				// only check one binary per nzb, they should all be in the same release anyway
				$skipCheck = true;

				// if the release is in the DB already then just skip this whole procedure
				if ($res !== false)
				{
					echo $n."\033[38;5;".$color_skipped."mSkipping ".$cleanerName.", it already exists in your database.\033[0m";
					flush();
					$importfailed = true;
					break;
				}
			}
			if (!$usenzbname && $skipCheck !== true)
			{
				$usename = $db->escapeString($name);
				$cleanerName = $namecleaning->releaseCleaner($subject);
				$dupeCheckSql = sprintf("SELECT name FROM releases WHERE name = %s AND postdate - interval 10 hour <= %s AND postdate + interval 10 hour > %s",
					$db->escapeString($firstname['0']), $db->escapeString($date), $db->escapeString($date));
				$res = $db->queryOneRow($dupeCheckSql);

				// only check one binary per nzb, they should all be in the same release anyway
				$skipCheck = true;

				// if the release is in the DB already then just skip this whole procedure
				if ($res !== false)
				{
					echo $n."\033[38;5;".$color_skipped."mSkipping ".$cleanerName.", it already exists in your database.\033[0m".$n;
					unlink($nzbFile);
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
					$errorMessage = $n."\033[38;5;".$color_blacklist."mSubject is blacklisted: ".$cleanerName."\033[0m".$n;
				}
				else
				{
					$errorMessage = $n."\033[38;5;".$color_group."mNo group found for ".$cleanerName." (one of ".implode(', ', $groupArr)." are missing)\033[0m".$n;
				}
				$importfailed = true;
				echo $errorMessage.$n;
				break;
			}
		}
		if (!$importfailed)
		{
			$relguid = sha1(uniqid());
			$nzb = new NZB();
			if($relID = $db->queryInsert(sprintf("insert into releases (name, searchname, totalpart, groupID, adddate, guid, rageID, postdate, fromname, size, passwordstatus, haspreview, categoryID, nfostatus, nzbstatus) values (%s, %s, %d, %d, now(), %s, -1, %s, %s, %s, %d, -1, 7010, -1, 1)", $db->escapeString($subject), $db->escapeString($cleanerName), $totalFiles, $groupID, $db->escapeString($relguid), $db->escapeString($postdate['0']), $db->escapeString($postername['0']), $db->escapeString($totalsize), ($page->site->checkpasswordedrar == "1" ? -1 : 0))));
			{
				if($nzb->copyNZBforImport($relguid, $nzbFile))
				{
					if ( $nzbCount % 100 == 0)
					{
						$seconds = TIME() - $time;
						if (( $nzbCount % 1000 == 0) && ( $nzbCount != 0 ))
						{
							$nzbsperhour = number_format(round($nzbCount / $seconds * 3600),0);
							echo $n."\033[38;5;".$color_blacklist."mAveraging ".$nzbsperhour." imports per hour from ".$path."\033[0m".$n;
						} else {
							categorize();
							echo $n."Imported #".$nzbCount." nzb's in ".relativeTime($time);
						}
					} else {
						echo ".";
					}
				}
				else
				{
					$db->queryOneRow(sprintf("delete from releases where postdate = %s and size = %d", $db->escapeString($postdate['0']), $db->escapeString($totalsize)));
					echo "\033[38;5;".$color_write_error."mFailed copying NZB, deleting release from DB.\033[0m".$n;
					$importfailed = true;
				}
				$nzbCount++;
				@unlink($nzbFile);
			}
		}
	}
}

echo "Processed ".$nzbCount." nzbs in ".relativeTime($time).$n;
die();

?>
