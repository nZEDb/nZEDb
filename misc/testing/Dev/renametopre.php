<?php
require_once dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;

/*
 *
 * This was added because I starting writing this before
 * all of the regexes were converted to by group in ReleaseCleaning.php
 * and I do not want to convert these regexes to run per group.
 * ReleaseCleaning.php is where the regexes should go
 * so that all new releases can be effected by them
 * instead of having to run this script to rename after the
 * release has been created
 *
 */
$c = new ColorCLI();
if (!(isset($argv[1]) && ($argv[1] == "all" || $argv[1] == "full" || $argv[1] == "preid" || is_numeric($argv[1])))) {
	exit($c->error(
		"\nThis script will attempt to rename releases using regexes first from ReleaseCleaning.php and then from this file.\n"
		. "An optional last argument, show, will display the release name changes.\n\n"
		. "php $argv[0] full                    ...: To process all releases not previously renamed.\n"
		. "php $argv[0] 2                       ...: To process all releases added in the previous 2 hours not previously renamed.\n"
		. "php $argv[0] all                     ...: To process all releases.\n"
		. "php $argv[0] full 155                ...: To process all releases in group_id 155 not previously renamed.\n"
		. "php $argv[0] all 155                 ...: To process all releases in group_id 155.\n"
		. "php $argv[0] all '(155, 140)'        ...: To process all releases in group_ids 155 and 140.\n"
		. "php $argv[0] preid                   ...: To process all releases where not matched to predb.\n"
	));
}
preName($argv, $argc);

function preName($argv, $argc)
{
	$pdo = new Settings();
	$groups = new Groups();
	$category = new Categorize();
	$internal = $external = $pre = $none = 0;
	$show = 2;
	if ($argv[$argc - 1] === 'show') {
		$show = 1;
	} else if ($argv[$argc - 1] === 'bad') {
		$show = 3;
	}
	$counter = 0;
	$c = new ColorCLI();
	$full = $all = $usepre = false;
	$what = $where = $why = '';
	if ($argv[1] === 'full') {
		$full = true;
	} else if ($argv[1] === 'all') {
		$all = true;
	} else if ($argv[1] === 'preid') {
		$usepre = true;
	} else if (is_numeric($argv[1])) {
		$what = ' AND adddate > NOW() - INTERVAL ' . $argv[1] . ' HOUR';
	}
	if ($usepre === true) {
		$where = '';
		$why = ' WHERE preid = 0 AND nzbstatus = 1';
	} else if (isset($argv[1]) && is_numeric($argv[1])) {
		$where = '';
		$why = ' WHERE nzbstatus = 1 AND isrenamed = 0';
	} else if (isset($argv[2]) && is_numeric($argv[2]) && $full === true) {
		$where = ' AND group_id = ' . $argv[2];
		$why = ' WHERE nzbstatus = 1 AND isrenamed = 0';
	} else if (isset($argv[2]) && preg_match('/\([\d, ]+\)/', $argv[2]) && $full === true) {
		$where = ' AND group_id IN ' . $argv[2];
		$why = ' WHERE nzbstatus = 1 AND isrenamed = 0';
	} else if (isset($argv[2]) && preg_match('/\([\d, ]+\)/', $argv[2]) && $all === true) {
		$where = ' AND group_id IN ' . $argv[2];
		$why = ' WHERE nzbstatus = 1';
	} else if (isset($argv[2]) && is_numeric($argv[2]) && $all === true) {
		$where = ' AND group_id = ' . $argv[2];
		$why = ' WHERE nzbstatus = 1 and preid = 0';
	} else if (isset($argv[2]) && is_numeric($argv[2])) {
		$where = ' AND group_id = ' . $argv[2];
		$why = ' WHERE nzbstatus = 1 AND isrenamed = 0';
	} else if ($full === true) {
		$why = ' WHERE nzbstatus = 1 AND (isrenamed = 0 OR categoryid between 7000 AND 7999)';
	} else if ($all === true) {
		$why = ' WHERE nzbstatus = 1';
	} else {
		$why = ' WHERE 1=1';
	}
	resetSearchnames();
	echo $c->header(
		"SELECT id, name, searchname, fromname, size, group_id, categoryid FROM releases" . $why . $what .
		$where . ";\n"
	);
	$res = $pdo->queryDirect("SELECT id, name, searchname, fromname, size, group_id, categoryid FROM releases" . $why . $what . $where);
	$total = $res->rowCount();
	if ($total > 0) {
		$consoletools = new ConsoleTools();
		foreach ($res as $row) {
			$groupname = $groups->getByNameByID($row['group_id']);
			$cleanerName = releaseCleaner($row['name'], $row['group_id'], $row['fromname'], $row['size'], $groupname, $usepre);
			$preid = 0;
			$predb = $predbfile = $increment = false;
			if (!is_array($cleanerName)) {
				$cleanName = trim($cleanerName);
				$propername = $increment = true;
				if ($cleanName != '' && $cleanerName != false) {
					$run = $pdo->queryOneRow("SELECT id FROM predb WHERE title = " . $pdo->escapeString($cleanName));
					if (isset($run['id'])) {
						$preid = $run['id'];
						$predb = true;
					}
				}
			} else {
				$cleanName = trim($cleanerName["cleansubject"]);
				$propername = $cleanerName["properlynamed"];
				if (isset($cleanerName["increment"])) {
					$increment = $cleanerName["increment"];
				}
				if (isset($cleanerName["predb"])) {
					$preid = $cleanerName["predb"];
					$predb = true;
				}
			}
			if ($cleanName != '') {
				$match = '';
				if (preg_match('/alt\.binaries\.e\-?book(\.[a-z]+)?/', $groupname)) {
					if (preg_match('/^[0-9]{1,6}-[0-9]{1,6}-[0-9]{1,6}$/', $cleanName, $match)) {
						$rf = new ReleaseFiles();
						$files = $rf->get($row['id']);
						foreach ($files as $f) {
							if (preg_match(
								'/^(?P<title>.+?)(\\[\w\[\]\(\). -]+)?\.(pdf|htm(l)?|epub|mobi|azw|tif|doc(x)?|lit|txt|rtf|opf|fb2|prc|djvu|cb[rz])/', $f["name"],
								$match
							)
							) {
								$cleanName = $match['title'];
								break;
							}
						}
					}
				}
					//try to match clean name against predb filename
					$prefile = $pdo->queryOneRow("SELECT id, title FROM predb WHERE filename = " . $pdo->escapeString($cleanName));
					if (isset($prefile['id'])) {
						$preid = $prefile['id'];
						$cleanName = $prefile['title'];
						$predbfile = true;
						$propername = true;
					}
				if ($cleanName != $row['name'] && $cleanName != $row['searchname']) {
					if (strlen(utf8_decode($cleanName)) <= 3) {
					} else {
						$determinedcat = $category->determineCategory($cleanName, $row["group_id"]);
						if ($propername == true) {
							$run = $pdo->queryExec(
								sprintf(
									"UPDATE releases SET rageid = -1, seriesfull = NULL, season = NULL, episode = NULL, tvtitle = NULL, tvairdate = NULL, imdbid = NULL, musicinfoid = NULL, consoleinfoid = NULL, bookinfoid = NULL, anidbid = NULL, "
									. "iscategorized = 1, isrenamed = 1, searchname = %s, categoryid = %d, preid = " . $preid . " WHERE id = %d", $pdo->escapeString($cleanName), $determinedcat, $row['id']
								)
							);
						} else {
							$run = $pdo->queryExec(
								sprintf(
									"UPDATE releases SET rageid = -1, seriesfull = NULL, season = NULL, episode = NULL, tvtitle = NULL, tvairdate = NULL, imdbid = NULL, musicinfoid = NULL, consoleinfoid = NULL, bookinfoid = NULL, anidbid = NULL,  "
									. "iscategorized = 1, searchname = %s, categoryid = %d, preid = " . $preid . " WHERE id = %d", $pdo->escapeString($cleanName), $determinedcat, $row['id']
								)
							);
						}
						if ($increment === true) {
							$status = "renametopre Match";
							$internal++;
						} else if ($predb === true) {
							$status = "PreDB: Match";
							$pre++;
						} else if ($predbfile === true) {
							$status = "PreDB: Filename Match";
							$pre++;
						} else if ($propername === true) {
							$status = "ReleaseCleaner Match";
							$external++;
						}
						if ($show === 1) {
							$oldcatname = $category->getNameByID($row["categoryid"]);
							$newcatname = $category->getNameByID($determinedcat);

							NameFixer::echoChangedReleaseName(array(
									'new_name'     => $cleanName,
									'old_name'     => $row["searchname"],
									'new_category' => $newcatname,
									'old_category' => $oldcatname,
									'group'        => $groupname,
									'release_id'   => $row["id"],
									'method'       => 'misc/testing/Dev/renametopre.php'
								)
							);
						}
					}
				} else if ($show === 3 && preg_match('/^\[?\d*\].+?yEnc/i', $row['name'])) {
					echo $c->primary($row['name']);
				}
			}
			if ($cleanName == $row['name']) {
				$pdo->queryExec(sprintf("UPDATE releases SET isrenamed = 1, iscategorized = 1 WHERE id = %d", $row['id']));
			}
			if ($show === 2 && $usepre === false) {
				$consoletools->overWritePrimary("Renamed Releases:  [Internal=" . number_format($internal) . "][External=" . number_format($external) . "][Predb=" . number_format($pre) . "] " . $consoletools->percentString(++$counter, $total));
			} else if ($show === 2 && $usepre === true) {
				$consoletools->overWritePrimary("Renamed Releases:  [" . number_format($pre) . "] " . $consoletools->percentString(++$counter, $total));
			}
		}
	}
	echo $c->header("\n" . number_format($pre) . " renamed using preDB Match\n" . number_format($external) . " renamed using ReleaseCleaning.php\n" . number_format($internal) . " using renametopre.php\nout of " . number_format($total) . " releases.\n");
	if (isset($argv[1]) && is_numeric($argv[1]) && !isset($argv[2])) {
		echo $c->header("Categorizing all releases using searchname from the last ${argv[1]} hours. This can take a while, be patient.");
	} else if (isset($argv[1]) && $argv[1] !== "all" && isset($argv[2]) && !is_numeric($argv[2]) && !preg_match('/\([\d, ]+\)/', $argv[2])) {
		echo $c->header("Categorizing all non-categorized releases in other->misc using searchname. This can take a while, be patient.");
	} else if (isset($argv[1]) && isset($argv[2]) && (is_numeric($argv[2]) || preg_match('/\([\d, ]+\)/', $argv[2]))) {
		echo $c->header("Categorizing all non-categorized releases in ${argv[2]} using searchname. This can take a while, be patient.");
	} else {
		echo $c->header("Categorizing all releases using searchname. This can take a while, be patient.");
	}
	$timestart = TIME();
	if (isset($argv[1]) && is_numeric($argv[1])) {
		$relcount = categorizeRelease("searchname", "WHERE (iscategorized = 0 OR categoryID = 7010) AND adddate > NOW() - INTERVAL " . $argv[1] . " HOUR", true);
	} else if (isset($argv[2]) && preg_match('/\([\d, ]+\)/', $argv[2]) && $full === true) {
		$relcount = categorizeRelease("searchname", str_replace(" AND", "WHERE", $where) . " AND iscategorized = 0 ", true);
	} else if (isset($argv[2]) && preg_match('/\([\d, ]+\)/', $argv[2]) && $all === true) {
		$relcount = categorizeRelease("searchname", str_replace(" AND", "WHERE", $where), true);
	} else if (isset($argv[2]) && is_numeric($argv[2]) && $argv[1] == "full") {
		$relcount = categorizeRelease("searchname", str_replace(" AND", "WHERE", $where) . " AND iscategorized = 0 ", true);
	} else if (isset($argv[2]) && is_numeric($argv[2]) && $argv[1] == "all") {
		$relcount = categorizeRelease("searchname", str_replace(" AND", "WHERE", $where), true);
	} else if (isset($argv[1]) && $argv[1] == "full") {
		$relcount = categorizeRelease("searchname", "WHERE categoryID = 7010 OR iscategorized = 0", true);
	} else if (isset($argv[1]) && $argv[1] == "all") {
		$relcount = categorizeRelease("searchname", "", true);
	} else if (isset($argv[1]) && $argv[1] == "preid") {
		$relcount = categorizeRelease("searchname", "WHERE preid = 0 AND nzbstatus = 1", true);
	} else {
		$relcount = categorizeRelease("searchname", "WHERE (iscategorized = 0 OR categoryID = 7010) AND adddate > NOW() - INTERVAL " . $argv[1] . " HOUR", true);
	}
	$consoletools = new ConsoleTools();
	$time = $consoletools->convertTime(TIME() - $timestart);
	echo $c->header("Finished categorizing " . number_format($relcount) . " releases in " . $time . " seconds, using the usenet subject.\n");
	/*
	  if (isset($argv[1]) && $argv[1] !== "all") {
	  echo $c->header("Categorizing all non-categorized releases in other->misc using searchname. This can take a while, be patient.");
	  $timestart1 = TIME();
	  if (isset($argv[2]) && is_numeric($argv[2])) {
	  $relcount = categorizeRelease("name", str_replace(" AND", "WHERE", $where), true);
	  } else {
	  $relcount = categorizeRelease("searchname", "WHERE (iscategorized = 0 OR categoryID = 7010) AND adddate > NOW() - INTERVAL " . $argv[1] . " HOUR", true);
	  }
	  $consoletools1 = new ConsoleTools();
	  $time1 = $consoletools1->convertTime(TIME() - $timestart1);
	  echo $c->header("Finished categorizing " . number_format($relcount) . " releases in " . $time1 . " seconds, using the searchname.\n");
	  }
	 */
	resetSearchnames();
}

function resetSearchnames()
{
	$pdo = new Settings();
	$c = new ColorCLI();
	echo $c->header("Resetting blank searchnames.");
	$bad = $pdo->queryDirect(
		"UPDATE releases SET rageid = -1, seriesfull = NULL, season = NULL, episode = NULL, tvtitle = NULL, tvairdate = NULL, imdbid = NULL, musicinfoid = NULL, consoleinfoid = NULL, bookinfoid = NULL, anidbid = NULL, "
		. "preid = 0, searchname = name, isrenamed = 0, iscategorized = 0 WHERE searchname = ''"
	);
	$tot = $bad->rowCount();
	if ($tot > 0) {
		echo $c->primary(number_format($tot) . " Releases had no searchname.");
	}
	echo $c->header("Resetting searchnames that are 8 characters or less.");
	$run = $pdo->queryDirect(
		"UPDATE releases SET rageid = -1, seriesfull = NULL, season = NULL, episode = NULL, tvtitle = NULL, tvairdate = NULL, imdbid = NULL, musicinfoid = NULL, consoleinfoid = NULL, bookinfoid = NULL, anidbid = NULL, "
		. "preid = 0, searchname = name, isrenamed = 0, iscategorized = 0 WHERE LENGTH(searchname) <= 8 AND LENGTH(name) > 8"
	);
	$total = $run->rowCount();
	if ($total > 0) {
		echo $c->primary(number_format($total) . " Releases had searchnames that were 8 characters or less.");
	}
}

// Categorizes releases.
// $type = name or searchname
// Returns the quantity of categorized releases.
function categorizeRelease($type, $where, $echooutput = false)
{
	$pdo = new Settings();
	$cat = new Categorize();
	$consoletools = new consoleTools();
	$relcount = 0;
	$c = new ColorCLI();
	echo $c->primary("SELECT id, " . $type . ", group_id FROM releases " . $where);
	$resrel = $pdo->queryDirect("SELECT id, " . $type . ", group_id FROM releases " . $where);
	$total = $resrel->rowCount();
	if ($total > 0) {
		foreach ($resrel as $rowrel) {
			$catId = $cat->determineCategory($rowrel[$type], $rowrel['group_id']);
			$pdo->queryExec(sprintf("UPDATE releases SET iscategorized = 1, categoryid = %d WHERE id = %d", $catId, $rowrel['id']));
			$relcount++;
			if ($echooutput) {
				$consoletools->overWritePrimary("Categorizing: " . $consoletools->percentString($relcount, $total));
			}
		}
	}
	if ($echooutput !== false && $relcount > 0) {
		echo "\n";
	}
	return $relcount;
}

function releaseCleaner($subject, $group_id, $fromName, $size, $groupname, $usepre)
{
	$groups = new Groups();
	$match = '';
	$groupName = $groups->getByNameByID($group_id);
	$releaseCleaning = new ReleaseCleaning();
	$cleanerName = $releaseCleaning->releaseCleaner($subject, $fromName, $size, $groupname, $usepre);
	if (!is_array($cleanerName) && $cleanerName != false) {
		return array("cleansubject" => $cleanerName, "properlynamed" => true, "increment" => false);
	} else {
		return $cleanerName;
	}
	if ($usepre === true) {
		return false;
	}
	if ($groupName == "alt.binaries.classic.tv.shows") {
		if (preg_match('/^(?P<title>.+\d+x\d+.+)[ _-]{0,3}\[\d+\/\d+\][ _-]{0,3}("|#34;).+("|#34;)[ _-]{0,3}(yEnc|rar|par2)$/i', $subject, $match)) {
			$cleanerName = preg_replace('/^REQ[ _-]{0,3}/i', '', preg_replace('/\.+$/', '', trim($match['title'])));
			if (!empty($cleanerName)) {
				return $cleanerName;
			}
		} //YANCY DERRINGER 109 Memo to a Firing Squad [1 of 13] "YANCY DERRINGER 109 Memo to a Firing Squad.vol127+73.par2" yEnc
		else if (preg_match('/^(?P<title>Yancy.+) \[\d+ of \d+\].+?("|#34;).+("|#34;) yEnc/i', $subject, $match)) {
			$cleanerName = $match['title'];
			if (!empty($cleanerName)) {
				return $cleanerName;
			}
		} //"Yancy Derringer - E-27-Duel At The Oaks.part01.rar" yEnc
		else if (preg_match('/^("|#34;)(?P<title>Yancy.+?)\.(par|zip|rar|nfo|txt).+?("|#34;) yEnc/i', $subject, $match)) {
			$cleanerName = $match['title'];
			if (!empty($cleanerName)) {
				return $cleanerName;
			}
		} //[Gunsmoke Season 16 Episode 02  Avi Xvid][00/24] yEnc
		else if (preg_match('/^7?\[(?P<title>.+) ?(Avi Xvid)?\]\[\d+\/\d+\] yEnc/i', $subject, $match)) {
			$cleanerName = $match['title'];
			if (!empty($cleanerName)) {
				return $cleanerName;
			}
		} //(Gunsmoke Season 5 Episode 18 - 10 par files) [00/17] - "Gunsmoke S05E18 - Big Tom.avi.nzb" yEnc
		else if (preg_match('/\((?P<title>.+)\) \[\d+\/\d+\][ _-]{0,3}("|#34;).+("|#34;) yEnc/i', $subject, $match)) {
			$cleanerName = $match['title'];
			if (!empty($cleanerName)) {
				return $cleanerName;
			}
		}
	}
	// [39975]-[FULL]-[#a.b.foreign@EFNet]-[ The.Cape.S01E10.FiNAL.FRENCH.LD.DVDRiP.XViD-EPZ ]-[01/34] - "epz-the.cape.s01e10-sample.avi" yEnc
	// [39975]-[FULL]-[#a.b.foreign@EFNet]-[ The.Cape.S01E10.FiNAL.FRENCH.LD.DVDRiP.XViD-EPZ ]-[01/34] - #34;epz-the.cape.s01e10-sample.avi#34; yEnc
	// [39975]-[#a.b.foreign@EFNet]-[FULL]-[ The.Cape.S01E10.FiNAL.FRENCH.LD.DVDRiP.XViD-EPZ ]-[01/34] - "epz-the.cape.s01e10-sample.avi" yEnc
	// [39975]-[#a.b.foreign@EFNet]-[FULL]-[ The.Cape.S01E10.FiNAL.FRENCH.LD.DVDRiP.XViD-EPZ ]-[01/34] - #34;epz-the.cape.s01e10-sample.avi#34; yEnc
	// [39975]-[FULL]-[#a.b.foreign@EFNet]-[ The.Cape.S01E10.FiNAL.FRENCH.LD.DVDRiP.XViD-EPZ ]-[REPOST]-[01/34] - "epz-the.cape.s01e10-sample.avi" yEnc
	// [39975]-[FULL]-[#a.b.foreign@EFNet]-[ The.Cape.S01E10.FiNAL.FRENCH.LD.DVDRiP.XViD-EPZ ]-[REPOST]-[01/34] - #34;epz-the.cape.s01e10-sample.avi#34; yEnc
	// [39975]-[#a.b.foreign@EFNet]-[FULL]-[ The.Cape.S01E10.FiNAL.FRENCH.LD.DVDRiP.XViD-EPZ ]-[REPOST]-[01/34] - "epz-the.cape.s01e10-sample.avi" yEnc
	// [39975]-[#a.b.foreign@EFNet]-[FULL]-[ The.Cape.S01E10.FiNAL.FRENCH.LD.DVDRiP.XViD-EPZ ]-[REPOST]-[01/34] - #34;epz-the.cape.s01e10-sample.avi#34; yEnc
	// [37090]-[#a.b.foreign@EFNet]-[ Alarm.fuer.Cobra.11.S30E06.German.SATRip.XviD-ITG ]-[04/33] - "itg-c11-s30e06-sample-sample.vol3+2.par2" yEnc
	// [270512]-[FULL]-[Koh.Lanta.La.Revanche.Des.Heros.Cambodge.E08.FRENCH.720p.HDTV.x264-TTHD] [01/75] - "kohlanta.cambodge.e08.720p.hdtv.x264-sample.mkv" yEnc
	// This matches the most AND the matches are usually predb matches, run first
	// 1130678 out of 5751006
	if (preg_match('/^\[?\d*\][ _-]{0,3}(\[(reup|full|repost.+?|part|re-repost|xtr|sample)(\])?[ _-]{0,3}\[[- #@\.\w]+\][ _-]{0,3}|\[[- #@\.\w]+\][ _-]{0,3}\[(reup|full|repost.+?|part|re-repost|xtr|sample)(\])?[ _-]{0,3}|\[.+?efnet\][ _-]{0,3}|\[(reup|full|repost.+?|part|re-repost|xtr|sample)(\])?[ _-]{0,3})(\[(FULL|REPOST)\])?[ _-]{0,3}(\[ )?(\[)? ?(\/sz\/)?(F: - )?(?P<title>[- _!@\.\'\w\(\)~]{10,})[ _-]{0,3}(\])?[ _-]{0,3}(\[)?[ _-]{0,3}(REPOST|REPACK|SCENE|EXTRA PARS|REAL)?[ _-]{0,3}(\])?[ _-]{0,3}(\[\d+[-\/~]\d+\])?[ _-]{0,3}("|#34;).+("|#34;)[ _-]{0,3}[yEnc]{0,4}/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	}
	// [2410]-[abgx.net]-[My_Weight_Loss_Coach_USA_NDS-CNBS]-[2/4]-"cnbs-mwlc.par2" yEnc
	// [40903]-[#altbin@EFNet]-[FULL]-[ Wishmaster.2.1999.DVDRip.Xvid.iNT-420RipZ ]-[01~68] - wishmaster2-xvid-420ripz.nfo yEnc
	// [24024]-[#ab@EFNet]-[FULL]-[Americas.Army.Rise.of.a.Soldier.PAL.XBOX-PaL]-po0p!-[00/43] - "24024.nzb" yEnc
	// [24144]-[#ab@EFNet]-[FULL]-[SNK_vs_Capcom_SVC_Chaos_Pal_Xbox-RIOT]-po0p!-(01/24) "r-snkcap.nfo" - 696.33 MB - yEnc
	// [4522]-[abgx@EFNET]-[FULL]-[Barbie_As_The_Island_Princess_PAL_MULTi6_Wii-BAHAMUT]-[101]-["b-barbie.nfo"] yEnc
	else if (preg_match('/^\[\d+\][ _-]{0,3}(.+?)[ _-]{0,3}(\[(reup|full|repost.+?|part|re-repost|xtr|sample)\])?[ _-]{0,3}\[ ?(?P<title>.+) ?\][ _-]{0,3}.+?[ _-]{0,3}([\[\(]\d+[~\/]\d+[\)\]]|\[\d+\])[ _-]{0,3}("|#34;)?.+("|#34;)?[ _-]{0,3}yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // [27423]-[#altbin@EFNet]-[FULL]-[Star.Trek-TNG.S04E03.iNTERNAL.DVDRip.XviD-DVDiSO]-[01-36] - Star.Trek-TNG.S04E03.iNTERNAL.DVDRip.XviD-DVDiSO.nfo yEnc
	else if (preg_match('/^\[\d+\][ _-]{0,3}\[.+\][ _-]{0,3}(\[(reup|full|repost.+?|part|re-repost|xtr|sample)\])[ _-]{0,3}\[(?P<title>.+)\][ _-]{0,3}\[\d+[\/-]\d+\][ _-]{0,3}.+yEnc/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // [3533] - #MovieGen - Extract.BDRip.XviD-DiAMOND [01/66] - #34;dmd-extract-subs.sfv#34; yEnc
	else if (preg_match('/^\[\d+\][ _-]{0,3}#(Movie|TV)gen[ _-]{0,3}(?P<title>.+)[ _-]{0,3}\[\d+\/\d+\][ _-]{0,3}("|#34;).+("|#34;) yEnc/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	}
	// [2409]Saarre-The_Dunes_EP-(NS2009027)-WEB-2009-SSR - "00-saarre-the_dunes_ep-(ns2009027)-web-2009.m3u" yEnc
	// [1106]Darren_Tate_Vs_Jono_Grant-Let_The_Light_Shine_In_2010-WEB-2010-TSP "03-darren_tate_vs_jono_grant-let_the_light_shine_in_2010__corderoy_vocal_mix.mp3" - yEnc
	// [17326] ATV_Offroad_Fury_USA_PSP-NONEEDPDX [01/33] - "atv_offroad_fury_usa_psp-noneedpdx.par2" yEnc
	else if (preg_match('/^\[\d+\][ _-]{0,3}(?P<title>.+)( \[\d+\/\d+\] )?[ _-]{0,3}("|#34;).+?(m3u|jpg|mp3|par2|nfo|nzb|rar|zip)("|#34;)[ _-]{0,3}yEnc/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // [21182] - (#a.b.g.x@EFnet) - Tom_Clancys_Rainbow_Six_3_Black_Arrow_PAL_MULTI5_XBOXDVD-MRN - mrn-rsba.nfo yEnc
	else if (preg_match('/^\[\d+\][ _-]{0,3}\(#a.b.g.x@EFnet\)[ _-]{0,3}(?P<title>.+) - .+?yEnc/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // []OOO[]  ASST NEW MTLS 26 MAR -  [138/158] - "Spintronics - Materials, Applications AND Devices - G. Lombardi, G. Bianchi (Nova, 2009) WW.pdf" yEnc
	else if (preg_match('/\[\]OOO\[\][ _-]{0,3}ASST.+?[ _-]{0,3}\[\d+\/\d+\][ _-]{0,3}("|#34;)(?P<title>.+)\.(pdf|doc|lit|mobi|txt|epub|chm|djvu|rar|zip)("|#34;) yEnc/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // [] - "3749_Mind_Your_Language_Learn_English_EUR_MULTi5_NDS-EXiMiUS.rar" yEnc
	else if (preg_match('/^\[\d*\][ _-]{0,3}("|#34;)(?P<title>.+)\.(rar|par2|zip)("|#34;) yEnc/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // <kere.ws> - MViD - 1341305405 - Metallica.Orion.Music.Festival.2012.AC3.HDTV.720p.x264-TSCC - [01/89] - "Metallica.Orion.Music.Festival.2012.AC3.HDTV.720p.x264-TSCC-thumb.jpg" yEnc
	else if (preg_match('/^<kere\.ws>[ _-]{0,3}\w+(-\w+)?[ _-]{0,3}\d+[ _-]{0,3}(?P<title>.+) - \[\d+\/\d+\][ _-]{0,3}("|#34;).+?("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	}
	// <TOWN><www.town.ag > <partner of www.ssl-news.info > [06/13] - "Grojband.S01E23E24.HDTV.x264-W4F.part04.rar" - 129,94 MB yEnc
	// [ TOWN ]-[ www.town.ag ]-[ partner of www.ssl-news.info ] [01/18] - "The.Big.Bang.Theory.S07E04.720p.HDTV.X264-DIMENSION.par2" - 618,80 MB yEnc
	else if (preg_match('/^[ <\[]{0,2}TOWN[ >\]]{0,2}[ _-]{0,3}[ <\[]{0,2}www\.town\.ag[ >\]]{0,2}[ _-]{0,3}[ <\[]{0,2}partner of www.ssl-news\.info[ >\]]{0,2}[ _-]{0,3}\[\d+\/\d+\][ _-]{0,3}("|#34;)(?P<title>.+)\.(par|vol|rar|nfo).*?("|#34;).+?yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // <kere.ws> - TV - 1338205816 - Der.letzte.Bulle.S03E12.Ich.sags.nicht.weiter.German.DVDRip.XviD-iNTENTiON - [01/43] - "itn-der.letzte.bulle.s03e12.xvid-sample-sample.par2" yEnc (1/1)
	else if (preg_match('/^<kere\.ws>[ _-]{0,3}(TV|Filme)[ _-]{0,3}\d+[ _-]{0,3}(?P<title>.+)[ _-]{0,3}\[\d+\/\d+\][ _-]{0,3}("|#34;).+?("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // <TOWN><www.town.ag > <partner of www.ssl-news.info > Once.Upon.a.Time.S02E01.720p.HDTV.X264-DIMENSION  [01/25] - "Once.Upon.a.Time.S02E01.720p.HDTV.X264-DIMENSION.par2" - 1,03 GB - yEnc
	else if (preg_match('/^<TOWN><www\.town\.ag > <partner of www\.ssl-news\.info > (?P<title>.+) \[\d+\/\d+\][ _-]{0,3}("|#34;).+?("|#34;).+?yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	}
	// (www.Thunder-News.org) >Zwei.Singles.im.Doppelbett.S01E06.Feiern.oder.Fischen.GERMAN.WS.dTV.XviD-FKKTV< <Sponsored by AstiNews> - (01/26) - "fkktv-almost_perfect-s01e06-sample.par2" yEnc
	// (www.Thunder-News.org)>Yu-Gi-Oh 1x29 - Duel Indentity (Part 1)<<Sponsored by Secretusenet> - [01/10] - "Yu-Gi-Oh 1x29 - Duel Indentity (Part 1).par2" yEnc (1/1)
	else if (preg_match('/^\(www\.Thunder-News\.org\) ?>(?P<title>.+)< ?<Sponsored.+?>[ _-]{0,3}(\(\d+\/\d+\)|\[\d+\/\d+\])[ _-]{0,3}("|#34;).+?("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // >ghost-of-usenet.org<< 360.Grad.Geo.Reportage.-.Die.letzten.Kamelkarawanen.der.Sahara.GERMAN.DOKU.WS.720p.HDTV.x264-MiSFiTS >>www.SSL-News.info> -  "misfits-kamelkarawanen.r14" yEnc
	else if (preg_match('/^>ghost-of-usenet\.org<< ?(?P<title>.+) ?>>www.+>[ _-]{0,3}("|#34;)?.+("|#34;)? ?yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // [ nEwZ[NZB].iNFO ] - [ The.Half.Hour.S02E11.Lil.Rel.Howery.HDTV.x264-YesTV ] - File [13/19]: "the.half.hour.0211-yestv.r10" yEnc
	else if (preg_match('/^\[ nEwZ\[NZB\]\.iNFO \][ _-]{0,3}\[ (?P<title>.+) \][ _-]{0,3}File \[\d+\/\d+\]: ("|#34;).+("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // Alias.4x05.Benvenuti.Al.Liberty.Village.ITA-ENG.720p.DLMux.h264-NovaRip [01/40] - "alias.4x05.ita-eng.720p.dlmux.h264-novarip.nfo" yEnc
	else if (preg_match('/^(?P<title>.+Novarip) \[\d+\/\d+\][ _-]{0,3}("|#34;).+?("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // (1/9) - [Lords-of-Usenet] <<Partner of SSL-News.info>> presents Breaking.In.S02E13.Episode.XIII.GERMAN.Dubbed.DVDRiP.XviD-idTV -"19104.par2" - 179,52 MB - yEnc
	else if (preg_match('/^\(\d+\/\d+\)[ _-]{0,3}\[Lords-of-Usenet\] (<<|\(\()(Partner|Sponsor).+(>>|\)\)) presents (?P<title>.+)[ _-]{0,3}("|#34;).+("|#34;).+yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // (????) [02656/43619] - "2 Schulerinnen-Wer ist d.Klassenbeste beim Wixen.exe" yEnc
	else if (preg_match('/^\(\?+\) \[\d+\/\d+\][ _-]{0,3}("|#34;)(?P<title>.+)("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	}
	// (www.Thunder-News.org) >Die.Schatzsucher.Helden.unter.Tage.S01E07.Wintereinbruch.GERMAN.DOKU.WS.SATRip.XviD-TVP< <Sponsored by AstiNews> -  - "tvp-coal-s01e07-xvid.vol15+12.par2" yEnc (1/25)
	// (www.Thunder-News.org) >The.Borgias.S02E08.HDTV.x264-ASAP< <Sponsored by Secretusenet> -  "the.borgias.s02e08.hdtv.x264-asap.nfo" yEnc
	else if (preg_match('/^\(www\.Thunder-News\.org\) ?>(?P<title>.+)< <Sponsored.+?>[ -]{0,7}("|#34;).+("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	}
	// [foreign]-[ PowNews.S02E87.DUTCH.WS.PDTV.XviD-iFH ] [01/24] - #34;PowNews.S02E87.DUTCH.WS.PDTV.XviD-iFH.par2#34; yEnc
	// [foreign]-[ El.Barco.S03E13.SPANiSH.HDTV.x264-FCC ] [01/44] - "El.Barco.S03E13.SPANiSH.HDTV.x264-FCC.par2" yEnc
	else if (preg_match('/^\[foreign\][ _-]{0,3}\[ (?P<title>.+) \][ -]?\[\d+\/\d+\][ _-]{0,3}("|#34;).+("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // (www.Thunder-News.org) )Aus.Versehen.gluecklich.S01E02.Alles.ueber.Zack.GERMAN.DUBBED.WS.DVDRip.XviD-TVP( (Sponsored by AstiNews) - (03/20) - #34;tvp-gluecklich-s01e02-xvid-sample.avi#34; yEnc
	else if (preg_match('/^\(www\.Thunder-News\.org\) ?\)(?P<title>.+)\( \(Sponsored.+\)[ _-]{0,3}\(\d+\/\d+\)[ _-]{0,3}("|#34;).+("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // <<<usenet-space-cowboys.info>>> THOR <<<Powered by https://secretusenet.com>< "Trucker.in.gefaehrlicher.Mission.S01E01.Abenteuer.Himalaja.GERMAN.DUBBED.DOKU.WS.HDTVRip.XviD-TVP_usenet-space-cowbys.info.avi" >< 03/15 (404.96 MB) >< 11.21 MB > yEnc
	else if (preg_match('/^<<<usenet-space-cowboys\.info>>>.+>< ("|#34;)(?P<title>.+)_usenet-space-cowbo?ys.+("|#34;) >< \d+\/\d+ \(.+\) ><.+> yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // <kere.ws/illuminatenboard.org> - ID - 1291273600 - Schluessel.zur.Vergangenheit.Das.Bermudadreieck.GERMAN.DOKU.720p.HDTV.x264-TVP [01/30] - "1291273600.par2" yEnc (1/1) (1/1)
	else if (preg_match('/^<kere\.ws\/illuminatenboard\.org>[ _-]{0,3}ID[ _-]{0,3}\d+[ _-]{0,3}(?P<title>.+) \[\d+\/\d+\][ _-]{0,3}("|#34;).+("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // Korn.Live.On.The.Other.Side.2006.Blu-ray.1080p.AVC.DTS-HD.5.1-TrollHD [der.Angler fuer usenet-4all.info]-[powered by U4all]-(01/84) "Korn.Live.On.The.Other.Side.2006.Blu-ray.1080p.AVC.DTS-HD.5.1-TrollHD.par2" yEnc
	else if (preg_match('/^(?P<title>.+)\[.+usenet-4all\.info\]-\[.+\]-\(\d+\/\d+\) ("|#34;).+("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} //[Lords-of-Usenet.org] <Sponsored by SSL-News.info> proudly presents: [Lords-of-Usenet.org]_<Sponsored_by_SSL-News_info>_Proudly_presents_Rescue.Me.S05E17.German.Dubbed.DVDRip.XviD-ITG [01/28] - "itg-rm-s05e17.nfo" yEnc
	else if (preg_match('/^\[Lords-of-Usenet\.org\][ _-]{0,3}<Sponsored.+>[ _-]{0,3}proudly.+_Proudly_presents_(?P<title>.+)[ _-]{0,3}\[\d+\/\d+\][ _-]{0,3}("|#34;).+("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match[1];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // [Lords-of-Usenet.org] <Sponsored by SSL-News.info> proudly presents: V.2009.S01E10.German.Dubbed.BDRip.XviD-MiRAMAX [01/28] - "mm-v-s01e10.nfo" yEnc
	else if (preg_match('/^\[Lords-of-Usenet\.org\][ _]<Sponsored.+> proudly presents:(?P<title>.+) \[\d+\/\d+\][ _-]{0,3}("|#34;).+("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // [:SEK9:][TV]-[:Cow.And.Chicken.S04E12.Part.1.DUTCH.PDTV.XViD-SPiROTV:]-[1/4]-"Cow.And.Chicken.S04E12.Part.1.DUTCH.PDTV.XViD-SPiROTV.par2" yEnc (1/1)
	else if (preg_match('/^\[:sek9:\]\[[-\w]+\][ _-]{0,3}\[:(?P<title>.+):\][ _-]{0,3}\[\d+\/\d+\][ _-]{0,3}("|#34;).+("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // View.from.the.Top.Flight.Girls.2003.German.DL.720p.WEB-DL.h264-msd [ich for usenet-4all.info] [ich25882] [powered by ssl-news.info] (01/70) "ich25882.par2" yEnc
	else if (preg_match('/^(?P<title>.+)\[.+?usenet-4all\.info\][ _-]{0,3}\[.+\][ _-]{0,3}\(\d+\/\d+\) ("|#34;).+("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // <<<Thor2204>>><<<Nam.Dienst.im.Vietnam.S02E04.Der.Gefreite.Martsen.GERMAN.FS.DVDRip.xviD-aWake>>>usenet-space-cowboys.info<<<Powered by https://secretusenet.com>< "awa-namdivs02e04.nfo" >< 02/31 (432,76 MB) >< 8,86 kB > yEnc (1/1)
	else if (preg_match('/^<<<(Thor2204|Thor)>>><<<(?P<title>.+)>>>usenet-space-cowboys.+<<<Powered.+>< ("|#34;).+("|#34;).+> yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // <<<Nimue>>><<<Terra.Xpress.Achtung.extrem.giftig.GERMAN.DOKU.HDTV.720p.x264-iNFOTv>>> usenet-space-cowboys.info <<<Powered by https://secretusenet.com>< "infotv-terra.xpress_ahegiftig_720p.nfo" >< 02/28 (1000,46 MB) >< 1,70 kB > yEnc (1/1)
	else if (preg_match('/^<<<Nimue>>><<<(?P<title>.+)>>>.+<<<Powered by.+yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // [Lie.to.me.S02E01.Gespalten.GERMAN.DUBBED.DL.WS.720p.HDTV.PROPER.x264-euHD]-[ich for usenet-4all.info]-[ich14126]-[powered by Dreamload.com] (001/108) "ich14126.par2" yEnc (1/1)
	else if (preg_match('/^\[(?P<title>.+)\][ _-]{0,3}\[.+usenet-4all\.info][ _-]{0,3}\[.+\][ _-]{0,3}\[powered.+\] \(\d+\/\d+\) ("|#34;).+("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // <<<Friends.S01E13>>>CowBoyUp26<<<Powered by https://secretusenet.com>< "Friends.S01E13.Der.Superbusen.German.FS.DVDRiP.XviD.INTERNAL-MOViESToRE_usenet-space-cowboys.info.nfo" >< 02/10 (256,07 MB) >< 7,51 kB > yEnc (1/1)
	else if (preg_match('/^<<<.+?>>>CowBoyUp26<<<Powered by.+>< ("|#34;)(?P<title>.+)_usenet-space-cowboys.+("|#34;) >< \d+\/\d+ \(.+?\) ><.+> yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // <kere.ws> The.Middle.S01E12.HDTV.XviD-P0W4 [01/21] - "the.middle.s01e12.hdtv.par2" yEnc
	else if (preg_match('/^<kere\.ws> (?P<title>.+)[ _-]{0,3}\[\d+\/\d+\][ _-]{0,3}("|#34;).+("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // panter - [40/41] - "675367-Monte Carlo 2011 BRRip XviD AC3 REFiLL NL ingebakken.vol069+69.PAR2" yEnc
	else if (preg_match('/^(\(snake\)|panter|wildrose|shadowman|P2H)[ _-]{0,3}(\[\d+\/\d+\])?[ _-]{0,3}("|#34;)(info-|P2H-)?(?P<title>.+)( \.){0,2}(part\d+\.rar|vol\d+\+\d+\.par2|rar\.vol\d+\+\d+\.PAR2|par2|rar|rar\.par2|mkv\.par2|(avi|dvd5|mkv)\.part\d+\.rar|nfo|zip|nzb)("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // [www.allyourbasearebelongtous.pw]-[Mike.And.Molly.S03.NTSC.DVDR-ToF]-[002/106] "mam.s3d1.tof.par2" - 4.85 GB - yEnc
	else if (preg_match('/^\[www\.allyourbasearebelongtous\.pw\][ _-]{0,3}\[(?P<title>.+)\][ _-]{0,3}("|#34;).+("|#34;)[ _-]{0,3}.+yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // ( Criminal.Minds.S06E16.Am.Ende.des.Traums.GERMAN.DUBBED.WS.DVDRiP.XviD-SOF ) )ghost-of-usenet.org( - (05/34) )www.SSL-News.info( - #34;sof-criminal.minds.s06e16.r00#34; yEnc
	else if (preg_match('/^\([ _-]{0,3}(?P<title>.+)[ _-]{0,3}\)[ _-]{0,3}\)ghost-of-usenet\.org\([ _-]{0,3}\(\d+\/\d+\) ?\).+("|#34;).+("|#34;)[ _-]{0,3}yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // (Public) (FULL) (a.b.teevee@EFNet) [04/13] (????) [001/101] - "S01E10.720p.HDTV.X264-DIMENSION (1).nzb" yEnc
	else if (preg_match('/^\(Public\)[ _-]{0,3}\(FULL\)[ _-]{0,3}\(.+efnet\)[ _-]{0,3}\[\d+\/\d+\][ _-]{0,3}\(\?+\)[ _-]{0,3}\[\d+\/\d+\][ _-]{0,3}("|#34;)(?P<title>.+)[ _-]{0,3}(\(1\))?( |\.| \.)?(part\d+\.rar|vol\d+\+\d+\.par2|rar\.vol\d+\+\d+\.PAR2|par2|rar|rar\.par2|mkv\.par2|(avi|dvd5|mkv)\.part\d+\.rar|nfo|zip|nzb)("|#34;) yEnc/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // P2H - Angry_Birds_Trilogy_EUR_3DS-ABSTRAKT - "as-abt.par2" yEnc
	else if (preg_match('/^P2H[ _-]{0,3}(?P<title>.+)[ _-]{0,3}("|#34;).+("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // <kere.ws> [ The.Middle.S03.COMPLETE.720p.WEB-DL.DD5.1.H.264-EbP ]-[644/911] "The.Middle.S03E18.720p.WEB-DL.DD5.1.H.264-EbP.par2" yEnc
	else if (preg_match('/^<kere\.ws> \[[ _-]{0,3}(?P<title>.+)[ _-]{0,3}\][ _-]{0,3}\[\d+\/\d+\][ _-]{0,3}("|#34;).+("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // (01/68) - "melodifestivalen.2013.deltavling.2.swedish.720p.hdtv.x264-xd2v.nfo" 2,90 GB - [Foreign] Melodifestivalen.2013.Deltavling.2.SWEDiSH.720p.HDTV.x264-xD2V yEnc
	else if (preg_match('/\(\d+\/\d+\)[ _-]{0,3}("|#34;).+("|#34;).+\[Foreign\] (?P<title>.+) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // [foreign]-[ Dicte.S01E05.DANiSH.HDTV.x264-TVBYEN ] - "Dicte.S01E05.DANiSH.HDTV.x264-TVBYEN.nfo"
	else if (preg_match('/^\[foreign\][ _-]{0,3}\[ (?P<title>.+) \][ _-]{0,3}("|#34;).+("|#34;)/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // [ nEwZ[NZB].iNFO - [ The.Middle.S04E14.The.Smile.GERMAN.DUBBED.WS.WEBRip.XviD-TVP ] - File [03/12]: "tvp-themiddle-s04e14-xvid.r01" yEnc
	else if (preg_match('/^\[ nEwZ\[NZB\]\.iNFO[ _-]{0,3}\[ (?P<title>.+) \][ _-]{0,3}File \[\d+\/\d+\]: ("|#34;).+?("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // [Lords-of-Usenet.org]_[Partner von SSL-News.info](001/179) "Flashpoint Staffel 5 HDTV 720p engl. + dt. Sub.par2" yEnc
	else if (preg_match('/^\[Lords-of-Usenet\.org\][ _-]{0,3}\[Partner.+\]\(\d+\/\d+\)[ _-]{0,3}("|#34;)(?P<title>.+)("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // Being.Erica.S03E01.Nicht.mehr.allein.German.DD20.Dubbed.DL.720p.iTunesHD.AVC-TVS [ich for usenet-4all.info]-[ich18707]- "ich18707.nfo" yEnc (1/105)
	else if (preg_match('/^(?P<title>.+)\[.+?usenet-4all\.info\]-\[.+\][ _-]{0,3}("|#34;).+("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // (02/13) "Lipstick.Jungle.S01E02.Nichts.ist.heilig.GERMAN.DUBBED.DL.WS.720p.HDTV.x264-euHD www.brothers-of-usenet.org - empfehlen - Newsconnection.eu.part1.rar" yEnc
	else if (preg_match('/^\(\d+\/\d+\) ("|#34;)(?P<title>.+) www\.brothers-of-usenet\.org.+("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} // [Lords-of-Usenet]_[Partner von SSL-News.info](412/554) "Spartacus Vengeance Staffel 2 HDTV 720p engl. + dt. Sub.part040.rar" yEnc
	else if (preg_match('/^\[Lords-of-Usenet\][ _-]{0,3}(<<|\(\(|\[)(Partner|Sponsor).+(>>|\)\)|\])\(\d+\/\d+\)[ _-]{0,3}("|#34;)(?P<title>.+)("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	}
	//[Lords-of-Usenet.org] <<Sponsored by SSL-News.info>> - Lie.to.me.Staffel2.DVD3.GERMAN.2009.WS.DL.DVDR-aWake- (03/99) - "awa-lietomes02d03.r00" - 4,46 GB - yEnc
	//94 out of 3657512
	else if (preg_match('/^\[Lords-of-Usenet\.org\][ _-]{0,3}<?<Sponsored.+>>?[ _-]{0,3}(?P<title>.+)[ _-]{0,3}\(\d+\/\d+\)[ _-]{0,3}("|#34;).+("|#34;)[ _-]{0,3}.+yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	}
	//[www.Lords-of-Usenet.org]_[Sposnored by SSL_News.info](001/193) "Emergency.Room Staffel 4 DL.German.Dubbed.720p.WEB-DL.x264-FREAKS E12-E22.par2" yEnc
	//602 out of 3657418
	else if (preg_match('/\[www\.Lords-of-Usenet\.org\][ _-]{0,3}\[.+\]\(\d+\/\d+\) ("|#34;)(?P<title>.+)("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	}
	//[Lords-of-Usenet.org]_<Sponsored_by_SSL-News_info>_Proudly_presents_Herzflimmern.S01E30.Die.Klinik.am.See.GERMAN.WS.dTV.XViD-SiTiN [01/28] - "sitin-hf-s01e30-xvid.nfo" yEnc
	//120 out of 3656816
	else if (preg_match('/^\[Lords-of-Usenet\.org\][ _-]{0,3}<?<Sponsored.+>>?_(?P<title>.+) \[\d+\/\d+\][ _-]{0,3}("|#34;).+("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	}
	//Brothers-of-Usenet.org - Newsconnection.eu "Emergency.Room.S02DVD2.DVDR.German.DL.BoU"[086/100] - "BoU-ER-S2D2.part084.rar" yEnc
	//531 out of 3652904
	else if (preg_match('/^Brothers-of-Usenet\.org.+("|#34;)(?P<title>.+)("|#34;)\[\d+\/\d+\][ _-]{0,3}("|#34;).+("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	}
	//<<<MetalDept>>><<<Vallorch - Neverfade (2013)>>>Best Fucking Metal<<< "Vallorch - Neverfade (2013).par2">[01/14] 142,47 MB yEnc
	//568 out of 5398477
	else if (preg_match('/^<<<MetalDept>>><<<(?P<title>.+)>>>.+<<< ("|#34;).+("|#34;)>\[\d+\/\d+\].+yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	}
	//<<<MetalDept>>><<<Amberian Dawn - Re-Evolution - 2013 (320 kbps)>>>usenet-of-outlaws.info-Powered by SecretUsenet.com<<< "Amberian Dawn - Re-Evolution - 2013 (320 kbps).par2">[01/16] 161,76 MB
	//238 out of 5398477
	else if (preg_match('/^<<<MetalDept>>><<<(?P<title>.+)>>>usenet-of-outlaws.info.+<<< ("|#34;).+("|#34;)>\[\d+\/\d+\].+/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	}
	//Brothers-of-Usenet.org (265/323) "Mayday-Alarm.im.Cockpit.S04E10.Geisterflug.Helios.522.German.DL.Doku.WS.SatRip.XviD-fBi.par2" - 6,00 GB Newsconnection.eu yEnc
	//127 out of 3652373
	else if (preg_match('/^Brothers-of-Usenet\.org \(\d+\/\d+\)[ _-]{0,3}("|#34;)(?P<title>.+)\.(par|rar|nfo|vol).+("|#34;)[ _-]{0,3}.+yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	}
	//[NZBMatrix.com]-[ The.Sopranos.S01.iNTERNAL.WS.AC3.DVDRip.XviD-SAiNTS ] [647/799] - "the.sopranos.s01e11.ws.ac3.dvdrip.xvid-saints.part29.rar" yEnc
	//108 out of 3583346
	else if (preg_match('/^\[NZBMatrix\.com\][ _-]{0,3}\[ (?P<title>.+) \] \[\d+\/\d+\][ _-]{0,3}("|#34;).+("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	}
	//[ VintageReduction ]-[ the.jeselnik.offensive.s01e01.THIRTY.TO.ONE.file.size.reduction.Please.Read ]-[01/16] - "the.burn.with.jeff.ross.s02e01.25.to.1.reduction.by.vintage.PAR2" yEnc (1/1)
	//1 out of 3583238
	else if (preg_match('/^\[ VintageReduction \][ _-]{0,3}\[ (?P<title>.+) \][ _-]{0,3}\[\d+\/\d+\][ _-]{0,3}.+yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	}
	//>ghost-of-usenet.org<Die.Bill.Cosby.Show.S07.German.xvid>Sponsored by Astinews< (529/576) "fkktv-cosby-s07e23.nfo" yEnc
	//1798 out of 3681602
	else if (preg_match('/^>ghost-of-usenet\.org< ?(?P<title>.+)>Sponsored.+< ?\(\d+\/\d+\)[ _-]{0,3}("|#34;)?.+("|#34;)?[ _-]{0,3}yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	}
	//< Grimm.S01E10.German.Subbed.HDTV.XviD-LOL.by.GhostUp10 > >ghost-of-usenet.org< - (01/27) >www.SSL-News.info< - "gu10maerchen110.par2" yEnc
	//862 out of 3679804
	else if (preg_match('/^<[ _-]{0,3}(?P<title>.+)\.by\.GhostUp10[ _-]{0,3}> ?>ghost-of-usenet\.org<[ _-]{0,3}\(\d+\/\d+\)[ _-]{0,3}>www.+<[ _-]{0,3}("|#34;)?.+("|#34;)?[ _-]{0,3}yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	}
	//(((CowboyUp2012)))(((Hooded_Fang-Tosta_Mista-2012-SO)))usenet-space-cowboys.info(((Powered by https://secretusenet.com)( #34;Hooded_Fang-Tosta_Mista-2012-SO.rar#34; )( 3/4 (48,14 MB) )( 44,83 MB ) yEnc
	//
	else if (preg_match('/^\(\(\(CowboyUp2012\)\)\)[ _-]{0,3}\(\(\((?P<title>.+)\)\)\)[ _-]{0,3}.+yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	}
	//<<<CowboyUp2012 Serie>>><<<Galileo.Big.Pictures.Die.Extremsten.Bilder.der.Welt.GERMAN.DOKU.WS.SATRiP.XviD-TVP>>>usenet-space-cowboys.info<<<Powered by https://secretusenet.com>< "tvp-galileo-pictures-extreme-xvid.r24" >< 27/69 (1,18 GB) >< 19,07 MB > yEnc
	//
	else if (preg_match('/^<<<CowboyUp2012.+>>>[ _-]{0,3}<<<(?P<title>.+)>>>[ _-]{0,3}.+yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} //[ TOWN ]-[ www.town.ag ]-[ Breaking.Bad.S05E14.HDTV.x264-ASAP ]-[01/39]- "breaking.bad.s05e14.hdtv.x264-asap.nfo" yEnc
	else if (preg_match('/^\[ TOWN \][ _-]{0,3}\[ www\.town\.ag \][ _-]{0,3}\[ (?P<title>.+) \][ _-]{0,3}\[\d+\/\d+\][ _-]{0,3}("|#34;).+\.(par|vol|rar|nfo).*("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	}
	//[ TOWN ]-[ www.town.ag ]-[ partner of www.ssl-news.info ]-[ PR0N ] [17/21] - "SexVideoCasting.13.09.30.Judy.Smile.XXX.1080p.MP4-SEXORS.vol00+1.par2" - 732,59 MB yEnc
	//[ TOWN ]-[ www.town.ag ]-[ partner of www.ssl-news.info ]-[ MUSIC ] [04/26] - "VA_-_Top_30_Dance_Club_Play-2013-SL.part02.rar" - 325,10 MB yEnc
	else if (preg_match('/^\[ TOWN \][ _-]{0,3}\[ www\.town\.ag \][ _-]{0,3}\[ partner of www\.ssl-news\.info \][ _-]{0,3}\[ .* \] \[\d+\/\d+\][ _-]{0,3}("|#34;)(?P<title>.+)(\.part\d+)?(\.(par2|(vol.+?))"|\.[a-z0-9]{3}"|")[ _-]{0,3}/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} //<TOWN><www.town.ag > Breaking.Bad.S05E16.720p.WEB-DL.DD5.1.H.264-BS <partner of www.ssl-news.info > [04/51]- "Breaking.Bad.S05E16.Felina.720p.WEB-DL.DD5.1.H.264-BS.r01" yEnc
	else if (preg_match('/^<TOWN><www.town.ag >[ _-]{0,3}(?P<title>.+)[ _-]{0,3}<partner of www\.ssl-news\.info >[ _-]{0,3}\[\d+\/\d+\][ _-]{0,3}("|#34;).+?("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} //FÃ¼r brothers-of-usenet.net - [01/10] - "Costume.Quest.Language.Changer.DOX-RAiN.par2" yEnc
	else if (preg_match('/^.+?brothers-of-usenet\.net[ _-]{0,3}\[\d+\/\d+\][ _-]{0,3}("|#34;)(?P<title>.+)\.(par|vol|rar|nfo).*?("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	}
	//[Charlie.Valentine.2009.German.DTS.DL.1080p.BluRay.x264-SoW]-[ZED for usenet-4all.info]-[zed7930]-[powered by Dreamload.com] (05/72) #34;zed7930.part03.rar" yEnc
	//Pusher.II.2004.German.1080p.BluRay.x264-DETAiLS [ZED for usenet-4all.info]-[zed15024]-(03/92) #34;zed15024.part01.rar#34; yEnc
	else if (preg_match('/^\[?(?P<title>.+)\]?[ _-]{0,3}\[ZED for usenet-4all.info\][ _-]{0,3}\[.+?\][ _-]{0,3}\(\d+\/\d+\)[ _-]{0,3}("|#34;).+?("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} //3000 Fiction Ebooks Collection - "Anthony Piers - Incarnations Of Immortality 2 - Bearing an Hourglass [uc].txt" yEnc
	else if (preg_match('/^3000 Fiction Ebooks Collection[ _-]{0,3}("|#34;)(?P<title>.+)\.(txt|pdf|lit|doc|rtf|chm|par2)("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} //[united-forums.co.uk] NDS Roms 0501-0750 [039/262] - "0537 - Kirarin x Revolution - Kira Kira Idol Audition (J) -WWW.UNITED-FORUMS.CO.UK-.7z" yEnc
	else if (preg_match('/^\[united-forums.co.uk\].+?\[\d+\/\d+\][ _-]{0,3}("|#34;)?(?P<title>.+)( -WWW.UNITED-FORUMS.CO.UK)?(\.|-|_)+(rar|zip|7z)("|#34;)? yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} //brothers-of-usenet.net()Die.Verschollenen.Inseln.GERMAN.DOKU.WS.HDTVRip.XviD-OWG()(03/17) #34;owg-dvi.part1.rar#34; - 391,36 MB - yEnc
	else if (preg_match('/^brothers-of-usenet\.net\(\)(?P<title>.+)\(\)\(\d+\/\d+\) ("|#34).+("|#34).+?[ _-]{0,3}.+?[ _-]{0,3}yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} //New eBooks 8 June 2013 - "Melody Carlson - [Carter House Girls 08] - Last Dance (mobi).rar"
	else if (preg_match('/^New eBooks.+[ _-]{0,3}("|#34;)(?P<title>.+)\.(par|vol|rar|nfo).*?("|#34;)/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} //[ nEwZ[NZB].iNFO ] - [ lttm.13.05.31.tilly.and.pepper.tied.and.tickeled ] - File [01/52]: "lttm.13.05.31.tilly.and.pepper.tied.and.tickeled.r00" yEnc
	else if (preg_match('/^\[ ?nEwZ\[NZB\]\.iNFO ?\][ _-]{0,3}\[ ?(?P<title>.*? ?)\][ _-]{0,3}.*?\[\d+\/\d+\][ -:]{0,3}("|#34;).+("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} //[NEW DOX] The.King.of.Fighters.XIII.Update.v1.1c-RELOADED [1/6] - "The.King.of.Fighters.XIII.Update.v1.1c-RELOADED.par2"
	else if (preg_match('/^\[NEW DOX\][ _-]{0,3}(?P<title>.+)[ _-]{0,3}\[\d+\/\d+\][ _-]{0,3}("|#34;).+("|#34;)/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	}
	//http://nzbroyalty.com - UK Top 40 Compilation Charts 21.08.11 - [01/10] - "39 - American Anthems.par2"
	//http://nzbroyalty.com - UK Top 40 Solo Artist Charts 21.08.11 - [1/6] - "34 - Jedward - Victory.par2" yEnc
	else if (preg_match('/^http:\/\/nzbroyalty\.com[ _-]{0,3}UK Top 40 (Solo Artist|Compilation) Charts .+[ _-]{0,3}\[\d+\/\d+\][ _-]{0,3}("|#34;)\d+[ _-]{0,3}(?P<title>.+)\.(par|vol|rar|nfo).*?("|#34;)	/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	}
	//http://nzbroyalty.com - UK Top 40 Compilation Charts 21.08.11 - [001/118] - "UK Top 40 Comps Charts 21.08.11.par2"
	//http://nzbroyalty.com - UK Top 40 Solo Artist Charts 21.08.11 - [01/96] - "UK Top 40 Solo Charts 21.08.11.par2" yEnc
	else if (preg_match('/^http:\/\/nzbroyalty\.com[ _-]{0,3}UK Top 40 (Solo Artist|Compilation) Charts .+[ _-]{0,3}\[\d+\/\d+\][ _-]{0,3}("|#34;)(?P<title>.+)\.(par|vol|rar|nfo).*?("|#34;)/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} //Dutch: Updates & NIEUW maand juni 2013 [NMS] [256/256] - "Young, Donna - Levensgevaarlijk Geheim.epub"
	else if (preg_match('/^\Dutch: Updates & NIEUW maand.+ \[NMS\] \[\d+\/\d+\][ _-]{0,3} ("|#34;)(?P<title>.+)("|#34;)/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} //(UHQ)(Bambi.2.2006.German.DTS.DL.1080p.BluRay.x264-RSG) [01/46] - #34;rsg-bambi2-1080p-sample.mkv#34; yEnc
	else if (preg_match('/\(UHQ\)\((?P<title>.+)\)[ _-]{0,3}\[\d+\/\d+\][ _-]{0,3}("|#34;).+("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} //!!www.usenet4all.eu!! - Failure.To.Launch.2006.1080p.BD9.x264-IGUANA[01/92] - #34;iguana-ftl.1080p.bd9.nfo#34; yEnc
	else if (preg_match('/^!!www.usenet4all.eu!![ _-]{0,3}(?P<title>.+)\[\d+\/\d+\][ _-]{0,3}("|#34;).+?("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} //)ghost-of-usenet.org)Final.Destination.2.2003.German.AC3D.1080p.BluRay.x264-CDD(have fun((03/84) #34;cdd-fd2_ger_ac3d_1080p_bluray.r00#34; yEnc
	else if (preg_match('/\)ghost-of-usenet\.org\)(?P<title>.+)\(.+?\(\(\d+\/\d+\)[ _-]{0,3}("|#34;).+?("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	} //0-Day Apps Flood - [www.united-forums.co.uk] - (239/408) #34;Plants.vs.Zombies.v1.2.0.1065.PLUS.10.TRAINER-BReWErS - [www.united-forums.co.uk] -.rar#34; yEnc
	else if (preg_match('/^0-Day Apps Flood[ _-]{0,3}\[www\.united-forums\.co\.uk\][ _-]{0,3}\(\d+\/\d+\)[ _-]{0,3}("|#34;)(?P<title>.+)[ _-]{0,3}\[.+\][ _-]{0,3}.+?("|#34;) yEnc$/i', $subject, $match)) {
		$cleanerName = $match['title'];
		if (!empty($cleanerName)) {
			return $cleanerName;
		}
	}
	//This one should remain last
	//Digitalmagazin.info.2011.01.25.GERMAN.RETAiL.eBOOk-sUppLeX.rar
	//no match is spaces
	else if (strlen($subject) > 20 && !preg_match('/\s/i', $subject) && preg_match('/(?P<title>[\w-\._]*)\.(rar|par|par2|part\d+)$/i', $subject, $match)) {
		if (strlen($match['title']) > 15) {
			$cleanerName = $match['title'];
			if (!empty($cleanerName)) {
				return $cleanerName;
			}
		}
	} else if (!empty($cleanerName) && is_array($cleanerName)) {
		return $cleanerName;
	}
}
