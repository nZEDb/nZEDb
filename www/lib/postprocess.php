<?php
require_once(WWW_DIR."/lib/anidb.php");
require_once(WWW_DIR."/lib/books.php");
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

class PostProcess {

	function PostProcess($echooutput=false)
	{
		$this->echooutput = $echooutput;
		$s = new Sites();
		$this->site = $s->get();
		$this->addqty = (!empty($this->site->maxaddprocessed)) ? $this->site->maxaddprocessed : 25;
		$this->partsqty = (!empty($this->site->maxpartsprocessed)) ? $this->site->maxpartsprocessed : 3;
		$this->password = false;

		$this->mediafileregex = '\.(AVI|F4V|IFO|M1V|M2V|M4V|MKV|MOV|MP4|MPEG|MPG|MPGV|MPV|QT|RM|RMVB|TS|VOB|WMV|AAC|AIFF|APE|AC3|ASF|DTS|FLAC|MKA|MKS|MP2|MP3|RA|OGG|OGM|W64|WAV|WMA)';
		$this->supportfiles = "/\.(vol\d{1,3}\+\d{1,3}|par2|srs|sfv|nzb";
		$this->debug = false;
	}

	public function processAll()
	{
		$this->processAdditional($threads=1);
		$this->processNfos($threads=1);
		$this->processMovies($threads=1);
		$this->processMusic($threads=1);
		$this->processGames($threads=1);
		$this->processAnime($threads=1);
		$this->processTv($threads=1);
		$this->processBooks($threads=1);
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
		$maxattemptstocheckpassworded = 5;
		$processSample = ($this->site->ffmpegpath != '') ? true : false;
		$processMediainfo = ($this->site->mediainfopath != '') ? true : false;
		$processPasswords = ($this->site->unrarpath != '') ? true : false;

		$db = new DB;
		$nntp = new Nntp;
		$consoleTools = new ConsoleTools();
		$rar = new RecursiveRarInfo();

		$tmpPath = $this->site->tmpunrarpath;
		$threads--;
		if (substr($tmpPath, -strlen( '/' ) ) != '/')
			$tmpPath = $tmpPath.'/';

		$tmpPath1 = $tmpPath;

		//
		// Get out all releases which have not been checked more than max attempts for password.
		//
		if ($id != '')
			$query = sprintf("select r.ID, r.guid, r.name, c.disablepreview, r.size from releases r
			left join category c on c.ID = r.categoryID
			where r.ID = %d", $id);
		else
			$query = sprintf("select r.ID, r.guid, r.name, c.disablepreview, r.size from releases r
			left join category c on c.ID = r.categoryID
			where nzbstatus = 1 and (r.passwordstatus between %d and -1)
			AND (r.haspreview = -1 and c.disablepreview = 0) order by r.postdate desc limit %d,%d", ($maxattemptstocheckpassworded + 1) * -1, floor(($this->addqty) * ($threads * 1.5)), $this->addqty);

		$result = $db->query($query);
		$rescount = count($result);
		if ($rescount > 0)
		{
			if ($this->echooutput)
				echo "(following started at: ".date("D M d, Y G:i a").")\nAdditional post-processing on {$rescount} release(s), starting at ".floor(($this->addqty) * ($threads * 1.5)).": ";
			$nntp->doConnect();

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

				$passStatus = array(Releases::PASSWD_NONE);
				$blnTookMediainfo = false;
				// Only attempt sample if not disabled.
				$blnTookSample =  ($rel['disablepreview'] == 1) ? true : false;
				if ($this->echooutput)
					$consoleTools->overWrite($rescount--." left..".(($this->debug) ? "{$rel['guid']} " : ""));

				if ($blnTookSample)
					$db->query(sprintf("update releases set haspreview = 0 where id = %d", $rel['ID']));

				//
				// Go through the nzb for this release looking for a rar, a sample, and a mediafile.
				//
				$nzbcontents = new NZBcontents(true);
				$relres = $db->queryOneRow(sprintf("select guid, groupID from releases where ID = %d", $rel["ID"]));
				$guid = $relres["guid"];
				$groupID = $relres["groupID"];
				$groups = new Groups;
				$groupName = $groups->getByNameByID($groupID);
				$samplemsgid = $mediamsgid = $mid = array();
				$bingroup = $samplegroup = $mediagroup = "";
				$hasrar = 0;
				$this->password = false;

				$nzbfiles = $nzbcontents->nzblist($rel['guid']);
				if (!$nzbfiles)
					continue;

				foreach ($nzbfiles as $nzbcontents)
				{
					$subject = $nzbcontents['subject'];

					if (preg_match($this->supportfiles."|nfo)/i",$subject))
						continue;

					if (preg_match("/\.(001|000|010|rar|r00|r01|zip)/i", $subject))
						$hasrar= 1;

					if (preg_match("/sample/i",$subject)) {
						$samplesegments = $nzbcontents['segment'];
						$samplepart = (array)$samplesegments;
						if (isset($samplepart))
						{
							$samplegroup = $groupName;
							$samplemsgid = array_merge($samplemsgid, array($samplepart[0]));
						}
					}
					elseif (preg_match('/'.$this->mediafileregex.'[\. "\)\]]/i',$subject) && !preg_match("/sample/i",$subject))
					{
						$mediasegments = $nzbcontents['segment'];
						$mediapart = (array)$mediasegments;
						if (isset($mediapart) && empty($mediamsgid))
						{
							$mediagroup = $groupName;
							$mediamsgid = array_merge($mediamsgid, array($mediapart[0]));
						}
					}
					if (preg_match("/.*\W(?:part0*1|(?!part\d+)[^.]+)\.rar[ \"\)\]\-]|.*\W(?:\"[\w.\-\',;& ]|(?!\"[\w.\-\',;& ]+)[^.]+)\.(001|((?=10[ \"\)\]\-].+\(\d{1,3}\/\d{2,3})10|11)|part01)[ \"\)\]\-]/i", $subject) && !preg_match("/[-_\.]sub/i", $subject))
					{
						$rarsegments = $nzbcontents['segment'];
						$rarpart = (array)$rarsegments;
						if (isset($rarpart))
						{
							$bingroup = $groupName;
							$mid = array_merge($mid, array($rarpart[0]));
						}
					}
				}

				$db->query("DELETE FROM `releasefiles` WHERE `releaseID` =".$rel['ID']);

				$bytes = $rel['size'] * 2;
				$bytes = min( 1024*1024*1024, $bytes);
				$this->password = false;
				$lsize = 0;
				$i = 0;
				$first = 'crazy';
				$rarpart = array();

				if (!empty($mid) && ($this->site->checkpasswordedrar > 0 || ($processSample && $blnTookSample === false) || $processMediainfo))
				{
					$notinfinite = 0;
					if (count($nzbfiles) > 1)
					{
						$nzbfiles = $this->subval_sort($nzbfiles, "subject");

						foreach ($nzbfiles as $k => $v)
						{
							if (preg_match($this->supportfiles."|nfo)/i", $v['subject']))
								continue;

							if (preg_match('/\.(rar|'.$first.')($|\")/i', $v['subject']))
							{
								$rarpart[] = $nzbfiles[$k];
								unset($nzbfiles[$k]);
							}
							elseif ($first === 'azy' && preg_match('/\.(0+|0*10?)($|\")/i', $v['subject'], $tmp))
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

					foreach ($nzbfiles as $rarFile)
					{
						if ($notinfinite > $this->partsqty)
							continue;

						$notinfinite++;
						$subject = $rarFile['subject'];
						if (preg_match($this->supportfiles.")/i", $subject))
							continue;

						if (!preg_match("/\.\b(part\d+|rar|r\d{1,3}|zipr\d{2,3}|\d{2,3}|zip|zipx)\b/i", $subject))
						{
							$this->doecho("not matched and skipping $subject");
							continue;
						}

						if ($this->password)
						{
							$this->doecho("-Skipping processing of rar $subject was found to be passworded");
							continue;
						}

						$size = $db->queryOneRow("SELECT sum(size) as size FROM `releasefiles` WHERE `releaseID` = ".$rel['ID']);
						if (is_numeric($size["size"]) && $size["size"] > $bytes)
							continue;

						if (is_numeric($size["size"]) && $size["size"] == $lsize)
							$i++;
						else
							$i = 0;

						$lsize = $size["size"];
						if ($i > count($nzbfiles)/ 10)
						{
							//$this->doecho("new files don't seem to contribute");
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

							if (is_array($relFiles) && count($relFiles) > 0)
								foreach ($relFiles as $file1)
								{
									if (preg_match($this->supportfiles.")/i", $file1['name']))
										continue;

									$rar->setData($fetchedBinary, true);

									//$rar->getArchiveFileList();

									if (preg_match("/\.nfo$/i",$file1['name']))
									{
										$nfodata = $rar->getFileData($file1['name'], $file1['source']);
										if ($nfodata !== false)
										{
											$nfo = new Nfo($this->echooutput);
											$nfo->addReleaseNfo($rel['ID']);
											$db->query(sprintf("UPDATE releasenfo SET nfo = compress(%s) WHERE releaseID = %d", $db->escapeString($nfodata), $rel["ID"]));
											$db->query(sprintf("UPDATE releases SET nfostatus = 1 WHERE ID = %d", $rel["ID"]));
										}

									} elseif (preg_match("/sample/i",$file1['name'])) {
										$rar->saveFileData($file1['name'], $tmpPath."_".$file1['source']."_".$file1['range']."_".rand(0,1000)."_".$file1['name'], $file1['source']);
									} elseif (preg_match('/'.$this->mediafileregex.'$/i',$file1['name']) && !preg_match("/sample/i",$file1['name']))
									{
										$rar->saveFileData($file1['name'], $tmpPath."_".$file1['source']."_".$file1['range']."_".rand(0,1000)."_".$file1['name'], $file1['source']);
									}
								}

							if ($relFiles === false)
							{
								$this->doecho("\nerror processing files {$rel['ID']}");
								continue;
							}

						}

					}
				}
				elseif(empty($mid) && $hasrar == 1)
				{
					$passStatus[] = Releases::PASSWD_POTENTIAL;
				}

				if(!empty($samplemsgid) && $processSample && $blnTookSample === false)
				{
					$nntp->doConnect();

					$sampleBinary = $nntp->getMessages($samplegroup, $samplemsgid);

					if (strlen($sampleBinary ) > 100)
					{
						$samplefile = $tmpPath.'sample.avi';
						file_put_contents($samplefile, $sampleBinary);
					}
					$blnTookSample = $this->getSample($tmpPath, $this->site->ffmpegpath, $rel['guid']);

					//unset($sampleBinary);
				}

				if ($processMediainfo && $blnTookMediainfo === false)
				{
					$nntp->doConnect();

					$mediaBinary = $nntp->getMessages($mediagroup, $mediamsgid);

					if (strlen($mediaBinary ) > 100)
					{
						$mediafile = $tmpPath.'media.avi';
						@file_put_contents($mediafile, $mediaBinary);

						if (!$blnTookSample)
						{
							$samplefile = $tmpPath.'sample.avi';
							@file_put_contents($samplefile, $mediaBinary);
							$blnTookSample = $this->getSample($tmpPath, $this->site->ffmpegpath, $rel['guid']);
						}
					}
					$blnTookMediainfo = $this->getMediainfo($tmpPath, $this->site->mediainfopath, $rel['ID']);
				}

				//
				// Last attempt to get a sample image.
				//
				if (!$blnTookSample)
				{
					$files = @scandir($tmpPath);

					if (isset($files) && is_array($files) && count($files) > 0)
						foreach ($files as $file)
						{
							if (preg_match('/(.*)'.$this->mediafileregex.'$/i',$file,$name))
							{
								if ($name[1] == 'sample')
									continue;

								rename($tmpPath.$name[0], $tmpPath."sample.avi");
								$blnTookSample = $this->getSample($tmpPath, $this->site->ffmpegpath, $rel['guid']);
								@unlink($tmpPath."sample.avi");
							}
						}
				}

				if ($blnTookSample)
					$this->updateReleaseHasPreview($rel['guid']);

				// attempt to process sample file

				$hpsql = '';
				if (!$blnTookSample)
					$hpsql = ', haspreview = 0';

				if (max($passStatus) >= 0)
					$sql = sprintf("update releases set passwordstatus = %d %s where ID = %d", max($passStatus), $hpsql, $rel["ID"]);
				else
					$sql = sprintf("update releases set passwordstatus = passwordstatus - 1 %s where ID = %d", $hpsql, $rel["ID"]);

				$db->query($sql);

				//clean up all files
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
			$nntp->doQuit();
			if ($this->echooutput)
				echo "\n";
		}
	}

	function doecho($str)
	{
		if ($this->echooutput && $this->debug)
		{
			echo $str."\n";
		}
	}

	function processReleaseZips($fetchedBinary, $open = false, $data = false)
	{
		// Load the ZIP file or data
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
			$this->doecho("Archive is password encrypted");
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
		}

		unset($fetchedBinary);
		unset($zip);
		return $files;
	}

	function getRar($fetchedBinary)
	{
		$rar = new RecursiveRarInfo();
		if ($rar->setData($fetchedBinary, true))
		{
			return $rar->getArchiveFileList();
		}
	}

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
				$this->doecho("Archive is password encrypted");
				$this->password = true;
				return false;
			}

			$tmp = $rar->getSummary(true, false);
			if ($tmp["is_encrypted"])
			{
				$this->doecho("Archive is password encrypted");
				$this->password = true;
				return false;
			}


			$files = $rar->getArchiveFileList();
			if ($files !== false)
			{
				foreach ($files as $file)
					if (isset($file['name']))
					{
						if ($file['pass'])
							$this->password = true;

						if (isset($file['error']))
						{
							$this->doecho("Error: {$file['error']} (in: {$file['source']})");
							continue;
						}

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

						$ok = preg_match("/main/i", $file['source']) && preg_match("/\.\b(part\d+|rar|r\d{1,3}|zipr\d{2,3}|\d{2,3}|zip|zipx)\b/i", $file['name']) && count($files) > 1;
						if (!$ok)
						{
							$rf->add($relid, $file['name'], $file['size'], $file['date'], $file['pass'] );

							$range = rand(0, 32767);
							if (isset($file['range']))
								$range = $file['range'];

							$retval[] = array('name'=>$file['name'], 'source'=>$file['source'], 'range'=>$range);
						}

						/*if ($file['compressed'] == false)
						{
							echo "Extracting uncompressed file: {$file['name']} from: {$file['source']}\n";
							$rar->saveFileData($file['name'], "./dir/{$file['name']}", $file['source']);
							// or $data = $rar->getFileData($file['name'], $file['source']);
						}*/
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
						$this->password = true;

					if (!$file['range'])
						$file['range'] = 0;

					$rf->add($relid, $file['name'], $file['size'], $file['date'], $file['pass'] );
					$retval[] = array('name'=>$file['name'], 'source'=>"main", 'range'=>$file['range']);
				}
		}
		unset($fetchedBinary);
		return $retval;
	}

	public function getMediainfo($ramdrive,$mediainfo,$releaseID)
	{
		$retval = false;
		$mediafiles = glob($ramdrive.'*.*');
		if (is_array($mediafiles))
		{
			foreach($mediafiles as $mediafile)
			{
				if (preg_match("/".$this->mediafileregex."$/i",$mediafile))
				{
					$execstring = '"'.$mediainfo.'" --Output=XML "'.$mediafile.'"';
					$xmlarray = runCmd($execstring);

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

	public function getSample($ramdrive, $ffmpeginfo, $releaseguid)
	{
		$ri = new ReleaseImage();
		$retval = false;

		$samplefiles = glob($ramdrive.'*.*');
		if (is_array($samplefiles))
		{
			foreach($samplefiles as $samplefile)
			{
				if (preg_match("/".$this->mediafileregex."$/i",$samplefile))
				{
					$execstring = '"'.$ffmpeginfo.'" -q:v 0 -i "'.$samplefile.'" -loglevel quiet -vframes 300 "'.$ramdrive.'zzzz%03d.jpg"';
					$output = runCmd($execstring);
					$all_files = scandir($ramdrive,1);
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
		return $retval;
	}

	public function updateReleaseHasPreview($guid)
	{
		$db = new DB();
		$db->queryOneRow(sprintf("update releases set haspreview = 1 where guid = %s", $db->escapeString($guid)));
	}
}
?>
