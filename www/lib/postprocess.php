<?php
require_once(WWW_DIR."/lib/anidb.php");
require_once(WWW_DIR."/lib/books.php");
require_once(WWW_DIR."/lib/category.php");
require_once(WWW_DIR."/lib/console.php");
require_once(WWW_DIR."/lib/consoletools.php");
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/movie.php");
require_once(WWW_DIR."/lib/music.php");
require_once(WWW_DIR."/lib/nfo.php");
require_once(WWW_DIR."/lib/nntp.php");
require_once(WWW_DIR."/lib/nzbcontents.php");
require_once(WWW_DIR."/lib/predb.php");
require_once(WWW_DIR."/lib/rarinfo.php");
require_once(WWW_DIR."/lib/releases.php");
require_once(WWW_DIR."/lib/releaseextra.php");
require_once(WWW_DIR."/lib/releasefiles.php");
require_once(WWW_DIR."/lib/releaseimage.php");
require_once(WWW_DIR."/lib/rrarinfo.php");
require_once(WWW_DIR."/lib/site.php");
require_once(WWW_DIR."/lib/tvrage.php");
require_once(WWW_DIR."/lib/util.php");
require_once(WWW_DIR."/lib/zipinfo.php");

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
		$this->sleeptime = (!empty($site->postdelay)) ? $site->postdelay : 300;
		$this->processAudioSample = ($this->site->processaudiosample == "0") ? false : true;
		$this->audSavePath = WWW_DIR.'covers/audiosample/';

		$this->videofileregex = '\.(AVI|F4V|IFO|M1V|M2V|M4V|MKV|MOV|MP4|MPEG|MPG|MPGV|MPV|OGV|QT|RM|RMVB|TS|VOB|WMV)';
		$this->audiofileregex = '\.(AAC|AIFF|APE|AC3|ASF|DTS|FLAC|MKA|MKS|MP2|MP3|RA|OGG|OGM|W64|WAV|WMA)';
		$this->supportfiles = "/\.(vol\d{1,3}\+\d{1,3}|par2|srs|sfv|nzb";
		$this->DEBUG_ECHO = false;
		if (defined("DEBUG_ECHO") && DEBUG_ECHO == true)
			$this->DEBUG_ECHO = true;
	}

	public function processAll($threads=1)
	{
		$this->processPredb();
		$this->processAdditional($threads);
		$this->processNfos($threads);
		$this->processMovies($threads);
		$this->processMusic($threads);
		$this->processGames($threads);
		$this->processAnime($threads);
		$this->processTv($threads);
		$this->processBooks($threads);
	}

	//
	// Fetch titles from predb sites.
	//
	public function processPredb()
	{
		$predb = new Predb($this->echooutput);
		$titles = $predb->combinePre();
		if ($this->echooutput && $titles > 0)
			echo "Fetched ".$titles." new title(s) from predb sources.\n";
	}

	//
	// Process nfo files.
	//
	public function processNfos($threads=1)
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
	public function processMovies($threads=1)
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
	public function processMusic($threads=1)
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
	public function processGames($threads=1)
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
	public function processAnime($threads=1)
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
	public function processTv($threads=1)
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
	public function processBooks($threads=1)
	{
		if ($this->site->lookupbooks == 1)
		{
			$books = new Books($this->echooutput);
			$books->processBookReleases($threads);
		}
	}

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
	// Check for passworded releases, RAR contents and Sample/Media info.
	//
	public function processAdditional($threads=1, $id = '')
	{
		$db = new DB;
		$nntp = new Nntp;
		$consoleTools = new ConsoleTools();
		$rar = new RecursiveRarInfo();
		$ri = new ReleaseImage;
		$site = new Sites;
		if ($threads > 1)
		{
			usleep($this->sleeptime*1000*($threads - 1));
		}
		$threads--;
		$update_files = true;

		$maxattemptstocheckpassworded = 5;
		$tries = ($maxattemptstocheckpassworded * -1) -1;
		$processSample = ($this->site->ffmpegpath != '') ? true : false;
		$processVideo = ($this->site->processvideos == "0") ? false : true;
		$processMediainfo = ($this->site->mediainfopath != '') ? true : false;
		$processAudioinfo = ($this->site->mediainfopath != '') ? true : false;
		$processJPGSample = ($this->site->processjpg == "0") ? false : true;
		$processPasswords = ($this->site->unrarpath != '') ? true : false;
		$tmpPath = $this->site->tmpunrarpath;

		if (substr($tmpPath, -strlen( '/' ) ) != '/')
			$tmpPath = $tmpPath.'/';

		$tmpPath1 = $tmpPath;

		//
		// Get out all releases which have not been checked more than max attempts for password.
		//
		if ($id != '')
		{
			$query = sprintf("select r.ID, r.guid, r.name, c.disablepreview, r.size, r.groupID, r.nfostatus from releases r left join category c on c.ID = r.categoryID where r.ID = %d", $id);
			$result = $db->query($query);
		}
		else
		{
			$i = -1;
			$result = 0;
			while ((count($result) != $this->addqty) && ($i >= $tries))
			{
				$query = sprintf("select r.ID, r.guid, r.name, c.disablepreview, r.size, r.groupID, r.nfostatus from releases r
				left join category c on c.ID = r.categoryID
				where nzbstatus = 1 and (r.passwordstatus between %d and -1)
				AND (r.haspreview = -1 and c.disablepreview = 0) AND r.size < %s order by r.postdate desc limit %d,%d", $i, $this->maxsize*1073741824, floor(($this->addqty)*($threads * 1.5)), $this->addqty);
				$result = $db->query($query);
				if ($this->echooutput && count($result) > 0)
					echo "Passwordstatus = ".$i.": Available to process = ".count($result)."\n";
				$i--;
			}
		}

		$rescount = count($result);
		if ($rescount > 0)
		{
			if ($this->echooutput)
			{
				echo "(following started at: ".date("D M d, Y G:i a").")\nAdditional post-processing on {$rescount} release(s)";
				if ($threads > 0)
					echo ", starting at ".floor(($this->addqty) * ($threads * 1.5)).": ";
				else
					$ppcount = $db->queryOneRow("SELECT COUNT(*) as cnt FROM releases r LEFT JOIN category c on c.ID = r.categoryID WHERE nzbstatus = 1 AND (r.passwordstatus BETWEEN -5 AND -1) AND (r.haspreview = -1 AND c.disablepreview = 0)");
			}

			// Loop through the releases.
			foreach ($result as $rel)
			{
				// Per release defaults.
				$tmpPath = $tmpPath1.$rel["guid"].'/';
				if (!file_exists($tmpPath))
				{
					$old = umask(0764);
					mkdir("$tmpPath", 0764, true);
					chmod("$tmpPath", 0764);
					umask($old);

					if (!is_dir("$tmpPath"))
					{
						trigger_error("$tmpPath was not created");
						exit (0);
					}
				}

				// Only attempt sample if not disabled.
				$blnTookSample =  ($rel["disablepreview"] == 1) ? true : false;
				$blnTookMediainfo = $blnTookAudioinfo = $blnTookJPG = $blnTookVideo = false;
				$passStatus = array(Releases::PASSWD_NONE);

				if ($this->echooutput && $threads > 0)
					$consoleTools->overWrite(" ".$rescount--." left..".(($this->DEBUG_ECHO) ? "{$rel["guid"]} " : ""));
				else if ($this->echooutput)
					$consoleTools->overWrite(", ".$rescount--." left in queue, ".$ppcount["cnt"]--." total in DB..".(($this->DEBUG_ECHO) ? "{$rel["guid"]} " : ""));

				// Go through the nzb for this release looking for a rar, a sample, and a mediafile.
				$nzbcontents = new NZBcontents(true);
				$groups = new Groups;
				$groupName = $groups->getByNameByID($rel["groupID"]);

				$bingroup = $samplegroup = $mediagroup = $jpggroup = $audiogroup = "";
				$samplemsgid = $mediamsgid = $audiomsgid = $jpgmsgid = $audiotype = $mid = array();
				$hasrar = 0;
				$this->password = $notmatched = false;

				$nzbfiles = $nzbcontents->nzblist($rel["guid"]);
				if (!$nzbfiles)
					continue;

				foreach ($nzbfiles as $nzbcontents)
				{
					// Check if it's not a nfo, par2 etc...
					if (preg_match($this->supportfiles."|nfo|inf|ofn)/i",$nzbcontents["subject"]))
						continue;

					// Check if it's a rar/zip.
					if (preg_match("/\.(part0*1|part0+|r0+|r0*1|0+|0*10?|zip)(\.rar)*($|[ \"\)\]\-])/i", $nzbcontents["subject"]))
						$hasrar= 1;
					elseif (preg_match("/\.rar($|[ \"\)\]\-])/i", $nzbcontents["subject"]))
						$hasrar= 1;
					elseif (!$hasrar)
						$notmatched = true;

					// Look for a sample.
					if ($processSample && preg_match("/sample/i", $nzbcontents["subject"]) && !preg_match("/\.(jpg|jpeg)/i", $nzbcontents["subject"]))
					{
						if (isset($nzbcontents["segment"]) && empty($samplemsgid))
						{
							$samplegroup = $groupName;
							$samplemsgid[] = $nzbcontents["segment"][0];
							$samplemsgid[] = $nzbcontents["segment"][1];
						}
					}
					// Look for a media file.
					elseif ($processMediainfo && preg_match('/'.$this->videofileregex.'[\. "\)\]]/i', $nzbcontents["subject"]) && !preg_match("/sample/i", $nzbcontents["subject"]))
					{
						if (isset($nzbcontents["segment"]) && empty($mediamsgid))
						{
							$mediagroup = $groupName;
							$mediamsgid[] = $nzbcontents["segment"][0];
						}
					}
					// Look for a audio file.
					elseif ($processAudioinfo && preg_match('/'.$this->audiofileregex.'[\. "\)\]]/i', $nzbcontents["subject"], $type))
					{
						if (isset($nzbcontents["segment"]) && empty($audiomsgid))
						{
							$audiogroup = $groupName;
							$audiotype = $type[1];
							$audiomsgid[] = $nzbcontents["segment"][0];
						}
					}
					// Look for a JPG picture.
					elseif (!preg_match('/flac|lossless|mp3|music|inner-sanctum|sound/i', $groupName) && $processJPGSample && preg_match('/\.(jpg|jpeg)[\. "\)\]]/i', $nzbcontents["subject"]))
					{
						if (isset($nzbcontents["segment"]) && empty($jpgmsgid))
						{
							$jpggroup = $groupName;
							$jpgmsgid[] = $nzbcontents["segment"][0];
							$jpgmsgid[] = $nzbcontents["segment"][1];
						}
					}
				}

				// If this release has release files, delete them.
				$oldreleasefiles = $db->query("SELECT * FROM `releasefiles` WHERE `releaseID` = ".$rel["ID"]);
				$db->query("DELETE FROM `releasefiles` WHERE `releaseID` = ".$rel["ID"]);

				// Process rar contents until 1G or 85% of file size is found (smaller of the two).
				$bytes = min( 1024*1024*1024, $rel["size"] * 0.85);
				$this->password = $foundcontent = false;
				$lsize = $i = 0;
				$first = 'crazy';
				$rarpart = array();

				// Seperate the nzb content into the different parts (support files, archive segments and the first parts).
				if ($hasrar && ($this->site->checkpasswordedrar > 0 || $processSample || $processMediainfo || $processAudioinfo))
				{
					if (count($nzbfiles) > 1)
					{
						$nzbfiles = $this->subval_sort($nzbfiles, "subject");
						foreach ($nzbfiles as $k => $v)
						{
							if (preg_match($this->supportfiles."|nfo|inf|ofn)/i", $v["subject"]))
								continue;

							if (preg_match('/\.(rar|'.$first.')($|\")/i', $v["subject"]))
							{
								$rarpart[] = $nzbfiles[$k];
								unset($nzbfiles[$k]);
							}
							elseif ($first === 'crazy' && preg_match('/\.(part0*1|part0+|r0+|r0*1|0+|0*10?|zip)(\.rar)*($|\")/i', $v["subject"], $tmp))
							{
								$rarpart[] = $nzbfiles[$k];
								unset($nzbfiles[$k]);
								$first = $tmp[1];
							}
						}
					}
					// Process first segments (rar, part001, r01, etc) in order.
					sort($rarpart);
					// Shuffle the rest, in order to grab more content.
					shuffle($nzbfiles);

					if (count($rarpart) > 0)
						$nzbfiles = array_merge($rarpart, $nzbfiles);

					$foundcontent = false;
					$notinfinite = 0;
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
							if ($foundcontent === true)
								break;
							if ($notinfinite > $this->partsqty)
								break;
						}

						if ($this->password)
						{
							$this->doecho("-Skipping processing of rar {$rarFile["subject"]} was found to be passworded");
							break;
						}

						if (preg_match($this->supportfiles.")/i", $rarFile["subject"]))
							continue;

						if (!preg_match("/\.\b(part\d+|rar|r\d{1,3}|zipr\d{2,3}|zip|zipx)($|[ \"\)\]\-])/i", $rarFile["subject"]))
						{
							$this->doecho("Not matched and skipping ".$rarFile["subject"]);
							continue;
						}

						$size = $db->queryOneRow("SELECT SUM(releasefiles.`size`) AS size FROM `releasefiles` WHERE `releaseID` = ".$rel["ID"]);
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

						// Starting to look for content.
						$mid = array_slice((array)$rarFile["segment"], 0, 1);

						$nntp->doConnect();
						$bingroup = $groupName;
						$fetchedBinary = $nntp->getMessages($bingroup, $mid);
						if ($fetchedBinary !== false)
						{
							$notinfinite++;
							$relFiles = $this->processReleaseFiles($fetchedBinary, $tmpPath, $rel["ID"], $rel["nfostatus"]);
							if ($this->password)
								$passStatus[] = Releases::PASSWD_RAR;

							if ($relFiles === false)
							{
								$this->doecho("\nError processing files {$rel["ID"]}");
								continue;
							}
							else
								// Flag to indicate only that the archive has content.
								$foundcontent = true;
						}
						$nntp->doQuit();
					}
				}
				elseif ($hasrar == 1)
					$passStatus[] = Releases::PASSWD_POTENTIAL;

				if(!$foundcontent && $hasrar == 1)
					$passStatus[] = Releases::PASSWD_POTENTIAL;

				// Download and process sample image.
				if(!empty($samplemsgid) && $processSample && $blnTookSample === false)
				{
					$nntp->doConnect();
					$sampleBinary = $nntp->getMessages($samplegroup, $samplemsgid);
					if ($sampleBinary !== false)
					{
						if (strlen($sampleBinary) > 100)
						{
							@file_put_contents($tmpPath.'sample.avi', $sampleBinary);
							$blnTookSample = $this->getSample($tmpPath, $this->site->ffmpegpath, $rel["guid"]);
							if ($processVideo)
								$blnTookVideo = $this->getVideo($tmpPath, $this->site->ffmpegpath, $rel["guid"]);
						}
						unset($sampleBinary);
					}
					$nntp->doQuit();
				}

				// Download and process mediainfo. Also try to get a sample if we didn't get one yet.
				if (!empty($mediamsgid) && $processMediainfo && $blnTookMediainfo === false)
				{
					$nntp->doConnect();
					$mediaBinary = $nntp->getMessages($mediagroup, $mediamsgid);
					if ($mediaBinary !== false)
					{
						if (strlen($mediaBinary ) > 100)
						{
							$mediafile = $tmpPath.'media.avi';
							@file_put_contents($mediafile, $mediaBinary);
							$blnTookMediainfo = $this->getMediainfo($tmpPath, $this->site->mediainfopath, $rel["ID"]);

							if ($processSample && $blnTookSample === false)
								$blnTookSample = $this->getSample($tmpPath, $this->site->ffmpegpath, $rel["guid"]);
							if ($processVideo && $blnTookVideo === false)
								$blnTookVideo = $this->getVideo($tmpPath, $this->site->ffmpegpath, $rel["guid"]);

							unset($mediafile);
						}
						unset($mediaBinary);
					}
					$nntp->doQuit();
				}

				// Download audio file, use mediainfo to try to get the artist / album.
				if(!empty($audiomsgid) && $processAudioinfo && $blnTookAudioinfo === false)
				{
					$nntp->doConnect();
					$audioBinary = $nntp->getMessages($audiogroup, $audiomsgid);
					if ($audioBinary !== false)
					{
						if (strlen($audioBinary) > 100)
						{
							@file_put_contents($tmpPath.'audio.'.$audiotype, $audioBinary);
							$blnTookAudioinfo = $this->getAudioinfo($tmpPath, $this->site->ffmpegpath, $this->site->mediainfopath, $rel["guid"], $rel["ID"]);
						}
						unset($audioBinary);
					}
					$nntp->doQuit();
				}

				// Download JPG file.
				if(!empty($jpgmsgid) && $processJPGSample && $blnTookJPG === false)
				{
					$nntp->doConnect();
					$jpgBinary = $nntp->getMessages($jpggroup, $jpgmsgid);
					if ($jpgBinary !== false)
					{
						@file_put_contents($tmpPath."samplepicture.jpg", $jpgBinary);
						if (is_dir($tmpPath))
						{
							$blnTookJPG = $ri->saveImage($rel["guid"].'_thumb', $tmpPath."samplepicture.jpg", $ri->jpgSavePath, 650, 650);
							if ($blnTookJPG !== false)
								$db->query(sprintf("UPDATE releases SET jpgstatus = %d WHERE ID = %d", 1, $rel["ID"]));

							foreach(glob($tmpPath.'*.jpg') as $v)
							{
								@unlink($v);
							}
						}
						unset($jpgBinary);
					}
					$nntp->doQuit();
				}

				// Last attempt to get image/mediainfo/audioinfo, using extracted files.
				if (($blnTookSample === false || $blnTookAudioinfo === false || $blnTookMediainfo === false) && is_dir($tmpPath))
				{
					$files = @scandir($tmpPath);
					if (isset($files) && is_array($files) && count($files) > 0)
					{
						foreach ($files as $file)
						{
							if (is_file($tmpPath.$file))
							{
								if ($blnTookAudioinfo === false && $processAudioinfo && preg_match('/(.*)'.$this->audiofileregex.'$/i', $file, $name))
								{
									rename($tmpPath.$name[0], $tmpPath."audiofile.".$name[2]);
									$blnTookAudioinfo = $this->getAudioinfo($tmpPath, $this->site->ffmpegpath, $this->site->mediainfopath, $rel["guid"], $rel["ID"]);
									@unlink($tmpPath."sample.".$name[2]);
								}
								if ($processJPGSample && $blnTookJPG === false && preg_match("/\.jpg$/",$file))
								{
									$blnTookJPG = $ri->saveImage($rel["guid"].'_thumb', $tmpPath.$file, $ri->jpgSavePath, 650, 650);
									if ($blnTookJPG !== false)
										$db->query(sprintf("UPDATE releases SET jpgstatus = %d WHERE ID = %d", 1, $rel["ID"]));
								}
								if (preg_match('/(.*)'.$this->videofileregex.'$/i', $file, $name))
								{
									rename($tmpPath.$name[0], $tmpPath."sample.avi");
									if ($processSample && $blnTookSample === false)
										$blnTookSample = $this->getSample($tmpPath, $this->site->ffmpegpath, $rel["guid"]);
									if ($processVideo && $blnTookVideo === false)
										$blnTookVideo = $this->getVideo($tmpPath, $this->site->ffmpegpath, $rel["guid"]);
									if ($processMediainfo && $blnTookMediainfo === false)
										$blnTookMediainfo = $this->getMediainfo($tmpPath, $this->site->mediainfopath, $rel["ID"]);
									@unlink($tmpPath."sample.avi");

									if ($blnTookSample)
										break;
								}
							}
						}
						unset($files);
					}
				}

				// Set up release values.
				$hpsql = '';
				if ($blnTookSample)
					$this->updateReleaseHasPreview($rel["guid"]);
				else
					$hpsql = ', haspreview = 0';

				$size = $db->queryOneRow("SELECT SUM(releasefiles.`size`) AS size FROM `releasefiles` WHERE `releaseID` = ".$rel["ID"]);
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

				$db->query($sql);

				// If update_files is true, the add previously found files to releasefiles.
				if ($update_files)
				{
					$rf = new ReleaseFiles;
					foreach ($oldreleasefiles as $file)
					{
						$query = sprintf("SELECT *  FROM `releasefiles` WHERE `releaseID` = %d AND `name` LIKE '%s' AND `size` = %s", $rel["ID"], $file["name"], $file["size"]);
						$row = $db->queryOneRow($query);

						if ($row === false)
						{
							//$this->doecho("adding missing file ".$rel["guid"]);
							$rf->add($rel["ID"], $file["name"], $file["size"], $file["createddate"], $file["passworded"] );
						}
					}
					unset($rf);
				}

				// rarinnerfilecount - This needs to be done or else the magnifier on the site does not show up.
				$size = $db->queryOneRow(sprintf("SELECT count(releasefiles.releaseID) as count FROM releasefiles WHERE releasefiles.releaseID = %d", $rel["ID"]));
				if ($size["count"] > 0)
					$db->query(sprintf("UPDATE releases SET rarinnerfilecount = %d WHERE ID = %d", $size["count"], $rel["ID"]));

				// If samples exist from previous runs, set flags.
				if (file_exists($ri->imgSavePath.$rel["guid"]."_thumb.jpg"))
					$this->updateReleaseHasPreview($rel["guid"]);
				if(file_exists($ri->vidSavePath.$rel["guid"].".ogv"))
					$db->query(sprintf("UPDATE releases SET videostatus = 1 WHERE ID = %d", $rel["ID"]));
				if(file_exists($ri->jpgSavePath.$rel["guid"]."_thumb.jpg"))
					$db->query(sprintf("UPDATE releases SET jpgstatus = %d WHERE ID = %d", 1, $rel["ID"]));

				// Erase all files and directory.
				foreach(glob($tmpPath.'*') as $v)
				{
					@unlink($v);
				}

				foreach(glob($tmpPath.'.*') as $v)
				{
					@unlink($v);
				}

				@rmdir($tmpPath);
			}
			if ($this->echooutput)
				echo "\n";
		}
		unset($db, $nntp, $consoleTools, $rar, $nzbcontents, $groups, $ri);
	}

	function doecho($str)
	{
		if ($this->echooutput && $this->DEBUG_ECHO)
			echo $str."\n";
	}

	// Open the zip, see if it has a password, attempt to get a file.
	function processReleaseZips($fetchedBinary, $open = false, $data = false, $relid = 0, $db, $nfostatus)
	{
		// Load the ZIP file or data.
		$zip = new ZipInfo;

		if ($open)
			$zip->open($fetchedBinary, true);
		else
			$zip->setData($fetchedBinary, true);

		if ($zip->error)
		{
		  $this->doecho("Error: {$zip->error}");
		  return false;
		}

		if ($zip->isEncrypted)
		{
			$this->doecho("Archive is password encrypted.");
			$this->password = true;
			return false;
		}

		$files = $zip->getFileList();
		$dataarray = array();
		if ($files !== false)
		{
			foreach ($files as $file)
			{
				$thisdata = $zip->getFileData($file["name"]);
				$dataarray[] = array('zip'=>$file, 'data'=>$thisdata);
				// Extract a NFO from the rar.
				if ($nfostatus < 1 && $file["size"] < 100000 && preg_match("/\.(nfo|inf|ofn)$/i", $file["name"]))
				{
					$nzbcontents = new NZBcontents(true);
					if ($nzbcontents->isNFO($thisdata) && $relid > 0)
					{
						$this->doecho("adding zip nfo");
						$nfo = new Nfo($this->echooutput);
						$nfo->addReleaseNfo($relid);
						$db->query(sprintf("UPDATE releasenfo SET nfo = compress(%s) WHERE releaseID = %d", $db->escapeString($thisdata), $relid));
						$db->query(sprintf("UPDATE releases SET nfostatus = 1 WHERE ID = %d", $relid));
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
		$rar = new RecursiveRarInfo();
		if ($rar->setData($fetchedBinary, true))
			return $rar->getArchiveFileList();

		return false;
	}

	// Open the rar, see if it has a password, attempt to get a file.
	function processReleaseFiles($fetchedBinary, $tmpPath, $relid, $nfostatus)
	{
		$retval = array();
		$rar = new RecursiveRarInfo();
		$rf = new ReleaseFiles;
		$db = new DB;
		$this->password = false;

		if ($rar->setData($fetchedBinary, true))
		{
			if ($rar->error)
			{
				$this->doecho("Error: {$rar->error}");
				return false;
			}

			if ($rar->isEncrypted)
			{
				$this->doecho("Archive is password encrypted.");
				$this->password = true;
				return false;
			}

			$tmp = $rar->getSummary(true, false);
			if ($tmp["is_encrypted"])
			{
				$this->doecho("Archive is password encrypted.");
				$this->password = true;
				return false;
			}

			$files = $rar->getArchiveFileList();
			if ($files !== false && $files[0]["compressed"] != 1)
			{
				// If archive is not stored compressed, process data
				foreach ($files as $file)
				{
					if (isset($file["name"]))
					{
						if ($file["pass"] > 0)
						{
							$this->password = true;
							break;
						}

						if (isset($file["error"]))
						{
							$this->doecho("Error: {$file["error"]} (in: {$file["source"]})");
							continue;
						}

						if (preg_match($this->supportfiles.")/i", $file["name"]))
							continue;

						/*if (preg_match('/\.zip/i', $file["name"]))
						{
							$zipdata = $rar->getFileData($file["name"], $file["source"]);
							$data = $this->processReleaseZips($zipdata, false, true , $relid, $db);

 							foreach($data as $d)
							{
								if (preg_match('/\.rar/i', $d["zip"]["name"]))
								{
									$file = $this->getRar($d["data"]);
								}
							}
						}*/
						if (!preg_match("/\.\b(part\d+|rar|r\d{1,3}|zipr\d{2,3}|\d{2,3}|zipx)$/i", $file["name"]) && count($files) > 0)
						{
							$rf->add($relid, $file["name"], $file["size"], $file["date"], $file["pass"] );
							$range = mt_rand(0,32767);
							if (isset($file["range"]))
								$range = $file["range"];
							$retval[] = array('name'=>$file["name"], 'source'=>$file["source"], 'range'=>$range);

							// Extract a NFO from the rar.
							if ($nfostatus < 1 && $file["size"] < 100000 && preg_match("/\.(nfo|inf|ofn)$/i", $file["name"]))
							{
								$nfodata = $rar->getFileData($file["name"], $file["source"]);
								$nzbcontents = new NZBcontents(true);
								if ($nzbcontents->isNFO($nfodata))
								{
									$this->doecho("adding nfo");
									$nfo = new Nfo($this->echooutput);
									$nfo->addReleaseNfo($relid);
									$db->query(sprintf("UPDATE releasenfo SET nfo = compress(%s) WHERE releaseID = %d", $db->escapeString($nfodata), $relid));
									$db->query(sprintf("UPDATE releases SET nfostatus = 1 WHERE ID = %d", $relid));
								}

							}
							// Extract a video file from the compressed file.
							elseif (preg_match('/'.$this->videofileregex.'$/i', $file["name"]))
							{
								$videofile = $rar->getFileData($file["name"], $file["source"]);
								if ($videofile !== false)
									@file_put_contents($tmpPath.'sample_'.mt_rand(0,99999).".avi", $videofile);
							}
							// Extract an audio file from the compressed file.
							elseif (preg_match('/'.$this->audiofileregex.'$/i', $file["name"], $ext))
							{
								$audiofile = $rar->getFileData($file["name"], $file["source"]);
								if ($audiofile !== false)
									@file_put_contents($tmpPath.'audio_'.mt_rand(0,99999).$ext[0], $audiofile);
							}
						}
					}
				}
			}
			else
			{
				$rarfile = $tmpPath.'rarfile.rar';
				file_put_contents($rarfile, $fetchedBinary);
				$execstring = '"'.$this->site->unrarpath.'" e -ai -ep -c- -id -r -kb -p- -y -inul "'.$rarfile.'" "'.$tmpPath.'"';
				$output = runCmd($execstring, false, true);
			}
		}
		else
		{
			// Load the ZIP file or data.
			$files = $this->processReleaseZips($fetchedBinary, false, false , $relid, $db, $nfostatus);
			if ($files !== false)
			{
				foreach ($files as $file)
				{
					if ($file["pass"])
					{
						$this->password = true;
						break;
					}

					if (!isset($file["range"]))
						$file["range"] = 0;

					$rf->add($relid, $file["name"], $file["size"], $file["date"], $file["pass"] );
					$retval[] = array('name'=>$file["name"], 'source'=>"main", 'range'=>$file["range"]);
				}
			}
			else
				$this->ignorenumbered = true;
		}
		unset($fetchedBinary, $rar, $rf, $db, $nfo);
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
				if (is_file($mediafile) && preg_match("/".$this->videofileregex."$/i",$mediafile))
				{
					$xmlarray = runCmd('"'.$mediainfo.'" --Output=XML "'.$mediafile.'"');
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
		return $retval;
	}

	// Attempt to get mediainfo/sample/title from a audio file.
	public function getAudioinfo($ramdrive,$ffmpeginfo,$audioinfo,$releaseguid, $releaseID)
	{
		$db = new DB();
		$retval = $audval = false;
		$processAudioinfo = ($this->site->mediainfopath != '') ? true : false;
		if (!($processAudioinfo && is_dir($ramdrive) && ($releaseID > 0)))
			return $retval;

		$catID = $db->queryOneRow(sprintf("SELECT categoryID as ID, relnamestatus, groupID FROM releases WHERE ID = %d", $releaseID));
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
						$xmlarray = runCmd('"'.$audioinfo.'" --Output=XML "'.$audiofile.'"');
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
											$db->query(sprintf("UPDATE releases SET searchname = %s, categoryID = %d, relnamestatus = 3 WHERE ID = %d", $db->escapeString($newname), $newcat, $releaseID));
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
									copy($ramdrive.$releaseguid.".ogg", $this->audSavePath.$releaseguid.".ogg");
									if(@file_exists($this->audSavePath.$releaseguid.".ogg"))
									{
										$db->query(sprintf("UPDATE releases SET audiostatus = 1 WHERE ID = %d",$releaseID));
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
					$output = runCmd('"'.$ffmpeginfo.'" -i "'.$samplefile.'" -loglevel quiet -vframes 250 -y "'.$ramdrive.'zzzz%03d.jpg"');
					if (is_dir($ramdrive))
					{
						@$all_files = scandir($ramdrive,1);
						if(preg_match("/zzzz\d{3}\.jpg/",$all_files[0]))
						{
							$ri->saveImage($releaseguid.'_thumb', $ramdrive.$all_files[0], $ri->imgSavePath, 800, 600);
							if(file_exists($ri->imgSavePath.$releaseguid."_thumb.jpg"))
								$retval = true;
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
		return $retval;
	}

	public function getVideo($ramdrive, $ffmpeginfo, $releaseguid)
	{
		$retval = false;
		$processSample = ($this->site->ffmpegpath != '') ? true : false;
		if (!($processSample && is_dir($ramdrive) && (strlen($releaseguid) > 0)))
			return $retval;

		$ri = new ReleaseImage();
		$db = new DB();
		$samplefiles = glob($ramdrive.'*.*');
		if (is_array($samplefiles))
		{
			foreach($samplefiles as $samplefile)
			{
				if (is_file($samplefile) && preg_match("/".$this->videofileregex."$/i",$samplefile))
				{
					$output = runCmd('"'.$ffmpeginfo.'" -i "'.$samplefile.'" -vcodec libtheora -filter:v scale=320:-1 -vframes 500 -acodec libvorbis -loglevel quiet -y "'.$ramdrive."zzzz".$releaseguid.'.ogv"');
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
										$db->query(sprintf("UPDATE releases SET videostatus = 1 WHERE guid = %s",$releaseguid));
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
		return $retval;
	}

	public function updateReleaseHasPreview($guid)
	{
		$db = new DB();
		$db->queryOneRow(sprintf("update releases set haspreview = 1 where guid = %s", $db->escapeString($guid)));
	}
}
?>
