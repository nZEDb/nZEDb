<?php
namespace nzedb;

use app\models\Settings;
use nzedb\db\DB;
use nzedb\utility\Misc;
use nzedb\processing\tv\TraktTv;
use Tmdb\ApiToken;
use Tmdb\Client as TmdbClient;
use Tmdb\Exception\TmdbApiException;

/**
 * Class Movie
 */
class Movie
{

	const SRC_BOXOFFICE = 1;
	const SRC_INTHEATRE = 2;
	const SRC_OPENING = 3;
	const SRC_UPCOMING = 4;
	const SRC_DVD = 5;

	/**
	 * @var \nzedb\db\DB
	 */
	public $pdo;

	/**
	 * @var
	 */
	public $traktTv;

	/**
	 * Current title being passed through various sites/api's.
	 * @var string
	 */
	protected $currentTitle = '';

	/**
	 * Current year of parsed search name.
	 * @var string|false
	 */
	protected $currentYear = '';

	/**
	 * Current release id of parsed search name.
	 *
	 * @var string
	 */
	protected $currentRelID = '';

	/**
	 * @var \nzedb\Logger
	 */
	protected $debugging;

	/**
	 * @var boolean
	 */
	protected $debug;

	/**
	 * Use search engines to find IMDB id's.
	 * @var boolean
	 */
	protected $searchEngines;

	/**
	 * How many times have we hit google this session.
	 * @var integer
	 */
	protected $googleLimit = 0;

	/**
	 * If we are temp banned from google, set time we were banned here, try again after 10 minutes.
	 * @var integer
	 */
	protected $googleBan = 0;

	/**
	 * How many times have we hit bing this session.
	 *
	 * @var integer
	 */
	protected $bingLimit = 0;

	/**
	 * How many times have we hit yahoo this session.
	 *
	 * @var integer
	 */
	protected $yahooLimit = 0;

	/**
	 * @var string
	 */
	protected $showPasswords;

	/**
	 * @var \nzedb\ReleaseImage
	 */
	protected $releaseImage;

	/**
	 * @var \Tmdb\Client
	 */
	protected $tmdbClient;

	/**
	 * @var \Tmdb\ApiToken
	 */
	protected $tmdbToken;

	/**
	 * Language to fetch from IMDB.
	 * @var string
	 */
	protected $imdbLanguage;

	/**
	 * @var null|string
	 */
	public $fanartapikey;

	/**
	 * @var boolean
	 */
	public $imdburl;

	/**
	 * @var int|null|string
	 */
	public $movieqty;

	/**
	 * @var boolean
	 */
	public $echooutput;

	/**
	 * @var string
	 */
	public $imgSavePath;

	/**
	 * @var string
	 */
	public  $service;

	/**
	 * @var string
	 */
	protected $catWhere;

	/**
	 * @var bool
	 */
	private $_debug;

	/**
	 * @param array $options Class instances / Echo to CLI.
	 *
	 * @throws \Exception
	 */
	public function __construct(array $options = [])
	{
		$defaults = [
				'Echo'         => false,
				'Logger'    => null,
				'ReleaseImage' => null,
				'Settings'     => null,
				'TMDb'         => null,
		];
		$options += $defaults;

		$this->pdo = ($options['Settings'] instanceof DB ? $options['Settings'] : new DB());
		$this->releaseImage = ($options['ReleaseImage'] instanceof ReleaseImage ? $options['ReleaseImage'] : new ReleaseImage($this->pdo));

		$this->tmdbToken = new ApiToken(Settings::value('APIs..tmdbkey'));
		$this->tmdbClient = new TmdbClient($this->tmdbToken, [
				'cache' => [
					'enabled' => false
				]
			]
		);

		$result = Settings::value('indexer.categorise.imdblanguage');
		$this->imdbLanguage = $result === null ? (string)$result : 'en';

		$this->fanartapikey = Settings::value('APIs..fanarttvkey');
		$this->imdburl = (int) Settings::value('indexer.categorise.imdburl') !== 0;
		$result = Settings::value('..maximdbprocessed');
		$this->movieqty = $result === null ? 100 : $result;
		$this->searchEngines = true;
		$this->showPasswords = Releases::showPasswords();

		$this->debug = nZEDb_DEBUG;
		$this->echooutput = ($options['Echo'] && nZEDb_ECHOCLI && $this->pdo->cli);
		$this->imgSavePath = nZEDb_COVERS . 'movies' . DS;
		$this->service = '';

		$this->catWhere = 'AND categories_id BETWEEN ' . Category::MOVIE_ROOT . ' AND ' . Category::MOVIE_OTHER;

		if (nZEDb_DEBUG || nZEDb_LOGGING) {
			$this->debug = true;
			try {
				$this->debugging = new Logger();
			} catch (LoggerException $error) {
				$this->_debug = false;
			}
		}
	}

	/**
	 * Get info for a IMDB id.
	 *
	 * @param integer $imdbId
	 *
	 * @return array|boolean
	 */
	public function getMovieInfo($imdbId)
	{
		return $this->pdo->queryOneRow(sprintf('SELECT * FROM movieinfo WHERE imdbid = %d', $imdbId));
	}

	/**
	 * Get info for multiple IMDB id's.
	 *
	 * @param array $imdbIDs
	 *
	 * @return array
	 */
	public function getMovieInfoMultiImdb($imdbIDs)
	{
		return $this->pdo->query(
			sprintf('
				SELECT DISTINCT movieinfo.*, releases.imdbid AS relimdb
				FROM movieinfo
				LEFT OUTER JOIN releases ON releases.imdbid = movieinfo.imdbid
				WHERE movieinfo.imdbid IN (%s)',
				str_replace(
					',,',
					',',
					str_replace(
						['(,', ' ,', ', )', ',)'],
						'',
						implode(',', $imdbIDs)
					)
				)
			), true, nZEDb_CACHE_EXPIRY_MEDIUM
		);
	}

	/**
	 * Retrieves a range of all shows for the show-edit admin list
	 *
	 * @param        $start
	 * @param        $num
	 * @param string $movietitle
	 *
	 * @return array
	 */
    public function getRange($start, $num, $movietitle = '')
    {
        if ($start === false) {
            $limit = '';
        } else {
            $limit = 'LIMIT ' . $num . ' OFFSET ' . $start;
        }

        $rsql = '';
        if ($movietitle !== '') {
            $rsql .= sprintf('AND movieinfo.title LIKE %s ', $this->pdo->escapeString('%' . $movietitle . '%'));
        }

        return $this->pdo->query(
            sprintf('
                        SELECT *
                        FROM movieinfo
                        WHERE 1=1 %s
                        ORDER BY createddate DESC %s',
                $rsql,
                $limit
            )
        );
    }

	/**
	 * Get count of movies for movie-list admin page.
	 *
	 * @return integer
	 */
	public function getCount()
	{
		$res = $this->pdo->queryOneRow('SELECT COUNT(id) AS num FROM movieinfo');
		return ($res === false ? 0 : $res['num']);
	}

	/**
	 * @param       $cat
	 * @param       $start
	 * @param       $num
	 * @param       $orderBy
	 * @param int   $maxAge
	 * @param array $excludedCats
	 *
	 * @return array
	 * @throws \RuntimeException
	 */
	public function getMovieRange($cat, $start, $num, $orderBy, $maxAge = -1, $excludedCats = [])
	{
		$catsrch = '';
		if (count($cat) > 0 && $cat[0] !== -1) {
			$catsrch = (new Category(['Settings' => $this->pdo]))->getCategorySearch($cat);
		}

		$order = $this->getMovieOrder($orderBy);

		$movies = $this->pdo->queryCalc(
				sprintf("
					SELECT SQL_CALC_FOUND_ROWS
						m.imdbid,
						GROUP_CONCAT(r.id ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_id
					FROM movieinfo m
					LEFT JOIN releases r USING (imdbid)
					WHERE r.nzbstatus = 1
					AND m.title != ''
					AND m.imdbid != '0000000'
					AND r.passwordstatus %s
					%s %s %s %s
					GROUP BY m.imdbid
					ORDER BY %s %s %s",
					$this->showPasswords,
					$this->getBrowseBy(),
					(!empty($catsrch) ? $catsrch : ''),
					($maxAge > 0
							? 'AND r.postdate > NOW() - INTERVAL ' . $maxAge . 'DAY '
							: ''
					),
					(count($excludedCats) > 0 ? ' AND r.categories_id NOT IN (' . implode(',', $excludedCats) . ')' : ''),
					$order[0],
					$order[1],
					($start === false ? '' : ' LIMIT ' . $num . ' OFFSET ' . $start)
				), true, nZEDb_CACHE_EXPIRY_MEDIUM
		);

		$movieIDs = $releaseIDs = false;

		if (\is_array($movies['result'])) {
			foreach ($movies['result'] as $movie => $id) {
				$movieIDs[] = $id['imdbid'];
				$releaseIDs[] = $id['grp_release_id'];
			}
		}

		$sql = sprintf("
			SELECT
				GROUP_CONCAT(r.id ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_id,
				GROUP_CONCAT(r.rarinnerfilecount ORDER BY r.postdate DESC SEPARATOR ',') AS grp_rarinnerfilecount,
				GROUP_CONCAT(r.haspreview ORDER BY r.postdate DESC SEPARATOR ',') AS grp_haspreview,
				GROUP_CONCAT(r.passwordstatus ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_password,
				GROUP_CONCAT(r.guid ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_guid,
				GROUP_CONCAT(rn.releases_id ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_nfoid,
				GROUP_CONCAT(g.name ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_grpname,
				GROUP_CONCAT(r.searchname ORDER BY r.postdate DESC SEPARATOR '#') AS grp_release_name,
				GROUP_CONCAT(r.postdate ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_postdate,
				GROUP_CONCAT(r.size ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_size,
				GROUP_CONCAT(r.totalpart ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_totalparts,
				GROUP_CONCAT(r.comments ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_comments,
				GROUP_CONCAT(r.grabs ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_grabs,
				GROUP_CONCAT(df.failed ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_failed,
				GROUP_CONCAT(cp.title, ' > ', c.title ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_catname,
			m.*,
			g.name AS group_name,
			rn.releases_id AS nfoid
			FROM releases r
			LEFT OUTER JOIN groups g ON g.id = r.groups_id
			LEFT OUTER JOIN release_nfos rn ON rn.releases_id = r.id
			LEFT OUTER JOIN dnzb_failures df ON df.release_id = r.id
			LEFT OUTER JOIN categories c ON c.id = r.categories_id
			LEFT OUTER JOIN categories cp ON cp.id = c.parentid
			INNER JOIN movieinfo m ON m.imdbid = r.imdbid
			WHERE m.imdbid IN (%s)
			AND r.id IN (%s) %s
			GROUP BY m.imdbid
			ORDER BY %s %s",
				(\is_array($movieIDs) ? implode(',', $movieIDs) : -1),
				(\is_array($releaseIDs) ? implode(',', $releaseIDs) : -1),
				(!empty($catsrch) ? $catsrch : ''),
				$order[0],
				$order[1]
		);
		$return = $this->pdo->query($sql, true, nZEDb_CACHE_EXPIRY_MEDIUM);
		if (!empty($return)) {
			$return[0]['_totalcount'] = ($movies['total'] ?? 0);
		}

		return $return;
	}

	/**
	 * Get the order type the user requested on the movies page.
	 *
	 * @param $orderBy
	 *
	 * @return array
	 */
	protected function getMovieOrder($orderBy)
	{
		$orderArr = explode('_', (($orderBy === '') ? 'MAX(r.postdate)' : $orderBy));
		switch ($orderArr[0]) {
			case 'title':
				$orderField = 'm.title';
				break;
			case 'year':
				$orderField = 'm.year';
				break;
			case 'rating':
				$orderField = 'm.rating';
				break;
			case 'posted':
			default:
				$orderField = 'MAX(r.postdate)';
				break;
		}

		return [$orderField, (isset($orderArr[1]) && preg_match('/^asc|desc$/i', $orderArr[1])) ? $orderArr[1] : 'desc'];
	}

	/**
	 * @return array
	 */
	public function getMovieOrdering()
	{
		return ['title_asc', 'title_desc', 'year_asc', 'year_desc', 'rating_asc', 'rating_desc'];
	}

	/**
	 * @return string
	 */
	protected function getBrowseBy()
	{
		$browseBy = ' ';
		$browseByArr = ['title', 'director', 'actors', 'genre', 'rating', 'year', 'imdb'];
		foreach ($browseByArr as $bb) {
			if (isset($_REQUEST[$bb]) && !empty($_REQUEST[$bb])) {
				$bbv = stripslashes($_REQUEST[$bb]);
				if ($bb === 'rating') {
					$bbv .= '.';
				}
				if ($bb === 'imdb') {
					$browseBy .= sprintf('AND m.%sid = %d', $bb, $bbv);
				} else {
					$browseBy .= 'AND m.' . $bb . ' ' . $this->pdo->likeString($bbv, true, true);
				}
			}
		}
		return $browseBy;
	}

	/**
	 * Get trailer using IMDB Id.
	 *
	 * @param integer $imdbID
	 *
	 * @return boolean|string
	 */
	public function getTrailer($imdbID)
	{
		if (!is_numeric($imdbID)) {
			return false;
		}

		$trailer = $this->pdo->queryOneRow("SELECT trailer FROM movieinfo WHERE imdbid = $imdbID and trailer != ''");
		if ($trailer) {
			return $trailer['trailer'];
		}

		if ($this->traktTv === null) {
			$this->traktTv = new TraktTv(['Settings' => $this->pdo]);
		}

		$data = $this->traktTv->client->movieSummary('tt' . $imdbID, 'full');
		if ($data) {
			$this->parseTraktTv($data);
			if (isset($data['trailer']) && !empty($data['trailer'])) {
				return $data['trailer'];
			}
		}

		$trailer = Misc::imdb_trailers($imdbID);
		if ($trailer) {
			$this->pdo->queryExec(
				'UPDATE movieinfo SET trailer = ' . $this->pdo->escapeString($trailer) . ' WHERE imdbid = ' . $imdbID
			);
			return $trailer;
		}
		return false;
	}

	/**
	 * @param $data
	 *
	 * @return bool|mixed
	 */
	public function parseTraktTv(&$data)
	{
		if (!isset($data['ids']['imdb']) || empty($data['ids']['imdb'])) {
			return false;
		}

		if (isset($data['trailer']) && !empty($data['trailer'])) {
			$data['trailer'] = str_ireplace(
				'http://', 'https://', str_ireplace('watch?v=', 'embed/', $data['trailer'])
			);
			return $data['trailer'];
		}

		$imdbid = (strpos($data['ids']['imdb'], 'tt') === 0) ? substr($data['ids']['imdb'], 2) : $data['ids']['imdb'];
		$cover = 0;
		if (is_file($this->imgSavePath . $imdbid) . '-cover.jpg') {
			$cover = 1;
		}

		$this->update([
				'genres'   => $this->checkTraktValue($data['genres']),
				'imdbid'   => $this->checkTraktValue($imdbid),
				'language' => $this->checkTraktValue($data['language']),
				'plot'     => $this->checkTraktValue($data['overview']),
				'rating'   => round($this->checkTraktValue($data['rating']), 1),
				'tagline'  => $this->checkTraktValue($data['tagline']),
				'title'    => $this->checkTraktValue($data['title']),
				'tmdbid'   => $this->checkTraktValue($data['ids']['tmdb']),
				'trailer'  => $this->checkTraktValue($data['trailer']),
				'cover'    => $cover,
				'year'     => $this->checkTraktValue($data['year'])
		]);
	}

	/**
	 * Checks if the value is set and not empty, returns it, else empty string.
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	private function checkTraktValue($value)
	{
		if (is_array($value)) {
			$temp = '';
			foreach ($value as $val) {
				if (!is_array($val) && !is_object($val)) {
					$temp .= (string)$val;
				}
			}
			$value = $temp;
		}
		return ($value !== null && !empty($value) ? $value : '');
	}

	/**
	 * Create click-able links to IMDB actors/genres/directors/etc..
	 *
	 * @param $data
	 * @param $field
	 *
	 * @return string
	 */
	public function makeFieldLinks($data, $field)
	{
		if (!isset($data[$field]) || $data[$field] === '') {
			return '';
		}

		$tmpArr = explode(', ', $data[$field]);
		$newArr = [];
		$i = 0;
		foreach ($tmpArr as $ta) {
			if (trim($ta) === '') {
				continue;
			}
			if ($i > 5) {
				break;
			} //only use first 6
			$newArr[] = '<a href="' . WWW_TOP . '/movies?' . $field . '=' . urlencode($ta) . '" title="' . $ta . '">' . $ta . '</a>';
			$i++;
		}
		return implode(', ', $newArr);
	}

	/**
	 * Get array of column names, for inserting / updating.
	 * @return string[]
	 */
	public function getColumnKeys()
	{
		return [
			'actors', 'backdrop', 'cover', 'director', 'genre', 'imdbid', 'language',
			'plot', 'rating', 'tagline', 'title', 'tmdbid', 'trailer', 'type', 'year'
		];
	}

	/**
	 * Update movie on movie-edit page.
	 *
	 * @param array $values Array of keys/values to update. See $validKeys
	 * @return integer|boolean
	 */
	public function update(array $values)
	{
		if (!count($values)) {
			return false;
		}

		$validKeys = $this->getColumnKeys();

		$query = [
			'0' => 'INSERT INTO movieinfo (updateddate, createddate, ',
			'1' => ' VALUES (NOW(), NOW(), ',
			'2' => 'ON DUPLICATE KEY UPDATE updateddate = NOW(), '
		];
		$found = 0;
		foreach ($values as $key => $value) {
			if (!empty($value) && in_array($key, $validKeys, false)) {
				$found++;
				$query[0] .= "$key, ";
				if (in_array($key, ['genre', 'language'])) {
					$value = substr($value, 0, 64);
				}
				$value = $this->pdo->escapeString($value);
				$query[1] .= "$value, ";
				$query[2] .= "$key = $value, ";
			}
		}
		if (!$found) {
			return false;
		}
		foreach ($query as $key => $value) {
			$query[$key] = rtrim($value, ', ');
		}

		return $this->pdo->queryInsert($query[0] . ') ' . $query[1] . ') ' . $query[2]);
	}

	/**
	 * Check if a variable is set and not a empty string.
	 *
	 * @param $variable
	 *
	 * @return boolean
	 */
	protected function checkVariable(&$variable)
	{
		return ! empty($variable);
	}

	/**
	 * Returns a tmdb, imdb or trakt variable, the one that is set. Empty string if both not set.
	 *
	 * @param string $variable1
	 * @param string $variable2
	 * @param string $variable3
	 *
	 * @return string
	 */
	protected function setTmdbImdbTraktVar(&$variable1, &$variable2, &$variable3)
	{
		if ($this->checkVariable($variable1)) {
			return $variable1;
		}

		if ($this->checkVariable($variable2)) {
			return $variable2;
		}

		if ($this->checkVariable($variable3)) {
			return $variable3;
		}

		return '';
	}

	/**
	 * Fetch IMDB/TMDB info for the movie.
	 *
	 * @param string $imdbId
	 *
	 * @return boolean
	 */
	public function updateMovieInfo($imdbId)
	{
		if ($this->echooutput && $this->service !== '') {
			ColorCLI::doEcho(ColorCLI::primary('Fetching IMDB info from TMDB using IMDB ID: ' . $imdbId));
		}

		// Check TMDB for IMDB info.
		$tmdb = $this->fetchTMDBProperties($imdbId);

		// Check IMDB for movie info.
		$imdb = $this->fetchIMDBProperties($imdbId);

		// Check TRAKT for movie info
		$trakt = $this->fetchTraktTVProperties($imdbId);
		if (!$imdb && !$tmdb && !$trakt) {
			return false;
		}

		// Check FanArt.tv for background images.
		$fanart = $this->fetchFanartTVProperties($imdbId);

		$mov = [];

		$mov['cover'] = $mov['backdrop'] = $mov['banner'] = $movieID = 0;
		$mov['type'] = $mov['director'] = $mov['actors'] = $mov['language'] = '';

		$mov['imdb_id'] = $imdbId;
		$mov['tmdb_id'] = (!isset($tmdb['tmdb_id']) || $tmdb['tmdb_id'] === '') ? 0 : $tmdb['tmdb_id'];

		// Prefer Fanart.tv cover over TMDB and TMDB over IMDB.
		if ($this->checkVariable($fanart['cover'])) {
			$mov['cover'] = $this->releaseImage->saveImage($imdbId . '-cover', $fanart['cover'], $this->imgSavePath);
		} else if ($this->checkVariable($tmdb['cover'])) {
			$mov['cover'] = $this->releaseImage->saveImage($imdbId . '-cover', $tmdb['cover'], $this->imgSavePath);
		} else if ($this->checkVariable($imdb['cover'])) {
			$mov['cover'] = $this->releaseImage->saveImage($imdbId . '-cover', $imdb['cover'], $this->imgSavePath);
		}

		// Backdrops.
		if ($this->checkVariable($fanart['backdrop'])) {
			$mov['backdrop'] = $this->releaseImage->saveImage($imdbId . '-backdrop', $fanart['backdrop'], $this->imgSavePath, 1920, 1024);
		} else if ($this->checkVariable($tmdb['backdrop'])) {
			$mov['backdrop'] = $this->releaseImage->saveImage($imdbId . '-backdrop', $tmdb['backdrop'], $this->imgSavePath, 1920, 1024);
		}

		$mov['title']   = $this->setTmdbImdbTraktVar($imdb['title'],   $tmdb['title'],   $trakt['title']);
		$mov['rating']  = $this->setTmdbImdbTraktVar($imdb['rating'],  $tmdb['rating'],  $trakt['rating']);
		$mov['plot']    = $this->setTmdbImdbTraktVar($imdb['plot'],    $tmdb['plot'],    $trakt['overview']);
		$mov['tagline'] = $this->setTmdbImdbTraktVar($imdb['tagline'], $tmdb['tagline'], $trakt['tagline']);
		$mov['year']    = $this->setTmdbImdbTraktVar($imdb['year'],    $tmdb['year'],    $trakt['year']);
		$mov['genre']   = $this->setTmdbImdbTraktVar($imdb['genre'],   $tmdb['genre'],   $trakt['genres']);

		if ($this->checkVariable($imdb['type'])) {
			$mov['type'] = $imdb['type'];
		}

		if ($this->checkVariable($imdb['director'])) {
			$mov['director'] = is_array($imdb['director']) ? implode(', ', array_unique($imdb['director'])) : $imdb['director'];
		}

		if ($this->checkVariable($imdb['actors'])) {
			$mov['actors'] = is_array($imdb['actors']) ? implode(', ', array_unique($imdb['actors'])) : $imdb['actors'];
		}

		if ($this->checkVariable($imdb['language'])) {
			$mov['language'] = is_array($imdb['language']) ? implode(', ', array_unique($imdb['language'])) : $imdb['language'];
		}

		if (is_array($mov['genre'])) {
			$mov['genre'] = implode(', ', array_unique($mov['genre']));
		}

		if (is_array($mov['type'])) {
			$mov['type'] = implode(', ', array_unique($mov['type']));
		}

		$mov['title'] = html_entity_decode($mov['title']   , ENT_QUOTES, 'UTF-8');

		$mov['title'] = str_replace(['/', '\\'], '', $mov['title']);
		$movieID = $this->update([
				'actors'    => html_entity_decode($mov['actors'], ENT_QUOTES, 'UTF-8'),
				'backdrop'  => $mov['backdrop'],
				'cover'     => $mov['cover'],
				'director'  => html_entity_decode($mov['director'], ENT_QUOTES, 'UTF-8'),
				'genre'     => html_entity_decode($mov['genre'], ENT_QUOTES, 'UTF-8'),
				'imdbid'    => $mov['imdb_id'],
				'language'  => html_entity_decode($mov['language'], ENT_QUOTES, 'UTF-8'),
				'plot'      => html_entity_decode(preg_replace('/\s+See full summary »/u', ' ', $mov['plot']),
					ENT_QUOTES, 'UTF-8'),
				'rating'    => round($mov['rating'], 1),
				'tagline'   => html_entity_decode($mov['tagline'], ENT_QUOTES, 'UTF-8'),
				'title'     => $mov['title'],
				'tmdbid'    => $mov['tmdb_id'],
				'type'      => html_entity_decode(ucwords(preg_replace('/[\.\_]/', ' ', $mov['type'])), ENT_QUOTES, 'UTF-8'),
				'year'      => $mov['year']
		]);

		if ($this->echooutput && $this->service !== '') {
			ColorCLI::doEcho(
					ColorCLI::headerOver(($movieID !== 0 ? 'Added/updated movie: ' : 'Nothing to update for movie: ')) .
					ColorCLI::primary($mov['title'] .
						' (' .
						$mov['year'] .
						') - ' .
						$mov['imdb_id']
					)
			);
		}

		return $movieID !== 0;
	}

	/**
	 * Fetch FanArt.tv backdrop / cover / title.
	 *
	 * @param string $imdbId
	 *
	 * @return boolean|array
	 */
	protected function fetchFanartTVProperties($imdbId)
	{
		if ($this->fanartapikey !== '') {
			$buffer = Misc::getUrl(['url' => 'https://webservice.fanart.tv/v3/movies/tt' . $imdbId . '?api_key=' . $this->fanartapikey, 'verifycert' => false]);
			if ($buffer !== false) {
				$art = json_decode($buffer, true);
				if (isset($art['status']) && $art['status'] === 'error') {
					return false;
				}
				$ret = [];
				if (isset($art['moviebackground'][0]['url'])) {
					$ret['backdrop'] = $art['moviebackground'][0]['url'];
				} elseif (isset($art['moviethumb'][0]['url'])) {
					$ret['backdrop'] = $art['moviethumb'][0]['url'];
				}
				if (isset($art['movieposter'][0]['url'])) {
					$ret['cover'] = $art['movieposter'][0]['url'];
				}
				if (isset($ret['backdrop'], $ret['cover'])) {
					$ret['title'] = $imdbId;
					if (isset($art['name'])) {
						$ret['title'] = $art['name'];
					}
					if ($this->echooutput) {
						ColorCLI::doEcho(ColorCLI::alternateOver('Fanart Found ') .
							ColorCLI::headerOver($ret['title']));
					}

					return $ret;
				}
			}
		}

		return false;
	}

	/**
	 * Fetch info for IMDB id from TMDB.
	 *
	 * @param string $imdbId
	 * @param boolean $text
	 *
	 * @return array|boolean
	 */
	public function fetchTMDBProperties($imdbId, $text = false)
	{
		$lookupId = ($text === false ? 'tt' . $imdbId : $imdbId);

		try {
			$result = $this->tmdbClient->getMoviesApi()->getMovie($lookupId);
		} catch (TmdbApiException $e) {
			return false;
		}

		/*$status = $result['status_code'];
		if (!$status || (isset($status) && $status !== 1)) {
			return false;
		}*/

		$ret = [];
		$ret['title'] = $result['original_title'];

		if ($this->currentTitle !== '') {
			// Check the similarity.
			similar_text($this->currentTitle, $ret['title'], $percent);
			if ($percent < 40) {
				if ($this->debug) {
					$this->debugging->log(
						__CLASS__,
						__FUNCTION__,
						'Found (' .
						$ret['title'] .
						') from TMDB, but it\'s only ' .
						$percent .
						'% similar to (' .
						$this->currentTitle . ')',
						Logger::LOG_INFO
					);
				}
				return false;
			}
		}

		$ret['tmdb_id'] = $result['id'];
		$ImdbID = str_replace('tt', '', $result['imdb_id']);
		$ret['imdb_id'] = $ImdbID;
		if (isset($result['vote_average'])) {
			$ret['rating'] = ($result['vote_average'] === 0) ? '' : $result['vote_average'];
		}

		if (!empty($result['overview'])) {
			$ret['plot'] = $result['overview'];
		}

		if (!empty($result['tagline'])) {
			$ret['tagline'] = $result['tagline'];
		}

		if (!empty($result['release_date'])) {
			$ret['year'] = date('Y', strtotime($result['release_date']));
		}

		if (!empty($result['genres']) && count($result['genres']) > 0) {
			$genres = [];
			foreach ($result['genres'] as $genre) {
				$genres[] = $genre['name'];
			}
			$ret['genre'] = $genres;
		}

		if (!empty($result['poster_path'])) {
			$ret['cover'] = 'http://image.tmdb.org/t/p/w185' . $result['poster_path'];
		}

		if (!empty($result['backdrop_path'])) {
			$ret['backdrop'] = 'http://image.tmdb.org/t/p/original' . $result['backdrop_path'];
		}
		if ($this->echooutput) {
			ColorCLI::doEcho(ColorCLI::primaryOver('TMDb Found ') . ColorCLI::headerOver($ret['title']), true);
		}
		return $ret;
	}

	/**
	 * @param $imdbId
	 *
	 * @return array|bool
	 * @throws \Exception
	 */
	protected function fetchIMDBProperties($imdbId)
	{
		$imdb_regex = [
				'title' => '/<title>(.*?)\s?\(.*?<\/title>/i',
				'tagline' => '/taglines:<\/h4>\s([^<]+)/i',
				'plot' => '/<p itemprop="description">\s*?(.*?)\s*?<\/p>/i',
				'rating' => '/"ratingValue">([\d.]+)<\/span>/i',
				'year' => '/<title>.*?\(.*?(\d{4}).*?<\/title>/i',
				'cover' => '/<link rel=\'image_src\' href="(http:\/\/ia\.media-imdb\.com.+\.jpg)">/'
		];

		$imdb_regex_multi = [
				'genre' => '/href="\/genre\/(.*?)\?/i',
				'language' => '/<a href="\/language\/.+?\'url\'>(.+?)<\/a>/s',
				'type' => '/<meta property=\'og\:type\' content=\"(.+)\" \/>/i'
		];

		$buffer =
				Misc::getUrl(
					[
						'url' => 'http://' . ($this->imdburl === false ? 'www' : 'akas') . '.imdb.com/title/tt' . $imdbId . '/',
						'language' => Settings::value('indexer.categorise.imdblanguage') !== '' ?
							Settings::value('indexer.categorise.imdblanguage') : 'en',
						'useragent' => 'Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) ' .
							'Version/4.0.4 Mobile/7B334b Safari/531.21.102011-10-16 20:23:10', 'foo=bar'
					]
				);

		if ($buffer !== false) {
			$ret = [];
			foreach ($imdb_regex as $field => $regex) {
				if (preg_match($regex, $buffer, $matches)) {
					$match = $matches[1];
					$match1 = trim(strip_tags($match));
					if ($match1 !== '') {
						$ret[$field] = $match1;
					}
				}
			}

			$matches = [];
			foreach ($imdb_regex_multi as $field => $regex) {
				if (preg_match_all($regex, $buffer, $matches)) {
					$match2 = $matches[1];
					$match3 = array_filter(array_map('trim', $match2));
					if (!empty($match3)) {
						$ret[$field] = $match3;
					}
				}
			}

			if ($this->currentTitle !== '' && isset($ret['title'])) {
				// Check the similarity.
				similar_text($this->currentTitle, $ret['title'], $percent);
				if ($percent < 40) {
					if ($this->debug) {
						$this->debugging->log(
							__CLASS__,
							__FUNCTION__,
							'Found (' .
							$ret['title'] .
							') from IMDB, but it\'s only ' .
							$percent .
							'% similar to (' .
							$this->currentTitle . ')',
							Logger::LOG_INFO
						);
					}
					return false;
				}
			}

			// Actors.
			if (preg_match('/<table class="cast_list">(.+?)<\/table>/s', $buffer, $hit)) {
				if (preg_match_all('/<span class="itemprop" itemprop="name">\s*(.+?)\s*<\/span>/i', $hit[0], $results, PREG_PATTERN_ORDER)) {
					$results[1] = array_filter(array_map('trim', $results[1]));
					$ret['actors'] = $results[1];
				}
			}

			// Directors.
			if (preg_match('/itemprop="directors?".+?<\/div>/s', $buffer, $hit)) {
				if (preg_match_all('/"name">(.*?)<\/span>/is', $hit[0], $results, PREG_PATTERN_ORDER)) {
					$results[1] = array_filter(array_map('trim', $results[1]));
					$ret['director'] = $results[1];
				}
			}
			if ($this->echooutput && isset($ret['title'])) {
				ColorCLI::doEcho(ColorCLI::headerOver('IMDb Found ') . ColorCLI::primaryOver($ret['title']), true);
			}
			return $ret;
		}
		return false;
	}

	/**
	 * @param $imdbId
	 *
	 * @return array|bool
	 */
	protected function fetchTraktTVProperties($imdbId)
	{
		if ($this->traktTv === null) {
			$this->traktTv = new TraktTv(['Settings' => $this->pdo]);
		}
		$resp = $this->traktTv->client->movieSummary('tt' . $imdbId, 'full');
		if ($resp !== false) {
			$ret = [];

			if (isset($resp['title'])) {
				$ret['title'] = $resp['title'];
			} else {
				return false;
			}
			if ($this->echooutput) {
				ColorCLI::doEcho(ColorCLI::alternateOver('Trakt Found ') . ColorCLI::headerOver($ret['title']), true);
			}
			return $ret;
		}

		return false;
	}

	/**
	 * Update a release with a IMDB id.
	 *
	 * @param string $buffer       Data to parse a IMDB id from.
	 * @param string $service      Method that called this method.
	 * @param integer    $id           ID of the release.
	 * @param integer    $processImdb  To get IMDB info on this IMDB id or not.
	 *
	 * @return string
	 */
	public function doMovieUpdate($buffer, $service, $id, $processImdb = 1)
	{
		$imdbID = false;
		if (is_string($buffer) && preg_match('/(?:imdb.*?)?(?:tt|Title\?)(?P<imdbid>\d{5,7})/i', $buffer, $matches)) {
			$imdbID = $matches['imdbid'];
		}

		if ($imdbID !== false) {
			$this->service = $service;
			if ($this->echooutput && $this->service !== '') {
				ColorCLI::doEcho(ColorCLI::headerOver($service . ' found IMDBid: ') . ColorCLI::primary('tt' . $imdbID));
			}

			$this->pdo->queryExec(sprintf('UPDATE releases SET imdbid = %s WHERE id = %d %s', $this->pdo->escapeString($imdbID), $id, $this->catWhere));

			// If set, scan for imdb info.
			if ($processImdb === 1) {
				$movCheck = $this->getMovieInfo($imdbID);
				if ($movCheck === false || (isset($movCheck['updateddate']) && (time() - strtotime($movCheck['updateddate'])) > 2592000)) {
					if ($this->updateMovieInfo($imdbID) === false) {
						$this->pdo->queryExec(sprintf('UPDATE releases SET imdbid = %s WHERE id = %d %s', 0000000, $id, $this->catWhere));
					}
				}
			}
		}
		return $imdbID;
	}

	/**
	 * Process releases with no IMDB ID's.
	 *
	 * @param string $groupID    (Optional) ID of a group to work on.
	 * @param string $guidChar   (Optional) First letter of a release GUID to use to get work.
	 * @param integer    $lookupIMDB (Optional) 0 Don't lookup IMDB, 1 lookup IMDB, 2 lookup IMDB on releases that were renamed.
	 */
	public function processMovieReleases($groupID = '', $guidChar = '', $lookupIMDB = 1)
	{
		if ($lookupIMDB === 0) {
			return;
		}

		// Get all releases without an IMDB id.
		$res = $this->pdo->query(
			sprintf('
			SELECT r.searchname, r.id
			FROM releases r
			WHERE r.imdbid IS NULL
			AND r.nzbstatus = 1
			%s %s %s %s
			LIMIT %d',
			$this->catWhere,
			$groupID === '' ? '' : ('AND r.groups_id = ' . $groupID),
			$guidChar === '' ? '' : 'AND r.leftguid = ' . $this->pdo->escapeString($guidChar),
			$lookupIMDB === 2 ? 'AND r.isrenamed = 1' : '',
			$this->movieqty
			)
		);
		$movieCount = count($res);

		if ($movieCount > 0) {
			if ($this->traktTv === null) {
				$this->traktTv = new TraktTv(['Settings' => $this->pdo]);
			}
			if ($this->echooutput && $movieCount > 1) {
				ColorCLI::doEcho(ColorCLI::header('Processing ' . $movieCount . ' movie releases.'));
			}

			// Loop over releases.
			foreach ($res as $arr) {
				// Try to get a name/year.
				if ($this->parseMovieSearchName($arr['searchname']) === false) {
					//We didn't find a name, so set to all 0's so we don't parse again.
					$this->pdo->queryExec(sprintf('UPDATE releases SET imdbid = 0000000 WHERE id = %d %s', $arr['id'], $this->catWhere));
				} else {
					$this->currentRelID = $arr['id'];

					$movieName = $this->currentTitle;
					if ($this->currentYear !== false) {
						$movieName .= ' (' . $this->currentYear . ')';
					}

					if ($this->echooutput) {
						ColorCLI::doEcho(ColorCLI::primaryOver('Looking up: ') . ColorCLI::headerOver($movieName), true);
					}

					// Check local DB.
					$getIMDBid = $this->localIMDBsearch();

					if ($getIMDBid !== false) {
						$imdbID = $this->doMovieUpdate('tt' . $getIMDBid, 'Local DB', $arr['id']);
						if ($imdbID !== false) {
							continue;
						}
					}

					// Check OMDB api.
					$buffer = Misc::getUrl(
						[
							'url' => 'http://www.omdbapi.com/?t=' .
								urlencode($this->currentTitle) .
								($this->currentYear !== false ? ('&y=' . $this->currentYear) : '') .
								'&r=json'
						]
					);

					if ($buffer !== false) {
						$getIMDBid = json_decode($buffer);

						if (isset($getIMDBid->imdbID)) {
							$imdbID = $this->doMovieUpdate($getIMDBid->imdbID, 'OMDbAPI', $arr['id']);
							if ($imdbID !== false) {
								continue;
							}
						}
					}

					// Check on trakt.
					$data = $this->traktTv->client->movieSummary($movieName, 'full');
					if ($data !== false) {
						$this->parseTraktTv($data);
						if (isset($data['ids']['imdb'])) {
							$imdbID = $this->doMovieUpdate($data['ids']['imdb'], 'Trakt', $arr['id']);
							if ($imdbID !== false) {
								continue;
							}
						}
					}

					// Try on search engines.
					if ($this->searchEngines && $this->currentYear !== false) {
						if ($this->imdbIDFromEngines() === true) {
							continue;
						}
					}

					// We failed to get an IMDB id from all sources.
					$this->pdo->queryExec(sprintf('UPDATE releases SET imdbid = 0000000 WHERE id = %d %s', $arr['id'], $this->catWhere));
				}
			}
		}
	}

	/**
	 * Try to fetch an IMDB id locally.
	 *
	 * @return integer|boolean   Int, the imdbid when true, Bool when false.
	 */
	protected function localIMDBsearch()
	{
		$query = 'SELECT imdbid FROM movieinfo';
		$andYearIn = '';

		//If we found a year, try looking in a 4 year range.
		if ($this->currentYear !== false) {
			$start = (int)$this->currentYear - 2;
			$end   = (int)$this->currentYear + 2;
			$andYearIn = 'AND year IN (';
			while ($start < $end) {
				$andYearIn .= $start . ',';
				$start++;
			}
			$andYearIn .= $end . ')';
		}
		$IMDBCheck = $this->pdo->queryOneRow(
				sprintf('%s WHERE title %s %s', $query, $this->pdo->likeString($this->currentTitle), $andYearIn));

		// Look by %word%word%word% etc..
		if ($IMDBCheck === false) {
			$pieces = explode(' ', $this->currentTitle);
			$tempTitle = '%';
			foreach ($pieces as $piece) {
				$tempTitle .= str_replace(["'", '!', '"'], '', $piece) . '%';
			}
			$IMDBCheck = $this->pdo->queryOneRow(
				sprintf("%s WHERE replace(replace(title, \"'\", ''), '!', '') %s %s",
					$query, $this->pdo->likeString($tempTitle), $andYearIn
				)
			);
		}

		// Try replacing er with re ?
		if ($IMDBCheck === false) {
			$tempTitle = str_replace('er', 're', $this->currentTitle);
			if ($tempTitle !== $this->currentTitle) {
				$IMDBCheck = $this->pdo->queryOneRow(
					sprintf('%s WHERE title %s %s',
						$query, $this->pdo->likeString($tempTitle), $andYearIn
					)
				);

				// Final check if everything else failed.
				if ($IMDBCheck === false) {
					$pieces = explode(' ', $tempTitle);
					$tempTitle = '%';
					foreach ($pieces as $piece) {
						$tempTitle .= str_replace(["'", '!', '"'], '', $piece) . '%';
					}
					$IMDBCheck = $this->pdo->queryOneRow(
						sprintf("%s WHERE replace(replace(replace(title, \"'\", ''), '!', ''), '\"', '') %s %s",
							$query, $this->pdo->likeString($tempTitle), $andYearIn
						)
					);
				}
			}
		}

		return $IMDBCheck === false
				? false
				: (is_numeric($IMDBCheck['imdbid'])
					? (int)$IMDBCheck['imdbid']
					: false
				);
	}

	/**
	 * Try to get an IMDB id from search engines.
	 *
	 * @return boolean
	 */
	protected function imdbIDFromEngines()
	{
		if ($this->googleLimit < 41 && (time() - $this->googleBan) > 600) {
			if ($this->googleSearch() === true) {
				return true;
			}
		}

		if ($this->yahooLimit < 41) {
			if ($this->yahooSearch() === true) {
				return true;
			}
		}

		// Not using this right now because bing's advanced search is not good enough.
		/*if ($this->bingLimit < 41) {
			if ($this->bingSearch() === true) {
				return true;
			}
		}*/

		return false;
	}

	/**
	 * Try to find a IMDB id on google.com
	 *
	 * @return boolean
	 */
	protected function googleSearch()
	{
		$buffer = Misc::getUrl([
						'url' =>
								'https://www.google.com/search?hl=en&as_q=&as_epq=' .
								urlencode(
									$this->currentTitle .
									' ' .
									/** @scrutinizer ignore-type */ $this->currentYear
								) .
								'&as_oq=&as_eq=&as_nlo=&as_nhi=&lr=&cr=&as_qdr=all&as_sitesearch=' .
								urlencode('www.imdb.com/title/') .
								'&as_occt=title&safe=images&tbs=&as_filetype=&as_rights='
				]
		);

		// Make sure we got some data.
		if ($buffer !== false) {
			$this->googleLimit++;

			if (preg_match('/(To continue, please type the characters below)|(- did not match any documents\.)/i', $buffer, $matches)) {
				if (!empty($matches[1])) {
					$this->googleBan = time();
				}
			} else if ($this->doMovieUpdate($buffer, 'Google.com', $this->currentRelID) !== false) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Try to find a IMDB id on bing.com
	 *
	 * @return boolean
	 */
	protected function bingSearch()
	{
		$buffer = Misc::getUrl(
			[
				'url' =>
						'http://www.bing.com/search?q=' .
						urlencode(
							'("' .
							$this->currentTitle .
							'" and "' .
							/** @scrutinizer ignore-type */
							$this->currentYear . '") site:www.imdb.com/title/'
						) .
						'&qs=n&form=QBLH&filt=all'
			]
		);

		if ($buffer !== false) {
			$this->bingLimit++;

			if ($this->doMovieUpdate($buffer, 'Bing.com', $this->currentRelID) !== false) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Try to find a IMDB id on yahoo.com
	 *
	 * @return boolean
	 */
	protected function yahooSearch()
	{
		$buffer = Misc::getUrl(
			[
				'url' =>
					'http://search.yahoo.com/search?n=10&ei=UTF-8&va_vt=title&vo_vt=any&ve_vt=any&vp_vt=any&vf=all&vm=p&fl=0&fr=fp-top&p=' .
					urlencode(
						'' .
						implode('+',
							explode(
								' ',
								preg_replace(
									'/\s+/',
									' ',
									preg_replace(
										'/\W/',
										' ',
										$this->currentTitle
									)
								)
							)
						) .
						'+' .
						/** @scrutinizer ignore-type */ $this->currentYear
					) .
					'&vs=' .
					urlencode('www.imdb.com/title/')
			]
		);

		if ($buffer !== false) {
			$this->yahooLimit++;

			if ($this->doMovieUpdate($buffer, 'Yahoo.com', $this->currentRelID) !== false) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Parse a movie name from a release search name.
	 *
	 * @param string $releaseName
	 *
	 * @return boolean
	 */
	protected function parseMovieSearchName($releaseName)
	{
		$name = $year = '';
		$followingList = '[^\w]((1080|480|720)p|AC3D|Directors([^\w]CUT)?|DD5\.1|(DVD|BD|BR)(Rip)?|BluRay|divx|HDTV|iNTERNAL|LiMiTED|(Real\.)?Proper|RE(pack|Rip)|Sub\.?(fix|pack)|Unrated|WEB-DL|(x|H)[-._ ]?264|xvid)[^\w]';

		/* Initial scan of getting a year/name.
		 * [\w. -]+ Gets 0-9a-z. - characters, most scene movie titles contain these chars.
		 * ie: [61420]-[FULL]-[a.b.foreignEFNet]-[ Coraline.2009.DUTCH.INTERNAL.1080p.BluRay.x264-VeDeTT ]-[21/85] - "vedett-coralien-1080p.r04" yEnc
		 * Then we look up the year, (19|20)\d\d, so $matches[1] would be Coraline $matches[2] 2009
		 */
		if (preg_match('/(?P<name>[\w. -]+)[^\w](?P<year>(19|20)\d\d)/i', $releaseName, $matches)) {
			$name = $matches['name'];
			$year = $matches['year'];

			/* If we didn't find a year, try to get a name anyways.
			 * Try to look for a title before the $followingList and after anything but a-z0-9 two times or more (-[ for example)
			 */
		} else if (preg_match('/([^\w]{2,})?(?P<name>[\w .-]+?)' . $followingList . '/i', $releaseName, $matches)) {
			$name = $matches['name'];
		}

		// Check if we got something.
		if ($name !== '') {

			// If we still have any of the words in $followingList, remove them.
			$name = preg_replace('/' . $followingList . '/i', ' ', $name);
			// Remove periods, underscored, anything between parenthesis.
			$name = preg_replace('/\(.*?\)|[._]/i', ' ', $name);
			// Finally remove multiple spaces and trim leading spaces.
			$name = trim(preg_replace('/\s{2,}/', ' ', $name));
			// Check if the name is long enough and not just numbers.
			if (strlen($name) > 4 && !preg_match('/^\d+$/', $name)) {
				if ($this->debug && $this->echooutput) {
					ColorCLI::doEcho("DB name: {$releaseName}", true);
				}
				$this->currentTitle = $name;
				$this->currentYear  = ($year === '' ? false : $year);
				return true;
			}
		}
		return false;
	}

	/**
	 * Get upcoming movies.
	 *
	 * @param        $type
	 * @param string $source
	 *
	 * @return array|boolean
	 */
	public function getUpcoming($type, $source = 'rottentomato')
	{
		$list = $this->pdo->queryOneRow(
			sprintf('SELECT * FROM upcoming_releases WHERE source = %s AND typeid = %d', $this->pdo->escapeString($source), $type)
		);
		if ($list === false) {
			$this->updateUpcoming();
			$list = $this->pdo->queryOneRow(
				sprintf('SELECT * FROM upcoming_releases WHERE source = %s AND typeid = %d', $this->pdo->escapeString($source), $type)
			);
		}
		return $list;
	}

	/**
	 * Update upcoming movies.
	 * @throws \Exception
	 */
	public function updateUpcoming()
	{
		if ($this->echooutput) {
			ColorCLI::doEcho(ColorCLI::header('Updating movie schedule using rotten tomatoes.'));
		}

		$rt = new RottenTomato(Settings::value('APIs..rottentomatokey'));

		if ($rt instanceof RottenTomato) {

			$this->_getRTData('boxoffice', $rt);
			$this->_getRTData('theaters', $rt);
			$this->_getRTData('opening', $rt);
			$this->_getRTData('upcoming', $rt);
			$this->_getRTData('dvd', $rt);

			if ($this->echooutput) {
				ColorCLI::doEcho(ColorCLI::header('Updated successfully.'));
			}

		} else if ($this->echooutput) {
			ColorCLI::doEcho(ColorCLI::header('Error retrieving your RottenTomato API Key. Exiting...' . PHP_EOL));
		}
	}

	/**
	 * @param string $operation
	 * @param \nzedb\RottenTomato $rt
	 */
	protected function _getRTData($rt, $operation = '')
	{
		$count = 0;
		$check = false;

		do {
			$count++;

			switch ($operation) {
				case 'boxoffice':
					$data = $rt->getBoxOffice();
					$update = Movie::SRC_BOXOFFICE;
					break;
				case 'theaters':
					$data = $rt->getInTheaters();
					$update = Movie::SRC_INTHEATRE;
					break;
				case 'opening':
					$data = $rt->getOpening();
					$update = Movie::SRC_OPENING;
					break;
				case 'upcoming':
					$data = $rt->getUpcoming();
					$update = Movie::SRC_UPCOMING;
					break;
				case 'dvd':
					$data = $rt->getDVDReleases();
					$update = Movie::SRC_DVD;
					break;
				default:
					$data = false;
					$update = 0;
			}

			if ($data !== false && $data !== '') {
				$test = @json_decode($data);
				if ($test !== null) {
					$count = 2;
					$check = true;
				}
			}

		} while ($count < 2);

		if ($check === true) {

			$success = $this->updateInsUpcoming('rottentomato', $update, $data);

			if ($this->echooutput) {
				if ($success !== false) {
					ColorCLI::doEcho(ColorCLI::header(sprintf('Added/updated movies to the %s list.', $operation)));
				} else {
					ColorCLI::doEcho(ColorCLI::primary(sprintf('No new updates for %s list.', $operation)));
				}
			}

		} else {
			exit(PHP_EOL . ColorCLI::error('Unable to fetch from Rotten Tomatoes, verify your API Key.' . PHP_EOL));
		}
	}

	/**
	 * Update upcoming table.
	 *
	 * @param string $source
	 * @param $type
	 * @param string|false $info
	 *
	 * @return boolean|integer
	 */
	protected function updateInsUpcoming($source, $type, $info)
	{
		return $this->pdo->queryExec(
				sprintf('
				INSERT INTO upcoming_releases (source, typeid, info, updateddate)
				VALUES (%s, %d, %s, NOW())
				ON DUPLICATE KEY UPDATE info = %s',
				$this->pdo->escapeString($source),
				$type,
				$this->pdo->escapeString($info),
				$this->pdo->escapeString($info)
				)
		);
	}

	/**
	 * @return array
	 */
	public function getGenres()
	{
		return [
			'Action',
			'Adventure',
			'Animation',
			'Biography',
			'Comedy',
			'Crime',
			'Documentary',
			'Drama',
			'Family',
			'Fantasy',
			'Film-Noir',
			'Game-Show',
			'History',
			'Horror',
			'Music',
			'Musical',
			'Mystery',
			'News',
			'Reality-TV',
			'Romance',
			'Sci-Fi',
			'Sport',
			'Talk-Show',
			'Thriller',
			'War',
			'Western'
		];
	}
}
