<?php
require_once nZEDb_LIBS . 'rarinfo/archiveinfo.php';
require_once nZEDb_LIBS . 'rarinfo/par2info.php';
Class ProcessAdditional
{
	/**
	 * How many compressed (rar/zip) files to check.
	 * @int
	 * @default 20
	 */
	const maxCompressedFilesToCheck = 20;

	/**
	 * @var nzedb\db\Settings
	 */
	public $pdo;

	/**
	 * @var bool
	 */
	protected $_echoDebug;

	/**
	 * Releases to work on.
	 * @var Array
	 */
	protected $_releases;

	/**
	 * Count of releases to work on.
	 * @var int
	 */
	protected $_totalReleases;

	/**
	 * Current release we are working on.
	 * @var Array
	 */
	protected $_release;

	/**
	 * @var NZB
	 */
	protected $_nzb;

	/**
	 * List of files with sizes/etc contained in the NZB.
	 * @var array
	 */
	protected $_nzbContents;

	/**
	 * @var Groups
	 */
	protected $_groups;

	/**
	 * @param bool        $echo          Echo to CLI.
	 * @param NNTP        $nntp
	 * @param nzedb\db\Settings $pdo
	 */
	public function __construct($echo = false, &$nntp, &$pdo)
	{
		$this->_echoCLI = ($echo && nZEDb_ECHOCLI && (strtolower(PHP_SAPI) === 'cli'));
		$this->_echoDebug = nZEDb_DEBUG;

		$this->_nntp = $nntp;
		$this->pdo = $pdo;

		$this->_nzb = new NZB($this->pdo);
		$this->_groups = new Groups($this->pdo);
		$this->_archiveInfo = new ArchiveInfo();
		$this->_releaseFiles = new ReleaseFiles();
		$this->_nameFixer = new NameFixer($this->_echoCLI);
		$this->_categorize = new Categorize();
		$this->_releaseExtra = new ReleaseExtra();
		$this->_releaseImage = new ReleaseImage($this->pdo);
		$this->_par2Info = new Par2Info();
		$this->_nfo = new Nfo($this->_echoCLI);

		$this->_innerFileBlacklist = ($this->pdo->getSetting('innerfileblacklist') == '' ? false : $this->pdo->getSetting('innerfileblacklist'));
		$this->_maxNestedLevels = ($this->pdo->getSetting('maxnestedlevels') == 0 ? 3 : $this->pdo->getSetting('maxnestedlevels'));
		$this->_extractUsingRarInfo = ($this->pdo->getSetting('extractusingrarinfo') == 0 ? false :true);

		$this->_7zipPath = false;
		$this->_unrarPath = false;

		// Pass the binary extractors to ArchiveInfo.
		$clients = array();
		if ($this->pdo->getSetting('unrarpath') != '') {
			$clients += array(ArchiveInfo::TYPE_RAR => $this->pdo->getSetting('unrarpath'));
			$this->_unrarPath = $this->pdo->getSetting('unrarpath');
		}
		if ($this->pdo->getSetting('zippath') != '') {
			$clients += array(ArchiveInfo::TYPE_ZIP => $this->pdo->getSetting('zippath'));
			$this->_7zipPath = $this->pdo->getSetting('zippath');
		}
		$this->_archiveInfo->setExternalClients($clients);

		$this->_hasGNUFile = (nzedb\utility\Utility::hasCommand('file') === true ? true : false);

		// Maximum amount of releases to fetch per run.
		$this->_queryLimit =
			($this->pdo->getSetting('maxaddprocessed') != '') ? (int)$this->pdo->getSetting('maxaddprocessed') : 25;

		// Maximum message ID's to download per file type in the NZB (video, jpg, etc).
		$this->_segmentsToDownload =
			($this->pdo->getSetting('segmentstodownload') != '') ? (int)$this->pdo->getSetting('segmentstodownload') : 2;

		// Maximum message ID's to download for a RAR file.
		$this->_maximumRarSegments =
			($this->pdo->getSetting('maxpartsprocessed') != '') ? (int)$this->pdo->getSetting('maxpartsprocessed') : 3;

		// Maximum RAR files to check for a password before stopping.
		$this->_maximumRarPasswordChecks =
			($this->pdo->getSetting('passchkattempts') != '') ? (int)$this->pdo->getSetting('passchkattempts') : 1;

		// Maximum size of releases in GB.
		$this->_maxSize =
			($this->pdo->getSetting('maxsizetopostprocess') != '') ? (int)$this->pdo->getSetting('maxsizetopostprocess') : 100;
		$this->_maxSize = ($this->_maxSize === 0 ? '' : 'AND r.size < ' . ($this->_maxSize * 1073741824));

		// Minimum size of releases in MB.
		$this->_minSize =
			($this->pdo->getSetting('minsizetopostprocess') != '') ? (int)$this->pdo->getSetting('minsizetopostprocess') : 1;
		$this->_minSize = ($this->_minSize === 0 ? '' : 'AND r.size > ' . ($this->_minSize * 1048576));

		// Use the alternate NNTP provider for downloading Message-ID's ?
		$this->_alternateNNTP = ($this->pdo->getSetting('alternate_nntp') == 1 ? true : false);

		$this->_ffMPEGDuration = ($this->pdo->getSetting('ffmpeg_duration') != '') ? (int)$this->pdo->getSetting('ffmpeg_duration') : 5;

		$this->_addPAR2Files = ($this->pdo->getSetting('addpar2') === '0') ? false : true;

		$this->_processSample      = ($this->pdo->getSetting('ffmpegpath') == '')        ? false : true;
		$this->_processVideo       = ($this->pdo->getSetting('processvideos') == 0)      ? false : true;
		$this->_processJPGSample   = ($this->pdo->getSetting('processjpg') == 0)         ? false : true;
		$this->_processAudioSample = ($this->pdo->getSetting('processaudiosample') == 0) ? false : true;
		$this->_processMediaInfo   = ($this->pdo->getSetting('mediainfopath') == '')     ? false : true;
		$this->_processAudioInfo   = $this->_processMediaInfo;
		$this->_processPasswords   = (
			((($this->pdo->getSetting('checkpasswordedrar') == 0) ? false : true)) &&
			(($this->pdo->getSetting('unrarpath') == '') ? false : true)
		);

		// Set up the temporary files folder location.
		$this->_mainTmpPath = $this->pdo->getSetting('tmpunrarpath');
		// Check if it ends with a dir separator.
		if (!preg_match('/[\/\\\\]$/', $this->_mainTmpPath)) {
			$this->_mainTmpPath .= DS;
		}
		$this->tmpPath = $this->_mainTmpPath;

		$this->_audioSavePath = nZEDb_COVERS . 'audiosample' . DS;

		$this->_audioFileRegex   = '\.(AAC|AIFF|APE|AC3|ASF|DTS|FLAC|MKA|MKS|MP2|MP3|RA|OGG|OGM|W64|WAV|WMA)';
		$this->_ignoreBookRegex  = '/\b(epub|lit|mobi|pdf|sipdf|html)\b.*\.rar(?!.{20,})/i';
		$this->_supportFileRegex = '/\.(vol\d{1,3}\+\d{1,3}|par2|srs|sfv|nzb';
		$this->_videoFileRegex   = '\.(AVI|F4V|IFO|M1V|M2V|M4V|MKV|MOV|MP4|MPEG|MPG|MPGV|MPV|OGV|QT|RM|RMVB|TS|VOB|WMV)';
	}

	/**
	 * Main method.
	 *
	 * @param string     $release Optional single release to work on.
	 * @param int|string $groupID Optional ID of a group to work on.
	 *
	 * @void
	 */
	public function start($release = '', $groupID = '')
	{
		// Fetch all the releases to work on.
		if ($release === '') {
			// Clear out old folders/files from the temp folder.
			$this->_recursivePathDelete(
				$this->_mainTmpPath,
				// These are folders we don't want to delete.
				array(
					// This is the actual unrar folder.
					$this->_mainTmpPath,
					// This folder is used by misc/testing/Dev/rename_u4e.php
					$this->_mainTmpPath . 'u4e'
				)
			);
			$this->_fetchReleases($groupID);
		} else {
			$release = explode('           =+=            ', $release);
			$this->_releases = array(
				array(
					'id'             => $release[0],
					'guid'           => $release[1],
					'name'           => $release[2],
					'disablepreview' => $release[3],
					'size'           => $release[4],
					'group_id'       => $release[5],
					'nfostatus'      => $release[6],
					'categoryid'     => $release[7],
					'searchname'     => $release[8]
				)
			);
			$this->_totalReleases = 1;
		}

		// Check if we have releases to work on.
		if ($this->_totalReleases > 0) {
			// Echo start time and process description.
			$this->_echoDescription();

			$this->_processReleases();
		}
	}

	/**
	 * Get all releases that need to be processed.
	 *
	 * @param int|string $groupID
	 *
	 * @void
	 */
	protected function _fetchReleases($groupID)
	{
		$this->_releases = array();
		$this->_totalReleases = 0;
		$groupID = ($groupID === '' ? '' : 'AND r.group_id = ' . $groupID);

		$i = -6;
		$limit = $this->_queryLimit;
		// Get releases starting from -6 password status until we reach our max limit set in site or we reach -1 password status.
		while (($this->_totalReleases <= $limit) && ($i <= -1)) {

			$releases = $this->pdo->query(
				sprintf('
						SELECT r.id, r.guid, r.name, c.disablepreview, r.size, r.group_id,
							r.nfostatus, r.completion, r.categoryid, r.searchname
						FROM releases r
						LEFT JOIN category c ON c.id = r.categoryid
						WHERE nzbstatus = 1
						%s %s %s
						AND r.passwordstatus = %d
						AND (r.haspreview = -1 AND c.disablepreview = 0)
						ORDER BY postdate
						DESC LIMIT %d',
					$this->_maxSize, $this->_minSize, $groupID, $i, $limit
				)
			);

			if ($releases === false) {
				return;
			}

			// Get the count of rows we got from the query.
			$currentCount = count($releases);

			if ($currentCount > 0) {

				// Merge the results.
				$this->_releases += $releases;

				// Decrement so we don't get more than the max user specified value.
				$limit -= $currentCount;

				// Update the total results.
				$this->_totalReleases += $currentCount;

				// Echo how many we got for this query.
				$this->_echo('Passwordstatus = ' . $i . ': Available to process = ' . $currentCount);
			}
			$i++;
		}
	}

	/**
	 * Output the description and start time.
	 *
	 * @void
	 */
	protected function _echoDescription()
	{
		if ($this->_totalReleases > 1 && $this->_echoCLI) {
			$this->_echo(
				'Additional post-processing, started at: ' .
				date('D M d, Y G:i a') .
				PHP_EOL .
				'Downloaded: (xB) = yEnc article, f= Failed ;Processing: z = ZIP file, r = RAR file' .
				PHP_EOL .
				'Added: s = Sample image, j = JPEG image, A = Audio sample, a = Audio MediaInfo, v = Video sample' .
				PHP_EOL .
				'Added: m = Video MediaInfo, n = NFO, ^ = File details from inside the RAR/ZIP'
			);
		}
	}

	/**
	 * Loop through the releases, processing them 1 at a time.
	 */
	protected function _processReleases()
	{
		foreach ($this->_releases as $this->_release) {
			$this->_echo(
				'[' . $this->_release['id'] . '][' .
				$this->_readableBytesString($this->_release['size']) . ']',
				false
			);

			// Create folder to store temporary files.
			if ($this->_createTempFolder() === false) {
				continue;
			}

			// Get NZB contents.
			if ($this->_getNZBContents() === false) {
				continue;
			}

			// Reset the current release variables.
			$this->_resetReleaseStatus();

			// Go through the files in the NZB, get the amount of book files.
			$totalBooks = $this->_processNZBContents();

			// Check if this NZB is a large collection of books.
			$bookFlood = false;
			if ($totalBooks > 80 && ($totalBooks * 2) >= count($this->_nzbContents)) {
				$bookFlood = true;
			}

			if ($this->_processPasswords === true ||
				$this->_processSample    === true ||
				$this->_processMediaInfo === true ||
				$this->_processAudioInfo === true ||
				$this->_processVideo     === true
			) {

				// Process usenet Message-ID downloads.
				$this->_processMessageIDDownloads();

				// Process compressed (RAR/ZIP) files inside the NZB.
				if ($bookFlood === false && $this->_NZBHasCompressedFile) {
					// Download the RARs/ZIPs, extract the files inside them and insert the file info into the DB.
					$this->_processNZBCompressedFiles();

					if ($this->_releaseHasPassword === false) {
						// Process the extracted files to get video/audio samples/etc.
						$this->_processExtractedFiles();
					}
				}
			}

			// Update the release to say we processed it.
			$this->_finalizeRelease();

			// Delete all files / folders for this release.
			$this->_recursivePathDelete($this->tmpPath);
		}
		if ($this->_echoCLI) {
			echo PHP_EOL;
		}
	}

	/**
	 * Deletes files and folders recursively.
	 *
	 * @param string $path   Path to a folder or file.
	 * @param array  $ignoredFolders Array with paths to folders to ignore.
	 *
	 * @void
	 * @access protected
	 */
	protected function _recursivePathDelete($path, $ignoredFolders = array())
	{
		if (is_dir($path)) {

			$files = glob(rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR  . '*');

			foreach($files as $file){
				$this->_recursivePathDelete($file, $ignoredFolders);
			}

			if (in_array($path, $ignoredFolders)) {
				return;
			}

			@rmdir($path);

		} else if (is_file($path)) {
			@unlink($path);
		}
	}

	/**
	 * Create a temporary storage folder for the current release.
	 *
	 * @return bool
	 */
	protected function _createTempFolder()
	{
		// Per release defaults.
		$this->tmpPath = $this->_mainTmpPath . $this->_release['guid'] . DS;
		if (!is_dir($this->tmpPath)) {
			$old = umask(0777);
			@mkdir($this->tmpPath, 0777, true);
			@chmod($this->tmpPath, 0777);
			@umask($old);

			if (!is_dir($this->tmpPath)) {

				$this->_echo('Unable to create directory: ' . $this->tmpPath);

				// Decrement password status.
				$this->pdo->queryExec(
					sprintf(
						'UPDATE releases SET passwordstatus = passwordstatus - 1 WHERE id = %d',
						$this->_release['id']
					)
				);
				return false;
			}
		}
		return true;
	}

	/**
	 * Get list of contents inside a release's NZB file.
	 *
	 * @return bool
	 */
	protected function _getNZBContents()
	{
		$nzbPath = $this->_nzb->NZBPath($this->_release['guid']);
		if ($nzbPath === false) {

			$this->_echo('NZB not found for GUID: ' . $this->_release['guid']);

			// The nzb was not located. decrement the password status.
			$this->pdo->queryExec(
				sprintf(
					'UPDATE releases SET passwordstatus = passwordstatus - 1 WHERE id = %d',
					$this->_release['id']
				)
			);
			return false;
		}

		// Turn on output buffering.
		ob_start();
		// Decompress the NZB.
		@readgzfile($nzbPath);
		// Read the nzb into memory.
		$nzbContents = ob_get_contents();
		// Clean (erase) the output buffer and turn off output buffering.
		ob_end_clean();

		// Get a list of files in the nzb.
		$this->_nzbContents = $this->_nzb->nzbFileList($nzbContents);
		if (count($this->_nzbContents) === 0) {

			$this->_echo('NZB is empty or broken for GUID: ' . $this->_release['guid']);

			// There does not appear to be any files in the nzb, decrement password status.
			$this->pdo->queryExec(
				sprintf(
					'UPDATE releases SET passwordstatus = passwordstatus - 1 WHERE id = %d',
					$this->_release['id']
				)
			);
			return false;
		}

		// Sort the files inside the NZB.
		usort($this->_nzbContents, 'ProcessAdditional::_sortNZB');

		return true;
	}

	/**
	 * Current file we are working on inside a NZB.
	 * @var array
	 */
	protected $_currentNZBFile;

	/**
	 * Does the current NZB contain a compressed (RAR/ZIP) file?
	 * @var bool
	 */
	protected $_NZBHasCompressedFile;

	/**
	 * Process the files inside the NZB, find Message-ID's to download.
	 * If we find files with book extensions, return the amount.
	 *
	 * @return int
	 */
	protected function _processNZBContents()
	{
		$totalBookFiles = 0;
		foreach($this->_nzbContents as $this->_currentNZBFile) {

			// Check if it's not a nfo, nzb, par2 etc...
			if (preg_match($this->_supportFileRegex . '|nfo\b|inf\b|ofn\b)($|[ ")\]-])(?!.{20,})/i', $this->_currentNZBFile['title'])) {
				continue;
			}

			// Check if it's a rar/zip.
			if ($this->_NZBHasCompressedFile === false &&
				preg_match('
					/\.(part0*1|part0+|r0+|r0*1|rar|0+|0*10?|zip)(\s*\.rar)*($|[ ")\]-])|"[a-f0-9]{32}\.[1-9]\d{1,2}".*\(\d+\/\d{2,}\)$/i',
					$this->_currentNZBFile['title']
				)
			) {
				$this->_NZBHasCompressedFile = true;
			}

			// Look for a video sample, make sure it's not an image.
			if ($this->_processSample === true &&
				empty($this->_sampleMessageIDs) &&
				preg_match('/sample/i', $this->_currentNZBFile['title']) &&
				!preg_match('/\.jpe?g/i', $this->_currentNZBFile['title'])
			) {

				if (isset($this->_currentNZBFile['segments'])) {
					// Get the amount of segments for this file.
					$segCount = count($this->_currentNZBFile['segments']);
					// If it's more than 1 try to get up to the site specified value of segments.
					for ($i = 0; $i < $this->_segmentsToDownload; $i++) {
						if ($segCount > $i) {
							$this->_sampleMessageIDs[] = (string)$this->_currentNZBFile['segments'][$i];
						} else {
							break;
						}
					}
				}
			}

			// Look for a JPG picture, make sure it's not a CD cover.
			if ($this->_processJPGSample === true &&
				empty($this->_JPGMessageIDs) &&
				!preg_match('/flac|lossless|mp3|music|inner-sanctum|sound/i', $this->_releaseGroupName) &&
				preg_match('/\.jpe?g[. ")\]]/i', $this->_currentNZBFile['title'])
			) {

				if (isset($this->_currentNZBFile['segments'])) {
					// Get the amount of segments for this file.
					$segCount = count($this->_currentNZBFile['segments']);
					// If it's more than 1 try to get up to the site specified value of segments.
					for ($i = 0; $i < $this->_segmentsToDownload; $i++) {
						if ($segCount > $i) {
							$this->_JPGMessageIDs[] = (string)$this->_currentNZBFile['segments'][$i];
						} else {
							break;
						}
					}
				}
			}

			// Look for a video file, make sure it's not a sample, for MediaInfo.
			if ($this->_processMediaInfo === true &&
				empty($this->_MediaInfoMessageIDs) &&
				!preg_match('/sample/i', $this->_currentNZBFile['title']) &&
				preg_match('/' . $this->_videoFileRegex . '[. ")\]]/i', $this->_currentNZBFile['title'])
			) {

				if (isset($this->_currentNZBFile['segments'])) {
					$this->_MediaInfoMessageIDs = (string)$this->_currentNZBFile['segments'][0];
				}
			}

			// Look for a audio file.
			if ($this->_processAudioInfo === true &&
				empty($this->_AudioInfoMessageIDs) &&
				preg_match('/' . $this->_audioFileRegex . '[. ")\]]/i', $this->_currentNZBFile['title'], $type)
			) {

				if (isset($this->_currentNZBFile['segments'])) {
					// Get the extension.
					$this->_AudioInfoExtension = $type[1];
					$this->_AudioInfoMessageIDs = (string)$this->_currentNZBFile['segments'][0];
				}
			}

			// Some releases contain many books, increment this to ignore them later.
			if (preg_match($this->_ignoreBookRegex, $this->_currentNZBFile['title'])) {
				$totalBookFiles++;
			}
		}
		return $totalBookFiles;
	}

	/**
	 * Process the NZB contents, find RAR/ZIP files, download them and extract them.
	 */
	protected function _processNZBCompressedFiles()
	{
		$notInfinite = 0;
		// Loop through the files, attempt to find if password-ed and files. Starting with what not to process.
		foreach ($this->_nzbContents as $nzbFile) {
			if ($this->_maximumRarPasswordChecks > 1 && $notInfinite > $this->_maximumRarPasswordChecks) {
				break;
			} else if ($notInfinite > $this->_maximumRarSegments) {
				break;
			}

			if ($this->_releaseHasPassword === true) {
				$this->_echo('Skipping processing of rar ' . $nzbFile['title'] . ' it has a password.');
				break;
			}

			// Probably not a rar/zip.
			if (!preg_match(
				'/\.\b(part\d+|part00\.rar|part01\.rar|rar|r00|r01|zipr\d{2,3}|zip|zipx)($|[ ")\]-])|"[a-f0-9]{32}\.[1-9]\d{1,2}".*\(\d+\/\d{2,}\)$/i',
				$nzbFile['title'])
			) {
				continue;
			}

			// Get message-id's for the rar file.
			$mID = array_slice((array) $nzbFile['segments'], 0, $this->_maximumRarSegments);

			// Download the article(s) from usenet.
			$fetchedBinary = $this->_nntp->getMessages($this->_releaseGroupName, $mID, $this->_alternateNNTP);
			if ($this->_nntp->isError($fetchedBinary)) {
				$fetchedBinary = false;
			}

			if ($fetchedBinary !== false) {

				// Echo we downloaded compressed file.
				if ($this->_echoCLI) {
					echo '(cB)';
				}

				$notInfinite++;

				// Process the compressed file.
				$decompressed = $this->_processCompressedData($fetchedBinary);

				if ($decompressed === true || $this->_releaseHasPassword === true) {
					break;
				}

			} else {

				if ($this->_echoCLI) {
					echo 'f(' . $notInfinite . ')';
				}

				$notInfinite += 0.2;
			}
		}
	}

	/**
	 * Check if the data is a ZIP / RAR file, extract files, get file info.
	 *
	 * @param string $compressedData
	 *
	 * @return bool
	 */
	protected function _processCompressedData(&$compressedData)
	{
		$this->_compressedFilesChecked++;
		// Give the data to archive info so it can check if it's a rar.
		if ($this->_archiveInfo->setData($compressedData, true) === false) {
			$this->_debug('Data is probably not RAR or ZIP.' . PHP_EOL);
			return false;
		}

		// Check if there's an error.
		if ($this->_archiveInfo->error !== '') {
			$this->_debug('ArchiveInfo Error: ' . $this->_archiveInfo->error);
			return false;
		}

		// Get a summary of the compressed file.
		$dataSummary = $this->_archiveInfo->getSummary(true);

		// Check if the compressed file is encrypted.
		if (!empty($this->_archiveInfo->isEncrypted) || (isset($dataSummary['is_encrypted']) && $dataSummary['is_encrypted'] != 0)) {
			$this->_debug('ArchiveInfo: Compressed file has a password.');
			$this->_releaseHasPassword = true;
			$this->_passwordStatus[] = Releases::PASSWD_RAR;
			return false;
		}

		switch ($dataSummary['main_type']) {
			case ArchiveInfo::TYPE_RAR:
				if ($this->_echoCLI) {
					echo 'r';
				}

				if ($this->_extractUsingRarInfo === false && $this->_unrarPath !== false) {
					$fileName = $this->tmpPath . uniqid() . '.rar';
					file_put_contents($fileName, $compressedData);
					nzedb\utility\runCmd('"' . $this->_unrarPath . '" e -ai -ep -c- -id -inul -kb -or -p- -r -y "' . $fileName . '" "' . $this->tmpPath . 'unrar/"');
					unlink($fileName);
				}
				break;
			case ArchiveInfo::TYPE_ZIP:
				if ($this->_echoCLI) {
					echo 'z';
				}

				if ($this->_extractUsingRarInfo === false && $this->_7zipPath !== false) {
					$fileName = $this->tmpPath . uniqid() . '.zip';
					file_put_contents($fileName, $compressedData);
					nzedb\utility\runCmd('"' . $this->_7zipPath . '" x "' . $fileName . '" -bd -y -o"' . $this->tmpPath . 'unzip/"');
					unlink($fileName);
				}
				break;
			default:
				return false;
		}

		return $this->_processCompressedFileList($dataSummary['main_type']);
	}

	/**
	 * Get a list of all files in the compressed file, add the file info to the DB.
	 *
	 * @return bool
	 */
	protected function _processCompressedFileList()
	{
		// Get a list of files inside the Compressed file.
		$files = $this->_archiveInfo->getArchiveFileList();
		if (!is_array($files) || count($files) === 0) {
			return false;
		}

		// Loop through the files.
		foreach ($files as $file) {

			if ($this->_releaseHasPassword === true) {
				break;
			}

			if (isset($file['name'])) {

				if (isset($file['error'])) {
					$this->_debug("Error: {$file['error']} (in: {$file['source']})");
					continue;
				}

				if ($file['pass'] == true) {
					$this->_releaseHasPassword = true;
					$this->_passwordStatus[] = Releases::PASSWD_RAR;
					break;
				}

				if ($this->_innerFileBlacklist !== false && preg_match($this->_innerFileBlacklist, $file['name'])) {
					$this->_releaseHasPassword = true;
					$this->_passwordStatus[] = Releases::PASSWD_POTENTIAL;
					break;
				}

				if (preg_match('/[^\/\\\\]*\.[a-zA-Z0-9]*$/', $file['name'], $fileName)) {
					$fileName = $fileName[0];
				} else {
					$fileName = '';
				}

				if ($this->_extractUsingRarInfo === true) {
					// Extract files from the rar.
					if (isset($file['compressed']) && $file['compressed'] == 0) {
						@file_put_contents(
							($this->tmpPath . mt_rand(10, 999999) . '_' . $fileName),
							$this->_archiveInfo->getFileData($file['name'], $file['source'])
						);
					}
					// If the files are compressed, use a binary extractor.
					else {
						$this->_archiveInfo->extractFile($file['name'], $this->tmpPath . mt_rand(10, 999999) . '_' . $fileName);
					}
				}
			}

			$this->_addFileInfo($file);
		}
		return ($this->_totalFileInfo > 0 ? true : false);
	}

	/**
	 * Add info from files within RAR/ZIP/PAR2/etc...
	 *
	 * @param array $file
	 *
	 * @void
	 */
	protected function _addFileInfo(&$file)
	{
		// Don't add rar/zip files to the DB.
		if (!isset($file['error']) && isset($file['source']) &&
			!preg_match($this->_supportFileRegex . '|part\d+|r\d{1,3}|zipr\d{2,3}|\d{2,3}|zipx|zip|rar)(\s*\.rar)?$/i', $file['name'])
		) {

			// Cache the amount of files we find in the RAR or ZIP, return this to say we did find RAR or ZIP content.
			// This is so we don't download more RAR or ZIP files for no reason.
			$this->_totalFileInfo++;

			/* Check if we already have the file or not.
			 * Also make sure we don't add too many files, some releases have 100's of files, like PS3 releases.
			 */
			if ($this->_addedFileInfo < 11 &&
				$this->pdo->queryOneRow(
					sprintf('
						SELECT id FROM releasefiles
						WHERE releaseid = %d
						AND name = %s
						AND size = %d',
						$this->_release['id'], $this->pdo->escapeString($file['name']), $file['size']
					)
				) === false
			) {

				if ($this->_releaseFiles->add($this->_release['id'], $file['name'], $file['size'], $file['date'], $file['pass'])) {
					$this->_addedFileInfo++;

					if ($this->_echoCLI) {
						echo '^';
					}

					// Check for "codec spam"
					if (preg_match('/alt\.binaries\.movies($|\.divx$)/', $this->_releaseGroupName) &&
						preg_match('/[\/\\\\]Codec[\/\\\\]Setup\.exe/i', $file['name'])
					) {
						$this->_debug('Codec spam found, setting release to potentially passworded.' . PHP_EOL);
						$this->_releaseHasPassword = true;
						$this->_passwordStatus[] = Releases::PASSWD_POTENTIAL;
					}

					//Run a PreDB filename check on insert to try and match the release
					else if (strpos($file['name'], '.') != 0 && strlen($file['name']) > 0) {
						$this->_release['filename'] = nzedb\utility\Utility::cutStringUsingLast('.', $file['name'], 'left', false);
						$this->_release['releaseid'] = $this->_release['id'];
						$this->_nameFixer->matchPredbFiles($this->_release, 1, 1, true, 1, 'full');
					}
				}
			}
		}
	}

	/**
	 * Go through all the extracted files in the temp folder and process them.
	 */
	protected function _processExtractedFiles()
	{
		$nestedLevels = 0;

		// Go through all the files in the temp folder, look for compressed files, extract them and the nested ones.
		while ($nestedLevels < $this->_maxNestedLevels) {

			// Break out if we checked more than x compressed files.
			if ($this->_compressedFilesChecked >= self::maxCompressedFilesToCheck) {
				break;
			}

			$foundCompressedFile = false;

			// Get all the compressed files in the temp folder.
			$files = $this->_getTempDirectoryContents('/.*\.([rz]\d{2,}|rar|zipx?|0{0,2}1)($|[^a-z0-9])/i');

			foreach ($files as $file) {

				// Check if the file exists.
				if (is_file($file[0])) {
					$rarData = @file_get_contents($file[0]);
					if ($rarData !== false) {
						$this->_processCompressedData($rarData);
						$foundCompressedFile = true;
					}
					@unlink($file[0]);
				}
			}

			// If we found no compressed files, break out.
			if ($foundCompressedFile === false) {
				break;
			}

			$nestedLevels++;
		}

		$fileType = array();

		// Get all the remaining files in the temp dir.
		$files = $this->_getTempDirectoryContents();
		if ($files !== false) {

			foreach ($files as $file) {
				$file = (string)$file;

				// Skip /. and /..
				if (preg_match('/[\/\\\\]\.{1,2}$/', $file)) {
					continue;
				}

				if (is_file($file)) {

					// Process PAR2 files.
					if ($this->_foundPAR2Info === false && preg_match('/\.par2$/', $file)) {
						$this->_siftPAR2Info($file);
					}

					// Process NFO files.
					else if ($this->_releaseHasNoNFO === true && preg_match('/(\.(nfo|inf|ofn)|info\.txt)$/i', $file)) {
						$this->_processNfoFile($file);
					}

					// Process audio files.
					else if (
						($this->_foundAudioInfo === false ||
						$this->_foundAudioSample === false) &&
						preg_match('/(.*)' . $this->_audioFileRegex . '$/i', $file, $fileType)
					) {
						// Try to get audio sample/audio media info.
						@rename($file, $this->tmpPath . 'audiofile.' . $fileType[2]);
						$this->_getAudioInfo($this->tmpPath . 'audiofile.' . $fileType[2], $fileType[2]);
						@unlink($this->tmpPath . 'audiofile.' . $fileType[2]);
					}

					// Process JPG files.
					else if ($this->_foundJPGSample === false && preg_match('/\.jpe?g$/i', $file)) {
						$this->_getJPGSample($file);
						@unlink($file);
					}

					// Video sample // video clip // video media info.
					else if (($this->_foundSample === false || $this->_foundVideo === false || $this->_foundMediaInfo === false) &&
						preg_match('/(.*)' . $this->_videoFileRegex . '$/i', $file)
					) {
						$this->_processVideoFile($file);
					}

					// Check if it's alt.binaries.u4e file.
					else if (in_array($this->_releaseGroupName, array('alt.binaries.u4e', 'alt.binaries.mom')) &&
						preg_match('/Linux_2rename\.sh/i', $file) &&
						$this->_release['categoryid'] == Category::CAT_OTHER_HASHED
					) {
						$this->_processU4ETitle($file);
					}

					// If we have GNU file, check the type of file and process it.
					else if ($this->_hasGNUFile) {
						exec('file -b "' . $file . '"', $output);

						if (!empty($output)) {

							if (count($output) > 1) {
								$output = implode(',', $output);
							} else {
								$output = $output[0];
							}

							switch (true) {

								case ($this->_foundJPGSample === false && preg_match('/^JPE?G/i', $output[0])):
									$this->_getJPGSample($file);
									@unlink($file);
									break;

								case (
									($this->_foundMediaInfo === false || $this->_foundSample === false || $this->_foundVideo === false)
									&& preg_match('/Matroska data|MPEG v4|MPEG sequence, v2|\WAVI\W/i', $output[0])
								):
									$this->_processVideoFile($file);
									break;

								case (
									($this->_foundAudioSample === false || $this->_foundAudioInfo === false) &&
									preg_match('/^FLAC|layer III|Vorbis audio/i', $file, $fileType)
								):
									switch ($fileType[0]) {
										case 'FLAC':
											$fileType = 'FLAC';
											break;
										case 'layer III':
											$fileType = 'MP3';
											break;
										case 'Vorbis audio':
											$fileType = 'OGG';
											break;
									}
									@rename($file, $this->tmpPath . 'audiofile.' . $fileType);
									$this->_getAudioInfo($this->tmpPath . 'audiofile.' . $fileType, $fileType);
									@unlink($this->tmpPath . 'audiofile.' . $fileType);
									break;

								case ($this->_foundPAR2Info === false && preg_match('/^Parity/i', $file)):
									$this->_siftPAR2Info($file);
									break;
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Download all binaries from usenet and form samples / get media info / etc from them.
	 *
	 * @void
	 */
	protected function _processMessageIDDownloads()
	{
		$this->_processSampleMessageIDs();
		$this->_processMediaInfoMessageIDs();
		$this->_processAudioInfoMessageIDs();
		$this->_processJPGMessageIDs();

	}

	/**
	 * Download and process binaries for sample videos.
	 *
	 * @void
	 * @access protected
	 */
	protected function _processSampleMessageIDs()
	{
		// Download and process sample image.
		if ($this->_foundSample === false || $this->_foundVideo === false) {

			if (!empty($this->_sampleMessageIDs)) {

				// Download it from usenet.
				$sampleBinary = $this->_nntp->getMessages($this->_releaseGroupName, $this->_sampleMessageIDs, $this->_alternateNNTP);
				if ($this->_nntp->isError($sampleBinary)) {
					$sampleBinary = false;
				}

				if ($sampleBinary !== false) {
					if ($this->_echoCLI) {
						echo '(sB)';
					}

					// Check if it's more than 40 bytes.
					if (strlen($sampleBinary) > 40) {

						$fileLocation = $this->tmpPath . 'sample_' . mt_rand(0, 99999) . '.avi';
						// Try to create the file.
						@file_put_contents($fileLocation, $sampleBinary);

						// Try to get a sample picture.
						if ($this->_foundSample === false) {
							$this->_foundSample = $this->_getSample($fileLocation);
						}

						// Try to get a sample video.
						if ($this->_foundVideo === false) {
							$this->_foundVideo = $this->_getVideo($fileLocation);
						}

						// Try to get media info. Don't get it here if $mediaMsgID is not empty.
						// 2014-06-28 -> Commented out, since the media info of a sample video is not indicative of the actual release.si
						/*if ($this->_foundMediaInfo === false && empty($mediaMsgID)) {
							$this->_foundMediaInfo = $this->_getMediaInfo($fileLocation);
						}*/

					}
				} else if ($this->_echoCLI) {
					echo 'f';
				}
			}
		}
	}

	/**
	 * Download and process binaries for media info from videos.
	 *
	 * @void
	 * @access protected
	 */
	protected function _processMediaInfoMessageIDs()
	{
		// Download and process mediainfo. Also try to get a sample if we didn't get one yet.
		if ($this->_foundMediaInfo === false || $this->_foundSample === false || $this->_foundVideo === false) {

			if ($this->_foundMediaInfo === false && !empty($this->_MediaInfoMessageIDs)) {

				// Try to download it from usenet.
				$mediaBinary = $this->_nntp->getMessages($this->_releaseGroupName, $this->_MediaInfoMessageIDs, $this->_alternateNNTP);
				if ($this->_nntp->isError($mediaBinary)) {
					// If error set it to false.
					$mediaBinary = false;
				}

				if ($mediaBinary !== false) {

					if ($this->_echoCLI) {
						echo '(mB)';
					}

					// If it's more than 40 bytes...
					if (strlen($mediaBinary) > 40) {

						$fileLocation = $this->tmpPath . 'media.avi';
						// Create a file on the disk with it.
						@file_put_contents($fileLocation, $mediaBinary);

						// Try to get media info.
						if ($this->_foundMediaInfo === false) {
							$this->_foundMediaInfo = $this->_getMediaInfo($fileLocation);
						}

						// Try to get a sample picture.
						if ($this->_foundSample === false) {
							$this->_foundSample = $this->_getSample($fileLocation);
						}

						// Try to get a sample video.
						if ($this->_foundVideo === false) {
							$this->_foundVideo = $this->_getVideo($fileLocation);
						}
					}
				} else if ($this->_echoCLI) {
					echo 'f';
				}
			}
		}
	}

	/**
	 * Download and process binaries for media info from songs.
	 *
	 * @void
	 * @access protected
	 */
	protected function _processAudioInfoMessageIDs()
	{
		// Download audio file, use media info to try to get the artist / album.
		if (($this->_foundAudioInfo === false || $this->_foundAudioSample === false)) {

			if (!empty($this->_AudioInfoMessageIDs)) {
				// Try to download it from usenet.
				$audioBinary = $this->_nntp->getMessages($this->_releaseGroupName, $this->_AudioInfoMessageIDs, $this->_alternateNNTP);
				if ($this->_nntp->isError($audioBinary)) {
					$audioBinary = false;
				}

				if ($audioBinary !== false) {
					if ($this->_echoCLI) {
						echo '(aB)';
					}

					$fileLocation = $this->tmpPath . 'audio.' . $this->_AudioInfoExtension;
					// Create a file with it.
					@file_put_contents($fileLocation, $audioBinary);

					// Try to get media info / sample of the audio file.
					$this->_getAudioInfo($fileLocation, $this->_AudioInfoExtension);

				} else if ($this->_echoCLI) {
					echo 'f';
				}
			}
		}
	}

	/**
	 * Download and process binaries for JPG pictures.
	 *
	 * @void
	 * @access protected
	 */
	protected function _processJPGMessageIDs()
	{
		// Download JPG file.
		if ($this->_foundJPGSample === false && !empty($this->_JPGMessageIDs)) {

			// Try to download it.
			$jpgBinary = $this->_nntp->getMessages($this->_releaseGroupName, $this->_JPGMessageIDs, $this->_alternateNNTP);
			if ($this->_nntp->isError($jpgBinary)) {
				$jpgBinary = false;
			}

			if ($jpgBinary !== false) {

				if ($this->_echoCLI) {
					echo '(jB)';
				}

				// Try to create a file with it.
				@file_put_contents($this->tmpPath . 'samplepicture.jpg', $jpgBinary);

				// Try to resize and move it.
				$this->_foundJPGSample = (
					$this->_releaseImage->saveImage(
						$this->_release['guid'] . '_thumb', $this->tmpPath . 'samplepicture.jpg',
						$this->_releaseImage->jpgSavePath, 650, 650
					) === 1 ? true : false
				);

				if ($this->_foundJPGSample !== false) {
					// Update the DB to say we got it.
					$this->pdo->queryExec(
						sprintf('
							UPDATE releases
							SET jpgstatus = %d
							WHERE id = %d', 1,
							$this->_release['id']
						)
					);

					if ($this->_echoCLI) {
						echo 'j';
					}
				}

				@unlink($this->tmpPath . 'samplepicture.jpg');

			} else if ($this->_echoCLI) {
				echo 'f';
			}
		}
	}

	/**
	 * Update the release to say we processed it.
	 */
	protected function _finalizeRelease()
	{
		$vSQL = $jSQL = $query = '';
		$iSQL = ', haspreview = 0';

		// If samples exist from previous runs, set flags.
		if (is_file($this->_releaseImage->imgSavePath . $this->_release['guid'] . '_thumb.jpg')) {
			$iSQL = ', haspreview = 1';
		}

		if (is_file($this->_releaseImage->vidSavePath . $this->_release['guid'] . '.ogv')) {
			$vSQL = ', videostatus = 1';
		}

		if (is_file($this->_releaseImage->jpgSavePath . $this->_release['guid'] . '_thumb.jpg')) {
			$jSQL = ', jpgstatus = 1';
		}

		// Get the amount of files we found inside the RAR/ZIP files.
		$releaseFiles = $this->pdo->queryOneRow(
			sprintf('
				SELECT COUNT(releasefiles.releaseid) AS count,
				SUM(releasefiles.size) AS size
				FROM releasefiles
				WHERE releaseid = %d',
				$this->_release['id']
			)
		);

		if ($releaseFiles === false) {
			$releaseFiles['count'] = $releaseFiles['size'] = 0;
		}

		$this->_passwordStatus = max($this->_passwordStatus);

		// Set the release to no password if password processing is off.
		if ($this->_processPasswords === false) {
			$this->_releaseHasPassword = false;
		}

		// If we failed to get anything from the RAR/ZIPs, decrement the passwordstatus, if the rar/zip has no password.
		if ($this->_releaseHasPassword === false && $this->_NZBHasCompressedFile && $releaseFiles['count'] == 0) {
			$query = sprintf('
				UPDATE releases
				SET passwordstatus = passwordstatus - 1, rarinnerfilecount = %d %s %s %s
				WHERE id = %d',
				$releaseFiles['count'],
				$iSQL,
				$vSQL,
				$jSQL,
				$this->_release['id']
			);
		}

		// Else update the release with the password status (if the admin enabled the setting).
		else {
			$query = sprintf('
				UPDATE releases
				SET passwordstatus = %d, rarinnerfilecount = %d %s %s %s
				WHERE id = %d',
				($this->_processPasswords === true ? $this->_passwordStatus : Releases::PASSWD_NONE),
				$releaseFiles['count'],
				$iSQL,
				$vSQL,
				$jSQL,
				$this->_release['id']
			);
		}

		$this->pdo->queryExec($query);
	}

	/**
	 * Return array of files in the Temp Directory.
	 * Optional, pass a regex to filter the files.
	 *
	 * @param string $pattern Regex, optional
	 * @param string $path    Path to the folder (if empty, uses $this->tmpPath)
	 *
	 * @return Iterator Object|bool
	 */
	protected function _getTempDirectoryContents($pattern = '', $path = '')
	{
		if ($path === '') {
			$path = $this->tmpPath;
		}
		try {
			if ($pattern !== '') {
				return new RegexIterator(
					new RecursiveIteratorIterator(
						new RecursiveDirectoryIterator($path)
					),
					$pattern,
					RecursiveRegexIterator::GET_MATCH
				);
			} else {
				return new RecursiveIteratorIterator(
					new RecursiveDirectoryIterator($path)
				);
			}
		} catch (exception $e) {
			$this->_debug('ERROR: Could not open temp dir: ' . $e->getMessage() . PHP_EOL);
			return false;
		}
	}

	/**
	 * Fetch MediaInfo and a OGG sample for a Audio file.
	 *
	 * @param string $fileLocation
	 * @param string $fileExtension
	 *
	 * @return bool
	 */
	protected function _getAudioInfo($fileLocation, $fileExtension)
	{
		// Return values.
		$retVal = $audVal = false;

		// Check if audio sample fetching is on.
		if ($this->_processAudioSample === false) {
			$audVal = true;
		}

		// Check if media info fetching is on.
		if ($this->_processAudioInfo === false) {
			$retVal = true;
		}

		// Make sure the category is music or other.
		$rQuery = $this->pdo->queryOneRow(
			sprintf(
				'SELECT searchname, categoryid as id, group_id FROM releases WHERE proc_pp = 0 AND id = %d',
				$this->_release['id']
			)
		);

		$musicParent = (string)Category::CAT_PARENT_MUSIC;
		if ($rQuery === false || !preg_match(
				sprintf(
					'/%d\d{3}|%d|%d|%d/',
					$musicParent[0],
					Category::CAT_MISC,
					Category::CAT_MOVIE_OTHER,
					Category::CAT_TV_OTHER
				),
				$rQuery['id']
			)
		) {
			return false;
		}

		if (is_file($fileLocation)) {

			// Check if media info is enabled.
			if ($retVal === false) {

				//  Get the media info for the file.
				$xmlArray = nzedb\utility\runCmd('"' . $this->pdo->getSetting('mediainfopath') . '" --Output=XML "' . $fileLocation . '"');
				if (is_array($xmlArray)) {

					// Convert to array.
					$arrXml = nzedb\utility\objectsIntoArray(@simplexml_load_string(implode("\n", $xmlArray)));

					if (isset($arrXml['File']['track'])) {

						foreach ($arrXml['File']['track'] as $track) {

							if (isset($track['Album']) && isset($track['Performer'])) {

								if (nZEDb_RENAME_MUSIC_MEDIAINFO) {
									// Make the extension upper case.
									$ext = strtoupper($fileExtension);

									// Form a new search name.
									if (!empty($track['Recorded_date']) && preg_match('/(?:19|20)\d\d/', $track['Recorded_date'], $Year)) {
										$newName = $track['Performer'] . ' - ' . $track['Album'] . ' (' . $Year[0] . ') ' . $ext;
									} else {
										$newName = $track['Performer'] . ' - ' . $track['Album'] . ' ' . $ext;
									}

									// Get the category or try to determine it.
									if ($ext === 'MP3') {
										$newCat = Category::CAT_MUSIC_MP3;
									} else if ($ext === 'FLAC') {
										$newCat = Category::CAT_MUSIC_LOSSLESS;
									} else {
										$newCat = $this->_categorize->determineCategory($newName, $rQuery['group_id']);
									}

									// Update the search name.
									$this->pdo->queryExec(
										sprintf('
											UPDATE releases
											SET searchname = %s, categoryid = %d, iscategorized = 1, isrenamed = 1, proc_pp = 1
											WHERE id = %d',
											$this->pdo->escapeString(substr($newName, 0, 255)),
											$newCat,
											$this->_release['id']
										)
									);

									// Echo the changed name.
									if ($this->_echoCLI) {
										NameFixer::echoChangedReleaseName(array(
												'new_name'     => $newName,
												'old_name'     => $rQuery['searchname'],
												'new_category' => $newCat,
												'old_category' => $rQuery['id'],
												'group'        => $rQuery['group_id'],
												'release_id'   => $this->_release['id'],
												'method'       => 'ProcessAdditional->_getAudioInfo'
											)
										);
									}
								}

								// Add the media info.
								$this->_releaseExtra->addFromXml($this->_release['id'], $xmlArray);

								$retVal = true;
								$this->_foundAudioInfo = true;
								if ($this->_echoCLI) {
									echo 'a';
								}
								break;
							}
						}
					}
				}
			}

			// Check if creating audio samples is enabled.
			if ($audVal === false) {

				// File name to store audio file.
				$audioFileName = ($this->_release['guid'] . '.ogg');

				// Create an audio sample.
				nzedb\utility\runCmd(
					'"' .
					$this->pdo->getSetting('ffmpegpath') .
					'" -t 30 -i "' .
					$fileLocation .
					'" -acodec libvorbis -loglevel quiet -y "' .
					$this->tmpPath . $audioFileName .
					'"'
				);

				// Check if the new file was created.
				if (is_file($this->tmpPath . $audioFileName)) {

					// Try to move the temp audio file.
					$renamed = rename($this->tmpPath . $audioFileName, $this->_audioSavePath . $audioFileName);

					if (!$renamed) {
						// Try to copy it if it fails.
						$copied = copy($this->tmpPath . $audioFileName, $this->_audioSavePath . $audioFileName);

						// Delete the old file.
						unlink($this->tmpPath . $audioFileName);

						// If it didn't copy continue.
						if (!$copied) {
							return false;
						}
					}

					// Try to set the file perms.
					@chmod($this->_audioSavePath . $audioFileName, 0764);

					// Update DB to said we got a audio sample.
					$this->pdo->queryExec(
						sprintf('
							UPDATE releases
							SET audiostatus = 1
							WHERE id = %d',
							$this->_release['id']
						)
					);

					$audVal = $this->_foundAudioSample = true;

					if ($this->_echoCLI) {
						echo 'A';
					}

				}
			}
		}
		return ($retVal && $audVal);
	}

	/**
	 * Try to get JPG picture, resize it and store it on disk.
	 *
	 * @param string $fileLocation
	 */
	protected function _getJPGSample($fileLocation)
	{
		// Try to resize/move the image.
		$this->_foundJPGSample = (
			$this->_releaseImage->saveImage(
				$this->_release['guid'] . '_thumb',
				$fileLocation, $this->_releaseImage->jpgSavePath, 650, 650
			) === 1 ? true : false
		);

		// If it's successful, tell the DB.
		if ($this->_foundJPGSample !== false) {
			$this->pdo->queryExec(
				sprintf('
					UPDATE releases
					SET jpgstatus = %d
					WHERE id = %d',
					1,
					$this->_release['id']
				)
			);
		}
	}

	/**
	 * Try to get a preview image from a video file.
	 *
	 * @param string $fileLocation
	 *
	 * @return bool
	 */
	protected function _getSample($fileLocation)
	{
		if (!$this->_processSample) {
			return false;
		}

		if (is_file($fileLocation)) {

			// Get the exact time of this video.
			$time = @exec(
				'"' .
				$this->pdo->getSetting('ffmpegpath') .
				'" -i "' .
				$fileLocation .
				'" -vcodec copy -f null /dev/null 2>&1 | cut -f 6 -d \'=\' | grep \'^[0-9].*bitrate\' | cut -f 1 -d \' \''
			);

			// If it's 11 chars long, it's good (00:00:00.00)
			if (strlen($time) !== 11) {
				// If not set it to 1 second.
				$time = '00:00:01';
			}

			// Create path to temp file.
			$fileName = ($this->tmpPath . 'zzzz' . mt_rand(5, 12) . mt_rand(5, 12) . '.jpg');

			// Create the image.
			nzedb\utility\runCmd(
				'"' .
				$this->pdo->getSetting('ffmpegpath') .
				'" -i "' .
				$fileLocation .
				'" -ss ' .
				$time .
				' -loglevel quiet -vframes 1 -y "' .
				$fileName .
				'"'
			);


			// Check if the file exists.
			if (is_file($fileName)) {

				// Try to resize/move the image.
				$saved = $this->_releaseImage->saveImage(
					$this->_release['guid'] . '_thumb',
					$fileName,
					$this->_releaseImage->imgSavePath, 800, 600
				);

				// Delete the temp file we created.
				@unlink($fileName);

				// Check if it saved.
				if ($saved === 1) {

					if ($this->_echoCLI) {
						echo 's';
					}
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Try to get a preview video from a video file.
	 *
	 * @param string $fileLocation
	 *
	 * @return bool
	 */
	protected function _getVideo($fileLocation)
	{
		if (!$this->_processVideo) {
			return false;
		}

		// Try to find an avi file.
		if (is_file($fileLocation)) {

			// Create a filename to store the temp file.
			$fileName = ($this->tmpPath . 'zzzz' . $this->_release['guid'] . '.ogv');

			// If wanted sample length is less than 60, try to get sample from the end of the video.
			if ($this->_ffMPEGDuration < 60) {
				// Get the real duration of the file.
				$time = @exec(
					'"' .
					$this->pdo->getSetting('ffmpegpath') .
					'" -i "' .
					$fileLocation .
					'" -vcodec copy -f null /dev/null 2>&1 | cut -f 6 -d \'=\' | grep \'^[0-9].*bitrate\' | cut -f 1 -d \' \''
				);

				// If we don't get the time create the sample the old way (gets the start of the video).
				$numbers = array();
				if (!preg_match('/^\d{2}:\d{2}:(\d{2}).(\d{2})$/', $time, $numbers)) {
					nzedb\utility\runCmd(
						'"' .
						$this->pdo->getSetting('ffmpegpath') .
						'" -i "' .
						$fileLocation .
						'" -vcodec libtheora -filter:v scale=320:-1 -t ' .
						$this->_ffMPEGDuration .
						' -acodec libvorbis -loglevel quiet -y "' .
						$fileName .
						'"'
					);
				} else {
					// Get the max seconds from the video clip.
					$maxLength = (int)$numbers[1];

					// If the clip is shorter than the length we want.
					if ($maxLength <= $this->_ffMPEGDuration) {
						// The lowest we want is 0.
						$lowestLength = '00:00:00.00';

						// If it's longer.
					} else {
						// The lowest we want is the the difference .
						$lowestLength = ($maxLength - $this->_ffMPEGDuration);

						// Form the time string.
						$end = '.' . $numbers[2];
						switch (strlen($lowestLength)) {
							case 1:
								$lowestLength = '00:00:0' . (string)$lowestLength . $end;
								break;
							case 2:

								$lowestLength = '00:00:' . (string)$lowestLength . $end;
								break;
							default:
								$lowestLength = '00:00:60.00';
						}
					}

					// Try to get the sample (from the end instead of the start).
					nzedb\utility\runCmd(
						'"' .
						$this->pdo->getSetting('ffmpegpath') .
						'" -i "' .
						$fileLocation .
						'" -ss ' . $lowestLength .
						' -t ' . $this->_ffMPEGDuration .
						' -vcodec libtheora -filter:v scale=320:-1 ' .
						' -acodec libvorbis -loglevel quiet -y "' .
						$fileName .
						'"'
					);
				}
			} else {
				// If longer than 60, then run the old way.
				nzedb\utility\runCmd(
					'"' .
					$this->pdo->getSetting('ffmpegpath') .
					'" -i "' .
					$fileLocation .
					'" -vcodec libtheora -filter:v scale=320:-1 -t ' .
					$this->_ffMPEGDuration .
					' -acodec libvorbis -loglevel quiet -y "' .
					$fileName .
					'"'
				);
			}

			// Until we find the video file.
			if (is_file($fileName)) {

				// Create a path to where the file should be moved.
				$newFile = ($this->_releaseImage->vidSavePath . $this->_release['guid'] . '.ogv');

				// Try to move the file to the new path.
				$renamed = @rename($fileName, $newFile);

				// If we couldn't rename it, try to copy it.
				if (!$renamed) {

					$copied = @copy($fileName, $newFile);

					// Delete the old file.
					@unlink($fileName);

					// If it didn't copy, continue.
					if (!$copied) {
						return false;
					}
				}

				// Change the permissions.
				@chmod($newFile, 0764);

				// Update query to say we got the video.
				$this->pdo->queryExec(
					sprintf('
						UPDATE releases
						SET videostatus = 1
						WHERE guid = %s',
						$this->pdo->escapeString($this->_release['guid'])
					)
				);
				if ($this->_echoCLI) {
					echo 'v';
				}
				return true;
			}
		}
		return false;
	}

	/**
	 * Try to get media info xml from a video file.
	 *
	 * @param string $fileLocation
	 *
	 * @return bool
	 */
	protected function _getMediaInfo($fileLocation)
	{
		if (!$this->_processMediaInfo) {
			return false;
		}

		// Look for the video file.
		if (is_file($fileLocation)) {

			// Run media info on it.
			$xmlArray = nzedb\utility\runCmd('"' . $this->pdo->getSetting('mediainfopath') . '" --Output=XML "' . $fileLocation . '"');

			// Check if we got it.
			if (is_array($xmlArray)) {

				// Convert it to string.
				$xmlArray = implode("\n", $xmlArray);

				if (!preg_match('/<track type="(Audio|Video)">/i', $xmlArray)) {
					return false;
				}

				// Insert it into the DB.
				$this->_releaseExtra->addFull($this->_release['id'], $xmlArray);
				$this->_releaseExtra->addFromXml($this->_release['id'], $xmlArray);

				if ($this->_echoCLI) {
					echo 'm';
				}
				return true;
			}
		}
		return false;
	}

	/**
	 * Get file info from inside PAR2, store it in DB, attempt to get a release name.
	 *
	 * @param string $fileLocation
	 */
	protected function _siftPAR2Info($fileLocation)
	{
		$this->_par2Info->open($fileLocation);

		if ($this->_par2Info->error) {
			return;
		}

		$releaseInfo = $this->pdo->queryOneRow(
			sprintf('
				SELECT UNIX_TIMESTAMP(postdate) AS postdate, proc_pp
				FROM releases
				WHERE id = %d',
				$this->_release['id']
			)
		);

		if ($releaseInfo === false) {
			return;
		}

		// Only get a new name if the category is OTHER.
		$foundName = true;
		if (nZEDb_RENAME_PAR2 &&
			$releaseInfo['proc_pp'] == 0 &&
			in_array(((int)$this->_release['categoryid']),
				array(
					Category::CAT_BOOKS_OTHER,
					Category::CAT_GAME_OTHER,
					Category::CAT_MOVIE_OTHER,
					Category::CAT_MUSIC_OTHER,
					Category::CAT_PC_PHONE_OTHER,
					Category::CAT_TV_OTHER,
					Category::CAT_OTHER_HASHED,
					Category::CAT_XXX_OTHER,
					Category::CAT_MISC
				)
			)
		) {
			$foundName = false;
		}

		$filesAdded = 0;

		$files = $this->_par2Info->getFileList();
		foreach ($files as $file) {

			if (!isset($file['name'])) {
				continue;
			}

			// If we found a name and added 10 files, stop.
			if ($foundName === true && $filesAdded > 10) {
				break;
			}

			// Add to release files.
			if ($this->_addPAR2Files) {
				if ($filesAdded < 11 &&
					$this->pdo->queryOneRow(
						sprintf('SELECT id FROM releasefiles WHERE releaseid = %d AND name = %s',
						$this->_release['id'], $this->pdo->escapeString($file['name']))) === false
				) {

					// Try to add the files to the DB.
					if ($this->_releaseFiles->add($this->_release['id'], $file['name'], $file['size'], $releaseInfo['postdate'], 0)) {
						$filesAdded++;
					}
				}
			} else {
				$filesAdded++;
			}

			// Try to get a new name.
			if ($foundName === false) {
				$this->_release['textstring'] = $file['name'];
				$this->_release['releaseid'] = $this->_release['id'];
				if ($this->_nameFixer->checkName($this->_release, ($this->_echoCLI ? 1 : 0), 'PAR2, ', 1, 1) === true) {
					$foundName = true;
				}
			}
		}
		// Update the file count with the new file count + old file count.
		$this->pdo->queryExec(
			sprintf(
				'UPDATE releases SET rarinnerfilecount = rarinnerfilecount + %d WHERE id = %d',
				$filesAdded,
				$this->_release['id']
			)
		);
		$this->_foundPAR2Info = true;
	}

	/**
	 * Verify a file is a NFO and add it to the database.
	 *
	 * @param string $fileLocation
	 */
	protected function _processNfoFile($fileLocation)
	{
		$data = @file_get_contents($fileLocation);
		if ($data !== false) {
			if ($this->_nfo->isNFO($data, $this->_release['guid']) === true) {
				if ($this->_nfo->addAlternateNfo($data, $this->_release, $this->_nntp) === true) {
					$this->_releaseHasNoNFO = false;
				}
			}
		}
	}

	/**
	 * Process a video file for a preview image/video and mediainfo.
	 *
	 * @param string $fileLocation
	 */
	protected function _processVideoFile($fileLocation)
	{
		// Try to get a sample with it.
		if ($this->_foundSample === false) {
			$this->_foundSample = $this->_getSample($fileLocation);
		}

		/* Try to get a video with it.
		 * Don't get it here if _sampleMessageIDs is empty
		 * or has 1 message-id (Saves downloading another part).
		 */
		if ($this->_foundVideo === false && count($this->_sampleMessageIDs) < 2) {
			$this->_foundVideo = $this->_getVideo($fileLocation);
		}

		// Try to get media info with it.
		if ($this->_foundMediaInfo === false) {
			$this->_foundMediaInfo = $this->_getMediaInfo($fileLocation);
		}
	}

	/**
	 * Try to get a title from a Linux_2rename.sh file for alt.binaries.u4e group.
	 *
	 * @param $fileLocation
	 */
	protected function _processU4ETitle($fileLocation)
	{
		// Open the file for reading.
		$handle = @fopen($fileLocation, 'r');
		// Check if it failed.
		if ($handle) {
			// Loop over the file line by line.
			while (($buffer = fgets($handle, 16384)) !== false) {
				// Check if we find the word
				if (stripos($buffer, 'mkdir') !== false) {

					// Get a new name.
					$newName = trim(str_replace('mkdir ', '', $buffer));

					// Check if it's a empty string or not.
					if (empty($newName)) {
						continue;
					}

					// Get a new category ID.
					$newCategory = $this->_categorize->determineCategory($newName, $this->_release['group_id']);

					// Update the release with the data.
					$this->pdo->queryExec(
						sprintf('
							UPDATE releases
							SET rageid = -1, seriesfull = NULL, season = NULL, episode = NULL,
								tvtitle = NULL, tvairdate = NULL, imdbid = NULL, musicinfoid = NULL,
								consoleinfoid = NULL, bookinfoid = NULL, anidbid = NULL, preid = 0,
								searchname = %s, isrenamed = 1, iscategorized = 1, proc_files = 1, categoryid = %d
							WHERE id = %d',
							$this->pdo->escapeString(substr($newName, 0, 255)),
							$newCategory,
							$this->_release['id']
						)
					);

					// Echo the changed name to CLI.
					if ($this->_echoCLI) {
						NameFixer::echoChangedReleaseName(array(
								'new_name'     => $newName,
								'old_name'     => $this->_release['searchname'],
								'new_category' => $newCategory,
								'old_category' => $this->_release['categoryid'],
								'group'        => $this->_release['group_id'],
								'release_id'   => $this->_release['id'],
								'method'       => 'ProcessAdditional->_processU4ETitle'
							)
						);
					}

					// Break out of the loop.
					break;
				}
			}
			// Close the file.
			fclose($handle);
		}
		// Delete the file.
		@unlink($fileLocation);
	}

	/**
	 * Convert bytes to KB/MB/GB/TB and return in human readable format.
	 *
	 * @example 240640 would return 235KB
	 *
	 * @param int $bytes
	 *
	 * @return string
	 */
	protected function _readableBytesString($bytes)
	{
		$kb = 1024;
		$mb = 1048576;
		$gb = 1073741824;
		$tb = $kb * $gb;
		if ($bytes < $kb) {
			return $bytes . 'B';
		} else if ($bytes < $mb) {
			return round($bytes / $kb, 1) . 'KB';
		} else if ($bytes < $gb) {
			return round($bytes / $mb, 1) . 'MB';
		} else if ($bytes < $tb) {
			return round($bytes / $gb, 1) . 'GB';
		} else {
			return round($bytes / $tb, 1) . 'TB';
		}
	}

	/**
	 * Comparison function for uSort, for sorting NZB files.
	 *
	 * @param array $a
	 * @param array $b
	 *
	 * @return int
	 */
	protected function _sortNZB($a, $b)
	{
		$pos = 0;
		$af = $bf = false;
		$a = preg_replace('/\d+[- ._]?(\/|\||[o0]f)[- ._]?\d+?(?![- ._]\d)/i', ' ', $a['title']);
		$b = preg_replace('/\d+[- ._]?(\/|\||[o0]f)[- ._]?\d+?(?![- ._]\d)/i', ' ', $b['title']);

		if (preg_match('/\.(part\d+|r\d+)(\s*\.rar)*($|[ ")\]-])/i', $a)) {
			$af = true;
		}
		if (preg_match('/\.(part\d+|r\d+)(\s*\.rar)*($|[ ")\]-])/i', $b)) {
			$bf = true;
		}

		if (!$af && preg_match('/\.rar($|[ ")\]-])/i', $a)) {
			$a = preg_replace('/\.rar(?:$|[ ")\]-])/i', '.*rar', $a);
			$af = true;
		}
		if (!$bf && preg_match('/\.rar($|[ ")\]-])/i', $b)) {
			$b = preg_replace('/\.rar(?:$|[ ")\]-])/i', '.*rar', $b);
			$bf = true;
		}

		if (!$af && !$bf) {
			return strnatcasecmp($a, $b);
		} else if (!$bf) {
			return -1;
		} else if (!$af) {
			return 1;
		}

		if ($af && $bf) {
			return strnatcasecmp($a, $b);
		} else if ($af) {
			return -1;
		} else if ($bf) {
			return 1;
		}

		return $pos;
	}

	/**
	 * Have we created a video file for the current release?
	 * @var bool
	 */
	protected $_foundVideo;

	/**
	 * Have we found MediaInfo data for a Video for the current release?
	 * @var bool
	 */
	protected $_foundMediaInfo;

	/**
	 * Have we found MediaInfo data for a Audio file for the current release?
	 * @var bool
	 */
	protected $_foundAudioInfo;

	/**
	 * Have we created a short Audio file sample for the current release?
	 * @var bool
	 */
	protected $_foundAudioSample;

	/**
	 * Extension of the found audio file (MP3/FLAC/etc).
	 * @var string
	 */
	protected $_AudioInfoExtension;

	/**
	 * Have we downloaded a JPG file for the current release?
	 * @var bool
	 */
	protected $_foundJPGSample;

	/**
	 * Have we created a Video JPG image sample for the current release?
	 * @var bool
	 */
	protected $_foundSample;

	/**
	 * Have we found PAR2 info on this release?
	 * @var bool
	 */
	protected $_foundPAR2Info;

	/**
	 * Message ID's for found content to download.
	 * @var array
	 */
	protected $_sampleMessageIDs;
	protected $_JPGMessageIDs;
	protected $_MediaInfoMessageIDs;
	protected $_AudioInfoMessageIDs;
	protected $_RARFileMessageIDs;

	/**
	 * Password status of the current release.
	 * @var array
	 */
	protected $_passwordStatus;

	/**
	 * Does the current release have a password?
	 * @var bool
	 */
	protected $_releaseHasPassword;

	/**
	 * Does the current release have an NFO file?
	 * @var bool
	 */
	protected $_releaseHasNoNFO;

	/**
	 * Name of the current release's usenet group.
	 * @var string
	 */
	protected $_releaseGroupName;

	/**
	 * Number of file information added to DB (from rar/zip/par2 contents).
	 * @var int
	 */
	protected $_addedFileInfo;

	/**
	 * Number of file information we found from RAR/ZIP.
	 * (if some of it was already in DB, this count goes up, while the count above does not)
	 * @var int
	 */
	protected $_totalFileInfo;

	/**
	 * How many compressed (rar/zip) files have we checked.
	 * @var int
	 */
	protected $_compressedFilesChecked;

	/**
	 * Reset some variables for the current release.
	 */
	protected function _resetReleaseStatus()
	{
		// Only process for samples, previews and images if not disabled.
		$this->_foundVideo       = ($this->_processVideo           ? false : true);
		$this->_foundMediaInfo   = ($this->_processMediaInfo       ? false : true);
		$this->_foundAudioInfo   = ($this->_processAudioInfo       ? false : true);
		$this->_foundAudioSample = ($this->_processAudioSample     ? false : true);
		$this->_foundJPGSample   = ($this->_processJPGSample       ? false : true);
		$this->_foundSample      = ($this->_processSample          ? false : true);
		$this->_foundSample      = (($this->_release['disablepreview'] == 1) ? true  : false);
		$this->_foundPAR2Info    = false;

		$this->_passwordStatus = array(Releases::PASSWD_NONE);
		$this->_releaseHasPassword = false;

		$this->_releaseGroupName = $this->_groups->getByNameByID($this->_release['group_id']);

		$this->_releaseHasNoNFO = false;
		// Make sure we don't already have an nfo.
		if ($this->_release['nfostatus'] != 1) {
			$this->_releaseHasNoNFO = true;
		}

		$this->_NZBHasCompressedFile = false;

		$this->_sampleMessageIDs = $this->_JPGMessageIDs = $this->_MediaInfoMessageIDs = array();
		$this->_AudioInfoMessageIDs = $this->_RARFileMessageIDs = array();
		$this->_AudioInfoExtension = '';

		$this->_addedFileInfo = 0;
		$this->_totalFileInfo = 0;
		$this->_compressedFilesChecked = 0;
	}

	/**
	 * Echo a string to CLI.
	 *
	 * @param string $string
	 * @param bool   $newLine Print a new line at the end of the string.
	 *
	 * @void
	 */
	protected function _echo($string, $newLine = true)
	{
		if ($this->_echoCLI) {
			echo ($string . ($newLine ? PHP_EOL : ''));
		}
	}

	/**
	 * Echo a string to CLI. For debugging.
	 *
	 * @param string $string
	 * @param bool   $newline
	 *
	 * @void
	 */
	protected function _debug($string, $newline = true)
	{
		if ($this->_echoDebug) {
			$this->_echo('DEBUG: ' . $string, $newline);
		}
	}
}
