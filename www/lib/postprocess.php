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

		$this->videofileregex = '\.(AVI|F4V|IFO|M1V|M2V|M4V|MKV|MOV|MP4|MPEG|MPG|MPGV|MPV|QT|RM|RMVB|TS|VOB|WMV)';
		$this->audiofileregex = '\.(AAC|AIFF|APE|AC3|ASF|DTS|FLAC|MKA|MKS|MP2|MP3|RA|OGG|OGM|W64|WAV|WMA)';
		$this->supportfiles = "/\.(vol\d{1,3}\+\d{1,3}|par2|srs|sfv|nzb";
		$this->DEBUG_ECHO = false;
		if (defined("DEBUG_ECHO") && DEBUG_ECHO == true)
			$this->DEBUG_ECHO = true;
	}

	public function processAll($threads=1)
	{
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

		$threads--;
		$update_files = true;

		$maxattemptstocheckpassworded = 5;
		$tries = ($maxattemptstocheckpassworded * -1) -1;
		$processSample = ($this->site->ffmpegpath != '') ? true : false;
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
			$query = sprintf("select r.ID, r.guid, r.name, c.disablepreview, r.size, r.groupID from releases r
			left join category c on c.ID = r.categoryID
			where r.ID = %d", $id);
		}
		else
		{
			$i = -1;
			$result = 0;
			while ((count($result) != $this->addqty) && ($i >= $tries))
			{
				$query = sprintf("select r.ID, r.guid, r.name, c.disablepreview, r.size, r.groupID from releases r
				left join category c on c.ID = r.categoryID
				where nzbstatus = 1 and (r.passwordstatus between %d and -1)
				AND (r.haspreview = -1 and c.disablepreview = 0) order by r.postdate desc limit %d,%d", $i, floor(($this->addqty) * ($threads * 1.5)), $this->addqty);
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
				$tmpPath = $tmpPath1.$rel['guid'].'/';
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
				$blnTookSample =  ($rel['disablepreview'] == 1) ? true : false;
				$blnTookMediainfo = $blnTookAudioinfo = $blnTookJPG = false;
				$passStatus = array(Releases::PASSWD_NONE);
				
				if ($this->echooutput && $threads > 0)
					$consoleTools->overWrite(" ".$rescount--." left..".(($this->DEBUG_ECHO) ? "{$rel['guid']} " : ""));
				else if ($this->echooutput)
					$consoleTools->overWrite(", ".$rescount--." left in queue, ".$ppcount["cnt"]--." total in DB..".(($this->DEBUG_ECHO) ? "{$rel['guid']} " : ""));

				// Go through the nzb for this release looking for a rar, a sample, and a mediafile.
				$nzbcontents = new NZBcontents(true);
				$groups = new Groups;
				$groupName = $groups->getByNameByID($rel["groupID"]);

				$bingroup = $samplegroup = $mediagroup = $jpggroup = $audiogroup = "";
				$samplemsgid = $mediamsgid = $audiomsgid = $jpgmsgid = $audiotype = $mid = array();
				$hasrar = 0;
				$this->password = $notmatched = false;
				
				$nzbfiles = $nzbcontents->nzblist($rel['guid']);
				if (!$nzbfiles)
					continue;

				foreach ($nzbfiles as $nzbcontents)
				{
					// Check if it's not a nfo, par2 etc...
					if (preg_match($this->supportfiles."|nfo|inf|ofn)/i",$nzbcontents['subject']))
						continue;

					// Check if it's not a rar/zip.
					if (preg_match("/\.(part0*1|part0+|r0+|r0*1|0+|0*10?|zip)(\.rar)*($|[ \"\)\]\-])/i", $nzbcontents['subject']))
						$hasrar= 1;
					elseif (preg_match("/\.rar($|[ \"\)\]\-])/i", $nzbcontents['subject']))
						$hasrar= 1;
					elseif (!$hasrar)
						$notmatched = true;

					// Look for a sample.
					if ($processSample && preg_match("/sample/i", $nzbcontents['subject']))
					{
						if (isset($nzbcontents['segment']) && empty($samplemsgid))
						{
							$samplegroup = $groupName;
							$samplemsgid[] = $nzbcontents['segment'][0];
							$samplemsgid[] = $nzbcontents['segment'][1];
						}
					}
					// Look for a media file.
					elseif ($processMediainfo && preg_match('/'.$this->videofileregex.'[\. "\)\]]/i', $nzbcontents['subject']) && !preg_match("/sample/i", $nzbcontents['subject']))
					{
						if (isset($nzbcontents['segment']) && empty($mediamsgid))
						{
							$mediagroup = $groupName;
							$mediamsgid[] = $nzbcontents['segment'][0];
						}
					}
					// Look for a audio file.
					elseif ($processAudioinfo && preg_match('/'.$this->audiofileregex.'[\. "\)\]]/i', $nzbcontents['subject'], $type))
					{
						if (isset($nzbcontents['segment']) && empty($audiomsgid))
						{
							$audiogroup = $groupName;
							$audiotype = $type[1];
							$audiomsgid[] = $nzbcontents['segment'][0];
						}
					}
					// Look for a JPG picture.
					elseif ($processJPGSample && preg_match('/\.(jpg|jpeg)[\. "\)\]]/i', $nzbcontents['subject']) && !preg_match('/FLAC|MP3|WEB/i', $nzbcontents['subject']))
					{
						if (isset($nzbcontents['segment']) && empty($jpgmsgid))
						{
							$jpggroup = $groupName;
							$jpgmsgid[] = $nzbcontents['segment'][0];
							$jpgmsgid[] = $nzbcontents['segment'][1];
						}
					}
				}

				$oldreleasefiles = $db->query("SELECT * FROM `releasefiles` WHERE `releaseID` = ".$rel['ID']);
				// If this release has release files, delete them.
				$db->query("DELETE FROM `releasefiles` WHERE `releaseID` = ".$rel['ID']);

				$bytes = min( 1024*1024*1024, $rel['size'] * 2);
				$this->password = $foundcontent = false;
				$lsize = $i = 0;
				$first = 'crazy';
				$rarpart = array();

				if ($hasrar && ($this->site->checkpasswordedrar > 0 || $processSample || $processMediainfo || $processAudioinfo))
				{
					if (count($nzbfiles) > 1)
					{
						$nzbfiles = $this->subval_sort($nzbfiles, "subject");
						foreach ($nzbfiles as $k => $v)
						{
							if (preg_match($this->supportfiles."|nfo|inf|ofn)/i", $v['subject']))
								continue;

							if (preg_match('/\.(rar|'.$first.')($|\")/i', $v['subject']))
							{
								$rarpart[] = $nzbfiles[$k];
								unset($nzbfiles[$k]);
							}
							elseif ($first === 'crazy' && preg_match('/\.(part0*1|part0+|r0+|r0*1|0+|0*10?|zip)(\.rar)*($|\")/i', $v['subject'], $tmp))
							{
								$rarpart[] = $nzbfiles[$k];
								unset($nzbfiles[$k]);
								$first = $tmp[1];
							}
						}
					}
					sort($rarpart);
					shuffle($nzbfiles);

					if (count($rarpart) > 0)
						$nzbfiles = array_merge($rarpart, $nzbfiles);

					$foundcontent = false;
					$notinfinite = 0;

					// Loop through the files, attempt to find passwords and files.
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

						$notinfinite++;
						if ($this->password)
						{
							$this->doecho("-Skipping processing of rar {$rarFile['subject']} was found to be passworded");
							break;
						}

						if (preg_match($this->supportfiles.")/i", $rarFile['subject']))
							continue;

						if (!preg_match("/\.\b(part\d+|rar|r\d{1,3}|zipr\d{2,3}|\d{2,3}|zip|zipx)($|[ \"\)\]\-])/i", $rarFile['subject']))
						{
							$this->doecho("Not matched and skipping ".$rarFile['subject']);
							continue;
						}

						$size = $db->queryOneRow("SELECT SUM(size) AS size FROM `releasefiles` WHERE `releaseID` = ".$rel['ID']);
						if (is_numeric($size["size"]) && $size["size"] > $bytes)
							continue;

						if (is_numeric($size["size"]) && $size["size"] == $lsize)
							$i++;
						else
							$i = 0;

						$lsize = $size["size"];
						if ($i > count($nzbfiles)/ 10)
						{
							$this->doecho("New files don't seem to contribute.");
							continue;
						}

						$mid = array_slice((array)$rarFile['segment'], 0, 1);

						$nntp->doConnect();
						$fetchedBinary = $nntp->getMessages($bingroup, $mid);
						if ($fetchedBinary !== false)
						{
							$relFiles = $this->processReleaseFiles($fetchedBinary, $tmpPath, $rel['ID']);

							if ($this->password)
								$passStatus[] = Releases::PASSWD_RAR;

							if ($relFiles === false)
							{
								$this->doecho("\nError processing files {$rel['ID']}");
								continue;
							}
							else
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
							$blnTookSample = $this->getSample($tmpPath, $this->site->ffmpegpath, $rel['guid']);
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
							$blnTookMediainfo = $this->getMediainfo($tmpPath, $this->site->mediainfopath, $rel['ID']);

							if ($processSample && $blnTookSample === false)
								$blnTookSample = $this->getSample($tmpPath, $this->site->ffmpegpath, $rel['guid']);

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
							$blnTookAudioinfo = $this->getAudioinfo($tmpPath, $this->site->mediainfopath, $rel['guid']);
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
							$ri = new ReleaseImage;
							if (@imagecreatefromjpeg($tmpPath."samplepicture.jpg") !== false)
								$blnTookJPG = $ri->saveImage($rel["guid"].'_thumb', $tmpPath."samplepicture.jpg", $ri->jpgSavePath, 650, 650);
							
							if ($blnTookJPG !== false)
								$db->query(sprintf("UPDATE releases SET jpgstatus = %d WHERE ID = %d", 1, $rel['ID']));

							foreach(glob($tmpPath.'*.jpg') as $v)
							{
								@unlink($v);
							}
						}
						unset($jpgBinary);
					}
					$nntp->doQuit();
				}
				
				// Last attempt to get image/mediainfo/audioinfo, using an extracted file.
				if (($blnTookSample === false || $blnTookAudioinfo === false || $blnTookMediainfo === false) && is_dir($tmpPath))
				{
					$files = @scandir($tmpPath);
					if (isset($files) && is_array($files) && count($files) > 0)
					{
						foreach ($files as $file)
						{
							if ($blnTookAudioinfo === false && $processAudioinfo && is_file($tmpPath.$file) && preg_match('/(.*)'.$this->audiofileregex.'$/i', $file, $name)) 
							{
								rename($tmpPath.$name[0], $tmpPath."audiofile.".$name[2]);
								$blnTookAudioinfo = $this->getAudioinfo($tmpPath, $this->site->mediainfopath, $rel['ID']);
								@unlink($tmpPath."sample.".$name[2]);
							}
							if (is_file($tmpPath.$file) && preg_match('/(.*)'.$this->videofileregex.'$/i', $file, $name)) 
							{
								rename($tmpPath.$name[0], $tmpPath."sample.avi");
								if ($processSample && $blnTookSample === false)
									$blnTookSample = $this->getSample($tmpPath, $this->site->ffmpegpath, $rel['guid']); 
								if ($processMediainfo && $blnTookMediainfo === false)
									$blnTookMediainfo = $this->getMediainfo($tmpPath, $this->site->mediainfopath, $rel['ID']);
								@unlink($tmpPath."sample.avi");

								if ($blnTookSample)
									break;
							}
						}
						unset($files);
					}
				}

				// Set up release values.

				$hpsql = '';
				if ($blnTookSample)
					$this->updateReleaseHasPreview($rel['guid']);
				else
					$hpsql = ', haspreview = 0';

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

				if ($update_files)
				{
					$rf = new ReleaseFiles;
					foreach ($oldreleasefiles as $file)
					{
						$query = sprintf("SELECT *  FROM `releasefiles` WHERE `releaseID` = %d AND `name` LIKE '%s' AND `size` = %s", $rel['ID'], $file['name'], $file['size']);
						$row = $db->queryOneRow($query);

						if ($row === false)
						{
							$this->doecho("adding missing file ".$rel['guid']);
							$rf->add($rel['ID'], $file['name'], $file['size'], $file['createddate'], $file['passworded'] );
						}
					}
					unset($rf);
				}

				// rarinnerfilecount - This needs to be done or else the magnifier on the site does not show up.
				$size = $db->queryOneRow(sprintf("SELECT count(releasefiles.releaseID) as count FROM releasefiles WHERE releasefiles.releaseID = %d", $rel['ID']));
				if ($size["count"] > 0)
					$db->query(sprintf("UPDATE releases SET rarinnerfilecount = %d WHERE ID = %d", $size["count"], $rel['ID']));

				// Clean up all files.
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
		unset($db, $nntp, $consoleTools, $rar, $nzbcontents, $groups);
	}

	function doecho($str)
	{
		if ($this->echooutput && $this->DEBUG_ECHO)
			echo $str."\n";
	}

	// Open the zip, see if it has a password, attempt to get a file.
	function processReleaseZips($fetchedBinary, $open = false, $data = false)
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

		if ($data)
		{
			$data = array();
			foreach ($files as $file)
				$data[] = array('zip'=>$file, 'data'=>$zip->getFileData($file["name"]));

			$files = $data;
			unset ($data);
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
	function processReleaseFiles($fetchedBinary, $tmpPath, $relid)
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
			if ($files !== false)
			{
				foreach ($files as $file)
				{
					if (isset($file['name']))
					{
						if ($file['pass'] > 0)
						{
							$this->password = true;
							break;
						}

						if (isset($file['error']))
						{
							$this->doecho("Error: {$file['error']} (in: {$file['source']})");
							continue;
						}

						if (preg_match($this->supportfiles.")/i", $file['name']))
							continue;

						/*if (preg_match('/\.zip/i', $file['name']))
						{
							$zipdata = $rar->getFileData($file['name'], $file['source']);
							$data = $this->processReleaseZips($zipdata, false, true);

 							foreach($data as $d)
							{
								if (preg_match('/\.rar/i', $d['zip']['name']))
								{
									$file = $this->getRar($d['data']);
								}
							}
						}*/
						if (!preg_match("/\.\b(part\d+|rar|r\d{1,3}|zipr\d{2,3}|\d{2,3}|zip|zipx)($|[ \"\)\]\-])/i", $file['name']) && count($files) > 0)
						{	
							$rf->add($relid, $file['name'], $file['size'], $file['date'], $file['pass'] );
							$range = mt_rand(0,32767);
							if (isset($file['range']))
								$range = $file['range'];
							$retval[] = array('name'=>$file['name'], 'source'=>$file['source'], 'range'=>$range);

							// Extract a NFO from the rar.
							if (preg_match("/\.(nfo|inf|ofn)$/i", $file['name']))
							{
								$nfodata = $rar->getFileData($file['name'], $file['source']);
								if ($nfodata !== false)
								{
									$nfo = new Nfo($this->echooutput);
									$nfo->addReleaseNfo($relid);
									$db->query(sprintf("UPDATE releasenfo SET nfo = compress(%s) WHERE releaseID = %d", $db->escapeString($nfodata), $relid));
									$db->query(sprintf("UPDATE releases SET nfostatus = 1 WHERE ID = %d", $relid));
								}

							}
							// Extract a video file from the compressed file.
							elseif (preg_match('/'.$this->videofileregex.'$/i', $file['name']))
							{
								$videofile = $rar->getFileData($file['name'], $file['source']);
								if ($videofile !== false)
									file_put_contents($tmpPath.'sample_'.mt_rand(0,99999).".avi", $videofile);
							}
							// Extract an audio file from the compressed file.
							elseif (preg_match('/'.$this->audiofileregex.'$/i', $file['name'], $ext))
							{
								$audiofile = $rar->getFileData($file['name'], $file['source']);
								if ($audiofile !== false)
									file_put_contents($tmpPath.'audio_'.mt_rand(0,99999).$ext[0], $audiofile);
							}
						}
					}
				}
			}
		}
		else
		{
			// Load the ZIP file or data.
			$files = $this->processReleaseZips($fetchedBinary, false);
			if ($files !== false)
				foreach ($files as $file)
				{
					if ($file['pass'])
					{
						$this->password = true;
						break;
					}

					if (!isset($file['range']))
						$file['range'] = 0;

					$rf->add($relid, $file['name'], $file['size'], $file['date'], $file['pass'] );
					$retval[] = array('name'=>$file['name'], 'source'=>"main", 'range'=>$file['range']);
				}
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
				if (preg_match("/".$this->videofileregex."$/i",$mediafile))
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

	// Attempt to get a release name from a audio file.
	public function getAudioinfo($ramdrive,$audioinfo,$releaseID)
	{
		$db = new DB();
		$retval = false;
		$processAudioinfo = ($this->site->mediainfopath != '') ? true : false;
		if (!($processAudioinfo && is_dir($ramdrive) && ($releaseID > 0)))
			return $retval;

		$catID = $db->queryOneRow(sprintf("SELECT categoryID as ID, groupID FROM releases WHERE ID = %d", $releaseID));
		if (!preg_match('/^3\d{3}|7010/', $catID["ID"]))
			return $retval;

		$audiofiles = glob($ramdrive.'*.*');
		if (is_array($audiofiles))
		{
			foreach($audiofiles as $audiofile)
			{
				if (preg_match("/".$this->audiofileregex."$/i",$audiofile, $ext))
				{
					$xmlarray = runCmd('"'.$audioinfo.'" --Output=XML "'.$audiofile.'"');
					if (is_array($xmlarray))
					{
						$xmlarray = implode("\n",$xmlarray);
						$xmlObj = @simplexml_load_string($xmlarray);
						$arrXml = objectsIntoArray($xmlObj);
						foreach ($arrXml["File"]["track"] as $track)
						{
							if (isset($track["Album"]) && isset($track["Performer"]) && isset($track["Recorded_date"]))
							{
								preg_match('/\d{4}/', $track["Recorded_date"], $Year);
								$newname = $track["Performer"]." - ".$track["Album"]." (".$Year[0].") ".strtoupper($ext[1]);
								$category = new Category();
								$newcat = $category->determineCategory($newname, $catID["groupID"]);
								$db->query(sprintf("UPDATE releases SET searchname = %s, categoryID = %d, relnamestatus = 3 WHERE ID = %d", $db->escapeString($newname), $newcat, $releaseID));
								$retval = true;
								break;
							}
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
		if (!($processSample && is_dir($ramdrive) && ($releaseguid > 0)))
			return $retval;

		$ri = new ReleaseImage();
		$samplefiles = glob($ramdrive.'*.*');
		if (is_array($samplefiles))
		{
			foreach($samplefiles as $samplefile)
			{
				if (preg_match("/".$this->videofileregex."$/i",$samplefile))
				{
					$output = runCmd('"'.$ffmpeginfo.'" -i "'.$samplefile.'" -loglevel quiet "'.$ramdrive.'zzzz%03d.jpg"');
					if (is_dir($ramdrive))
					{
						@$all_files = scandir($ramdrive,1);
						if(preg_match("/zzzz\d{3}\.jpg/",$all_files[1]))
						{
							$ri->saveImage($releaseguid.'_thumb', $ramdrive.$all_files[1], $ri->imgSavePath, 800, 600);
							$retval = true;
						}

						// Clean up all files.
						foreach(glob($ramdrive.'*.jpg') as $v)
						{
							@unlink($v);
						}
					}
				}
			}
		}
		// If an image was made, return true, else return false.
		return $retval;
	}

	public function updateReleaseHasPreview($guid)
	{
		$db = new DB();
		$db->queryOneRow(sprintf("update releases set haspreview = 1 where guid = %s", $db->escapeString($guid)));
	}
}
?>
