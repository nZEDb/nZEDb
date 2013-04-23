<?php
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/nntp.php");
require_once(WWW_DIR."/lib/site.php");
require_once(WWW_DIR."/lib/util.php");
require_once(WWW_DIR."/lib/releases.php");
require_once(WWW_DIR."/lib/rarinfo.php");
require_once(WWW_DIR."/lib/releasefiles.php");
require_once(WWW_DIR."/lib/releaseextra.php");
require_once(WWW_DIR."/lib/releaseimage.php");
require_once(WWW_DIR."/lib/tvrage.php");
require_once(WWW_DIR."/lib/anidb.php");
require_once(WWW_DIR."/lib/movie.php");
require_once(WWW_DIR."/lib/music.php");
require_once(WWW_DIR."/lib/console.php");
require_once(WWW_DIR."/lib/nfo.php");
require_once(WWW_DIR."/lib/groups.php");

class PostProcess {
	
	function PostProcess($echooutput=false)
	{
		$this->echooutput = $echooutput;
		$s = new Sites();
		$this->site = $s->get();
		$this-> addqty = (!empty($site->maxaddprocessed)) ? $site->maxaddprocessed : 25;
		
		$this->mediafileregex = 'AVI|VOB|MKV|MP4|TS|WMV|MOV|M4V|F4V|MPG|MPEG';
	}
	
	public function processAll()
	{
		$this->processNfos();
		$this->processMovies();
		$this->processMusic();
		$this->processGames();
		$this->processAnime();
		$this->processTv();
		$this->processAdditional();
	}
	
	//
	// Process nfo files
	//
	public function processNfos()
	{		
		if ($this->site->lookupnfo == 1)
		{
			$nfo = new Nfo($this->echooutput);
			$nfo->processNfoFiles($this->site->lookupimdb, $this->site->lookuptvrage);
		}
	}
	
	//
	// Lookup imdb if enabled
	//
	public function processMovies()
	{	
		if ($this->site->lookupimdb == 1) 
		{
			$movie = new Movie($this->echooutput);
			$movie->processMovieReleases();
		}
	}
	
	//
	// Lookup music if enabled
	//
	public function processMusic()
	{
		if ($this->site->lookupmusic == 1) 
		{
			$music = new Music($this->echooutput);
			$music->processMusicReleases();
		}
	}
	
	//
	// Lookup games if enabled
	//
	public function processGames()
	{
		if ($this->site->lookupgames == 1) 
		{
			$console = new Console($this->echooutput);
			$console->processConsoleReleases();
		}
	}
	
	//
	// Lookup anidb if enabled - always run before tvrage.
	//
	public function processAnime()
	{
		if ($this->site->lookupanidb == 1) 
		{
			$anidb = new AniDB($this->echooutput);
			$anidb->animetitlesUpdate();
			$anidb->processAnimeReleases();
		}
	}
	
	//
	// Process all TV related releases which will assign their series/episode/rage data
	//
	public function processTv()
	{
		if ($this->site->lookuptvrage == 1) 
		{
			$tvrage = new TVRage($this->echooutput);
			$tvrage->processTvReleases(($this->site->lookuptvrage==1));
		}
	}
	
	//
	// Check for passworded releases, RAR contents and Sample/Media info
	//
	public function processAdditional()
	{
		$maxattemptstocheckpassworded = 5;
		$processSample = ($this->site->ffmpegpath != '') ? true : false;
		$processMediainfo = ($this->site->mediainfopath != '') ? true : false;
		$processPasswords = ($this->site->unrarpath != '') ? true : false;
		
		$tmpPath = $this->site->tmpunrarpath;
		if (substr($tmpPath, -strlen( '/' ) ) != '/')
		{
			$tmpPath = $tmpPath.'/';								
		}
		
		$db = new DB;
		$nntp = new Nntp;
		
		//
		// Get out all releases which have not been checked more than max attempts for password.
		//
		$result = $db->query(sprintf("select r.ID, r.guid, r.name, c.disablepreview from releases r 
			left join category c on c.ID = r.categoryID
			where nzbstatus = 1 and (r.passwordstatus between %d and -1)
			or (r.haspreview = -1 and c.disablepreview = 0) order by adddate asc limit %d", ($maxattemptstocheckpassworded + 1) * -1, $this->addqty));
		
		$rescount = sizeof($result);
		if ($rescount > 0)
		{
			echo "Additional post-processing on {$rescount} releases: ";
			$nntp->doConnect();
			
			foreach ($result as $rel)
			{
				// Per release defaults
				$passStatus = array(Releases::PASSWD_NONE);
				$blnTookMediainfo = false;
				$blnTookSample =  ($rel['disablepreview'] == 1) ? true : false; //only attempt sample if not disabled
				echo $rescount--.".";
				
				if ($blnTookSample)
					$db->query(sprintf("update releases set haspreview = 0 where id = %d", $rel['ID']));
				
				//
				// Go through the nzb for this release looking for a rar, a sample, and a mediafile
				//
				$relres = $db->queryOneRow(sprintf("select guid, groupID from releases where ID = %d", $rel["ID"]));
				$guid = $relres["guid"];
				$groupID = $relres["groupID"];
				$groups = new Groups;
				$groupName = $groups->getByNameByID($groupID);
				$samplemsgid = $mediamsgid = -1;
				$bingroup = $samplegroup = $mediagroup = "";
				$norar = 0;

                // Fetch the NZB using the GUID.
                $nzb = new NZB();

				if (!$nzbpath = $nzb->NZBPath($guid))
				{
					echo "ERROR: wrong permissions on NZB file, or it does not exist.\n";
					break;
				}
				$nzbpath = 'compress.zlib://'.$nzbpath;
				if (!$nzbpath)
                {
                    echo "ERROR: NZB file contents empty.\n";
                    break;
                }
				$nzbfile = simplexml_load_file($nzbpath);

				foreach ($nzbfile->file as $nzbcontents)
				{
					$subject = $nzbcontents->attributes()->subject;

					if (preg_match("/\W\.r00/i",$subject)) {
						$norar= 1;
					}
					if (preg_match("/sample/i",$subject) && !preg_match("/\.par2|\.srs/i",$subject))
					{
						$samplesegments = $nzbcontents->segments->segment;
						$samplepart = (string)$samplesegments;
						if (isset($samplepart))
						{
							$samplegroup = $groupName;
							$samplemsgid = $samplepart;
						}
					}
					if (preg_match('/\.('.$this->mediafileregex.')[\. "\)\]]/i',$subject) && !preg_match("/\.par2|\.srs/i",$subject))
					{
						$mediasegments = $nzbcontents->segments->segment;
						$mediapart = (string)$mediasegments;
						if (isset($mediapart) && $mediapart != $samplemsgid)
						{
							$mediagroup = $groupName;
							$samplemsgid = $mediapart;
						}
					}
					if (preg_match("/.*\W(?:part0*1|(?!part\d+)[^.]+)\.rar[ \"\)\]\-]|.*\W(?:\"[\w.\-\',;& ]|(?!\"[\w.\-\',;& ]+)[^.]+)\.(001|((?=10[ \"\)\]\-].+\(\d{1,3}\/\d{2,3})10|11)|part01)[ \"\)\]\-]/i", $subject) && !preg_match("/[-_\.]sub/i", $subject))
					{
						$rarsegments = $nzbcontents->segments->segment;
						$rarpart = (string)$rarsegments;
						if (isset($rarpart))
						{
							$bingroup = $groupName;
							$mid = $rarpart;
						}
					}
				}
				
				// attempt to process sample file
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
				
				if (!empty($mid) && ($this->site->checkpasswordedrar > 0 || ($processSample && $blnTookSample === false) || $processMediainfo))
				{
					$fetchedBinary = $nntp->getMessage($bingroup, $mid);
					if ($fetchedBinary === false) 
					{
						$db->query(sprintf("update releases set passwordstatus = passwordstatus - 1 where ID = %d", $rel['ID']));
						continue;
					}
					else
					{
						$relFiles = $this->processReleaseFiles($fetchedBinary, $rel['ID']);
							
						if ($this->site->checkpasswordedrar > 0 && $processPasswords)
						{
							$passStatus[] = $this->processReleasePasswords($fetchedBinary, $tmpPath, $this->site->unrarpath, $this->site->checkpasswordedrar);
						}
							
						// we need to unrar the fetched binary if checkpasswordedrar wasnt 2
						if ($this->site->checkpasswordedrar < 2 && $processPasswords)
						{
							$rarfile = $tmpPath.'rarfile.rar';
					
							file_put_contents($rarfile, $fetchedBinary);
								
							$execstring = '"'.$this->site->unrarpath.'" e -ai -ep -c- -id -r -kb -p- -y -inul "'.$rarfile.'" "'.$tmpPath.'"';
								
							$output = runCmd($execstring);
			
							unlink($rarfile);
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
				elseif(empty($msgid) && $norar == 1) 
				{
					$passStatus[] = Releases::PASSWD_POTENTIAL;
				}
				
				$hpsql = '';
				if (!$blnTookSample)
					$hpsql = ', haspreview = 0';
				
				$sql = sprintf("update releases set passwordstatus = %d %s where ID = %d", max($passStatus), $hpsql, $rel["ID"]);
				$db->query($sql);				
			}
			$nntp->doQuit();
		}
		echo "\n";
	}
	
	public function processReleaseFiles($fetchedBinary, $relid)
	{
		$retval = array();
		$rar = new RarInfo;
		$rf = new ReleaseFiles;
		
		if ($rar->setData($fetchedBinary))
		{
			$files = $rar->getFileList();		
			foreach ($files as $file) 
			{
				$rf->add($relid, $file['name'], $file['size'], $file['date'], $file['pass'] );
				$retval[] = $file['name'];
			}
		}
		unset($fetchedBinary);
		return $retval;
	}
	
	public function processReleasePasswords($fetchedBinary, $tmpPath, $unrarPath, $checkpasswordedrar)
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
