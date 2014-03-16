<?php
/*
 * Gets information contained within the NZB.
 */
Class NZBContents
{
	public function __construct($echooutput = false)
	{
		$this->echooutput = ($echooutput && nZEDb_ECHOCLI);
		$s = new Sites();
		$this->site = $s->get();
		$this->lookuppar2 = (isset($this->site->lookuppar2)) ? $this->site->lookuppar2 : 0;
		$this->alternateNNTP = ($this->site->alternate_nntp === '1' ? true : false);
	}

	public function getNfoFromNZB($guid, $relID, $groupID, $nntp, $groupName, $db, $nfo)
	{
		if (!isset($nntp)) {
			exit($this->c->error("Not connected to usenet(nzbcontents->getNfoFromNZB).\n"));
		}

		if ($fetchedBinary = $this->NFOfromNZB($guid, $relID, $groupID, $nntp, $groupName, $db, $nfo)) {
			return $fetchedBinary;
		} else if ($fetchedBinary = $this->hiddenNFOfromNZB($guid, $relID, $groupID, $nntp, $groupName, $db, $nfo)) {
			return $fetchedBinary;
		} else {
			return false;
		}
	}

	// Returns a XML of the NZB file.
	public function LoadNZB($guid)
	{
		$nzb = new NZB();
		// Fetch the NZB location using the GUID.
		$nzbpath = $nzb->NZBPath($guid, $this->site->nzbsplitlevel);
		if ($nzbpath === false) {
			if ($this->echooutput) {
				echo "\n" . $guid . " appears to be missing the nzb file, skipping.\n";
			}
			return false;
		}

		$nzbpath = 'compress.zlib://' . $nzbpath;
		if (!$nzbpath) {
			if ($this->echooutput) {
				echo "\nUnable to decompress: " . $nzbpath . ' - ' . fileperms($nzbpath) . " - may have bad file permissions, skipping.\n";
			}
			return false;
		}

		$nzbfile = @simplexml_load_file($nzbpath);
		if (!$nzbfile) {
			if ($this->echooutput) {
				echo "\nUnable to load NZB: " . $guid . " appears to be an invalid NZB, skipping.\n";
			}
			return false;
		}
		return $nzbfile;
	}

	// Attempts to get the releasename from a par2 file
	public function checkPAR2($guid, $relID, $groupID, $db, $pp, $namestatus, $nntp, $show)
	{
		if (!isset($nntp)) {
			exit($this->c->error("Not connected to usenet(nzbcontents->checkPAR2).\n"));
		}

		$nzbfile = $this->LoadNZB($guid);
		if ($nzbfile !== false) {
			foreach ($nzbfile->file as $nzbcontents) {
				if (preg_match('/\.(par[2" ]|\d{2,3}").+\(1\/1\)$/i', $nzbcontents->attributes()->subject)) {
					if ($pp->parsePAR2($nzbcontents->segments->segment, $relID, $groupID, $nntp, $show) === true && $namestatus === 1) {
						$db->queryExec(sprintf('UPDATE releases SET proc_par2 = 1 WHERE id = %d', $relID));
						return true;
					}
				}
			}
		}
		if ($namestatus === 1) {
			$db->queryExec(sprintf('UPDATE releases SET proc_par2 = 1 WHERE id = %d', $relID));
		}
		return false;
	}

	// Gets the completion from the NZB, optionally looks if there is an NFO/PAR2 file.
	public function NZBcompletion($guid, $relID, $groupID, $nntp, $db, $nfocheck = false)
	{
		if (!isset($nntp)) {
			exit($this->c->error("Not connected to usenet(nzbcontents->NZBcompletion).\n"));
		}

		$nzbfile = $this->LoadNZB($guid);
		if ($nzbfile !== false) {
			$messageid = '';
			$actualParts = $artificialParts = 0;
			$foundnfo = $foundpar2 = false;

			foreach ($nzbfile->file as $nzbcontents) {
				foreach ($nzbcontents->segments->segment as $segment) {
					$actualParts++;
				}

				$subject = $nzbcontents->attributes()->subject;
				if (preg_match('/(\d+)\)$/', $subject, $parts)) {
					$artificialParts = $artificialParts + $parts[1];
				}

				if ($nfocheck !== false && $foundnfo !== true) {
					if (preg_match('/\.\b(nfo|inf|ofn)\b(?![ .-])/i', $subject)) {
						$messageid = (string)$nzbcontents->segments->segment;
						$foundnfo = true;
					}
				}
				if ($this->lookuppar2 == 1 && $foundpar2 === false) {
					if (preg_match('/\.(par[2" ]|\d{2,3}").+\(1\/1\)$/i', $subject)) {
						$pp = new PostProcess($this->echooutput);
						if ($pp->parsePAR2((string)$nzbcontents->segments->segment, $relID, $groupID, $nntp, 1) === true) {
							$foundpar2 = true;
						}
					}
				}
			}


			if ($artificialParts <= 0 || $actualParts <= 0) {
				$completion = 0;
			} else {
				$completion = ($actualParts / $artificialParts) * 100;
			}

			if ($completion > 100) {
				$completion = 100;
			}

			$db->queryExec(sprintf('UPDATE releases SET completion = %d WHERE id = %d', $completion, $relID));
			if ($nfocheck !== false) {
				if ($foundnfo === true) {
					return $messageid;
				} else {
					return false;
				}
			} else {
				return true;
			}
		}
		return false;
	}

	// Look for an .nfo file in the NZB, return the NFO. Also gets the NZB completion.
	public function NFOfromNZB($guid, $relID, $groupID, $nntp, $groupName, $db, $nfo)
	{
		if (!isset($nntp)) {
			exit($this->c->error("Not connected to usenet(nzbcontents->NFOfromNZB).\n"));
		}

		$messageid = $this->NZBcompletion($guid, $relID, $groupID, $nntp, $db, true);

		if ($messageid !== false) {
			$fetchedBinary = $nntp->getMessages($groupName, $messageid, $this->alternateNNTP);
			if ($nntp->isError($fetchedBinary)) {
					$fetchedBinary = false;
			}
			if ($nfo->isNFO($fetchedBinary, $guid) === true) {
				if ($this->echooutput) {
					echo '+';
				}
				return $fetchedBinary;
			}
		}
		return false;
	}

	// Look for an NFO in the nzb which does not end in .nfo, return the nfo.
	public function hiddenNFOfromNZB($guid, $relID, $groupID, $nntp, $groupName, $db, $nfo)
	{
		if (!isset($nntp)) {
			exit($this->c->error("Not connected to usenet(nzbcontents->hiddenNFOfromNZB).\n"));
		}

		$nzbfile = $this->LoadNZB($guid);
		if ($nzbfile !== false) {
			$foundnfo = $failed = false;
			foreach ($nzbfile->file as $nzbcontents) {
				$subject = $nzbcontents->attributes()->subject;
				// Look for a subject with 1 part, ignore common file extensions.
				if (preg_match('/\(1\/1\)$/i', $subject) && !preg_match('/\.(apk|bat|bmp|cbr|cbz|cfg|css|csv|cue|db|dll|doc|epub|exe|gif|htm|ico|idx|ini|jpg|lit|log|m3u|mid|mobi|mp3|nib|nzb|odt|opf|otf|par|par2|pdf|psd|pps|png|ppt|r\d{2,4}|rar|sfv|srr|sub|srt|sql|rom|rtf|tif|torrent|ttf|txt|vb|vol\d+\+\d+|wps|xml|zip)/i', $subject)) {
					$messageid = (string)$nzbcontents->segments->segment;
					if ($messageid !== false) {
						$possibleNFO = $nntp->getMessages($groupName, $messageid, $this->alternateNNTP);
						if ($nntp->isError($possibleNFO)) {
							$possibleNFO = false;
						}
						if ($possibleNFO !== false) {
							if ($nfo->isNFO($possibleNFO, $guid) == true) {
								// If a previous attempt failed, set this to false because we got an nfo anyways.
								if ($failed === true) {
									$failed = false;
								}
								$fetchedBinary = $possibleNFO;
								$foundnfo = true;
								break;
							}
							// Set it back to false so we can possibly get another nfo.
							else {
								$possibleNFO = false;
							}
						} else {
							$failed = true;
						}
					}
				}
			}
			if ($foundnfo !== false && $failed === false) {
				if ($this->echooutput) {
					echo '*';
				}
				return $fetchedBinary;
			}
			if ($foundnfo === false && $failed === false) {
				// No NFO file in the NZB.
				if ($this->echooutput) {
					echo '-';
				}
				$db->queryExec(sprintf('UPDATE releases SET nfostatus = 0 WHERE id = %d', $relID));
				return false;
			}
			if ($failed === true) {
				// NFO download failed, increment attempts.
				$db->queryExec(sprintf('UPDATE releases SET nfostatus = nfostatus-1 WHERE id = %d', $relID));
				if ($this->echooutput) {
					echo 'f';
				}
				return false;
			}
		} else {
			return false;
		}
	}

	public function nzblist($guid = '')
	{
		if (empty($guid)) {
			return false;
		}

		$nzb1 = new NZB();
		$nzbpath = $nzb1->NZBPath($guid, $this->site->nzbsplitlevel);
		$nzb = array();

		if ($nzbpath !== false) {
			$xmlObj = @simplexml_load_file('compress.zlib://' . $nzbpath);
			if ($xmlObj && strtolower($xmlObj->getName()) == 'nzb') {
				foreach ($xmlObj->file as $file) {
					$nzbfile = array();
					$nzbfile['subject'] = (string) $file->attributes()->subject;
					$nzbfile = array_merge($nzbfile, (array) $file->groups);
					$nzbfile = array_merge($nzbfile, (array) $file->segments);
					$nzb[] = $nzbfile;
					$nzbfile = null;
				}
			} else {
				$nzb = false;
			}
			unset($xmlObj);
			return $nzb;
		} else {
			return false;
		}
	}
}
