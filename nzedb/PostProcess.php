<?php

require_once nZEDb_LIB . 'Util.php';
require_once nZEDb_LIBS . 'rarinfo/archiveinfo.php';
require_once nZEDb_LIBS . 'rarinfo/par2info.php';
require_once nZEDb_LIBS . 'rarinfo/zipinfo.php';

class PostProcess
{

	/**
	 * @TODO: Remove ffmpeg_image_time from DB..
	 */

	/**
	 * @var ColorCLI
	 */
	private $c;

	/**
	 * @var DB
	 */
	private $db;
	/**
	 * @var Groups
	 */
	private $groups;

	/**
	 * @var Nfo
	 */
	private $Nfo;

	/**
	 * @var ReleaseFiles
	 */
	private $releaseFiles;

	/**
	 * Object containing site settings.
	 * @var bool|stdClass
	 */
	private $site;

	/**
	 * How many additional to process per run.
	 * @var int
	 */
	private $addqty;

	/**
	 * Have we initiated the objects used for processAdditional?
	 * @var bool
	 */
	private $additionalInitiated;

	/**
	 * Add par2 info to rar list?
	 * @var bool
	 */
	private $addpar2;

	/**
	 * Use alternate NNTP provider when download fails?
	 * @var bool
	 */
	private $alternateNNTP;

	/**
	 * Should we echo to CLI?
	 * @var bool
	 */
	private $echooutput;

	/**
	 * Max file size to post process.
	 * @var int
	 */
	private $maxsize;

	/**
	 * Constructor.
	 *
	 * @param bool $echoOutput Echo to CLI or not?
	 */
	public function __construct($echoOutput = false)
	{
		//\\ Class instances.
		$this->c = new ColorCLI();
		$this->db = new DB();
		$this->groups = new Groups();
		$this->Nfo = new Nfo($echoOutput);
		$this->releaseFiles = new ReleaseFiles();
		$s = new sites();
		//\\

		//\\ Site object.
		$this->site = $s->get();
		//\\

		//\\ Site settings.
		$this->addqty = (!empty($this->site->maxaddprocessed)) ? (int)$this->site->maxaddprocessed : 25;
		$this->addpar2 = ($this->site->addpar2 === '0') ? false : true;
		$this->alternateNNTP = ($this->site->alternate_nntp === '1' ? true : false);
		$this->maxsize = (!empty($this->site->maxsizetopostprocess)) ? (int)$this->site->maxsizetopostprocess : 100;
		//\\

		//\\ Various.
		$this->echooutput = $echoOutput;
		$this->additionalInitiated = false;
		//\\
	}

	/**
	 * Go through every type of post proc.
	 *
	 * @param $nntp
	 *
	 * @return void
	 */
	public function processAll($nntp)
	{
		if (!isset($nntp)) {
			exit($this->c->error("Not connected to usenet(postprocess->processAll).\n"));
		}

		$this->processPredb($nntp);
		$this->processAdditional($releaseToWork = '', $id = '', $gui = false, $groupID = '', $nntp);
		$this->processNfos($releaseToWork = '', $nntp);
		$this->processMovies($releaseToWork = '');
		$this->processMusic();
		$this->processGames();
		$this->processAnime();
		$this->processTv($releaseToWork = '');
		$this->processBooks();
	}

	/**
	 * Lookup anidb if enabled - always run before tvrage.
	 *
	 * @return void
	 */
	public function processAnime()
	{
		if ($this->site->lookupanidb === '1') {
			$anidb = new AniDB($this->echooutput);
			$anidb->animetitlesUpdate();
			$anidb->processAnimeReleases();
		}
	}

	/**
	 * Process books using amazon.com.
	 *
	 * @return void
	 */
	public function processBooks()
	{
		if ($this->site->lookupbooks !== '0') {
			$books = new Books($this->echooutput);
			$books->processBookReleases();
		}
	}

	/**
	 * Lookup games if enabled.
	 *
	 * @return void
	 */
	public function processGames()
	{
		if ($this->site->lookupgames !== '0') {
			$console = new Console($this->echooutput);
			$console->processConsoleReleases();
		}
	}

	/**
	 * Lookup imdb if enabled.
	 *
	 * @param string $releaseToWork
	 *
	 * @return void
	 */
	public function processMovies($releaseToWork = '')
	{
		if ($this->site->lookupimdb === '1') {
			$movie = new Movie($this->echooutput);
			$movie->processMovieReleases($releaseToWork);
		}
	}

	/**
	 * Lookup music if enabled.
	 *
	 * @return void
	 */
	public function processMusic()
	{
		if ($this->site->lookupmusic !== '0') {
			$music = new Music($this->echooutput);
			$music->processMusicReleases();
		}
	}

	/**
	 * Process nfo files.
	 *
	 * @param string $releaseToWork
	 * @param $nntp
	 *
	 * @return void
	 */
	public function processNfos($releaseToWork = '', $nntp)
	{
		if (!isset($nntp)) {
			exit($this->c->error("Not connected to usenet(postprocess->processNfos).\n"));
		}

		if ($this->site->lookupnfo === '1') {
			$this->Nfo->processNfoFiles($releaseToWork, $this->site->lookupimdb, $this->site->lookuptvrage, $groupID = '', $nntp);
		}
	}

	/**
	 * Fetch titles from predb sites.
	 *
	 * @param $nntp
	 *
	 * @return void
	 */
	public function processPredb($nntp)
	{
		if (!isset($nntp)) {
			exit($this->c->error("Not connected to usenet(postprocess->processPredb).\n"));
		}

		$predb = new PreDb($this->echooutput);
		$titles = $predb->updatePre();
		$predb->checkPre($nntp);
		if ($titles > 0) {
			$this->doecho($this->c->header('Fetched ' . number_format($titles) . ' new title(s) from predb sources.'));
		}
	}

	/**
	 * Process all TV related releases which will assign their series/episode/rage data.
	 *
	 * @param string $releaseToWork
	 *
	 * @return void
	 */
	public function processTv($releaseToWork = '')
	{
		if ($this->site->lookuptvrage === '1') {
			$tvrage = new TvRage($this->echooutput);
			$tvrage->processTvReleases($releaseToWork, $this->site->lookuptvrage === '1');
		}
	}

	/**
	 * Attempt to get a better name from a par2 file and categorize the release.
	 *
	 * @note Called from NZBContents.php
	 *
	 * @param $messageID
	 * @param $relID
	 * @param $groupID
	 * @param $nntp
	 * @param $show
	 *
	 * @return bool
	 */
	public function parsePAR2($messageID, $relID, $groupID, $nntp, $show)
	{
		if (!isset($nntp)) {
			exit($this->c->error("Not connected to usenet(postprocess->parsePAR2).\n"));
		}

		if ($messageID === '') {
			return false;
		}

		if ($this->db->dbSystem() === 'mysql') {
			$t = 'UNIX_TIMESTAMP(postdate)';
		} else {
			$t = 'extract(epoch FROM postdate)';
		}

		$quer = $this->db->queryOneRow('SELECT id, groupid, categoryid, searchname, ' . $t . ' as postdate, id as releaseid  FROM releases WHERE isrenamed = 0 AND id = ' . $relID);
		if ($quer['categoryid'] != Category::CAT_MISC) {
			return false;
		}

		$par2 = $nntp->getMessages($this->groups->getByNameByID($groupID), $messageID, $this->alternateNNTP);
		if ($nntp->isError($par2)) {
			return false;
		}

		$par2info = new Par2Info();
		$par2info->setData($par2);
		if ($par2info->error) {
			return false;
		}

		$files = $par2info->getFileList();
		if ($files !== false && count($files) > 0) {
			$namefixer = new NameFixer($this->echooutput);
			$relfiles = 0;
			$foundname = false;
			foreach ($files as $fileID => $file) {

				if (!array_key_exists('name', $file)) {
					return false;
				}

				// Add to releasefiles.
				if ($this->addpar2 && $relfiles < 11 && $this->db->queryOneRow(sprintf('SELECT id FROM releasefiles WHERE releaseid = %d AND name = %s', $relID, $this->db->escapeString($file['name']))) === false) {
					if ($this->releaseFiles->add($relID, $file['name'], $file['size'], $quer['postdate'], 0)) {
						$relfiles++;
					}
				}

				$quer['textstring'] = $file['name'];
				if ($namefixer->checkName($quer, 1, 'PAR2, ', 1, $show) === true) {
					$foundname = true;
					break;
				}
			}
			if ($relfiles > 0) {
				$this->debug('Added ' . $relfiles . ' releasefiles from PAR2 for ' . $quer['searchname']);
				$cnt = $this->db->queryOneRow('SELECT COUNT(releaseid) AS count FROM releasefiles WHERE releaseid = ' . $relID);
				$count = $relfiles;
				if ($cnt !== false && $cnt['count'] > 0) {
					$count = $relfiles + $cnt['count'];
				}
				$this->db->queryExec(sprintf('UPDATE releases SET rarinnerfilecount = %d where id = %d', $count, $relID));
			}
			if ($foundname === true) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * Echo messages if echo is on.
	 *
	 * @param $str
	 */
	protected function doecho($str)
	{
		if ($this->echooutput) {
			echo $this->c->header($str);
		}
	}

	/**
	 * Echo debug messages if debug is on.
	 *
	 * @param $str
	 */
	protected function debug($str)
	{
		if (nZEDb_DEBUG) {
			echo $this->c->debug($str);
		}
	}

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////// Start of ProcessAdditional methods ////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * How long should the video sample be?
	 * @var int
	 */
	private $ffmpeg_duration;

	/**
	 * How many parts to download before giving up.
	 * @var int
	 */
	private $partsqty;

	/**
	 * How many attempts to check for a password.
	 * @var int
	 */
	private $passchkattempts;

	/**
	 * Should we process audio samples?
	 * @var bool
	 */
	private $processAudioSample;

	/**
	 * How many articles to download when getting a JPG.
	 * @var int
	 */
	private $segmentstodownload;

	/**
	 * Path to store audio samples.
	 * @var string
	 */
	private $audSavePath;

	/**
	 * Path to store files temporarily.
	 * @var string
	 */
	private $tmpPath;

	/**
	 * @var ReleaseImage
	 */
	private $releaseImage;

	/**
	 * @var int
	 */
	private $filesadded;

	/**
	 * @var bool
	 */
	private $nonfo;

	/**
	 * @var bool
	 */
	private $password;

	/**
	 * Regex of common audio file extensions.
	 * @var string
	 */
	private $audiofileregex;

	/**
	 * Regex of common book extensions.
	 * @var string
	 */
	private $ignorebookregex;

	/**
	 * Regex of common usenet binary extensions,
	 * @var string
	 */
	private $supportfiles;

	/**
	 * Regex of common video file extensions.
	 * @var string
	 */
	private $videofileregex;

	/**
	 * Sigs regex.
	 * @var string
	 */
	private $sigregex;

	/**
	 * Initiate objects used in processAdditional.
	 *
	 * @return void
	 */
	protected  function initAdditional()
	{
		// Check if the objects are already initiated.
		if ($this->additionalInitiated) {
			return;
		}

		//\\ Class instances.
		$this->releaseExtra = new ReleaseExtra();
		$this->releaseImage = new ReleaseImage();
		//\\

		//\\ Site settings.
		$this->ffmpeg_duration = (!empty($this->site->ffmpeg_duration)) ? (int)$this->site->ffmpeg_duration : 5;
		$this->partsqty = (!empty($this->site->maxpartsprocessed)) ? (int)$this->site->maxpartsprocessed : 3;
		$this->passchkattempts = (!empty($this->site->passchkattempts)) ? (int)$this->site->passchkattempts : 1;
		$this->processAudioSample = ($this->site->processaudiosample === '0') ? false : true;
		$this->segmentstodownload = (!empty($this->site->segmentstodownload)) ? (int)$this->site->segmentstodownload : 2;
		//\\

		//\\ Paths.
		$this->audSavePath = nZEDb_COVERS . 'audiosample' . DS;
		$this->tmpPath = $this->site->tmpunrarpath;
		if (substr($this->tmpPath, -strlen('/')) !== '/') {
			$this->tmpPath = $this->tmpPath . '/';
		}
		//\\

		//\\ Various.
		$this->filesadded = 0;
		$this->nonfo = $this->password = false;
		//\\

		//\\ Regex.
		$this->audiofileregex = '\.(AAC|AIFF|APE|AC3|ASF|DTS|FLAC|MKA|MKS|MP2|MP3|RA|OGG|OGM|W64|WAV|WMA)';
		$this->ignorebookregex = '/\b(epub|lit|mobi|pdf|sipdf|html)\b.*\.rar(?!.{20,})/i';
		$this->supportfiles = '/\.(vol\d{1,3}\+\d{1,3}|par2|srs|sfv|nzb';
		$this->videofileregex = '\.(AVI|F4V|IFO|M1V|M2V|M4V|MKV|MOV|MP4|MPEG|MPG|MPGV|MPV|OGV|QT|RM|RMVB|TS|VOB|WMV)';

		/*// These are pieces of text that can be found inside of a video file.
		$sigs =
			array(
				// .mpg
				array('00', '00', '01', 'BA'),
				array('00', '00', '01', 'B3'),
				array('00', '00', '01', 'B7'),
				array('00', '00', '01', 'B9'),

				//wma
				array('30', '26', 'B2', '75'),
				array('A6', 'D9', '00', 'AA'),

				// mkv
				array('1A', '45', 'DF', 'A3'),

				// ??
				array('01', '00', '09', '00')
			);
		$sigstr = '';
		foreach ($sigs as $sig) {
			$str = '';
			foreach ($sig as $s) {
				$str = $str . "\x$s";
			}
			$sigstr = $sigstr . '|' . $str;
		}
		$sigstr1 = "/^(0&²u|ftyp|oggs|riff)|\.(rec|rmf)|avi|dvd|free|matroska|mdat|moov|mp4|pnot|skip|wide$sigstr/i";
		$this->sigregex = $sigstr1;
		//\\*/

		// Note that we initiated the objects.
		$this->additionalInitiated = true;
	}

	/**
	 * Run processAdditional threaded.
	 *
	 * @param string $releaseToWork
	 * @param $nntp
	 *
	 * @return void
	 */
	public function processAdditionalThreaded($releaseToWork = '', $nntp)
	{
		if (!isset($nntp)) {
			exit($this->c->error("Not connected to usenet(postprocess->processAdditionalThreaded).\n"));
		}

		$this->processAdditional($releaseToWork, $id = '', $gui = false, $groupID = '', $nntp);
	}

	/**
	 * Check for passworded releases, RAR contents and Sample/Media info.
	 *
	 * @note Called externally by tmux/bin/update_per_group and update/postprocess.php
	 *
	 * @param string $releaseToWork
	 * @param string $id
	 * @param bool $gui
	 * @param string $groupID
	 * @param $nntp
	 *
	 * @return void
	 */
	public function processAdditional($releaseToWork = '', $id = '', $gui = false, $groupID = '', $nntp)
	{

		if (!isset($nntp))
			exit($this->c->error("Not connected to usenet(postprocess->processAdditional).\n"));

		$like = 'ILIKE';
		if ($this->db->dbSystem() === 'mysql') {
			$like = 'LIKE';
		}

		// not sure if ugo ever implemented this in the ui, other that his own
		if ($gui) {
			$ok = false;
			while (!$ok) {
				usleep(mt_rand(10, 300));
				$this->db->setAutoCommit(false);
				$ticket = $this->db->queryOneRow('SELECT value  FROM site WHERE setting ' . $like . " 'nextppticket'");
				$ticket = $ticket['value'];
				$upcnt = $this->db->queryExec(sprintf("UPDATE site SET value = %d WHERE setting %s 'nextppticket' AND value = %d", $ticket + 1, $like, $ticket));
				if (count($upcnt) === 1) {
					$ok = true;
					$this->db->Commit();
				} else
					$this->db->Rollback();
			}
			$this->db->setAutoCommit(true);
			$sleep = 1;
			$delay = 100;

			do {
				sleep($sleep);
				$serving = $this->db->queryOneRow('SELECT * FROM site WHERE setting ' . $like . " 'currentppticket1'");
				$time = strtotime($serving['updateddate']);
				$serving = $serving['value'];
				$sleep = min(max(($time + $delay - time()) / 5, 2), 15);
			} while ($serving > $ticket && ($time + $delay + 5 * ($ticket - $serving)) > time());
		}

		$groupid = $groupID == '' ? '' : 'AND groupid = ' . $groupID;
		// Get out all releases which have not been checked more than max attempts for password.
		if ($id != '')
			$result = $this->db->queryDirect('SELECT r.id, r.guid, r.name, c.disablepreview, r.size, r.groupid, r.nfostatus, r.completion, r.categoryid FROM releases r LEFT JOIN category c ON c.id = r.categoryid WHERE r.id = ' . $id);
		else {
			$result = $totresults = 0;
			if ($releaseToWork == '') {
				$i = -1;
				$tries = (5 * -1) - 1;
				while (($totresults !== $this->addqty) && ($i >= $tries)) {
					$result = $this->db->queryDirect(sprintf('SELECT r.id, r.guid, r.name, c.disablepreview, r.size, r.groupid, r.nfostatus, r.completion, r.categoryid FROM releases r LEFT JOIN category c ON c.id = r.categoryid WHERE nzbstatus = 1 AND r.size < %d ' . $groupid . ' AND r.passwordstatus BETWEEN %d AND -1 AND (r.haspreview = -1 AND c.disablepreview = 0) ORDER BY postdate DESC LIMIT %d', $this->maxsize * 1073741824, $i, $this->addqty));
					$totresults = $result->rowCount();
					if ($totresults > 0)
						$this->doecho('Passwordstatus = ' . $i . ': Available to process = ' . $totresults);
					$i--;
				}
			} else {
				$pieces = explode('           =+=            ', $releaseToWork);
				$result = array(array('id' => $pieces[0], 'guid' => $pieces[1], 'name' => $pieces[2], 'disablepreview' => $pieces[3], 'size' => $pieces[4], 'groupid' => $pieces[5], 'nfostatus' => $pieces[6], 'categoryid' => $pieces[7]));
				$totresults = 1;
			}
		}


		$rescount = $startCount = $totresults;
		if ($rescount > 0) {
			$this->initAdditional();


			if ($this->echooutput && $rescount > 1) {
				$this->doecho('Additional post-processing, started at: ' . date('D M d, Y G:i a'));
				$this->doecho('Downloaded: b = yEnc article, f= failed ;Processing: z = zip file, r = rar file');
				$this->doecho('Added: s = sample image, j = jpeg image, A = audio sample, a = audio mediainfo, v = video sample');
				$this->doecho('Added: m = video mediainfo, n = nfo, ^ = file details from inside the rar/zip');
				// Get count of releases per passwordstatus
				$pw1 = $this->db->query('SELECT count(*) as count FROM releases WHERE haspreview = -1 and passwordstatus = -1');
				$pw2 = $this->db->query('SELECT count(*) as count FROM releases WHERE haspreview = -1 and passwordstatus = -2');
				$pw3 = $this->db->query('SELECT count(*) as count FROM releases WHERE haspreview = -1 and passwordstatus = -3');
				$pw4 = $this->db->query('SELECT count(*) as count FROM releases WHERE haspreview = -1 and passwordstatus = -4');
				$pw5 = $this->db->query('SELECT count(*) as count FROM releases WHERE haspreview = -1 and passwordstatus = -5');
				$pw6 = $this->db->query('SELECT count(*) as count FROM releases WHERE haspreview = -1 and passwordstatus = -6');
				$this->doecho('Available to process: -6 = ' . number_format($pw6[0]['count']) . ', -5 = ' . number_format($pw5[0]['count']) . ', -4 = ' . number_format($pw4[0]['count']) . ', -3 = ' . number_format($pw3[0]['count']) . ', -2 = ' . number_format($pw2[0]['count']) . ', -1 = ' . number_format($pw1[0]['count']));
			}

			$nzbcontents = new NZBContents($this->echooutput);
			$nzb = new NZB($this->echooutput);
			$processSample = ($this->site->ffmpegpath !== '') ? true : false;
			$processVideo = ($this->site->processvideos === '0') ? false : true;
			$processMediainfo = ($this->site->mediainfopath !== '') ? true : false;
			$processAudioinfo = ($this->site->mediainfopath !== '') ? true : false;
			$processJPGSample = ($this->site->processjpg === '0') ? false : true;
			$processPasswords = ($this->site->unrarpath !== '') ? true : false;
			$tmpPath = $this->tmpPath;

			// Loop through the releases.
			foreach ($result as $rel) {
				if ($this->echooutput && $releaseToWork == '') {
					echo "\n[" . $this->c->primaryOver($startCount--) . ']';
				} else if ($this->echooutput) {
					echo '[' . $this->c->primaryOver($rel['id']) . ']';
				}

				// Per release defaults.
				$this->tmpPath = $tmpPath . $rel['guid'] . '/';
				if (!is_dir($this->tmpPath)) {
					$old = umask(0777);
					mkdir($this->tmpPath, 0777, true);
					chmod($this->tmpPath, 0777);
					umask($old);

					if (!is_dir($this->tmpPath)) {
						if ($this->echooutput)
							echo $this->c->error("Unable to create directory: {$this->tmpPath}");
						// Decrement passwordstatus.
						$this->db->queryExec('UPDATE releases SET passwordstatus = passwordstatus - 1 WHERE id = ' . $rel['id']);
						continue;
					}
				}

				$nzbpath = $nzb->getNZBPath($rel['guid'], $this->site->nzbpath, false, $this->site->nzbsplitlevel);
				if (!file_exists($nzbpath)) {
					// The nzb was not located. decrement the passwordstatus.
					$this->db->queryExec('UPDATE releases SET passwordstatus = passwordstatus - 1 WHERE id = ' . $rel['id']);
					continue;
				}

				// turn on output buffering
				ob_start();

				// uncompress the nzb
				@readgzfile($nzbpath);

				// read the nzb into memory
				$nzbfile = ob_get_contents();

				// Clean (erase) the output buffer and turn off output buffering
				ob_end_clean();

				// get a list of files in the nzb
				$nzbfiles = $nzb->nzbFileList($nzbfile);
				if (count($nzbfiles) === 0) {
					// There does not appear to be any files in the nzb, decrement passwordstatus
					$this->db->queryExec('UPDATE releases SET passwordstatus = passwordstatus - 1 WHERE id = ' . $rel['id']);
					continue;
				}

				// sort the files
				usort($nzbfiles, 'PostProcess::sortrar');

				// Only process for samples, previews and images if not disabled.
				$blnTookSample = ($rel['disablepreview'] === 1) ? true : false;
				$blnTookMediainfo = $blnTookAudioinfo = $blnTookJPG = $blnTookVideo = false;
				if ($processSample === false) {
					$blnTookSample = true;
				}
				if ($processVideo === false) {
					$blnTookVideo = true;
				}
				if ($processMediainfo === false) {
					$blnTookMediainfo = true;
				}
				if ($processAudioinfo === false) {
					$blnTookAudioinfo = true;
				}
				if ($processJPGSample === false) {
					$blnTookJPG = true;
				}
				$passStatus = array(Releases::PASSWD_NONE);
				$bingroup = $samplegroup = $mediagroup = $jpggroup = $audiogroup = '';
				$samplemsgid = $mediamsgid = $audiomsgid = $jpgmsgid = $audiotype = $mid = $rarpart = array();
				$hasrar = $ignoredbooks = $failed = $this->filesadded = 0;
				$this->password = $this->nonfo = $notmatched = $flood = $foundcontent = false;

				// Make sure we don't already have an nfo.
				if ($rel['nfostatus'] !== '1') {
					$this->nonfo = true;
				}

				$groupName = $this->groups->getByNameByID($rel['groupid']);
				// Go through the nzb for this release looking for a rar, a sample etc...
				foreach ($nzbfiles as $nzbcontents) {
					// Check if it's not a nfo, nzb, par2 etc...
					if (preg_match($this->supportfiles . "|nfo\b|inf\b|ofn\b)($|[ \")\]-])(?!.{20,})/i", $nzbcontents['title']))
						continue;

					// Check if it's a rar/zip.
					if (preg_match("/\.(part0*1|part0+|r0+|r0*1|rar|0+|0*10?|zip)(\.rar)*($|[ \")\]-])|\"[a-f0-9]{32}\.[1-9]\d{1,2}\".*\(\d+\/\d{2,}\)$/i", $nzbcontents['title']))
						$hasrar = 1;
					else if (!$hasrar)
						$notmatched = true;

					// Look for a sample.
					if ($processSample === true && !preg_match('/\.(jpg|jpeg)/i', $nzbcontents['title']) && preg_match('/sample/i', $nzbcontents['title'])) {
						if (isset($nzbcontents['segments']) && empty($samplemsgid)) {
							$samplegroup = $groupName;
							$samplemsgid[] = $nzbcontents['segments'][0];

							for ($i = 1; $i < $this->segmentstodownload; $i++) {
								if (count($nzbcontents['segments']) > $i)
									$samplemsgid[] = $nzbcontents['segments'][$i];
							}
						}
					}

					// Look for a media file.
					if ($processMediainfo === true && !preg_match('/sample/i', $nzbcontents['title']) && preg_match('/' . $this->videofileregex . '[. ")\]]/i', $nzbcontents['title'])) {
						if (isset($nzbcontents['segments']) && empty($mediamsgid)) {
							$mediagroup = $groupName;
							$mediamsgid[] = $nzbcontents['segments'][0];
						}
					}

					// Look for a audio file.
					if ($processAudioinfo === true && preg_match('/' . $this->audiofileregex . '[. ")\]]/i', $nzbcontents['title'], $type)) {
						if (isset($nzbcontents['segments']) && empty($audiomsgid)) {
							$audiogroup = $groupName;
							$audiotype = $type[1];
							$audiomsgid[] = $nzbcontents['segments'][0];
						}
					}

					// Look for a JPG picture.
					if ($processJPGSample === true && !preg_match('/flac|lossless|mp3|music|inner-sanctum|sound/i', $groupName) && preg_match('/\.(jpg|jpeg)[. ")\]]/i', $nzbcontents['title'])) {
						if (isset($nzbcontents['segments']) && empty($jpgmsgid)) {
							$jpggroup = $groupName;
							$jpgmsgid[] = $nzbcontents['segments'][0];
							if (count($nzbcontents['segments']) > 1)
								$jpgmsgid[] = $nzbcontents['segments'][1];
						}
					}
					if (preg_match($this->ignorebookregex, $nzbcontents['title']))
						$ignoredbooks++;
				}

				// Ignore massive book NZB's.
				if (count($nzbfiles) > 40 && $ignoredbooks * 2 >= count($nzbfiles)) {
					$this->debug(' skipping book flood');
					if (isset($rel['categoryid']) && substr($rel['categoryid'], 0, 1) === '8') {
						$this->db->queryExec(sprintf('UPDATE releases SET passwordstatus = 0, haspreview = 0, categoryid = 8050 WHERE id = %d', $rel['id']));
					}
					$flood = true;
				}

				// Seperate the nzb content into the different parts (support files, archive segments and the first parts).
				if ($flood === false && $hasrar !== 0) {
					if ($this->site->checkpasswordedrar > 0 || $processSample === true || $processMediainfo === true || $processAudioinfo === true) {
						$this->sum = $this->size = $this->segsize = $this->adj = $notinfinite = $failed = 0;
						$this->name = '';
						$this->ignorenumbered = $foundcontent = false;

						// Loop through the files, attempt to find if passworded and files. Starting with what not to process.
						foreach ($nzbfiles as $rarFile) {
							if ($this->passchkattempts > 1) {
								if ($notinfinite > $this->passchkattempts) {
									break;
								}
							} else {
								if ($notinfinite > $this->partsqty) {
									if ($this->echooutput) {
										echo $this->c->info("\nMax parts to pp reached");
									}
									break;
								}
							}

							if ($this->password === true) {
								$this->debug('Skipping processing of rar ' . $rarFile['title'] . ' it has a password.');
								break;
							}

							// Probably not a rar/zip.
							if (!preg_match("/\.\b(part\d+|part00\.rar|part01\.rar|rar|r00|r01|zipr\d{2,3}|zip|zipx)($|[ \")\]-])|\"[a-f0-9]{32}\.[1-9]\d{1,2}\".*\(\d+\/\d{2,}\)$/i", $rarFile['title'])) {
								continue;
							}

							// Process rar contents until 1G or 85% of file size is found (smaller of the two).
							if ($rarFile['size'] === 0 && $rarFile['partsactual'] !== 0 && $rarFile['partstotal'] !== 0) {
								$this->segsize = $rarFile['size'] / ($rarFile['partsactual'] / $rarFile['partstotal']);
							} else {
								$this->segsize = 0;
							}
							$this->sum = $this->sum + $this->adj * $this->segsize;
							if ($this->sum > $this->size || $this->adj === 0) {
								$mid = array_slice((array) $rarFile['segments'], 0, $this->segmentstodownload);

								$bingroup = $groupName;
								$fetchedBinary = $nntp->getMessages($bingroup, $mid, $this->alternateNNTP);
								if ($nntp->isError($fetchedBinary)) {
									$fetchedBinary = false;
								}

								if ($fetchedBinary !== false) {
									$this->debug("Processing " . $rarFile['title']);
									if ($this->echooutput) {
										echo 'b';
									}
									$notinfinite++;
									$relFiles = $this->processReleaseFiles($fetchedBinary, $rel, $rarFile['title'], $nntp);
									if ($this->password === true) {
										$passStatus[] = Releases::PASSWD_RAR;
									}

									if ($relFiles === false) {
										$this->debug('Error processing files ' . $rarFile['title']);
										continue;
									} else {
										// Flag to indicate the archive has content.
										$foundcontent = true;
									}
								} else {
									if ($this->echooutput) {
										echo $this->c->alternateOver("f(" . $notinfinite . ")");
									}
									$notinfinite = $notinfinite + 0.2;
									$failed++;
								}
							}
						}
					}

					// Starting to look for content.
					$files = @scandir($this->tmpPath);
					if ($files) {
						foreach ($files as $file) {
							if (is_file($this->tmpPath . $file)) {
								if (substr($file, -4) === '.rar') {
									$archInfo = new ArchiveInfo();
									$archInfo->open($this->tmpPath . $file, true);
									if ($archInfo->error) {
										continue;
									}

									$tmpfiles = $archInfo->getArchiveFileList();
									if (isset($tmpfiles[0]['name'])) {
										foreach ($tmpfiles as $r) {
											if (isset($r['range'])) {
												$range = $r['range'];
											} else {
												$range = mt_rand(0, 99999);
											}

											$r['range'] = $range;
											if (!isset($r['error']) && !preg_match($this->supportfiles . '|part\d+|r\d{1,3}|zipr\d{2,3}|\d{2,3}|zipx|zip|rar)(\.rar)?$/i', $r['name'])) {
												$this->addfile($r, $rel, $archInfo, $nntp);
											}
										}
									}
								}
							}
						}
					}
				}
				/* Not a good indicator of if there is a password or not, the rar could have had an error for example.
				  else if ($hasrar == 1)
				  $passStatus[] = Releases::PASSWD_POTENTIAL;

				  if(!$foundcontent && $hasrar == 1)
				  $passStatus[] = Releases::PASSWD_POTENTIAL; */

				// Try to get image/mediainfo/audioinfo, using extracted files before downloading more data
				if ($blnTookSample === false || $blnTookAudioinfo === false || $blnTookMediainfo === false || $blnTookJPG === false || $blnTookVideo === false) {
					$files = @scandir($this->tmpPath);
					if ($files) {
						foreach ($files as $file) {
							if (is_file($this->tmpPath . $file)) {
								$name = '';

								// Audio sample.
								if ($processAudioinfo === true && $blnTookAudioinfo === false && preg_match('/(.*)' . $this->audiofileregex . '$/i', $file, $name)) {
									@rename($this->tmpPath . $name[0], $this->tmpPath . 'audiofile.' . $name[2]);
									$blnTookAudioinfo = $this->getAudioinfo($rel['guid'], $rel['id'], $name[2]);
								}

								// JGP file sample.
								if ($processJPGSample === true && $blnTookJPG === false && preg_match('/\.(jpg|jpeg)$/', $file)) {
									$blnTookJPG = $this->releaseImage->saveImage($rel['guid'] . '_thumb', $this->tmpPath . $file, $this->releaseImage->jpgSavePath, 650, 650);
									if ($blnTookJPG !== false) {
										$this->db->queryExec(sprintf('UPDATE releases SET jpgstatus = %d WHERE id = %d', 1, $rel['id']));
									}
								}

								// Video sample // video clip // video mediainfo.
								if ($processSample === true || $processVideo === true || $processMediainfo === true) {
									if (preg_match('/(.*)' . $this->videofileregex . '$/i', $file, $name)) {
										rename($this->tmpPath . $name[0], $this->tmpPath . 'sample.avi');
										if ($processSample && $blnTookSample === false) {
											$blnTookSample = $this->getSample($rel['guid']);
										}
										if ($processVideo && $blnTookVideo === false) {
											$blnTookVideo = $this->getVideo($rel['guid']);
										}
										if ($processMediainfo && $blnTookMediainfo === false) {
											$blnTookMediainfo = $this->getMediainfo($rel['id']);
										}
										@unlink($this->tmpPath . 'sample.avi');
									}
								}
								if ($blnTookJPG === true && $blnTookAudioinfo === true && $blnTookMediainfo === true && $blnTookVideo === true && $blnTookSample === true) {
									break;
								}
							}
						}
						unset($files);
					}
				}

				// Download and process sample image.
				if ($processSample === true || $processVideo === true) {
					if ($blnTookSample === false || $blnTookVideo === false) {
						if (!empty($samplemsgid)) {
							$sampleBinary = $nntp->getMessages($samplegroup, $samplemsgid, $this->alternateNNTP);
							if ($nntp->isError($sampleBinary)) {
								$sampleBinary = false;
							}

							if ($sampleBinary !== false) {
								if ($this->echooutput) {
									echo 'b';
								}
								if (strlen($sampleBinary) > 100) {
									$this->addmediafile($this->tmpPath . 'sample_' . mt_rand(0, 99999) . '.avi', $sampleBinary);
									if ($processSample === true && $blnTookSample === false) {
										$blnTookSample = $this->getSample($rel['guid']);
									}
									if ($processVideo === true && $blnTookVideo === false) {
										$blnTookVideo = $this->getVideo($rel['guid']);
									}
									if ($processMediainfo === true && $blnTookMediainfo === false) {
										$blnTookMediainfo = $this->getMediainfo($rel['id']);
									}
								}
								unset($sampleBinary);
							}
							else {
								if ($this->echooutput) {
									echo 'f';
								}
							}
						}
					}
				}

				// Download and process mediainfo. Also try to get a sample if we didn't get one yet.
				if ($processMediainfo === true || $processSample === true || $processVideo === true) {
					if ($blnTookMediainfo === false || $blnTookSample === false || $blnTookVideo === false) {
						if (!empty($mediamsgid)) {
							$mediaBinary = $nntp->getMessages($mediagroup, $mediamsgid, $this->alternateNNTP);
							if ($nntp->isError($mediaBinary)) {
								$mediaBinary = false;
							}
							if ($mediaBinary !== false) {
								if ($this->echooutput) {
									echo 'b';
								}
								if (strlen($mediaBinary) > 100) {
									$this->addmediafile($this->tmpPath . 'media.avi', $mediaBinary);
									if ($processMediainfo === true && $blnTookMediainfo === false) {
										$blnTookMediainfo = $this->getMediainfo($rel['id']);
									}
									if ($processSample === true && $blnTookSample === false) {
										$blnTookSample = $this->getSample($rel['guid']);
									}
									if ($processVideo === true && $blnTookVideo === false) {
										$blnTookVideo = $this->getVideo($rel['guid']);
									}
								}
								unset($mediaBinary);
							}
							else {
								if ($this->echooutput)
									echo 'f';
							}
						}
					}
				}

				// Download audio file, use mediainfo to try to get the artist / album.
				if ($processAudioinfo === true && !empty($audiomsgid) && $blnTookAudioinfo === false) {
					$audioBinary = $nntp->getMessages($audiogroup, $audiomsgid, $this->alternateNNTP);
					if ($nntp->isError($audioBinary)) {
						$audioBinary = false;
					}
					if ($audioBinary !== false) {
						if ($this->echooutput) {
							echo 'b';
						}
						if (strlen($audioBinary) > 100) {
							$this->addmediafile($this->tmpPath . 'audio.' . $audiotype, $audioBinary);
							$blnTookAudioinfo = $this->getAudioinfo($rel['guid'], $rel['id'], $audiotype);
						}
						unset($audioBinary);
					} else {
						if ($this->echooutput) {
							echo 'f';
						}
					}
				}

				// Download JPG file.
				if ($processJPGSample === true && !empty($jpgmsgid) && $blnTookJPG === false) {
					$jpgBinary = $nntp->getMessages($jpggroup, $jpgmsgid, $this->alternateNNTP);
					if ($nntp->isError($jpgBinary)) {
						$jpgBinary = false;
					}
					if ($jpgBinary !== false) {
						if ($this->echooutput) {
							echo 'b';
						}
						$this->addmediafile($this->tmpPath . 'samplepicture.jpg', $jpgBinary);
						if (is_file($this->tmpPath . 'samplepicture.jpg')) {
							if ($blnTookJPG === false) {
								$blnTookJPG = $this->releaseImage->saveImage($rel['guid'] . '_thumb', $this->tmpPath . 'samplepicture.jpg', $this->releaseImage->jpgSavePath, 650, 650);
								if ($blnTookJPG !== false)
									$this->db->queryExec(sprintf('UPDATE releases SET jpgstatus = %d WHERE id = %d', 1, $rel['id']));
							}

							foreach (glob($this->tmpPath . 'samplepicture.jpg') as $v) {
								@unlink($v);
							}
						}
						unset($jpgBinary);
					} else {
						if ($this->echooutput) {
							echo 'f';
						}
					}
				}

				// Set up release values.
				$hpsql = $isql = $vsql = $jsql = '';
				if ($processSample === true && $blnTookSample !== false) {
					$this->db->queryExec(sprintf('UPDATE releases SET haspreview = 1 WHERE guid = %s', $this->db->escapeString($rel['guid'])));
				} else {
					$hpsql = ', haspreview = 0';
				}

				if ($failed > 0) {
					if ($failed / count($nzbfiles) > 0.7 || $notinfinite > $this->passchkattempts || $notinfinite > $this->partsqty) {
						$passStatus[] = Releases::BAD_FILE;
					}
				}

				// If samples exist from previous runs, set flags.
				if (file_exists($this->releaseImage->imgSavePath . $rel['guid'] . '_thumb.jpg')) {
					$isql = ', haspreview = 1';
				}
				if (file_exists($this->releaseImage->vidSavePath . $rel['guid'] . '.ogv')) {
					$vsql = ', videostatus = 1';
				}
				if (file_exists($this->releaseImage->jpgSavePath . $rel['guid'] . '_thumb.jpg')) {
					$jsql = ', jpgstatus = 1';
				}

				$size = $this->db->queryOneRow('SELECT COUNT(releasefiles.releaseid) AS count, SUM(releasefiles.size) AS size FROM releasefiles WHERE releaseid = ' . $rel['id']);

				if (max($passStatus) > 0) {
					$sql = sprintf('UPDATE releases SET passwordstatus = %d, rarinnerfilecount = %d %s %s %s %s WHERE id = %d', max($passStatus), $size['count'], $isql, $vsql, $jsql, $hpsql, $rel['id']);
				} else if ($hasrar && ((isset($size['size']) && (is_null($size['size']) || $size['size'] === '0')) || !isset($size['size']))) {
					if (!$blnTookSample) {
						$hpsql = '';
					}
					$sql = sprintf('UPDATE releases SET passwordstatus = passwordstatus - 1, rarinnerfilecount = %d %s %s %s %s WHERE id = %d', $size['count'], $isql, $vsql, $jsql, $hpsql, $rel['id']);
				} else {
					$sql = sprintf('UPDATE releases SET passwordstatus = %s, rarinnerfilecount = %d %s %s %s %s WHERE id = %d', Releases::PASSWD_NONE, $size['count'], $isql, $vsql, $jsql, $hpsql, $rel['id']);
				}

				$this->db->queryExec($sql);

				// Erase all files and directory.
				foreach (glob($this->tmpPath . '*') as $v) {
					@unlink($v);
				}
				foreach (glob($this->tmpPath . '.*') as $v) {
					@unlink($v);
				}
				@rmdir($this->tmpPath);
			}
			if ($this->echooutput) {
				echo "\n";
			}
		}
		if ($gui) {
			$this->db->queryExec(sprintf("UPDATE site SET value = %d WHERE setting %s 'currentppticket1'", $ticket + 1, $like));
		}

		unset($rar, $nzbcontents);
	}

	/**
	 * Comparison function for usort, for sorting nzb file content.
	 *
	 * @note used in processAdditional
	 *
	 * @param $a
	 * @param $b
	 *
	 * @return int
	 */
	protected function sortrar($a, $b)
	{
		$pos = 0;
		$af = $bf = false;
		$a = preg_replace('/\d+[- ._]?(\/|\||[o0]f)[- ._]?\d+?(?![- ._]\d)/i', ' ', $a['title']);
		$b = preg_replace('/\d+[- ._]?(\/|\||[o0]f)[- ._]?\d+?(?![- ._]\d)/i', ' ', $b['title']);

		if (preg_match("/\.(part\d+|r\d+)(\.rar)*($|[ \")\]-])/i", $a))
			$af = true;
		if (preg_match("/\.(part\d+|r\d+)(\.rar)*($|[ \")\]-])/i", $b))
			$bf = true;

		if (!$af && preg_match("/\.(rar)($|[ \")\]-])/i", $a)) {
			$a = preg_replace('/\.(rar)(?:$|[ \")\]-])/i', '.*rar', $a);
			$af = true;
		}
		if (!$bf && preg_match("/\.(rar)($|[ \")\]-])/i", $b)) {
			$b = preg_replace('/\.(rar)(?:$|[ \")\]-])/i', '.*rar', $b);
			$bf = true;
		}

		if (!$af && !$bf)
			return strnatcasecmp($a, $b);
		else if (!$bf)
			return -1;
		else if (!$af)
			return 1;

		if ($af && $bf)
			$pos = strnatcasecmp($a, $b);
		else if ($af)
			$pos = -1;
		else if ($bf)
			$pos = 1;

		return $pos;
	}

	/**
	 * @note Called by addfile, getRar, processAdditional
	 *
	 * @param $file
	 * @param $data
	 *
	 * @return void
	 */
	protected function addmediafile($file, $data)
	{
		if (@file_put_contents($file, $data) !== false) {
			$xmlarray = @runCmd('"' . $this->site->mediainfopath . '" --Output=XML "' . $file . '"');
			if (is_array($xmlarray)) {
				$xmlarray = implode("\n", $xmlarray);
				$xmlObj = @simplexml_load_string($xmlarray);
				$arrXml = objectsIntoArray($xmlObj);
				if (!isset($arrXml['File']['track'][0])) {
					@unlink($file);
				}
			}
		}
	}

	/**
	 * @note Called by processAdditional, processReleaseFiles, processReleaseZips.
	 *
	 * @param $v
	 * @param $release
	 * @param bool $rar
	 * @param $nntp
	 *
	 * @return void
	 */
	protected function addfile($v, $release, $rar = false, $nntp)
	{
		if (!isset($nntp)) {
			exit($this->c->error("Not connected to usenet(postprocess->addfile).\n"));
		}

		if (!isset($v['error']) && isset($v['source'])) {
			if ($rar !== false && preg_match('/\.zip$/', $v['source'])) {
				$zip = new ZipInfo();
				$tmpdata = $zip->getFileData($v['name'], $v['source']);
			} else if ($rar !== false) {
				$tmpdata = $rar->getFileData($v['name'], $v['source']);
			} else {
				$tmpdata = false;
			}

			// Check if we already have the file or not.
			// Also make sure we don't add too many files, some releases have 100's of files, like PS3 releases.
			if ($this->filesadded < 11 && $this->db->queryOneRow(sprintf('SELECT id FROM releasefiles WHERE releaseid = %d AND name = %s AND size = %d', $release['id'], $this->db->escapeString($v['name']), $v['size'])) === false) {
				if ($this->releaseFiles->add($release['id'], $v['name'], $v['size'], $v['date'], $v['pass'])) {
					$this->filesadded++;
					$this->newfiles = true;
					if ($this->echooutput) {
						echo '^';
					}
				}
			}

			if ($tmpdata !== false) {
				// Extract a NFO from the rar.
				if ($this->nonfo === true && $v['size'] > 100 && $v['size'] < 100000 && preg_match('/(\.(nfo|inf|ofn)|info.txt)$/i', $v['name'])) {
					if ($this->Nfo->addAlternateNfo($this->db, $tmpdata, $release, $nntp)) {
						$this->debug('added rar nfo');
						if ($this->echooutput)
							echo 'n';
						$this->nonfo = false;
					}
				}
				// Extract a video file from the compressed file.
				else if ($this->site->mediainfopath !== '' && $this->site->processvideos === '1' && preg_match('/' . $this->videofileregex . '$/i', $v['name']))
					$this->addmediafile($this->tmpPath . 'sample_' . mt_rand(0, 99999) . '.avi', $tmpdata);
				// Extract an audio file from the compressed file.
				else if ($this->site->mediainfopath !== '' && preg_match('/' . $this->audiofileregex . '$/i', $v['name'], $ext))
					$this->addmediafile($this->tmpPath . 'audio_' . mt_rand(0, 99999) . $ext[0], $tmpdata);
				else if ($this->site->mediainfopath !== '' && preg_match('/([^\/\\\r]+)(\.[a-z][a-z0-9]{2,3})$/i', $v['name'], $name))
					$this->addmediafile($this->tmpPath . $name[1] . mt_rand(0, 99999) . $name[2], $tmpdata);
			}
			unset($tmpdata, $rf);
		}
	}

	/**
	 * Open the zip, see if it has a password, attempt to get a file.
	 *
	 * @note Called by processReleaseFiles
	 *
	 * @param $fetchedBinary
	 * @param bool $open
	 * @param bool $data
	 * @param $release
	 * @param $nntp
	 *
	 * @return array|bool
	 */
	protected function processReleaseZips($fetchedBinary, $open = false, $data = false, $release, $nntp)
	{
		if (!isset($nntp))
			exit($this->c->error("Not connected to usenet(postprocess->processReleaseZips).\n"));

		// Load the ZIP file or data.
		$zip = new ZipInfo();
		if ($open)
			$zip->open($fetchedBinary, true);
		else
			$zip->setData($fetchedBinary, true);

		if ($zip->error) {
			$this->debug('Error: ' . $zip->error);
			return false;
		}

		if (!empty($zip->isEncrypted)) {
			$this->debug('ZIP archive is password encrypted.');
			$this->password = true;
			return false;
		}

		$files = $zip->getFileList();
		$dataarray = array();
		if ($files !== false) {
			if ($this->echooutput) {
				echo 'z';
			}
			foreach ($files as $file) {
				$thisdata = $zip->getFileData($file['name']);
				$dataarray[] = array('zip' => $file, 'data' => $thisdata);

				//Extract a NFO from the zip.
				if ($this->nonfo === true && $file['size'] < 100000 && preg_match('/\.(nfo|inf|ofn)$/i', $file['name'])) {
					if ($file['compressed'] !== 1) {
						if ($this->Nfo->addAlternateNfo($this->db, $thisdata, $release, $nntp)) {
							$this->debug('Added zip NFO.');
							if ($this->echooutput)
								echo 'n';
							$this->nonfo = false;
						}
					}
					else if ($this->site->zippath !== '' && $file['compressed'] === 1) {
						$zip->setExternalClient($this->site->zippath);
						$zipdata = $zip->extractFile($file['name']);
						if ($zipdata !== false && strlen($zipdata) > 5)
							; {
							if ($this->Nfo->addAlternateNfo($this->db, $zipdata, $release, $nntp)) {
								$this->debug('Added compressed zip NFO.');
								if ($this->echooutput)
									echo 'n';
								$this->nonfo = false;
							}
						}
					}
				}
				// Process RARs inside the ZIP.
				else if (preg_match('/\.(r\d+|part\d+|rar)$/i', $file['name'])) {
					$tmpfiles = $this->getRar($thisdata);
					if ($tmpfiles !== false) {
						$limit = 0;
						foreach ($tmpfiles as $f) {
							if ($limit++ > 11)
								break;
							$ret = $this->addfile($f, $release, $rar = false, $nntp);
							$files[] = $f;
						}
					}
				}
			}
		}

		if ($data) {
			$files = $dataarray;
			unset($dataarray);
		}

		unset($fetchedBinary, $zip);
		return $files;
	}

	/**
	 * Get contents of rar file.
	 *
	 * @note Called by processReleaseFiles and processReleaseZips
	 *
	 * @param $fetchedBinary
	 *
	 * @return array|bool
	 */
	protected function getRar($fetchedBinary)
	{
		$rar = new ArchiveInfo();
		$files = $retval = false;
		if ($rar->setData($fetchedBinary, true))
			$files = $rar->getArchiveFileList();
		if ($rar->error) {
			$this->debug('Error: ' . $rar->error);
			return $retval;
		}
		if (!empty($rar->isEncrypted)) {
			$this->debug('Archive is password encrypted.');
			$this->password = true;
			return $retval;
		}
		$tmp = $rar->getSummary(true, false);

		if (isset($tmp['is_encrypted']) && $tmp['is_encrypted'] != 0) {
			$this->debug('Archive is password encrypted.');
			$this->password = true;
			return $retval;
		}
		$files = $rar->getArchiveFileList();
		if ($files !== false) {
			$retval = array();
			if ($this->echooutput !== false)
				echo 'r';
			foreach ($files as $file) {
				if (isset($file['name'])) {
					if (isset($file['error'])) {
						$this->debug("Error: {$file['error']} (in: {$file['source']})");
						continue;
					}
					if (isset($file['pass']) && $file['pass'] == true) {
						$this->password = true;
						break;
					}
					if (preg_match($this->supportfiles . ')(?!.{20,})/i', $file['name']))
						continue;
					if (preg_match('/([^\/\\\\]+)(\.[a-z][a-z0-9]{2,3})$/i', $file['name'], $name)) {
						$rarfile = $this->tmpPath . $name[1] . mt_rand(0, 99999) . $name[2];
						$fetchedBinary = $rar->getFileData($file['name'], $file['source']);
						if ($this->site->mediainfopath !== '')
							$this->addmediafile($rarfile, $fetchedBinary);
					}
					if (!preg_match('/\.(r\d+|part\d+)$/i', $file['name']))
						$retval[] = $file;
				}
			}
		}

		if (count($retval) === 0)
			return false;
		return $retval;
	}

	/**
	 * Open the rar, see if it has a password, attempt to get a file.
	 *
	 * @note Only called by processAddtional
	 *
	 * @param $fetchedBinary
	 * @param $release
	 * @param $name
	 * @param $nntp
	 *
	 * @return array|bool
	 */
	protected function processReleaseFiles($fetchedBinary, $release, $name, $nntp)
	{
		if (!isset($nntp))
			exit($this->c->error("Not connected to usenet(postprocess->processReleaseFiles).\n"));

		$retval = array();
		$rar = new ArchiveInfo();
		$this->password = false;

		if (preg_match("/\.(part\d+|rar|r\d{1,3})($|[ \")\]-])|\"[a-f0-9]{32}\.[1-9]\d{1,2}\".*\(\d+\/\d{2,}\)$/i", $name)) {
			$rar->setData($fetchedBinary, true);
			if ($rar->error) {
				$this->debug("\nError: {$rar->error}.");
				return false;
			}

			$tmp = $rar->getSummary(true, false);
			if (preg_match('/par2/i', $tmp['main_info']))
				return false;

			if (isset($tmp['is_encrypted']) && $tmp['is_encrypted'] != 0) {
				$this->debug('Archive is password encrypted.');
				$this->password = true;
				return false;
			}

			if (!empty($rar->isEncrypted)) {
				$this->debug('Archive is password encrypted.');
				$this->password = true;
				return false;
			}

			$files = $rar->getArchiveFileList();
			if (count($files) === 0 || !is_array($files) || !isset($files[0]['compressed']))
				return false;

			if ($files[0]['compressed'] == 0 && $files[0]['name'] != $this->name) {
				$this->name = $files[0]['name'];
				$this->size = $files[0]['size'] * 0.95;
				$this->adj = $this->sum = 0;

				if ($this->echooutput)
					echo 'r';
				// If archive is not stored compressed, process data
				foreach ($files as $file) {
					if (isset($file['name'])) {
						if (isset($file['error'])) {
							$this->debug("Error: {$file['error']} (in: {$file['source']})");
							continue;
						}
						if ($file['pass'] == true) {
							$this->password = true;
							break;
						}

						if (preg_match($this->supportfiles . ')(?!.{20,})/i', $file['name']))
							continue;

						if (preg_match('/\.zip$/i', $file['name'])) {
							$zipdata = $rar->getFileData($file['name'], $file['source']);
							$data = $this->processReleaseZips($zipdata, false, true, $release, $nntp);

							if ($data != false) {
								foreach ($data as $d) {
									if (preg_match('/\.(part\d+|r\d+|rar)(\.rar)?$/i', $d['zip']['name']))
										$tmpfiles = $this->getRar($d['data']);
								}
							}
						}

						if (!isset($file['next_offset']))
							$file['next_offset'] = 0;
						$range = mt_rand(0, 99999);
						if (isset($file['range']))
							$range = $file['range'];
						$retval[] = array('name' => $file['name'], 'source' => $file['source'], 'range' => $range, 'size' => $file['size'], 'date' => $file['date'], 'pass' => $file['pass'], 'next_offset' => $file['next_offset']);
						$this->adj = $file['next_offset'] + $this->adj;
					}
				}

				$this->sum = $this->adj;
				if ($this->segsize !== 0)
					$this->adj = $this->adj / $this->segsize;
				else
					$this->adj = 0;

				if ($this->adj < .7)
					$this->adj = 1;
			}
			else {
				$this->size = $files[0]['size'] * 0.95;
				if ($this->name != $files[0]['name']) {
					$this->name = $files[0]['name'];
					$this->sum = $this->segsize;
					$this->adj = 1;
				}

				// File is compressed, use unrar to get the content
				$rarfile = $this->tmpPath . 'rarfile' . mt_rand(0, 99999) . '.rar';
				if (@file_put_contents($rarfile, $fetchedBinary)) {
					$execstring = '"' . $this->site->unrarpath . '" e -ai -ep -c- -id -inul -kb -or -p- -r -y "' . $rarfile . '" "' . $this->tmpPath . '"';
					$output = @runCmd($execstring, false, true);
					if (isset($files[0]['name'])) {
						if ($this->echooutput)
							echo 'r';
						foreach ($files as $file) {
							if (isset($file['name'])) {
								if (!isset($file['next_offset']))
									$file['next_offset'] = 0;
								$range = mt_rand(0, 99999);
								if (isset($file['range']))
									$range = $file['range'];

								$retval[] = array('name' => $file['name'], 'source' => $file['source'], 'range' => $range, 'size' => $file['size'], 'date' => $file['date'], 'pass' => $file['pass'], 'next_offset' => $file['next_offset']);
							}
						}
					}
				}
			}
		}
		else {
			// Not a rar file, try it as a ZIP file.
			$files = $this->processReleaseZips($fetchedBinary, false, false, $release, $nntp);
			if ($files !== false && isset($files[0]['name'])) {
				$this->name = $files[0]['name'];
				$this->size = $files[0]['size'] * 0.95;
				$this->sum = $this->adj = 0;

				foreach ($files as $file) {
					if (isset($file['pass']) && $file['pass']) {
						$this->password = true;
						break;
					}

					if (!isset($file['next_offset']))
						$file['next_offset'] = 0;
					if (!isset($file['range']))
						$file['range'] = 0;

					$retval[] = array('name' => $file['name'], 'source ' => 'main', 'range' => $file['range'], 'size' => $file['size'], 'date' => $file['date'], 'pass' => $file['pass'], 'next_offset' => $file['next_offset']);
					$this->adj = $file['next_offset'] + $this->adj;
					$this->sum = $file['size'] + $this->sum;
				}

				$this->size = $this->sum;
				$this->sum = $this->adj;
				if ($this->segsize !== 0)
					$this->adj = $this->adj / $this->segsize;
				else
					$this->adj = 0;

				if ($this->adj < .7)
					$this->adj = 1;
			}
			// Not a compressed file, but segmented.
			else
				$this->ignorenumbered = true;
		}

		// Use found content to populate releasefiles, nfo, and create multimedia files.
		foreach ($retval as $k => $v) {
			if (!preg_match($this->supportfiles . '|part\d+|r\d{1,3}|zipr\d{2,3}|\d{2,3}|zipx|zip|rar)(\.rar)?$/i', $v['name']) && count($retval) > 0)
				$this->addfile($v, $release, $rar, $nntp);
			else
				unset($retval[$k]);
		}

		if (count($retval) === 0) {
			$retval = false;
		}
		unset($fetchedBinary, $rar, $nfo);
		return $retval;
	}

	/**
	 * Attempt to get mediafio xml from a video file.
	 *
	 * @note Only called by processAddtional
	 *
	 * @param $releaseID
	 *
	 * @return bool
	 */
	protected function getMediainfo($releaseID)
	{
		// Return value.
		$retVal = false;

		// Get all the files in the temp folder.
		$mediaFiles = glob($this->tmpPath . '*.*');

		// Check if we got them.
		if ($mediaFiles !== false) {

			// Loop over them.
			foreach ($mediaFiles as $mediaFile) {

				// Look for the video file.
				if (preg_match('/\.avi$/i', $mediaFile) && is_file($mediaFile)) {

					// Run media info on it.
					$xmlArray = @runCmd('"' . $this->site->mediainfopath . '" --Output=XML "' . $mediaFile . '"');

					// Check if we got it.
					if (is_array($xmlArray)) {

						// Convert it to string.
						$xmlArray = implode("\n", $xmlArray);

						// Insert it into the DB.
						$this->releaseExtra->addFull($releaseID, $xmlArray);
						$this->releaseExtra->addFromXml($releaseID, $xmlArray);

						$retVal = true;
						if ($this->echooutput) {
							echo 'm';
						}
						break;
					}
				}
			}
		}
		return $retVal;
	}

	/**
	 * Attempt to get mediainfo/sample/title from a audio file.
	 *
	 * @note Only called by processAddtional
	 *
	 * @param $releaseGuid
	 * @param $releaseID
	 * @param string $extension, the extension (mp3, flac, etc).
	 *
	 * @return bool
	 */
	protected function getAudioinfo($releaseGuid, $releaseID, $extension)
	{
		// Return values.
		$retVal = $audVal = false;

		// Check if audio sample fetching is on.
		if (!$this->processAudioSample) {
			$audVal = true;
		}

		// Check if media info fetching is on.
		if ($this->site->mediainfopath === '') {
			$retVal = true;
		}

		// Make sure the category is music or other->misc.
		$rquer = $this->db->queryOneRow(sprintf(
			'SELECT categoryid as id, groupid FROM releases WHERE proc_pp = 0 AND id = %d', $releaseID));
		if (!preg_match('/^3\d{3}|7010/', $rquer['id'])) {
			return $retVal;
		}

		// Get all the files in temp folder.
		$audiofiles = glob($this->tmpPath . '*.*');

		// Check that we got some files.
		if ($audiofiles !== false) {

			// Loop over the files.
			foreach ($audiofiles as $audiofile) {

				// Check if we find the file.
				if (preg_match('/' . $extension . '$/i', $audiofile) && is_file($audiofile)) {

					// Check if mediainfo is enabled.
					if ($retVal === false) {

						//  Get the mediainfo for the file.
						$xmlarray = @runCmd('"' . $this->site->mediainfopath . '" --Output=XML "' . $audiofile . '"');
						if (is_array($xmlarray)) {

							// Convert to array.
							$arrXml = objectsIntoArray(@simplexml_load_string(implode("\n", $xmlarray)));


							if (isset($arrXml['File']['track'])) {

								foreach ($arrXml['File']['track'] as $track) {

									if (isset($track['Album']) && isset($track['Performer'])) {

										// Make the extension upper case.
										$ext = strtoupper($extension);

										// Form a new search name.
										if (!empty($track['Recorded_date']) && preg_match('/(?:19|20)\d\d/', $track['Recorded_date'], $Year)) {
											$newname = $track['Performer'] . ' - ' . $track['Album'] . ' (' . $Year[0] . ') ' . $ext;
										} else {
											$newname = $track['Performer'] . ' - ' . $track['Album'] . ' ' . $ext;
										}

										// Get the category or try to determine it.
										$category = new Category();
										if ($ext === 'MP3') {
											$newcat = Category::CAT_MUSIC_MP3;
										} else if ($ext === 'FLAC') {
											$newcat = Category::CAT_MUSIC_LOSSLESS;
										} else {
											$newcat = $category->determineCategory($newname, $rquer['groupid']);
										}

										// Update the search name.
										$this->db->queryExec(sprintf('UPDATE releases SET searchname = %s, categoryid = %d, iscategorized = 1, isrenamed = 1, proc_pp = 1 WHERE id = %d', $this->db->escapeString(substr($newname, 0, 255)), $newcat, $releaseID));

										// Add the mediainfo.
										$this->releaseExtra->addFromXml($releaseID, $xmlarray);

										$retVal = true;
										if ($this->echooutput) {
											echo 'a';
										}
										break;
									}
								}
							}
						}
					}

					// File name to store audio file.
					$audioFileName = $releaseGuid . '.ogg';

					// Check if creating audio samples is enabled.
					if ($audVal === false) {

						// Create an audio sample.
						@runCmd(
							'"' .
							$this->site->ffmpegpath .
							'" -t 30 -i "' .
							$audiofile .
							'" -acodec libvorbis -loglevel quiet -y "' .
							$this->tmpPath .
							$audioFileName .
							'"');

						// Get all the files in the temp path.
						$all_files = @scandir($this->tmpPath, 1);

						// If it's false, continue.
						if ($all_files === false) {
							continue;
						}

						// Loop over the temp files.
						foreach ($all_files as $file) {

							// Try to find the temp audio file.
							if ($file === $audioFileName) {

								// Try to move the temp audio file.
								$renamed = @rename($this->tmpPath . $audioFileName, $this->audSavePath . $audioFileName);

								if (!$renamed) {
									// Try to copy it if it fails.
									$copied = @copy($this->tmpPath . $audioFileName, $this->audSavePath . $audioFileName);

									// Delete the old file.
									unlink($this->tmpPath . $audioFileName);

									// If it didn't copy continue.
									if (!$copied) {
										continue;
									}
								}

								// Try to set the file perms.
								chmod($this->audSavePath . $audioFileName, 0764);

								// Update DB to said we got a audio sample.
								$this->db->queryExec(sprintf('UPDATE releases SET audiostatus = 1 WHERE id = %d', $releaseID));

								$audVal = true;

								if ($this->echooutput) {
									echo 'A';
								}

								break;
							}
						}
					}
					// If we got both, break.
					if ($retVal === true && $audVal === true) {
						break;
					}
				}
			}
		}
		return ($retVal && $audVal);
	}

	/**
	 * Attempt to get a sample image from a video file.
	 *
	 * @note Only called by processAddtional
	 *
	 * @param $releaseGuid
	 *
	 * @return bool
	 */
	protected function getSample($releaseGuid)
	{
		// Return value.
		$retVal = false;

		// Get all file in temp folder.
		$sampleFiles = glob($this->tmpPath . '*.*');

		// Check if it failed.
		if ($sampleFiles !== false) {

			// Create path to temp file.
			$fileName = 'zzzz' . mt_rand(5, 12) . mt_rand(5, 12) . '.jpg';

			// Loop over all the files.
			foreach ($sampleFiles as $sampleFile) {

				// Look for a file ending with .avi, check if it's really a file.
				if (preg_match('/\.avi$/i', $sampleFile) && is_file($sampleFile)) {

					// Get the exact time of this video, using the header is not precise so use -vcodec.
					$time = @exec(
						'"' .
						$this->site->ffmpegpath .
						'" -i "' .
						$sampleFile .
						'" -vcodec copy -f null /dev/null 2>&1 | cut -f 6 -d \'=\' | grep \'^[0-9].*bitrate\' | cut -f 1 -d \' \''
					);

					// If it's 11 chars long, it's good (00:00:00.00)
					if (strlen($time) !== 11) {
						// If not set it to 1 second.
						$time = '00:00:01';
					}

					// Create the image.
					@exec(
						'"' .
						$this->site->ffmpegpath .
						'" -i "' .
						$sampleFile .
						'" -ss ' .
						$time .
						' -loglevel quiet -vframes 1 -y "' .
						$this->tmpPath .
						$fileName .
						'"'
					);

					// Get all the files in the temp folder.
					$all_files = @scandir($this->tmpPath, 1);

					// Loop all the files.
					foreach ($all_files as $file) {

						// Check if the file is the file we created.
						if ($file === $fileName) {

							// Try to resize/move the image.
							$saved =
								$this->releaseImage->saveImage(
									$releaseGuid . '_thumb',
									$this->tmpPath .$file,
									$this->releaseImage->imgSavePath, 800, 600
								);

							// Delete the temp file we created.
							unlink($this->tmpPath . $fileName);

							// Check if it saved.
							if ($saved === 1 && is_file($this->releaseImage->imgSavePath . $releaseGuid . '_thumb.jpg')) {

								$retVal = true;
								if ($this->echooutput) {
									echo 's';
								}
								return $retVal;
							}
						}
					}
				}
			}
		}
		// If an image was made, return true, else return false.
		return $retVal;
	}

	/**
	 * Get a video sample.
	 *
	 * @note Only called by processAddtional
	 *
	 * @param $releaseGuid Guid of the release.
	 *
	 * @return bool
	 */
	protected function getVideo($releaseGuid)
	{
		// Return value.
		$retVal = false;

		// Get all the files in the temp dir.
		$sampleFiles = glob($this->tmpPath . '*.*');
		if ($sampleFiles !== false) {

			// Create a filename to store the temp file.
			$fileName = 'zzzz' . $releaseGuid . '.ogv';

			// Loop all the files in the temp folder.
			foreach ($sampleFiles as $sampleFile) {

				// Try to find an avi file.
				if (preg_match('/\.avi$/i', $sampleFile) && is_file($sampleFile)) {

					// Try to create an ogv video using the avi video.
					$output = @runCmd
					(
						'"' .
						$this->site->ffmpegpath .
						'" -i "' .
						$sampleFile .
						'" -vcodec libtheora -filter:v scale=320:-1 -t ' .
						$this->ffmpeg_duration .
						' -acodec libvorbis -loglevel quiet -y "' .
						$this->tmpPath .
						$fileName .
						'"'
					);

					// Get all the files in the temp dir.
					$all_files = @scandir($this->tmpPath, 1);
					if ($all_files === false ) {
						continue;
					}

					// Loop over them.
					foreach ($all_files as $file) {

						// Until we find the video file.
						if ($file === $fileName) {

							// Create a path to where the file should be moved.
							$newFile = $this->releaseImage->vidSavePath . $releaseGuid . '.ogv';

							// Try to move the file to the new path.
							$renamed = @rename($this->tmpPath . $fileName, $newFile);

							// If we couldn't rename it, try to copy it.
							if (!$renamed) {

								$copied = @copy($this->tmpPath . $fileName, $newFile);

								// Delete the old file.
								unlink($this->tmpPath . $fileName);

								// If it didn't copy, continue.
								if (!$copied) {
									continue;
								}
							}

							// Change the permissions.
							chmod($newFile, 0764);

							// Update query to say we got the video.
							$this->db->queryExec(sprintf('UPDATE releases SET videostatus = 1 WHERE guid = %s', $this->db->escapeString($releaseGuid)));
							$retVal = true;
							if ($this->echooutput) {
								echo 'v';
							}
							return $retVal;
						}
					}
				}
			}
		}
		// If an video was made, return true, else return false.
		return $retVal;
	}

}
