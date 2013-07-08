<?php
require_once(WWW_DIR."lib/anidb.php");
require_once(WWW_DIR."lib/rarinfo/archiveinfo.php");
require_once(WWW_DIR."lib/books.php");
require_once(WWW_DIR."lib/category.php");
require_once(WWW_DIR."lib/console.php");
require_once(WWW_DIR."lib/consoletools.php");
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/movie.php");
require_once(WWW_DIR."lib/music.php");
require_once(WWW_DIR."lib/nfo.php");
require_once(WWW_DIR."lib/nntp.php");
require_once(WWW_DIR."lib/nzb.php");
require_once(WWW_DIR."lib/nzbcontents.php");
require_once(WWW_DIR."lib/predb.php");
require_once(WWW_DIR."lib/releases.php");
require_once(WWW_DIR."lib/releaseextra.php");
require_once(WWW_DIR."lib/releasefiles.php");
require_once(WWW_DIR."lib/releaseimage.php");
require_once(WWW_DIR."lib/site.php");
require_once(WWW_DIR."lib/tvrage.php");
require_once(WWW_DIR."lib/util.php");
require_once(WWW_DIR."lib/rarinfo/zipinfo.php");

class PostProcess
{
	function PostProcess($echooutput=false)
	{
		$this->echooutput = $echooutput;
		$s = new Sites();
		$this->site = $s->get();
		$this->addqty = (!empty($this->site->maxaddprocessed)) ? $this->site->maxaddprocessed : 25;
		$this->partsqty = (!empty($this->site->maxpartsprocessed)) ? $this->site->maxpartsprocessed : 3;
		$this->passchkattempts = (!empty($this->site->passchkattempts)) ? $this->site->passchkattempts : 1;
		$this->password = false;
		$this->maxsize = (!empty($this->site->maxsizetopostprocess)) ? $this->site->maxsizetopostprocess : 100;
		$this->processAudioSample = ($this->site->processaudiosample == "0") ? false : true;
		$this->audSavePath = WWW_DIR.'covers/audiosample/';
		$this->tmpPath = $this->site->tmpunrarpath;
		$this->db = new DB();
		$this->consoleTools = new ConsoleTools();
		$this->segmentstodownload = (!empty($this->site->segmentstodownload)) ? $this->site->segmentstodownload : 2;
		$this->ffmpeg_duration = (!empty($this->site->ffmpeg_duration)) ? $this->site->ffmpeg_duration : 5;
		$this->ffmpeg_image_time = (!empty($this->site->ffmpeg_image_time)) ? $this->site->ffmpeg_image_time : 5;

		$this->videofileregex = '\.(AVI|F4V|IFO|M1V|M2V|M4V|MKV|MOV|MP4|MPEG|MPG|MPGV|MPV|OGV|QT|RM|RMVB|TS|VOB|WMV)';
		$this->audiofileregex = '\.(AAC|AIFF|APE|AC3|ASF|DTS|FLAC|MKA|MKS|MP2|MP3|RA|OGG|OGM|W64|WAV|WMA)';
		$this->supportfiles = "/\.(vol\d{1,3}\+\d{1,3}|par2|srs|sfv|nzb";
		$this->ignorebookregex = "/\b(epub|lit|mobi|pdf|sipdf|html)\b.*\.rar(?!.{20,})/i";

		$sigs = array(array('00', '00', '01', 'BA'),
					array('00', '00', '01', 'B3'),
					array('00', '00', '01', 'B7'),
					array('1A', '45', 'DF', 'A3'),
					array('01', '00', '09', '00'),
					array('30', '26', 'B2', '75'),
					array('A6', 'D9', '00', 'AA'));
		$sigstr = '';
		foreach($sigs as $sig)
		{
			$str = '';
			foreach($sig as $s)
			{
				$str = $str."\x$s";
			}
			$sigstr = $sigstr."|".$str;
		}
		$sigstr = "/^ftyp|mp4|^riff|avi|matroska|.rec|.rmf|^oggs|moov|dvd|^0&²u|free|mdat||pnot|skip|wide$sigstr/i";
		$this->sigregex = $sigstr;
		$this->DEBUG_ECHO = false;
		if (defined("DEBUG_ECHO") && DEBUG_ECHO == true)
			$this->DEBUG_ECHO = true;
	}

	public function processAll($releaseToWork='', $threads=1)
	{
		$this->processPredb();
		$this->processAdditional($releaseToWork);
		$this->processNfos($releaseToWork);
		$this->processMovies($releaseToWork);
		$this->processMusic($threads);
		$this->processGames($threads);
		$this->processAnime($threads);
		$this->processTv($releaseToWork);
		$this->processBooks($threads);
	}

	//
	// Fetch titles from predb sites.
	//
	public function processPredb()
	{
		$predb = new Predb($this->echooutput);
		$titles = $predb->combinePre();
		if ($titles > 0)
			$this->doecho("Fetched ".$titles." new title(s) from predb sources.");
	}

	//
	// Process nfo files.
	//
	public function processNfos($threads='')
	{
		if ($this->site->lookupnfo == 1)
		{
			$nfo = new Nfo($this->echooutput);
			$nfo->processNfoFiles($threads, $this->site->lookupimdb, $this->site->lookuptvrage);
		}
	}

	//
	// Lookup imdb if enabled.
	//
	public function processMovies($threads='')
	{
		if ($this->site->lookupimdb == 1)
		{
			$movie = new Movie($this->echooutput);
			$movie->processMovieReleases($threads);
		}
	}

	//
	// Lookup music if enabled.
	//
	public function processMusic($threads='')
	{
		if ($this->site->lookupmusic == 1)
		{
			$music = new Music($this->echooutput);
			$music->processMusicReleases($threads);
		}
	}

	//
	// Lookup games if enabled.
	//
	public function processGames($threads='')
	{
		if ($this->site->lookupgames == 1)
		{
			$console = new Console($this->echooutput);
			$console->processConsoleReleases($threads);
		}
	}

	//
	// Lookup anidb if enabled - always run before tvrage.
	//
	public function processAnime($threads='')
	{
		if ($this->site->lookupanidb == 1)
		{
			$anidb = new AniDB($this->echooutput);
			$anidb->animetitlesUpdate($threads);
			$anidb->processAnimeReleases($threads);
		}
	}

	//
	// Process all TV related releases which will assign their series/episode/rage data.
	//
	public function processTv($threads='')
	{
		if ($this->site->lookuptvrage == 1)
		{
			$tvrage = new TVRage($this->echooutput);
			$tvrage->processTvReleases($threads, $this->site->lookuptvrage==1);
		}
	}

	//
	// Process books using amazon.com.
	//
	public function processBooks($threads='')
	{
		if ($this->site->lookupbooks == 1)
		{
			$books = new Books($this->echooutput);
			$books->processBookReleases($threads);
		}
	}

	//
	// Sort a multidimensional array using one subkey.
	//
	function subval_sort($a,$subkey)
	{
		foreach($a as $k=>$v)
			$b[$k] = strtolower($v[$subkey]);

		natcasesort($b);

		foreach($b as $k=>$v)
			$c[] = $a[$k];

		return $c;
	}

	//
	// Comparison function for usort, for sorting nzb file content.
	//
	function sortrar($a, $b)
	{
		$pos = 0;
		$af = $bf = false;
		$a = preg_replace('/[0-9]+[ \-\.\_]?(\/|\||[o0]f)[ \-\.\_]?[0-9]+?(?![ \-\.\_][0-9])/i', ' ', $a["title"]);
		$b = preg_replace('/[0-9]+[ \-\.\_]?(\/|\||[o0]f)[ \-\.\_]?[0-9]+?(?![ \-\.\_][0-9])/i', ' ', $b["title"]);

		if (preg_match("/\.(part\d+|r\d+)(\.rar)*($|[ \"\)\]\-])/i", $a))
			$af = true;
		if (preg_match("/\.(part\d+|r\d+)(\.rar)*($|[ \"\)\]\-])/i", $b))
			$bf = true;

		if (!$af && preg_match("/\.(rar)($|[ \"\)\]\-])/i", $a))
		{
			$a = preg_replace('/\.(rar)(?:$|[ \"\)\]\-])/i', '.*rar', $a);
			$af = true;
		}
		if (!$bf && preg_match("/\.(rar)($|[ \"\)\]\-])/i", $b))
		{
			$b = preg_replace('/\.(rar)(?:$|[ \"\)\]\-])/i', '.*rar', $b);
			$bf = true;
		}

		if (!$af && !$bf )
			return strnatcasecmp($a,$b);
		elseif (!$bf)
			return -1;
		elseif (!$af)
			return 1;

		if ($af && $bf)
			$pos = strnatcasecmp($a,$b);
		elseif ($af)
			$pos = -1;
		elseif ($bf)
			$pos = 1;

		return $pos;
	}

	//
	// Check for passworded releases, RAR contents and Sample/Media info.
	//
	public function processAdditional($releaseToWork = '', $id = '', $gui = false)
	{
		$ri = new ReleaseImage();
		$update_files = false;

		$maxattemptstocheckpassworded = 5;
		$tries = ($maxattemptstocheckpassworded * -1) -1;
		$processSample = ($this->site->ffmpegpath != '') ? true : false;
		$processVideo = ($this->site->processvideos == "0") ? false : true;
		$processMediainfo = ($this->site->mediainfopath != '') ? true : false;
		$processAudioinfo = ($this->site->mediainfopath != '') ? true : false;
		$processJPGSample = ($this->site->processjpg == "0") ? false : true;
		$processPasswords = ($this->site->unrarpath != '') ? true : false;
		$this->tmpPath = $this->site->tmpunrarpath;

		$nntp = new Nntp();
		$connect = ($this->site->alternate_nntp == "1") ? $nntp->doConnect_A() : $nntp->doConnect();

		if (substr($this->tmpPath, -strlen( '/' ) ) != '/')
			$this->tmpPath = $this->tmpPath.'/';

		$tmpPath1 = $this->tmpPath;

		if ($gui)
		{
			$ok = false;
			while (!$ok) {
				usleep(mt_rand(10,300));
				$this->db->setAutoCommit(false);
				$ticket = $this->db->queryOneRow("SELECT value  FROM `site` WHERE `setting` LIKE 'nextppticket'");
				$ticket = $ticket["value"];
				$this->db->queryDirect(sprintf("UPDATE `nZEDb`.`site` SET `value` = %d WHERE `setting` LIKE 'nextppticket' AND `value` = %d", $ticket + 1, $ticket));
				if ($this->db->getAffectedRows() == 1)
				{
					$ok = true;
					$this->db->Commit();
				}
				else
					$this->db->Rollback();
			}
			$this->db->setAutoCommit(true);

			$sleep = 1;

			$delay = 100;

			do
			{
				sleep($sleep);
				$serving = $this->db->queryOneRow("SELECT *  FROM `site` WHERE `setting` LIKE 'currentppticket1'");
				$time = strtotime($serving["updateddate"]);
				$serving = $serving["value"];
				$sleep = min(max(($time + $delay - time()) / 5, 2), 15);

			} while ($serving > $ticket && ($time + $delay + 5 * ($ticket - $serving)) > time());
		}

		//
		// Get out all releases which have not been checked more than max attempts for password.
		//
		if ($id != '')
		{
			$query = sprintf("select r.ID, r.guid, r.name, c.disablepreview, r.size, r.groupID, r.nfostatus from releases r left join category c on c.ID = r.categoryID where r.ID = %d", $id);
			$result = $this->db->query($query);
		}
		else
		{
			if ($releaseToWork == '')
			{
				$i = -1;
				$result = 0;
				while ((count($result) != $this->addqty) && ($i >= $tries))
				{
					$result = $this->db->query(sprintf("select r.ID, r.guid, r.name, c.disablepreview, r.size, r.groupID, r.nfostatus from releases r
						left join category c on c.ID = r.categoryID
						where r.size < %s and r.passwordstatus between %d and -1 and (r.haspreview = -1 and c.disablepreview = 0) and nzbstatus = 1
						order by r.postdate desc limit %d", $this->maxsize*1073741824, $i, $this->addqty));
					if (count($result) > 0)
						$this->doecho("Passwordstatus = ".$i.": Available to process = ".count($result));
					$i--;
				}
			}
			else
			{
				$result = 0;
				$pieces = explode("                       ", $releaseToWork);
				$result = array(array('ID' => $pieces[0], 'guid' => $pieces[1], 'name' => $pieces[2], 'disablepreview' => $pieces[3], 'size' => $pieces[4], 'groupID' => $pieces[5], 'nfostatus' => $pieces[6]));
			}
		}

		$rescount = count($result);
		if ($rescount > 0)
		{
		//	if ($this->echooutput)
		//	{
		//		echo "(following started at: ".date("D M d, Y G:i a").")\nAdditional post-processing on {$rescount} release(s)\n";
		//		if ($releaseToWork != '')
		//			echo ", working 1 release: ";
		//		else
		//			$ppcount = $this->db->queryOneRow("SELECT COUNT(*) as cnt FROM releases r LEFT JOIN category c on c.ID = r.categoryID WHERE nzbstatus = 1 AND (r.passwordstatus BETWEEN -5 AND -1) AND (r.haspreview = -1 AND c.disablepreview = 0)");
		//	}

			if ($rescount > 1)
			{
				$this->doecho("\nFetch for: b = binary, f= failed binary, s = sample, m = mediainfo, a = audio, j = jpeg");
				$this->doecho("^ added file content, o added previous, z = doing zip, r = doing rar, n = found nfo");
			}

			// Loop through the releases.
			foreach ($result as $rel)
			{
				// Per release defaults.
				$this->tmpPath = $tmpPath1.$rel['guid'].'/';
				if (!is_dir($this->tmpPath))
				{
					$old = umask(0777);
					mkdir("$this->tmpPath", 0777, true);
					chmod("$this->tmpPath", 0777);
					umask($old);

					if (!is_dir("$this->tmpPath"))
					{
						trigger_error("{$this->tmpPath} was not created");
						exit (0);
					}
				}

				if ($this->echooutput)
					echo "\n".$rel['guid']."->";
				// Only attempt sample if not disabled.
				$blnTookSample =  ($rel["disablepreview"] == 1) ? true : false;
				$blnTookMediainfo = $blnTookAudioinfo = $blnTookJPG = $blnTookVideo = false;
				$passStatus = array(Releases::PASSWD_NONE);

/*				if ($this->echooutput && $threads > 0)
					$this->consoleTools->overWrite(" ".$rescount--." left..".(($this->DEBUG_ECHO) ? "{$rel['guid']} " : ""));
				else if ($this->echooutput)
					$this->consoleTools->overWrite(", ".$rescount--." left in queue, ".$ppcount["cnt"]--." total in DB..".(($this->DEBUG_ECHO) ? "{$rel['guid']} " : ""));
*/
				// Go through the nzb for this release looking for a rar, a sample, and a mediafile.
				$nzbcontents = new NZBcontents(true);
				$nzb = new NZB(true);
				$groups = new Groups();
				$groupName = $groups->getByNameByID($rel["groupID"]);

				$bingroup = $samplegroup = $mediagroup = $jpggroup = $audiogroup = "";
				$samplemsgid = $mediamsgid = $audiomsgid = $jpgmsgid = $audiotype = $mid = array();
				$hasrar = 0;
				$flood = false;
				$ignoredbooks = 0;
				$failed = 0;
				$this->password = $notmatched = false;

				$nzbpath = $nzb->getNZBPath($rel["guid"], $this->site->nzbpath, false, $this->site->nzbsplitlevel);

				if (!file_exists($nzbpath))
					continue;

				ob_start();
				@readgzfile($nzbpath);
				$nzbfile = ob_get_contents();
				ob_end_clean();

				$nzbfiles = $nzb->nzbFileList($nzbfile);
				if (!$nzbfiles)
					continue;

				usort($nzbfiles, "PostProcess::sortrar");

				foreach ($nzbfiles as $nzbcontents)
				{
					// Check if it's not a nfo, par2 etc...
					if (preg_match($this->supportfiles."|nfo\b|inf\b|ofn\b)($|[ \"\)\]\-])(?!.{20,})/i",$nzbcontents["title"]))
						continue;

					// Check if it's a rar/zip.
					if (preg_match("/\.(part0*1|part0+|r0+|r0*1|0+|0*10?|zip)(\.rar)*($|[ \"\)\]\-])/i", $nzbcontents["title"]))
						$hasrar= 1;
					elseif (preg_match("/\.rar($|[ \"\)\]\-])/i", $nzbcontents["title"]))
						$hasrar= 1;
					elseif (!$hasrar)
						$notmatched = true;

					// Look for a sample.
					if ($processSample && preg_match("/sample/i", $nzbcontents["title"]) && !preg_match("/\.(jpg|jpeg)/i", $nzbcontents["title"]))
					{
						if (isset($nzbcontents["segments"]) && empty($samplemsgid))
						{
							$samplegroup = $groupName;
							$samplemsgid[] = $nzbcontents["segments"][0];

							for($i=1; $i < $this->segmentstodownload; $i++)
							{
								if (count($nzbcontents["segments"]) > $i)
									$samplemsgid[] = $nzbcontents["segments"][$i];
							}
						}
					}

					// Look for a media file.
					elseif ($processMediainfo && preg_match('/'.$this->videofileregex.'[\. "\)\]]/i', $nzbcontents["title"]) && !preg_match("/sample/i", $nzbcontents["title"]))
					{
						if (isset($nzbcontents["segments"]) && empty($mediamsgid))
						{
							$mediagroup = $groupName;
							$mediamsgid[] = $nzbcontents["segments"][0];
						}
					}

					// Look for a audio file.
					elseif ($processAudioinfo && preg_match('/'.$this->audiofileregex.'[\. "\)\]]/i', $nzbcontents["title"], $type))
					{
						if (isset($nzbcontents["segments"]) && empty($audiomsgid))
						{
							$audiogroup = $groupName;
							$audiotype = $type[1];
							$audiomsgid[] = $nzbcontents["segments"][0];
						}
					}

					// Look for a JPG picture.
					elseif (!preg_match('/flac|lossless|mp3|music|inner-sanctum|sound/i', $groupName) && $processJPGSample && preg_match('/\.(jpg|jpeg)[\. "\)\]]/i', $nzbcontents["title"]))
					{
						if (isset($nzbcontents["segments"]) && empty($jpgmsgid))
						{
							$jpggroup = $groupName;
							$jpgmsgid[] = $nzbcontents["segments"][0];
							if (count($nzbcontents["segments"]) > 1)
								$jpgmsgid[] = $nzbcontents["segments"][1];
						}
					}
					elseif (preg_match($this->ignorebookregex, $nzbcontents["title"], $type))
					{
						$ignoredbooks++;
					}
				}

				// If this release has release files, delete them.
				$oldreleasefiles = $this->db->query("SELECT * FROM `releasefiles` WHERE `releaseID` = ".$rel["ID"]);
				$this->db->query("DELETE FROM `releasefiles` WHERE `releaseID` = ".$rel["ID"]);

				// Process rar contents until 1G or 85% of file size is found (smaller of the two).
				$this->password = $foundcontent = false;
				$rarpart = array();

				if (count($nzbfiles) > 40 && $ignoredbooks * 2 >= count($nzbfiles))
				{
					echo " skipping book flood";
					$this->db->query($sql = sprintf("update releases set passwordstatus = 0, haspreview = 0, categoryID = 8050 where ID = %d", $rel["ID"]));
					$flood = true;
				}

				// Seperate the nzb content into the different parts (support files, archive segments and the first parts).
				if (!$flood && $hasrar && ($this->site->checkpasswordedrar > 0 || $processSample || $processMediainfo || $processAudioinfo))
				{

					$this->sum = 0;
					$this->size = 0;
					$this->segsize = 0;
					$this->adj = 0;
					$this->name = '';

					$foundcontent = false;
					$notinfinite = 0;
					$failed = 0;
					$this->ignorenumbered = false;

					// Loop through the files, attempt to find if passworded and files. Starting with what not to process.
					foreach ($nzbfiles as $rarFile)
					{
						if ($this->passchkattempts > 1)
						{
							if ($notinfinite > $this->passchkattempts)
								break;
						}
						else
						{
							if ($notinfinite > $this->partsqty)
								break;
						}

						if ($this->password)
						{
							$this->debug("-Skipping processing of rar {$rarFile['title']} was found to be passworded");
								break;
						}

						if (preg_match($this->supportfiles.")(?!.{20,})/i", $rarFile["title"]))
							continue;

						if (!preg_match("/\.\b(part\d+|rar|r00|r01|zipr\d{2,3}|zip|zipx)($|[ \"\)\]\-])/i", $rarFile["title"]))
						{
							$this->debug("Not matched and skipping ".$rarFile["title"]);
							continue;
						}

						if (preg_match("/\.\b(part\d+.rar)($|[ \"\)\]\-])/i", $rarFile["title"]) && !preg_match("/\.\b(part00.rar|part01.rar)($|[ \"\)\]\-])/i", $rarFile["title"]))
						{
							$this->debug("Not matched and skipping ".$rarFile["title"]);
							continue;
						}

/*						$size = $this->db->queryOneRow("SELECT SUM(releasefiles.`size`) AS size FROM `releasefiles` WHERE `releaseID` = ".$rel["ID"]);
						if (is_numeric($size["size"]) && $size["size"] > $bytes)
							continue;

						// Do 10% of files if the size didn't change, in order to grab a different archive volume.
						if (is_numeric($size["size"]) && $size["size"] == $lsize)
							$i++;
						else
							$i = 0;

						$lsize = $size["size"];
						if ($i > count($nzbfiles)/ 10)
						{
							//$this->doecho("New files don't seem to contribute.");
							continue;
						}
*/

						// Starting to look for content.
						$this->segsize = $rarFile["size"]/($rarFile["partsactual"]/$rarFile["partstotal"]);
						$this->sum = $this->sum + $this->adj * $this->segsize;
						if ($this->sum > $this->size || $this->adj == 0)
						{
							$mid = array_slice((array)$rarFile["segments"], 0, $this->segmentstodownload);

							$bingroup = $groupName;
							$connect;
							$fetchedBinary = $nntp->getMessages($bingroup, $mid);
							if ($this->echooutput)
								echo " b";

							if ($fetchedBinary !== false)
							{
								$notinfinite++;
								$relFiles = $this->processReleaseFiles($fetchedBinary, $rel["ID"], $rel["nfostatus"], $rarFile["title"]);
								if ($this->password)
								{
									$passStatus[] = Releases::PASSWD_RAR;
								}

								if ($relFiles === false)
								{
									$this->debug("\nError processing files {$rel['ID']}");
									continue;
								}
								else
								{
									// Flag to indicate only that the archive has content.
									$foundcontent = true;
								}
							}
							else
							{
								$notinfinite = $notinfinite + 0.2;
								$failed++;
								if ($this->echooutput)
									echo " f";
							}
						}
					}

					if (is_dir($this->tmpPath))
					{
						$files = scandir($this->tmpPath);
						$rar = new ArchiveInfo();
						if (count($files) > 0)
						{
							foreach($files as $file)
							{
								if (is_file($this->tmpPath.$file))
								{
									if (preg_match('/\.rar$/i', $file))
									{
										$rar->open($this->tmpPath.$file, true);
										if ($rar->error)
											continue;

										$tmpfiles = $rar->getArchiveFileList();
										if (isset($tmpfiles[0]["name"]))
										{
											foreach($tmpfiles as $r)
											{
												$range = mt_rand(0,99999);
												if (isset($r["range"]))
													$range = $r["range"];

												$r["range"] = $range;
												if (!isset($r["error"]) && !preg_match($this->supportfiles."|part\d+|r\d{1,3}|zipr\d{2,3}|\d{2,3}|zipx|zip|rar)(\.rar)?$/i", $r["name"]))
													$this->addfile($r, $rel["ID"], $rar);
											}
										}
									}
								}
							}
						}
						unset($rar);
					}
				}
				elseif ($hasrar == 1)
				{
					$passStatus[] = Releases::PASSWD_POTENTIAL;
				}

				if(!$foundcontent && $hasrar == 1)
				{
					$passStatus[] = Releases::PASSWD_POTENTIAL;
				}

				// Try to get image/mediainfo/audioinfo, using extracted files before downloading more data
				if (($blnTookSample === false || $blnTookAudioinfo === false || $blnTookMediainfo === false) && is_dir($this->tmpPath))
				{
					$files = @scandir($this->tmpPath);
					if (isset($files) && is_array($files) && count($files) > 0)
					{

						foreach ($files as $file)
						{
							if (is_file($this->tmpPath.$file))
							{
								if ($blnTookAudioinfo === false && $processAudioinfo && preg_match('/(.*)'.$this->audiofileregex.'$/i', $file, $name))
								{
									rename($this->tmpPath.$name[0], $this->tmpPath."audiofile.".$name[2]);
									$blnTookAudioinfo = $this->getAudioinfo($this->tmpPath, $this->site->ffmpegpath, $this->site->mediainfopath, $rel["guid"], $rel["ID"]);
									@unlink($this->tmpPath."sample.".$name[2]);
								}
								if ($processJPGSample && $blnTookJPG === false && preg_match("/\.(jpg|jpeg)$/",$file))
								{
									if (filesize($this->tmpPath.$file) < 15)
										continue;
									if (exif_imagetype($this->tmpPath.$file) === false)
										continue;
									$blnTookJPG = $ri->saveImage($rel["guid"].'_thumb', $this->tmpPath.$file, $ri->jpgSavePath, 650, 650);
									if ($blnTookJPG !== false)
										$this->db->query(sprintf("UPDATE releases SET jpgstatus = %d WHERE ID = %d", 1, $rel["ID"]));

								}
								if (preg_match('/(.*)'.$this->videofileregex.'$/i', $file, $name))
								{
									rename($this->tmpPath.$name[0], $this->tmpPath."sample.avi");
									if ($processSample && $blnTookSample === false)
										$blnTookSample = $this->getSample($this->tmpPath, $this->site->ffmpegpath, $rel["guid"]);
									if ($processVideo && $blnTookVideo === false)
										$blnTookVideo = $this->getVideo($this->tmpPath, $this->site->ffmpegpath, $rel["guid"]);
									if ($processMediainfo && $blnTookMediainfo === false)
										$blnTookMediainfo = $this->getMediainfo($this->tmpPath, $this->site->mediainfopath, $rel["ID"]);
									@unlink($this->tmpPath."sample.avi");

									if ($blnTookSample)
										break;
								}
							}
						}
						unset($files);
					}
				}

				// Download and process sample image.
				if(!empty($samplemsgid) && $processSample && $blnTookSample === false)
				{
					$connect;
					$sampleBinary = $nntp->getMessages($samplegroup, $samplemsgid);
					if ($this->echooutput)
						echo " s";
					if ($sampleBinary !== false)
					{
						if (strlen($sampleBinary) > 100)
						{
							$this->addmediafile($this->tmpPath.'sample_'.mt_rand(0,99999).'.avi', $sampleBinary);
							$blnTookSample = $this->getSample($this->tmpPath, $this->site->ffmpegpath, $rel["guid"]);
							if ($processVideo)
								$blnTookVideo = $this->getVideo($this->tmpPath, $this->site->ffmpegpath, $rel["guid"]);
						}
						unset($sampleBinary);
					}
				}

				// Download and process mediainfo. Also try to get a sample if we didn't get one yet.
				if (!empty($mediamsgid) && $processMediainfo && $blnTookMediainfo === false)
				{
					$connect;
					$mediaBinary = $nntp->getMessages($mediagroup, $mediamsgid);
					if ($this->echooutput)
						echo " m";
					if ($mediaBinary !== false)
					{
						if (strlen($mediaBinary ) > 100)
						{
							$mediafile = $this->tmpPath.'media.avi';
							$this->addmediafile($mediafile, $mediaBinary);
							$blnTookMediainfo = $this->getMediainfo($this->tmpPath, $this->site->mediainfopath, $rel["ID"]);

							if ($processSample && $blnTookSample === false)
								$blnTookSample = $this->getSample($this->tmpPath, $this->site->ffmpegpath, $rel["guid"]);
							if ($processVideo && $blnTookVideo === false)
								$blnTookVideo = $this->getVideo($this->tmpPath, $this->site->ffmpegpath, $rel["guid"]);

							unset($mediafile);
						}
						unset($mediaBinary);
					}
				}

				// Download audio file, use mediainfo to try to get the artist / album.
				if(!empty($audiomsgid) && $processAudioinfo && $blnTookAudioinfo === false)
				{
					$connect;
					$audioBinary = $nntp->getMessages($audiogroup, $audiomsgid);
					if ($this->echooutput)
						echo " a";
					if ($audioBinary !== false)
					{
						if (strlen($audioBinary) > 100)
						{
							$this->addmediafile($this->tmpPath.'audio.'.$audiotype, $audioBinary);
							$blnTookAudioinfo = $this->getAudioinfo($this->tmpPath, $this->site->ffmpegpath, $this->site->mediainfopath, $rel["guid"], $rel["ID"]);
						}
						unset($audioBinary);
					}
				}

				// Download JPG file.
				if(!empty($jpgmsgid) && $processJPGSample && $blnTookJPG === false)
				{
					$connect;
					$jpgBinary = $nntp->getMessages($jpggroup, $jpgmsgid);
					if ($this->echooutput)
						echo " j";
					if ($jpgBinary !== false)
					{
						$this->addmediafile($this->tmpPath."samplepicture.jpg", $jpgBinary);
						if (is_dir($this->tmpPath))
						{
							if (filesize($this->tmpPath."samplepicture.jpg") > 15 && exif_imagetype($this->tmpPath."samplepicture.jpg") !== false && $blnTookJPG === false)
							{
								$blnTookJPG = $ri->saveImage($rel["guid"].'_thumb', $this->tmpPath."samplepicture.jpg", $ri->jpgSavePath, 650, 650);
								if ($blnTookJPG !== false)
									$this->db->query(sprintf("UPDATE releases SET jpgstatus = %d WHERE ID = %d", 1, $rel["ID"]));
							}

							foreach(glob($this->tmpPath.'samplepicture.jpg') as $v)
							{
								@unlink($v);
							}
						}
						unset($jpgBinary);
					}
				}

				// Set up release values.
				$hpsql = '';
				if ($blnTookSample)
					$this->updateReleaseHasPreview($rel["guid"]);
				else
					$hpsql = ', haspreview = 0';

				if ($failed > 0 && ($failed / count($nzbfiles) > 0.7 || $notinfinite > $this->passchkattempts || $notinfinite > $this->partsqty))
				{
					if ($this->echooutput)
						echo "not viable";
					$passStatus[] = Releases::BAD_FILE;
				}

				$size = $this->db->queryOneRow("SELECT SUM(releasefiles.`size`) AS size FROM `releasefiles` WHERE `releaseID` = ".$rel["ID"]);
				if (max($passStatus) > 0)
					$sql = sprintf("update releases set passwordstatus = %d %s where ID = %d", max($passStatus), $hpsql, $rel["ID"]);
				elseif ($hasrar && ((isset($size["size"]) && (is_null($size["size"]) || $size["size"] == 0)) || !isset($size["size"])))
				{
					if (!$blnTookSample)
						$hpsql = '';
					$sql = sprintf("update releases set passwordstatus = passwordstatus - 1  %s where ID = %d",  $hpsql, $rel["ID"]);
				}
				else
					$sql = sprintf("update releases set passwordstatus = %s %s where ID = %d", Releases::PASSWD_NONE, $hpsql, $rel["ID"]);

				$this->db->query($sql);

				// If update_files is true, the add previously found files to releasefiles.
				if ($update_files)
				{
					$rf = new ReleaseFiles();
					foreach ($oldreleasefiles as $file)
					{
						$query = sprintf("SELECT *  FROM `releasefiles` WHERE `releaseID` = %d AND `name` LIKE '%s' AND `size` = %s", $rel["ID"], $file["name"], $file["size"]);
						$row = $this->db->queryOneRow($query);

						if ($row === false)
						{
							$rf->add($rel["ID"], $file["name"], $file["size"], $file["date"], $file["pass"]);
							if ($this->echooutput)
								echo "o";
						}
					}
					unset($rf);
				}

				// rarinnerfilecount - This needs to be done or else the magnifier on the site does not show up.
				$size = $this->db->queryOneRow(sprintf("SELECT count(releasefiles.releaseID) as count FROM releasefiles WHERE releasefiles.releaseID = %d", $rel["ID"]));
				if ($size["count"] > 0)
					$this->db->query(sprintf("UPDATE releases SET rarinnerfilecount = %d WHERE ID = %d", $size["count"], $rel["ID"]));

				// If samples exist from previous runs, set flags.
				if (file_exists($ri->imgSavePath.$rel["guid"]."_thumb.jpg"))
					$this->updateReleaseHasPreview($rel["guid"]);
				if(file_exists($ri->vidSavePath.$rel["guid"].".ogv"))
					$this->db->query(sprintf("UPDATE releases SET videostatus = 1 WHERE ID = %d", $rel["ID"]));
				if(file_exists($ri->jpgSavePath.$rel["guid"]."_thumb.jpg"))
					$this->db->query(sprintf("UPDATE releases SET jpgstatus = %d WHERE ID = %d", 1, $rel["ID"]));

				// Erase all files and directory.
				foreach(glob($this->tmpPath.'*') as $v)
				{
					@unlink($v);
				}

				foreach(glob($this->tmpPath.'.*') as $v)
				{
					@unlink($v);
				}

				@rmdir($this->tmpPath);
			}
			if ($this->echooutput)
				echo "\n";
		}
		if ($gui)
			$this->db->queryDirect(sprintf("UPDATE `nZEDb`.`site` SET `value` = %d WHERE `setting` LIKE 'currentppticket1'", $ticket + 1));

		$nntp->doQuit();
		unset($nntp, $this->consoleTools, $rar, $nzbcontents, $groups, $ri);
	}

	function doecho($str)
	{
		if ($this->echooutput)
			echo $str."\n";
	}

	function debug($str)
	{
		if ($this->echooutput && $this->DEBUG_ECHO)
		   echo $str."\n";
	}

	function addmediafile ($file, $data)
	{
		if (@file_put_contents($file, $data) !== false)
		{
		@$xmlarray = runCmd('"'.$this->site->mediainfopath.'" --Output=XML "'.$file.'"');
		if (is_array($xmlarray))
		{
			$xmlarray = implode("\n",$xmlarray);
			$xmlObj = @simplexml_load_string($xmlarray);
			$arrXml = objectsIntoArray($xmlObj);
			if (!isset($arrXml["File"]["track"][0]))
				unlink($file);
		}
	}
	}

	function addfile($v, $relid, $rar = false)
	{
		// Only process if not a support file, or file segment.
		if (!isset($v["error"]) && !preg_match($this->supportfiles.")$/i", $v["name"]))
		{
			if ($rar !==  false)
				$tmpdata = $rar->getFileData($v["name"], $v["source"]);
			else
				$tmpdata = false;

			if (preg_match("/\.zip$/i", $v["name"]))
			{
//				$files = $this->processReleaseZips($tmpdata, false, false, $relid);
//				var_dump($files);
			}

			$rf = new ReleaseFiles();
			if ($rf->add($relid, $v["name"], $v["size"], $v["date"], $v["pass"]))
			{
				if ($this->echooutput)
					echo "^";
			}

			if ($tmpdata !== false)
			{
				// Extract a NFO from the rar.
				if ($v["size"] > 100 && $v["size"] < 100000 && preg_match("/(\.(nfo|inf|ofn)|info.txt)$/i", $v["name"]))
				{
					$nzbcontents = new NZBcontents(true);
					if ($nzbcontents->isNFO($tmpdata))
					{
						$nfo = new Nfo($this->echooutput);
						$nfo->addReleaseNfo($relid);
						$this->db->query(sprintf("UPDATE releasenfo SET nfo = compress(%s) WHERE releaseID = %d", $this->db->escapeString($tmpdata), $relid));
						$this->db->query(sprintf("UPDATE releases SET nfostatus = 1 WHERE ID = %d", $relid));
						if ($this->echooutput)
							echo "n";
					}
				}
				// Extract a video file from the compressed file.
				elseif (preg_match('/'.$this->videofileregex.'$/i', $v["name"]))
				{
					$this->addmediafile($this->tmpPath.'sample_'.mt_rand(0,99999).".avi", $tmpdata);
				}
				// Extract an audio file from the compressed file.
				elseif (preg_match('/'.$this->audiofileregex.'$/i', $v["name"], $ext))
				{
					$this->addmediafile($this->tmpPath.'audio_'.mt_rand(0,99999).$ext[0], $tmpdata);
				}
				else
				{
					if (preg_match('/([^\/\\\r]+)(\.[a-z][a-z0-9]{2,3})$/i', $v["name"], $name))
						$this->addmediafile($this->tmpPath.$name[1].mt_rand(0,99999).$name[2], $tmpdata);
				}
			}
			unset($tmpdata, $rf);
		}
	}

	// Open the zip, see if it has a password, attempt to get a file.
	function processReleaseZips($fetchedBinary, $open = false, $data = false, $relid = 0)
	{
		// Load the ZIP file or data.
		$zip = new ZipInfo();

		if ($open)
			$zip->open($fetchedBinary, true);
		else
			$zip->setData($fetchedBinary, true);

		if ($zip->error)
		{
		  $this->debug("Error: {$zip->error}");
		  return false;
		}

		if ($zip->isEncrypted)
		{
			$this->debug("Archive is password encrypted.");
			$this->password = true;
			return false;
		}

		$files = $zip->getFileList();
		if ($this->echooutput)
			echo "z";
		$dataarray = array();
		if ($files !== false)
		{
			foreach ($files as $file)
			{
				$thisdata = $zip->getFileData($file["name"]);
				$dataarray[] = array('zip'=>$file, 'data'=>$thisdata);
				// Extract a NFO from the rar.
				if ($file["size"] < 100000 && preg_match("/\.(nfo|inf|ofn)$/i", $file["name"]))
				{
					$nzbcontents = new NZBcontents(true);
					if ($nzbcontents->isNFO($thisdata) && $relid > 0)
					{
						$this->debug("adding zip nfo");
						$nfo = new Nfo($this->echooutput);
						$nfo->addReleaseNfo($relid);
						$this->db->query(sprintf("UPDATE releasenfo SET nfo = compress(%s) WHERE releaseID = %d", $this->db->escapeString($thisdata), $relid));
						$this->db->query(sprintf("UPDATE releases SET nfostatus = 1 WHERE ID = %d", $relid));
						if ($this->echooutput)
							echo "n";
					}
				}
				elseif (preg_match("/\.(r\d+|part\d+|rar)$/i", $file["name"]))
				{
					$tmpfiles = $this->getRar($thisdata);
					if ($tmpfiles != false)
						foreach ($tmpfiles as $f)
						{
							$ret = $this->addfile($f, $relid);
							$files[] = $f;
						}
				}
			}
		}

		if ($data)
		{
			$files = $dataarray;
			unset ($dataarray);
		}

		unset($fetchedBinary, $zip);
		return $files;
	}

	function getRar($fetchedBinary)
	{
		$rar = new ArchiveInfo();
		$files = false;
		if ($rar->setData($fetchedBinary, true))
			$files = $rar->getArchiveFileList();
		if ($rar->error)
		{
			$this->debug("Error: {$rar->error}");
			return false;
		}

		if ($rar->isEncrypted)
		{
			$this->debug("Archive is password encrypted.");
			$this->password = true;
			return false;
		}
		$tmp = $rar->getSummary(true, false);
		if (isset($tmp["is_encrypted"]) && $tmp["is_encrypted"] != 0)
		{
			$this->debug("Archive is password encrypted.");
			$this->password = true;
			return false;
		}
		$files = $rar->getArchiveFileList();
		if ($this->echooutput)
			echo "r";
		$retval = array();
		if ($files !== false)
		{
			foreach ($files as $file)
			{
				if (isset($file["name"]))
				{
					if (isset($file["error"]))
					{
						$this->debug("Error: {$file['error']} (in: {$file['source']})");
						continue;
					}
					if ($file["pass"] == true)
					{
						$this->password = true;
						break;
					}
					if (preg_match($this->supportfiles.")(?!.{20,})/i", $file["name"]))
						continue;
					if (preg_match("/([^\/\\\\]+)(\.[a-z][a-z0-9]{2,3})$/i", $file["name"], $name))
					{
						$rarfile = $this->tmpPath.$name[1].mt_rand(0,99999).$name[2];
						$fetchedBinary = $rar->getFileData($file["name"], $file["source"]);
						$this->addmediafile($rarfile, $fetchedBinary);
					}
					if (!preg_match("/\.(r\d+|part\d+)$/i", $file["name"]))
						$retval[] = $file;
				}
			}
		}

		if (count($retval) == 0)
			return false;
		return $retval;
	}

	// Open the rar, see if it has a password, attempt to get a file.
	function processReleaseFiles($fetchedBinary, $relid, $nfostatus, $name)
	{
		$retval = array();
		$rar = new ArchiveInfo();
		$rf = new ReleaseFiles();
		$this->password = false;

//		echo "\n$name ".preg_match("/\.(part\d+|rar|r\d{1,3})($|[ \"\)\]\-])/i", $name)."\n";

		if (preg_match("/\.(part\d+|rar|r\d{1,3})($|[ \"\)\]\-])/i", $name))
		{
			$rar->setData($fetchedBinary, true);
			if ($rar->error)
			{
				$this->debug("Error: {$rar->error}");
				return false;
			}

			$tmp = $rar->getSummary(true, false);
			if (preg_match('/par2/i', $tmp["main_info"]))
				return false;

			if (isset($tmp["is_encrypted"]) && $tmp["is_encrypted"] != 0)
			{
				$this->debug("Archive is password encrypted.");
				$this->password = true;
				return false;
			}

			if ($rar->isEncrypted)
			{
				$this->debug("Archive is password encrypted.");
				$this->password = true;
				return false;
			}

			$files = $rar->getArchiveFileList();
			if ($this->echooutput)
				echo "r";

			if (count($files) == 0)
				return false;

			if ($files[0]["compressed"] == 0 && $files[0]["name"] != $this->name)
			{
				$this->name = $files[0]["name"];
				$this->size = $files[0]["size"] * 0.95;
				$this->sum = 0;
				$this->adj = 0;
				// If archive is not stored compressed, process data
				foreach ($files as $file)
				{
//					var_dump($file);
					if (isset($file["name"]))
					{
						if (isset($file["error"]))
						{
							$this->debug("Error: {$file['error']} (in: {$file['source']})");
							continue;
						}
						if ($file["pass"] == true)
						{
							$this->password = true;
							break;
						}

						if (preg_match($this->supportfiles.")(?!.{20,})/i", $file["name"]))
							continue;

						if (preg_match('/\.zip$/i', $file["name"]))
						{
							$zipdata = $rar->getFileData($file["name"], $file["source"]);
							$data = $this->processReleaseZips($zipdata, false, true , $relid);

							if ($data != false)
							{
								foreach($data as $d)
								{
									if (preg_match('/\.(part\d+|r\d+|rar)(\.rar)?$/i', $d["zip"]["name"]))
									{
										$tmpfiles = $this->getRar($d["data"]);
									}
								}
							}
						}

						if (!isset($file["next_offset"]))
							$file["next_offset"] = 0;
						$range = mt_rand(0,99999);
						if (isset($file["range"]))
							$range = $file["range"];
						$retval[] = array('name'=>$file["name"], 'source'=>$file["source"], 'range'=>$range, 'size'=>$file["size"], 'date'=>$file["date"], 'pass'=>$file["pass"], 'next_offset'=>$file["next_offset"]);
						$this->adj = $file["next_offset"] + $this->adj;
					}
				}

				$this->sum = $this->adj;

				$this->adj = $this->adj / $this->segsize;

				if ($this->adj < .7)
					$this->adj = 1;

			}
			else
			{
				$this->size = $files[0]["size"] * 0.95;
				if ($this->name != $files[0]["name"])
				{
					$this->name = $files[0]["name"];
					$this->sum = $this->segsize;
					$this->adj = 1;
				}

				// File is compressed, use unrar to get the content
				$this->debug($this->tmpPath);
				$rarfile = $this->tmpPath."rarfile".mt_rand(0,99999).".rar";
				file_put_contents($rarfile, $fetchedBinary);
				$execstring = '"'.$this->site->unrarpath.'" e -ai -ep -c- -id -inul -kb -or -p- -r -y "'.$rarfile.'" "'.$this->tmpPath.'"';
				$output = runCmd($execstring, false, true);
				if (isset($files[0]["name"]))
				{
					foreach ($files as $file)
					{
						if (isset($file["name"]))
						{
							if (!isset($file["next_offset"]))
								$file["next_offset"] = 0;
							$range = mt_rand(0,99999);
							if (isset($file["range"]))
								$range = $file["range"];

							$retval[] = array('name'=>$file["name"], 'source'=>$file["source"], 'range'=>$range, 'size'=>$file["size"], 'date'=>$file["date"], 'pass'=>$file["pass"], 'next_offset'=>$file["next_offset"]);
						}
					}
				}
			}
		}
		else
		{
			// Not a rar file, try it as a ZIP file.
			$files = $this->processReleaseZips($fetchedBinary, false, false , $relid);
			if ($files !== false)
			{
				$this->name = $files[0]["name"];
				$this->size = $files[0]["size"] * 0.95;
				$this->sum = 0;
				$this->adj = 0;

				foreach ($files as $file)
				{
					if ($file["pass"])
					{
						$this->password = true;
						break;
					}

					if (!isset($file["next_offset"]))
							$file["next_offset"] = 0;
					if (!isset($file["range"]))
						$file["range"] = 0;

					$retval[] = array('name'=>$file["name"], 'source'=>"main", 'range'=>$file["range"], 'size'=>$file["size"], 'date'=>$file["date"], 'pass'=>$file["pass"], 'next_offset'=>$file["next_offset"]);
					$this->adj = $file["next_offset"] + $this->adj;
					$this->sum = $file["size"] + $this->sum;
				}

				$this->size = $this->sum;
				$this->sum = $this->adj;
				$this->adj = $this->adj / $this->segsize;

				if ($this->adj < .7)
					$this->adj = 1;

			}
			else
			// Not a compressed file, but segmented.
				$this->ignorenumbered = true;
		}

		// Use found content to populate releasefiles, nfo, and create multimedia files.
		foreach ($retval as $k => $v)
		{
			if (!preg_match($this->supportfiles."|part\d+|r\d{1,3}|zipr\d{2,3}|\d{2,3}|zipx|zip|rar)(\.rar)?$/i", $v["name"]) && count($retval) > 0)
			{
				$this->addfile($v, $relid, $rar);
			}
			else
			{
				unset($retval[$k]);
			}
		}

		if (count($retval) == 0)
			$retval = false;
		unset($fetchedBinary, $rar, $rf, $nfo);
		return $retval;
	}

	// Attempt to get mediafio xml from a video file.
	public function getMediainfo($ramdrive,$mediainfo,$releaseID)
	{
		$retval = false;
		$processMediainfo = ($this->site->mediainfopath != '') ? true : false;
		if (!($processMediainfo && is_dir($ramdrive) && ($releaseID > 0)))
			return $retval;

		$mediafiles = glob($ramdrive.'*.*');
		if (is_array($mediafiles))
		{
			foreach($mediafiles as $mediafile)
			{
				if (is_file($mediafile) && filesize($mediafile) > 15 &&preg_match("/".$this->videofileregex."$/i",$mediafile))
				{
					@$xmlarray = runCmd('"'.$mediainfo.'" --Output=XML "'.$mediafile.'"');
					if (is_array($xmlarray))
					{
						$xmlarray = implode("\n",$xmlarray);
						$re = new ReleaseExtra();
						$re->addFull($releaseID,$xmlarray);
						$re->addFromXml($releaseID,$xmlarray);
						$retval = true;
					}
				}
			}
		}
		if ($retval !== false && $this->echooutput)
				echo "M";
		return $retval;
	}

	// Attempt to get mediainfo/sample/title from a audio file.
	public function getAudioinfo($ramdrive,$ffmpeginfo,$audioinfo,$releaseguid, $releaseID)
	{
		$retval = $audval = false;
		$processAudioinfo = ($this->site->mediainfopath != '') ? true : false;
		if (!($processAudioinfo && is_dir($ramdrive) && ($releaseID > 0)))
			return $retval;

		$catID = $this->db->queryOneRow(sprintf("SELECT categoryID as ID, relnamestatus, groupID FROM releases WHERE ID = %d", $releaseID));
		if (!preg_match('/^3\d{3}|7010/', $catID["ID"]))
			return $retval;

		$audiofiles = glob($ramdrive.'*.*');
		if (is_array($audiofiles))
		{
			foreach($audiofiles as $audiofile)
			{
				if (is_file($audiofile) && preg_match("/".$this->audiofileregex."$/i",$audiofile, $ext))
				{
					if ($retval === false)
					{
					@$xmlarray = runCmd('"'.$audioinfo.'" --Output=XML "'.$audiofile.'"');
					if (is_array($xmlarray))
					{
						$xmlarray = implode("\n",$xmlarray);
						$xmlObj = @simplexml_load_string($xmlarray);
						$arrXml = objectsIntoArray($xmlObj);
						if (isset($arrXml["File"]["track"]))
						{
							foreach ($arrXml["File"]["track"] as $track)
							{
								if (isset($track["Album"]) && isset($track["Performer"]) && !empty($track["Recorded_date"]))
								{
									if (preg_match('/(?:19|20)\d{2}/', $track["Recorded_date"], $Year))
										$newname = $track["Performer"]." - ".$track["Album"]." (".$Year[0].") ".strtoupper($ext[1]);
									else
										$newname = $track["Performer"]." - ".$track["Album"]." ".strtoupper($ext[1]);
									$category = new Category();
									$newcat = $category->determineCategory($newname, $catID["groupID"]);
										if ($catID["relnamestatus"] != "3")
									$this->db->query(sprintf("UPDATE releases SET searchname = %s, categoryID = %d, relnamestatus = 3 WHERE ID = %d", $this->db->escapeString($newname), $newcat, $releaseID));
									$re = new ReleaseExtra();
									$re->addFromXml($releaseID, $xmlarray);
									$retval = true;
										if($this->processAudioSample === false)
									break;
								}
							}
					}
				}
					}
					if($this->processAudioSample && $audval === false)
					{
						$output = runCmd('"'.$ffmpeginfo.'" -t 30 -i "'.$audiofile.'" -acodec libvorbis -loglevel quiet -y "'.$ramdrive.$releaseguid.'.ogg"');
						if (is_dir($ramdrive))
						{
							@$all_files = scandir($ramdrive,1);
							foreach($all_files as $file)
							{
								if(preg_match("/".$releaseguid."\.ogg/",$file))
								{
									if (filesize($ramdrive.$file) < 15)
										continue;

									@copy($ramdrive.$releaseguid.".ogg", $this->audSavePath.$releaseguid.".ogg");
									if(@file_exists($this->audSavePath.$releaseguid.".ogg"))
									{
										chmod($this->audSavePath.$releaseguid.".ogg", 0764);
										$this->db->query(sprintf("UPDATE releases SET audiostatus = 1 WHERE ID = %d",$releaseID));
										$audval = true;
									}
								}
							}

							// Clean up all files.
							foreach(glob($ramdrive.'*.ogg') as $v)
							{
								@unlink($v);
							}
							if ($retval === true && $audval === true)
								break;
						}
					}
				}
			}
		}
		if ($retval !== false && $this->echooutput)
			echo "A";
		return $retval;
	}

	// Attempt to get a sample image from a video file.
	public function getSample($ramdrive, $ffmpeginfo, $releaseguid)
	{
		$retval = false;
		$processSample = ($this->site->ffmpegpath != '') ? true : false;
		if (!($processSample && is_dir($ramdrive) && (strlen($releaseguid) > 0)))
			return $retval;

		$ri = new ReleaseImage();
		$samplefiles = glob($ramdrive.'*.*');
		if (is_array($samplefiles))
		{
			foreach($samplefiles as $samplefile)
			{
				if (is_file($samplefile) && preg_match("/".$this->videofileregex."$/i",$samplefile))
				{
					@$filecont = file_get_contents($samplefile, true, null, 0, 40);
					if (!preg_match($this->sigregex, $filecont) || strlen($filecont) <30)
						continue;

					//$cmd = '"'.$ffmpeginfo.'" -i "'.$samplefile.'" -loglevel quiet -f image2 -ss ' . $this->ffmpeg_image_time . ' -vframes 1 -y "'.$ramdrive.'"zzzz"'.mt_rand(0,9).mt_rand(0,9).mt_rand(0,9).'".jpg';
					//$output = runCmd($cmd);
					
					$sample_duration = exec($ffmpeginfo." -i ".$samplefile." 2>&1 | grep \"Duration\"| cut -d ' ' -f 4 | sed s/,// | awk '{ split($1, A, \":\"); split(A[3], B, \".\"); print 3600*A[1] + 60*A[2] + B[1] }'");
					if ($sample_duration > 100 || $sample_duration==0 || $sample_duration=="")
						$sample_duration=2;
					$output_file=$ramdrive."zzzz".mt_rand(0,9).mt_rand(0,9).mt_rand(0,9).".jpg";
					$output = exec($ffmpeginfo." -i ".$samplefile." -loglevel quiet -vframes 250 -y ".$output_file);
					$output = exec($ffmpeginfo." -i ".$samplefile." -loglevel quiet -vframes 1 -ss ".$sample_duration." -y ".$output_file);
					
					if (is_dir($ramdrive))
					{
						@$all_files = scandir($ramdrive,1);
						foreach ($all_files as $file)
						{
							if(preg_match("/zzzz\d{3}\.jpg/", $file) && !$retval)
							{
								if (filesize($ramdrive.$file) < 15)
									continue;
								if (exif_imagetype( $ramdrive.$file) === false)
									continue;

								$ri->saveImage($releaseguid.'_thumb', $ramdrive.$file, $ri->imgSavePath, 800, 600);
								if(file_exists($ri->imgSavePath.$releaseguid."_thumb.jpg"))
									$retval = true;
							}
						}

						// Clean up all files.
						foreach(glob($ramdrive.'*.jpg') as $v)
						{
							@unlink($v);
						}
						if ($retval === true)
							break;
					}
				}
			}
		}
		// If an image was made, return true, else return false.
		if ($retval !== false && $this->echooutput)
			echo "S";
		return $retval;
	}

	public function getVideo($ramdrive, $ffmpeginfo, $releaseguid)
	{
		$retval = false;
		$processSample = ($this->site->ffmpegpath != '') ? true : false;
		if (!($processSample && is_dir($ramdrive) && (strlen($releaseguid) > 0)))
			return $retval;

		$ri = new ReleaseImage();
		$samplefiles = glob($ramdrive.'*.*');
		if (is_array($samplefiles))
		{
			foreach($samplefiles as $samplefile)
			{
				if (is_file($samplefile) && preg_match("/".$this->videofileregex."$/i",$samplefile))
				{
					@$filecont = file_get_contents($samplefile, true, null, 0, 40);
					if (!preg_match($this->sigregex, $filecont) || strlen($filecont) <30)
						continue;

					$output = runCmd('"'.$ffmpeginfo.'" -i "'.$samplefile.'" -vcodec libtheora -filter:v scale=320:-1 -t ' . $this->ffmpeg_duration . ' -acodec libvorbis -loglevel quiet -y "'.$ramdrive."zzzz".$releaseguid.'.ogv"');

					if (is_dir($ramdrive))
					{
						@$all_files = scandir($ramdrive,1);
						foreach ($all_files as $file)
						{
							if(preg_match("/zzzz".$releaseguid."\.ogv/",$file))
							{
								if (filesize($ramdrive."zzzz".$releaseguid.".ogv") > 4096)
								{
									@copy($ramdrive."zzzz".$releaseguid.".ogv", $ri->vidSavePath.$releaseguid.".ogv");
									if(@file_exists($ri->vidSavePath.$releaseguid.".ogv"))
									{
										chmod($ri->vidSavePath.$releaseguid.".ogv", 0764);
										$this->db->query(sprintf("UPDATE releases SET videostatus = 1 WHERE guid = %s",$releaseguid));
										$retval = true;
									}
								}
							}
							if ($retval === true)
							{
								// Clean up all files.
								foreach(glob($ramdrive.'*.ogv') as $v)
								{
									@unlink($v);
								}
								break;
							}
						}
					}
				}
			}
		}
		// If an video was made, return true, else return false.
		if ($retval !== false && $this->echooutput)
			echo "V";
		return $retval;
	}

	public function updateReleaseHasPreview($guid)
	{
		$this->db->queryOneRow(sprintf("update releases set haspreview = 1 where guid = %s", $this->db->escapeString($guid)));
		if ($this->echooutput)
			echo "P";
	}
}
