<?php
require_once nZEDb_LIB . 'Util.php';
require_once nZEDb_LIBS . 'rarinfo/archiveinfo.php';
require_once nZEDb_LIBS . 'rarinfo/par2info.php';
require_once nZEDb_LIBS . 'rarinfo/zipinfo.php';

class PostProcess
{
	public function __construct($echooutput = false)
	{
		$s = new Sites();
		$this->site = $s->get();
		$this->addqty = (!empty($this->site->maxaddprocessed)) ? $this->site->maxaddprocessed : 25;
		$this->addpar2 = ($this->site->addpar2 == '0') ? false : true;
		$this->audSavePath = nZEDb_WWW . 'covers/audiosample/';
		$this->consoleTools = new ConsoleTools();
		$this->db = new DB();
		$this->DEBUG_ECHO = ($this->site->debuginfo == '0') ? false : true;
		if (defined('DEBUG_ECHO') && DEBUG_ECHO == true) {
			$this->DEBUG_ECHO = true;
		}
		$this->echooutput = $echooutput;
		$this->ffmpeg_duration = (!empty($this->site->ffmpeg_duration)) ? $this->site->ffmpeg_duration : 5;
		$this->ffmpeg_image_time = (!empty($this->site->ffmpeg_image_time)) ? $this->site->ffmpeg_image_time : 5;
		$this->filesadded = 0;
		$this->maxsize = (!empty($this->site->maxsizetopostprocess)) ? $this->site->maxsizetopostprocess : 100;
		$this->partsqty = (!empty($this->site->maxpartsprocessed)) ? $this->site->maxpartsprocessed : 3;
		$this->passchkattempts = (!empty($this->site->passchkattempts)) ? $this->site->passchkattempts : 1;
		$this->password = $this->nonfo = false;
		$this->processAudioSample = ($this->site->processaudiosample == '0') ? false : true;
		$this->segmentstodownload = (!empty($this->site->segmentstodownload)) ? $this->site->segmentstodownload : 2;
		$this->tmpPath = $this->site->tmpunrarpath;
		if (substr($this->tmpPath, -strlen('/')) != '/') {
			$this->tmpPath = $this->tmpPath . '/';
		}

		$this->audiofileregex = '\.(AAC|AIFF|APE|AC3|ASF|DTS|FLAC|MKA|MKS|MP2|MP3|RA|OGG|OGM|W64|WAV|WMA)';
		$this->ignorebookregex = '/\b(epub|lit|mobi|pdf|sipdf|html)\b.*\.rar(?!.{20,})/i';
		$this->supportfiles = '/\.(vol\d{1,3}\+\d{1,3}|par2|srs|sfv|nzb';
		$this->videofileregex = '\.(AVI|F4V|IFO|M1V|M2V|M4V|MKV|MOV|MP4|MPEG|MPG|MPGV|MPV|OGV|QT|RM|RMVB|TS|VOB|WMV)';

		$sigs = array(array('00', '00', '01', 'BA'), array('00', '00', '01', 'B3'), array('00', '00', '01', 'B7'), array('1A', '45', 'DF', 'A3'), array('01', '00', '09', '00'), array('30', '26', 'B2', '75'), array('A6', 'D9', '00', 'AA'));
		$sigstr = '';
		foreach ($sigs as $sig) {
			$str = '';
			foreach ($sig as $s) {
				$str = $str . "\x$s";
			}
			$sigstr = $sigstr . '|' . $str;
		}
		$sigstr1 = "/^ftyp|mp4|^riff|avi|matroska|.rec|.rmf|^oggs|moov|dvd|^0&²u|free|mdat|pnot|skip|wide$sigstr/i";
		$this->sigregex = $sigstr1;
		$this->c = new ColorCLI();
	}

	public function processAll($nntp)
	{
		if (!isset($nntp)) {
			exit($this->c->error("Not connected to usenet(postprocess->processAll).\n"));
		}

		$this->processPredb($nntp);
		$this->processAdditional($releaseToWork = '', $id = '', $gui = false, $groupID = '', $nntp);
		$this->processNfos($releaseToWork = '', $nntp);
		$this->processMovies($releaseToWork = '');
		$this->processMusic();
		$this->processGames();
		$this->processAnime();
		$this->processTv($releaseToWork = '');
		$this->processBooks();
	}

	// Lookup anidb if enabled - always run before tvrage.
	public function processAnime()
	{
		if ($this->site->lookupanidb == 1) {
			$anidb = new AniDB($this->echooutput);
			$anidb->animetitlesUpdate();
			$anidb->processAnimeReleases();
		}
	}

	// Process books using amazon.com.
	public function processBooks()
	{
		if ($this->site->lookupbooks != 0) {
			$books = new Books($this->echooutput);
			$books->processBookReleases();
		}
	}

	// Lookup games if enabled.
	public function processGames()
	{
		if ($this->site->lookupgames != 0) {
			$console = new Console($this->echooutput);
			$console->processConsoleReleases();
		}
	}

	// Lookup imdb if enabled.
	public function processMovies($releaseToWork = '')
	{
		if ($this->site->lookupimdb == 1) {
			$movie = new Movie($this->echooutput);
			$movie->processMovieReleases($releaseToWork);
		}
	}

	// Lookup music if enabled.
	public function processMusic()
	{
		if ($this->site->lookupmusic != 0) {
			$music = new Music($this->echooutput);
			$music->processMusicReleases();
		}
	}

	// Process nfo files.
	public function processNfos($releaseToWork = '', $nntp)
	{
		if (!isset($nntp))
			exit($this->c->error("Not connected to usenet(postprocess->processNfos).\n"));

		if ($this->site->lookupnfo == 1) {
			$nfo = new Nfo($this->echooutput);
			$nfo->processNfoFiles($releaseToWork, $this->site->lookupimdb, $this->site->lookuptvrage, $groupID = '', $nntp);
		}
	}

	// Process nfo files.
	public function processAdditionalThreaded($releaseToWork = '', $nntp)
	{
		if (!isset($nntp)) {
			exit($this->c->error("Not connected to usenet(postprocess->processAdditionalThreaded).\n"));
		}

		$this->processAdditional($releaseToWork, $id = '', $gui = false, $groupID = '', $nntp);
	}

	// Fetch titles from predb sites.
	public function processPredb($nntp)
	{
		if (!isset($nntp)) {
			exit($this->c->error("Not connected to usenet(postprocess->processPredb).\n"));
		}

		$predb = new PreDb($this->echooutput);
		$titles = $predb->updatePre();
		$predb->checkPre($nntp);
		if ($titles > 0) {
			$this->doecho($this->c->header('Fetched ' . number_format($titles) . ' new title(s) from predb sources.'));
		}
	}

	// Process all TV related releases which will assign their series/episode/rage data.
	public function processTv($releaseToWork = '')
	{
		if ($this->site->lookuptvrage == 1) {
			$tvrage = new TvRage($this->echooutput);
			$tvrage->processTvReleases($releaseToWork, $this->site->lookuptvrage == 1);
		}
	}

	// Attempt to get a better name from a par2 file and categorize the release.
	public function parsePAR2($messageID, $relID, $groupID, $nntp, $show)
	{
		if (!isset($nntp)) {
			exit($this->c->error("Not connected to usenet(postprocess->parsePAR2).\n"));
		}

		if ($messageID == '') {
			return false;
		}
		$db = $this->db;
		if ($db->dbSystem() == 'mysql') {
			$t = 'UNIX_TIMESTAMP(postdate)';
		} else {
			$t = 'extract(epoch FROM postdate)';
		}

		$quer = $db->queryOneRow('SELECT id, groupid, categoryid, searchname, ' . $t . ' as postdate, id as releaseid  FROM releases WHERE (bitwise & 4) = 0 AND id = ' . $relID);
		if ($quer['categoryid'] != Category::CAT_MISC) {
			return false;
		}

		$groups = new Groups();
		$par2 = $nntp->getMessage($groups->getByNameByID($groupID), $messageID);
		if (PEAR::isError($par2)) {
			$nntp->doQuit();
			$this->site->alternate_nntp == 1 ? $nntp->doConnect_A() : $nntp->doConnect();
			$par2 = $nntp->getMessage($groups->getByNameByID($groupID), $messageID);
			if (PEAR::isError($par2)) {
				$nntp->doQuit();
				return false;
			}
		}

		$par2info = new Par2Info();
		$par2info->setData($par2);
		if ($par2info->error) {
			return false;
		}

		$files = $par2info->getFileList();
		if ($files !== false && count($files) > 0) {
			$namefixer = new NameFixer($this->echooutput);
			$rf = new ReleaseFiles();
			$relfiles = 0;
			$foundname = false;
			foreach ($files as $fileID => $file) {
				if (!array_key_exists('name', $file)) {
					return false;
				}
				// Add to releasefiles.
				if ($this->addpar2 && $relfiles < 11 && $db->queryOneRow(sprintf('SELECT id FROM releasefiles WHERE releaseid = %d AND name = %s', $relID, $this->db->escapeString($file['name']))) === false) {
					if ($rf->add($relID, $file['name'], $file['size'], $quer['postdate'], 0)) {
						$relfiles++;
					}
				}
				$quer['textstring'] = $file['name'];
				//$namefixer->checkName($quer, 1, 'PAR2, ', 1);
				//$stat = $db->queryOneRow('SELECT id FROM releases WHERE (bitwise & 4) = 4 AND id = '.$relID);
				//if ($stat['id'] === $relID)
				if ($namefixer->checkName($quer, 1, 'PAR2, ', 1, $show) === true) {
					$foundname = true;
					break;
				}
			}
			if ($relfiles > 0) {
				$this->debug('Added ' . $relfiles . ' releasefiles from PAR2 for ' . $quer['searchname']);
				$cnt = $db->queryOneRow('SELECT COUNT(releaseid) AS count FROM releasefiles WHERE releaseid = ' . $relID);
				$count = $relfiles;
				if ($cnt !== false && $cnt['count'] > 0) {
					$count = $relfiles + $cnt['count'];
				}
				$db->queryExec(sprintf('UPDATE releases SET rarinnerfilecount = %d where id = %d', $count, $relID));
			}
			if ($foundname === true) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	// Comparison function for usort, for sorting nzb file content.
	public function sortrar($a, $b)
	{
		$pos = 0;
		$af = $bf = false;
		$a = preg_replace('/\d+[- ._]?(\/|\||[o0]f)[- ._]?\d+?(?![- ._]\d)/i', ' ', $a['title']);
		$b = preg_replace('/\d+[- ._]?(\/|\||[o0]f)[- ._]?\d+?(?![- ._]\d)/i', ' ', $b['title']);

		if (preg_match("/\.(part\d+|r\d+)(\.rar)*($|[ \")\]-])/i", $a))
			$af = true;
		if (preg_match("/\.(part\d+|r\d+)(\.rar)*($|[ \")\]-])/i", $b))
			$bf = true;

		if (!$af && preg_match("/\.(rar)($|[ \")\]-])/i", $a)) {
			$a = preg_replace('/\.(rar)(?:$|[ \")\]-])/i', '.*rar', $a);
			$af = true;
		}
		if (!$bf && preg_match("/\.(rar)($|[ \")\]-])/i", $b)) {
			$b = preg_replace('/\.(rar)(?:$|[ \")\]-])/i', '.*rar', $b);
			$bf = true;
		}

		if (!$af && !$bf)
			return strnatcasecmp($a, $b);
		else if (!$bf)
			return -1;
		else if (!$af)
			return 1;

		if ($af && $bf)
			$pos = strnatcasecmp($a, $b);
		else if ($af)
			$pos = -1;
		else if ($bf)
			$pos = 1;

		return $pos;
	}

	// Sort a multidimensional array using one subkey.
	public function subval_sort($a, $subkey)
	{
		foreach ($a as $k => $v)
			$b[$k] = strtolower($v[$subkey]);

		natcasesort($b);

		foreach ($b as $k => $v)
			$c[] = $a[$k];

		return $c;
	}

	// Check for passworded releases, RAR contents and Sample/Media info.
	public function processAdditional($releaseToWork = '', $id = '', $gui = false, $groupID = '', $nntp)
	{
		if (!isset($nntp))
			exit($this->c->error("Not connected to usenet(postprocess->processAdditional).\n"));

		$like = 'ILIKE';
		if ($this->db->dbSystem() == 'mysql')
			$like = 'LIKE';

		// not sure if ugo ever implemented this in the ui, other that his own
		if ($gui) {
			$ok = false;
			while (!$ok) {
				usleep(mt_rand(10, 300));
				$this->db->setAutoCommit(false);
				$ticket = $this->db->queryOneRow('SELECT value  FROM site WHERE setting ' . $like . " 'nextppticket'");
				$ticket = $ticket['value'];
				$upcnt = $this->db->queryExec(sprintf("UPDATE site SET value = %d WHERE setting %s 'nextppticket' AND value = %d", $ticket + 1, $like, $ticket));
				if (count($upcnt) == 1) {
					$ok = true;
					$this->db->Commit();
				} else
					$this->db->Rollback();
			}
			$this->db->setAutoCommit(true);
			$sleep = 1;
			$delay = 100;

			do {
				sleep($sleep);
				$serving = $this->db->queryOneRow('SELECT * FROM site WHERE setting ' . $like . " 'currentppticket1'");
				$time = strtotime($serving['updateddate']);
				$serving = $serving['value'];
				$sleep = min(max(($time + $delay - time()) / 5, 2), 15);
			} while ($serving > $ticket && ($time + $delay + 5 * ($ticket - $serving)) > time());
		}

		$groupid = $groupID == '' ? '' : 'AND groupid = ' . $groupID;
		// Get out all releases which have not been checked more than max attempts for password.
		if ($id != '')
			$result = $this->db->queryDirect('SELECT r.id, r.guid, r.name, c.disablepreview, r.size, r.groupid, r.nfostatus, r.completion, r.categoryid FROM releases r LEFT JOIN category c ON c.id = r.categoryid WHERE r.id = ' . $id);
		else {
			$result = $totresults = 0;
			if ($releaseToWork == '') {
				$i = -1;
				$tries = (5 * -1) - 1;
				while (($totresults != $this->addqty) && ($i >= $tries)) {
					$result = $this->db->queryDirect(sprintf('SELECT r.id, r.guid, r.name, c.disablepreview, r.size, r.groupid, r.nfostatus, r.completion, r.categoryid FROM releases r LEFT JOIN category c ON c.id = r.categoryid WHERE r.size < %d ' . $groupid . ' AND r.passwordstatus BETWEEN %d AND -1 AND (r.haspreview = -1 AND c.disablepreview = 0) AND (bitwise & 256) = 256 ORDER BY postdate DESC LIMIT %d', $this->maxsize * 1073741824, $i, $this->addqty));
					$totresults = $result->rowCount();
					if ($totresults > 0)
						$this->doecho('Passwordstatus = ' . $i . ': Available to process = ' . $totresults);
					$i--;
				}
			} else {
				$pieces = explode('           =+=            ', $releaseToWork);
				$result = array(array('id' => $pieces[0], 'guid' => $pieces[1], 'name' => $pieces[2], 'disablepreview' => $pieces[3], 'size' => $pieces[4], 'groupid' => $pieces[5], 'nfostatus' => $pieces[6], 'categoryid' => $pieces[7]));
				$totresults = 1;
			}
		}


		$rescount = $startCount = $totresults;
		if ($rescount > 0) {
			if ($this->echooutput && $rescount > 1) {
				$this->doecho('Additional post-processing, started at: ' . date('D M d, Y G:i a'));
				$this->doecho('Downloaded: b = yEnc article, f= failed ;Processing: z = zip file, r = rar file');
				$this->doecho('Added: s = sample image, j = jpeg image, A = audio sample, a = audio mediainfo, v = video sample');
				$this->doecho('Added: m = video mediainfo, n = nfo, ^ = file details from inside the rar/zip');
				// Get count of releases per passwordstatus
				$pw1 = $this->db->query('SELECT count(*) as count FROM releases WHERE haspreview = -1 and passwordstatus = -1');
				$pw2 = $this->db->query('SELECT count(*) as count FROM releases WHERE haspreview = -1 and passwordstatus = -2');
				$pw3 = $this->db->query('SELECT count(*) as count FROM releases WHERE haspreview = -1 and passwordstatus = -3');
				$pw4 = $this->db->query('SELECT count(*) as count FROM releases WHERE haspreview = -1 and passwordstatus = -4');
				$pw5 = $this->db->query('SELECT count(*) as count FROM releases WHERE haspreview = -1 and passwordstatus = -5');
				$pw6 = $this->db->query('SELECT count(*) as count FROM releases WHERE haspreview = -1 and passwordstatus = -6');
				$this->doecho('Available to process: -6 = ' . number_format($pw6[0]['count']) . ', -5 = ' . number_format($pw5[0]['count']) . ', -4 = ' . number_format($pw4[0]['count']) . ', -3 = ' . number_format($pw3[0]['count']) . ', -2 = ' . number_format($pw2[0]['count']) . ', -1 = ' . number_format($pw1[0]['count']));
			}

			$ri = new ReleaseImage();
			$nzbcontents = new NZBContents($this->echooutput);
			$nzb = new NZB($this->echooutput);
			$groups = new Groups();
			$processSample = ($this->site->ffmpegpath != '') ? true : false;
			$processVideo = ($this->site->processvideos == '0') ? false : true;
			$processMediainfo = ($this->site->mediainfopath != '') ? true : false;
			$processAudioinfo = ($this->site->mediainfopath != '') ? true : false;
			$processJPGSample = ($this->site->processjpg == '0') ? false : true;
			$processPasswords = ($this->site->unrarpath != '') ? true : false;
			$tmpPath = $this->tmpPath;

			// Loop through the releases.
			foreach ($result as $rel) {
				if ($this->echooutput && $releaseToWork == '') {
					echo "\n[" . $this->c->primaryOver($startCount--) . ']';
				} else if ($this->echooutput) {
					echo '[' . $this->c->primaryOver($rel['id']) . ']';
				}

				// Per release defaults.
				$this->tmpPath = $tmpPath . $rel['guid'] . '/';
				if (!is_dir($this->tmpPath)) {
					$old = umask(0777);
					mkdir($this->tmpPath, 0777, true);
					chmod($this->tmpPath, 0777);
					umask($old);

					if (!is_dir($this->tmpPath)) {
						if ($this->echooutput)
							echo $this->c->error("Unable to create directory: {$this->tmpPath}");
						// Decrement passwordstatus.
						$this->db->queryExec('UPDATE releases SET passwordstatus = passwordstatus - 1 WHERE id = ' . $rel['id']);
						continue;
					}
				}

				$nzbpath = $nzb->getNZBPath($rel['guid'], $this->site->nzbpath, false, $this->site->nzbsplitlevel);
				if (!file_exists($nzbpath)) {
					// The nzb was not located. decrement the passwordstatus.
					$this->db->queryExec('UPDATE releases SET passwordstatus = passwordstatus - 1 WHERE id = ' . $rel['id']);
					continue;
				}

				// turn on output buffering
				ob_start();

				// uncompress the nzb
				@readgzfile($nzbpath);

				// read the nzb into memory
				$nzbfile = ob_get_contents();

				// Clean (erase) the output buffer and turn off output buffering
				ob_end_clean();

				// get a list of files in the nzb
				$nzbfiles = $nzb->nzbFileList($nzbfile);
				if (count($nzbfiles) == 0) {
					// There does not appear to be any files in the nzb, decrement passwordstatus
					$this->db->queryExec('UPDATE releases SET passwordstatus = passwordstatus - 1 WHERE id = ' . $rel['id']);
					continue;
				}

				// sort the files
				usort($nzbfiles, 'PostProcess::sortrar');

				// Only process for samples, previews and images if not disabled.
				$blnTookSample = ($rel['disablepreview'] == 1) ? true : false;
				$blnTookMediainfo = $blnTookAudioinfo = $blnTookJPG = $blnTookVideo = false;
				if ($processSample === false)
					$blnTookSample = true;
				if ($processVideo === false)
					$blnTookVideo = true;
				if ($processMediainfo === false)
					$blnTookMediainfo = true;
				if ($processAudioinfo === false)
					$blnTookAudioinfo = true;
				if ($processJPGSample === false)
					$blnTookJPG = true;
				$passStatus = array(Releases::PASSWD_NONE);
				$bingroup = $samplegroup = $mediagroup = $jpggroup = $audiogroup = '';
				$samplemsgid = $mediamsgid = $audiomsgid = $jpgmsgid = $audiotype = $mid = $rarpart = array();
				$hasrar = $ignoredbooks = $failed = $this->filesadded = 0;
				$this->password = $this->nonfo = $notmatched = $flood = $foundcontent = false;

				// Make sure we don't already have an nfo.
				if ($rel['nfostatus'] !== 1)
					$this->nonfo = true;

				$groupName = $groups->getByNameByID($rel['groupid']);
				// Go through the nzb for this release looking for a rar, a sample etc...
				foreach ($nzbfiles as $nzbcontents) {
					// Check if it's not a nfo, nzb, par2 etc...
					if (preg_match($this->supportfiles . "|nfo\b|inf\b|ofn\b)($|[ \")\]-])(?!.{20,})/i", $nzbcontents['title']))
						continue;

					// Check if it's a rar/zip.
					if (preg_match("/\.(part0*1|part0+|r0+|r0*1|rar|0+|0*10?|zip)(\.rar)*($|[ \")\]-])|\"[a-f0-9]{32}\.[1-9]\d{1,2}\".*\(\d+\/\d{2,}\)$/i", $nzbcontents['title']))
						$hasrar = 1;
					else if (!$hasrar)
						$notmatched = true;

					// Look for a sample.
					if ($processSample === true && !preg_match('/\.(jpg|jpeg)/i', $nzbcontents['title']) && preg_match('/sample/i', $nzbcontents['title'])) {
						if (isset($nzbcontents['segments']) && empty($samplemsgid)) {
							$samplegroup = $groupName;
							$samplemsgid[] = $nzbcontents['segments'][0];

							for ($i = 1; $i < $this->segmentstodownload; $i++) {
								if (count($nzbcontents['segments']) > $i)
									$samplemsgid[] = $nzbcontents['segments'][$i];
							}
						}
					}

					// Look for a media file.
					if ($processMediainfo === true && !preg_match('/sample/i', $nzbcontents['title']) && preg_match('/' . $this->videofileregex . '[. ")\]]/i', $nzbcontents['title'])) {
						if (isset($nzbcontents['segments']) && empty($mediamsgid)) {
							$mediagroup = $groupName;
							$mediamsgid[] = $nzbcontents['segments'][0];
						}
					}

					// Look for a audio file.
					if ($processAudioinfo === true && preg_match('/' . $this->audiofileregex . '[. ")\]]/i', $nzbcontents['title'], $type)) {
						if (isset($nzbcontents['segments']) && empty($audiomsgid)) {
							$audiogroup = $groupName;
							$audiotype = $type[1];
							$audiomsgid[] = $nzbcontents['segments'][0];
						}
					}

					// Look for a JPG picture.
					if ($processJPGSample === true && !preg_match('/flac|lossless|mp3|music|inner-sanctum|sound/i', $groupName) && preg_match('/\.(jpg|jpeg)[. ")\]]/i', $nzbcontents['title'])) {
						if (isset($nzbcontents['segments']) && empty($jpgmsgid)) {
							$jpggroup = $groupName;
							$jpgmsgid[] = $nzbcontents['segments'][0];
							if (count($nzbcontents['segments']) > 1)
								$jpgmsgid[] = $nzbcontents['segments'][1];
						}
					}
					if (preg_match($this->ignorebookregex, $nzbcontents['title']))
						$ignoredbooks++;
				}

				// Ignore massive book NZB's.
				if (count($nzbfiles) > 40 && $ignoredbooks * 2 >= count($nzbfiles)) {
					$this->debug(' skipping book flood');
					if (isset($rel['categoryid']) && substr($rel['categoryid'], 0, 1) == 8) {
						$this->db->queryExec(sprintf('UPDATE releases SET passwordstatus = 0, haspreview = 0, categoryid = 8050 WHERE id = %d', $rel['id']));
					}
					$flood = true;
				}

				// Seperate the nzb content into the different parts (support files, archive segments and the first parts).
				if ($flood === false && $hasrar !== 0) {
					if ($this->site->checkpasswordedrar > 0 || $processSample === true || $processMediainfo === true || $processAudioinfo === true) {
						$this->sum = $this->size = $this->segsize = $this->adj = $notinfinite = $failed = 0;
						$this->name = '';
						$this->ignorenumbered = $foundcontent = false;

						// Loop through the files, attempt to find if passworded and files. Starting with what not to process.
						foreach ($nzbfiles as $rarFile) {
							if ($this->passchkattempts > 1) {
								if ($notinfinite > $this->passchkattempts) {
									break;
								}
							} else {
								if ($notinfinite > $this->partsqty) {
									if ($this->echooutput) {
										echo $this->c->info("\nMax parts to pp reached");
									}
									break;
								}
							}

							if ($this->password === true) {
								$this->debug('Skipping processing of rar ' . $rarFile['title'] . ' it has a password.');
								break;
							}

							// Probably not a rar/zip.
							if (!preg_match("/\.\b(part\d+|part00\.rar|part01\.rar|rar|r00|r01|zipr\d{2,3}|zip|zipx)($|[ \")\]-])|\"[a-f0-9]{32}\.[1-9]\d{1,2}\".*\(\d+\/\d{2,}\)$/i", $rarFile['title'])) {
								continue;
							}

							// Process rar contents until 1G or 85% of file size is found (smaller of the two).
							if ($rarFile['size'] == 0 && $rarFile['partsactual'] != 0 && $rarFile['partstotal'] != 0) {
								$this->segsize = $rarFile['size'] / ($rarFile['partsactual'] / $rarFile['partstotal']);
							} else {
								$this->segsize = 0;
							}
							$this->sum = $this->sum + $this->adj * $this->segsize;
							if ($this->sum > $this->size || $this->adj === 0) {
								$mid = array_slice((array) $rarFile['segments'], 0, $this->segmentstodownload);

								$bingroup = $groupName;
								$fetchedBinary = $nntp->getMessages($bingroup, $mid);
								if (PEAR::isError($fetchedBinary)) {
									$nntp->doQuit();
									$this->site->alternate_nntp == 1 ? $nntp->doConnect_A() : $nntp->doConnect();
									$fetchedBinary = $nntp->getMessages($bingroup, $mid);
									if (PEAR::isError($fetchedBinary)) {
										$fetchedBinary = false;
									}
								}

								if ($fetchedBinary !== false) {
									$this->debug("\nProcessing " . $rarFile['title']);
									if ($this->echooutput) {
										echo 'b';
									}
									$notinfinite++;
									$relFiles = $this->processReleaseFiles($fetchedBinary, $rel, $rarFile['title'], $nntp);
									if ($this->password === true) {
										$passStatus[] = Releases::PASSWD_RAR;
									}

									if ($relFiles === false) {
										$this->debug('Error processing files ' . $rarFile['title']);
										continue;
									} else {
										// Flag to indicate the archive has content.
										$foundcontent = true;
									}
								} else {
									if ($this->echooutput) {
										echo $this->c->alternateOver("f(" . $notinfinite . ")");
									}
									$notinfinite = $notinfinite + 0.2;
									$failed++;
								}
							}
						}
					}

					// Starting to look for content.
					if (is_dir($this->tmpPath)) {
						$files = @scandir($this->tmpPath);
						$rar = new ArchiveInfo();
						if (!empty($files) && count($files) > 0) {
							foreach ($files as $file) {
								if (is_file($this->tmpPath . $file)) {
									if (preg_match('/\.rar$/i', $file)) {
										$rar->open($this->tmpPath . $file, true);
										if ($rar->error) {
											continue;
										}

										$tmpfiles = $rar->getArchiveFileList();
										if (isset($tmpfiles[0]['name'])) {
											foreach ($tmpfiles as $r) {
												$range = mt_rand(0, 99999);
												if (isset($r['range'])) {
													$range = $r['range'];
												}

												$r['range'] = $range;
												if (!isset($r['error']) && !preg_match($this->supportfiles . '|part\d+|r\d{1,3}|zipr\d{2,3}|\d{2,3}|zipx|zip|rar)(\.rar)?$/i', $r['name'])) {
													$this->addfile($r, $rel, $rar, $nntp);
												}
											}
										}
									}
								}
							}
						}
						unset($rar);
					}
				}
				/* Not a good indicator of if there is a password or not, the rar could have had an error for example.
				  else if ($hasrar == 1)
				  $passStatus[] = Releases::PASSWD_POTENTIAL;

				  if(!$foundcontent && $hasrar == 1)
				  $passStatus[] = Releases::PASSWD_POTENTIAL; */

				// Try to get image/mediainfo/audioinfo, using extracted files before downloading more data
				if ($blnTookSample === false || $blnTookAudioinfo === false || $blnTookMediainfo === false || $blnTookJPG === false || $blnTookVideo === false) {
					if (is_dir($this->tmpPath)) {
						$files = @scandir($this->tmpPath);
						if (isset($files) && is_array($files) && count($files) > 0) {
							foreach ($files as $file) {
								if (is_file($this->tmpPath . $file)) {
									$name = '';
									if ($processAudioinfo === true && $blnTookAudioinfo === false && preg_match('/(.*)' . $this->audiofileregex . '$/i', $file, $name)) {
										rename($this->tmpPath . $name[0], $this->tmpPath . 'audiofile.' . $name[2]);
										$blnTookAudioinfo = $this->getAudioinfo($this->tmpPath, $this->site->ffmpegpath, $this->site->mediainfopath, $rel['guid'], $rel['id']);
										@unlink($this->tmpPath . 'sample.' . $name[2]);
									}
									if ($processJPGSample === true && $blnTookJPG === false && preg_match('/\.(jpg|jpeg)$/', $file)) {
										if (filesize($this->tmpPath . $file) < 15) {
											continue;
										}
										if (exif_imagetype($this->tmpPath . $file) === false) {
											continue;
										}
										$blnTookJPG = $ri->saveImage($rel['guid'] . '_thumb', $this->tmpPath . $file, $ri->jpgSavePath, 650, 650);
										if ($blnTookJPG !== false) {
											$this->db->queryExec(sprintf('UPDATE releases SET jpgstatus = %d WHERE id = %d', 1, $rel['id']));
										}
									}
									if ($processSample === true || $processVideo === true || $processMediainfo === true) {
										if (preg_match('/(.*)' . $this->videofileregex . '$/i', $file, $name)) {
											rename($this->tmpPath . $name[0], $this->tmpPath . 'sample.avi');
											if ($processSample && $blnTookSample === false) {
												$blnTookSample = $this->getSample($this->tmpPath, $this->site->ffmpegpath, $rel['guid']);
											}
											if ($processVideo && $blnTookVideo === false) {
												$blnTookVideo = $this->getVideo($this->tmpPath, $this->site->ffmpegpath, $rel['guid']);
											}
											if ($processMediainfo && $blnTookMediainfo === false) {
												$blnTookMediainfo = $this->getMediainfo($this->tmpPath, $this->site->mediainfopath, $rel['id']);
											}
											@unlink($this->tmpPath . 'sample.avi');
										}
									}
									if ($blnTookJPG === true && $blnTookAudioinfo === true && $blnTookMediainfo === true && $blnTookVideo === true && $blnTookSample === true) {
										break;
									}
								}
							}
							unset($files);
						}
					}
				}

				// Download and process sample image.
				if ($processSample === true || $processVideo === true) {
					if ($blnTookSample === false || $blnTookVideo === false) {
						if (!empty($samplemsgid)) {
							$sampleBinary = $nntp->getMessages($samplegroup, $samplemsgid);
							if (PEAR::isError($sampleBinary)) {
								$nntp->doQuit();
								$this->site->alternate_nntp == 1 ? $nntp->doConnect_A() : $nntp->doConnect();
								$sampleBinary = $nntp->getMessages($samplegroup, $samplemsgid);
								if (PEAR::isError($sampleBinary))
									$sampleBinary = false;
							}

							if ($sampleBinary !== false) {
								if ($this->echooutput)
									echo 'b';
								if (strlen($sampleBinary) > 100) {
									$this->addmediafile($this->tmpPath . 'sample_' . mt_rand(0, 99999) . '.avi', $sampleBinary);
									if ($processSample === true && $blnTookSample === false)
										$blnTookSample = $this->getSample($this->tmpPath, $this->site->ffmpegpath, $rel['guid']);
									if ($processVideo === true && $blnTookVideo === false)
										$blnTookVideo = $this->getVideo($this->tmpPath, $this->site->ffmpegpath, $rel['guid']);
								}
								unset($sampleBinary);
							}
							else {
								if ($this->echooutput)
									echo 'f';
							}
						}
					}
				}

				// Download and process mediainfo. Also try to get a sample if we didn't get one yet.
				if ($processMediainfo === true || $processSample === true || $processVideo === true) {
					if ($blnTookMediainfo === false || $blnTookSample === false || $blnTookVideo === false) {
						if (!empty($mediamsgid)) {
							$mediaBinary = $nntp->getMessages($mediagroup, $mediamsgid);
							if (PEAR::isError($mediaBinary)) {
								$nntp->doQuit();
								$this->site->alternate_nntp == 1 ? $nntp->doConnect_A() : $nntp->doConnect();
								$mediaBinary = $nntp->getMessages($mediagroup, $mediamsgid);
								if (PEAR::isError($mediaBinary))
									$mediaBinary = false;
							}
							if ($mediaBinary !== false) {
								if ($this->echooutput)
									echo 'b';
								if (strlen($mediaBinary) > 100) {
									$this->addmediafile($this->tmpPath . 'media.avi', $mediaBinary);
									if ($processMediainfo === true && $blnTookMediainfo === false)
										$blnTookMediainfo = $this->getMediainfo($this->tmpPath, $this->site->mediainfopath, $rel['id']);
									if ($processSample === true && $blnTookSample === false)
										$blnTookSample = $this->getSample($this->tmpPath, $this->site->ffmpegpath, $rel['guid']);
									if ($processVideo === true && $blnTookVideo === false)
										$blnTookVideo = $this->getVideo($this->tmpPath, $this->site->ffmpegpath, $rel['guid']);
								}
								unset($mediaBinary);
							}
							else {
								if ($this->echooutput)
									echo 'f';
							}
						}
					}
				}

				// Download audio file, use mediainfo to try to get the artist / album.
				if ($processAudioinfo === true && !empty($audiomsgid) && $blnTookAudioinfo === false) {
					$audioBinary = $nntp->getMessages($audiogroup, $audiomsgid);
					if (PEAR::isError($audioBinary)) {
						$nntp->doQuit();
						$this->site->alternate_nntp == 1 ? $nntp->doConnect_A() : $nntp->doConnect();
						$audioBinary = $nntp->getMessages($audiogroup, $audiomsgid);
						if (PEAR::isError($audioBinary))
							$audioBinary = false;
					}
					if ($audioBinary !== false) {
						if ($this->echooutput)
							echo 'b';
						if (strlen($audioBinary) > 100) {
							$this->addmediafile($this->tmpPath . 'audio.' . $audiotype, $audioBinary);
							$blnTookAudioinfo = $this->getAudioinfo($this->tmpPath, $this->site->ffmpegpath, $this->site->mediainfopath, $rel['guid'], $rel['id']);
						}
						unset($audioBinary);
					} else {
						if ($this->echooutput)
							echo 'f';
					}
				}

				// Download JPG file.
				if ($processJPGSample === true && !empty($jpgmsgid) && $blnTookJPG === false) {
					$jpgBinary = $nntp->getMessages($jpggroup, $jpgmsgid);
					if (PEAR::isError($jpgBinary)) {
						$nntp->doQuit();
						$this->site->alternate_nntp == 1 ? $nntp->doConnect_A() : $nntp->doConnect();
						$jpgBinary = $nntp->getMessages($jpggroup, $jpgmsgid);
						if (PEAR::isError($jpgBinary))
							$jpgBinary = false;
					}
					if ($jpgBinary !== false) {
						if ($this->echooutput)
							echo 'b';
						$this->addmediafile($this->tmpPath . 'samplepicture.jpg', $jpgBinary);
						if (is_dir($this->tmpPath) && is_file($this->tmpPath . 'samplepicture.jpg')) {
							if (filesize($this->tmpPath . 'samplepicture.jpg') > 15 && exif_imagetype($this->tmpPath . 'samplepicture.jpg') !== false && $blnTookJPG === false) {
								$blnTookJPG = $ri->saveImage($rel['guid'] . '_thumb', $this->tmpPath . 'samplepicture.jpg', $ri->jpgSavePath, 650, 650);
								if ($blnTookJPG !== false)
									$this->db->queryExec(sprintf('UPDATE releases SET jpgstatus = %d WHERE id = %d', 1, $rel['id']));
							}

							foreach (glob($this->tmpPath . 'samplepicture.jpg') as $v) {
								@unlink($v);
							}
						}
						unset($jpgBinary);
					} else {
						if ($this->echooutput)
							echo 'f';
					}
				}

				// Set up release values.
				$hpsql = $isql = $vsql = $jsql = '';
				if ($processSample === true && $blnTookSample !== false)
					$this->updateReleaseHasPreview($rel['guid']);
				else
					$hpsql = ', haspreview = 0';

				if ($failed > 0) {
					if ($failed / count($nzbfiles) > 0.7 || $notinfinite > $this->passchkattempts || $notinfinite > $this->partsqty)
						$passStatus[] = Releases::BAD_FILE;
				}

				// If samples exist from previous runs, set flags.
				if (file_exists($ri->imgSavePath . $rel['guid'] . '_thumb.jpg'))
					$isql = ', haspreview = 1';
				if (file_exists($ri->vidSavePath . $rel['guid'] . '.ogv'))
					$vsql = ', videostatus = 1';
				if (file_exists($ri->jpgSavePath . $rel['guid'] . '_thumb.jpg'))
					$jsql = ', jpgstatus = 1';

				$size = $this->db->queryOneRow('SELECT COUNT(releasefiles.releaseid) AS count, SUM(releasefiles.size) AS size FROM releasefiles WHERE releaseid = ' . $rel['id']);
				if (max($passStatus) > 0)
					$sql = sprintf('UPDATE releases SET passwordstatus = %d, rarinnerfilecount = %d %s %s %s %s WHERE id = %d', max($passStatus), $size['count'], $isql, $vsql, $jsql, $hpsql, $rel['id']);
				else if ($hasrar && ((isset($size['size']) && (is_null($size['size']) || $size['size'] == 0)) || !isset($size['size']))) {
					if (!$blnTookSample)
						$hpsql = '';
					$sql = sprintf('UPDATE releases SET passwordstatus = passwordstatus - 1, rarinnerfilecount = %d %s %s %s %s WHERE id = %d', $size['count'], $isql, $vsql, $jsql, $hpsql, $rel['id']);
				} else
					$sql = sprintf('UPDATE releases SET passwordstatus = %s, rarinnerfilecount = %d %s %s %s %s WHERE id = %d', Releases::PASSWD_NONE, $size['count'], $isql, $vsql, $jsql, $hpsql, $rel['id']);

				$this->db->queryExec($sql);

				// Erase all files and directory.
				foreach (glob($this->tmpPath . '*') as $v) {
					@unlink($v);
				}
				foreach (glob($this->tmpPath . '.*') as $v) {
					@unlink($v);
				}
				@rmdir($this->tmpPath);
			}
			if ($this->echooutput)
				echo "\n";
		}
		if ($gui)
			$this->db->queryExec(sprintf("UPDATE site SET value = %d WHERE setting %s 'currentppticket1'", $ticket + 1, $like));

		unset($this->consoleTools, $rar, $nzbcontents, $groups, $ri);
	}

	function doecho($str)
	{
		if ($this->echooutput)
			echo $this->c->header($str);
	}

	function debug($str)
	{
		if ($this->echooutput && $this->DEBUG_ECHO) {
			echo $this->c->debug($str);
		}
	}

	function addmediafile($file, $data)
	{
		if (@file_put_contents($file, $data) !== false) {
			$xmlarray = @runCmd('"' . $this->site->mediainfopath . '" --Output=XML "' . $file . '"');
			if (is_array($xmlarray)) {
				$xmlarray = implode("\n", $xmlarray);
				$xmlObj = @simplexml_load_string($xmlarray);
				$arrXml = objectsIntoArray($xmlObj);
				if (!isset($arrXml['File']['track'][0]))
					@unlink($file);
			}
		}
	}

	function addfile($v, $release, $rar = false, $nntp)
	{
		if (!isset($nntp))
			exit($this->c->error("Not connected to usenet(postprocess->addfile).\n"));

		if (!isset($v['error']) && isset($v['source'])) {
			if ($rar !== false && preg_match('/\.zip$/', $v['source'])) {
				$zip = new ZipInfo();
				$tmpdata = $zip->getFileData($v['name'], $v['source']);
			} else if ($rar !== false)
				$tmpdata = $rar->getFileData($v['name'], $v['source']);
			else
				$tmpdata = false;

			// Check if we already have the file or not.
			// Also make sure we don't add too many files, some releases have 100's of files, like PS3 releases.
			if ($this->filesadded < 11 && $this->db->queryOneRow(sprintf('SELECT id FROM releasefiles WHERE releaseid = %d AND name = %s AND size = %d', $release['id'], $this->db->escapeString($v['name']), $v['size'])) === false) {
				$rf = new ReleaseFiles();
				if ($rf->add($release['id'], $v['name'], $v['size'], $v['date'], $v['pass'])) {
					$this->filesadded++;
					$this->newfiles = true;
					if ($this->echooutput)
						echo '^';
				}
			}

			if ($tmpdata !== false) {
				// Extract a NFO from the rar.
				if ($this->nonfo === true && $v['size'] > 100 && $v['size'] < 100000 && preg_match('/(\.(nfo|inf|ofn)|info.txt)$/i', $v['name'])) {
					$nfo = new Nfo($this->echooutput);
					if ($nfo->addAlternateNfo($this->db, $tmpdata, $release, $nntp)) {
						$this->debug('added rar nfo');
						if ($this->echooutput)
							echo 'n';
						$this->nonfo = false;
					}
				}
				// Extract a video file from the compressed file.
				else if ($this->site->mediainfopath != '' && $this->site->processvideos == '1' && preg_match('/' . $this->videofileregex . '$/i', $v['name']))
					$this->addmediafile($this->tmpPath . 'sample_' . mt_rand(0, 99999) . '.avi', $tmpdata);
				// Extract an audio file from the compressed file.
				else if ($this->site->mediainfopath != '' && preg_match('/' . $this->audiofileregex . '$/i', $v['name'], $ext))
					$this->addmediafile($this->tmpPath . 'audio_' . mt_rand(0, 99999) . $ext[0], $tmpdata);
				else if ($this->site->mediainfopath != '' && preg_match('/([^\/\\\r]+)(\.[a-z][a-z0-9]{2,3})$/i', $v['name'], $name))
					$this->addmediafile($this->tmpPath . $name[1] . mt_rand(0, 99999) . $name[2], $tmpdata);
			}
			unset($tmpdata, $rf);
		}
	}

	// Open the zip, see if it has a password, attempt to get a file.
	function processReleaseZips($fetchedBinary, $open = false, $data = false, $release, $nntp)
	{
		if (!isset($nntp))
			exit($this->c->error("Not connected to usenet(postprocess->processReleaseZips).\n"));

		// Load the ZIP file or data.
		$zip = new ZipInfo();
		if ($open)
			$zip->open($fetchedBinary, true);
		else
			$zip->setData($fetchedBinary, true);

		if ($zip->error) {
			$this->debug('Error: ' . $zip->error);
			return false;
		}

		if (!empty($zip->isEncrypted)) {
			$this->debug('ZIP archive is password encrypted.');
			$this->password = true;
			return false;
		}

		$files = $zip->getFileList();
		$dataarray = array();
		if ($files !== false) {
			if ($this->echooutput)
				echo 'z';
			if ($this->nonfo === true)
				$nfo = new Nfo($this->echooutput);
			foreach ($files as $file) {
				$thisdata = $zip->getFileData($file['name']);
				$dataarray[] = array('zip' => $file, 'data' => $thisdata);

				//Extract a NFO from the zip.
				if ($this->nonfo === true && $file['size'] < 100000 && preg_match('/\.(nfo|inf|ofn)$/i', $file['name'])) {
					if ($file['compressed'] !== 1) {
						if ($nfo->addAlternateNfo($this->db, $thisdata, $release, $nntp)) {
							$this->debug('Added zip NFO.');
							if ($this->echooutput)
								echo 'n';
							$this->nonfo = false;
						}
					}
					else if ($this->site->zippath != '' && $file['compressed'] === 1) {
						$zip->setExternalClient($this->site->zippath);
						$zipdata = $zip->extractFile($file['name']);
						if ($zipdata !== false && strlen($zipdata) > 5)
							; {
							if ($nfo->addAlternateNfo($this->db, $zipdata, $release, $nntp)) {
								$this->debug('Added compressed zip NFO.');
								if ($this->echooutput)
									echo 'n';
								$this->nonfo = false;
							}
						}
					}
				}
				// Process RARs inside the ZIP.
				else if (preg_match('/\.(r\d+|part\d+|rar)$/i', $file['name'])) {
					$tmpfiles = $this->getRar($thisdata);
					if ($tmpfiles != false) {
						$limit = 0;
						foreach ($tmpfiles as $f) {
							if ($limit++ > 11)
								break;
							$ret = $this->addfile($f, $release, $rar = false, $nntp);
							$files[] = $f;
						}
					}
				}
			}
		}

		if ($data) {
			$files = $dataarray;
			unset($dataarray);
		}

		unset($fetchedBinary, $zip);
		return $files;
	}

	function getRar($fetchedBinary)
	{
		$rar = new ArchiveInfo();
		$files = $retval = false;
		if ($rar->setData($fetchedBinary, true))
			$files = $rar->getArchiveFileList();
		if ($rar->error) {
			$this->debug('Error: ' . $rar->error);
			return $retval;
		}
		if (!empty($rar->isEncrypted)) {
			$this->debug('Archive is password encrypted.');
			$this->password = true;
			return $retval;
		}
		$tmp = $rar->getSummary(true, false);
		if (isset($tmp['is_encrypted']) && $tmp['is_encrypted'] != 0) {
			$this->debug('Archive is password encrypted.');
			$this->password = true;
			return $retval;
		}
		$files = $rar->getArchiveFileList();
		if ($files !== false) {
			$retval = array();
			if ($this->echooutput !== false)
				echo 'r';
			foreach ($files as $file) {
				if (isset($file['name'])) {
					if (isset($file['error'])) {
						$this->debug("Error: {$file['error']} (in: {$file['source']})");
						continue;
					}
					if (isset($file['pass']) && $file['pass'] == true) {
						$this->password = true;
						break;
					}
					if (preg_match($this->supportfiles . ')(?!.{20,})/i', $file['name']))
						continue;
					if (preg_match('/([^\/\\\\]+)(\.[a-z][a-z0-9]{2,3})$/i', $file['name'], $name)) {
						$rarfile = $this->tmpPath . $name[1] . mt_rand(0, 99999) . $name[2];
						$fetchedBinary = $rar->getFileData($file['name'], $file['source']);
						if ($this->site->mediainfopath != '')
							$this->addmediafile($rarfile, $fetchedBinary);
					}
					if (!preg_match('/\.(r\d+|part\d+)$/i', $file['name']))
						$retval[] = $file;
				}
			}
		}

		if (count($retval) == 0)
			return false;
		return $retval;
	}

	// Open the rar, see if it has a password, attempt to get a file.
	function processReleaseFiles($fetchedBinary, $release, $name, $nntp)
	{
		if (!isset($nntp))
			exit($this->c->error("Not connected to usenet(postprocess->processReleaseFiles).\n"));

		$retval = array();
		$rar = new ArchiveInfo();
		$rf = new ReleaseFiles();
		$this->password = false;

		if (preg_match("/\.(part\d+|rar|r\d{1,3})($|[ \")\]-])|\"[a-f0-9]{32}\.[1-9]\d{1,2}\".*\(\d+\/\d{2,}\)$/i", $name)) {
			$rar->setData($fetchedBinary, true);
			if ($rar->error) {
				$this->debug("\nError: {$rar->error}.");
				return false;
			}

			$tmp = $rar->getSummary(true, false);
			if (preg_match('/par2/i', $tmp['main_info']))
				return false;

			if (isset($tmp['is_encrypted']) && $tmp['is_encrypted'] != 0) {
				$this->debug('Archive is password encrypted.');
				$this->password = true;
				return false;
			}

			if (!empty($rar->isEncrypted)) {
				$this->debug('Archive is password encrypted.');
				$this->password = true;
				return false;
			}

			$files = $rar->getArchiveFileList();
			if (count($files) == 0 || !is_array($files) || !isset($files[0]['compressed']))
				return false;

			if ($files[0]['compressed'] == 0 && $files[0]['name'] != $this->name) {
				$this->name = $files[0]['name'];
				$this->size = $files[0]['size'] * 0.95;
				$this->adj = $this->sum = 0;

				if ($this->echooutput)
					echo 'r';
				// If archive is not stored compressed, process data
				foreach ($files as $file) {
					if (isset($file['name'])) {
						if (isset($file['error'])) {
							$this->debug("Error: {$file['error']} (in: {$file['source']})");
							continue;
						}
						if ($file['pass'] == true) {
							$this->password = true;
							break;
						}

						if (preg_match($this->supportfiles . ')(?!.{20,})/i', $file['name']))
							continue;

						if (preg_match('/\.zip$/i', $file['name'])) {
							$zipdata = $rar->getFileData($file['name'], $file['source']);
							$data = $this->processReleaseZips($zipdata, false, true, $release, $nntp);

							if ($data != false) {
								foreach ($data as $d) {
									if (preg_match('/\.(part\d+|r\d+|rar)(\.rar)?$/i', $d['zip']['name']))
										$tmpfiles = $this->getRar($d['data']);
								}
							}
						}

						if (!isset($file['next_offset']))
							$file['next_offset'] = 0;
						$range = mt_rand(0, 99999);
						if (isset($file['range']))
							$range = $file['range'];
						$retval[] = array('name' => $file['name'], 'source' => $file['source'], 'range' => $range, 'size' => $file['size'], 'date' => $file['date'], 'pass' => $file['pass'], 'next_offset' => $file['next_offset']);
						$this->adj = $file['next_offset'] + $this->adj;
					}
				}

				$this->sum = $this->adj;
				if ($this->segsize != 0)
					$this->adj = $this->adj / $this->segsize;
				else
					$this->adj = 0;

				if ($this->adj < .7)
					$this->adj = 1;
			}
			else {
				$this->size = $files[0]['size'] * 0.95;
				if ($this->name != $files[0]['name']) {
					$this->name = $files[0]['name'];
					$this->sum = $this->segsize;
					$this->adj = 1;
				}

				// File is compressed, use unrar to get the content
				$rarfile = $this->tmpPath . 'rarfile' . mt_rand(0, 99999) . '.rar';
				if (@file_put_contents($rarfile, $fetchedBinary)) {
					$execstring = '"' . $this->site->unrarpath . '" e -ai -ep -c- -id -inul -kb -or -p- -r -y "' . $rarfile . '" "' . $this->tmpPath . '"';
					$output = @runCmd($execstring, false, true);
					if (isset($files[0]['name'])) {
						if ($this->echooutput)
							echo 'r';
						foreach ($files as $file) {
							if (isset($file['name'])) {
								if (!isset($file['next_offset']))
									$file['next_offset'] = 0;
								$range = mt_rand(0, 99999);
								if (isset($file['range']))
									$range = $file['range'];

								$retval[] = array('name' => $file['name'], 'source' => $file['source'], 'range' => $range, 'size' => $file['size'], 'date' => $file['date'], 'pass' => $file['pass'], 'next_offset' => $file['next_offset']);
							}
						}
					}
				}
			}
		}
		else {
			// Not a rar file, try it as a ZIP file.
			$files = $this->processReleaseZips($fetchedBinary, false, false, $release, $nntp);
			if ($files !== false) {
				$this->name = $files[0]['name'];
				$this->size = $files[0]['size'] * 0.95;
				$this->sum = $this->adj = 0;

				foreach ($files as $file) {
					if (isset($file['pass']) && $file['pass']) {
						$this->password = true;
						break;
					}

					if (!isset($file['next_offset']))
						$file['next_offset'] = 0;
					if (!isset($file['range']))
						$file['range'] = 0;

					$retval[] = array('name' => $file['name'], 'source ' => 'main', 'range' => $file['range'], 'size' => $file['size'], 'date' => $file['date'], 'pass' => $file['pass'], 'next_offset' => $file['next_offset']);
					$this->adj = $file['next_offset'] + $this->adj;
					$this->sum = $file['size'] + $this->sum;
				}

				$this->size = $this->sum;
				$this->sum = $this->adj;
				if ($this->segsize != 0)
					$this->adj = $this->adj / $this->segsize;
				else
					$this->adj = 0;

				if ($this->adj < .7)
					$this->adj = 1;
			}
			// Not a compressed file, but segmented.
			else
				$this->ignorenumbered = true;
		}

		// Use found content to populate releasefiles, nfo, and create multimedia files.
		foreach ($retval as $k => $v) {
			if (!preg_match($this->supportfiles . '|part\d+|r\d{1,3}|zipr\d{2,3}|\d{2,3}|zipx|zip|rar)(\.rar)?$/i', $v['name']) && count($retval) > 0)
				$this->addfile($v, $release, $rar, $nntp);
			else
				unset($retval[$k]);
		}

		if (count($retval) == 0)
			$retval = false;
		unset($fetchedBinary, $rar, $rf, $nfo);
		return $retval;
	}

	// Attempt to get mediafio xml from a video file.
	public function getMediainfo($ramdrive, $mediainfo, $releaseID)
	{
		$retval = false;
		if ($mediainfo == '' && !is_dir($ramdrive) && $releaseID <= 0)
			return $retval;

		$mediafiles = glob($ramdrive . '*.*');
		if (is_array($mediafiles)) {
			foreach ($mediafiles as $mediafile) {
				if (is_file($mediafile) && filesize($mediafile) > 15 && preg_match('/' . $this->videofileregex . '$/i', $mediafile)) {
					$xmlarray = @runCmd('"' . $mediainfo . '" --Output=XML "' . $mediafile . '"');
					if (is_array($xmlarray)) {
						$xmlarray = implode("\n", $xmlarray);
						$re = new ReleaseExtra();
						$re->addFull($releaseID, $xmlarray);
						$re->addFromXml($releaseID, $xmlarray);
						$retval = true;
						if ($this->echooutput)
							echo 'm';
						break;
					}
				}
			}
		}
		return $retval;
	}

	// Attempt to get mediainfo/sample/title from a audio file.
	public function getAudioinfo($ramdrive, $ffmpeginfo, $audioinfo, $releaseguid, $releaseID)
	{
		$retval = $audval = false;
		if (!is_dir($ramdrive) && $releaseID <= 0) {
			return $retval;
		}

		// Make sure the category is music or other->misc.
		$rquer = $this->db->queryOneRow(sprintf('SELECT categoryid as id, groupid FROM releases WHERE (bitwise & 8) = 0 '
				. 'AND id = %d', $releaseID));
		if (!preg_match('/^3\d{3}|7010/', $rquer['id'])) {
			return $retval;
		}

		$audiofiles = glob($ramdrive . '*.*');
		if (is_array($audiofiles)) {
			foreach ($audiofiles as $audiofile) {
				if (is_file($audiofile) && preg_match('/' . $this->audiofileregex . '$/i', $audiofile, $ext)) {
					// Process audio info, change searchname if we find a group/album name in the tags.
					if ($this->site->mediainfopath != '' && $retval === false) {
						$xmlarray = @runCmd('"' . $audioinfo . '" --Output=XML "' . $audiofile . '"');
						if (is_array($xmlarray)) {
							$arrXml = objectsIntoArray(@simplexml_load_string(implode("\n", $xmlarray)));
							if (isset($arrXml['File']['track'])) {
								foreach ($arrXml['File']['track'] as $track) {
									if (isset($track['Album']) && isset($track['Performer'])) {
										$ext = strtoupper($ext[1]);
										if (!empty($track['Recorded_date']) && preg_match('/(?:19|20)\d\d/', $track['Recorded_date'], $Year)) {
											$newname = $track['Performer'] . ' - ' . $track['Album'] . ' (' . $Year[0] . ') ' . $ext;
										} else {
											$newname = $track['Performer'] . ' - ' . $track['Album'] . ' ' . $ext;
										}
										$category = new Category();
										if ($ext == 'MP3') {
											$newcat = Category::CAT_MUSIC_MP3;
										} else if ($ext == 'FLAC') {
											$newcat = Category::CAT_MUSIC_LOSSLESS;
										} else {
											$newcat = $category->determineCategory($newname, $rquer['groupid']);
										}
										$this->db->queryExec(sprintf('UPDATE releases SET searchname = %s, categoryid = %d, bitwise = ((bitwise & ~13)|13) WHERE id = %d', $this->db->escapeString(substr($newname, 0, 255)), $newcat, $releaseID));

										$re = new ReleaseExtra();
										$re->addFromXml($releaseID, $xmlarray);
										$retval = true;
										if ($this->echooutput) {
											echo 'a';
										}
										break;
									}
								}
							}
						}
					}
					// Create an audio sample in ogg format.
					if ($this->processAudioSample && $audval === false) {
						$output = @runCmd('"' . $ffmpeginfo . '" -t 30 -i "' . $audiofile . '" -acodec libvorbis -loglevel quiet -y "' . $ramdrive . $releaseguid . '.ogg"');
						if (is_dir($ramdrive)) {
							$all_files = @scandir($ramdrive, 1);
							foreach ($all_files as $file) {
								if (preg_match('/' . $releaseguid . '\.ogg/', $file)) {
									if (filesize($ramdrive . $file) < 15) {
										continue;
									}

									@copy($ramdrive . $releaseguid . '.ogg', $this->audSavePath . $releaseguid . '.ogg');
									if (@file_exists($this->audSavePath . $releaseguid . '.ogg')) {
										chmod($this->audSavePath . $releaseguid . '.ogg', 0764);
										$this->db->queryExec(sprintf('UPDATE releases SET audiostatus = 1 WHERE id = %d', $releaseID));
										$audval = true;
										if ($this->echooutput) {
											echo 'A';
										}
										break;
									}
								}
							}
							// Clean up all files.
							foreach (glob($ramdrive . '*.ogg') as $v) {
								@unlink($v);
							}
						}
					}
					if ($retval === true && $audval === true) {
						break;
					}
				}
			}
		}
		return $retval;
	}

	// Attempt to get a sample image from a video file.
	public function getSample($ramdrive, $ffmpeginfo, $releaseguid)
	{
		$retval = false;
		if ($ffmpeginfo == '' && !is_dir($ramdrive) && strlen($releaseguid) <= 0)
			return $retval;

		$ri = new ReleaseImage();
		$samplefiles = glob($ramdrive . '*.*');
		if (is_array($samplefiles)) {
			foreach ($samplefiles as $samplefile) {
				if (is_file($samplefile) && preg_match('/' . $this->videofileregex . '$/i', $samplefile)) {
					$filecont = @file_get_contents($samplefile, true, null, 0, 40);
					if (!preg_match($this->sigregex, $filecont) || strlen($filecont) < 30)
						continue;

					//$cmd = '"'.$ffmpeginfo.'" -i "'.$samplefile.'" -loglevel quiet -f image2 -ss ' . $this->ffmpeg_image_time . ' -vframes 1 -y "'.$ramdrive.'"zzzz"'.mt_rand(0,9).mt_rand(0,9).mt_rand(0,9).'".jpg';
					//$output = @runCmd($cmd);
					$sample_duration = exec($ffmpeginfo . ' -i "' . $samplefile . "\" 2>&1 | grep \"Duration\"| cut -d ' ' -f 4 | sed s/,// | awk '{ split($1, A, \":\"); split(A[3], B, \".\"); print 3600*A[1] + 60*A[2] + B[1] }'");
					if ($sample_duration > 100 || $sample_duration == 0 || $sample_duration == '')
						$sample_duration = 2;
					$output_file = $ramdrive . 'zzzz' . mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9) . '.jpg';
					$output = exec($ffmpeginfo . ' -i "' . $samplefile . '" -loglevel quiet -vframes 250 -y "' . $output_file . '"');
					$output = exec($ffmpeginfo . ' -i "' . $samplefile . '" -loglevel quiet -vframes 1 -ss ' . $sample_duration . ' -y "' . $output_file . '"');

					if (is_dir($ramdrive)) {
						$all_files = @scandir($ramdrive, 1);
						foreach ($all_files as $file) {
							if (preg_match('/zzzz\d{3}\.jpg/', $file) && $retval === false) {
								if (filesize($ramdrive . $file) < 15)
									continue;
								if (exif_imagetype($ramdrive . $file) === false)
									continue;

								$ri->saveImage($releaseguid . '_thumb', $ramdrive . $file, $ri->imgSavePath, 800, 600);
								if (file_exists($ri->imgSavePath . $releaseguid . '_thumb.jpg')) {
									$retval = true;
									if ($this->echooutput)
										echo 's';
									break;
								}
							}
						}

						// Clean up all files.
						foreach (glob($ramdrive . '*.jpg') as $v) {
							@unlink($v);
						}
						if ($retval === true)
							break;
					}
				}
			}
		}
		// If an image was made, return true, else return false.
		return $retval;
	}

	public function getVideo($ramdrive, $ffmpeginfo, $releaseguid)
	{
		$retval = false;
		if ($ffmpeginfo == '' && !is_dir($ramdrive) && strlen($releaseguid) <= 0)
			return $retval;

		$ri = new ReleaseImage();
		$samplefiles = glob($ramdrive . '*.*');
		if (is_array($samplefiles)) {
			foreach ($samplefiles as $samplefile) {
				if (is_file($samplefile) && preg_match('/' . $this->videofileregex . '$/i', $samplefile)) {
					$filecont = @file_get_contents($samplefile, true, null, 0, 40);
					if (!preg_match($this->sigregex, $filecont) || strlen($filecont) < 30)
						continue;

					$output = @runCmd('"' . $ffmpeginfo . '" -i "' . $samplefile . '" -vcodec libtheora -filter:v scale=320:-1 -t ' . $this->ffmpeg_duration . ' -acodec libvorbis -loglevel quiet -y "' . $ramdrive . 'zzzz' . $releaseguid . '.ogv"');

					if (is_dir($ramdrive)) {
						$all_files = @scandir($ramdrive, 1);
						foreach ($all_files as $file) {
							if (preg_match('/zzzz' . $releaseguid . '\.ogv/', $file)) {
								if (filesize($ramdrive . 'zzzz' . $releaseguid . '.ogv') > 4096) {
									@copy($ramdrive . 'zzzz' . $releaseguid . '.ogv', $ri->vidSavePath . $releaseguid . '.ogv');
									if (@file_exists($ri->vidSavePath . $releaseguid . '.ogv')) {
										chmod($ri->vidSavePath . $releaseguid . '.ogv', 0764);
										$this->db->queryExec(sprintf('UPDATE releases SET videostatus = 1 WHERE guid = %s', $this->db->escapeString($releaseguid)));
										$retval = true;
										if ($this->echooutput)
											echo 'v';
										break;
									}
								}
							}
							// Clean up all files.
							foreach (glob($ramdrive . '*.ogv') as $v) {
								@unlink($v);
							}
							if ($retval === true)
								break;
						}
					}
				}
			}
		}
		// If an video was made, return true, else return false.
		return $retval;
	}

	public function updateReleaseHasPreview($guid)
	{
		$this->db->queryExec(sprintf('UPDATE releases SET haspreview = 1 WHERE guid = %s', $this->db->escapeString($guid)));
	}
}
?>
