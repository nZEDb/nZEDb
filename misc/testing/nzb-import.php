<?php

require_once dirname(__FILE__) . '/../../www/config.php';
$c = new ColorCLI();

if (!isset($argv[1])) {
	exit($c->error("nYou must supply a path as the first argument. Two additional, optional arguments can also be used.\n"
			. "php $argv[0] /path/to/import true 1000            ...: To import using the filename as release searchname, limited to 1000"
			. "php $argv[0] /path/to/import false                ...: To import using the subject as release searchname"));
}

$db = new DB();
$binaries = new Binaries();
$s = new Sites();
$site = $s->get();
$crosspostt = (!empty($site->crossposttime)) ? $site->crossposttime : 2;
$releasecleaning = new ReleaseCleaning();
$categorize = new Category();
$maxtoprocess = 0;

if (isset($argv[2]) && is_numeric($argv[2])) {
	exit("To use a max number to process, it must be the third argument. To run:\nphp nzb-import.php /path [true, fasle] 1000\n");
}
if (!isset($argv[2])) {
	$pieces = explode("   ", $argv[1]);
	$usenzbname = (isset($pieces[1]) && $pieces[1] == 'true') ? true : false;
	$path = $pieces[0];
} else {
	$path = $argv[1];
	$usenzbname = (isset($argv[2]) && $argv[2] == 'true') ? true : false;
}
if (isset($argv[3]) && is_numeric($argv[3])) {
	$maxtoprocess = $argv[3];
}

$filestoprocess = Array();

if (substr($path, strlen($path) - 1) != '/') {
	$path = $path . "/";
}

$color_skipped = 190;
$color_blacklist = 11;
$color_group = 1;
$color_write_error = 9;

function relativeTime($_time)
{
	$d[0] = array(1, "sec");
	$d[1] = array(60, "min");
	$d[2] = array(3600, "hr");
	$d[3] = array(86400, "day");
	$d[4] = array(31104000, "yr");

	$w = array();

	$return = "";
	$now = TIME();
	$diff = ($now - $_time);
	$secondsLeft = $diff;

	for ($i = 4; $i > -1; $i--) {
		$w[$i] = intval($secondsLeft / $d[$i][0]);
		$secondsLeft -= ($w[$i] * $d[$i][0]);
		if ($w[$i] != 0) {
			$return.= $w[$i] . " " . $d[$i][1] . (($w[$i] > 1) ? 's' : '') . " ";
		}
	}
	return $return;
}

$groups = $db->query("SELECT id, name FROM groups");
foreach ($groups as $group) {
	$siteGroups[$group["name"]] = $group["id"];
}

$data = array();

if (!isset($groups) || count($groups) == 0) {
	echo "No groups specified.\n";
} else {
	$nzbCount = 0;
	$time = TIME();

	//iterate over all nzb files in all folders and subfolders
	if (!file_exists($path)) {
		echo "ERROR: Unable to access " . $path . "  Only use a folder (/path/to/nzbs/, not /path/to/nzbs/file.nzb).\n";
		return;
	}
	$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
	foreach ($objects as $filestoprocess => $nzbFile) {
		if (!$nzbFile->getExtension() == "nzb" || !$nzbFile->getExtension() == "gz") {
			continue;
		}

		$compressed = $isBlackListed = $importfailed = $skipCheck = false;

		if ($nzbFile->getExtension() == "nzb") {
			$nzba = file_get_contents($nzbFile);
			$compressed = false;
		} else if ($nzbFile->getExtension() == "gz") {
			$nzbc = 'compress.zlib://' . $nzbFile;
			$nzba = file_get_contents($nzbc);
			$compressed = true;
		} else {
			continue;
		}

		$xml = @simplexml_load_string($nzba);
		// delete invalid nzbs
		if (!$xml) {
			@unlink($nzbFile);
		}
		if (!$xml || strtolower($xml->getName()) != 'nzb') {
			continue;
		}

		$postdate = $postername = $firstname = array();
		$totalFiles = $i = $totalsize = 0;

		foreach ($xml->file as $file) {
			// File info.
			$groupID = -1;
			$name = (string) $file->attributes()->subject;
			$firstname[] = $name;
			$fromname = (string) $file->attributes()->poster;
			$postername[] = $fromname;
			$unixdate = (string) $file->attributes()->date;
			$totalFiles++;
			$date = date("Y-m-d H:i:s", (string) ($file->attributes()->date));
			$postdate[] = $date;
			//removes everything after yEnc in subject
			$partless = preg_replace('/(\(\d+\/\d+\))?(\(\d+\/\d+\))?(\(\d+\/\d+\))?(\(\d+\/\d+\))?(\(\d+\/\d+\))?(\(\d+\/\d+\))?(\(\d+\/\d+\))?$/', 'yEnc', $firstname['0']);
			$partless = preg_replace('/yEnc.*?$/i', 'yEnc', $partless);
			$subject = utf8_encode(trim($partless));

			// Make a fake message object to use to check the blacklist.
			$msg = array("Subject" => $subject, "From" => $fromname, "Message-ID" => "");

			// Groups.
			$groupArr = array();
			foreach ($file->groups->group as $group) {
				$group = (string) $group;
				if (array_key_exists($group, $siteGroups)) {
					$groupName = $group;
					$groupID = $siteGroups[$group];
				}

				$groupArr[] = $group;

				if ($binaries->isBlacklisted($msg, $group)) {
					$isBlackListed = true;
				}
			}
			if ($groupID != -1 && !$isBlackListed) {
				if ($usenzbname) {
					$usename = str_replace(array('.nzb.gz', '.nzb'), '', basename($nzbFile));
				}
				if (count($file->segments->segment) > 0) {
					foreach ($file->segments->segment as $segment) {
						$size = $segment->attributes()->bytes;
						$totalsize = $totalsize + $size;
					}
				}
			} else {
				if ($isBlackListed) {
					$errorMessage = "\n\033[38;5;" . $color_blacklist . "mSubject is blacklisted: " . $subject . "\033[0m\n";
				} else {
					$errorMessage = "\n\033[38;5;" . $color_group . "mNo group found for " . $subject . " (one of " . implode(', ', $groupArr) . " are missing)\033[0m\n";
				}

				$importfailed = true;
				echo $errorMessage . "\n";
				break;
			}
		}
		if ($importfailed) {
			@unlink($nzbFile);
		} else {
			$relguid = sha1(uniqid('', true) . mt_rand());
			$nzb = new NZB();
			$propername = true;
			if ($usenzbname === true) {
				$cleanerName = $usename;
			} else {
				$cleanerName = $releasecleaning->releaseCleaner($subject, $groupName);
			}
			if (!is_array($cleanerName)) {
				$cleanName = $cleanerName;
			} else {
				$cleanName = $cleanerName['cleansubject'];
				$propername = $cleanerName['properlynamed'];
			}
			if (empty($postername[0])) {
				$poster = '';
			} else {
				$poster = $postername[0];
			}
			if (empty($postdate[0])) {
				$posteddate = $date = date("Y-m-d H:i:s");
			} else {
				$posteddate = $postdate[0];
			}
			$category = $categorize->determineCategory($cleanName, $groupName);
			if ($propername === true) {
				$relid = $db->queryInsert(sprintf("INSERT INTO releases (name, searchname, totalpart, groupid, adddate, guid, rageid, postdate, fromname, size, passwordstatus, haspreview, categoryid, nfostatus, bitwise) VALUES (%s, %s, %d, %d, NOW(), %s, -1, %s, %s, %s, %d, -1, %d, -1, (bitwise & ~261)|261)", $db->escapeString($subject), $db->escapeString($cleanName), $totalFiles, $groupID, $db->escapeString($relguid), $db->escapeString($posteddate), $db->escapeString($poster), $db->escapeString($totalsize), ($site->checkpasswordedrar == "1" ? -1 : 0), $category));
			} else {
				$relid = $db->queryInsert(sprintf("INSERT INTO releases (name, searchname, totalpart, groupid, adddate, guid, rageid, postdate, fromname, size, passwordstatus, haspreview, categoryid, nfostatus, bitwise) VALUES (%s, %s, %d, %d, NOW(), %s, -1, %s, %s, %s, %d, -1, %d, -1, (bitwise & ~257)|257)", $db->escapeString($subject), $db->escapeString($cleanName), $totalFiles, $groupID, $db->escapeString($relguid), $db->escapeString($posteddate), $db->escapeString($poster), $db->escapeString($totalsize), ($site->checkpasswordedrar == "1" ? -1 : 0), $category));
			}

			if (isset($relid) && $relid == false) {
				echo "\n\033[38;5;" . $color_skipped . "mSkipping " . $subject . ", it already exists in your database.\033[0m\n";
				//echo "\033[38;5;".$color_skipped."m!\033[0m";
				@unlink($nzbFile);
				flush();
				continue;
			}
			if ($nzb->copyNZBforImport($relguid, $nzba)) {
				$nzbCount++;
				if ($nzbCount % 100 == 0) {
					$seconds = TIME() - $time;
					if (( $nzbCount % 1000 == 0) && ( $nzbCount != 0 )) {
						$nzbsperhour = number_format(round($nzbCount / $seconds * 3600), 0);
						echo "\n\033[38;5;" . $color_blacklist . "mAveraging " . $nzbsperhour . " imports per hour from " . $path . "\033[0m\n";
					} else if (( $nzbCount >= $maxtoprocess) && ( $maxtoprocess != 0 )) {
						$nzbsperhour = number_format(round($nzbCount / $seconds * 3600), 0);
						exit("\n\033[38;5;" . $color_blacklist . "mAveraging " . $nzbsperhour . " imports per hour from " . $path . "\033[0m\n");
					} else {
						echo "\nImported #" . $nzbCount . " nzb's in " . relativeTime($time);
					}
				} else {
					echo ".";
				}
				@unlink($nzbFile);
			} else {
				$db->queryExec(sprintf("DELETE FROM releases WHERE guid = %s AND postdate = %s AND size = %d", $db->escapeString($relguid), $db->escapeString($totalsize)));
				echo "\033[38;5;" . $color_write_error . "mFailed copying NZB, deleting release from DB.\033[0m\n";
				@unlink($nzbFile);
				flush();
				$importfailed = true;
			}
		}
	}
}

exit("Processed " . $nzbCount . " nzbs in " . relativeTime($time) . "\n");

