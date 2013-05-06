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
		$this-> addqty = (!empty($this->site->maxaddprocessed)) ? $this->site->maxaddprocessed : 25;
		$this->password = false;
		
		$this->mediafileregex = 'AVI|VOB|MKV|MP4|TS|WMV|MOV|M4V|F4V|MPG|MPEG';
	}
	
	public function processAll()
	{
		$this->processNfos($threads=1);
		$this->processMovies($threads=1);
		$this->processMusic($threads=1);
		$this->processGames($threads=1);
		$this->processAnime($threads=1);
		$this->processTv($threads=1);
		$this->processBooks($threads=1);
		$this->processAdditional($threads=1);
	}
	
	//
	// Process nfo files
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
	// Lookup imdb if enabled
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
	// Lookup music if enabled
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
	// Lookup games if enabled
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
	// Process all TV related releases which will assign their series/episode/rage data
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
	// Process books using amazon.com
	//
	public function processBooks($threads=1)
	{
		if ($this->site->lookupbooks == 1) 
		{
			$books = new Books($this->echooutput);
			$books->processBookReleases($threads);
		}
	}
	
	//
	// Check for passworded releases, RAR contents and Sample/Media info
	//
	public function processAdditional($threads=1)
	{
		$maxattemptstocheckpassworded = 5;
		$processSample = ($this->site->ffmpegpath != '') ? true : false;
		$processMediainfo = ($this->site->mediainfopath != '') ? true : false;
		$processPasswords = ($this->site->unrarpath != '') ? true : false;
		
		$db = new DB;
		$nntp = new Nntp;
		$consoleTools = new ConsoleTools();
		
		$tmpPath = $this->site->tmpunrarpath;
		$threads--;
		if (substr($tmpPath, -strlen( '/' ) ) != '/')
			$tmpPath = $tmpPath.'/';								
		
		$tmpPath1 = $tmpPath;
		
		//
		// Get out all releases which have not been checked more than max attempts for password.
		//
		/*if ($id != '')
			$query = sprintf("select r.ID, r.guid, r.name, c.disablepreview, r.size from releases r
				left join category c on c.ID = r.categoryID
				where r.ID = %d", $id);
		else*/
			$query = sprintf("select r.ID, r.guid, r.name, c.disablepreview, r.size from releases r
			left join category c on c.ID = r.categoryID
			where nzbstatus = 1 and (r.passwordstatus between %d and -1)
			AND (r.haspreview = -1 and c.disablepreview = 0) order by RAND()  limit %d,%d", ($maxattemptstocheckpassworded + 1) * -1, floor(($this->addqty) * ($threads * 1.5)), $this->addqty);
		
		$result = $db->query($query);
		$rescount = count($result);
		if ($rescount > 0)
		{
			if ($this->echooutput)
				echo "(following started at: ".date("D M d, Y G:i a").")\nAdditional post-processing on {$rescount} release(s), starting at ".floor(($this->addqty) * ($threads * 1.5)).": ";
			$nntp->doConnect();
			
			foreach ($result as $rel)
			{
				// Per release defaults
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
				$blnTookSample =  ($rel['disablepreview'] == 1) ? true : false; //only attempt sample if not disabled
				if ($this->echooutput)
					$consoleTools->overWrite($rescount--." left..");
				
				if ($blnTookSample)
					$db->query(sprintf("update releases set haspreview = 0 where id = %d", $rel['ID']));
				
				//
				// Go through the nzb for this release looking for a rar, a sample, and a mediafile
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

					if (preg_match("/\.(001|000|rar|r00|zip)/i", $subject))
						$hasrar= 1;
					
					if (preg_match("/sample/i",$subject) && !preg_match("/\.par2|\.srs/i",$subject))
					{
						$samplesegments = $nzbcontents['segment'];
						$samplepart = (array)$samplesegments;
						if (isset($samplepart))
						{
							$samplegroup = $groupName;
							$samplemsgid = array_merge($samplemsgidm, array($samplepart[0]));
						}
					}
					if (preg_match('/\.('.$this->mediafileregex.')[\. "\)\]]/i',$subject) && !preg_match("/\.par2|\.srs/i",$subject))
					{
						$mediasegments = $nzbcontents['segment'];
						$mediapart = (array)$mediasegments;
						if (isset($mediapart) && $mediapart != $samplemsgid)
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
				
				// Attempt to process sample file.
				if($samplemsgid != -1 && $processSample && $blnTookSample === false)
				{
					$sampleBinary = $nntp->getMessage($samplegroup, $samplemsgid);
					if ($sampleBinary === false) 
					{
						$samplemsgid = -1;
					}
					else
					{						
						$samplefile = $tmpPath.'sample.avi';
						
						file_put_contents($samplefile, $sampleBinary);
						
						$blnTookSample = $this->getSample($tmpPath, $this->site->ffmpegpath, $rel['guid']);
						if ($blnTookSample)
							$this->updateReleaseHasPreview($rel['guid']);
						
						unlink($samplefile);
					}
					unset($sampleBinary);
				}
				
				if ($hasrar)
				{
					foreach ($nzbcontents as $rarFile)
					{
						$rarMsgids = (array)$nzbcontents['segment'];
						$mid = array_merge($mid, array($rarMsgids[0]));
					}
					$mid = array_keys(array_count_values($mid));
				}
				
				$db->query("DELETE FROM `releasefiles` WHERE `releaseID` =".$rel['ID']);
				//if ($this->echooutput)
					//echo "Deleted ".$db->getAffectedRows()." releasefiles.\n";
				
				$bytes = $rel['size'] * 2;
				$bytes = min( 1024*1024*1024, $bytes);
				$this->password = false;
				$lsize = 0;
				$i = 0;
				
				if (!empty($mid) && ($this->site->checkpasswordedrar > 0 || ($processSample && $blnTookSample === false) || $processMediainfo))
				{
					shuffle($nzbfiles);
					foreach ($nzbfiles as $rarFile)
					{
						$subject = $rarFile['subject'];
						//if ($this->echooutput)
							//echo "starting {$rel['guid']}\n";
						if (preg_match("/\.(vol\d{1,3}\+\d{1,3}|par2|sfv)/i", $subject))
							continue;
						
						//if ($this->echooutput)
							//echo "a\n";
						if (!preg_match("/\.\b(part\d+|rar|r\d{1,3}|zipr\d{2,3}|\d{2,3}|zip|zipx)\b/i", $subject))
						{
							//if ($this->echooutput)
								//echo "not matched and skipping $subject\n";
							continue;
						}
						
						//if ($this->echooutput)
							//echo "b\n";
						if ($this->password)
						{
							if ($this->echooutput)
								echo "-Skipping processing of rar $subject was found to be passworded.\n";
							continue;
						}

						$size = $db->queryOneRow("SELECT sum(size) as size FROM `releasefiles` WHERE `releaseID` = ".$rel['ID']);
						//if ($this->echooutput)
							//echo "size = {$size["size"]} name = $subject id = {$rel['ID']} count ".count($nzbfiles)."\n";

						if (is_numeric($size["size"]) && $size["size"] > $bytes)
							continue;


						if (is_numeric($size["size"]) && $size["size"] == $lsize)
							$i++;
						else
							$i = 0;

						$lsize = $size["size"];

						if ($i > count($nzbfiles)/ 10)
						{
							//if ($this->echooutput)
								//echo "new files don't seem to contribute\n";
							continue;
						}
						
						//if ($this->echooutput)
							//echo "c\n";
						//$rarMsgids = array($rarFile['segment']);
						//$mid = array($rarMsgids[0]);
						
						$mid = array_slice((array)$rarFile['segment'], 0, 1);
						
						$fetchedBinary = $nntp->getMessages($bingroup, $mid);
						if ($fetchedBinary === false)
						{
							$db->query(sprintf("update releases set passwordstatus = passwordstatus - 1 where ID = %d", $rel['ID']));
							continue;
						}
						else
						{
							
							$relFiles = $this->processReleaseFiles($fetchedBinary, $rel['ID']);
							//if ($this->echooutput)
								//var_dump($relFiles);
							
							if ($relFiles === false)
							{
								//if ($this->echooutput)
									//echo "error processing files {$rel['ID']}";
								$passStatus[] = Releases::PASSWD_POTENTIAL;
							}
							
							if ($this->password)
								$passStatus[] = Releases::PASSWD_RAR;
							
							//if ($this->echooutput)
								//echo $this->password."\n";
							
							if ($this->site->checkpasswordedrar > 0 && $processPasswords)
							{
								//echo "processReleasePasswords\n";
								//$passStatus[] = $this->processReleasePasswords($fetchedBinary, $tmpPath, $this->site->unrarpath, $this->site->checkpasswordedrar, $rel['ID']);
							}
							
							// we need to unrar the fetched binary if checkpasswordedrar wasnt 2
							if ($this->site->checkpasswordedrar < 2 && $processPasswords)
							{
								$rarfile = $tmpPath.'rarfile.rar';

								//file_put_contents($rarfile, $fetchedBinary);
								//$execstring = '"'.$this->site->unrarpath.'" e -ai -ep -c- -id -r -kb -p- -y -inul "'.$rarfile.'" "'.$tmpPath.'"';
								//$output = runCmd($execstring);
								//unlink($rarfile);
							}

							if ($processSample && $blnTookSample === false)
							{
								$blnTookSample = $this->getSample($tmpPath, $this->site->ffmpegpath, $rel['guid']);
								if ($blnTookSample)
									$this->updateReleaseHasPreview($rel['guid']);
							}

							if ($processMediainfo && $blnTookMediainfo === false)
							{
								$blnTookMediainfo = $this->getMediainfo($tmpPath, $this->site->mediainfopath, $rel['ID']);
							}
						}

						//clean up all files
						foreach(glob($tmpPath.'*') as $v)
						{
							unlink($v);
						}
					}
				}
				elseif(empty($mid) && $hasrar == 1)
				{
					$passStatus[] = Releases::PASSWD_POTENTIAL;
				}

				$hpsql = '';
				if (!$blnTookSample)
					$hpsql = ', haspreview = 0';
				
				//if ($this->echooutput)
					//echo max($passStatus)."\n";
				
				$sql = sprintf("update releases set passwordstatus = %d %s where ID = %d", max($passStatus), $hpsql, $rel["ID"]);
				$db->query($sql);
			}
			$nntp->doQuit();
			if ($this->echooutput)
				echo "\n";
		}

		@rmdir($tmpPath);
	}
	
	public function processReleaseZips($fetchedBinary, $open = false)
	{
		// Load the ZIP file or data
		$zip = new ZipInfo;

		if ($open)
			$zip->open($fetchedBinary, true);
		else
			$zip->setData($fetchedBinary, true);

		if ($zip->error)
		{
			//if ($this->echooutput)
				//echo "Error: {$zip->error}\n";
		  return false;
		}

		if ($zip->isEncrypted)
		{
			if ($this->echooutput)
				echo "Archive is password encrypted\n";
			$this->password = true;
			return false;
		}

		$files = $zip->getFileList();

		unset($fetchedBinary);
		unset($zip);
		return $files;
	}
	
	public function processReleaseFiles($fetchedBinary, $relid)
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
				//if ($this->echooutput)
					//echo "Error: {$rar->error}\n";
				return false;
			}

			if ($rar->isEncrypted)
			{
				if ($this->echooutput)
					echo "Archive is password encrypted\n";
				$this->password = true;
				return false;
			}
			
			/*if ($this->echooutput)
			{
				echo "nested rar? ".$rar->containsArchive()."\n";
				echo "summary ";
			}*/
			$tmp = $rar->getSummary(true, false);

			if ($tmp["is_encrypted"])
				$this->password = true;
			
			//if ($this->echooutput)
				//var_dump($tmp);
			$files = $rar->getArchiveFileList();
			
			//if ($this->echooutput)
				//var_dump($files);
			if ($files !== false)
			{
				foreach ($files as $file)

					if (isset($file['name']))
					{
						if ($file['pass'])
							$this->password = true;

						if (isset($file['error']))
						{
							//if ($this->echooutput)
								//echo "Error: {$file['error']} (in: {$file['source']})\n";
							continue;
						}

						$ok = preg_match("/main/i", $file['source']) && preg_match("/\.\b(part\d+|rar|r\d{1,3}|zipr\d{2,3}|\d{2,3}|zip|zipx)\b/i", $file['name']) && count($files) > 1;

						if (!$ok)
							$rf->add($relid, $file['name'], $file['size'], $file['date'], $file['pass'] );
						$retval[] = $file['name'];

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
			// Load the ZIP file or data
			$files = processReleaseZips($fetchedBinary, false);
			if ($files !== false)
				foreach ($files as $file)
				{
					if ($file['pass'])
						$this->password = true;

					$rf->add($relid, $file['name'], $file['size'], $file['date'], $file['pass'] );
					$retval[] = $file['name'];
				}
		}
		unset($fetchedBinary);
		return $retval;
	}
	
	public function processReleasePasswords($fetchedBinary, $tmpPath, $unrarPath, $checkpasswordedrar, $relID)
	{
		$passStatus = Releases::PASSWD_NONE;
		$potentiallypasswordedfileregex = "/\.(ace|cab|tar|gz|rar)$/i";
		$rar = new RarInfo;
		
		if ($rar->setData($fetchedBinary))
		{
			if ($rar->isEncrypted)
			{
				$passStatus = Releases::PASSWD_RAR;
			}
			else
			{
				$files = $rar->getFileList();		
				foreach ($files as $file) 
				{
					//
					// individual file rar passworded
					//
					if ($file['pass'] == 1) 
					{
						$passStatus = Releases::PASSWD_RAR;
					}
					//
					// individual file looks suspect
					//
					elseif (preg_match($potentiallypasswordedfileregex, $file["name"]) && $passStatus != Releases::PASSWD_RAR)
					{
						$passStatus = Releases::PASSWD_POTENTIAL;
					}
				}
				
				// rarinnerfilecount
				if (sizeof($files) > 0)
				{
					$db = new DB();
					$db->query(sprintf("UPDATE releases SET rarinnerfilecount = %d WHERE ID = %d", sizeof($files), $relID));
				}
				
				//
				// Deep Checking
				//
				if ($checkpasswordedrar == 2)
				{
					$israr = $this->isRar($fetchedBinary);
					for ($i=0;$i<sizeof($israr);$i++) 
					{
						if (preg_match('/\\\\/',$israr[$i]))
						{
							$israr[$i] = ltrim((strrchr($israr[$i],"\\")),"\\");	
						}
					}
					
					$rarfile = $tmpPath.'rarfile.rar';
					
					file_put_contents($rarfile, $fetchedBinary);
					
					$execstring = '"'.$unrarPath.'" e -ai -ep -c- -id -r -kb -p- -y -inul "'.$rarfile.'" "'.$tmpPath.'"';
					
					$output = runCmd($execstring);

					// delete the rar
					unlink($rarfile);
					
					// ok, now we have all the files extracted from the rar into the tempdir and
					// the rar file deleted, now to loop through the files and recursively unrar
					// if any of those are rars, we don't trust their names and we test every file
					// for the rar header
					for ($i=0;$i<sizeof($israr);$i++)
					{
						$mayberar = @file_get_contents($unrarPath.$israr[$i]);
						$tmp = $this->isRar($mayberar);
						unset($mayberar);
						if (is_array($tmp)) 
						// it's a rar
						{
							for ($x=0;$x<sizeof($tmp);$x++) 
							{
								if (preg_match('/\\\\/',$tmp[$x]))
								{
									$tmp[$x] = ltrim((strrchr($tmp[$x],"\\")),"\\");
								}
								$israr[] = $tmp[$x];
							}
						
							$execstring = '"'.$unrarPath.'" e -ai -ep -c- -id -r -kb -p- -y -inul "'.$tmpPath.$israr[$i].'" "'.$tmpPath.'"';
							
							$output2 = runCmd($execstring);

							unlink($tmpPath.$israr[$i]);
						}
						else
						{
							switch($tmp)
							{
								case 1:
									$passStatus = Releases::PASSWD_RAR;
									unlink($tmpPath.$israr[$i]);
									break;
								case 2:
									$passStatus = Releases::PASSWD_RAR;
									unlink($tmpPath.$israr[$i]);
									break;
							}
						}
						unset($tmp);
					}
				}
			}
		}
		unset($fetchedBinary);
		
		return $passStatus;
	}
	
	public function isRar($rarfile)
	{
	// returns 0 if not rar
	// returns 1 if encrypted rar
	// returns 2 if passworded rar
	// returns array of files in the rar if normal rar
		unset($filelist);
		$rar = new RarInfo;
		if ($rar->setData($rarfile))
		{
			if ($rar->isEncrypted)
			{
				return 1;
			}
			else
			{
				$files = $rar->getFileList();			
				foreach ($files as $file) 
				{
					$filelist[] = $file['name'];
					if ($file['pass'] == true) 
					//
					// individual file rar passworded
					//
					{
						return 2;
						// passworded
					}
				}
				return ($filelist);
				// normal rar
			}					
		}
		else 
		{
			return 0;
			// not a rar
		}
	}
	
	public function getMediainfo($ramdrive,$mediainfo,$releaseID)
	{
		$retval = false;
		$mediafiles = glob($ramdrive.'*.*');
		if (is_array($mediafiles))
		{
			foreach($mediafiles as $mediafile) 
			{
				if (preg_match("/\.(".$this->mediafileregex.")$/i",$mediafile))  
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
				if (preg_match("/\.(".$this->mediafileregex.")$/i",$samplefile)) 
				{
					$execstring = '"'.$ffmpeginfo.'" -q:v 0 -i "'.$samplefile.'" -loglevel quiet -vframes 300 "'.$ramdrive.'zzzz%03d.jpg"';
					$output = runCmd($execstring);		
					$all_files = scandir($ramdrive,1);
					if(preg_match("/zzzz\d{3}\.jpg/",$all_files[1]))
					{
						$ri->saveImage($releaseguid.'_thumb', $ramdrive.$all_files[1], $ri->imgSavePath, 800, 600);
						$retval = true;
					}
					
					//clean up all files
					foreach(glob($ramdrive.'*.jpg') as $v)
					{
						unlink($v);
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
