<?php

require_once './config.php';

$db = new DB();
$binaries = new Binaries();
$releaseCleaner = new ReleaseCleaning();
$s = new Sites();
$site = $s->get();
$crosspostt = (!empty($site->crossposttime)) ? $site->crossposttime : 2;
$categorize = new Category();

$page = new Page;

if (empty($argc)) {
	$page = new AdminPage();
}

if (!empty($argc)) {
	if (!isset($argv[1])) {
		exit("ERROR: You must supply a path as the first argument.\n");
	}
}

$filestoprocess = Array();
$browserpostednames = Array();
$viabrowser = false;

if (!empty($argc) || $page->isPostBack()) {
	$retval = "";

	// Via browser, build an array of all the nzb files uploaded into php /tmp location.
	if (isset($_FILES["uploadedfiles"])) {
		foreach ($_FILES["uploadedfiles"]["error"] as $key => $error) {
			if ($error == UPLOAD_ERR_OK) {
				$tmp_name = $_FILES["uploadedfiles"]["tmp_name"][$key];
				$name = $_FILES["uploadedfiles"]["name"][$key];
				$filestoprocess[] = $tmp_name;
				$browserpostednames[$tmp_name] = $name;
				$viabrowser = true;
			}
		}
	}

	if (!empty($argc)) {
		$strTerminator = "\n";
		$path = $argv[1];
		$usenzbname = (isset($argv[2]) && $argv[2] == 'true') ? true : false;
	} else {
		$strTerminator = "<br />";
		$path = (isset($_POST["folder"]) ? $_POST["folder"] : "");
		$usenzbname = (isset($_POST['usefilename']) && $_POST["usefilename"] == 'on') ? true : false;
	}

	if (substr($path, strlen($path) - 1) != '/') {
		$path = $path . "/";
	}

	$groups = $db->query("SELECT id, name FROM groups");
	foreach ($groups as $group) {
		$siteGroups[$group["name"]] = $group["id"];
	}

	if (!isset($groups) || count($groups) == 0) {
		if (!empty($argc)) {
			echo "no groups specified\n";
		} else {
			$retval.= "no groups specified" . "<br />";
		}
	} else {
		$nzbCount = 0;

		// Read from the path, if no files submitted via the browser.
		if (count($filestoprocess) == 0) {
			$filestoprocess = glob($path . "*.nzb");
		}
		$start = date('Y-m-d H:i:s');

		foreach ($filestoprocess as $nzbFile) {
			$nzba = file_get_contents($nzbFile);

			$xml = @simplexml_load_string($nzba);
			if (!$xml || strtolower($xml->getName()) != 'nzb') {
				continue;
			}

			$importfailed = $isBlackListed = $skipCheck = false;
			$i = $totalFiles = $totalsize = 0;
			$firstname = $postername = $postdate = array();

			foreach ($xml->file as $file) {
				// File info.
				$groupID = -1;
				$name = (string) $file->attributes()->subject;
				$firstname[] = $name;
				$fromname = (string) $file->attributes()->poster;
				$postername[] = $fromname;
				$unixdate = (string) $file->attributes()->date;
				$totalFiles++;
				$date = date("Y-m-d H:i:s", (string) $file->attributes()->date);
				$postdate[] = $date;
				$subject = utf8_encode(trim($firstname['0']));

				// Make a fake message object to use to check the blacklist.
				$msg = array("Subject" => $firstname['0'], "From" => $fromname, "Message-ID" => "");

				// Groups.
				$groupArr = array();
				foreach ($file->groups->group as $group) {
					$group = (string) $group;
					if (array_key_exists($group, $siteGroups)) {
						$groupID = $siteGroups[$group];
						$groupName = $group;
					}
					$groupArr[] = $group;

					if ($binaries->isBlacklisted($msg, $group)) {
						$isBlackListed = TRUE;
					}
				}

				if ($groupID != -1 && !$isBlackListed) {
					if ($usenzbname) {
						$usename = str_replace('.nzb', '', ($viabrowser ? $browserpostednames[$nzbFile] : basename($nzbFile)));
					}
					if (count($file->segments->segment) > 0) {
						foreach ($file->segments->segment as $segment) {
							$size = $segment->attributes()->bytes;
							$totalsize = $totalsize + $size;
						}
					}
				} else {
					if ($isBlackListed) {
						$errorMessage = "Subject is blacklisted: " . $subject;
					} else {
						$errorMessage = "No group found for " . $name . " (one of " . implode(', ', $groupArr) . " are missing";
					}

					$importfailed = true;
					if (!empty($argc)) {
						echo ($errorMessage . "\n");
						flush();
					} else {
						$retval.= $errorMessage . "<br />";
					}

					break;
				}
			}

			if (!$importfailed) {
				$relguid = sha1(uniqid('', true) . mt_rand());
				$nzb = new NZB();
				$propername = false;
				// Removes everything after yEnc in subject.
				$partless = preg_replace('/(\(\d+\/\d+\))?(\(\d+\/\d+\))?(\(\d+\/\d+\))?(\(\d+\/\d+\))?(\(\d+\/\d+\))?(\(\d+\/\d+\))?(\(\d+\/\d+\))?$/', 'yEnc', $firstname['0']);
				$partless = preg_replace('/yEnc.*?$/i', 'yEnc', $partless);
				$subject = utf8_encode(trim($partless));
				$cleanerName = $releaseCleaner->releaseCleaner($subject, $groupName);
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
					$relID = $db->queryInsert(sprintf("INSERT INTO releases (name, searchname, totalpart, groupid, adddate, guid, rageid, postdate, fromname, size, passwordstatus, haspreview, categoryid, nfostatus, nzbstatus, renamed, categorized) VALUES (%s, %s, %d, %d, NOW(), %s, -1, %s, %s, %s, %d, -1, %d, -1, 1, 1, 1", $db->escapeString($subject), $db->escapeString($cleanName), $totalFiles, $groupID, $db->escapeString($relguid), $db->escapeString($posteddate), $db->escapeString($poster), $db->escapeString($totalsize), ($page->site->checkpasswordedrar == "1" ? -1 : 0), $category));
				} else {
					$relID = $db->queryInsert(sprintf("INSERT INTO releases (name, searchname, totalpart, groupid, adddate, guid, rageid, postdate, fromname, size, passwordstatus, haspreview, categoryid, nfostatus, nzbstatus, categorized) VALUES (%s, %s, %d, %d, NOW(), %s, -1, %s, %s, %s, %d, -1, %d, -1, 1, 1", $db->escapeString($subject), $db->escapeString($cleanName), $totalFiles, $groupID, $db->escapeString($relguid), $db->escapeString($posteddate), $db->escapeString($poster), $db->escapeString($totalsize), ($page->site->checkpasswordedrar == "1" ? -1 : 0), $category));
				}

				if (isset($relid) && $relid == false) {
					echo "\n\033[38;5;" . $color_skipped . "mSkipping " . $subject . ", it already exists in your database.\033[0m\n";
					@unlink($nzbFile);
					flush();
					$importfailed = true;
					continue;
				}
				if ($nzb->copyNZBforImport($relguid, $nzba)) {
					$message = "Imported NZB successfully. Subject: " . $firstname['0'] . "\n";
					if (!empty($argc)) {
						echo ($message . "\n");
						flush();
					} else {
						$retval.= $message . "<br />";
					}
				} else {
					$db->queryExec(sprintf("DELETE FROM releases WHERE guid = %s AND postdate = %s AND size = %d", $db->escapeString($relguid), $db->escapeString($posteddate), $db->escapeString($totalsize)));
					echo "Failed copying NZB, deleting release from DB.\n";
					$importfailed = true;
				}
				$nzbCount++;
				@unlink($nzbFile);
			}
		}
	}
	$seconds = strtotime(date('Y-m-d H:i:s')) - strtotime($start);
	$retval .= 'Processed ' . $nzbCount . ' nzbs in ' . $seconds . ' second(s)';

	if (!empty($argc)) {
		echo $retval . "\n";
		die();
	}
	$page->smarty->assign('output', $retval);
}

$page->title = "Import Nzbs";
$page->content = $page->smarty->fetch('nzb-import.tpl');
$page->render();
