<?php
require_once nZEDb_LIBS . 'TMDb.php';

use nzedb\db\DB;
use nzedb\utility;

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
	 * Current title being passed through various sites/api's.
	 * @var string
	 */
	protected $currentTitle = '';

	/**
	 * Current year of parsed search name.
	 * @var string
	 */
	protected $currentYear  = '';

	/**
	 * Current release id of parsed search name.
	 *
	 * @var string
	 */
	protected $currentRelID = '';

	/**
	 * @var Debugging
	 */
	protected $debugging;

	/**
	 * @var bool
	 */
	protected $debug;

	/**
	 * Use search engines to find IMDB id's.
	 * @var bool
	 */
	protected $searchEngines;

	/**
	 * How many times have we hit google this session.
	 * @var int
	 */
	protected $googleLimit = 0;

	/**
	 * If we are temp banned from google, set time we were banned here, try again after 10 minutes.
	 * @var int
	 */
	protected $googleBan = 0;

	/**
	 * How many times have we hit bing this session.
	 *
	 * @var int
	 */
	protected $bingLimit = 0;

	/**
	 * How many times have we hit yahoo this session.
	 *
	 * @var int
	 */
	protected $yahooLimit = 0;

	/**
	 * @var int
	 */
	protected $showPasswords;

	/**
	 * @var bool|stdClass
	 */
	protected $site;

	/**
	 * @var ReleaseImage
	 */
	protected $releaseImage;

	/**
	 * @var TMDb
	 */
	protected $tmdb;

	/**
	 * Language to fetch from IMDB.
	 * @var string
	 */
	protected $imdbLanguage;

	/**
	 * @param bool $echoOutput
	 */
	public function __construct($echoOutput = false)
	{
		$this->c = new ColorCLI();
		$this->db = new DB();
		$this->releaseImage = new ReleaseImage();
		$s = new Sites();
		$this->site = $s->get();

		$this->imdbLanguage = (!empty($this->site->imdblanguage) ? $this->site->imdblanguage : 'en');

		$this->tmdb = new TMDb($this->site->tmdbkey, $this->imdbLanguage);

		$this->fanartapikey = $this->site->fanarttvkey;
		$this->imdburl = ($this->site->imdburl == 0 ? false : true);
		$this->movieqty = (!empty($this->site->maximdbprocessed) ? $this->site->maximdbprocessed : 100);
		$this->searchEngines = true;
		$this->showPasswords = (!empty($this->site->showpasswordedrelease) ? $this->site->showpasswordedrelease : 0);

		$this->debug = nZEDb_DEBUG;
		$this->echooutput = ($echoOutput && nZEDb_ECHOCLI);
		$this->imgSavePath = nZEDb_COVERS . 'movies' . DS;
		$this->service = '';

		if (nZEDb_DEBUG || nZEDb_LOGGING) {
			$this->debug = true;
			$this->debugging = new Debugging('Movie');
		}
	}

	/**
	 * Get info for a IMDB id.
	 *
	 * @param int $imdbId
	 *
	 * @return array|bool
	 */
	public function getMovieInfo($imdbId)
	{
		return $this->db->queryOneRow(sprintf("SELECT * FROM movieinfo WHERE imdbid = %d", $imdbId));
	}

	/**
	 * Get info for multiple IMDB id's.
	 *
	 * @param string $imdbIDs
	 *
	 * @return array
	 */
	public function getMovieInfoMultiImdb($imdbIDs)
	{
		return $this->db->query(
			sprintf("
				SELECT DISTINCT movieinfo.*, releases.imdbid AS relimdb
				FROM movieinfo
				LEFT OUTER JOIN releases ON releases.imdbid = movieinfo.imdbid
				WHERE movieinfo.imdbid IN (%s)",
				str_replace(
					',,',
					',',
					str_replace(
						array('(,', ' ,', ', )', ',)'),
						'',
						implode(',', $imdbIDs)
					)
				)
			)
		);
	}

	/**
	 * Get movies for movie-list admin page.
	 *
	 * @param int $start
	 * @param int $num
	 *
	 * @return array
	 */
	public function getRange($start, $num)
	{
		return $this->db->query(
			sprintf('
				SELECT *
				FROM movieinfo
				ORDER BY createddate DESC %s',
				($start === false ? '' : ' LIMIT ' . $num . ' OFFSET ' . $start)
			)
		);
	}

	/**
	 * Get count of movies for movie-list admin page.
	 *
	 * @return int
	 */
	public function getCount()
	{
		$res = $this->db->queryOneRow('SELECT COUNT(id) AS num FROM movieinfo');
		return ($res === false ? 0 : $res['num']);
	}

	/**
	 * Get count of movies for movies browse page.
	 *
	 * @param       $cat
	 * @param       $maxAge
	 * @param array $excludedCats
	 *
	 * @return int
	 */
	public function getMovieCount($cat, $maxAge = -1, $excludedCats = array())
	{
		$catSearch = $this->formCategorySearchSQL($cat);

		$res = $this->db->queryOneRow(
			sprintf("
				SELECT COUNT(DISTINCT r.imdbid) AS num
				FROM releases r
				INNER JOIN movieinfo m ON m.imdbid = r.imdbid
				WHERE r.nzbstatus = 1
				AND m.cover = 1
				AND m.title != ''
				AND r.passwordstatus <= %d
				AND %s %s %s %s ",
				$this->showPasswords,
				$this->getBrowseBy(),
				$catSearch,
				($maxAge > 0
					?
					'AND r.postdate > NOW() - INTERVAL ' .
					($this->db->dbSystem() === 'mysql'
						? $maxAge . 'DAY '
						: "'" . $maxAge . "DAYS' "
					)
					: ''
				),
				(count($excludedCats) > 0 ? ' AND r.categoryid NOT IN (' . implode(',', $excludedCats) . ')' : '')
			)
		);

		return ($res === false ? 0 : $res['num']);
	}

	/**
	 * Get movie releases with covers for movie browse page.
	 *
	 * @param       $cat
	 * @param       $start
	 * @param       $num
	 * @param       $orderBy
	 * @param       $maxAge
	 * @param array $excludedCats
	 *
	 * @return bool
	 */
	public function getMovieRange($cat, $start, $num, $orderBy, $maxAge = -1, $excludedCats = array())
	{
		$order = $this->getMovieOrder($orderBy);
		if ($this->db->dbSystem() === 'mysql') {
			$sql = sprintf("
				SELECT
				GROUP_CONCAT(r.id ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_id,
				GROUP_CONCAT(r.rarinnerfilecount ORDER BY r.postdate DESC SEPARATOR ',') as grp_rarinnerfilecount,
				GROUP_CONCAT(r.haspreview ORDER BY r.postdate DESC SEPARATOR ',') AS grp_haspreview,
				GROUP_CONCAT(r.passwordstatus ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_password,
				GROUP_CONCAT(r.guid ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_guid,
				GROUP_CONCAT(rn.id ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_nfoid,
				GROUP_CONCAT(groups.name ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_grpname,
				GROUP_CONCAT(r.searchname ORDER BY r.postdate DESC SEPARATOR '#') AS grp_release_name,
				GROUP_CONCAT(r.postdate ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_postdate,
				GROUP_CONCAT(r.size ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_size,
				GROUP_CONCAT(r.totalpart ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_totalparts,
				GROUP_CONCAT(r.comments ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_comments,
				GROUP_CONCAT(r.grabs ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_grabs,
				m.*, groups.name AS group_name, rn.id as nfoid FROM releases r
				LEFT OUTER JOIN groups ON groups.id = r.groupid
				LEFT OUTER JOIN releasenfo rn ON rn.releaseid = r.id
				INNER JOIN movieinfo m ON m.imdbid = r.imdbid
				WHERE r.nzbstatus = 1 AND m.cover = 1 AND m.title != '' AND
				r.passwordstatus <= %d AND %s %s %s %s
				GROUP BY m.imdbid ORDER BY %s %s %s",
				$this->showPasswords,
				$this->getBrowseBy(),
				$this->formCategorySearchSQL($cat),
				($maxAge > 0
					? 'AND r.postdate > NOW() - INTERVAL ' . $maxAge . 'DAY '
					: ''
				),
				(count($excludedCats) > 0 ? ' AND r.categoryid NOT IN (' . implode(',', $excludedCats) . ')' : ''),
				$order[0],
				$order[1],
				($start === false ? '' : ' LIMIT ' . $num . ' OFFSET ' . $start)
			);
		} else {
			$sql = sprintf("
				SELECT STRING_AGG(r.id::text, ',' ORDER BY r.postdate DESC) AS grp_release_id,
				STRING_AGG(r.rarinnerfilecount::text, ',' ORDER BY r.postdate DESC) as grp_rarinnerfilecount,
				STRING_AGG(r.haspreview::text, ',' ORDER BY r.postdate DESC) AS grp_haspreview,
				STRING_AGG(r.passwordstatus::text, ',' ORDER BY r.postdate) AS grp_release_password,
				STRING_AGG(r.guid, ',' ORDER BY r.postdate DESC) AS grp_release_guid,
				STRING_AGG(rn.id::text, ',' ORDER BY r.postdate DESC) AS grp_release_nfoid,
				STRING_AGG(groups.name, ',' ORDER BY r.postdate DESC) AS grp_release_grpname,
				STRING_AGG(r.searchname, '#' ORDER BY r.postdate) AS grp_release_name,
				STRING_AGG(r.postdate::text, ',' ORDER BY r.postdate DESC) AS grp_release_postdate,
				STRING_AGG(r.size::text, ',' ORDER BY r.postdate DESC) AS grp_release_size,
				STRING_AGG(r.totalpart::text, ',' ORDER BY r.postdate DESC) AS grp_release_totalparts,
				STRING_AGG(r.comments::text, ',' ORDER BY r.postdate DESC) AS grp_release_comments,
				STRING_AGG(r.grabs::text, ',' ORDER BY r.postdate DESC) AS grp_release_grabs,
				m.*, groups.name AS group_name,
				rn.id as nfoid
				FROM releases r
				LEFT OUTER JOIN groups ON groups.id = r.groupid
				INNER JOIN movieinfo m ON m.imdbid = r.imdbid AND m.title != ''
				LEFT OUTER JOIN releasenfo rn ON rn.releaseid = r.id AND rn.nfo IS NOT NULL
				WHERE r.nzbstatus = 1
				AND r.passwordstatus <= %s
				AND %s %s %s %s
				GROUP BY m.imdbid, m.id, groups.name, rn.id
				ORDER BY %s %s %s",
				$this->showPasswords,
				$this->getBrowseBy(),
				$this->formCategorySearchSQL($cat),
				($maxAge > 0
					?
					'AND r.postdate > NOW() - INTERVAL ' .  "'" . $maxAge . "DAYS' "
					: ''
				),
				(count($excludedCats) > 0 ? ' AND r.categoryid NOT IN (' . implode(',', $excludedCats) . ')' : ''),
				$order[0],
				$order[1],
				($start === false ? '' : ' LIMIT ' . $num . ' OFFSET ' . $start)
			);
		}
		return $this->db->queryDirect($sql);
	}

	/**
	 * Form category search SQL.
	 *
	 * @param $cat
	 *
	 * @return string
	 */
	protected function formCategorySearchSQL($cat)
	{
		$catSearch = '';
		if (count($cat) > 0 && $cat[0] != -1) {
			$catSearch = '(';
			$Category = new Category();
			foreach ($cat as $category) {
				if ($category != -1) {

					if ($Category->isParent($category)) {
						$children = $Category->getChildren($category);
						$chList = '-99';
						foreach ($children as $child) {
							$chList .= ', ' . $child['id'];
						}

						if ($chList != '-99') {
							$catSearch .= ' r.categoryid IN (' . $chList . ') OR ';
						}
					} else {
						$catSearch .= sprintf(' r.categoryid = %d OR ', $category);
					}
				}
			}
			$catSearch .= '1=2)';
		}
		return $catSearch;
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
		$orderArr = explode('_', (($orderBy == '') ? 'MAX(r.postdate)' : $orderBy));
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

		return array($orderField, ((isset($orderArr[1]) && preg_match('/^asc|desc$/i', $orderArr[1])) ? $orderArr[1] : 'desc'));
	}

	/**
	 * Order types for movies page.
	 *
	 * @return array
	 */
	public function getMovieOrdering()
	{
		return array('title_asc', 'title_desc', 'year_asc', 'year_desc', 'rating_asc', 'rating_desc');
	}

	/**
	 * @return string
	 */
	protected function getBrowseBy()
	{
		$browseBy = ' ';
		$browseByArr = array('title', 'director', 'actors', 'genre', 'rating', 'year', 'imdb');
		foreach ($browseByArr as $bb) {
			if (isset($_REQUEST[$bb]) && !empty($_REQUEST[$bb])) {
				$bbv = stripslashes($_REQUEST[$bb]);
				if ($bb === 'rating') {
					$bbv .= '.';
				}
				if ($bb === 'imdb') {
					$browseBy .= 'm.' . $bb . 'id = ' . $bbv . ' AND ';
				} else {
					$browseBy .= 'm.' . $bb . ' LIKE (' . $this->db->escapeString('%' . $bbv . '%') . ') AND ';
				}
			}
		}
		return $browseBy;
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
		if (!isset($data[$field]) || $data[$field] == '') {
			return '';
		}

		$tmpArr = explode(', ', $data[$field]);
		$newArr = array();
		$i = 0;
		foreach ($tmpArr as $ta) {
			if ($i > 5) {
				break;
			} //only use first 6
			$newArr[] = '<a href="' . WWW_TOP . '/movies?' . $field . '=' . urlencode($ta) . '" title="' . $ta . '">' . $ta . '</a>';
			$i++;
		}
		return implode(', ', $newArr);
	}

	/**
	 * Update movie on movie-edit page.
	 *
	 * @param $id
	 * @param $title
	 * @param $tagLine
	 * @param $plot
	 * @param $year
	 * @param $rating
	 * @param $genre
	 * @param $director
	 * @param $actors
	 * @param $language
	 * @param $cover
	 * @param $backdrop
	 */
	public function update(
		$id = '', $title = '', $tagLine = '', $plot = '', $year = '', $rating = '', $genre = '', $director = '',
		$actors = '', $language = '', $cover = '', $backdrop = ''
	)
	{
		if (!empty($id)) {

			$this->db->queryExec(
				sprintf("
					UPDATE movieinfo
					SET %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, %d, updateddate = NOW()
					WHERE imdbid = %d",
					(empty($title)    ? '' : 'title = '    . $this->db->escapeString($title)),
					(empty($tagLine)  ? '' : 'tagline = '  . $this->db->escapeString($tagLine)),
					(empty($plot)     ? '' : 'plot = '     . $this->db->escapeString($plot)),
					(empty($year)     ? '' : 'year = '     . $this->db->escapeString($year)),
					(empty($rating)   ? '' : 'rating = '   . $this->db->escapeString($rating)),
					(empty($genre)    ? '' : 'genre = '    . $this->db->escapeString($genre)),
					(empty($director) ? '' : 'director = ' . $this->db->escapeString($director)),
					(empty($actors)   ? '' : 'actors = '   . $this->db->escapeString($actors)),
					(empty($language) ? '' : 'language = ' . $this->db->escapeString($language)),
					(empty($cover)    ? '' : 'cover = '    . $cover),
					(empty($backdrop) ? '' : 'backdrop = ' . $backdrop),
					$id
				)
			);
		}
	}

	/**
	 * Check if a variable is set and not a empty string.
	 *
	 * @param $variable
	 *
	 * @return string
	 */
	protected function checkVariable(&$variable)
	{
		if (isset($variable) && $variable != '') {
			return true;
		}
		return false;
	}

	/**
	 * Returns a tmdb or imdb variable, the one that is set. Empty string if both not set.
	 *
	 * @param string $variable1
	 * @param string $variable2
	 *
	 * @return string
	 */
	protected function  setTmdbImdbVar(&$variable1, &$variable2)
	{
		if ($this->checkVariable($variable1)) {
			return $variable1;
		} elseif ($this->checkVariable($variable2)) {
			return $variable2;
		}
		return '';
	}

	/**
	 * Fetch IMDB/TMDB info for the movie.
	 *
	 * @param $imdbId
	 *
	 * @return bool
	 */
	public function updateMovieInfo($imdbId)
	{
		if ($this->echooutput && $this->service !== '') {
			$this->c->doEcho($this->c->primary("Fetching IMDB info from TMDB using IMDB ID: " . $imdbId));
		}

		// Check TMDB for IMDB info.
		$tmdb = $this->fetchTMDBProperties($imdbId);

		// Check IMDB for movie info.
		$imdb = $this->fetchIMDBProperties($imdbId);
		if (!$imdb && !$tmdb) {
			return false;
		}

		// Check FanArt.tv for background images.
		$fanart = $this->fetchFanartTVProperties($imdbId);

		$mov = array();

		$mov['cover'] = $mov['backdrop'] = $movieID = 0;
		$mov['type'] = $mov['director'] = $mov['actors'] = $mov['language'] = '';

		$mov['imdb_id'] = $imdbId;
		$mov['tmdb_id'] = (!isset($tmdb['tmdb_id']) || $tmdb['tmdb_id'] == '') ? 'NULL' : $tmdb['tmdb_id'];

		// Prefer FanArt.tv cover over TMDB. And TMDB over IMDB.
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

		$mov['title']   = $this->setTmdbImdbVar($imdb['title']  , $tmdb['title']);
		$mov['rating']  = $this->setTmdbImdbVar($imdb['rating'] , $tmdb['rating']);
		$mov['plot']    = $this->setTmdbImdbVar($imdb['plot']   , $tmdb['plot']);
		$mov['tagline'] = $this->setTmdbImdbVar($imdb['tagline'], $tmdb['tagline']);
		$mov['year']    = $this->setTmdbImdbVar($imdb['year']   , $tmdb['year']);
		$mov['genre']   = $this->setTmdbImdbVar($imdb['genre']  , $tmdb['genre']);

		if ($this->checkVariable($imdb['type'])) {
			$mov['type'] = $imdb['type'];
		}

		if ($this->checkVariable($imdb['director'])) {
			$mov['director'] = (is_array($imdb['director'])) ? implode(', ', array_unique($imdb['director'])) : $imdb['director'];
		}

		if ($this->checkVariable($imdb['actors'])) {
			$mov['actors'] = (is_array($imdb['actors'])) ? implode(', ', array_unique($imdb['actors'])) : $imdb['actors'];
		}

		if ($this->checkVariable($imdb['language'])) {
			$mov['language'] = (is_array($imdb['language'])) ? implode(', ', array_unique($imdb['language'])) : $imdb['language'];
		}

		if (is_array($mov['genre'])) {
			$mov['genre'] = implode(', ', array_unique($mov['genre']));
		}

		if (is_array($mov['type'])) {
			$mov['type'] = implode(', ', array_unique($mov['type']));
		}

		$mov['title']    = html_entity_decode($mov['title']   , ENT_QUOTES, 'UTF-8');
		$mov['plot']     = html_entity_decode($mov['plot']    , ENT_QUOTES, 'UTF-8');
		$mov['tagline']  = html_entity_decode($mov['tagline'] , ENT_QUOTES, 'UTF-8');
		$mov['genre']    = html_entity_decode($mov['genre']   , ENT_QUOTES, 'UTF-8');
		$mov['director'] = html_entity_decode($mov['director'], ENT_QUOTES, 'UTF-8');
		$mov['actors']   = html_entity_decode($mov['actors']  , ENT_QUOTES, 'UTF-8');
		$mov['language'] = html_entity_decode($mov['language'], ENT_QUOTES, 'UTF-8');

		$mov['type']    = html_entity_decode(ucwords(preg_replace('/[\.\_]/', ' ', $mov['type'])), ENT_QUOTES, 'UTF-8');

		$mov['title'] = str_replace(array('/', '\\'), '', $mov['title']);
		if ($this->db->dbSystem() === 'mysql') {
			$movieID = $this->db->queryInsert(
				sprintf("
					INSERT INTO movieinfo
						(imdbid, tmdbid, title, rating, tagline, plot, year, genre, type,
						director, actors, language, cover, backdrop, createddate, updateddate)
					VALUES
						(%d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, %d, NOW(), NOW())
					ON DUPLICATE KEY UPDATE
						imdbid = %d, tmdbid = %s, title = %s, rating = %s, tagline = %s, plot = %s, year = %s, genre = %s,
						type = %s, director = %s, actors = %s, language = %s, cover = %d, backdrop = %d, updateddate = NOW()",
					$mov['imdb_id'],
					$mov['tmdb_id'],
					$this->db->escapeString($mov['title']),
					$this->db->escapeString($mov['rating']),
					$this->db->escapeString($mov['tagline']),
					$this->db->escapeString($mov['plot']),
					$this->db->escapeString($mov['year']),
					$this->db->escapeString(substr($mov['genre'], 0, 64)),
					$this->db->escapeString($mov['type']),
					$this->db->escapeString($mov['director']),
					$this->db->escapeString($mov['actors']),
					$this->db->escapeString(substr($mov['language'], 0, 64)),
					$mov['cover'],
					$mov['backdrop'],
					$mov['imdb_id'],
					$mov['tmdb_id'],
					$this->db->escapeString($mov['title']),
					$this->db->escapeString($mov['rating']),
					$this->db->escapeString($mov['tagline']),
					$this->db->escapeString($mov['plot']),
					$this->db->escapeString($mov['year']),
					$this->db->escapeString(substr($mov['genre'], 0, 64)),
					$this->db->escapeString($mov['type']),
					$this->db->escapeString($mov['director']),
					$this->db->escapeString($mov['actors']),
					$this->db->escapeString(substr($mov['language'], 0, 64)),
					$mov['cover'],
					$mov['backdrop']
				)
			);
		} else if ($this->db->dbSystem() === 'pgsql') {
			$ckID = $this->db->queryOneRow(sprintf('SELECT id FROM movieinfo WHERE imdbid = %d', $mov['imdb_id']));
			if ($ckID === false) {
				$movieID = $this->db->queryInsert(
					sprintf("
						INSERT INTO movieinfo
							(imdbid, tmdbid, title, rating, tagline, plot, year, genre, type,
							director, actors, language, cover, backdrop, createddate, updateddate)
						VALUES
							(%d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, %d, NOW(), NOW())",
						$mov['imdb_id'],
						$mov['tmdb_id'],
						$this->db->escapeString($mov['title']),
						$this->db->escapeString($mov['rating']),
						$this->db->escapeString($mov['tagline']),
						$this->db->escapeString($mov['plot']),
						$this->db->escapeString($mov['year']),
						$this->db->escapeString($mov['genre']),
						$this->db->escapeString($mov['type']),
						$this->db->escapeString($mov['director']),
						$this->db->escapeString($mov['actors']),
						$this->db->escapeString($mov['language']),
						$mov['cover'],
						$mov['backdrop']
					)
				);
			} else {
				$movieID = $ckID['id'];
				$this->db->queryExec(
					sprintf('
						UPDATE movieinfo SET
							tmdbid = %d, title = %s, rating = %s, tagline = %s, plot = %s, year = %s,
							genre = %s, type = %s, director = %s, actors = %s, language = %s, cover = %d,
							backdrop = %d, updateddate = NOW()
						WHERE id = %d',
						$mov['tmdb_id'],
						$this->db->escapeString($mov['title']),
						$this->db->escapeString($mov['rating']),
						$this->db->escapeString($mov['tagline']),
						$this->db->escapeString($mov['plot']),
						$this->db->escapeString($mov['year']),
						$this->db->escapeString($mov['genre']),
						$this->db->escapeString($mov['type']),
						$this->db->escapeString($mov['director']),
						$this->db->escapeString($mov['actors']),
						$this->db->escapeString($mov['language']),
						$mov['cover'],
						$mov['backdrop'],
						$movieID)
				);
			}
		}

		if ($this->echooutput && $this->service !== '') {
			$this->c->doEcho(
				$this->c->headerOver(($movieID !== 0 ? 'Added/updated movie: ' : 'Nothing to update for movie: ')) .
				$this->c->primary($mov['title'] .
					' (' .
					$mov['year'] .
					') - ' .
					$mov['imdb_id']
				)
			);
		}

		return ($movieID === 0 ? false : true);
	}

	/**
	 * Fetch FanArt.tv backdrop / cover / title.
	 *
	 * @param $imdbId
	 *
	 * @return bool|array
	 */
	protected function fetchFanartTVProperties($imdbId)
	{
		if ($this->fanartapikey != '') {
			$buffer = nzedb\utility\getUrl('http://api.fanart.tv/webservice/movie/' . $this->fanartapikey . '/tt' . $imdbId . '/xml/');
			if ($buffer !== false) {
				$art = @simplexml_load_string($buffer);
				if ($art !== false) {
					if (isset($art->movie->moviebackgrounds->moviebackground[0]['url'])) {
						$ret['backdrop'] = $art->movie->moviebackgrounds->moviebackground[0]['url'];
					} else if (isset($art->movie->moviethumbs->moviethumb[0]['url'])) {
						$ret['backdrop'] = $art->movie->moviethumbs->moviethumb[0]['url'];
					}

					if (isset($art->movie->movieposters->movieposter[0]['url'])) {
						$ret['cover'] = $art->movie->movieposters->movieposter[0]['url'];
					}

					if (isset($ret['backdrop']) && isset($ret['cover'])) {

						$ret['title'] = $imdbId;
						if (isset($art->movie['name'])) {
							$ret['title'] = $art->movie['name'];
						}
						if ($this->echooutput) {
							$this->c->doEcho($this->c->alternateOver("Fanart Found ") . $this->c->headerOver($ret['title']));
						}
						return $ret;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Fetch info for IMDB id from TMDB.
	 *
	 * @param      $imdbId
	 * @param bool $text
	 *
	 * @return array|bool
	 */
	public function fetchTMDBProperties($imdbId, $text = false)
	{
		$lookupId = ($text === false ? 'tt' . $imdbId : $imdbId);

		try {
			$tmdbLookup = $this->tmdb->getMovie($lookupId);
		} catch (exception $e) {
			return false;
		}

		if (!$tmdbLookup || (isset($tmdbLookup['status_code']) && $tmdbLookup['status_code'] !== 1)) {
			return false;
		}

		$ret = array();
		$ret['title'] = $tmdbLookup['title'];

		if ($this->currentTitle !== '') {
			// Check the similarity.
			similar_text($this->currentTitle, $ret['title'], $percent);
			if ($percent < 40) {
				if ($this->debug) {
					$this->debugging->start(
						'fetchTmdbProperties',
						'Found (' .
						$ret['title'] .
						') from TMDB, but it\'s only ' .
						$percent .
						'% similar to (' .
						$this->currentTitle . ')',
						5
					);
				}
				return false;
			}
		}

		$ret['tmdb_id'] = $tmdbLookup['id'];
		$ImdbID = str_replace('tt', '', $tmdbLookup['imdb_id']);
		$ret['imdb_id'] = $ImdbID;
		if (isset($tmdbLookup['vote_average'])) {
			$ret['rating'] = ($tmdbLookup['vote_average'] == 0) ? '' : $tmdbLookup['vote_average'];
		}
		if (isset($tmdbLookup['overview'])) {
			$ret['plot'] = $tmdbLookup['overview'];
		}
		if (isset($tmdbLookup['tagline'])) {
			$ret['tagline'] = $tmdbLookup['tagline'];
		}
		if (isset($tmdbLookup['release_date'])) {
			$ret['year'] = date("Y", strtotime($tmdbLookup['release_date']));
		}
		if (isset($tmdbLookup['genres']) && sizeof($tmdbLookup['genres']) > 0) {
			$genres = array();
			foreach ($tmdbLookup['genres'] as $genre) {
				$genres[] = $genre['name'];
			}
			$ret['genre'] = $genres;
		}
		if (isset($tmdbLookup['poster_path']) && sizeof($tmdbLookup['poster_path']) > 0) {
			$ret['cover'] = "http://image.tmdb.org/t/p/w185" . $tmdbLookup['poster_path'];
		}
		if (isset($tmdbLookup['backdrop_path']) && sizeof($tmdbLookup['backdrop_path']) > 0) {
			$ret['backdrop'] = "http://image.tmdb.org/t/p/original" . $tmdbLookup['backdrop_path'];
		}
		if ($this->echooutput) {
			$this->c->doEcho($this->c->primaryOver("TMDb Found ") . $this->c->headerOver($ret['title']), true);
		}
		return $ret;
	}

	/**
	 * @param $imdbId
	 *
	 * @return array|bool
	 */
	protected function fetchIMDBProperties($imdbId)
	{
		$matches = $match = $hit = $results = '';
		$imdb_regex = array(
			'title' => '/<title>(.*?)\s?\(.*?<\/title>/i',
			'tagline' => '/taglines:<\/h4>\s([^<]+)/i',
			'plot' => '/<p itemprop="description">\s*?(.*?)\s*?<\/p>/i',
			'rating' => '/"ratingValue">([\d.]+)<\/span>/i',
			'year' => '/<title>.*?\(.*?(\d{4}).*?<\/title>/i',
			'cover' => '/<link rel=\'image_src\' href="(http:\/\/ia\.media-imdb\.com.+\.jpg)">/'
		);

		$imdb_regex_multi = array(
			'genre' => '/href="\/genre\/(.*?)\?/i',
			'language' => '/<a href="\/language\/.+\'url\'>(.+)<\/a>/i',
			'type' => '/<meta property=\'og\:type\' content=\"(.+)\" \/>/i'
		);


		$buffer =
			nzedb\utility\getUrl(
				'http://' . ($this->imdburl === false ? 'www' : 'akas') . '.imdb.com/title/tt' . $imdbId . '/',
				'get',
				'',
				(!empty($this->site->imdblanguage) ? $this->site->imdblanguage : 'en'),
				false,
				'Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) ' .
				'Version/4.0.4 Mobile/7B334b Safari/531.21.102011-10-16 20:23:10', 'foo=bar'
			);

		if ($buffer !== false) {
			$ret = array();
			foreach ($imdb_regex as $field => $regex) {
				if (preg_match($regex, $buffer, $matches)) {
					$match = $matches[1];
					$match1 = strip_tags(trim(rtrim($match)));
					$ret[$field] = $match1;
				}
			}

			foreach ($imdb_regex_multi as $field => $regex) {
				if (preg_match_all($regex, $buffer, $matches)) {
					$match2 = $matches[1];
					$match3 = array_map("trim", $match2);
					$ret[$field] = $match3;
				}
			}

			if ($this->currentTitle !== '' && isset($ret['title'])) {
				// Check the similarity.
				similar_text($this->currentTitle, $ret['title'], $percent);
				if ($percent < 40) {
					if ($this->debug) {
						$this->debugging->start(
							'fetchImdbProperties',
							'Found (' .
							$ret['title'] .
							') from IMDB, but it\'s only ' .
							$percent .
							'% similar to (' .
							$this->currentTitle . ')',
							5
						);
					}
					return false;
				}
			}

			// Actors.
			if (preg_match('/<table class="cast_list">(.+)<\/table>/s', $buffer, $hit)) {
				if (preg_match_all('/<a.*?href="\/name\/(nm\d{1,8})\/.+"name">(.+)<\/span>/i', $hit[0], $results, PREG_PATTERN_ORDER)) {
					$ret['actors'] = $results[2];
				}
			}

			// Directors.
			if (preg_match('/Directors?:([\s]+)?<\/h4>(.+)<\/div>/sU', $buffer, $hit)) {
				if (preg_match_all('/"name">(.*?)<\/span>/is', $hit[0], $results, PREG_PATTERN_ORDER)) {
					$ret['director'] = $results[1];
				}
			}
			if ($this->echooutput && isset($ret['title'])) {
				$this->c->doEcho($this->c->headerOver("IMDb Found ") . $this->c->primaryOver($ret['title']), true);
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
	 * @param int    $id           ID of the release.
	 * @param int    $processImdb  To get IMDB info on this IMDB id or not.
	 *
	 * @return string
	 */
	public function doMovieUpdate($buffer, $service, $id, $processImdb = 1)
	{
		$imdbID = false;
		if (preg_match('/(?:imdb.*?)?(?:tt|Title\?)(?P<imdbid>\d{5,7})/i', $buffer, $matches)) {
			$imdbID = $matches['imdbid'];
		}

		if ($imdbID !== false) {
			$this->service = $service;
			if ($this->echooutput && $this->service !== '') {
				$this->c->doEcho($this->c->headerOver($service . ' found IMDBid: ') . $this->c->primary('tt' . $imdbID));
			}

			$this->db->queryExec(sprintf('UPDATE releases SET imdbid = %s WHERE id = %d', $this->db->escapeString($imdbID), $id));

			// If set, scan for imdb info.
			if ($processImdb == 1) {
				$movCheck = $this->getMovieInfo($imdbID);
				if ($movCheck === false || (isset($movCheck['updateddate']) && (time() - strtotime($movCheck['updateddate'])) > 2592000)) {
					if ($this->updateMovieInfo($imdbID) === false) {
						$this->db->queryExec(sprintf('UPDATE releases SET imdbid = %s WHERE id = %d', 0000000, $id));
					}
				}
			}
		}
		return $imdbID;
	}

	/**
	 * Process releases with no IMDB ID's.
	 *
	 * @param string $releaseToWork
	 */
	public function processMovieReleases($releaseToWork = '')
	{
		$trakTv = new TraktTv();

		// Get all releases without an IMDB id.
		if ($releaseToWork === '') {
			$res = $this->db->query(
				sprintf("
					SELECT r.searchname, r.id
					FROM releases r
					WHERE r.imdbid IS NULL
					AND r.nzbstatus = 1
					AND r.categoryid BETWEEN 2000 AND 2999
					LIMIT %d",
					$this->movieqty
				)
			);
			$movieCount = count($res);
		} else {
			$pieces = explode("           =+=            ", $releaseToWork);
			$res = array(array('searchname' => $pieces[0], 'id' => $pieces[1]));
			$movieCount = 1;
		}

		if ($movieCount > 0) {
			if ($this->echooutput && $movieCount > 1) {
				$this->c->doEcho($this->c->header("Processing " . $movieCount . " movie releases."));
			}

			// Loop over releases.
			foreach ($res as $arr) {
				// Try to get a name/year.
				if ($this->parseMovieSearchName($arr['searchname']) === false) {
					//We didn't find a name, so set to all 0's so we don't parse again.
					$this->db->queryExec(sprintf("UPDATE releases SET imdbid = 0000000 WHERE id = %d", $arr["id"]));
					continue;

				} else {
					$this->currentRelID = $arr['id'];

					$movieName = $this->currentTitle;
					if ($this->currentYear !== false) {
						$movieName .= ' (' . $this->currentYear . ')';
					}

					if ($this->echooutput) {
						$this->c->doEcho($this->c->primaryOver("Looking up: ") . $this->c->headerOver($movieName), true);
					}

					// Check local DB.
					$getIMDBid = $this->localIMDBsearch($this->currentTitle, $this->currentYear);

					if ($getIMDBid !== false) {
						$imdbID = $this->doMovieUpdate('tt' . $getIMDBid, 'Local DB', $arr['id']);
						if ($imdbID !== false) {
							continue;
						}
					}

					// Check OMDB api.
					$buffer =
						nzedb\utility\getUrl(
							'http://www.omdbapi.com/?t=' .
							urlencode($this->currentTitle) .
							($this->currentYear !== false ? ('&y=' . $this->currentYear) : '') .
							'&r=json'
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
					$getIMDBid = $trakTv->traktMoviesummary($movieName);
					if ($getIMDBid !== false) {
						$imdbID = $this->doMovieUpdate($getIMDBid, 'Trakt', $arr['id']);
						if ($imdbID !== false) {
							continue;
						}
					}

					// Try on search engines.
					if ($this->searchEngines && $this->currentYear !== false) {
						if ($this->imdbIDFromEngines() === true) {
							continue;
						}
					}

					// We failed to get an IMDB id from all sources.
					$this->db->queryExec(sprintf("UPDATE releases SET imdbid = 0000000 WHERE id = %d", $arr["id"]));
				}
			}
		}
	}

	/**
	 * Try to fetch an IMDB id locally.
	 *
	 * @return int|bool   Int, the imdbid when true, Bool when false.
	 */
	protected function localIMDBsearch()
	{
		$query = 'SELECT imdbid FROM movieinfo';
		$andYearIn = '';

		//If we found a year, try looking in a 4 year range.
		if ($this->currentYear !== false) {
			$start = (int) $this->currentYear - 2;
			$end   = (int) $this->currentYear + 2;
			$andYearIn = 'AND year IN (';
			while ($start < $end) {
				$andYearIn .= $start . ',';
				$start++;
			}
			$andYearIn .= $end . ')';
		}
		$IMDBCheck = $this->db->queryOneRow(
			sprintf('%s WHERE title %s %s', $query, $this->db->likeString($this->currentTitle), $andYearIn));

		// Look by %word%word%word% etc..
		if ($IMDBCheck === false) {
			$pieces = explode(' ', $this->currentTitle);
			$tempTitle = '%';
			foreach ($pieces as $piece) {
				$tempTitle .= str_replace(array("'", "!", '"'), '', $piece) . '%';
			}
			$IMDBCheck = $this->db->queryOneRow(
				sprintf("%s WHERE replace(replace(title, \"'\", ''), '!', '') %s %s",
					$query, $this->db->likeString($tempTitle), $andYearIn
				)
			);
		}

		// Try replacing er with re ?
		if ($IMDBCheck === false) {
			$tempTitle = str_replace('er', 're', $this->currentTitle);
			if ($tempTitle !== $this->currentTitle) {
				$IMDBCheck = $this->db->queryOneRow(
					sprintf('%s WHERE title %s %s',
						$query, $this->db->likeString($tempTitle), $andYearIn
					)
				);

				// Final check if everything else failed.
				if ($IMDBCheck === false) {
					$pieces = explode(' ', $tempTitle);
					$tempTitle = '%';
					foreach ($pieces as $piece) {
						$tempTitle .= str_replace(array("'", "!", '"'), "", $piece) . '%';
					}
					$IMDBCheck = $this->db->queryOneRow(
						sprintf("%s WHERE replace(replace(replace(title, \"'\", ''), '!', ''), '\"', '') %s %s",
							$query, $this->db->likeString($tempTitle), $andYearIn
						)
					);
				}
			}
		}

		return (
		($IMDBCheck === false
			? false
			: (is_numeric($IMDBCheck['imdbid'])
				? (int)$IMDBCheck['imdbid']
				: false
			)
		)
		);
	}

	/**
	 * Try to get an IMDB id from search engines.
	 *
	 * @return bool
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
	 * @return bool
	 */
	protected function googleSearch()
	{
		$buffer = nzedb\utility\getUrl(
			'https://www.google.com/search?hl=en&as_q=&as_epq=' .
			urlencode(
				$this->currentTitle .
				' ' .
				$this->currentYear
			) .
			'&as_oq=&as_eq=&as_nlo=&as_nhi=&lr=&cr=&as_qdr=all&as_sitesearch=' .
			urlencode('www.imdb.com/title/') .
			'&as_occt=title&safe=images&tbs=&as_filetype=&as_rights='
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
	 * @return bool
	 */
	protected function bingSearch()
	{
		$buffer = nzedb\utility\getUrl(
			"http://www.bing.com/search?q=" .
			urlencode(
				'("' .
				$this->currentTitle .
				'" and "' .
				$this->currentYear .
				'") site:www.imdb.com/title/'
			) .
			'&qs=n&form=QBLH&filt=all'
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
	 * @return bool
	 */
	protected function yahooSearch()
	{
		$buffer = nzedb\utility\getUrl(
			"http://search.yahoo.com/search?n=10&ei=UTF-8&va_vt=title&vo_vt=any&ve_vt=any&vp_vt=any&vf=all&vm=p&fl=0&fr=fp-top&p=intitle:" .
			urlencode(
				'intitle:' .
				implode(' intitle:',
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
				' intitle:' .
				$this->currentYear
			) .
			'&vs=' .
			urlencode('www.imdb.com/title/')
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
	 * @return bool
	 */
	protected function parseMovieSearchName($releaseName)
	{
		// Check if it's foreign ?
		$cat = new Category();
		if (!$cat->isMovieForeign($releaseName)) {
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
						$this->c->doEcho("DB name: {$releaseName}", true);
					}

					$this->currentTitle = $name;
					$this->currentYear  = ($year === '' ? false : $year);

					return true;
				}
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
	 * @return array|bool
	 */
	public function getUpcoming($type, $source = 'rottentomato')
	{
		$list = $this->db->queryOneRow(
			sprintf('SELECT * FROM upcoming WHERE source = %s AND typeid = %d', $this->db->escapeString($source), $type)
		);
		if ($list === false) {
			$this->updateUpcoming();
			$list = $this->db->queryOneRow(
				sprintf('SELECT * FROM upcoming WHERE source = %s AND typeid = %d', $this->db->escapeString($source), $type)
			);
		}
		return $list;
	}

	/**
	 * Update upcoming movies.
	 */
	public function updateUpcoming()
	{
		if ($this->echooutput) {
			$this->c->doEcho($this->c->header('Updating movie schedule using rotten tomatoes.'));
		}

		if (isset($this->site->rottentomatokey)) {
			$rt = new RottenTomato($this->site->rottentomatokey);

			$retBo = $rt->getBoxOffice();
			$test = @json_decode($retBo);
			if (!$test || $retBo === "") {
				sleep(1);
				$retBo = $rt->getBoxOffice();
				$test = @json_decode($retBo);
				if (!$test || $retBo === "") {
					if ($this->echooutput) {
						exit($this->c->error("\nUnable to fetch from Rotten Tomatoes, verify your API Key\n"));
					}
				}
			}
			if ($test) {
				$cnt1 = $this->updateInsUpcoming('rottentomato', Movie::SRC_BOXOFFICE, $retBo);
				if ($this->echooutput && $cnt1 > 0) {
					$this->c->doEcho($this->c->header("Added/updated movies to the box office list."));
				} else {
					if ($this->echooutput) {
						$this->c->doEcho($this->c->primary("No new updates for box office list."));
					}
				}
			}

			$retTh = $rt->getInTheaters();
			$test = @json_decode($retTh);
			if (!$test || $retTh === "") {
				sleep(1);
				$retTh = $rt->getInTheaters();
				$test = @json_decode($retTh);
				if (!$test || $retTh === "") {
					if ($this->echooutput) {
						exit($this->c->error("\nUnable to fetch from Rotten Tomatoes, verify your API Key\n"));
					}
				}
			}
			if ($test) {
				$cnt2 = $this->updateInsUpcoming('rottentomato', Movie::SRC_INTHEATRE, $retTh);
				if ($this->echooutput && $cnt2 > 0) {
					echo $this->c->header("Added/updated movies to the theaters list.");
				} else {
					if ($this->echooutput) {
						$this->c->doEcho($this->c->primary("No new updates for theaters list."));
					}
				}
			}

			$retOp = $rt->getOpening();
			$test = @json_decode($retOp);
			if (!$test || $retOp === '') {
				sleep(1);
				$retOp = $rt->getOpening();
				$test = @json_decode($retOp);
				if (!$test || $retOp === '') {
					if ($this->echooutput) {
						exit($this->c->error("\nUnable to fetch from Rotten Tomatoes, verify your API Key\n"));
					}
				}
			}
			if ($test) {
				$cnt3 = $this->updateInsUpcoming('rottentomato', Movie::SRC_OPENING, $retOp);
				if ($this->echooutput && $cnt3 > 0) {
					$this->c->doEcho($this->c->header("Added/updated movies to the opening list."));
				} else {
					if ($this->echooutput) {
						$this->c->doEcho($this->c->primary("No new updates for upcoming list."));
					}
				}
			}

			$retUp = $rt->getUpcoming();
			$test = @json_decode($retUp);
			if (!$test || $retUp === "") {
				sleep(1);
				$retUp = $rt->getUpcoming();
				$test = @json_decode($retUp);
				if (!$test || $retUp === "") {
					if ($this->echooutput) {
						exit($this->c->error("\nUnable to fetch from Rotten Tomatoes, verify your API Key\n"));
					}
				}
			}
			if ($test) {
				$cnt4 = $this->updateInsUpcoming('rottentomato', Movie::SRC_UPCOMING, $retUp);
				if ($this->echooutput && $cnt4 > 0) {
					$this->c->doEcho($this->c->header("Added/updated movies to the upcoming list."));
				} else {
					if ($this->echooutput) {
						$this->c->doEcho($this->c->primary("No new updates for upcoming list."));
					}
				}
			}

			$retDr = $rt->getDVDReleases();
			$test = @json_decode($retDr);
			if (!$test || $retDr === "") {
				sleep(1);
				$retDr = $rt->getDVDReleases();
				$test = @json_decode($retDr);
				if (!$test || $retDr === "") {
					if ($this->echooutput) {
						exit($this->c->error("\nUnable to fetch from Rotten Tomatoes, verify your API Key\n"));
					}
				}
			}
			if ($test) {
				$cnt5 = $this->updateInsUpcoming('rottentomato', Movie::SRC_DVD, $retDr);
				if ($this->echooutput && $cnt5 > 0) {
					$this->c->doEcho($this->c->header("Added/updated movies to the DVD list."));
				} else {
					if ($this->echooutput) {
						$this->c->doEcho($this->c->primary("No new updates for upcoming list."));
					}
				}
			}

			if ($this->echooutput) {
				$this->c->doEcho($this->c->header("Updated successfully."));
			}
		}
	}

	/**
	 * Update upcoming table.
	 *
	 * @param $source
	 * @param $type
	 * @param $info
	 *
	 * @return bool|int
	 */
	protected function updateInsUpcoming($source, $type, $info)
	{
		if ($this->db->dbSystem() === 'mysql') {
			return $this->db->Exec(
				sprintf("
					INSERT INTO upcoming (source, typeid, info, updateddate)
					VALUES (%s, %d, %s, NOW())
					ON DUPLICATE KEY UPDATE info = %s",
					$this->db->escapeString($source),
					$type,
					$this->db->escapeString($info),
					$this->db->escapeString($info)
				)
			);
		} else {
			$ckId = $this->db->queryOneRow(
				sprintf('
					SELECT id FROM upcoming
					WHERE source = %s
					AND typeid = %d
					AND info = %s',
					$this->db->escapeString($source),
					$type,
					$this->db->escapeString($info)
				)
			);
			if ($ckId === false) {
				return $this->db->Exec(
					sprintf("
						INSERT INTO upcoming (source, typeid, info, updateddate)
						VALUES (%s, %d, %s, NOW())",
						$this->db->escapeString($source),
						$type,
						$this->db->escapeString($info)
					)
				);
			} else {
				return $this->db->Exec(
					sprintf('
						UPDATE upcoming
						SET source = %s, typeid = %s, info = %s, updateddate = NOW()
						WHERE id = %d',
						$this->db->escapeString($source),
						$type,
						$this->db->escapeString($info),
						$ckId['id']
					)
				);
			}
		}
	}

	/**
	 * Get IMDB genres.
	 *
	 * @return array
	 */
	public function getGenres()
	{
		return array(
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
		);
	}

}
