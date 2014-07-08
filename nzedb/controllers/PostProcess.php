<?php
require_once nZEDb_LIBS . 'rarinfo/par2info.php';

use nzedb\utility;

class PostProcess
{
	/**
	 * @var nzedb\db\DB
	 */
	private $pdo;

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
	 * Class instance of debugging.
	 * @var Debugging
	 */
	protected $debugging;

	/**
	 * Instance of NameFixer.
	 * @var NameFixer
	 */
	protected $nameFixer;

	/**
	 * Constructor.
	 *
	 * @param bool $echoOutput Echo to CLI or not?
	 */
	public function __construct($echoOutput = false)
	{
		//\\ Various.
		$this->echooutput = ($echoOutput && nZEDb_ECHOCLI);
		//\\

		//\\ Class instances.
		$this->pdo = new nzedb\db\Settings();
		$this->groups = new Groups();
		$this->_par2Info = new Par2Info();
		$this->debugging = new Debugging('PostProcess');
		$this->nameFixer = new NameFixer($this->echooutput);
		$this->Nfo = new Nfo($this->echooutput);
		$this->releaseFiles = new ReleaseFiles();
		//\\

		//\\ Site settings.
		$this->addpar2 = ($this->pdo->getSetting('addpar2') == 0) ? false : true;
		$this->alternateNNTP = ($this->pdo->getSetting('alternate_nntp') == 1 ? true : false);
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
		$this->processPredb($nntp);
		$this->processAdditional($nntp);
		$this->processNfos('', $nntp);
		$this->processSharing($nntp);
		$this->processMovies();
		$this->processMusic();
		$this->processConsoles();
		$this->processGames();
		$this->processAnime();
		$this->processTv();
		$this->processBooks();
	}

	/**
	 * Lookup anidb if enabled - always run before tvrage.
	 *
	 * @return void
	 */
	public function processAnime()
	{
		if ($this->pdo->getSetting('lookupanidb') == 1) {
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
		if ($this->pdo->getSetting('lookupbooks') != 0) {
			$books = new Books($this->echooutput);
			$books->processBookReleases();
		}
	}

	/**
	 * Lookup console games if enabled.
	 *
	 * @return void
	 */
	public function processConsoles()
	{
		if ($this->pdo->getSetting('lookupgames') != 0) {
			$console = new Console($this->echooutput);
			$console->processConsoleReleases();
		}
	}

	/**
	 * Lookup games if enabled.
	 *
	 * @return void
	 */
	public function processGames()
	{
		if ($this->pdo->getSetting('lookupgames') != 0) {
			$games = new Games($this->echooutput);
			$games->processGamesReleases();
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
		if ($this->pdo->getSetting('lookupimdb') == 1) {
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
		if ($this->pdo->getSetting('lookupmusic') != 0) {
			$music = new Music($this->echooutput);
			$music->processMusicReleases();
		}
	}

	/**
	 * Process nfo files.
	 *
	 * @param string $releaseToWork
	 * @param NNTP   $nntp
	 *
	 * @return void
	 */
	public function processNfos($releaseToWork = '', $nntp)
	{
		if ($this->pdo->getSetting('lookupnfo') == 1) {
			$this->Nfo->processNfoFiles($releaseToWork,	$this->pdo->getSetting('lookupimdb'), $this->pdo->getSetting('lookuptvrage'),	$groupID = '', $nntp);
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
		// 2014-05-31 : Web PreDB fetching is removed. Using IRC is now recommended.
	}

	/**
	 * Process comments.
	 *
	 * @param NNTP $nntp
	 */
	public function processSharing(&$nntp)
	{
		$sharing = new Sharing($this->pdo, $nntp);
		$sharing->start();
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
		if ($this->pdo->getSetting('lookuptvrage') == 1) {
			$tvRage = new TvRage($this->echooutput);
			$tvRage->processTvReleases($releaseToWork, true);
		}
	}

	/**
	 * Check for passworded releases, RAR/ZIP contents and Sample/Media info.
	 *
	 * @note Called externally by tmux/bin/update_per_group and update/postprocess.php
	 *
	 * @param NNTP   $nntp          Class NNTP
	 * @param string $releaseToWork String containing SQL results. Optional.
	 * @param string $groupID       Group ID. Optional
	 *
	 * @return void
	 */
	public function processAdditional($nntp, $releaseToWork = '', $groupID = '')
	{
		$processAdditional = new ProcessAdditional($this->echooutput, $nntp, $this->pdo);
		$processAdditional->start($releaseToWork, $groupID);
	}

	/**
	 * Attempt to get a better name from a par2 file and categorize the release.
	 *
	 * @note Called from NZBContents.php
	 *
	 * @param string $messageID MessageID from NZB file.
	 * @param int    $relID     ID of the release.
	 * @param int    $groupID   Group ID of the release.
	 * @param NNTP   $nntp      Class NNTP
	 * @param int    $show      Only show result or apply iy.
	 *
	 * @return bool
	 */
	public function parsePAR2($messageID, $relID, $groupID, $nntp, $show)
	{
		if ($messageID === '') {
			return false;
		}

		$query = $this->pdo->queryOneRow(
			sprintf('
				SELECT id, group_id, categoryid, name, searchname, UNIX_TIMESTAMP(postdate) AS post_date, id AS releaseid
				FROM releases
				WHERE isrenamed = 0
				AND id = %d',
				$relID
			)
		);

		if ($query === false) {
			return false;
		}

		// Only get a new name if the category is OTHER.
		$foundName = true;
		if (!in_array(
			(int)$query['categoryid'],
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

		// Get the PAR2 file.
		$par2 = $nntp->getMessages($this->groups->getByNameByID($groupID), $messageID, $this->alternateNNTP);
		if ($nntp->isError($par2)) {
			return false;
		}

		// Put the PAR2 into Par2Info, check if there's an error.
		$this->_par2Info->setData($par2);
		if ($this->_par2Info->error) {
			return false;
		}

		// Get the file list from Par2Info.
		$files = $this->_par2Info->getFileList();
		if ($files !== false && count($files) > 0) {

			$filesAdded = 0;

			// Loop through the files.
			foreach ($files as $file) {

				if (!isset($file['name'])) {
					continue;
				}

				// If we found a name and added 10 files, stop.
				if ($foundName === true && $filesAdded > 10) {
					break;
				}

				if ($this->addpar2) {
					// Add to release files.
					if ($filesAdded < 11 &&
						$this->pdo->queryOneRow(
							sprintf('
								SELECT id
								FROM releasefiles
								WHERE releaseid = %d
								AND name = %s',
								$relID,
								$this->pdo->escapeString($file['name'])
							)
						) === false
					) {

						// Try to add the files to the DB.
						if ($this->releaseFiles->add($relID, $file['name'], $file['size'], $query['post_date'], 0)) {
							$filesAdded++;
						}
					}
				} else {
					$filesAdded++;
				}

				// Try to get a new name.
				if ($foundName === false) {
					$query['textstring'] = $file['name'];
					if ($this->nameFixer->checkName($query, 1, 'PAR2, ', 1, $show) === true) {
						$foundName = true;
					}
				}
			}

			// If we found some files.
			if ($filesAdded > 0) {
				$this->debugging->start('parsePAR2', 'Added ' . $filesAdded . ' releasefiles from PAR2 for ' . $query['searchname'], 5);

				// Update the file count with the new file count + old file count.
				$this->pdo->queryExec(
					sprintf('
						UPDATE releases
						SET rarinnerfilecount = rarinnerfilecount + %d
						WHERE id = %d',
						$filesAdded,
						$relID
					)
				);
			}
			if ($foundName === true) {
				return true;
			}
		}
		return false;
	}
}
