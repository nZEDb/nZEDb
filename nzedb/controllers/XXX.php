<?php

use nzedb\db\Settings;
use nzedb\utility;

/**
 * Class XXX
 */
class XXX
{
	/**
	 * @var nzedb\db\Settings
	 */
	public $pdo;

	/**
	 * We used AdultDVDEmpire or PopPorn class -- used for template and trailer information
	 *
	 * @var string
	 */
	protected $whichclass = '';

	/**
	 * Current title being passed through various sites/api's.
	 * @var string
	 */
	protected $currentTitle = '';

	/**
	 * @var Logger
	 */
	protected $debugging;

	/**
	 * @var bool
	 */
	protected $debug;

	/**
	 * @var ReleaseImage
	 */
	protected $releaseImage;

	protected $currentRelID;

	protected $movieqty;

	protected $showPasswords;

	protected $cookie;

	/**
	 * @param array $options Echo to cli / Class instances.
	 */
	public function __construct(array $options = array())
	{
		$defaults = [
			'Echo'         => false,
			'ReleaseImage' => null,
			'Settings'     => null,
		];
		$options += $defaults;

		$this->pdo = ($options['Settings'] instanceof Settings ? $options['Settings'] : new Settings());
		$this->releaseImage = ($options['ReleaseImage'] instanceof \ReleaseImage ? $options['ReleaseImage'] : new \ReleaseImage($this->pdo));

		$this->movieqty = ($this->pdo->getSetting('maxxxxprocessed') != '') ? $this->pdo->getSetting('maxxxxprocessed') : 100;
		$this->showPasswords = ($this->pdo->getSetting('showpasswordedrelease') != '') ? $this->pdo->getSetting('showpasswordedrelease') : 0;
		$this->debug = nZEDb_DEBUG;
		$this->echooutput = ($options['Echo'] && nZEDb_ECHOCLI);
		$this->imgSavePath = nZEDb_COVERS . 'xxx' . DS;
		$this->cookie = nZEDb_TMP . 'xxx.cookie';

		if (nZEDb_DEBUG || nZEDb_LOGGING) {
			$this->debug = true;
			try {
				$this->debugging = new \Logger();
			} catch (\LoggerException $error) {
				$this->_debug = false;
			}
		}
	}

	/**
	 * Get info for a xxx id.
	 *
	 * @param int $xxxid
	 *
	 * @return array|bool
	 */
	public function getXXXInfo($xxxid)
	{
		return $this->pdo->queryOneRow(sprintf("SELECT *, UNCOMPRESS(plot) AS plot FROM xxxinfo WHERE id = %d", $xxxid));
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
		return $this->pdo->query(
			sprintf('
				SELECT *,
				UNCOMPRESS(plot) AS plot
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
		$res = $this->pdo->queryOneRow('SELECT COUNT(id) AS num FROM xxxinfo');
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
	public function getXXXCount($cat, $maxAge = -1, $excludedCats = array())
	{
		$catsrch = '';
		if (count($cat) > 0 && $cat[0] != -1) {
			$catsrch = (new \Category(['Settings' => $this->pdo]))->getCategorySearch($cat);
		}

		$res = $this->pdo->queryOneRow(
			sprintf("
				SELECT COUNT(DISTINCT r.xxxinfo_id) AS num
				FROM releases r
				INNER JOIN xxxinfo xxx ON xxx.id = r.xxxinfo_id
				WHERE r.nzbstatus = 1
				AND xxx.title != ''
				AND r.passwordstatus <= %d
				AND %s %s %s %s ",
				$this->showPasswords,
				$this->getBrowseBy(),
				$catsrch,
				($maxAge > 0
					? 'AND r.postdate > NOW() - INTERVAL ' . $maxAge . 'DAY '
					: ''
				),
				(count($excludedCats) > 0 ? ' AND r.categoryid NOT IN (' . implode(',', $excludedCats) . ')' : '')
			)
		);
		return ($res === false ? 0 : $res['num']);
	}

	/**
	 * Get movie releases with covers for xxx browse page.
	 *
	 * @param       $cat
	 * @param       $start
	 * @param       $num
	 * @param       $orderBy
	 * @param       $maxAge
	 * @param array $excludedCats
	 *
	 * @return array
	 */
	public function getXXXRange($cat, $start, $num, $orderBy, $maxAge = -1, $excludedCats = array())
	{
		$catsrch = '';
		if (count($cat) > 0 && $cat[0] != -1) {
			$catsrch = (new \Category(['Settings' => $this->pdo]))->getCategorySearch($cat);
		}

		$order = $this->getXXXOrder($orderBy);
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
			xxx.*, UNCOMPRESS(xxx.plot) AS plot, groups.name AS group_name, rn.id as nfoid FROM releases r
			LEFT OUTER JOIN groups ON groups.id = r.group_id
			LEFT OUTER JOIN releasenfo rn ON rn.releaseid = r.id
			INNER JOIN xxxinfo xxx ON xxx.id = r.xxxinfo_id
			WHERE r.nzbstatus = 1
			AND xxx.title != ''
			AND r.passwordstatus <= %d AND %s %s %s %s
			GROUP BY xxx.id ORDER BY %s %s %s",
			$this->showPasswords,
			$this->getBrowseBy(),
			$catsrch,
			($maxAge > 0
				? 'AND r.postdate > NOW() - INTERVAL ' . $maxAge . 'DAY '
				: ''
			),
			(count($excludedCats) > 0 ? ' AND r.categoryid NOT IN (' . implode(',', $excludedCats) . ')' : ''),
			$order[0],
			$order[1],
			($start === false ? '' : ' LIMIT ' . $num . ' OFFSET ' . $start)
		);
		return $this->pdo->query($sql);
	}

	/**
	 * Get the order type the user requested on the movies page.
	 *
	 * @param $orderBy
	 *
	 * @return array
	 */
	protected function getXXXOrder($orderBy)
	{
		$orderArr = explode('_', (($orderBy == '') ? 'MAX(r.postdate)' : $orderBy));
		switch ($orderArr[0]) {
			case 'title':
				$orderField = 'xxx.title';
				break;
			case 'posted':
			default:
				$orderField = 'MAX(r.postdate)';
				break;
		}

		return array($orderField, ((isset($orderArr[1]) && preg_match('/^asc|desc$/i', $orderArr[1])) ? $orderArr[1] : 'desc'));
	}

	/**
	 * Order types for xxx page.
	 *
	 * @return array
	 */
	public function getXXXOrdering()
	{
		return array('title_asc', 'title_desc');
	}

	/**
	 * @return string
	 */
	protected function getBrowseBy()
	{
		$browseBy = ' ';
		$browseByArr = array('title', 'director', 'actors', 'genre', 'id');
		foreach ($browseByArr as $bb) {
			if (isset($_REQUEST[$bb]) && !empty($_REQUEST[$bb])) {
				$bbv = stripslashes($_REQUEST[$bb]);
				if ($bb == "genre") {
					$bbv = $this->getgenreid($bbv);
				}
				if ($bb == 'id') {
					$browseBy .= 'xxx.' . $bb . '=' . $bbv . ' AND ';
				} else {
					$browseBy .= 'xxx.' . $bb . ' ' . $this->pdo->likeString($bbv, true, true) . ' AND ';
				}
			}
		}
		return $browseBy;
	}

	/**
	 * Create click-able links to actors/genres/directors/etc..
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

		$tmpArr = explode(',', $data[$field]);
		$newArr = array();
		$i = 0;
		foreach ($tmpArr as $ta) {
			if ($field == "genre" ) {
				$ta = $this->getGenres(true,$ta);
				$ta = $ta["title"];
			}
			if ($i > 5) {
				break;
			} //only use first 6
			$newArr[] = '<a href="' . WWW_TOP . '/xxx?' . $field . '=' . urlencode($ta) . '" title="' . $ta . '">' . $ta . '</a>';
			$i++;
		}
		return implode(', ', $newArr);
	}

	/**
	 * Update XXX Information from getXXXCovers.php in misc/testing/PostProc
	 *
	 * @param string $id
	 * @param string $title
	 * @param string $tagLine
	 * @param string $plot
	 * @param string $genre
	 * @param string $director
	 * @param string $actors
	 * @param string $extras
	 * @param string $productInfo
	 * @param string $trailers
	 * @param string $directUrl
	 * @param string $classUsed
	 * @param string $cover
	 * @param string $backdrop
	 */
	public function update(
		$id = '', $title = '', $tagLine = '', $plot = '', $genre = '', $director = '',
		$actors = '', $extras = '', $productInfo = '', $trailers = '', $directUrl = '', $classUsed = '', $cover = '', $backdrop = ''
	)
	{
		if (!empty($id)) {

			$this->pdo->queryExec(
				sprintf("UPDATE xxxinfo	SET title = %s, tagline = %s, plot = COMPRESS(%s), genre = %s, director = %s,
					actors = %s, extras = %s, productinfo = %s, trailers = %s, directurl = %s,
					classused = %s, cover = %d, backdrop = %d, updateddate = NOW()
					WHERE id = %d",
					$this->pdo->escapeString($title),
					$this->pdo->escapeString($tagLine),
					$this->pdo->escapeString($plot),
					$this->pdo->escapeString(substr($genre, 0, 64)),
					$this->pdo->escapeString($director),
					$this->pdo->escapeString($actors),
					$this->pdo->escapeString($extras),
					$this->pdo->escapeString($productInfo),
					$this->pdo->escapeString($trailers),
					$this->pdo->escapeString($directUrl),
					$this->pdo->escapeString($classUsed),
					(empty($cover) ? 0 : $cover),
					(empty($backdrop) ? 0 : $backdrop),
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
	public function updateXXXInfo($xxxmovie)
	{

		$res = false;
		$this->whichclass = '';

		$iafd = new \IAFD();
		$iafd->searchTerm = $xxxmovie;

		if ($iafd->findme() !== false) {

			switch($iafd->classUsed) {
				case "ade":
					$mov = new \ADE();
					$mov->directLink = (string)$iafd->directUrl;
					$res = $mov->getDirect();
					$res['title'] = $iafd->title;
					$res['directurl'] = (string)$iafd->directUrl;
					$this->whichclass = $iafd->classUsed;
					$this->pdo->log->doEcho($this->pdo->log->primary("Fetching XXX info from IAFD -> Adult DVD Empire"));
					break;
			}
		}

		if ($res === false) {

			$this->whichclass = "aebn";
			$mov = new \AEBN();
			$mov->cookie = $this->cookie;
			$mov->searchTerm = $xxxmovie;
			$res = $mov->search();

			if ($res === false) {
				$this->whichclass = "ade";
				$mov = new \ADE();
				$mov->searchTerm = $xxxmovie;
				$res = $mov->search();
			}

			if ($res === false) {
				$this->whichclass = "pop";
				$mov = new \Popporn();
				$mov->cookie = $this->cookie;
				$mov->searchTerm = $xxxmovie;
				$res = $mov->search();
			}

			// Last in list as it doesn't have trailers
			if ($res === false) {
				$this->whichclass = "adm";
				$mov = new \ADM();
				$mov->cookie = $this->cookie;
				$mov->searchTerm = $xxxmovie;
				$res = $mov->search();
			}


			// If a result is true getAll information.
			if ($res !== false) {
				if ($this->echooutput) {

					switch ($this->whichclass) {
						case "aebn":
							$fromstr = "Adult Entertainment Broadcast Network";
							break;
						case "ade":
							$fromstr = "Adult DVD Empire";
							break;
						case "pop":
							$fromstr = "PopPorn";
							break;
						case "adm":
							$fromstr = "Adult DVD Marketplace";
							break;
						default:
							$fromstr = null;
					}
					$this->pdo->log->doEcho($this->pdo->log->primary("Fetching XXX info from: " . $fromstr));
				}
				$res = $mov->getAll();
			} else {
				// Nothing was found, go ahead and set to -2
				return -2;
			}
		}

		$mov = array();

		$mov['trailers'] = (isset($res['trailers'])) ? serialize($res['trailers']) : '';
		$mov['extras'] = (isset($res['extras'])) ? serialize($res['extras']) : '';
		$mov['productinfo'] = (isset($res['productinfo'])) ? serialize($res['productinfo']) : '';
		$mov['backdrop'] = (isset($res['backcover'])) ? $res['backcover'] : 0;
		$mov['cover'] = (isset($res['boxcover'])) ? $res['boxcover'] : 0;
		$res['cast'] = (isset($res['cast'])) ? join(",", $res['cast']) : '';
		$res['genres'] = (isset($res['genres'])) ? $this->getgenreid($res['genres']) : '';
		$mov['title'] = html_entity_decode($res['title'], ENT_QUOTES, 'UTF-8');
		$mov['plot'] = (isset($res['sypnosis'])) ? html_entity_decode($res['sypnosis'], ENT_QUOTES, 'UTF-8') : '';
		$mov['tagline'] = (isset($res['tagline'])) ? html_entity_decode($res['tagline'], ENT_QUOTES, 'UTF-8') : '';
		$mov['genre'] = html_entity_decode($res['genres'], ENT_QUOTES, 'UTF-8');
		$mov['director'] = (isset($res['director'])) ? html_entity_decode($res['director'], ENT_QUOTES, 'UTF-8') : '';
		$mov['actors'] = html_entity_decode($res['cast'], ENT_QUOTES, 'UTF-8');
		$mov['directurl'] = html_entity_decode($res['directurl'], ENT_QUOTES, 'UTF-8');
		$mov['classused'] = $this->whichclass;

		$check = $this->pdo->queryOneRow(sprintf('SELECT id FROM xxxinfo WHERE title = %s',	$this->pdo->escapeString($mov['title'])));
		$xxxID = 0;
		if(isset($check['id'])){
		$xxxID = $check['id'];
		}

		if($check === false OR $xxxID > 0){

		// Update Current XXX Information - getXXXCovers.php
		if($xxxID > 0){
			$this->update($check['id'], $mov['title'], $mov['tagline'], $mov['plot'], $mov['genre'], $mov['director'], $mov['actors'], $mov['extras'], $mov['productinfo'], $mov['trailers'], $mov['directurl'], $mov['classused']);
			$xxxID = $check['id'];
		}

		// Insert New XXX Information
		if($check === false){
			$xxxID = $this->pdo->queryInsert(
				sprintf("
					INSERT INTO xxxinfo
						(title, tagline, plot, genre, director, actors, extras, productinfo, trailers, directurl, classused, cover, backdrop, createddate, updateddate)
					VALUES
						(%s, %s, COMPRESS(%s), %s, %s, %s, %s, %s, %s, %s, %s, 0, 0, NOW(), NOW())",
					$this->pdo->escapeString($mov['title']),
					$this->pdo->escapeString($mov['tagline']),
					$this->pdo->escapeString($mov['plot']),
					$this->pdo->escapeString(substr($mov['genre'], 0, 64)),
					$this->pdo->escapeString($mov['director']),
					$this->pdo->escapeString($mov['actors']),
					$this->pdo->escapeString($mov['extras']),
					$this->pdo->escapeString($mov['productinfo']),
					$this->pdo->escapeString($mov['trailers']),
					$this->pdo->escapeString($mov['directurl']),
					$this->pdo->escapeString($mov['classused'])
				)
			);
			}

			if ($xxxID > 0) {

				// BoxCover.
				if (isset($mov['cover'])) {
					$mov['cover'] = $this->releaseImage->saveImage($xxxID . '-cover', $mov['cover'], $this->imgSavePath);
				}

				// BackCover.
				if (isset($mov['backdrop'])) {
					$mov['backdrop'] = $this->releaseImage->saveImage($xxxID . '-backdrop', $mov['backdrop'], $this->imgSavePath, 1920, 1024);
				}

				$this->pdo->queryExec(sprintf('UPDATE xxxinfo SET cover = %d, backdrop = %d  WHERE id = %d', $mov['cover'], $mov['backdrop'], $xxxID));

			} else {
				$xxxID = -2;
			}

		}

		if ($this->echooutput) {
			$this->pdo->log->doEcho(
				$this->pdo->log->headerOver(($xxxID !== false ? 'Added/updated movie: ' : 'Nothing to update for xxx movie: ')) .
				$this->pdo->log->primary($mov['title'])
			);
		}

		return $xxxID;
	}

	/**
	 * Process releases with no xxxinfo ID's.
	 *
	 */

	public function processXXXReleases()
	{

		// Get all releases without an IMpdo id.
		$res = $this->pdo->query(
			sprintf("
				SELECT r.searchname, r.id
				FROM releases r
				WHERE r.nzbstatus = 1
				AND r.xxxinfo_id = 0
				AND r.categoryid BETWEEN 6000 AND 6040
				LIMIT %d",
				$this->movieqty
			)
		);
		$movieCount = count($res);

		if ($movieCount > 0) {

			if ($this->echooutput) {
				$this->pdo->log->doEcho($this->pdo->log->header("Processing " . $movieCount . " XXX releases."));
			}

			// Loop over releases.
			foreach ($res as $arr) {

				$idcheck = -2;

				// Try to get a name.
				if ($this->debug && $this->echooutput) {
					$this->pdo->log->doEcho("DB name: " . $arr['searchname'], true);
				}
				if ($this->parseXXXSearchName($arr['searchname']) !== false) {

					$this->currentRelID = $arr['id'];
					$movieName = $this->currentTitle;

					if ($this->echooutput) {
						$this->pdo->log->doEcho($this->pdo->log->primaryOver("Looking up: ") . $this->pdo->log->headerOver($movieName), true);
					}

					$idcheck = $this->updateXXXInfo($movieName);
				} else {
					$this->pdo->log->doEcho(".", true);
				}
				$this->pdo->queryExec(sprintf('UPDATE releases SET xxxinfo_id = %d WHERE id = %d', $idcheck, $arr['id']));
			}

		} elseif ($this->echooutput) {
			$this->pdo->log->doEcho($this->pdo->log->header('No xxx releases to process.'));
		}
	}

	/**
	 * Parse a xxx name from a release search name.
	 *
	 * @param string $releaseName
	 *
	 * @return bool
	 */
	protected function parseXXXSearchName($releaseName)
	{
			$name = '';
			$followingList = '[^\w]((2160|1080|480|720)(p|i)|AC3D|Directors([^\w]CUT)?|DD5\.1|(DVD|BD|BR)(Rip)?|BluRay|divx|HDTV|iNTERNAL|LiMiTED|(Real\.)?Proper|RE(pack|Rip)|Sub\.?(fix|pack)|Unrated|WEB-DL|(x|H)[-._ ]?264|xvid|[Dd][Ii][Ss][Cc](\d+|\s*\d+|\.\d+)|XXX|BTS|DirFix|Trailer|WEBRiP|NFO|(19|20)\d\d)[^\w]';

			if (preg_match('/([^\w]{2,})?(?P<name>[\w .-]+?)' . $followingList . '/i', $releaseName, $matches)) {
				$name = $matches['name'];
			}

			// Check if we got something.
			if ($name !== '') {

				// If we still have any of the words in $followingList, remove them.
				$name = preg_replace('/' . $followingList . '/i', ' ', $name);
				// Remove periods, underscored, anything between parenthesis.
				$name = preg_replace('/\(.*?\)|[-._]/i', ' ', $name);
				// Finally remove multiple spaces and trim leading spaces.
				$name = trim(preg_replace('/\s{2,}/', ' ', $name));
				// Remove Private Movies {d} from name better matching.
				$name = trim(preg_replace('/^Private\s(Specials|Blockbusters|Blockbuster|Sports|Gold|Lesbian|Movies|Classics|Castings|Fetish|Stars|Pictures|XXX|Private|Black\sLabel|Black)\s\d+/i', '', $name));
				// Remove Foreign Words at the end of the name.
				$name = trim(preg_replace('/(brazilian|chinese|croatian|danish|deutsch|dutch|estonian|flemish|finnish|french|german|greek|hebrew|icelandic|italian|latin|nordic|norwegian|polish|portuguese|japenese|japanese|russian|serbian|slovenian|spanish|spanisch|swedish|thai|turkish)$/i', '', $name));

				// Check if the name is long enough and not just numbers and not file (d) of (d) and does not contain Episodes and any dated 00.00.00 which are site rips..
				if (strlen($name) > 5 && !preg_match('/^\d+$/', $name) && !preg_match('/( File \d+ of \d+|\d+.\d+.\d+)/',$name) && !preg_match('/(E\d+)/',$name) && !preg_match('/\d\d\.\d\d.\d\d/', $name)) {
					$this->currentTitle = $name;
					return true;
				} else {
					$this->pdo->log->doEcho(".", false);
				}
			}

		return false;
	}

	/**
	 * Get all genres for search-filter.tpl
	 *
	 * @param bool $activeOnly
	 *
	 * @return array|null
	 */
	public function getAllGenres($activeOnly = false) {
		$res = $ret = null;

		if ($activeOnly) {
			$res = $this->pdo->query("SELECT title FROM genres WHERE disabled = 0 AND type = 6000 ORDER BY title");
		} else {
			$res = $this->pdo->query("SELECT title FROM genres WHERE disabled = 1 AND type = 6000 ORDER BY title");
		}

		foreach ($res as $arr => $value) {
			$ret[] = $value['title'];
		}
		return $ret;
	}

	/**
	 * Get Genres for activeonly and/or an ID
	 *
	 * @param bool $activeOnly
	 * @param null $gid
	 *
	 * @return array|bool
	 */
	public function getGenres($activeOnly = false, $gid = null)
	{
		if (isset($gid)) {
			$gid = " AND id = ". $this->pdo->escapeString($gid) . " ORDER BY title";
		} else {
			$gid = " ORDER BY title";
		}

		if ($activeOnly) {
			return $this->pdo->queryOneRow("SELECT title FROM genres WHERE disabled = 0 AND type = 6000" . $gid);
		} else {
			return $this->pdo->queryOneRow("SELECT title FROM genres WHERE disabled = 1 AND type = 6000" . $gid);
		}
	}

	/**
	 * Get Genre ID's Of the title
	 *
	 * @param $arr - Array or String
	 *
	 * @return string - If array .. 1,2,3,4 if string .. 1
	 */
	private function getGenreID($arr)
	{
		$ret = null;

		if (!is_array($arr)) {
			$res = $this->pdo->queryOneRow("SELECT id FROM genres WHERE title = " . $this->pdo->escapeString($arr));
			if ($res !== false) {
				return $res["id"];
			}
		}

		foreach ($arr as $key => $value) {
			$res = $this->pdo->queryOneRow("SELECT id FROM genres WHERE title = " . $this->pdo->escapeString($value));
			if ($res !== false) {
				$ret .= "," . $res["id"];
			} else {
				$ret .= "," . $this->insertGenre($value);
			}
		}

		$ret = ltrim($ret,",");
		return ($ret);
	}

	/**
	 * Inserts Genre and returns last affected row (Genre ID)
	 *
	 * @param $genre
	 *
	 * @return bool
	 */
	private function insertGenre($genre)
	{
		$res = '';
		if (isset($genre)) {
			$res = $this->pdo->queryInsert(sprintf("INSERT INTO genres (title, type, disabled) VALUES (%s ,%d ,%d)",$this->pdo->escapeString($genre), 6000, 0));
		}
		return $res;
	}

	/**
	 * Inserts Trailer Code by Class
	 *
	 * @param $whichclass
	 * @param $res
	 *
	 * @return string
	 */
	public function insertSwf($whichclass, $res)
	{
		$ret = '';
		if ($whichclass === "ade") {
			if (!empty($res)) {
				$trailers = unserialize($res);
				$ret .="<object width='360' height='240' type='application/x-shockwave-flash' id='EmpireFlashPlayer' name='EmpireFlashPlayer' data='".	$trailers['url'] . "'>";
				$ret .= "<param name='flashvars' value= 'streamID=" . $trailers['streamid'] . "&amp;autoPlay=false&amp;BaseStreamingUrl=" . $trailers['baseurl'] . "'>";
				$ret .= "</object>";

				return ($ret);
			}
		}
		if ($whichclass === "pop") {
			if (!empty($res)) {
				$trailers = unserialize($res);
				$ret .= "<embed id='trailer' width='480' height='360'";
				$ret .= "flashvars='" .	$trailers['flashvars'] . "' allowfullscreen='true' allowscriptaccess='always' quality='high' name='trailer' style='undefined'";
				$ret .= "src='" . $trailers['baseurl'] . "' type='application/x-shockwave-flash'>";

				return ($ret);
			}
		}

		return $ret;
	}
}
