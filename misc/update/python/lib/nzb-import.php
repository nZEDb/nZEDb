<?php
require_once dirname(__FILE__) . '/../../../../www/config.php';

use nzedb\db\Settings;

$c = new ColorCLI();

if (!isset($argv[1])) {
	exit($c->error("\nYou must supply a path as the first argument. Two additional, optional arguments can also be used.\n\n"
			. "php $argv[0] /path/to/import true 1000            ...: To import using the filename as release searchname, limited to 1000\n"
			. "php $argv[0] /path/to/import false                ...: To import using the subject as release searchname\n"));
}

$pdo = new Settings();
$binaries = new Binaries();
$crosspostt = $pdo->getSetting('crossposttime');
$crosspostt = (!empty($crosspostt)) ? $crosspostt : 2;
$releasecleaning = new ReleaseCleaning();
$categorize = new Categorize();
$nzbsperhour = $nzbSkipped = $maxtoprocess = 0;
$consoleTools = new ConsoleTools();

if (isset($argv[2]) && is_numeric($argv[2])) {
	exit($c->error("\nTo use a max number to process, it must be the third argument. \nTo run:\nphp nzb-import.php /path [true, false] 1000\n"));
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

$groups = $pdo->query("SELECT id, name FROM groups");
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
		echo $c->error("\nUnable to access " . $path . "  Only use a folder (/path/to/nzbs/, not /path/to/nzbs/file.nzb).\n");
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
			$nzba = @file_get_contents($nzbc);
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
					$nzbSkipped++;
				}

				$importfailed = true;
				break;
			}
		}
		if ($importfailed) {
			@unlink($nzbFile);
		} else {
			$relguid = sha1(uniqid('', true) . mt_rand());
			$nzb = new NZB();
			$propername = true;
			$relid = false;
			if ($usenzbname === true) {
				$cleanerName = $usename;
			} else {
				$cleanerName = $releasecleaning->releaseCleaner($subject, $fromname, $totalsize, $groupName);
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
			$category = $categorize->determineCategory($cleanName, $groupID);

			// A 1% variance in size is considered the same size when the subject and poster are the same
			$minsize = $totalsize * .99;
			$maxsize = $totalsize * 1.01;

			// Look for match on name, poster and size
			$dupecheck = $pdo->queryOneRow(sprintf('SELECT id, guid FROM releases WHERE name = %s AND fromname = %s AND size BETWEEN %s AND %s', $pdo->escapeString($subject), $pdo->escapeString($poster), $pdo->escapeString($minsize), $pdo->escapeString($maxsize)));
			if ($dupecheck === false) {
				if ($propername === true && $importfailed === false) {
					$relid = $pdo->queryInsert(sprintf("INSERT INTO releases (name, searchname, totalpart, group_id, adddate, guid, rageid, postdate, fromname, size, passwordstatus, haspreview, categoryid, nfostatus, nzbstatus, isrenamed, iscategorized) VALUES (%s, %s, %d, %d, NOW(), %s, -1, %s, %s, %s, %d, -1, %d, -1, 1, 1, 1)", $pdo->escapeString($subject), $pdo->escapeString($cleanName), $totalFiles, $groupID, $pdo->escapeString($relguid), $pdo->escapeString($posteddate), $pdo->escapeString($poster), $pdo->escapeString($totalsize), ($pdo->getSetting('checkpasswordedrar') == "1" ? -1 : 0), $category));
				} else {
					$relid = $pdo->queryInsert(sprintf("INSERT INTO releases (name, searchname, totalpart, group_id, adddate, guid, rageid, postdate, fromname, size, passwordstatus, haspreview, categoryid, nfostatus, nzbstatus, isrenamed, iscategorized) VALUES (%s, %s, %d, %d, NOW(), %s, -1, %s, %s, %s, %d, -1, %d, -1, 1, 1, 1)", $pdo->escapeString($subject), $pdo->escapeString($cleanName), $totalFiles, $groupID, $pdo->escapeString($relguid), $pdo->escapeString($posteddate), $pdo->escapeString($poster), $pdo->escapeString($totalsize), ($pdo->getSetting('checkpasswordedrar') == "1" ? -1 : 0), $category));
				}
			}

			if ($relid === false || $dupecheck === false) {
				$nzbSkipped++;
				@unlink($nzbFile);
				flush();
			}
			if (copyNZBforImport($relguid, $nzba, $nzb, $pdo)) {
				if ($relid !== false) {
					$nzbCount++;
				}
				if (( $nzbCount % 100 == 0) && ( $nzbCount != 0 )) {
					$seconds = TIME() - $time;
					$nzbsperhour = number_format(round($nzbCount / $seconds * 3600), 0);
				}
				if (( $nzbCount >= $maxtoprocess) && ( $maxtoprocess != 0 )) {
					$nzbsperhour = number_format(round($nzbCount / $seconds * 3600), 0);
					exit($c->header("\nProcessed " . number_format($nzbCount) . " nzbs in " . relativeTime($time) . "\nAveraged " . $nzbsperhour . " imports per hour from " . $path));
				}
				@unlink($nzbFile);
			} else {
				$pdo->queryExec(sprintf("DELETE FROM releases WHERE guid = %s AND postdate = %s AND size = %d", $pdo->escapeString($relguid), $pdo->escapeString($totalsize)));
				echo $c->error("\nFailed copying NZB, deleting release from DB.\n");
				@unlink($nzbFile);
				flush();
				$importfailed = true;
			}
			$consoleTools->overWritePrimary('Imported ' . "[" . number_format($nzbSkipped) . "] " . number_format($nzbCount) . " NZBs (" . $nzbsperhour . "iph) in " . relativeTime($time));
		}
	}
}

exit($c->header("\nRunning Time: " . relativeTime($time) . "\n"
		. "Processed:    " . number_format($nzbCount + $nzbSkipped) . "\n"
		. "Imported:     " . number_format($nzbCount) . "\n"
		. "Duplicates:   " . number_format($nzbSkipped)));

/**
 * Compress an imported NZB and store it inside the nzbfiles folder.
 *
 * @param string $relguid    The guid of the release.
 * @param string $nzb        String containing the imported NZB.
 * @param NZB    $NZB
 * @param object $site
 *
 * @return bool
 *
 */
function copyNZBforImport($relguid, $nzb, $NZB, $pdo)
{
	$path = $NZB->getNZBPath($relguid, 0, true);
	$fp = gzopen($path, 'w5');
	if ($fp && $nzb) {
		$date1 = htmlspecialchars(date('F j, Y, g:i a O'), ENT_QUOTES, 'utf-8');
		$article =
			preg_replace(
				'/dtd">\s*<nzb xmlns=/', "dtd\">\n<!-- NZB Generated by: nZEDb "
				. $pdo->version() . ' ' . $date1 . " -->\n<nzb xmlns=", $nzb
			);

		gzwrite($fp,
			preg_replace(
				'/<\/file>\s*(<!--.+)?\s*<\/nzb>\s*/si'
				, "</file>\n  <!--GrabNZBs-->\n</nzb>"
				, $article
			)
		);

		gzclose($fp);
		// Chmod to fix issues some users have with file permissions.
		chmod($path, 0777);
		return true;
	} else {
		echo "ERROR: NZB already exists?\n";
		return false;
	}
}
