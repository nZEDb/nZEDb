<?php
namespace nzedb\processing\post;

use app\models\Settings;
use lithium\analysis\Logger;
use nzedb\Categorize;
use nzedb\Category;
use nzedb\db\DB;
use nzedb\Groups;
use nzedb\NameFixer;
use nzedb\Nfo;
use nzedb\NNTP;
use nzedb\NZB;
use nzedb\ReleaseExtra;
use nzedb\ReleaseFiles;
use nzedb\ReleaseImage;
use nzedb\Releases;
use nzedb\SphinxSearch;
use nzedb\utility\Misc;

class ProcessAdditional
{
	/**
	 * How many compressed (rar/zip) files to check.
	 *
	 * @int
	 * @default 20
	 */
	const maxCompressedFilesToCheck = 20;

	/**
	 * @var \nzedb\db\DB
	 */
	public $pdo;

	/**
	 * Number of file information added to DB (from rar/zip/par2 contents).
	 *
	 * @var int
	 */
	protected $addedFileInfo;

	/**
	 * @var bool
	 */
	protected $addPAR2Files;

	/**
	 * @var bool
	 */
	protected $alternateNNTP;

	/**
	 * @var \ArchiveInfo
	 */
	protected $archiveInfo;

	/**
	 * @var string
	 */
	protected $audioFileRegex;

	/**
	 * Extension of the found audio file (MP3/FLAC/etc).
	 *
	 * @var string
	 */
	protected $audioInfoExtension;

	protected $audioInfoMessageIDs;

	/**
	 * @var string
	 */
	protected $audioSavePath;

	/**
	 * @var \nzedb\Categorize
	 */
	protected $categorize;

	/**
	 * How many compressed (rar/zip) files have we checked.
	 *
	 * @var int
	 */
	protected $compressedFilesChecked;

	/**
	 * Current file we are working on inside a NZB.
	 *
	 * @var array
	 */
	protected $currentNZBFile;

	/**
	 * @var bool
	 */
	protected $echoCLI;

	/**
	 * @var bool
	 */
	protected $echoDebug;

	/**
	 * @var bool
	 */
	protected $extractUsingRarInfo;

	/**
	 * Should we download the last rar?
	 *
	 * @var bool
	 */
	protected $fetchLastFiles;

	/**
	 * @var int
	 */
	protected $ffMPEGDuration;

	/**
	 * Have we found MediaInfo data for a Audio file for the current release?
	 *
	 * @var bool
	 */
	protected $foundAudioInfo;

	/**
	 * Have we created a short Audio file sample for the current release?
	 *
	 * @var bool
	 */
	protected $foundAudioSample;

	/**
	 * Have we downloaded a JPG file for the current release?
	 *
	 * @var bool
	 */
	protected $foundJPGSample;

	/**
	 * Have we found MediaInfo data for a Video for the current release?
	 *
	 * @var bool
	 */
	protected $foundMediaInfo;

	/**
	 * Have we found PAR2 info on this release?
	 *
	 * @var bool
	 */
	protected $foundPAR2Info;

	/**
	 * Have we created a Video JPG image sample for the current release?
	 *
	 * @var bool
	 */
	protected $foundSample;

	/**
	 * Have we created a video file for the current release?
	 *
	 * @var bool
	 */
	protected $foundVideo;

	/**
	 * @var \nzedb\Groups
	 */
	protected $groups;

	/**
	 * @var string
	 */
	protected $ignoreBookRegex;

	/**
	 * @var array
	 */
	protected $innerFileBlacklist;

	protected $jpgMessageIDs;

	/**
	 * @var string
	 */
	protected $killString;

	/**
	 * @var string Main temp path to work on.
	 */
	protected $mainTmpPath;

	/**
	 * @var int
	 */
	protected $maxNestedLevels;

	/**
	 * @var string
	 */
	protected $maxSize;

	/**
	 * @var int
	 */
	protected $maximumRarPasswordChecks;

	/**
	 * @var int
	 */
	protected $maximumRarSegments;

	protected $mediaInfoMessageIDs;

	/**
	 * @var string
	 */
	protected $minSize;

	/**
	 * @var \nzedb\NameFixer
	 */
	protected $nameFixer;

	/**
	 * @var \nzedb\Nfo
	 */
	protected $nfo;

	/**
	 * @var \nzedb\NNTP
	 */
	protected $nntp;

	/**
	 * @var \nzedb\NZB
	 */
	protected $nzb;

	/**
	 * List of files with sizes/etc contained in the NZB.
	 *
	 * @var array
	 */
	protected $nzbContents;

	/**
	 * Does the current NZB contain a compressed (RAR/ZIP) file?
	 *
	 * @var bool
	 */
	protected $nzbHasCompressedFile;

	/**
	 * @var \Par2Info
	 */
	protected $par2Info;

	/**
	 * Password status of the current release.
	 *
	 * @var array
	 */
	protected $passwordStatus;

	/**
	 * @var string
	 */
	protected $path7zip;

	/**
	 * @var string
	 */
	protected $pathUnrar;

	/**
	 * @var bool
	 */
	protected $processAudioInfo;

	/**
	 * @var bool
	 */
	protected $processAudioSample;

	/**
	 * @var bool
	 */
	protected $processJPGSample;

	/**
	 * @var bool
	 */
	protected $processMediaInfo;

	/**
	 * @var bool
	 */
	protected $processPasswords;

	/**
	 * @var bool
	 */
	protected $processThumbnails;

	/**
	 * @var bool
	 */
	protected $processVideo;

	/**
	 * @var int
	 */
	protected $queryLimit;

	protected $rarFileMessageIDs;

	/**
	 * Current release we are working on.
	 *
	 * @var array
	 */
	protected $release;

	/**
	 * @var \nzedb\ReleaseExtra
	 */
	protected $releaseExtra;

	/**
	 * @var \nzedb\ReleaseFiles
	 */
	protected $releaseFiles;

	/**
	 * Name of the current release's usenet group.
	 *
	 * @var string
	 */
	protected $releaseGroupName;

	/**
	 * Does the current release have an NFO file?
	 *
	 * @var bool
	 */
	protected $releaseHasNoNFO;

	/**
	 * Does the current release have a password?
	 *
	 * @var bool
	 */
	protected $releaseHasPassword;

	/**
	 * @var \nzedb\ReleaseImage
	 */
	protected $releaseImage;

	/**
	 * Releases to work on.
	 *
	 * @var array
	 */
	protected $releases;

	/**
	 * Are we downloading the last rar?
	 *
	 * @var bool
	 */
	protected $reverse;

	/**
	 * Message ID's for found content to download.
	 *
	 * @var array
	 */
	protected $sampleMessageIDs;

	/**
	 * @var int
	 */
	protected $segmentsToDownload;

	/**
	 * @var bool|string
	 */
	protected $showCLIReleaseID;

	/**
	 * @var string
	 */
	protected $supportFileRegex;

	/**
	 * @var string Temp path for current release.
	 */
	protected $tmpPath;

	/**
	 * Number of file information we found from RAR/ZIP.
	 * (if some of it was already in DB, this count goes up, while the count above does not).
	 *
	 * @var int
	 */
	protected $totalFileInfo;

	/**
	 * Count of releases to work on.
	 *
	 * @var int
	 */
	protected $totalReleases;

	/**
	 * List of message-id's we have tried for rar/zip files.
	 *
	 * @var array
	 */
	protected $triedCompressedMids = [];

	/**
	 * @var string
	 */
	protected $videoFileRegex;

	/**
	 * @param array $options Class instances / echo to cli.
	 */
	public function __construct(array $options = [])
	{
		$defaults = [
			'Echo'         => false,
			'Categorize'   => null,
			'Groups'       => null,
			'NameFixer'    => null,
			'Nfo'          => null,
			'NNTP'         => null,
			'NZB'          => null,
			'ReleaseExtra' => null,
			'ReleaseFiles' => null,
			'ReleaseImage' => null,
			'Settings'     => null,
			'SphinxSearch' => null,
		];
		$options += $defaults;

		$this->echoCLI = ($options['Echo'] && nZEDb_ECHOCLI && (strtolower(PHP_SAPI) === 'cli'));
		$this->echoDebug = nZEDb_DEBUG;

		$this->pdo = ($options['Settings'] instanceof DB ? $options['Settings'] : new DB());
		$this->nntp = ($options['NNTP'] instanceof NNTP ? $options['NNTP'] : new NNTP(['Echo' => $this->echoCLI, 'Settings' => $this->pdo]));

		$this->nzb = ($options['NZB'] instanceof NZB ? $options['NZB'] : new NZB($this->pdo));
		$this->groups = ($options['Groups'] instanceof Groups ? $options['Groups'] : new Groups(['Settings' => $this->pdo]));
		$this->archiveInfo = new \ArchiveInfo();
		$this->releaseFiles = ($options['ReleaseFiles'] instanceof ReleaseFiles ? $options['ReleaseFiles'] : new ReleaseFiles($this->pdo));
		$this->categorize = ($options['Categorize'] instanceof Categorize ? $options['Categorize'] : new Categorize(['Settings' => $this->pdo]));
		$this->nameFixer = ($options['NameFixer'] instanceof NameFixer ? $options['NameFixer'] : new NameFixer(['Echo' =>$this->echoCLI, 'Groups' => $this->groups, 'Settings' => $this->pdo, 'Categorize' => $this->categorize]));
		$this->releaseExtra = ($options['ReleaseExtra'] instanceof ReleaseExtra ? $options['ReleaseExtra'] : new ReleaseExtra($this->pdo));
		$this->releaseImage = ($options['ReleaseImage'] instanceof ReleaseImage ? $options['ReleaseImage'] : new ReleaseImage($this->pdo));
		$this->par2Info = new \Par2Info();
		$this->nfo = ($options['Nfo'] instanceof Nfo ? $options['Nfo'] : new Nfo(['Echo' => $this->echoCLI, 'Settings' => $this->pdo]));
		$this->sphinx = ($options['SphinxSearch'] instanceof SphinxSearch ? $options['SphinxSearch'] : new SphinxSearch());

		$value = Settings::value('indexer.ppa.innerfileblacklist');
		$this->innerFileBlacklist = ($value == '' ? false : $value);

		$value = Settings::value('..maxnestedlevels');
		$this->maxNestedLevels = ($value == 0 ? 3 : $value);
		$this->extractUsingRarInfo = (Settings::value('..extractusingrarinfo') == 0 ? false : true);
		$this->fetchLastFiles = (Settings::value('archive.fetch.end') == 0 ? false : true);

		$this->path7zip = false;
		$this->pathUnrar = false;

		// Pass the binary extractors to ArchiveInfo.
		$clients = [];
		if (Settings::value('apps..unrarpath') != '') {
			$clients += [\ArchiveInfo::TYPE_RAR => Settings::value('apps..unrarpath')];
			$this->pathUnrar = Settings::value('apps..unrarpath');
		}
		if (Settings::value('apps..7zippath') != '') {
			$clients += [\ArchiveInfo::TYPE_ZIP => Settings::value('apps..7zippath')];
			$this->path7zip = Settings::value('apps..7zippath');
		}
		$this->archiveInfo->setExternalClients($clients);

		$this->killString = '"';
		if (Settings::value('apps..timeoutpath') != '' && Settings::value('..timeoutseconds') > 0) {
			$this->killString = (
				'"' . Settings::value('apps..timeoutpath') .
				'" --foreground --signal=KILL ' .
				Settings::value('..timeoutseconds') . ' "'
			);
		}

		$this->showCLIReleaseID = (PHP_BINARY . ' ' . __DIR__ . DS . 'ProcessAdditional.php ReleaseID: ');

		// Maximum amount of releases to fetch per run.
		$value = Settings::value('..maxaddprocessed');
		$this->queryLimit = ($value != '') ? (int)$value : 25;

		// Maximum message ID's to download per file type in the NZB (video, jpg, etc).
		$value = Settings::value('..segmentstodownload');
		$this->segmentsToDownload = ($value != '') ? (int)$value : 2;

		// Maximum message ID's to download for a RAR file.
		$value = Settings::value('..maxpartsprocessed');
		$this->maximumRarSegments = ($value != '') ? (int)$value : 3;

		// Maximum RAR files to check for a password before stopping.
		$value = Settings::value('..passchkattempts');
		$this->maximumRarPasswordChecks = ($value != '') ? (int)$value : 1;

		$this->maximumRarPasswordChecks = ($this->maximumRarPasswordChecks < 1 ? 1 : $this->maximumRarPasswordChecks);

		// Maximum size of releases in GB.
		$value = Settings::value('..maxsizetopostprocess');
		$this->maxSize = ($value !== '') ? (int)$value : 100;
		$this->maxSize = ($this->maxSize > 0 ? ('AND r.size < ' . ($this->maxSize * 1073741824)) : '');
		// Minimum size of releases in MB.
		$value = Settings::value('..minsizetopostprocess');
		$this->minSize = ($value !== '') ? (int)$value : 100;
		$this->minSize = ($this->minSize > 0 ? ('AND r.size > ' . ($this->minSize * 1048576)) : '');

		// Use the alternate NNTP provider for downloading Message-ID's ?
		$this->alternateNNTP = (Settings::value('..alternate_nntp') == 1);

		$value = Settings::value('..ffmpeg_duration');
		$this->ffMPEGDuration = ($value !== '') ? (int)$value : 5;

		$this->addPAR2Files = Settings::value('..addpar2') !== '0';

		if (! Settings::value('apps..ffmpegpath')) {
			$this->processAudioSample = $this->processThumbnails = $this->processVideo = false;
		} else {
			$this->processAudioSample = (Settings::value('..processaudiosample') == 0) ? false : true;
			$this->processThumbnails = (Settings::value('..processthumbnails') == 0 ? false : true);
			$this->processVideo = (Settings::value('..processvideos') == 0) ? false : true;
		}

		$this->processJPGSample = (Settings::value('..processjpg') == 0) ? false : true;
		$this->processMediaInfo = (Settings::value('apps..mediainfopath') == '') ? false : true;
		$this->processAudioInfo = $this->processMediaInfo;

		$value1 = (Settings::value('..checkpasswordedrar') == 0) ? false : true;
		$value2 = (Settings::value('apps..unrarpath') == '') ? false : true;
		$this->processPasswords = ($value1 && $value2);

		$this->audioSavePath = nZEDb_COVERS . 'audiosample' . DS;

		$this->audioFileRegex = '\.(AAC|AIFF|APE|AC3|ASF|DTS|FLAC|MKA|MKS|MP2|MP3|RA|OGG|OGM|W64|WAV|WMA)';
		$this->ignoreBookRegex = '/\b(epub|lit|mobi|pdf|sipdf|html)\b.*\.rar(?!.{20,})/i';
		$this->supportFileRegex = '/\.(vol\d{1,3}\+\d{1,3}|par2|srs|sfv|nzb';
		$this->videoFileRegex = '\.(AVI|F4V|IFO|M1V|M2V|M4V|MKV|MOV|MP4|MPEG|MPG|MPGV|MPV|OGV|QT|RM|RMVB|TS|VOB|WMV)';
	}

	/**
	 * Clear out the main temp path when done.
	 */
	public function __destruct()
	{
		$this->_clearMainTmpPath();
	}

	/**
	 * Main method.
	 *
	 * @param int|string $groupID  (Optional) ID of a group to work on.
	 * @param string     $guidChar (Optional) First char of release GUID, can be used to select work.
	 *
	 * @void
	 */
	public function start($groupID = '', $guidChar = '')
	{
		$this->_setMainTempPath($guidChar, $groupID);

		// Fetch all the releases to work on.
		$this->_fetchReleases($groupID, $guidChar);

		// Check if we have releases to work on.
		if ($this->totalReleases > 0) {
			// Echo start time and process description.
			$this->_echoDescription();

			$this->_processReleases();
		}
	}

	/**
	 * Set up the path to the folder we will work in.
	 *
	 * @param string|int $groupID
	 * @param string     $guidChar
	 *
	 * @throws ProcessAdditionalException
	 */
	protected function _setMainTempPath(&$guidChar, &$groupID = '')
	{
		// Set up the temporary files folder location.
		$this->mainTmpPath = (string)Settings::value('..tmpunrarpath');

		// Check if it ends with a dir separator.
		if (! preg_match('/[\/\\\\]$/', $this->mainTmpPath)) {
			$this->mainTmpPath .= DS;
		}

		// If we are doing per group, use the groupID has a inner path, so other scripts don't delete the files we are working on.
		if ($groupID !== '') {
			$this->mainTmpPath .= ($groupID . DS);
		} elseif ($guidChar !== '') {
			$this->mainTmpPath .= ($guidChar . DS);
		}

		if (! is_dir($this->mainTmpPath)) {
			$old = umask(0777);
			if (! mkdir($this->mainTmpPath, 0777, true) && ! is_dir($this->mainTmpPath)) {
				throw new ProcessAdditionalException('Could not create the tmpunrar folder (' .
					$this->mainTmpPath .
					')');
			}
			@chmod($this->mainTmpPath, 0777);
			@umask($old);
		}

		$this->_clearMainTmpPath();

		$this->tmpPath = $this->mainTmpPath;
	}

	/**
	 * Clear out old folders/files from the main temp folder.
	 */
	protected function _clearMainTmpPath()
	{
		if ($this->mainTmpPath != '') {
			$this->_recursivePathDelete(
				$this->mainTmpPath,
				// These are folders we don't want to delete.
				[
					// This is the actual temp folder.
					$this->mainTmpPath,
				]
			);
		}
	}

	/**
	 * Get all releases that need to be processed.
	 *
	 * @param int|string $groupID
	 * @param string     $guidChar
	 *
	 * @void
	 */
	protected function _fetchReleases($groupID, &$guidChar)
	{
		$this->releases = $this->pdo->query(
			sprintf(
				'
				SELECT r.id, r.id AS releases_id, r.guid, r.name, r.size, r.groups_id, r.nfostatus,
					r.completion, r.categories_id, r.searchname, r.predb_id,
					c.disablepreview
				FROM releases r
				LEFT JOIN categories c ON c.id = r.categories_id
				WHERE r.nzbstatus = 1
				%s %s %s %s
				AND r.passwordstatus BETWEEN -6 AND -1
				AND r.haspreview = -1
				AND c.disablepreview = 0
				ORDER BY r.passwordstatus ASC, r.postdate DESC
				LIMIT %d',
				$this->maxSize,
				$this->minSize,
				($groupID === '' ? '' : 'AND r.groups_id = ' . $groupID),
				($guidChar === '' ? '' : 'AND r.leftguid = ' . $this->pdo->escapeString($guidChar)),
				$this->queryLimit
			)
		);

		if (\is_array($this->releases)) {
			$this->totalReleases = \count($this->releases);
		} else {
			$this->releases = [];
			$this->totalReleases = 0;
		}
	}

	/**
	 * Output the description and start time.
	 *
	 * @void
	 */
	protected function _echoDescription()
	{
		if ($this->totalReleases > 1 && $this->echoCLI) {
			$this->_echo(
				PHP_EOL .
				'Additional post-processing, started at: ' .
				date('D M d, Y G:i a') .
				PHP_EOL .
				'Downloaded: (xB) = yEnc article, f= Failed ;Processing: z = ZIP file, r = RAR file' .
				PHP_EOL .
				'Added: s = Sample image, j = JPEG image, A = Audio sample, a = Audio MediaInfo, v = Video sample' .
				PHP_EOL .
				'Added: m = Video MediaInfo, n = NFO, ^ = File details from inside the RAR/ZIP',
				'header'
			);
		}
	}

	/**
	 * Loop through the releases, processing them 1 at a time.
	 */
	protected function _processReleases()
	{
		foreach ($this->releases as $this->release) {
			$this->_echo(
				PHP_EOL . '[' . $this->release['id'] . '][' .
				$this->_readableBytesString($this->release['size']) . ']',
				'primaryOver',
				false
			);

			cli_set_process_title($this->showCLIReleaseID . $this->release['id']);

			// Create folder to store temporary files.
			if ($this->_createTempFolder() === false) {
				continue;
			}

			// Get NZB contents.
			if ($this->getNZBContents() === false) {
				continue;
			}

			// Reset the current release variables.
			$this->_resetReleaseStatus();

			// Go through the files in the NZB, get the amount of book files.
			$totalBooks = $this->processNZBContents();

			// Check if this NZB is a large collection of books.
			$bookFlood = false;
			if ($totalBooks > 80 && ($totalBooks * 2) >= count($this->nzbContents)) {
				$bookFlood = true;
			}

			if ($this->processPasswords === true ||
				$this->processThumbnails === true ||
				$this->processMediaInfo === true ||
				$this->processAudioInfo === true ||
				$this->processVideo === true
			) {

				// Process usenet Message-ID downloads.
				$this->_processMessageIDDownloads();

				// Process compressed (RAR/ZIP) files inside the NZB.
				if ($bookFlood === false && $this->nzbHasCompressedFile) {
					// Download the RARs/ZIPs, extract the files inside them and insert the file info into the DB.
					$this->processNZBCompressedFiles();

					// Download rar/zip in reverse order, to get the last rar or zip file.
					if ($this->fetchLastFiles == 1) {
						$this->processNZBCompressedFiles(true);
					}

					if ($this->releaseHasPassword === false) {
						// Process the extracted files to get video/audio samples/etc.
						$this->processExtractedFiles();
					}
				}
			}

			// Update the release to say we processed it.
			$this->_finalizeRelease();

			// Delete all files / folders for this release.
			$this->_recursivePathDelete($this->tmpPath);
		}
		if ($this->echoCLI) {
			echo PHP_EOL;
		}
	}

	/**
	 * Deletes files and folders recursively.
	 *
	 * @param string   $path           Path to a folder or file.
	 * @param string[] $ignoredFolders Array with paths to folders to ignore.
	 *
	 * @void
	 * @access protected
	 */
	protected function _recursivePathDelete($path, $ignoredFolders = [])
	{
		if (is_dir($path)) {
			$files = glob(rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*');

			foreach ($files as $file) {
				$this->_recursivePathDelete($file, $ignoredFolders);
			}

			if (in_array($path, $ignoredFolders)) {
				return;
			}

			@rmdir($path);
		} elseif (is_file($path)) {
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
		$this->tmpPath = $this->mainTmpPath . $this->release['guid'] . DS;
		if (! is_dir($this->tmpPath)) {
			$old = umask(0777);
			if (! mkdir($this->tmpPath, 0777, true) && ! is_dir($this->tmpPath)) {
				$this->_echo('Unable to create directory: ' . $this->tmpPath, 'warning');

				return $this->decrementPasswordStatus();
			}
			@chmod($this->tmpPath, 0777);
			@umask($old);
		}

		return true;
	}

	/**
	 * Get list of contents inside a release's NZB file.
	 *
	 * @return bool
	 */
	protected function getNZBContents()
	{
		$status = true;
		$nzbPath = $this->nzb->NZBPath($this->release['guid']);
		if ($nzbPath === false) {
			$this->_echo('NZB not found for GUID: ' . $this->release['guid'], 'warning');
			$status = $this->decrementPasswordStatus();
		}

		$nzbContents = Misc::unzipGzipFile($nzbPath);
		if (! $nzbContents) {
			$this->_echo('NZB is empty or broken for GUID: ' . $this->release['guid'], 'warning');
			$status = $this->decrementPasswordStatus();
		}

		// Get a list of files in the nzb.
		$this->nzbContents = $this->nzb->nzbFileList($nzbContents, ['no-file-key' => false, 'strip-count' => true]);
		if (\count($this->nzbContents) === 0) {
			$this->_echo('NZB is broken (it has no header content) for GUID: ' . $this->release['guid'], 'warning');
			$status = $this->decrementPasswordStatus();
		}
		// Sort keys.
		ksort($this->nzbContents, SORT_NATURAL);

		return $status;
	}

	/**
	 * Decrement password status for the current release.
	 *
	 * @param bool $return Return value.
	 *
	 * @return bool
	 */
	protected function decrementPasswordStatus($return = false) : bool
	{
		$this->pdo->queryExec(
			sprintf(
				'UPDATE releases SET passwordstatus = passwordstatus - 1 WHERE id = %d',
				$this->release['id']
			)
		);

		return $return;
	}

	/**
	 * Process the files inside the NZB, find Message-ID's to download.
	 * If we find files with book extensions, return the amount.
	 *
	 * @return int
	 */
	protected function processNZBContents() : int
	{
		$totalBookFiles = 0;
		foreach ($this->nzbContents as $this->currentNZBFile) {

			// Check if it's not a nfo, nzb, par2 etc...
			if (preg_match($this->supportFileRegex . '|nfo\b|inf\b|ofn\b)($|[ ")\]-])(?!.{20,})/i', $this->currentNZBFile['title'])) {
				continue;
			}

			// Check if it's a rar/zip.
			if ($this->nzbHasCompressedFile === false &&
				preg_match(
					'/\.(part\d+|r\d+|rar|0+|0*10?|zipr\d{2,3}|zipx?)(\s*\.rar)*($|[ ")\]-])|"[a-f0-9]{32}\.[1-9]\d{1,2}".*\(\d+\/\d{2,}\)$/i',
					$this->currentNZBFile['title']
				)
			) {
				$this->nzbHasCompressedFile = true;
			}

			// Look for a video sample, make sure it's not an image.
			if ($this->processThumbnails === true &&
				empty($this->sampleMessageIDs) &&
				preg_match('/sample/i', $this->currentNZBFile['title']) &&
				! preg_match('/\.jpe?g/i', $this->currentNZBFile['title'])
			) {
				if (isset($this->currentNZBFile['segments'])) {
					// Get the amount of segments for this file.
					$segCount = (\count($this->currentNZBFile['segments']) - 1);
					// If it's more than 1 try to get up to the site specified value of segments.
					for ($i = 0; $i < $this->segmentsToDownload; $i++) {
						if ($i > $segCount) {
							break;
						}
						$this->sampleMessageIDs[] = (string)$this->currentNZBFile['segments'][$i];
					}
				}
			}

			// Look for a JPG picture, make sure it's not a CD cover.
			if ($this->processJPGSample === true &&
				empty($this->jpgMessageIDs) &&
				! preg_match('/flac|lossless|mp3|music|inner-sanctum|sound/i', $this->releaseGroupName) &&
				preg_match('/\.jpe?g[. ")\]]/i', $this->currentNZBFile['title'])
			) {
				if (isset($this->currentNZBFile['segments'])) {
					// Get the amount of segments for this file.
					$segCount = (count($this->currentNZBFile['segments']) - 1);
					// If it's more than 1 try to get up to the site specified value of segments.
					for ($i = 0; $i < $this->segmentsToDownload; $i++) {
						if ($i > $segCount) {
							break;
						}
						$this->jpgMessageIDs[] = (string)$this->currentNZBFile['segments'][$i];
					}
				}
			}

			// Look for a video file, make sure it's not a sample, for MediaInfo.
			if ($this->processMediaInfo === true &&
				empty($this->mediaInfoMessageIDs) &&
				! preg_match('/sample/i', $this->currentNZBFile['title']) &&
				preg_match('/' . $this->videoFileRegex . '[. ")\]]/i', $this->currentNZBFile['title'])
			) {
				if (isset($this->currentNZBFile['segments'][0])) {
					$this->mediaInfoMessageIDs = (string)$this->currentNZBFile['segments'][0];
				}
			}

			// Look for a audio file.
			if ($this->processAudioInfo === true &&
				empty($this->audioInfoMessageIDs) &&
				preg_match('/' . $this->audioFileRegex . '[. ")\]]/i', $this->currentNZBFile['title'], $type)
			) {
				if (isset($this->currentNZBFile['segments'])) {
					// Get the extension.
					$this->audioInfoExtension = $type[1];
					$this->audioInfoMessageIDs = (string)$this->currentNZBFile['segments'][0];
				}
			}

			// Some releases contain many books, increment this to ignore them later.
			if (preg_match($this->ignoreBookRegex, $this->currentNZBFile['title'])) {
				$totalBookFiles++;
			}
		}

		return $totalBookFiles;
	}

	/**
	 * Process the NZB contents, find RAR/ZIP files, download them and extract them.
	 *
	 * @param bool $reverse Reverse sort $this->nzbContents ? - To find the largest rar / zip file first.
	 */
	protected function processNZBCompressedFiles($reverse = false)
	{
		$this->reverse = $reverse;

		if ($this->reverse) {
			if (! krsort($this->nzbContents)) {
				return;
			}
		} else {
			$this->triedCompressedMids = [];
		}

		$failed = $downloaded = 0;
		// Loop through the files, attempt to find if password-ed and files. Starting with what not to process.
		foreach ($this->nzbContents as $nzbFile) {
			// TODO change this to max calculated size, as segments vary in size greatly.
			if ($downloaded >= $this->maximumRarSegments) {
				break;
			} elseif ($failed >= $this->maximumRarPasswordChecks) {
				break;
			}

			if ($this->releaseHasPassword === true) {
				$this->_echo('Skipping processing of rar ' . $nzbFile['title'] . ' it has a password.', 'primaryOver', false);

				break;
			}

			// Probably not a rar/zip.
			if (! preg_match(
				'/\.(part\d+|r\d+|rar|0+|0*10?|zipr\d{2,3}|zipx?)(\s*\.rar)*($|[ ")\]-])|"[a-f0-9]{32}\.[1-9]\d{1,2}".*\(\d+\/\d{2,}\)$/i',
				$nzbFile['title']
			)
			) {
				continue;
			}

			// Get message-id's for the rar file.
			$segCount = (\count($nzbFile['segments']) - 1);
			$mID = [];
			for ($i = 0; $i < $this->maximumRarSegments; $i++) {
				if ($i > $segCount) {
					break;
				}
				$segment = (string)$nzbFile['segments'][$i];
				if (! $this->reverse) {
					$this->triedCompressedMids[] = $segment;
				} elseif (\in_array($segment, $this->triedCompressedMids)) {
					// We already downloaded this file.
					continue 2;
				}
				$mID[] = $segment;
			}
			// Nothing to download.
			if (empty($mID)) {
				continue;
			}

			// Download the article(s) from usenet.
			$fetchedBinary = $this->nntp->getMessages($this->releaseGroupName, $mID, $this->alternateNNTP);
			if ($this->nntp->isError($fetchedBinary)) {
				$fetchedBinary = false;
			}

			if ($fetchedBinary !== false) {

				// Echo we downloaded compressed file.
				if ($this->echoCLI) {
					$this->_echo('(cB)', 'primaryOver', false);
				}

				$downloaded++;

				// Process the compressed file.
				$decompressed = $this->processCompressedData($fetchedBinary);

				if ($decompressed === true || $this->releaseHasPassword === true) {
					break;
				}
			} else {
				$failed++;
				if ($this->echoCLI) {
					$this->_echo('f(' . $failed . ')', 'warningOver', false);
				}
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
	protected function processCompressedData(&$compressedData)
	{
		$this->compressedFilesChecked++;
		// Give the data to archive info so it can check if it's a rar.
		if ($this->archiveInfo->setData($compressedData, true) === false) {
			$this->_debug('Data is probably not RAR or ZIP.' . PHP_EOL);

			return false;
		}

		// Check if there's an error.
		if ($this->archiveInfo->error !== '') {
			$this->_debug('ArchiveInfo Error: ' . $this->archiveInfo->error);

			return false;
		}

		// Get a summary of the compressed file.
		$dataSummary = $this->archiveInfo->getSummary(true);

		// Check if the compressed file is encrypted.
		if (! empty($this->archiveInfo->isEncrypted) || (isset($dataSummary['is_encrypted']) && $dataSummary['is_encrypted'] != 0)) {
			$this->_debug('ArchiveInfo: Compressed file has a password.');
			$this->releaseHasPassword = true;
			$this->passwordStatus[] = Releases::PASSWD_RAR;

			return false;
		}

		switch ($dataSummary['main_type']) {
			case \ArchiveInfo::TYPE_RAR:
				if ($this->echoCLI) {
					$this->_echo('r', 'primaryOver', false);
				}

				if ($this->extractUsingRarInfo === false && $this->pathUnrar !== false) {
					$fileName = $this->tmpPath . uniqid() . '.rar';
					file_put_contents($fileName, $compressedData);
					Misc::runCmd(
						$this->killString . $this->pathUnrar .
						'" e -ai -ep -c- -id -inul -kb -or -p- -r -y "' .
						$fileName . '" "' . $this->tmpPath . 'unrar/"'
					);
					unlink($fileName);
				}

				break;
			case \ArchiveInfo::TYPE_ZIP:
				if ($this->echoCLI) {
					$this->_echo('z', 'primaryOver', false);
				}

				if ($this->extractUsingRarInfo === false && $this->path7zip !== false) {
					$fileName = $this->tmpPath . uniqid() . '.zip';
					file_put_contents($fileName, $compressedData);
					Misc::runCmd(
						$this->killString . $this->path7zip . '" x "' .
						$fileName . '" -bd -y -o"' . $this->tmpPath . 'unzip/"'
					);
					unlink($fileName);
				}

				break;
			default:
				return false;
		}

		return $this->processCompressedFileList();
	}

	/**
	 * Get a list of all files in the compressed file, add the file info to the DB.
	 *
	 * @return bool
	 */
	protected function processCompressedFileList()
	{
		// Get a list of files inside the Compressed file.
		$files = $this->archiveInfo->getArchiveFileList();
		if (! is_array($files) || count($files) === 0) {
			return false;
		}

		// Loop through the files.
		foreach ($files as $file) {
			if ($this->releaseHasPassword === true) {
				break;
			}

			if (isset($file['name'])) {
				if (isset($file['error'])) {
					$this->_debug("Error: {$file['error']} (in: {$file['source']})");

					continue;
				}

				if ($file['pass'] == true) {
					$this->releaseHasPassword = true;
					$this->passwordStatus[] = Releases::PASSWD_RAR;

					break;
				}

				if ($this->innerFileBlacklist !== false && preg_match($this->innerFileBlacklist, $file['name'])) {
					$this->releaseHasPassword = true;
					$this->passwordStatus[] = Releases::PASSWD_POTENTIAL;

					break;
				}

				$fileName = [];
				if (preg_match('/[^\/\\\\]*\.[a-zA-Z0-9]*$/', $file['name'], $fileName)) {
					$fileName = $fileName[0];
				} else {
					$fileName = '';
				}

				if ($this->extractUsingRarInfo === true) {
					// Extract files from the rar.
					if (isset($file['compressed']) && $file['compressed'] == 0) {
						@file_put_contents(
							($this->tmpPath . mt_rand(10, 999999) . '_' . $fileName),
							$this->archiveInfo->getFileData($file['name'], $file['source'])
						);
					} // If the files are compressed, use a binary extractor.
					else {
						$this->archiveInfo->extractFile($file['name'], $this->tmpPath . mt_rand(10, 999999) . '_' . $fileName);
					}
				}
			}
			$this->addFileInfo($file);
		}
		if ($this->addedFileInfo > 0) {
			$this->sphinx->updateRelease($this->release['id'], $this->pdo);
		}

		return ($this->totalFileInfo > 0);
	}

	/**
	 * Add info from files within RAR/ZIP/PAR2/etc...
	 *
	 * @param array $file
	 *
	 * @void
	 */
	protected function addFileInfo(&$file)
	{
		// Don't add rar/zip files to the DB.
		if (! isset($file['error']) && isset($file['source']) &&
			! preg_match($this->supportFileRegex . '|part\d+|r\d{1,3}|zipr\d{2,3}|\d{2,3}|zipx|zip|rar)(\s*\.rar)?$/i', $file['name'])
		) {

			// Cache the amount of files we find in the RAR or ZIP, return this to say we did find RAR or ZIP content.
			// This is so we don't download more RAR or ZIP files for no reason.
			$this->totalFileInfo++;

			/* Check if we already have the file or not.
			 * Also make sure we don't add too many files, some releases have 100's of files, like PS3 releases.
			 */
			if ($this->addedFileInfo < 11 &&
				$this->pdo->queryOneRow(
					sprintf(
						'
						SELECT releases_id FROM release_files
						WHERE releases_id = %d
						AND name = %s
						AND size = %d',
						$this->release['id'],
						$this->pdo->escapeString($file['name']),
						$file['size']
					)
				) === false
			) {
				if ($this->releaseFiles->add($this->release['id'], $file['name'], $file['size'], $file['date'], $file['pass'])) {
					$this->addedFileInfo++;

					if ($this->echoCLI) {
						$this->_echo('^', 'primaryOver', false);
					}

					// Check for "codec spam"
					if (preg_match('/alt\.binaries\.movies($|\.divx$)/', $this->releaseGroupName) &&
						preg_match('/[\/\\\\]Codec[\/\\\\]Setup\.exe/i', $file['name'])
					) {
						$this->_debug('Codec spam found, setting release to potentially passworded.' . PHP_EOL);
						$this->releaseHasPassword = true;
						$this->passwordStatus[] = Releases::PASSWD_POTENTIAL;
					} elseif (strpos($file['name'], '.') !== 0 && \strlen($file['name']) > 0) {
						//Run a PreDB filename check on insert to try and match the release
						$this->release['filename'] = $file['name'];
						$this->release['releases_id'] = $this->release['id'];
						$this->nameFixer->matchPredbFiles($this->release, 1, 1, true, 1);
					}
				}
			}
		}
	}

	/**
	 * Go through all the extracted files in the temp folder and process them.
	 */
	protected function processExtractedFiles()
	{
		$nestedLevels = 0;

		// Go through all the files in the temp folder, look for compressed files, extract them and the nested ones.
		while ($nestedLevels < $this->maxNestedLevels) {

			// Break out if we checked more than x compressed files.
			if ($this->compressedFilesChecked >= self::maxCompressedFilesToCheck) {
				break;
			}

			$foundCompressedFile = false;

			// Get all the compressed files in the temp folder.
			$files = $this->_getTempDirectoryContents('/.*\.([rz]\d{2,}|rar|zipx?|0{0,2}1)($|[^a-z0-9])/i');

			if ($files instanceof \Traversable) {
				foreach ($files as $file) {

					// Check if the file exists.
					if (is_file($file[0])) {
						$rarData = @file_get_contents($file[0]);
						if ($rarData !== false) {
							$this->processCompressedData($rarData);
							$foundCompressedFile = true;
						}
						@unlink($file[0]);
					}
				}
			}

			// If we found no compressed files, break out.
			if ($foundCompressedFile === false) {
				break;
			}

			$nestedLevels++;
		}

		$fileType = [];

		// Get all the remaining files in the temp dir.
		$files = $this->_getTempDirectoryContents();
		if ($files instanceof \Traversable) {
			foreach ($files as $file) {
				$file = (string)$file;

				// Skip /. and /..
				if (preg_match('/[\/\\\\]\.{1,2}$/', $file)) {
					continue;
				}

				if (is_file($file)) {

					// Process PAR2 files.
					if ($this->foundPAR2Info === false && preg_match('/\.par2$/', $file)) {
						$this->_siftPAR2Info($file);
					} // Process NFO files.
					elseif ($this->releaseHasNoNFO === true && preg_match('/(\.(nfo|inf|ofn)|info\.txt)$/i', $file)) {
						$this->_processNfoFile($file);
					} // Process audio files.
					elseif (
						($this->foundAudioInfo === false ||
							$this->foundAudioSample === false) &&
						preg_match('/(.*)' . $this->audioFileRegex . '$/i', $file, $fileType)
					) {
						// Try to get audio sample/audio media info.
						@rename($file, $this->tmpPath . 'audiofile.' . $fileType[2]);
						$this->_getAudioInfo($this->tmpPath . 'audiofile.' . $fileType[2], $fileType[2]);
						@unlink($this->tmpPath . 'audiofile.' . $fileType[2]);
					} // Process JPG files.
					elseif ($this->foundJPGSample === false && preg_match('/\.jpe?g$/i', $file)) {
						$this->_getJPGSample($file);
						@unlink($file);
					} // Video sample // video clip // video media info.
					elseif (($this->foundSample === false || $this->foundVideo === false || $this->foundMediaInfo === false) &&
						preg_match('/(.*)' . $this->videoFileRegex . '$/i', $file)
					) {
						$this->_processVideoFile($file);
					} // Check if it's alt.binaries.u4e file.
					elseif (in_array($this->releaseGroupName, ['alt.binaries.u4e', 'alt.binaries.mom']) &&
						preg_match('/Linux_2rename\.sh/i', $file) &&
						($this->release['categories_id'] == Category::OTHER_HASHED || $this->release['categories_id'] == Category::OTHER_MISC)
					) {
						$this->_processU4ETitle($file);
					}

					// Check file's magic info.
					else {
						$output = Misc::fileInfo($file);
						if (! empty($output)) {
							switch (true) {

								case ($this->foundJPGSample === false && preg_match('/^JPE?G/i', $output)):
									$this->_getJPGSample($file);
									@unlink($file);

									break;

								case (
									($this->foundMediaInfo === false || $this->foundSample === false || $this->foundVideo === false)
									&& preg_match('/Matroska data|MPEG v4|MPEG sequence, v2|\WAVI\W/i', $output)
								):
									$this->_processVideoFile($file);

									break;

								case (
									($this->foundAudioSample === false || $this->foundAudioInfo === false) &&
									preg_match('/^FLAC|layer III|Vorbis audio/i', $output, $fileType)
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

								case ($this->foundPAR2Info === false && preg_match('/^Parity/i', $output)):
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
		if ($this->foundSample === false || $this->foundVideo === false) {
			if (! empty($this->sampleMessageIDs)) {

				// Download it from usenet.
				$sampleBinary = $this->nntp->getMessages($this->releaseGroupName, $this->sampleMessageIDs, $this->alternateNNTP);
				if ($this->nntp->isError($sampleBinary)) {
					Logger::debug($sampleBinary, ['name' => 'default']);
					$sampleBinary = false;
				}

				if ($sampleBinary !== false) {
					if ($this->echoCLI) {
						$this->_echo('(sB)', 'primaryOver', false);
					}

					// Check if it's more than 40 bytes.
					if (strlen($sampleBinary) > 40) {
						$fileLocation = $this->tmpPath . 'sample_' . mt_rand(0, 99999) . '.avi';
						// Try to create the file.
						@file_put_contents($fileLocation, $sampleBinary);

						// Try to get a sample picture.
						if ($this->foundSample === false) {
							$this->foundSample = $this->_getSample($fileLocation);
						}

						// Try to get a sample video.
						if ($this->foundVideo === false) {
							$this->foundVideo = $this->_getVideo($fileLocation);
						}

						// Try to get media info. Don't get it here if $mediaMsgID is not empty.
						// 2014-06-28 -> Commented out, since the media info of a sample video is not indicative of the actual release.si
						/*if ($this->foundMediaInfo === false && empty($mediaMsgID)) {
							$this->foundMediaInfo = $this->_getMediaInfo($fileLocation);
						}*/
					}
				} elseif ($this->echoCLI) {
					$this->_echo('f', 'warningOver', false);
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
		if ($this->foundMediaInfo === false || $this->foundSample === false || $this->foundVideo === false) {
			if ($this->foundMediaInfo === false && ! empty($this->mediaInfoMessageIDs)) {

				// Try to download it from usenet.
				$mediaBinary = $this->nntp->getMessages($this->releaseGroupName, $this->mediaInfoMessageIDs, $this->alternateNNTP);
				if ($this->nntp->isError($mediaBinary)) {
					Logger::debug($mediaBinary, ['name' => 'default']);
					// If error set it to false.
					$mediaBinary = false;
				}

				if ($mediaBinary !== false) {
					if ($this->echoCLI) {
						$this->_echo('(mB)', 'primaryOver', false);
					}

					// If it's more than 40 bytes...
					if (strlen($mediaBinary) > 40) {
						$fileLocation = $this->tmpPath . 'media.avi';
						// Create a file on the disk with it.
						@file_put_contents($fileLocation, $mediaBinary);

						// Try to get media info.
						if ($this->foundMediaInfo === false) {
							$this->foundMediaInfo = $this->_getMediaInfo($fileLocation);
						}

						// Try to get a sample picture.
						if ($this->foundSample === false) {
							$this->foundSample = $this->_getSample($fileLocation);
						}

						// Try to get a sample video.
						if ($this->foundVideo === false) {
							$this->foundVideo = $this->_getVideo($fileLocation);
						}
					}
				} elseif ($this->echoCLI) {
					$this->_echo('f', 'warningOver', false);
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
		if (($this->foundAudioInfo === false || $this->foundAudioSample === false)) {
			if (! empty($this->audioInfoMessageIDs)) {
				// Try to download it from usenet.
				$audioBinary = $this->nntp->getMessages($this->releaseGroupName, $this->audioInfoMessageIDs, $this->alternateNNTP);
				if ($this->nntp->isError($audioBinary)) {
					Logger::debug($audioBinary, ['name' => 'default']);
					$audioBinary = false;
				}

				if ($audioBinary !== false) {
					if ($this->echoCLI) {
						$this->_echo('(aB)', 'primaryOver', false);
					}

					$fileLocation = $this->tmpPath . 'audio.' . $this->audioInfoExtension;
					// Create a file with it.
					@file_put_contents($fileLocation, $audioBinary);

					// Try to get media info / sample of the audio file.
					$this->_getAudioInfo($fileLocation, $this->audioInfoExtension);
				} elseif ($this->echoCLI) {
					$this->_echo('f', 'warningOver', false);
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
		if ($this->foundJPGSample === false && ! empty($this->jpgMessageIDs)) {

			// Try to download it.
			$jpgBinary = $this->nntp->getMessages($this->releaseGroupName, $this->jpgMessageIDs, $this->alternateNNTP);
			if ($this->nntp->isError($jpgBinary)) {
				Logger::alert($jpgBinary, ['name' => 'default']);
				$jpgBinary = false;
			}

			if ($jpgBinary !== false) {
				if ($this->echoCLI) {
					$this->_echo('(jB)', 'primaryOver', false);
				}

				// Try to create a file with it.
				@file_put_contents($this->tmpPath . 'samplepicture.jpg', $jpgBinary);

				// Try to resize and move it.
				$this->foundJPGSample = (
				$this->releaseImage->saveImage(
					$this->release['guid'] . '_thumb',
					$this->tmpPath . 'samplepicture.jpg',
					$this->releaseImage->jpgSavePath,
					650,
					650
				) === 1 ? true : false
				);

				if ($this->foundJPGSample !== false) {
					// Update the DB to say we got it.
					$this->pdo->queryExec(
						sprintf(
							'
							UPDATE releases
							SET jpgstatus = %d
							WHERE id = %d',
							1,
							$this->release['id']
						)
					);

					if ($this->echoCLI) {
						$this->_echo('j', 'primaryOver', false);
					}
				}

				@unlink($this->tmpPath . 'samplepicture.jpg');
			} elseif ($this->echoCLI) {
				$this->_echo('f', 'warningOver', false);
			}
		}
	}

	/**
	 * Update the release to say we processed it.
	 */
	protected function _finalizeRelease()
	{
		$vSQL = $jSQL = '';
		$iSQL = ', haspreview = 0';

		// If samples exist from previous runs, set flags.
		if (is_file($this->releaseImage->imgSavePath . $this->release['guid'] . '_thumb.jpg')) {
			$iSQL = ', haspreview = 1';
		}

		if (is_file($this->releaseImage->vidSavePath . $this->release['guid'] . '.ogv')) {
			$vSQL = ', videostatus = 1';
		}

		if (is_file($this->releaseImage->jpgSavePath . $this->release['guid'] . '_thumb.jpg')) {
			$jSQL = ', jpgstatus = 1';
		}

		// Get the amount of files we found inside the RAR/ZIP files.
		$releaseFiles = $this->pdo->queryOneRow(
			sprintf(
				'
				SELECT COUNT(release_files.releases_id) AS count,
				SUM(release_files.size) AS size
				FROM release_files
				WHERE releases_id = %d',
				$this->release['id']
			)
		);

		if ($releaseFiles === false) {
			$releaseFiles['count'] = $releaseFiles['size'] = 0;
		}

		$this->passwordStatus = max($this->passwordStatus);

		// Set the release to no password if password processing is off.
		if ($this->processPasswords === false) {
			$this->releaseHasPassword = false;
		}

		// If we failed to get anything from the RAR/ZIPs, decrement the passwordstatus, if the rar/zip has no password.
		if ($this->releaseHasPassword === false && $this->nzbHasCompressedFile && $releaseFiles['count'] == 0) {
			$query = sprintf(
				'UPDATE releases
				SET passwordstatus = passwordstatus - 1, rarinnerfilecount = %d %s %s %s
				WHERE id = %d',
				$releaseFiles['count'],
				$iSQL,
				$vSQL,
				$jSQL,
				$this->release['id']
			);
		} // Else update the release with the password status (if the admin enabled the setting).
		else {
			$query = sprintf(
				'UPDATE releases
				SET passwordstatus = %d, rarinnerfilecount = %d %s %s %s
				WHERE id = %d',
				($this->processPasswords === true ? $this->passwordStatus : Releases::PASSWD_NONE),
				$releaseFiles['count'],
				$iSQL,
				$vSQL,
				$jSQL,
				$this->release['id']
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
	 * @return \Iterator Object|bool
	 */
	protected function _getTempDirectoryContents($pattern = '', $path = '')
	{
		if ($path === '') {
			$path = $this->tmpPath;
		}

		try {
			if ($pattern !== '') {
				return new \RegexIterator(
					new \RecursiveIteratorIterator(
						new \RecursiveDirectoryIterator($path)
					),
					$pattern,
					\RecursiveRegexIterator::GET_MATCH
				);
			} else {
				return new \RecursiveIteratorIterator(
					new \RecursiveDirectoryIterator($path)
				);
			}
		} catch (\Exception $e) {
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
		if ($this->processAudioSample === false) {
			$audVal = true;
		}

		// Check if media info fetching is on.
		if ($this->processAudioInfo === false) {
			$retVal = true;
		}

		// Make sure the category is music or other.
		$rQuery = $this->pdo->queryOneRow(
			sprintf(
				'SELECT searchname, categories_id AS id, groups_id FROM releases WHERE proc_pp = 0 AND id = %d',
				$this->release['id']
			)
		);

		$musicParent = (string)Category::MUSIC_ROOT;
		if ($rQuery === false || ! preg_match(
				sprintf(
					'/%d\d{3}|%d|%d|%d/',
					$musicParent[0],
					Category::OTHER_MISC,
					Category::MOVIE_OTHER,
					Category::TV_OTHER
				),
				$rQuery['id']
			)
		) {
			return false;
		}

		if (is_file($fileLocation)) {

			// Check if media info is enabled.
			if ($retVal === false) {

				// Get the media info for the file.
				$xmlArray = Misc::runCmd(
					$this->killString . Settings::value('apps..mediainfopath') . '" --Output=XML "' . $fileLocation . '"'
				);
				if (is_array($xmlArray)) {

					// Convert to array.
					$arrXml = Misc::objectsIntoArray(@simplexml_load_string(implode("\n", $xmlArray)));

					if (isset($arrXml['File']['track'])) {
						foreach ($arrXml['File']['track'] as $track) {
							if (isset($track['Album']) && isset($track['Performer'])) {
								if (nZEDb_RENAME_MUSIC_MEDIAINFO && $this->release['predb_id'] == 0) {
									// Make the extension upper case.
									$ext = strtoupper($fileExtension);

									// Form a new search name.
									if (! empty($track['Recorded_date']) && preg_match('/(?:19|20)\d\d/', $track['Recorded_date'], $Year)) {
										$newName = $track['Performer'] . ' - ' . $track['Album'] . ' (' . $Year[0] . ') ' . $ext;
									} else {
										$newName = $track['Performer'] . ' - ' . $track['Album'] . ' ' . $ext;
									}

									// Get the category or try to determine it.
									if ($ext === 'MP3') {
										$newCat = Category::MUSIC_MP3;
									} elseif ($ext === 'FLAC') {
										$newCat = Category::MUSIC_LOSSLESS;
									} else {
										$newCat = $this->categorize->determineCategory($rQuery['groups_id'], $newName);
									}

									$newTitle = $this->pdo->escapeString(substr($newName, 0, 255));
									// Update the search name.
									$this->pdo->queryExec(
										sprintf(
											'
											UPDATE releases
											SET searchname = %s, categories_id = %d, iscategorized = 1, isrenamed = 1, proc_pp = 1
											WHERE id = %d',
											$newTitle,
											$newCat,
											$this->release['id']
										)
									);
									$this->sphinx->updateRelease($this->release['id'], $this->pdo);

									// Echo the changed name.
									if ($this->echoCLI) {
										NameFixer::echoChangedReleaseName(
											[
												'new_name' => $newName,
												'old_name' => $rQuery['searchname'],
												'new_category' => $newCat,
												'old_category' => $rQuery['id'],
												'group' => $rQuery['groups_id'],
												'release_id' => $this->release['id'],
												'method' => 'ProcessAdditional->_getAudioInfo',
											]
										);
									}
								}

								// Add the media info.
								$this->releaseExtra->addFromXml($this->release['id'], $xmlArray);

								$retVal = true;
								$this->foundAudioInfo = true;
								if ($this->echoCLI) {
									$this->_echo('a', 'primaryOver', false);
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
				$audioFileName = ($this->release['guid'] . '.ogg');

				// Create an audio sample.
				Misc::runCmd(
					$this->killString .
					Settings::value('apps..ffmpegpath') .
					'" -t 30 -i "' .
					$fileLocation .
					'" -acodec libvorbis -loglevel quiet -y "' .
					$this->tmpPath . $audioFileName .
					'"'
				);

				// Check if the new file was created.
				if (is_file($this->tmpPath . $audioFileName)) {

					// Try to move the temp audio file.
					$renamed = rename($this->tmpPath . $audioFileName, $this->audioSavePath . $audioFileName);

					if (! $renamed) {
						// Try to copy it if it fails.
						$copied = copy($this->tmpPath . $audioFileName, $this->audioSavePath . $audioFileName);

						// Delete the old file.
						unlink($this->tmpPath . $audioFileName);

						// If it didn't copy continue.
						if (! $copied) {
							return false;
						}
					}

					// Try to set the file perms.
					@chmod($this->audioSavePath . $audioFileName, 0764);

					// Update DB to said we got a audio sample.
					$this->pdo->queryExec(
						sprintf(
							'
							UPDATE releases
							SET audiostatus = 1
							WHERE id = %d',
							$this->release['id']
						)
					);

					$audVal = $this->foundAudioSample = true;

					if ($this->echoCLI) {
						$this->_echo('A', 'primaryOver', false);
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
		$this->foundJPGSample = (
			$this->releaseImage->saveImage(
				$this->release['guid'] . '_thumb',
				$fileLocation,
				$this->releaseImage->jpgSavePath,
				650,
				650
			) === 1 ? true : false
		);

		// If it's successful, tell the DB.
		if ($this->foundJPGSample !== false) {
			$this->pdo->queryExec(
				sprintf(
					'
					UPDATE releases
					SET jpgstatus = %d
					WHERE id = %d',
					1,
					$this->release['id']
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
		if (! $this->processThumbnails) {
			return false;
		}

		if (is_file($fileLocation)) {

			// Create path to temp file.
			$fileName = ($this->tmpPath . 'zzzz' . mt_rand(5, 12) . mt_rand(5, 12) . '.jpg');

			$time = $this->getVideoTime($fileLocation);

			// Create the image.
			Misc::runCmd(
				$this->killString .
				Settings::value('apps..ffmpegpath') .
				'" -i "' .
				$fileLocation .
				'" -ss ' . ($time === '' ? '00:00:03.00' : $time) .
				' -vframes 1 -loglevel quiet -y "' .
				$fileName .
				'"'
			);

			// Check if the file exists.
			if (is_file($fileName)) {

				// Try to resize/move the image.
				$saved = $this->releaseImage->saveImage(
					$this->release['guid'] . '_thumb',
					$fileName,
					$this->releaseImage->imgSavePath,
					800,
					600
				);

				// Delete the temp file we created.
				@unlink($fileName);

				// Check if it saved.
				if ($saved === 1) {
					if ($this->echoCLI) {
						$this->_echo('s', 'primaryOver', false);
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
		if (! $this->processVideo) {
			return false;
		}

		// Try to find an avi file.
		if (is_file($fileLocation)) {

			// Create a filename to store the temp file.
			$fileName = ($this->tmpPath . 'zzzz' . $this->release['guid'] . '.ogv');

			$newMethod = false;
			// If wanted sample length is less than 60, try to get sample from the end of the video.
			if ($this->ffMPEGDuration < 60) {
				// Get the real duration of the file.
				$time = $this->getVideoTime($fileLocation);

				if ($time !== '' && preg_match('/(\d{2}).(\d{2})/', $time, $numbers)) {
					$newMethod = true;

					// Get the lowest time we can start making the video at based on how many seconds the admin wants the video to be.
					if ($numbers[1] <= $this->ffMPEGDuration) {
						// If the clip is shorter than the length we want.

						// The lowest we want is 0.
						$lowestLength = '00:00:00.00';
					} else {
						// If the clip is longer than the length we want.

						// The lowest we want is the the difference between the max video length and our wanted total time.
						$lowestLength = ($numbers[1] - $this->ffMPEGDuration);

						// Form the time string.
						$end = '.' . $numbers[2];
						switch (strlen($lowestLength)) {
							case 1:
								$lowestLength = ('00:00:0' . (string)$lowestLength . $end);

								break;
							case 2:
								$lowestLength = ('00:00:' . (string)$lowestLength . $end);

								break;
							default:
								$lowestLength = '00:00:60.00';
						}
					}

					// Try to get the sample (from the end instead of the start).
					Misc::runCmd(
						$this->killString .
						Settings::value('apps..ffmpegpath') .
						'" -i "' .
						$fileLocation .
						'" -ss ' . $lowestLength .
						' -t ' . $this->ffMPEGDuration .
						' -vcodec libtheora -filter:v scale=320:-1 ' .
						' -acodec libvorbis -loglevel quiet -y "' .
						$fileName .
						'"'
					);
				}
			}

			if ($newMethod === false) {
				// If longer than 60 or we could not get the video length, run the old way.
				Misc::runCmd(
					$this->killString .
					Settings::value('apps..ffmpegpath') .
					'" -i "' .
					$fileLocation .
					'" -vcodec libtheora -filter:v scale=320:-1 -t ' .
					$this->ffMPEGDuration .
					' -acodec libvorbis -loglevel quiet -y "' .
					$fileName .
					'"'
				);
			}

			// Until we find the video file.
			if (is_file($fileName)) {

				// Create a path to where the file should be moved.
				$newFile = ($this->releaseImage->vidSavePath . $this->release['guid'] . '.ogv');

				// Try to move the file to the new path.
				$renamed = @rename($fileName, $newFile);

				// If we couldn't rename it, try to copy it.
				if (! $renamed) {
					$copied = @copy($fileName, $newFile);

					// Delete the old file.
					@unlink($fileName);

					// If it didn't copy, continue.
					if (! $copied) {
						return false;
					}
				}

				// Change the permissions.
				@chmod($newFile, 0764);

				// Update query to say we got the video.
				$this->pdo->queryExec(
					sprintf(
						'
						UPDATE releases
						SET videostatus = 1
						WHERE guid = %s',
						$this->pdo->escapeString($this->release['guid'])
					)
				);
				if ($this->echoCLI) {
					$this->_echo('v', 'primaryOver', false);
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
		if (! $this->processMediaInfo) {
			return false;
		}

		// Look for the video file.
		if (is_file($fileLocation)) {

			// Run media info on it.
			$xmlArray = Misc::runCmd(
				$this->killString . Settings::value('apps..mediainfopath') . '" --Output=XML "' . $fileLocation . '"'
			);

			// Check if we got it.
			if (is_array($xmlArray)) {

				// Convert it to string.
				$xmlArray = implode("\n", $xmlArray);

				if (! preg_match('/<track type="(Audio|Video)">/i', $xmlArray)) {
					return false;
				}

				// Insert it into the DB.
				$this->releaseExtra->addFull($this->release['id'], $xmlArray);
				$this->releaseExtra->addFromXml($this->release['id'], $xmlArray);

				if ($this->echoCLI) {
					$this->_echo('m', 'primaryOver', false);
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
		$this->par2Info->open($fileLocation);

		if ($this->par2Info->error) {
			return;
		}

		$releaseInfo = $this->pdo->queryOneRow(
			sprintf(
				'
				SELECT UNIX_TIMESTAMP(postdate) AS postdate, proc_pp
				FROM releases
				WHERE id = %d',
				$this->release['id']
			)
		);

		if ($releaseInfo === false) {
			return;
		}

		// Only get a new name if the category is OTHER.
		$foundName = true;
		if (nZEDb_RENAME_PAR2 &&
			$releaseInfo['proc_pp'] == 0 &&
			in_array(
				((int)$this->release['categories_id']),
				[
					Category::BOOKS_UNKNOWN,
					Category::GAME_OTHER,
					Category::MOVIE_OTHER,
					Category::MUSIC_OTHER,
					Category::PC_PHONE_OTHER,
					Category::TV_OTHER,
					Category::OTHER_HASHED,
					Category::XXX_OTHER,
					Category::OTHER_MISC,
				]
			)
		) {
			$foundName = false;
		}

		$filesAdded = 0;

		$files = $this->par2Info->getFileList();
		foreach ($files as $file) {
			if (! isset($file['name'])) {
				continue;
			}

			// If we found a name and added 10 files, stop.
			if ($foundName === true && $filesAdded > 10) {
				break;
			}

			// Add to release files.
			if ($this->addPAR2Files) {
				if ($filesAdded < 11 &&
					$this->pdo->queryOneRow(
						sprintf(
							'SELECT releases_id FROM release_files WHERE releases_id = %d AND name = %s',
							$this->release['id'],
							$this->pdo->escapeString($file['name'])
						)
					) === false
				) {

					// Try to add the files to the DB.
					if ($this->releaseFiles->add($this->release['id'], $file['name'], $file['size'], $releaseInfo['postdate'], 0)) {
						$filesAdded++;
					}
				}
			} else {
				$filesAdded++;
			}

			// Try to get a new name.
			if ($foundName === false) {
				$this->release['textstring'] = $file['name'];
				$this->release['releases_id'] = $this->release['id'];
				if ($this->nameFixer->checkName($this->release, ($this->echoCLI ? true : false), 'PAR2, ', 1, 1) === true) {
					$foundName = true;
				}
			}
		}
		// Update the file count with the new file count + old file count.
		$this->pdo->queryExec(
			sprintf(
				'UPDATE releases SET rarinnerfilecount = rarinnerfilecount + %d WHERE id = %d',
				$filesAdded,
				$this->release['id']
			)
		);
		$this->foundPAR2Info = true;
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
			if ($this->nfo->isNFO($data, $this->release['guid']) === true) {
				if ($this->nfo->addAlternateNfo($data, $this->release, $this->nntp) === true) {
					$this->releaseHasNoNFO = false;
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
		if ($this->foundSample === false) {
			$this->foundSample = $this->_getSample($fileLocation);
		}

		/* Try to get a video with it.
		 * Don't get it here if sampleMessageIDs is empty
		 * or has 1 message-id (Saves downloading another part).
		 */
		if ($this->foundVideo === false && count($this->sampleMessageIDs) < 2) {
			$this->foundVideo = $this->_getVideo($fileLocation);
		}

		// Try to get media info with it.
		if ($this->foundMediaInfo === false) {
			$this->foundMediaInfo = $this->_getMediaInfo($fileLocation);
		}
	}

	/**
	 * Try to get a title from a Linux_2rename.sh file for alt.binaries.u4e group.
	 *
	 * @param string $fileLocation
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
					$newCategory = $this->categorize->determineCategory($this->release['groups_id'], $newName);

					$newTitle = $this->pdo->escapeString(substr($newName, 0, 255));
					// Update the release with the data.
					$this->pdo->queryExec(
						sprintf(
							'UPDATE releases
							SET videos_id = 0, tv_episodes_id = 0, imdbid = NULL, musicinfo_id = NULL, consoleinfo_id = NULL,
							bookinfo_id = NULL, anidbid = NULL, searchname = %s, isrenamed = 1, iscategorized = 1,
							proc_files = 1, categories_id = %d
							WHERE id = %d',
							$newTitle,
							$newCategory,
							$this->release['id']
						)
					);
					$this->sphinx->updateRelease($this->release['id'], $this->pdo);

					// Echo the changed name to CLI.
					if ($this->echoCLI) {
						NameFixer::echoChangedReleaseName(
							[
								'new_name' => $newName,
								'old_name' => $this->release['searchname'],
								'new_category' => $newCategory,
								'old_category' => $this->release['categories_id'],
								'group' => $this->release['groups_id'],
								'release_id' => $this->release['id'],
								'method' => 'ProcessAdditional->_processU4ETitle',
							]
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
		} elseif ($bytes < $mb) {
			return round($bytes / $kb, 1) . 'KB';
		} elseif ($bytes < $gb) {
			return round($bytes / $mb, 1) . 'MB';
		} elseif ($bytes < $tb) {
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

		if (! $af && preg_match('/\.rar($|[ ")\]-])/i', $a)) {
			$a = preg_replace('/\.rar(?:$|[ ")\]-])/i', '.*rar', $a);
			$af = true;
		}
		if (! $bf && preg_match('/\.rar($|[ ")\]-])/i', $b)) {
			$b = preg_replace('/\.rar(?:$|[ ")\]-])/i', '.*rar', $b);
			$bf = true;
		}

		if (! $af && ! $bf) {
			return strnatcasecmp($a, $b);
		} elseif (! $bf) {
			return -1;
		} elseif (! $af) {
			return 1;
		}

		if ($af && $bf) {
			return strnatcasecmp($a, $b);
		} elseif ($af) {
			return -1;
		} elseif ($bf) {
			return 1;
		}

		return $pos;
	}

	/**
	 * Reset some variables for the current release.
	 */
	protected function _resetReleaseStatus()
	{
		// Only process for samples, previews and images if not disabled.
		$this->foundVideo = ($this->processVideo ? false : true);
		$this->foundMediaInfo = ($this->processMediaInfo ? false : true);
		$this->foundAudioInfo = ($this->processAudioInfo ? false : true);
		$this->foundAudioSample = ($this->processAudioSample ? false : true);
		$this->foundJPGSample = ($this->processJPGSample ? false : true);
		$this->foundSample = ($this->processThumbnails ? false : true);
		$this->foundSample = (($this->release['disablepreview'] == 1) ? true : false);
		$this->foundPAR2Info = false;

		$this->passwordStatus = [Releases::PASSWD_NONE];
		$this->releaseHasPassword = false;

		$this->releaseGroupName = $this->groups->getNameByID($this->release['groups_id']);

		$this->releaseHasNoNFO = false;
		// Make sure we don't already have an nfo.
		if ($this->release['nfostatus'] != 1) {
			$this->releaseHasNoNFO = true;
		}

		$this->nzbHasCompressedFile = false;

		$this->sampleMessageIDs = $this->jpgMessageIDs = $this->mediaInfoMessageIDs = [];
		$this->audioInfoMessageIDs = $this->rarFileMessageIDs = [];
		$this->audioInfoExtension = '';

		$this->addedFileInfo = 0;
		$this->totalFileInfo = 0;
		$this->compressedFilesChecked = 0;
	}

	/**
	 * Echo a string to CLI.
	 *
	 * @param string $string  String to echo.
	 * @param string $type    Method type.
	 * @param bool   $newLine Print a new line at the end of the string.
	 *
	 * @void
	 */
	protected function _echo($string, $type, $newLine = true)
	{
		if ($this->echoCLI) {
			$this->pdo->log->doEcho($this->pdo->log->$type($string), $newLine);
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
		if ($this->echoDebug) {
			$this->_echo('DEBUG: ' . $string, 'debug', $newline);
		}
	}

	/**
	 * Get accurate time from video segment.
	 *
	 * @param string $videoLocation
	 *
	 * @return string
	 */
	private function getVideoTime($videoLocation)
	{
		// Attempt to get the file extension as ffmpeg fails on some videos with the wrong extension, avconv however is fine.
		if (preg_match('/(\.[a-zA-Z0-9]+)\s*$/', $videoLocation, $extension)) {
			$extension = $extension[1];
		} else {
			$extension = '.avi';
		}

		$tmpVideo = ($this->tmpPath . uniqid() . $extension);
		// Get the real duration of the file.
		$time = Misc::runCmd(
			$this->killString .
			Settings::value('apps..ffmpegpath') .
			'" -i "' . $videoLocation .
			'" -vcodec copy -y 2>&1 "' .
			$tmpVideo . '"',
			false
		);
		@unlink($tmpVideo);

		if (empty($time) || ! preg_match('/time=(\d{1,2}:\d{1,2}:)?(\d{1,2})\.(\d{1,2})\s*bitrate=/i', implode(' ', $time), $numbers)) {
			return '';
		} else {
			// Reduce the last number by 1, this is to make sure we don't ask avconv/ffmpeg for non existing data.
			if ($numbers[3] > 0) {
				$numbers[3] -= 1;
			} elseif ($numbers[1] > 0) {
				$numbers[2] -= 1;
				$numbers[3] = '99';
			}
			// Manually pad the numbers in case they are 1 number. to get 02 for example instead of 2.
			return ('00:00:' . str_pad($numbers[2], 2, '0', STR_PAD_LEFT) . '.' . str_pad($numbers[3], 2, '0', STR_PAD_LEFT));
		}
	}
}

?>
