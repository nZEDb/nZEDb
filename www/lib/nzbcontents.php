<?php
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/nzb.php");
require_once(WWW_DIR."lib/nfo.php");

/*
 * Gets information contained within the NZB.
 */
Class NZBcontents
{
	function NZBcontents($echooutput=false)
	{
		$this->echooutput = $echooutput;
	}

	public function getNfoFromNZB($guid, $relID, $groupID, $nntp)
	{
		if($fetchedBinary = $this->NFOfromNZB($guid, $relID, $groupID, $nntp))
		{
			return $fetchedBinary;
		}
		else if ($fetchedBinary = $this->hiddenNFOfromNZB($guid, $relID, $groupID, $nntp))
		{
			return $fetchedBinary;
		}
		else
		{
			return false;
		}
	}

	//
	// Confirm that the .nfo file is not something else.
	//
	public function isNFO($possibleNFO)
	{
		$notin = '/(<?xml|;\sGenerated\sby.+SF\w|^PAR|\.[a-z0-9]{2,7}\s[a-z0-9]{8}|^RAR|\A.{0,10}(JFIF|matroska|ftyp|ID3))/i';
		$ok = false;
		$maxsize = 45 * 1024;

		if ($possibleNFO !== false)
		{
			if (!preg_match($notin, $possibleNFO))
			{
				$pNFOsize = strlen($possibleNFO);
				if ($pNFOsize < $maxsize)
				{
					// exif_imagetype needs a minimum size or else it doesn't work.
					if ($pNFOsize > 15)
					{
						// Check if it's a picture - EXIF.
						if (@exif_imagetype($possibleNFO) == false)
						{
							// Check if it's a picture - JFIF.
							if ($this->check_JFIF($possibleNFO) == false)
							{
								$ok = true;
							}
						}
					}
				}
			}
		}
		return $ok;
	}

	//
	// Look for an .nfo file in the nzb, return the nfo. Also gets the nzb completion.
	//
	public function NFOfromNZB($guid, $relID, $groupID, $nntp)
	{
		$db = new DB();
		$nfo = new NFO();
		$nzb = new NZB();
		$groups = new Groups();
		// Fetch the NZB location using the GUID.
		if (file_exists($nzbpath = $nzb->NZBPath($guid)))
		{
			$nzbpath = 'compress.zlib://'.$nzbpath;
			$nzbfile = simplexml_load_file($nzbpath);
			$foundnfo = false;
			$actualParts = 0;
			$artificialParts = 0;
			$messageid = "";

			foreach ($nzbfile->file as $nzbcontents)
			{
				// Get the completion while we are here...
				foreach($nzbcontents->segments->segment as $segment)
				{
					$actualParts++;
				}

				$subject = $nzbcontents->attributes()->subject;
				if(preg_match('/(?P<total>\d{1,4})\)$/', $subject, $parts))
				{
					$artificialParts = $artificialParts+$parts['total'];
				}

				if ($foundnfo !== true)
				{
					if (preg_match('/\.\b(nfo|inf|ofn)\b(?![ \.\-])/i', $subject))
					{
						$messageid = $nzbcontents->segments->segment;
						$foundnfo = true;
					}
				}
			}
			if($artificialParts <= 0 || $actualParts <= 0)
			{
				$completion = 0;
			}
			else
			{
				$completion = ($actualParts/$artificialParts)*100;
			}
			if ($completion > 100)
			{
				$completion = 100;
			}
			$this->updateCompletion($completion, $relID);

			if ($foundnfo !== false)
			{
				if ($messageid !== "")
				{
					$nfo->addReleaseNfo($relID);
					$groupName = $groups->getByNameByID($groupID);
					$fetchedBinary = $nntp->getMessage($groupName, $messageid);
					if ($this->isNFO($fetchedBinary) == true)
					{
						if ($this->echooutput)
							echo "+";
						return $fetchedBinary;
					}
					else
						return false;
				}
			}
		}
		else
		{
			echo "ERROR: wrong permissions on NZB file, or it does not exist.\n";
			return false;
		}
	}

	//
	// Look for an NFO in the nzb which does not end in .nfo, return the nfo.
	//
	public function hiddenNFOfromNZB($guid, $relID, $groupID, $nntp)
	{
		$db = new DB();
		$nfo = new NFO();
		$nzb = new NZB();
		$groups = new Groups();
		// Fetch the NZB location using the GUID.
		if (file_exists($nzbpath = $nzb->NZBPath($guid)))
		{
			$nzbpath = 'compress.zlib://'.$nzbpath;
			$nzbfile = @simplexml_load_file($nzbpath);
			$foundnfo = false;
			$failed = false;
			$groupName = $groups->getByNameByID($groupID);

			//
			// Ignore common file extensions from the post
			//
			$ext = array();
			foreach ($nzbfile->file as $nzbcontents)
			{
				$subject = $nzbcontents->attributes()->subject;
				if (preg_match('/(yEnc\s\(1\/1\)|\(1\/1\)$)/i', $subject))
				{
					if (preg_match('/\.([a-z][a-z0-9]{2,3})(?:\"|$)/i', $subject, $matches))
					{
						$ext[] = $matches[1];
					}
				}
			}
			$ext = array_count_values($ext);
			asort($ext);
			if (count($ext) > 0)
				$avg = floor(array_sum($ext)/count($ext));
			else
				$avg = 1;

			foreach($ext as $k => $v)
			{
				if ($v < $avg || $v == 1 || preg_match('/\.(nfo|inf|ofn)/i', $k))
					unset($ext[$k]);
			}

			$ext = array_keys($ext);
			$ext = implode("|", $ext);
			$notout = '/\.(\d{2,4}|apk|bat|bmp|cbr|cbz|cfg|css|csv|cue|db|dll|doc|epub|exe|gif|htm|ico|idx|ini|jpg|lit|log|m3u|mid|mobi|mp3|nib|nzb|odt|opf|otf';
			$notout = $notout.'|par|par2|pdf|psd|pps|png|ppt|r\d{2,4}|rar|sfv|srr|sub|srt|sql|rom|rtf|tif|torrent|ttf|txt|vb|vol\d+\+\d+|wps|xml|zip';

			if (strlen($ext) > 0)
				$notout = $notout."|".$ext;
			$notout = $notout.")/i";

			foreach ($nzbfile->file as $nzbcontents)
			{
				$subject = $nzbcontents->attributes()->subject;
				if (preg_match('/(yEnc\s\(1\/1\)|\(1\/1\)$)/i', $subject) && !preg_match($notout, $subject))
				{
					$messageid = $nzbcontents->segments->segment;
					if ($messageid !== false)
					{
						$possibleNFO = $nntp->getMessage($groupName, $messageid);
						if ($possibleNFO !== false)
						{
							if ($this->isNFO($possibleNFO) == true)
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
		{
			echo "ERROR: Wrong permissions on NZB file, or it does not exist.\n";
			$db->query(sprintf("update releases set nzbstatus = 2 where ID = %d", $relID));
			return false;
		}
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

	//
	//	Check if the possible NFo is a JFIF.
	//
	function check_JFIF($filename)
	{
		$fp = @fopen($filename, 'r');
		if ($fp)
		{
			// JFIF often (but not always) starts at offset 6.
			if (fseek($fp, 6) == 0)
			{
				// JFIF header is 16 bytes.
				if (($bytes = fread($fp, 16)) !== false)
				{
					// Make sure it is JFIF header.
					if (substr($bytes, 0, 4) == "JFIF")
					{
						return true;
					}
					else
					{
						return false;
					}
				}
			}
		}
	}

	//
	//	Update the releases completion.
	//
	function updateCompletion($completion, $relID)
	{
		$db = new DB();
		$db->queryDirect(sprintf("update releases set completion = %d where ID = %d", $completion, $relID));
	}
}
?>
