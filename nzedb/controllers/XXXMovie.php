<?php

/**
 * Todo: Add Trailers and/or Productinfo
 */
require_once nZEDb_LIBS . 'adultdvdempire.php';
require_once nZEDb_LIBS . 'popporn.php';

use nzedb\db\DB;
use nzedb\utility;

/**
 * Class XXXMovie
 */
class XXXMovie
{
	protected $adeclass = false; // We used AdultDVDEmpire class?
	protected $popclass = false; // We used PopPorn class?

	/**
	 * Current title being passed through various sites/api's.
	 * @var string
	 */
	protected $currentTitle = '';

	/**
	 * @var Debugging
	 */
	protected $debugging;

	/**
	 * @var bool
	 */
	protected $debug;

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

	protected $currentRelID;

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
		$this->movieqty = (!empty($this->site->maximdbprocessed) ? $this->site->maximdbprocessed : 100);
		$this->showPasswords = (!empty($this->site->showpasswordedrelease) ? $this->site->showpasswordedrelease : 0);

		$this->debug = nZEDb_DEBUG;
		$this->echooutput = ($echoOutput && nZEDb_ECHOCLI);
		$this->imgSavePath = nZEDb_COVERS . 'xxx' . DS;
		$this->service = '';

		if (nZEDb_DEBUG || nZEDb_LOGGING) {
			$this->debug = true;
			$this->debugging = new Debugging('Movie');
		}
	}

	/**
	 * Get info for a xxx id.
	 *
	 * @param int $xxxid
	 *
	 * @return array|bool
	 */
	public function getMovieInfo($xxxid)
	{
		return $this->db->queryOneRow(sprintf("SELECT * FROM xxxinfo WHERE id = %d", $xxxid));
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
				FROM xxxinfo
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
				SELECT COUNT(DISTINCT r.xxxinfo_id) AS num
				FROM releases r
				INNER JOIN xxxinfo m ON m.id = r.xxxinfo_id
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
				LEFT OUTER JOIN groups ON groups.id = r.group_id
				LEFT OUTER JOIN releasenfo rn ON rn.releaseid = r.id
				INNER JOIN xxxinfo m ON m.id = r.xxxinfo_id
				WHERE r.nzbstatus = 1
				AND m.cover = 1
				AND m.title != ''
				AND r.passwordstatus <= %d AND %s %s %s %s
				GROUP BY m.id ORDER BY %s %s %s",
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
				LEFT OUTER JOIN groups ON groups.id = r.group_id
				INNER JOIN xxxinfo m ON m.id = r.xxxinfo_id AND m.title != ''
				LEFT OUTER JOIN releasenfo rn ON rn.releaseid = r.id AND rn.nfo IS NOT NULL
				WHERE r.nzbstatus = 1
				AND r.passwordstatus <= %s
				AND %s %s %s %s
				GROUP BY m.id, groups.name, rn.id
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
		return array('title_asc', 'title_desc');
	}

	/**
	 * @return string
	 */
	protected function getBrowseBy()
	{
		$browseBy = ' ';
		$browseByArr = array('title', 'director', 'actors', 'genre');
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
			$newArr[] = '<a href="' . WWW_TOP . '/xxx?' . $field . '=' . urlencode($ta) . '" title="' . $ta . '">' . $ta . '</a>';
			$i++;
		}
		return implode(', ', $newArr);
	}
	/**
	 * Update movie on movie-edit page.
	 *
	 *@param $id
	 * @param $title
	 * @param $tagline
	 * @param $plot
	 * @param $genre
	 * @param $director
	 * @param $actors
	 * @param $cover
	 * @param $backdrop
	 */
	public function update(
		$id = '', $title = '', $tagline = '', $plot = '', $genre = '', $director = '',
		$actors = '', $cover = '', $backdrop = ''
	)
	{
		if (!empty($id)) {

			$this->db->queryExec(
				sprintf("
					UPDATE xxxinfo
					SET %s, %s, %s, %s, %s, %s, %d, %d, updateddate = NOW()
					WHERE id = %d",
					(empty($title)    ? '' : 'title = '    . $this->db->escapeString($title)),
					(empty($tagLine)  ? '' : 'tagline = '  . $this->db->escapeString($tagLine)),
					(empty($plot)     ? '' : 'plot = '     . $this->db->escapeString($plot)),
					(empty($genre)    ? '' : 'genre = '    . $this->db->escapeString($genre)),
					(empty($director) ? '' : 'director = ' . $this->db->escapeString($director)),
					(empty($actors)   ? '' : 'actors = '   . $this->db->escapeString($actors)),
					(empty($cover)    ? '' : 'cover = '    . $cover),
					(empty($backdrop) ? '' : 'backdrop = ' . $backdrop),
					$id
				)
			);
		}
	}

	/**
	 * Fetch xxx info for the movie.
	 *
	 * @param $xxxmovie
	 *
	 * @return bool
	 */
	public function updateMovieInfo($xxxmovie)
	{

		$res = false;
		// Check Adultdvdempire for xxx info.
		$mov = new adultdvdempire();
		$mov->searchterm = $xxxmovie;
		$res = $mov->search();
		$this->adeclass = true;
		if ($res === false) {
			$this->adeclass = false;
			// IF no result from Adultdvdempire check popporn
			$mov = new popporn();
			$mov->searchterm = $xxxmovie;
			$res = $mov->search();
			$this->popclass = true;
		}
		// If a result is true getall information.
		if ($res !== false) {
			$res = $mov->_getall();
		}
		if ($res === false) {
			$this->popclass = false;

			return false;
		}
		if ($this->echooutput) {
			$this->c->doEcho($this->c->primary("Fetching XXX info for: " . $xxxmovie));
		}
		$mov = array();
		$mov['backdrop'] = (isset($res['backcover'])) ? $res['backcover'] : '';
		$mov['cover'] = (isset($res['boxcover'])) ? $res['boxcover'] : '';
		$res['cast'] = (isset($res['cast'])) ? join(",", $res['cast']) : '';
		$res['genres'] = (isset($res['genres'])) ? join(",", $res['genres']) : '';
		$mov['title'] = html_entity_decode($xxxmovie, ENT_QUOTES, 'UTF-8');
		$mov['plot'] = (isset($res['sypnosis'])) ? html_entity_decode($res['sypnosis'], ENT_QUOTES, 'UTF-8') : '';
		$mov['tagline'] = (isset($res['tagline'])) ? html_entity_decode($res['tagline'], ENT_QUOTES, 'UTF-8') : '';
		$mov['genre'] = html_entity_decode($res['genres'], ENT_QUOTES, 'UTF-8');
		$mov['director'] = (isset($res['director'])) ? html_entity_decode($res['director'], ENT_QUOTES, 'UTF-8') : '';
		$mov['actors'] = html_entity_decode($res['cast'], ENT_QUOTES, 'UTF-8');
		$mov['title'] = str_replace(array('/', '\\'), '', $mov['title']);
		$check = $this->db->queryOneRow(
						  sprintf('
				SELECT id
				FROM xxxinfo
				WHERE title = %s',
				$this->db->escapeString($mov['title'])
						  )
		);
		if($check === false){
		if ($this->db->dbSystem() === 'mysql') {
			$xxxID = $this->db->queryInsert(
				sprintf("
					INSERT INTO xxxinfo
						(title, tagline, plot, genre, director, actors, cover, backdrop, createddate, updateddate)
					VALUES
						(%s, %s, %s, %s, %s, %s, %d, %d, NOW(), NOW())
					ON DUPLICATE KEY UPDATE
						title = %s, tagline = %s, plot = %s, genre = %s, director = %s, actors = %s, cover = %d, backdrop = %d, updateddate = NOW()",
					$this->db->escapeString($mov['title']),
					$this->db->escapeString($mov['tagline']),
					$this->db->escapeString($mov['plot']),
					$this->db->escapeString(substr($mov['genre'], 0, 64)),
					$this->db->escapeString($mov['director']),
					$this->db->escapeString($mov['actors']),
					0,
					0,
					$this->db->escapeString($mov['title']),
					$this->db->escapeString($mov['tagline']),
					$this->db->escapeString($mov['plot']),
					$this->db->escapeString(substr($mov['genre'], 0, 64)),
					$this->db->escapeString($mov['director']),
					$this->db->escapeString($mov['actors']),
					0,
					0
				)
			);
		} else if ($this->db->dbSystem() === 'pgsql') {
				$xxxID = $this->db->queryInsert(
					sprintf("
						INSERT INTO xxxinfo
							(title, tagline, plot, genre, director, actors, cover, backdrop, createddate, updateddate)
						VALUES
							(%s, %s, %s, %s, %s, %s, %d, %d, NOW(), NOW())",
						$this->db->escapeString($mov['title']),
						$this->db->escapeString($mov['tagline']),
						$this->db->escapeString($mov['plot']),
						$this->db->escapeString($mov['genre']),
						$this->db->escapeString($mov['director']),
						$this->db->escapeString($mov['actors']),
						0,
						0
					)
				);
			}
		if($xxxID !=0){

			// BoxCover.
			if(isset($mov['cover'])){
			$mov['cover'] = $this->releaseImage->saveImage($xxxID . '-cover', $mov['cover'], $this->imgSavePath);

			}
			// BackCover.
			if(isset($mov['backdrop'])){
			$mov['backdrop'] = $this->releaseImage->saveImage($xxxID . '-backdrop', $mov['backdrop'], $this->imgSavePath, 1920, 1024);
			}
			$this->db->queryExec(sprintf('UPDATE xxxinfo SET cover = %d, backdrop = %d  WHERE id = %d', $mov['cover'], $mov['backdrop'], $xxxID));
		}
		}else{
		// If xxxinfo title is found, update release with xxxinfo id
			$this->db->queryExec(sprintf('UPDATE releases SET xxxinfo_id = %d  WHERE id = %d', $check, $this->currentRelID));
			$xxxID=$check;
		}

		if ($this->echooutput) {
			$this->c->doEcho(
				$this->c->headerOver(($xxxID !== 0 ? 'Added/updated movie: ' : 'Nothing to update for xxx movie: ')) .
				$this->c->primary($mov['title'])
			);
		}

		return ($xxxID === 0 ? false : true);
	}

	/**
	 * Process releases with no xxxinfo ID's.
	 *
	 * @param string $releaseToWork
	 */

	public function processXXXMovieReleases($releaseToWork = '')
	{
		// Get all releases without an IMDB id.
		if ($releaseToWork === '') {
			$res = $this->db->query(
				sprintf("
					SELECT r.searchname, r.id
					FROM releases r
					WHERE r.nzbstatus = 1
					AND r.xxxinfo_id IS NULL
					AND r.categoryid BETWEEN 6030 AND 6040
					AND r.isrenamed = 1
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
				$this->c->doEcho($this->c->header("Processing " . $movieCount . " XXX releases."));
			}

			// Loop over releases.
			foreach ($res as $arr) {
				// Try to get a name/year.
				if ($this->parseXXXMovieSearchName($arr['searchname']) === false) {
					//We didn't find a name, so set to all 0's so we don't parse again.
					$this->db->queryExec(sprintf("UPDATE releases SET xxxinfo_id = %d WHERE id = %d", -2, $arr["id"]));
					continue;

				} else {
					$this->currentRelID = $arr['id'];

					$movieName = $this->currentTitle;

					if ($this->echooutput) {
						$this->c->doEcho($this->c->primaryOver("Looking up: ") . $this->c->headerOver($movieName), true);
						$xxxid = $this->updateMovieInfo($movieName);

					}
					if($xxxid < 0){
						$this->db->queryExec(sprintf('UPDATE releases SET xxxinfo_id = %d WHERE id = %d', $xxxid, $arr['id']));
					}else{
						/// We failed to get any xxx info from all sources.
						$this->db->queryExec(sprintf('UPDATE releases SET xxxinfo_id = %d WHERE id = %d', -2, $arr['id']));

					}


				}
			}
		}
	}

	/**
	 * Parse a movie name from a release search name.
	 *
	 * @param string $releaseName
	 *
	 * @return bool
	 */
	protected function parseXXXMovieSearchName($releaseName)
	{
		// Check if it's foreign ?
		$cat = new Categorize();
		if (!$cat->isMovieForeign($releaseName)) {
			$name = '';
			$followingList = '[^\w]((1080|480|720)p|AC3D|Directors([^\w]CUT)?|DD5\.1|(DVD|BD|BR)(Rip)?|BluRay|divx|HDTV|iNTERNAL|LiMiTED|(Real\.)?Proper|RE(pack|Rip)|Sub\.?(fix|pack)|Unrated|WEB-DL|(x|H)[-._ ]?264|xvid|XXX)[^\w]';

			/* Initial scan of getting a year/name.
			 * [\w. -]+ Gets 0-9a-z. - characters, most scene movie titles contain these chars.
			 * ie: [61420]-[FULL]-[a.b.foreignEFNet]-[ Coraline.2009.DUTCH.INTERNAL.1080p.BluRay.x264-VeDeTT ]-[21/85] - "vedett-coralien-1080p.r04" yEnc
			 * Then we look up the year, (19|20)\d\d, so $matches[1] would be Coraline $matches[2] 2009
			 */
			if (preg_match('/([^\w]{2,})?(?P<name>[\w .-]+?)' . $followingList . '/i', $releaseName, $matches)) {
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
					// Check if the name is long enough and not just numbers and not file (d) of (d).
				if (strlen($name) > 5 && !preg_match('/^\d+$/', $name) && !preg_match('/(- File \d+ of \d+|\d+.\d+.\d+)/',$name)) {
					if ($this->debug && $this->echooutput) {
						$this->c->doEcho("DB name: {$releaseName}", true);
					}
					$this->currentTitle = $name;
					return true;
				}
			}
		}
		return false;
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
