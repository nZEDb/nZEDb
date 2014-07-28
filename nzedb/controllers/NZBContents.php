<?php
/**
 * Gets information contained within the NZB.
 *
 * Class NZBContents
 */
Class NZBContents
{
	/**
	 * @var nzedb\db\Settings
	 * @access protected
	 */
	public $pdo;

	/**
	 * @var NNTP
	 * @access protected
	 */
	protected $nntp;

	/**
	 * @var Nfo
	 * @access protected
	 */
	protected $nfo;

	/**
	 * @var PostProcess
	 * @access protected
	 */
	protected $pp;

	/**
	 * @var NZB
	 * @access protected
	 */
	protected $nzb;

	/**
	 * @var bool|stdClass
	 * @access protected
	 */
	protected $site;

	/**
	 * @var bool
	 * @access protected
	 */
	protected $alternateNNTP;

	/**
	 * @var bool
	 * @access protected
	 */
	protected $lookuppar2;

	/**
	 * @var bool
	 * @access protected
	 */
	protected $echooutput;

	/**
	 * Construct.
	 *
	 * @param array $options
	 *     array(
	 *         'echo'  => bool        ; To echo to CLI or not.
	 *         'nntp'  => NNTP        ; Class NNTP.
	 *         'nfo'   => Nfo         ; Class Nfo.
	 *         'db'    => DB          ; Class nzedb\db\DB.
	 *         'pp'    => PostProcess ; Class PostProcess.
	 *     )
	 *
	 * @access public
	 */
	public function __construct($options)
	{
		$this->pdo  = $options['db'];
		$this->nntp = $options['nntp'];
		$this->nfo  = $options['nfo'];
		$this->pp   = $options['pp'];

		$this->echooutput = ($options['echo'] && nZEDb_ECHOCLI);
		$this->lookuppar2 = ($this->pdo->getSetting('lookuppar2') == 1 ? true : false);
		$this->alternateNNTP = ($this->pdo->getSetting('alternate_nntp') == 1 ? true : false);
		$this->nzb  = new NZB($this->pdo);
	}

	/**
	 * Look for an .nfo file in the NZB, return the NFO message id.
	 * Gets the NZB completion.
	 * Looks for PAR2 files in the NZB.
	 *
	 * @param string $guid
	 * @param string $relID
	 * @param int    $groupID
	 * @param string $groupName
	 *
	 * @return bool
	 *
	 * @access public
	 */
	public function getNfoFromNZB($guid, $relID, $groupID, $groupName)
	{
		$fetchedBinary = false;

		$messageID = $this->parseNZB($guid, $relID, $groupID, true);
		if ($messageID !== false) {
			$fetchedBinary = $this->nntp->getMessages($groupName, $messageID['ID'], $this->alternateNNTP);
			if ($this->nntp->isError($fetchedBinary)) {
				// NFO download failed, increment attempts.
				$this->pdo->queryExec(sprintf('UPDATE releases SET nfostatus = nfostatus - 1 WHERE id = %d', $relID));
				if ($this->echooutput) {
					echo 'f';
				}
				return false;
			}
			if ($this->nfo->isNFO($fetchedBinary, $guid) === true) {
				if ($this->echooutput) {
					echo ($messageID['hidden'] === false ? '+' : '*');
				}
			} else {
				if ($this->echooutput) {
					echo '-';
				}
				$this->pdo->queryExec(sprintf('UPDATE releases SET nfostatus = 0 WHERE id = %d', $relID));
				$fetchedBinary = false;
			}
		} else {
			if ($this->echooutput) {
				echo '-';
			}
			$this->pdo->queryExec(sprintf('UPDATE releases SET nfostatus = 0 WHERE id = %d', $relID));
		}

		return $fetchedBinary;
	}

	/**
	 * Attempts to get the releasename from a par2 file
	 *
	 * @param string $guid
	 * @param int    $relID
	 * @param int    $groupID
	 * @param int    $nameStatus
	 * @param int    $show
	 *
	 * @return bool
	 *
	 * @access public
	 */
	public function checkPAR2($guid, $relID, $groupID, $nameStatus, $show)
	{
		$nzbFile = $this->LoadNZB($guid);
		if ($nzbFile !== false) {
			foreach ($nzbFile->file as $nzbContents) {
				if (preg_match('/\.(par[2" ]|\d{2,3}").+\(1\/1\)$/i', (string)$nzbContents->attributes()->subject)) {
					if ($this->pp->parsePAR2((string)$nzbContents->segments->segment, $relID, $groupID, $this->nntp, $show) === true && $nameStatus === 1) {
						$this->pdo->queryExec(sprintf('UPDATE releases SET proc_par2 = 1 WHERE id = %d', $relID));
						return true;
					}
				}
			}
		}
		if ($nameStatus === 1) {
			$this->pdo->queryExec(sprintf('UPDATE releases SET proc_par2 = 1 WHERE id = %d', $relID));
		}
		return false;
	}

	/**
	 * Gets the completion from the NZB, optionally looks if there is an NFO/PAR2 file.
	 *
	 * @param string $guid
	 * @param int    $relID
	 * @param int    $groupID
	 * @param bool   $nfoCheck
	 *
	 * @return array|bool
	 *
	 * @access public
	 */
	public function parseNZB($guid, $relID, $groupID, $nfoCheck = false)
	{
		$nzbFile = $this->LoadNZB($guid);
		if ($nzbFile !== false) {
			$messageID = $hiddenID = '';
			$actualParts = $artificialParts = 0;
			$foundPAR2 = ($this->lookuppar2 === false ? true : false);
			$foundNFO = $hiddenNFO = ($nfoCheck === false ? true : false);

			foreach ($nzbFile->file as $nzbcontents) {
				foreach ($nzbcontents->segments->segment as $segment) {
					$actualParts++;
				}

				$subject = (string)$nzbcontents->attributes()->subject;
				if (preg_match('/(\d+)\)$/', $subject, $parts)) {
					$artificialParts += $parts[1];
				}

				if ($foundNFO === false) {
					if (preg_match('/\.\b(nfo|inf|ofn)\b(?![ .-])/i', $subject)) {
						$messageID = (string)$nzbcontents->segments->segment;
						$foundNFO = true;
					}
				}

				if ($foundNFO === false && $hiddenNFO === false) {
					if (preg_match('/\(1\/1\)$/i', $subject) &&
						!preg_match('/\.(apk|bat|bmp|cbr|cbz|cfg|css|csv|cue|db|dll|doc|epub|exe|gif|htm|ico|idx|ini' .
							'|jpg|lit|log|m3u|mid|mobi|mp3|nib|nzb|odt|opf|otf|par|par2|pdf|psd|pps|png|ppt|r\d{2,4}' .
							'|rar|sfv|srr|sub|srt|sql|rom|rtf|tif|torrent|ttf|txt|vb|vol\d+\+\d+|wps|xml|zip)/i',
							$subject))
					{
						$hiddenID = (string)$nzbcontents->segments->segment;
						$hiddenNFO = true;
					}
				}

				if ($foundPAR2 === false) {
					if (preg_match('/\.(par[2" ]|\d{2,3}").+\(1\/1\)$/i', $subject)) {
						if ($this->pp->parsePAR2((string)$nzbcontents->segments->segment, $relID, $groupID, $this->nntp, 1) === true) {
							$this->pdo->queryExec(sprintf('UPDATE releases SET proc_par2 = 1 WHERE id = %d', $relID));
							$foundPAR2 = true;
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

			$this->pdo->queryExec(sprintf('UPDATE releases SET completion = %d WHERE id = %d', $completion, $relID));

			if ($foundNFO === true && strlen($messageID) > 1) {
				return array('hidden' => false, 'ID' => $messageID);
			} elseif ($hiddenNFO === true && strlen($hiddenID) > 1) {
				return array('hidden' => true, 'ID' => $hiddenID);
			}
		}
		return false;
	}

	/**
	 * Decompress a NZB, load it into simplexml and return.
	 *
	 * @param string $guid Release guid.
	 *
	 * @return bool|SimpleXMLElement
	 *
	 * @access public
	 */
	public function LoadNZB(&$guid)
	{
		// Fetch the NZB location using the GUID.
		$nzbPath = $this->nzb->NZBPath($guid);
		if ($nzbPath === false) {
			if ($this->echooutput) {
				echo PHP_EOL . $guid . ' appears to be missing the nzb file, skipping.' . PHP_EOL;
			}
			return false;
		}

		$nzbPath = 'compress.zlib://' . $nzbPath;
		if (!$nzbPath) {
			if ($this->echooutput) {
				echo
					PHP_EOL .
					'Unable to decompress: ' .
					$nzbPath .
					' - ' .
					fileperms($nzbPath) .
					' - may have bad file permissions, skipping.' .
					PHP_EOL;
			}
			return false;
		}

		$nzbFile = @simplexml_load_file($nzbPath);
		if (!$nzbFile) {
			if ($this->echooutput) {
				echo PHP_EOL . "Unable to load NZB: $guid appears to be an invalid NZB, skipping." . PHP_EOL;
			}
			return false;
		}
		return $nzbFile;
	}
}
