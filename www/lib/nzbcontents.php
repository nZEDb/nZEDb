<?php

require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/nzb.php");
require_once(WWW_DIR."/lib/nfo.php");

/*
 * Gets information contained within the NZB.
 */
Class NZBcontents
{
	function NZBcontents($echooutput=false)
	{
		$this->echooutput = $echooutput;
		$s = new Sites();
		$this->site = $s->get();
	}

	public function getNfoFromNZB($guid, $relID, $groupID, $nntp)
	{
		if($fetchedBinary = $this->NFOfromNZB($guid, $relID, $groupID, $nntp))
			return $fetchedBinary;
		else if ($fetchedBinary = $this->hiddenNFOfromNZB($guid, $relID, $groupID, $nntp))
			return $fetchedBinary;
		else
			return false;
	}

	// Returns a XML of the NZB file.
	public function LoadNZB($guid)
	{
		$nzb = new NZB();
		// Fetch the NZB location using the GUID.
		if (!file_exists($nzbpath = $nzb->NZBPath($guid)))
		{
			echo "\n".$guid." appears to be missing the nzb file, skipping.\n";
			return false;
		}
		else if(!$nzbpath = 'compress.zlib://'.$nzbpath)
		{
			echo "\nUnable to decompress: ".$nzbpath." - ".fileperms($nzbpath)." - may have bad file permissions, skipping.\n";
			return false;
		}
		else if(!$nzbfile = simplexml_load_file($nzbpath))
		{
			echo "\nUnable to load NZB: ".$guid." appears to be an invalid NZB, skipping.\n";
			return false;
		}
		else
			return $nzbfile;
	}

	// Gets the completion from the NZB, optionally looks if there is an NFO/PAR2 file.
	public function NZBcompletion($guid, $relID, $groupID, $nfocheck=false)
	{
		$nzbfile = $this->LoadNZB($guid);
		if ($nzbfile !== false)
		{
			$messageid = '';
			$actualParts = $artificialParts = 0;
			$foundnfo = $foundpar2 = false;

			foreach ($nzbfile->file as $nzbcontents)
			{
				foreach($nzbcontents->segments->segment as $segment)
				{
					$actualParts++;
				}

				$subject = $nzbcontents->attributes()->subject;
				if(preg_match('/(\d+)\)$/', $subject, $parts))
					$artificialParts = $artificialParts+$parts[1];

				if ($nfocheck !== false && $foundnfo !== true)
				{
					if (preg_match('/\.\b(nfo|inf|ofn)\b(?![ .-])/i', $subject))
					{
						$messageid = $nzbcontents->segments->segment;
						$foundnfo = true;
					}
				}

				if ($this->site->lookuppar2 == 1 && $foundpar2 === false)
				{
					if (preg_match('/yEnc\s\(1\/1\)|\(1\/1\)$/i', $subject))
					{
						$pp = new Postprocess($this->echooutput);
						if ($pp->parsePAR2($nzbcontents->segments->segment, $relID, $groupID) === true)
							$foundpar2 = true;
					}
				}
			}


			if($artificialParts <= 0 || $actualParts <= 0)
				$completion = 0;
			else
				$completion = ($actualParts/$artificialParts)*100;

			if ($completion > 100)
				$completion = 100;

			$this->updateCompletion($completion, $relID);
			if ($nfocheck !== false)
				return $messageid;
			else
				return true;
		}
	}

	// Look for an .nfo file in the NZB, return the NFO. Also gets the NZB completion.
	public function NFOfromNZB($guid, $relID, $groupID, $nntp)
	{
		$messageid = $this->NZBcompletion($guid, $relID, $groupID, true);
		if ($messageid !== "")
		{
			$nfo = new NFO();
			$nfo->addReleaseNfo($relID);
			$groups = new Groups();
			$fetchedBinary = $nntp->getMessage($groups->getByNameByID($groupID), $messageid);
			if (PEAR::isError($fetchedBinary))
			{
				$nntp->doQuit();
				$this->site->alternate_nntp == "1" ? $nntp->doConnect_A() : $nntp->doConnect();
				$fetchedBinary = $nntp->getMessage($groups->getByNameByID($groupID), $messageid);
				if (PEAR::isError($fetchedBinary))
				{
					$nntp->doQuit();
					if ($this->echooutput)
						echo "\n\nError {$fetchedBinary->code}: {$fetchedBinary->message}\n\n";
					$fetchedBinary = false;
				}
			}
			if ($nfo->isNFO($fetchedBinary) === true)
			{
				if ($this->echooutput)
					echo "+";
				return $fetchedBinary;
			}
			else
				return false;
		}
		else
			return false;
	}

	// Look for an NFO in the nzb which does not end in .nfo, return the nfo.
	public function hiddenNFOfromNZB($guid, $relID, $groupID, $nntp)
	{
		$nzbfile = $this->LoadNZB($guid);
		if ($nzbfile !== false)
		{
			$db = new DB();
			$groups = new Groups();
			$groupName = $groups->getByNameByID($groupID);
			$foundnfo = $failed = false;
			$nfo = new NFO($this->echooutput);
			foreach ($nzbfile->file as $nzbcontents)
			{
				$subject = $nzbcontents->attributes()->subject;
				// Look for a subject with 1 part, ignore common file extensions.
				if (preg_match('/(yEnc\s\(1\/1\)|\(1\/1\)$)/i', $subject) && !preg_match('/\.(apk|bat|bmp|cbr|cbz|cfg|css|csv|cue|db|dll|doc|epub|exe|gif|htm|ico|idx|ini|jpg|lit|log|m3u|mid|mobi|mp3|nib|nzb|odt|opf|otf|par|par2|pdf|psd|pps|png|ppt|r\d{2,4}|rar|sfv|srr|sub|srt|sql|rom|rtf|tif|torrent|ttf|txt|vb|vol\d+\+\d+|wps|xml|zip)/i', $subject))
				{
					$messageid = $nzbcontents->segments->segment;
					if ($messageid !== false)
					{
						$possibleNFO = $nntp->getMessage($groupName, $messageid);
						if (PEAR::isError($possibleNFO))
						{
							$nntp->doQuit();
							$this->site->alternate_nntp == "1" ? $nntp->doConnect_A() : $nntp->doConnect();
							$possibleNFO = $nntp->getMessage($groupName, $messageid);
							if (PEAR::isError($possibleNFO))
							{
								$nntp->doQuit();
								if ($this->echooutput)
									echo "\n\nError {$possibleNFO->code}: {$possibleNFO->message}\n\n";
								$possibleNFO = false;
							}
						}
						if ($possibleNFO !== false)
						{
							if ($nfo->isNFO($possibleNFO) == true)
							{
								$fetchedBinary = $possibleNFO;
								$foundnfo = true;
							}
						}
						else
						{
							// NFO download failed, increment attempts.
							$db->queryDirect(sprintf("UPDATE releases SET nfostatus = nfostatus-1 WHERE ID = %d", $relID));
							$failed = true;
						}
					}
				}
			}
			if ($foundnfo !== false && $failed == false)
			{
				$nfo->addReleaseNfo($relID);
				if ($this->echooutput)
					echo "*";
				return $fetchedBinary;
			}
			if ($foundnfo == false && $failed == false)
			{
				// No NFO file in the NZB.
				if ($this->echooutput)
					echo "-";
				$db->queryDirect(sprintf("update releases set nfostatus = 0 where ID = %d", $relID));
				return false;
			}
			if ($failed == true)
			{
				if ($this->echooutput)
					echo "f";
				return false;
			}
		}
		else
			return false;
	}

	public function nzblist($guid='')
	{
		if (empty($guid))
			return false;

		$nzb = new NZB();
		$nzbpath = $nzb->getNZBPath($guid);
		$nzb = array();

		if (file_exists($nzbpath))
		{
			$nzbpath = 'compress.zlib://'.$nzbpath;
			$xmlObj = @simplexml_load_file($nzbpath);

			if ($xmlObj && strtolower($xmlObj->getName()) == 'nzb')
			{
				foreach($xmlObj->file as $file)
				{
					$nzbfile = array();
					$nzbfile['subject'] = (string) $file->attributes()->subject;
					$nzbfile = array_merge($nzbfile, (array) $file->groups);
					$nzbfile = array_merge($nzbfile, (array) $file->segments);
					$nzb[] = $nzbfile;
					$nzbfile = null;
				}
			}
			else
				$nzb = false;
			unset($xmlObj);
			return $nzb;
		}
		else
			return false;
	}

	//	Update the releases completion.
	function updateCompletion($completion, $relID)
	{
		$db = new DB();
		$db->queryDirect(sprintf("update releases set completion = %d where ID = %d", $completion, $relID));
	}
}
