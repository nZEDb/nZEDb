<?php

/**
 * Class NameFixer
 */
class NameFixer
{
	CONST PREDB_REGEX = "/([\w\(\)]+[\._]([\w\(\)]+[\._-])+[\w\(\)]+-\w+)/";

	/**
	 * @param bool $echooutput
	 */
	function __construct($echooutput = true)
	{
		$this->echooutput = ($echooutput && nZEDb_ECHOCLI);
		$this->relid = $this->fixed = $this->checked = 0;
		$this->db = new DB();
		$db = $this->db;
		if ($db->dbSystem() == 'mysql') {
			$this->timeother = " AND rel.adddate > (NOW() - INTERVAL 0 HOUR) AND rel.categoryid IN (1090, 2020, 3050, 6050, 5050, 7010, 8050) GROUP BY rel.id ORDER BY postdate DESC";
			$this->timeall = " AND rel.adddate > (NOW() - INTERVAL 6 HOUR) GROUP BY rel.id ORDER BY postdate DESC";
		} else if ($db->dbSystem() == 'pgsql') {
			$this->timeother = " AND rel.adddate > (NOW() - INTERVAL '6 HOURS') AND rel.categoryid IN (1090, 2020, 3050, 6050, 5050, 7010, 8050) GROUP BY rel.id ORDER BY postdate DESC";
			$this->timeall = " AND rel.adddate > (NOW() - INTERVAL '6 HOURS') GROUP BY rel.id ORDER BY postdate DESC";
		}
		$this->fullother = " AND rel.categoryid IN (1090, 2020, 3050, 6050, 5050, 7010, 8050) GROUP BY rel.id";
		$this->fullall = "";
		$this->done = $this->matched = false;
		$this->c = new ColorCLI();
		$this->consoletools = new ConsoleTools();
	}

	/**
	 * Attempts to fix release names using the NFO.
	 *
	 * @param $time
	 * @param $echo
	 * @param $cats
	 * @param $namestatus
	 * @param $show
	 */
	public function fixNamesWithNfo($time, $echo, $cats, $namestatus, $show)
	{

		if ($time == 1) {
			echo $this->c->header("Fixing search names in the past 6 hours using .nfo files.");
		} else {
			echo $this->c->header("Fixing search names since the beginning using .nfo files.");
		}

		$db = $this->db;
		$type = "NFO, ";
		// Only select releases we haven't checked here before
		if ($db->dbSystem() == "mysql") {
			$uc = "UNCOMPRESS(nfo)";
		} else if ($db->dbSystem() == "pgsql") {
			$uc = "nfo";
		}
		$preid = false;
		if ($cats === 3) {
			$query = "SELECT rel.id AS releaseid FROM releases rel "
				. "INNER JOIN releasenfo nfo ON (nfo.releaseid = rel.id) "
				. "WHERE nzbstatus = 1 AND preid = 0";
			$cats = 2;
			$preid = true;
		} else {
			$query = "SELECT rel.id AS releaseid FROM releases rel "
				. "INNER JOIN releasenfo nfo ON (nfo.releaseid = rel.id) "
				. "WHERE (isrenamed = 0 OR rel.categoryid = 7010) AND proc_nfo = 0";
		}
		//24 hours, other cats
		if ($time == 1 && $cats == 1) {
			echo $this->c->header($query . $this->timeother . ";\n");
			$relres = $db->queryDirect($query . $this->timeother);
		}
		//24 hours, all cats
		else if ($time == 1 && $cats == 2) {
			echo $this->c->header($query . $this->timeall . ";\n");
			$relres = $db->queryDirect($query . $this->timeall);
		}
		//other cats
		else if ($time == 2 && $cats == 1) {
			echo $this->c->header($query . $this->fullother . ";\n");
			$relres = $db->queryDirect($query . $this->fullother);
		}
		//all cats
		if ($time == 2 && $cats == 2) {
			echo $this->c->header($query . $this->fullall . ";\n");
			$relres = $db->queryDirect($query . $this->fullall);
		}
		$total = $relres->rowCount();
		if ($total > 0) {
			echo $this->c->primary(number_format($total) . " releases to process.");
			sleep(2);
			foreach ($relres as $rel) {
				$relrow = $db->queryOneRow("SELECT nfo.releaseid AS nfoid, rel.groupid, rel.categoryid, rel.searchname, {$uc} AS textstring, "
					. "rel.id AS releaseid FROM releases rel "
					. "INNER JOIN releasenfo nfo ON (nfo.releaseid = rel.id) "
					. "WHERE rel.id = " . $rel['releaseid']);

				//ignore encrypted nfos
				if (preg_match('/^=newz\[NZB\]=\w+/', $relrow['textstring'])) {
					$db->queryExec(sprintf("UPDATE releases SET proc_nfo = 1 WHERE id = %d", $relrow['rel.id']));
					$this->checked++;
				} else {
					$this->done = $this->matched = false;
					$this->checkName($relrow, $echo, $type, $namestatus, $show, $preid);
					$this->checked++;
					if ($this->checked % 500 === 0 && $show === 1) {
						echo $this->c->alternate(number_format($this->checked) . " NFOs processed.\n");
						sleep(1);
					}
				}
				if ($show === 2) {
					$this->consoletools->overWritePrimary("Renamed Releases: [" . number_format($this->fixed) . "] " . $this->consoletools->percentString($this->checked, $total));
				}
			}
			if ($echo == 1) {
				echo $this->c->header("\n" . number_format($this->fixed) . " releases have had their names changed out of: " . number_format($this->checked) . " NFO's.");
			} else {
				echo $this->c->header("\n" . number_format($this->fixed) . " releases could have their names changed. " . number_format($this->checked) . " NFO's were checked.");
			}
		} else {
			echo $this->c->info("Nothing to fix.");
		}
	}

	/**
	 * Attempts to fix release names using the File name.
	 *
	 * @param $time
	 * @param $echo
	 * @param $cats
	 * @param $namestatus
	 * @param $show
	 */
	public function fixNamesWithFiles($time, $echo, $cats, $namestatus, $show)
	{
		if ($time == 1) {
			echo $this->c->header("Fixing search names in the past 6 hours using the filename.");
		} else {
			echo $this->c->header("Fixing search names since the beginning using the filename.");
		}

		$db = $this->db;
		$type = "Filenames, ";
		$preid = false;
		if ($cats === 3) {
			$query = "SELECT relfiles.name AS textstring, rel.categoryid, rel.searchname, rel.groupid, relfiles.releaseid AS fileid, "
				. "rel.id AS releaseid FROM releases rel "
				. "INNER JOIN releasefiles relfiles ON (relfiles.releaseid = rel.id) "
				. "WHERE nzbstatus = 1 AND preid = 0";
			$cats = 2;
			$preid = true;
		} else {
			$query = "SELECT relfiles.name AS textstring, rel.categoryid, rel.searchname, rel.groupid, relfiles.releaseid AS fileid, "
				. "rel.id AS releaseid FROM releases rel "
				. "INNER JOIN releasefiles relfiles ON (relfiles.releaseid = rel.id) "
				. "WHERE (isrenamed = 0 OR rel.categoryid = 7010) AND proc_files = 0";
		}
		//24 hours, other cats
		if ($time == 1 && $cats == 1) {
			echo $this->c->header($query . $this->timeother . ";\n");
			$relres = $db->queryDirect($query . $this->timeother);
		}
		//24 hours, all cats
		if ($time == 1 && $cats == 2) {
			echo $this->c->header($query . $this->timeall . ";\n");
			$relres = $db->queryDirect($query . $this->timeall);
		}
		//other cats
		if ($time == 2 && $cats == 1) {
			echo $this->c->header($query . $this->fullother . ";\n");
			$relres = $db->queryDirect($query . $this->fullother);
		}
		//all cats
		if ($time == 2 && $cats == 2) {
			echo $this->c->header($query . $this->fullall . ";\n");
			$relres = $db->queryDirect($query . $this->fullall);
		}
		$total = $relres->rowCount();
		if ($total > 0) {
			echo $this->c->primary(number_format($total) . " file names to process.");
			sleep(2);
			foreach ($relres as $relrow) {
				$this->done = $this->matched = false;
				$this->checkName($relrow, $echo, $type, $namestatus, $show, $preid);
				$this->checked++;
				if ($this->checked % 500 == 0 && $show === 1) {
					echo $this->c->alternate(number_format($this->checked) . " files processed.");
					sleep(1);
				}
				if ($show === 2) {
					$this->consoletools->overWritePrimary("Renamed Releases: [" . number_format($this->fixed) . "] " . $this->consoletools->percentString($this->checked, $total));
				}
			}
			if ($echo == 1) {
				echo $this->c->header("\n" . number_format($this->fixed) . " releases have had their names changed out of: " . number_format($this->checked) . " files.");
			} else {
				echo $this->c->header("\n" . number_format($this->fixed) . " releases could have their names changed. " . number_format($this->checked) . " files were checked.");
			}
		} else {
			echo $this->c->info("Nothing to fix.");
		}
	}

	/**
	 * Attempts to fix release names using the Par2 File.
	 *
	 * @param $time
	 * @param $echo
	 * @param $cats
	 * @param $namestatus
	 * @param $show
	 * @param $nntp
	 */
	public function fixNamesWithPar2($time, $echo, $cats, $namestatus, $show, $nntp)
	{
		if (!isset($nntp)) {
			exit($this->c->error("\nNot connected to usenet(namefixer->fixNamesWithPar2).\n"));
		}

		if ($time == 1) {
			echo $this->c->header("Fixing search names in the past 6 hours using the par2 files.");
		} else {
			echo $this->c->header("Fixing search names since the beginning using the par2 files.");
		}

		$db = $this->db;
		if ($cats === 3) {
			$query = "SELECT rel.id AS releaseid, rel.guid, rel.groupid FROM releases rel WHERE nzbstatus = 1 AND preid = 0";
			$cats = 2;
		} else {
			$query = "SELECT rel.id AS releaseid, rel.guid, rel.groupid FROM releases rel WHERE (isrenamed = 0 OR rel.categoryid = 7010) AND proc_par2 = 0";
		}

		//24 hours, other cats
		if ($time == 1 && $cats == 1) {
			echo $this->c->header($query . $this->timeother . ";\n");
			$relres = $db->queryDirect($query . $this->timeother);
		}
		//24 hours, all cats
		if ($time == 1 && $cats == 2) {
			echo $this->c->header($query . $this->timeall . ";\n");
			$relres = $db->queryDirect($query . $this->timeall);
		}
		//other cats
		if ($time == 2 && $cats == 1) {
			echo $this->c->header($query . $this->fullother . ";\n");
			$relres = $db->queryDirect($query . $this->fullother);
		}
		//all cats
		if ($time == 2 && $cats == 2) {
			echo $this->c->header($query . $this->fullall . ";\n");
			$relres = $db->queryDirect($query . $this->fullall);
		}
		$total = $relres->rowCount();
		if ($total > 0) {
			echo $this->c->primary(number_format($total) . " releases to process.");
			sleep(2);
			$db = $this->db;
			$nzbcontents = new NZBContents($this->echooutput);
			$pp = new PostProcess($this->echooutput);
			foreach ($relres as $relrow) {
				if (($nzbcontents->checkPAR2($relrow['guid'], $relrow['releaseid'], $relrow['groupid'], $db, $pp, $namestatus, $nntp, $show)) === true) {
					$this->fixed++;
				}
				$this->checked++;
				if ($this->checked % 500 == 0 && $show === 1) {
					echo $this->c->alternate("\n" . number_format($this->checked) . " files processed.\n");
				}
				if ($show === 2) {
					$this->consoletools->overWritePrimary("Renamed Releases: [" . number_format($this->fixed) . "] " . $this->consoletools->percentString($this->checked, $total));
				}
			}
			if ($echo == 1) {
				echo $this->c->header("\n" . number_format($this->fixed) . " releases have had their names changed out of: " . number_format($this->checked) . " files.");
			} else {
				echo $this->c->header("\n" . number_format($this->fixed) . " releases could have their names changed. " . number_format($this->checked) . " files were checked.");
			}
		} else {
			echo $this->c->alternate("Nothing to fix.");
		}
	}

	/**
	 * Update the release with the new information.
	 *
	 * @param     $release
	 * @param     $name
	 * @param     $method
	 * @param     $echo
	 * @param     $type
	 * @param     $namestatus
	 * @param     $show
	 * @param int $preid
	 */
	public function updateRelease($release, $name, $method, $echo, $type, $namestatus, $show, $preid = 0)
	{
		if ($this->relid !== $release["releaseid"]) {
			$namecleaning = new ReleaseCleaning();
			$newname = $namecleaning->fixerCleaner($name);
			if (strtolower($newname) != strtolower($release["searchname"])) {
				$this->matched = true;
				$this->relid = $release["releaseid"];

				$this->category = new Category();
				$determinedcat = $this->category->determineCategory($newname, $release["groupid"]);

				if ($type === "PAR2, ") {
					$newname = ucwords($newname);
					if (preg_match('/(.+?)\.[a-z0-9]{2,3}(PAR2)?$/i', $name, $match)) {
						$newname = $match[1];
					}
				}

				$this->fixed++;

				$this->checkedname = explode("\\", $newname);
				$newname = $this->checkedname[0];
				$newname = preg_replace(array('/^[-=_\.:\s]+/', '/[-=_\.:\s]+$/'), '', $newname);

				if ($this->echooutput === true && $show === 1) {
					$groups = new Groups();
					$groupname = $groups->getByNameByID($release["groupid"]);
					$oldcatname = $this->category->getNameByID($release["categoryid"]);
					$newcatname = $this->category->getNameByID($determinedcat);

					if ($type === "PAR2, ") {
						echo "\n";
					}

					echo
						$this->c->headerOver("\nNew name:  ") .
						$this->c->primary($newname) .
						$this->c->headerOver("Old name:  ") .
						$this->c->primary($release["searchname"]) .
						$this->c->headerOver("New cat:   ") .
						$this->c->primary($newcatname) .
						$this->c->headerOver("Old cat:   ") .
						$this->c->primary($oldcatname) .
						$this->c->headerOver("Group:     ") .
						$this->c->primary($groupname) .
						$this->c->headerOver("Method:    ") .
						$this->c->primary($type . $method) .
						$this->c->headerOver("ReleaseID: ") .
						$this->c->primary($release["releaseid"]);

					if ($type !== "PAR2, ") {
						echo "\n";
					}
				}

				if ($echo == 1) {
					$db = $this->db;
					if ($namestatus == 1) {
						$status = '';
						if ($type == "NFO, ") {
							$status = "isrenamed = 1, iscategorized = 1, proc_nfo = 1,";
						} else if ($type == "PAR2, ") {
							$status = "isrenamed = 1, iscategorized = 1, proc_par2 = 1,";
						} else if ($type == "Filenames, ") {
							$status = "isrenamed = 1, iscategorized = 1, proc_files = 1,";
						}
						$run = $db->queryExec(sprintf("UPDATE releases SET rageid = -1, seriesfull = NULL, season = NULL, episode = NULL, tvtitle = NULL, tvairdate = NULL, imdbid = NULL, musicinfoid = NULL, consoleinfoid = NULL, bookinfoid = NULL, "
								. "anidbid = NULL, preid = %s, searchname = %s, isrenamed = 1, %s categoryid = %d WHERE id = %d", $preid, $db->escapeString(substr($newname, 0, 255)), $status, $determinedcat, $release["releaseid"]));
					} else {
						$run = $db->queryExec(sprintf("UPDATE releases SET rageid = -1, seriesfull = NULL, season = NULL, episode = NULL, tvtitle = NULL, tvairdate = NULL, imdbid = NULL, musicinfoid = NULL, consoleinfoid = NULL, bookinfoid = NULL, "
								. "anidbid = NULL, preid = %s, searchname = %s, iscategorized = 1, categoryid = %d WHERE id = %d", $preid, $db->escapeString(substr($newname, 0, 255)), $determinedcat, $release["releaseid"]));
					}
				}
			}
		}
		$this->done = true;
	}

	// Match a MD5 from the predb to a release.
	public function matchPredbMD5($md5, $release, $echo, $namestatus, $echooutput, $show)
	{
		$db = $this->db;
		$matching = 0;
		$this->category = new Category();
		$this->matched = false;
		$res = $db->queryDirect(sprintf("SELECT title, source FROM predb WHERE md5 = %s", $db->escapeString($md5)));
		$total = $res->rowCount();
		if ($total > 0) {
			foreach ($res as $row) {
				if ($row["title"] !== $release["searchname"]) {
					$determinedcat = $this->category->determineCategory($row["title"], $release["groupid"]);

					if ($echo == 1) {
						$this->matched = true;
						if ($namestatus == 1) {
							$db->queryExec(sprintf("UPDATE releases SET rageid = -1, seriesfull = NULL, season = NULL, episode = NULL, tvtitle = NULL, tvairdate = NULL, imdbid = NULL, musicinfoid = NULL, consoleinfoid = NULL, bookinfoid = NULL, anidbid = NULL, "
									. "searchname = %s, categoryid = %d, isrenamed = 1, iscategorized = 1, dehashstatus = 1 WHERE id = %d", $db->escapeString($row["title"]), $determinedcat, $release["releaseid"]));
						} else {
							$db->queryExec(sprintf("UPDATE releases SET rageid = -1, seriesfull = NULL, season = NULL, episode = NULL, tvtitle = NULL, tvairdate = NULL, imdbid = NULL, musicinfoid = NULL, consoleinfoid = NULL, bookinfoid = NULL, anidbid = NULL, "
									. "searchname = %s, categoryid = %d, dehashstatus = 1 WHERE id = %d", $db->escapeString($row["title"]), $determinedcat, $release["releaseid"]));
						}
					}

					if ($echooutput && $show === 1) {
						$this->updateRelease($release, $row["title"], $method = "predb md5 release name: " . $row["source"], $echo, "MD5, ", $namestatus, $show);
					}
					$matching++;
				}
			}
		} else {
			$db->queryExec(sprintf("UPDATE releases SET dehashstatus = %d - 1 WHERE id = %d", $release['dehashstatus'], $release['releaseid']));
		}
		return $matching;
	}

	//  Check the array using regex for a clean name.
	public function checkName($release, $echo, $type, $namestatus, $show, $preid = false)
	{
		// Get pre style name from releases.name
		$matches = '';
		if (preg_match_all('/([\w\(\)]+[\s\._-]([\w\(\)]+[\s\._-])+[\w\(\)]+-\w+)/', $release['textstring'], $matches)) {
			foreach ($matches as $match) {
				foreach ($match as $val) {
					$title = $this->db->queryOneRow("SELECT title, id from predb WHERE title = " . $this->db->escapeString(trim($val)));
					if (isset($title['title'])) {
						$this->cleanerName = $title['title'];
						if (!empty($this->cleanerName)) {
							$this->updateRelease($release, $title['title'], $method = "preDB: Match", $echo, $type, $namestatus, $show, $title['id']);
							continue;
						}
					}
				}
			}
		}

		// if processing preid on filename, do not continue
		if ($preid === true) {
			return false;
		}

		if ($type == "PAR2, ") {
			$this->fileCheck($release, $echo, $type, $namestatus, $show);
		} else {
			// Just for NFOs.
			if ($type == "NFO, ") {
				$this->nfoCheckTV($release, $echo, $type, $namestatus, $show);
				$this->nfoCheckMov($release, $echo, $type, $namestatus, $show);
				$this->nfoCheckMus($release, $echo, $type, $namestatus, $show);
				$this->nfoCheckTY($release, $echo, $type, $namestatus, $show);
				$this->nfoCheckG($release, $echo, $type, $namestatus, $show);
			}
			// Just for filenames.
			if ($type == "Filenames, ") {
				$this->fileCheck($release, $echo, $type, $namestatus, $show);
			}
			$this->tvCheck($release, $echo, $type, $namestatus, $show);
			$this->movieCheck($release, $echo, $type, $namestatus, $show);
			$this->gameCheck($release, $echo, $type, $namestatus, $show);
			$this->appCheck($release, $echo, $type, $namestatus, $show);
		}
		// The release didn't match so set proc_nfo = 1 so it doesn't get rechecked. Also allows removeCrapReleases to run extra things on the release.
		if ($namestatus == 1 && $this->matched === false && $type == "NFO, ") {
			$db = $this->db;
			$db->queryExec(sprintf("UPDATE releases SET proc_nfo = 1 WHERE id = %d", $release["releaseid"]));
		}
		// The release didn't match so set proc_files = 1 so it doesn't get rechecked. Also allows removeCrapReleases to run extra things on the release.
		else if ($namestatus == 1 && $this->matched === false && $type == "Filenames, ") {
			$db = $this->db;
			$db->queryExec(sprintf("UPDATE releases SET proc_files = 1 WHERE id = %d", $release["releaseid"]));
		}
		// The release didn't match so set proc_par2 = 1 so it doesn't get rechecked. Also allows removeCrapReleases to run extra things on the release.
		else if ($namestatus == 1 && $this->matched === false && $type == "PAR2, ") {
			$db = $this->db;
			$db->queryExec(sprintf("UPDATE releases SET proc_par2 = 1 WHERE id = %d", $release["releaseid"]));
		}
		return $this->matched;
	}

	//  Look for a TV name.
	public function tvCheck($release, $echo, $type, $namestatus, $show)
	{
		$result = '';
		if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w[-\w.\',;& ]+((s\d{1,2}[._ -]?[bde]\d{1,2})|\d{1,2}x\d{2}|ep[._ -]?\d{2})[-\w.\',;.()]+(BD(-?(25|50|RIP))?|Blu-?Ray ?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|[HPS]D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |S?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)[._ -][-\w.\',;& ]+\w/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["0"], $method = "tvCheck: Title.SxxExx.Text.source.group", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w[-\w.\',;& ]+((s\d{1,2}[._ -]?[bde]\d{1,2})|\d{1,2}x\d{2}|ep[._ -]?\d{2})[-\w.\',;& ]+((19|20)\d\d)[-\w.\',;& ]+\w/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["0"], $method = "tvCheck: Title.SxxExx.Text.year.group", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w[-\w.\',;& ]+((s\d{1,2}[._ -]?[bde]\d{1,2})|\d{1,2}x\d{2}|ep[._ -]?\d{2})[-\w.\',;& ]+(480|720|1080)[ip][._ -](BD(-?(25|50|RIP))?|Blu-?Ray ?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|[HPS]D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |S?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)[._ -](DivX|[HX][._ -]?264|MPEG2|XviD(HD)?|WMV)[-\w.\',;& ]+\w/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["0"], $method = "tvCheck: Title.SxxExx.Text.resolution.source.vcodec.group", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w[-\w.\',;& ]+((s\d{1,2}[._ -]?[bde]\d{1,2})|\d{1,2}x\d{2}|ep[._ -]?\d{2})[._ -](BD(-?(25|50|RIP))?|Blu-?Ray ?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|[HPS]D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |S?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)[._ -](DivX|[HX][._ -]?264|MPEG2|XviD(HD)?|WMV)[-\w.\',;& ]+\w/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["0"], $method = "tvCheck: Title.SxxExx.source.vcodec.group", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w[-\w.\',;& ]+((s\d{1,2}[._ -]?[bde]\d{1,2})|\d{1,2}x\d{2}|ep[._ -]?\d{2})[._ -](AAC( LC)?|AC-?3|DD5([._ -]1)?|(A_)?DTS-?(HD)?|Dolby( ?TrueHD)?|MP3|TrueHD)[._ -](BD(-?(25|50|RIP))?|Blu-?Ray ?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|[HPS]D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |S?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)[._ -](480|720|1080)[ip][._ -](DivX|[HX][._ -]?264|MPEG2|XviD(HD)?|WMV)[-\w.\',;& ]+\w/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["0"], $method = "tvCheck: Title.SxxExx.acodec.source.res.vcodec.group", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w[-\w.\',;& ]+((s\d{1,2}[._ -]?[bde]\d{1,2})|\d{1,2}x\d{2}|ep[._ -]?\d{2})[-\w.\',;& ]+((19|20)\d\d)[-\w.\',;& ]+\w/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["0"], $method = "tvCheck: Title.SxxExx.resolution.source.vcodec.group", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w[-\w.\',;& ]+((19|20)\d\d)[._ -]((s\d{1,2}[._ -]?[bde]\d{1,2})|\d{1,2}x\d{2}|ep[._ -]?\d{2})[._ -](BD(-?(25|50|RIP))?|Blu-?Ray ?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|[HPS]D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |S?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)[-\w.\',;& ]+\w/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["0"], $method = "tvCheck: Title.year.###(season/episode).source.group", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w(19|20)\d\d[._ -]\d{2}[._ -]\d{2}[._ -](IndyCar|NBA|NCW(T|Y)S|NNS|NSCS?)([._ -](19|20)\d\d)?[-\w.\',;& ]+\w/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["0"], $method = "tvCheck: Sports", $echo, $type, $namestatus, $show);
		}
	}

	//  Look for a movie name.
	public function movieCheck($release, $echo, $type, $namestatus, $show)
	{
		$result = '';
		if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w[-\w.\',;& ]+((19|20)\d\d)[-\w.\',;& ]+(480|720|1080)[ip][._ -](DivX|[HX][._ -]?264|MPEG2|XviD(HD)?|WMV)[-\w.\',;& ]+\w/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["0"], $method = "movieCheck: Title.year.Text.res.vcod.group", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w[-\w.\',;& ]+((19|20)\d\d)[._ -](BD(-?(25|50|RIP))?|Blu-?Ray ?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|[HPS]D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |S?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)[._ -](DivX|[HX][._ -]?264|MPEG2|XviD(HD)?|WMV)[._ -](480|720|1080)[ip][-\w.\',;& ]+\w/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["0"], $method = "movieCheck: Title.year.source.vcodec.res.group", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w[-\w.\',;& ]+((19|20)\d\d)[._ -](BD(-?(25|50|RIP))?|Blu-?Ray ?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|[HPS]D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |S?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)[._ -](DivX|[HX][._ -]?264|MPEG2|XviD(HD)?|WMV)[._ -](AAC( LC)?|AC-?3|DD5([._ -]1)?|(A_)?DTS-?(HD)?|Dolby( ?TrueHD)?|MP3|TrueHD)[-\w.\',;& ]+\w/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["0"], $method = "movieCheck: Title.year.source.vcodec.acodec.group", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w[-\w.\',;& ]+(Brazilian|Chinese|Croatian|Danish|Deutsch|Dutch|Estonian|English|Finnish|Flemish|Francais|French|German|Greek|Hebrew|Icelandic|Italian|Japenese|Japan|Japanese|Korean|Latin|Nordic|Norwegian|Polish|Portuguese|Russian|Serbian|Slovenian|Swedish|Spanisch|Spanish|Thai|Turkish)[._ -](AAC( LC)?|AC-?3|DD5([._ -]1)?|(A_)?DTS-?(HD)?|Dolby( ?TrueHD)?|MP3|TrueHD)[._ -](BD(-?(25|50|RIP))?|Blu-?Ray ?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|[HPS]D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |S?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)[._ -](DivX|[HX][._ -]?264|MPEG2|XviD(HD)?|WMV)[-\w.\',;& ]+\w/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["0"], $method = "movieCheck: Title.year.language.acodec.source.vcodec.group", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w[-\w.\',;& ]+((19|20)\d\d)[._ -](480|720|1080)[ip][._ -](BD(-?(25|50|RIP))?|Blu-?Ray ?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|[HPS]D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |S?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)[._ -](AAC( LC)?|AC-?3|DD5([._ -]1)?|(A_)?DTS-?(HD)?|Dolby( ?TrueHD)?|MP3|TrueHD)[._ -](DivX|[HX][._ -]?264|MPEG2|XviD(HD)?|WMV)[-\w.\',;& ]+\w/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["0"], $method = "movieCheck: Title.year.resolution.source.acodec.vcodec.group", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w[-\w.\',;& ]+((19|20)\d\d)[._ -](480|720|1080)[ip][._ -](BD(-?(25|50|RIP))?|Blu-?Ray ?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|[HPS]D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |S?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)[._ -](DivX|[HX][._ -]?264|MPEG2|XviD(HD)?|WMV)[-\w.\',;& ]+\w/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["0"], $method = "movieCheck: Title.year.resolution.source.vcodec.group", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w[-\w.\',;& ]+((19|20)\d\d)[._ -](BD(-?(25|50|RIP))?|Blu-?Ray ?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|[HPS]D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |S?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)[._ -](480|720|1080)[ip][._ -](AAC( LC)?|AC-?3|DD5([._ -]1)?|(A_)?DTS-?(HD)?|Dolby( ?TrueHD)?|MP3|TrueHD)[._ -](DivX|[HX][._ -]?264|MPEG2|XviD(HD)?|WMV)[-\w.\',;& ]+\w/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["0"], $method = "movieCheck: Title.year.source.resolution.acodec.vcodec.group", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w[-\w.\',;& ]+((19|20)\d\d)[._ -](480|720|1080)[ip][._ -](AAC( LC)?|AC-?3|DD5([._ -]1)?|(A_)?DTS-?(HD)?|Dolby( ?TrueHD)?|MP3|TrueHD)[._ -](DivX|[HX][._ -]?264|MPEG2|XviD(HD)?|WMV)[-\w.\',;& ]+\w/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["0"], $method = "movieCheck: Title.year.resolution.acodec.vcodec.group", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/[-\w.\',;& ]+((19|20)\d\d)[._ -](BD(-?(25|50|RIP))?|Blu-?Ray ?(3D)?|BR(RIP)?|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|[HPS]D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |S?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)[._ -](480|720|1080)[ip][._ -][-\w.\',;& ]+\w/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["0"], $method = "movieCheck: Title.year.source.res.group", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w[-\w.\',;& ]+((19|20)\d\d)[._ -][-\w.\',;& ]+[._ -](BD(-?(25|50|RIP))?|Blu-?Ray ?(3D)?|BR(RIP)?|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|[HPS]D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |S?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)[._ -](DivX|[HX][._ -]?264|MPEG2|XviD(HD)?|WMV)[-\w.\',;& ]+\w/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["0"], $method = "movieCheck: Title.year.eptitle.source.vcodec.group", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w[-\w.\',;& ]+(480|720|1080)[ip][._ -](BD(-?(25|50|RIP))?|Blu-?Ray ?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|[HPS]D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |S?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)[._ -](AAC( LC)?|AC-?3|DD5([._ -]1)?|(A_)?DTS-?(HD)?|Dolby( ?TrueHD)?|MP3|TrueHD)[._ -](DivX|[HX][._ -]?264|MPEG2|XviD(HD)?|WMV)[-\w.\',;& ]+\w/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["0"], $method = "movieCheck: Title.resolution.source.acodec.vcodec.group", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w[-\w.\',;& ]+(480|720|1080)[ip][._ -](AAC( LC)?|AC-?3|DD5([._ -]1)?|(A_)?DTS-?(HD)?|Dolby( ?TrueHD)?|MP3|TrueHD)[-\w.\',;& ]+(BD(-?(25|50|RIP))?|Blu-?Ray ?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|[HPS]D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |S?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)[._ -]((19|20)\d\d)[-\w.\',;& ]+\w/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["0"], $method = "movieCheck: Title.resolution.acodec.eptitle.source.year.group", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w[-\w.\',;& ]+(Brazilian|Chinese|Croatian|Danish|Deutsch|Dutch|Estonian|English|Finnish|Flemish|Francais|French|German|Greek|Hebrew|Icelandic|Italian|Japenese|Japan|Japanese|Korean|Latin|Nordic|Norwegian|Polish|Portuguese|Russian|Serbian|Slovenian|Swedish|Spanisch|Spanish|Thai|Turkish)[._ -]((19|20)\d\d)[._ -](AAC( LC)?|AC-?3|DD5([._ -]1)?|(A_)?DTS-?(HD)?|Dolby( ?TrueHD)?|MP3|TrueHD)[._ -](BD(-?(25|50|RIP))?|Blu-?Ray ?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|[HPS]D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |S?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)[-\w.\',;& ]+\w/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["0"], $method = "movieCheck: Title.language.year.acodec.src", $echo, $type, $namestatus, $show);
		}
	}

	//  Look for a game name.
	public function gameCheck($release, $echo, $type, $namestatus, $show)
	{
		$result = '';
		if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w[-\w.\',;& ]+(ASIA|DLC|EUR|GOTY|JPN|KOR|MULTI\d{1}|NTSCU?|PAL|RF|Region[._ -]?Free|USA|XBLA)[._ -](DLC[._ -]Complete|FRENCH|GERMAN|MULTI\d{1}|PROPER|PSN|READ[._ -]?NFO|UMD)?[._ -]?(GC|NDS|NGC|PS3|PSP|WII|XBOX(360)?)[-\w.\',;& ]+\w/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["0"], $method = "gameCheck: Videogames 1", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w[-\w.\',;& ]+(GC|NDS|NGC|PS3|WII|XBOX(360)?)[._ -](DUPLEX|iNSOMNi|OneUp|STRANGE|SWAG|SKY)[-\w.\',;& ]+\w/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["0"], $method = "gameCheck: Videogames 2", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w[\w.\',;-].+-OUTLAWS/i', $release["textstring"], $result)) {
			$result = str_replace("OUTLAWS", "PC GAME OUTLAWS", $result['0']);
			$this->updateRelease($release, $result["0"], $method = "gameCheck: PC Games -OUTLAWS", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w[\w.\',;-].+\-ALiAS/i', $release["textstring"], $result)) {
			$newresult = str_replace("-ALiAS", " PC GAME ALiAS", $result['0']);
			$this->updateRelease($release, $newresult, $method = "gameCheck: PC Games -ALiAS", $echo, $type, $namestatus, $show);
		}
	}

	//  Look for a app name.
	public function appCheck($release, $echo, $type, $namestatus, $show)
	{
		$result = '';
		if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w[-\w.\',;& ]+(\d{1,10}|Linux|UNIX)[._ -](RPM)?[._ -]?(X64)?[._ -]?(Incl)[._ -](Keygen)[-\w.\',;& ]+\w/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["0"], $method = "appCheck: Apps 1", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w[-\w.\',;& ]+\d{1,8}[._ -](winall-freeware)[-\w.\',;& ]+\w/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["0"], $method = "appCheck: Apps 2", $echo, $type, $namestatus, $show);
		}
	}

	/*
	 * Just for NFOS.
	 */

	//  TV.
	public function nfoCheckTV($release, $echo, $type, $namestatus, $show)
	{
		$result = '';
		if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/:\s*.*[\\\\\/]([A-Z0-9].+?S\d+[.-_ ]?[ED]\d+.+?)\.\w{2,}\s+/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["1"], $method = "nfoCheck: Generic TV 1", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/(?:(\:\s{1,}))(.+?S\d{1,3}[.-_ ]?[ED]\d{1,3}.+?)(\s{2,}|\r|\n)/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["2"], $method = "nfoCheck: Generic TV 2", $echo, $type, $namestatus, $show);
		}
	}

	//  Movies.
	public function nfoCheckMov($release, $echo, $type, $namestatus, $show)
	{
		$result = '';
		if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/(?:(\:\s{1,}))(.+?(19|20)\d\d.+?(BDRip|bluray|DVD(R|Rip)?|XVID).+?)(\s{2,}|\r|\n)/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["2"], $method = "nfoCheck: Generic Movies 1", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/(?:(\s{2,}))(.+?[\.\-_ ](19|20)\d\d.+?(BDRip|bluray|DVD(R|Rip)?|XVID).+?)(\s{2,}|\r|\n)/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["2"], $method = "nfoCheck: Generic Movies 2", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/(?:(\s{2,}))(.+?[\.\-_ ](NTSC|MULTi).+?(MULTi|DVDR)[\.\-_ ].+?)(\s{2,}|\r|\n)/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["2"], $method = "nfoCheck: Generic Movies 3", $echo, $type, $namestatus, $show);
		}
	}

	//  Music.
	public function nfoCheckMus($release, $echo, $type, $namestatus, $show)
	{
		$result = '';
		if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/(?:\s{2,})(.+?-FM-\d{2}-\d{2})/i', $release["textstring"], $result)) {
			$newname = str_replace('-FM-', '-FM-Radio-MP3-', $result["1"]);
			$this->updateRelease($release, $newname, $method = "nfoCheck: Music FM RADIO", $echo, $type, $namestatus, $show);
		}
	}

	//  Title (year)
	public function nfoCheckTY($release, $echo, $type, $namestatus, $show)
	{
		$result = '';
		if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/(\w[-\w`~!@#$%^&*()_+={}|"<>?\[\]\\;\',.\/ ]+\s?\((19|20)\d\d\))/i', $release["textstring"], $result) && !preg_match('/\.pdf|Audio ?Book/i', $release["textstring"])) {
			$releasename = $result[0];
			if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/(idiomas|lang|language|langue|sprache).*?\b(Brazilian|Chinese|Croatian|Danish|DE|Deutsch|Dutch|Estonian|ES|English|Englisch|Finnish|Flemish|Francais|French|FR|German|Greek|Hebrew|Icelandic|Italian|Japenese|Japan|Japanese|Korean|Latin|Nordic|Norwegian|Polish|Portuguese|Russian|Serbian|Slovenian|Swedish|Spanisch|Spanish|Thai|Turkish)\b/i', $release["textstring"], $result)) {
				if ($result[2] == 'DE') {
					$result[2] = 'DUTCH';
				} else if ($result[2] == 'Englisch') {
					$result[2] = 'English';
				} else if ($result[2] == 'FR') {
					$result[2] = 'FRENCH';
				} else if ($result[2] == 'ES') {
					$result[2] = 'SPANISH';
				}
				$releasename = $releasename . "." . $result[2];
			}
			if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/(frame size|res|resolution|video|video res).*?(272|336|480|494|528|608|640|\(640|688|704|720x480|816|820|1080|1 080|1280 @|1280|1920|1 920|1920x1080)/i', $release["textstring"], $result)) {
				if ($result[2] == '272') {
					$result[2] = '272p';
				} else if ($result[2] == '336') {
					$result[2] = '480p';
				} else if ($result[2] == '480') {
					$result[2] = '480p';
				} else if ($result[2] == '494') {
					$result[2] = '480p';
				} else if ($result[2] == '608') {
					$result[2] = '480p';
				} else if ($result[2] == '640') {
					$result[2] = '480p';
				} else if ($result[2] == '\(640') {
					$result[2] = '480p';
				} else if ($result[2] == '688') {
					$result[2] = '480p';
				} else if ($result[2] == '704') {
					$result[2] = '480p';
				} else if ($result[2] == '720x480') {
					$result[2] = '480p';
				} else if ($result[2] == '816') {
					$result[2] = '1080p';
				} else if ($result[2] == '820') {
					$result[2] = '1080p';
				} else if ($result[2] == '1080') {
					$result[2] = '1080p';
				} else if ($result[2] == '1280x720') {
					$result[2] = '720p';
				} else if ($result[2] == '1280 @') {
					$result[2] = '720p';
				} else if ($result[2] == '1280') {
					$result[2] = '720p';
				} else if ($result[2] == '1920') {
					$result[2] = '1080p';
				} else if ($result[2] == '1 920') {
					$result[2] = '1080p';
				} else if ($result[2] == '1 080') {
					$result[2] = '1080p';
				} else if ($result[2] == '1920x1080') {
					$result[2] = '1080p';
				}
				$releasename = $releasename . "." . $result[2];
			}
			if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/(largeur|width).*?(640|\(640|688|704|720|1280 @|1280|1920|1 920)/i', $release["textstring"], $result)) {
				if ($result[2] == '640') {
					$result[2] = '480p';
				} else if ($result[2] == '\(640') {
					$result[2] = '480p';
				} else if ($result[2] == '688') {
					$result[2] = '480p';
				} else if ($result[2] == '704') {
					$result[2] = '480p';
				} else if ($result[2] == '1280 @') {
					$result[2] = '720p';
				} else if ($result[2] == '1280') {
					$result[2] = '720p';
				} else if ($result[2] == '1920') {
					$result[2] = '1080p';
				} else if ($result[2] == '1 920') {
					$result[2] = '1080p';
				} else if ($result[2] == '720') {
					$result[2] = '480p';
				}
				$releasename = $releasename . "." . $result[2];
			}
			if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/source.*?\b(BD(-?(25|50|RIP))?|Blu-?Ray ?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|[HPS]D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |S?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)\b/i', $release["textstring"], $result)) {
				if ($result[1] == 'BD') {
					$result[1] = 'Bluray.x264';
				} else if ($result[1] == 'CAMRIP') {
					$result[1] = 'CAM';
				} else if ($result[1] == 'DBrip') {
					$result[1] = 'BDRIP';
				} else if ($result[1] == 'DVD R1') {
					$result[1] = 'DVD';
				} else if ($result[1] == 'HD') {
					$result[1] = 'HDTV';
				} else if ($result[1] == 'NTSC') {
					$result[1] = 'DVD';
				} else if ($result[1] == 'PAL') {
					$result[1] = 'DVD';
				} else if ($result[1] == 'Ripped ') {
					$result[1] = 'DVDRIP';
				} else if ($result[1] == 'VOD') {
					$result[1] = 'DVD';
				}
				$releasename = $releasename . "." . $result[1];
			}
			if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/(codec|codec name|codec code|format|MPEG-4 Visual|original format|res|resolution|video|video codec|video format|video res|tv system|type|writing library).*?\b(AVC|AVI|DBrip|DIVX|\(Divx|DVD|[HX][._ -]?264|NTSC|PAL|WMV|XVID)\b/i', $release["textstring"], $result)) {
				if ($result[2] == 'AVI') {
					$result[2] = 'DVDRIP';
				} else if ($result[2] == 'DBrip') {
					$result[2] = 'BDRIP';
				} else if ($result[2] == '(Divx') {
					$result[2] = 'DIVX';
				} else if ($result[2] == 'h.264') {
					$result[2] = 'H264';
				} else if ($result[2] == 'MPEG-4 Visual') {
					$result[2] = 'x264';
				} else if ($result[1] == 'NTSC') {
					$result[1] = 'DVD';
				} else if ($result[1] == 'PAL') {
					$result[1] = 'DVD';
				} else if ($result[2] == 'x.264') {
					$result[2] = 'x264';
				}
				$releasename = $releasename . "." . $result[2];
			}
			if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/(audio|audio format|codec|codec name|format).*?\b(0x0055 MPEG-1 Layer 3|AAC( LC)?|AC-?3|\(AC3|DD5(.1)?|(A_)?DTS-?(HD)?|Dolby(\s?TrueHD)?|TrueHD|FLAC|MP3)\b/i', $release["textstring"], $result)) {
				if ($result[2] == '0x0055 MPEG-1 Layer 3') {
					$result[2] = 'MP3';
				} else if ($result[2] == 'AC-3') {
					$result[2] = 'AC3';
				} else if ($result[2] == '(AC3') {
					$result[2] = 'AC3';
				} else if ($result[2] == 'AAC LC') {
					$result[2] = 'AAC';
				} else if ($result[2] == 'A_DTS') {
					$result[2] = 'DTS';
				} else if ($result[2] == 'DTS-HD') {
					$result[2] = 'DTS';
				} else if ($result[2] == 'DTSHD') {
					$result[2] = 'DTS';
				}
				$releasename = $releasename . "." . $result[2];
			}
			$releasename = $releasename . "-NoGroup";
			$this->updateRelease($release, $releasename, $method = "nfoCheck: Title (Year)", $echo, $type, $namestatus, $show);
		}
	}

	//  Games.
	public function nfoCheckG($release, $echo, $type, $namestatus, $show)
	{
		if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/ALiAS|BAT-TEAM|\FAiRLiGHT|Game Type|Glamoury|HI2U|iTWINS|JAGUAR|LARGEISO|MAZE|MEDIUMISO|nERv|PROPHET|PROFiT|PROCYON|RELOADED|REVOLVER|ROGUE|ViTALiTY/i', $release["textstring"])) {
			$result = '';
			if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w[\w.+&*\/\()\',;: -]+\(c\)[-\w.\',;& ]+\w/i', $release["textstring"], $result)) {
				$releasename = str_replace(array("(c)", "(C)"), "(GAMES) (c)", $result['0']);
				$this->updateRelease($release, $releasename, $method = "nfoCheck: PC Games (c)", $echo, $type, $namestatus, $show);
			} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w[\w.+&*\/()\',;: -]+\*ISO\*/i', $release["textstring"], $result)) {
				$releasename = str_replace("*ISO*", "*ISO* (PC GAMES)", $result['0']);
				$this->updateRelease($release, $releasename, $method = "nfoCheck: PC Games *ISO*", $echo, $type, $namestatus, $show);
			}
		}
	}

	//  Misc.
	public function nfoCheckMisc($release, $echo, $type, $namestatus, $show)
	{
		if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/Supplier.+?IGUANA/i', $release["textstring"])) {
			$releasename = '';
			$result = '';
			if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w[-\w`~!@#$%^&*()+={}|:"<>?\[\]\\;\',.\/ ]+\s\((19|20)\d\d\)/i', $release["textstring"], $result)) {
				$releasename = $result[0];
			} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\s\[\*\] (English|Dutch|French|German|Spanish)\b/i', $release["textstring"], $result)) {
				$releasename = $releasename . "." . $result[1];
			} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\s\[\*\] (DTS 6[._ -]1|DS 5[._ -]1|DS 2[._ -]0|DS 2[._ -]0 MONO)\b/i', $release["textstring"], $result)) {
				$releasename = $releasename . "." . $result[2];
			} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/Format.+(DVD(5|9|R)?|[HX][._ -]?264)\b/i', $release["textstring"], $result)) {
				$releasename = $releasename . "." . $result[1];
			} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\[(640x.+|1280x.+|1920x.+)\] Resolution\b/i', $release["textstring"], $result)) {
				if ($result[1] == '640x.+') {
					$result[1] = '480p';
				} else if ($result[1] == '1280x.+') {
					$result[1] = '720p';
				} else if ($result[1] == '1920x.+') {
					$result[1] = '1080p';
				}
				$releasename = $releasename . "." . $result[1];
			}
			$result = $releasename . ".IGUANA";
			$this->updateRelease($release, $result, $method = "nfoCheck: IGUANA", $echo, $type, $namestatus, $show);
		}
	}

	/*
	 * Just for filenames.
	 */

	public function fileCheck($release, $echo, $type, $namestatus, $show)
	{
		$result = '';
		if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/^(.+?(x264|XviD)\-TVP)\\\\/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["1"], $method = "fileCheck: TVP", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/^(\\\\|\/)?(.+(\\\\|\/))*(.+?S\d{1,3}[.-_ ]?[ED]\d{1,3}.+)\.(.+)$/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["4"], $method = "fileCheck: Generic TV", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/^(\\\\|\/)?(.+(\\\\|\/))*(.+?([\.\-_ ]\d{4}[\.\-_ ].+?(BDRip|bluray|DVDRip|XVID)).+)\.(.+)$/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["4"], $method = "fileCheck: Generic movie 1", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/^([a-z0-9\.\-_]+(19|20)\d\d[a-z0-9\.\-_]+[\.\-_ ](720p|1080p|BDRip|bluray|DVDRip|x264|XviD)[a-z0-9\.\-_]+)\.[a-z]{2,}$/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["1"], $method = "fileCheck: Generic movie 2", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/(.+?([\.\-_ ](CD|FM)|[\.\-_ ]\dCD|CDR|FLAC|SAT|WEB).+?(19|20)\d\d.+?)\\\\.+/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["1"], $method = "fileCheck: Generic music", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/^(.+?(19|20)\d\d\-([a-z0-9]{3}|[a-z]{2,}|C4))\\\\/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["1"], $method = "fileCheck: music groups", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/.+\\\\(.+\((19|20)\d\d\)\.avi)/i', $release["textstring"], $result)) {
			$newname = str_replace('.avi', ' DVDRip XVID NoGroup', $result["1"]);
			$this->updateRelease($release, $newname, $method = "fileCheck: Movie (year) avi", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/.+\\\\(.+\((19|20)\d\d\)\.iso)/i', $release["textstring"], $result)) {
			$newname = str_replace('.iso', ' DVD NoGroup', $result["1"]);
			$this->updateRelease($release, $newname, $method = "fileCheck: Movie (year) iso", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/^(.+?IMAGESET.+?)\\\\.+/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["1"], $method = "fileCheck: XXX Imagesets", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w[-\w.\',;& ]+1080i[._ -]DD5[._ -]1[._ -]MPEG2-R&C(?=\.ts)/i', $release["textstring"], $result)) {
			$result = str_replace("MPEG2", "MPEG2.HDTV", $result["0"]);
			$this->updateRelease($release, $result, $method = "fileCheck: R&C", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w[-\w.\',;& ]+((s\d{1,2}[._ -]?[bde]\d{1,2})|\d{1,2}x\d{2}|ep[._ -]?\d{2})[._ -](480|720|1080)[ip][._ -](BD(-?(25|50|RIP))?|Blu-?Ray ?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|[HPS]D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |S?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)[._ -]nSD[._ -](DivX|[HX][._ -]?264|MPEG2|XviD(HD)?|WMV)[._ -]NhaNC3[-\w.\',;& ]+\w/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["0"], $method = "fileCheck: NhaNc3", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\wtvp-[\w.\-\',;]+((s\d{1,2}[._ -]?[bde]\d{1,2})|\d{1,2}x\d{2}|ep[._ -]?\d{2})[._ -](720p|1080p|xvid)(?=\.(avi|mkv))/i', $release["textstring"], $result)) {
			$result = str_replace("720p", "720p.HDTV.X264", $result['0']);
			$result = str_replace("1080p", "1080p.Bluray.X264", $result['0']);
			$result = str_replace("xvid", "XVID.DVDrip", $result['0']);
			$this->updateRelease($release, $result, $method = "fileCheck: tvp", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w[-\w.\',;& ]+\d{3,4}\.hdtv-lol\.(avi|mp4|mkv|ts|nfo|nzb)/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["0"], $method = "fileCheck: Title.211.hdtv-lol.extension", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w[-\w.\',;& ]+-S\d{1,2}[EX]\d{1,2}-XVID-DL.avi/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["0"], $method = "fileCheck: Title-SxxExx-XVID-DL.avi", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\S.*[\w.\-\',;]+\s\-\ss\d{2}[ex]\d{2}\s\-\s[\w.\-\',;].+\./i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["0"], $method = "fileCheck: Title - SxxExx - Eptitle", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w.+?\)\.nds/i', $release["textstring"], $result)) {
			$this->updateRelease($release, $result["0"], $method = "fileCheck: ).nds Nintendo DS", $echo, $type, $namestatus, $show);
		} else if ($this->done === false && $this->relid !== $release["releaseid"] && preg_match('/\w.+?\.(pdf|html|epub|mobi|azw)/i', $release["textstring"], $result)) {
			$result = str_replace("." . $result["1"], " (" . $result["1"] . ")", $result['0']);
			$this->updateRelease($release, $result, $method = "fileCheck: EBook", $echo, $type, $namestatus, $show);
		}
	}

}
