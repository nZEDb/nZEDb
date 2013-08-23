<?php

require_once(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/binaries.php");
require_once(WWW_DIR."lib/page.php");
require_once(WWW_DIR."lib/category.php");
require_once(WWW_DIR."lib/mysqlBulk.inc.php");
require_once(WWW_DIR."lib/namecleaning.php");
require_once(WWW_DIR."lib/site.php");


$db = new DB();
$binaries = new Binaries();
$page = new Page();
$n = "\n";
$s = new Sites();
$site = $s->get();
$crosspostt = (!empty($site->crossposttime)) ? $site->crossposttime : 2;
$namecleaning = new nameCleaning();

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
	$relres = $db->query("SELECT name, ID, groupID from releases where categoryID = 7010 and relnamestatus = 0");
	foreach ($relres as $relrow)
	{
		$catID = $cat->determineCategory($relrow['name'], $relrow['groupID']);
		$db->queryUpdate(sprintf("UPDATE releases set categoryID = %d, relnamestatus = 1 where ID = %d", $catID, $relrow['ID']));
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
$filenames = array();

if (!isset($groups) || count($groups) == 0)
{
	echo "no groups specified".$n;
}
else
{
	$nzbCount = 0;
	$time = TIME();

	//iterate over all nzb files in all folders and subfolders
	$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
	foreach($objects as $filestoprocess => $nzbFile){
		if(!$nzbFile->getExtension() == "nzb" || !$nzbFile->getExtension() == "gz")
		{
			continue;
		}
		$compressed = false;
		$isBlackListed = FALSE;
		$importfailed = false;
		if ($nzbFile->getExtension() == "nzb")
		{
			$nzba = file_get_contents($nzbFile);
			$compressed = false;
		}
		elseif ($nzbFile->getExtension() == "gz")
		{
			$nzbc = 'compress.zlib://'.$nzbFile;
			$nzba = file_get_contents($nzbc);
			$compressed = true;
		}

		$xml = @simplexml_load_string($nzba);
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
            $partless = preg_replace('/\((\d+)\/(\d+)\)$/', '', $firstname['0']);
            $subject = utf8_encode(trim($partless));
			$namecleaning = new nameCleaning();

			// make a fake message object to use to check the blacklist
			$msg = array("Subject" => $subject, "From" => $fromname, "Message-ID" => "");

			// if the release is in our DB already then don't bother importing it
			if ($usenzbname && $skipCheck !== true)
			{
				$usename = str_replace('.nzb', '', basename($nzbFile));
				$usename = str_replace('.gz', '', $usename);
				$dupeCheckSql = sprintf("SELECT * FROM releases WHERE name = %s AND postdate - interval %d hour <= %s AND postdate + interval %d hour > %s", $db->escapeString($usename), $crosspostt, $db->escapeString($date), $crosspostt, $db->escapeString($date));
				$res = $db->queryOneRow($dupeCheckSql);
				$dupeCheckSql = sprintf("SELECT * FROM releases WHERE name = %s AND postdate - interval %d hour <= %s AND postdate + interval %d hour > %s", $db->escapeString($subject), $crosspostt, $db->escapeString($date), $crosspostt, $db->escapeString($date));
				$res1 = $db->queryOneRow($dupeCheckSql);
				// only check one binary per nzb, they should all be in the same release anyway
				$skipCheck = true;

				// if the release is in the DB already then just skip this whole procedure
				if ($res !== false || $res1 !== false)
				{
					echo $n."\033[38;5;".$color_skipped."mSkipping ".$subject.", it already exists in your database.\033[0m";
					@unlink($nzbFile);
					flush();
					$importfailed = true;
					break;
				}
			}
			if (!$usenzbname && $skipCheck !== true)
			{
				$usename = $db->escapeString($name);
				$dupeCheckSql = sprintf("SELECT name FROM releases WHERE name = %s AND postdate - interval %d hour <= %s AND postdate + interval %d hour > %s",
					$db->escapeString($subject), $crosspostt, $db->escapeString($date), $crosspostt, $db->escapeString($date));
				$res = $db->queryOneRow($dupeCheckSql);

				// only check one binary per nzb, they should all be in the same release anyway
				$skipCheck = true;

				// if the release is in the DB already then just skip this whole procedure
				if ($res !== false)
				{
					echo $n."\033[38;5;".$color_skipped."mSkipping ".$subject.", it already exists in your database.\033[0m".$n;
					@unlink($nzbFile);
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
					$errorMessage = $n."\033[38;5;".$color_blacklist."mSubject is blacklisted: ".$subject."\033[0m".$n;
				}
				else
				{
					$errorMessage = $n."\033[38;5;".$color_group."mNo group found for ".$subject." (one of ".implode(', ', $groupArr)." are missing)\033[0m".$n;
				}
				$importfailed = true;
				echo $errorMessage.$n;
				break;
			}
		}
		if (!$importfailed)
		{
			$relguid = sha1(uniqid().mt_rand());
			$nzb = new NZB();
			$cleanerName = $namecleaning->releaseCleaner($subject, $groupID);
			$data[] = array('name' => $subject, 'searchname' => $cleanerName, 'totalpart' => $totalFiles, 'groupID' => $groupID, 'adddate' => date('Y-m-d H:i:s'), 'guid' => $relguid, 'rageID' => '-1', 'postdate' => $postdate['0'], 'fromname' => $postername['0'], 'size' => $totalsize, 'passwordstatus' => ($page->site->checkpasswordedrar == "1" ? -1 : 0), 'haspreview' => '-1', 'categoryID' => '7010', 'nfostatus' => '-1', 'nzbstatus' => '1');
			if($nzb->copyNZBforImport($relguid, $nzba))
			{
				if ( $nzbCount % 100 == 0)
				{
					if (( $nzbCount % 1000 == 0) && ( $nzbCount != 0 ))
					{
						if (false === (mysqlBulk($data, 'releases', 'loaddata', array('query_handler' => array($db, 'query'), 'error_handler' => array($db, 'Error')))))
						{
							trigger_error('mysqlBulk failed!', E_USER_ERROR);
						}
						else
						{
							unset($data);
							foreach ($filenames as $value) {
 								@unlink($value);
							}
							unset($filenames);
							categorize();
							echo $n."Prepared #".$nzbCount." for import in ".relativeTime($time)."\t";
							echo $n."Imported #".$nzbCount." nzb's in ".relativeTime($time);
							$seconds = TIME() - $time;
							$nzbsperhour = number_format(round($nzbCount / $seconds * 3600),0);
							echo $n."\033[38;5;".$color_blacklist."mAveraging ".$nzbsperhour." imports per hour from ".$path."\033[0m".$n;
						}
					}
					else
					{
						echo $n."Prepared #".$nzbCount." for import in ".relativeTime($time)."\t";
					}
				}
				else
				{
					if ( $nzbCount % 2 == 0)
						echo ".";
				}
			}
			else
			{
				$db->queryDelete(sprintf("delete from releases where postdate = %s and size = %d", $db->escapeString($postdate['0']), $db->escapeString($totalsize)));
				echo "\033[38;5;".$color_write_error."mFailed copying NZB, deleting release from DB.\033[0m".$n;
				$importfailed = true;
			}
			$nzbCount++;
			$filenames[] = $nzbFile;
		}
	}
}

echo "Processed ".$nzbCount." nzbs in ".relativeTime($time).$n;
die();

?>
