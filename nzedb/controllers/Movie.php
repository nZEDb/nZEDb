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
	 * @var Debugging
	 */
	protected $debugging;

	/**
	 * @var bool
	 */
	protected $debug;

	/**
	 * @param bool $echooutput
	 */
	function __construct($echooutput = false)
	{
		$this->echooutput = ($echooutput && nZEDb_ECHOCLI);
		$this->db = new DB();
		$s = new Sites();
		$site = $s->get();
		$this->apikey = $site->tmdbkey;
		$this->fanartapikey = $site->fanarttvkey;
		$this->binglimit = $this->yahoolimit = 0;
		$this->debug = nZEDb_DEBUG;
		$this->imdburl = ($site->imdburl == "0") ? false : true;
		$this->imdblanguage = (!empty($site->imdblanguage)) ? $site->imdblanguage : "en";
		$this->imgSavePath = nZEDb_COVERS . 'movies' . DS;
		$this->movieqty = (!empty($site->maximdbprocessed)) ? $site->maximdbprocessed : 100;
		$this->service = '';
		$this->c = new ColorCLI();
		if (nZEDb_DEBUG || nZEDb_LOGGING) {
			$this->debug = true;
			$this->debugging = new Debugging('Movie');
		}
	}

	/**
	 * Look for an IMDB id in a string.
	 *
	 * @param $str    String containing the IMDB id.
	 *
	 * @return string IMDB id on success.
	 * @return bool   False on failure.
	 *
	 * @access public
	 */
	public function parseImdb($str)
	{
		if (preg_match('/(?:imdb.*?)?(?:tt|Title\?)(\d{5,7})/i', $str, $matches)) {
			return trim($matches[1]);
		}

		return false;
	}

	public function getMovieInfo($imdbId)
	{
		return $this->db->queryOneRow(sprintf("SELECT * FROM movieinfo WHERE imdbid = %d", $imdbId));
	}

	public function getMovieInfoMultiImdb($imdbIds)
	{
		$allids = str_replace(',,', ',', str_replace(array('(,', ' ,', ', )', ',)'), '', implode(',', $imdbIds)));
		$sql = sprintf("SELECT DISTINCT movieinfo.*, releases.imdbid AS relimdb FROM movieinfo "
			. "LEFT OUTER JOIN releases ON releases.imdbid = movieinfo.imdbid WHERE movieinfo.imdbid IN (%s)", $allids);
		return $this->db->query($sql);
	}

	public function getRange($start, $num)
	{
		if ($start === false) {
			$limit = "";
		} else {
			$limit = " LIMIT " . $num . " OFFSET " . $start;
		}

		return $this->db->query(" SELECT * FROM movieinfo ORDER BY createddate DESC" . $limit);
	}

	public function getCount()
	{
		$res = $this->db->queryOneRow("SELECT COUNT(id) AS num FROM movieinfo");
		return $res["num"];
	}

	public function getMovieCount($cat, $maxage = -1, $excludedcats = array())
	{
		$browseby = $this->getBrowseBy();

		$catsrch = "";
		if (count($cat) > 0 && $cat[0] != -1) {
			$catsrch = " (";
			foreach ($cat as $category) {
				if ($category != -1) {
					$categ = new Category();
					if ($categ->isParent($category)) {
						$children = $categ->getChildren($category);
						$chlist = "-99";
						foreach ($children as $child) {
							$chlist.=", " . $child["id"];
						}

						if ($chlist != "-99") {
							$catsrch .= " r.categoryid IN (" . $chlist . ") OR ";
						}
					} else {
						$catsrch .= sprintf(" r.categoryid = %d OR ", $category);
					}
				}
			}
			$catsrch.= "1=2 )";
		}

		if ($maxage > 0) {
			if ($this->db->dbSystem() === 'mysql') {
				$maxage = sprintf(' AND r.postdate > NOW() - INTERVAL %d DAY ', $maxage);
			} else if ($this->db->dbSystem() === 'pgsql') {
				$maxage = sprintf(" AND r.postdate > NOW() - INTERVAL '%d DAYS' ", $maxage);
			}
		} else {
			$maxage = '';
		}

		$exccatlist = "";
		if (count($excludedcats) > 0) {
			$exccatlist = " AND r.categoryid NOT IN (" . implode(",", $excludedcats) . ")";
		}

		$rel = new Releases($this->echooutput);

		$sql = sprintf("SELECT COUNT(DISTINCT r.imdbid) AS num FROM releases r "
			. "INNER JOIN movieinfo m ON m.imdbid = r.imdbid "
			. "WHERE r.nzbstatus = 1 AND m.cover = 1 AND m.title != '' AND r.passwordstatus <= %d AND %s %s %s %s ", $rel->showPasswords(), $browseby, $catsrch, $maxage, $exccatlist);
		$res = $this->db->queryOneRow($sql);
		return $res["num"];
	}

	public function getMovieRange($cat, $start, $num, $orderby, $maxage = -1, $excludedcats = array())
	{
		$browseby = $this->getBrowseBy();

		if ($start === false) {
			$limit = "";
		} else {
			$limit = " LIMIT " . $num . " OFFSET " . $start;
		}

		$catsrch = "";
		if (count($cat) > 0 && $cat[0] != -1) {
			$catsrch = " (";
			foreach ($cat as $category) {
				if ($category != -1) {
					$categ = new Category();
					if ($categ->isParent($category)) {
						$children = $categ->getChildren($category);
						$chlist = "-99";
						foreach ($children as $child) {
							$chlist.=", " . $child["id"];
						}

						if ($chlist != "-99") {
							$catsrch .= " r.categoryid IN (" . $chlist . ") OR ";
						}
					} else {
						$catsrch .= sprintf(" r.categoryid = %d OR ", $category);
					}
				}
			}
			$catsrch.= "1=2 )";
		}

		$maxage = '';
		if ($maxage > 0) {
			if ($this->db->dbSystem() === 'mysql') {
				$maxage = sprintf(' AND r.postdate > NOW() - INTERVAL %d DAY ', $maxage);
			} else {
				$maxage = sprintf(" AND r.postdate > NOW() - INTERVAL '%d DAYS' ", $maxage);
			}
		}

		$exccatlist = "";
		if (count($excludedcats) > 0) {
			$exccatlist = " AND r.categoryid NOT IN (" . implode(",", $excludedcats) . ")";
		}

		$order = $this->getMovieOrder($orderby);
		if ($this->db->dbSystem() === 'mysql') {
			$sql = sprintf("SELECT "
				. "GROUP_CONCAT(r.id ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_id, "
				. "GROUP_CONCAT(r.rarinnerfilecount ORDER BY r.postdate DESC SEPARATOR ',') as grp_rarinnerfilecount, "
				. "GROUP_CONCAT(r.haspreview ORDER BY r.postdate DESC SEPARATOR ',') AS grp_haspreview, "
				. "GROUP_CONCAT(r.passwordstatus ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_password, "
				. "GROUP_CONCAT(r.guid ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_guid, "
				. "GROUP_CONCAT(rn.id ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_nfoid, "
				. "GROUP_CONCAT(groups.name ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_grpname, "
				. "GROUP_CONCAT(r.searchname ORDER BY r.postdate DESC SEPARATOR '#') AS grp_release_name, "
				. "GROUP_CONCAT(r.postdate ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_postdate, "
				. "GROUP_CONCAT(r.size ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_size, "
				. "GROUP_CONCAT(r.totalpart ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_totalparts, "
				. "GROUP_CONCAT(r.comments ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_comments, "
				. "GROUP_CONCAT(r.grabs ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_grabs, "
				. "m.*, groups.name AS group_name, rn.id as nfoid FROM releases r "
				. "LEFT OUTER JOIN groups ON groups.id = r.groupid "
				. "LEFT OUTER JOIN releasenfo rn ON rn.releaseid = r.id "
				. "INNER JOIN movieinfo m ON m.imdbid = r.imdbid "
				. "WHERE r.nzbstatus = 1 AND m.cover = 1 AND m.title != '' AND "
				. "r.passwordstatus <= (SELECT value FROM site WHERE setting='showpasswordedrelease') AND %s %s %s %s "
				. "GROUP BY m.imdbid ORDER BY %s %s" . $limit, $browseby, $catsrch, $maxage, $exccatlist, $order[0], $order[1]);
		} else {
			$rel = new Releases($this->echooutput);
			$sql = sprintf("SELECT STRING_AGG(r.id::text, ',' ORDER BY r.postdate DESC) AS grp_release_id, STRING_AGG(r.rarinnerfilecount::text, ',' ORDER BY r.postdate DESC) as grp_rarinnerfilecount, STRING_AGG(r.haspreview::text, ',' ORDER BY r.postdate DESC) AS grp_haspreview, STRING_AGG(r.passwordstatus::text, ',' ORDER BY r.postdate) AS grp_release_password, STRING_AGG(r.guid, ',' ORDER BY r.postdate DESC) AS grp_release_guid, STRING_AGG(rn.id::text, ',' ORDER BY r.postdate DESC) AS grp_release_nfoid, STRING_AGG(groups.name, ',' ORDER BY r.postdate DESC) AS grp_release_grpname, STRING_AGG(r.searchname, '#' ORDER BY r.postdate) AS grp_release_name, STRING_AGG(r.postdate::text, ',' ORDER BY r.postdate DESC) AS grp_release_postdate, STRING_AGG(r.size::text, ',' ORDER BY r.postdate DESC) AS grp_release_size, STRING_AGG(r.totalpart::text, ',' ORDER BY r.postdate DESC) AS grp_release_totalparts, STRING_AGG(r.comments::text, ',' ORDER BY r.postdate DESC) AS grp_release_comments, STRING_AGG(r.grabs::text, ',' ORDER BY r.postdate DESC) AS grp_release_grabs, m.*, groups.name AS group_name, rn.id as nfoid FROM releases r LEFT OUTER JOIN groups ON groups.id = r.groupid INNER JOIN movieinfo m ON m.imdbid = r.imdbid and m.title != '' LEFT OUTER JOIN releasenfo rn ON rn.releaseid = r.id AND rn.nfo IS NOT NULL WHERE r.nzbstatus = 1 AND r.passwordstatus <= %s AND %s %s %s %s GROUP BY m.imdbid, m.id, groups.name, rn.id ORDER BY %s %s" . $limit, $rel->showPasswords(), $browseby, $catsrch, $maxage, $exccatlist, $order[0], $order[1]);
		}
		return $this->db->queryDirect($sql);
	}

	public function getMovieOrder($orderby)
	{
		$order = ($orderby == '') ? 'MAX(r.postdate)' : $orderby;
		$orderArr = explode("_", $order);
		switch ($orderArr[0]) {
			case 'title':
				$orderfield = 'm.title';
				break;
			case 'year':
				$orderfield = 'm.year';
				break;
			case 'rating':
				$orderfield = 'm.rating';
				break;
			case 'posted':
			default:
				$orderfield = 'max(r.postdate)';
				break;
		}
		$ordersort = (isset($orderArr[1]) && preg_match('/^asc|desc$/i', $orderArr[1])) ? $orderArr[1] : 'desc';
		return array($orderfield, $ordersort);
	}

	public function getMovieOrdering()
	{
		return array('title_asc', 'title_desc', 'year_asc', 'year_desc', 'rating_asc', 'rating_desc');
	}

	public function getBrowseByOptions()
	{
		return array('title', 'director', 'actors', 'genre', 'rating', 'year', 'imdb');
	}

	public function getBrowseBy()
	{
		$browseby = ' ';
		$browsebyArr = $this->getBrowseByOptions();
		$like = ' ILIKE(';
		if ($this->db->dbSystem() === 'mysql') {
			$like = ' LIKE(';
		}
		foreach ($browsebyArr as $bb) {
			if (isset($_REQUEST[$bb]) && !empty($_REQUEST[$bb])) {
				$bbv = stripslashes($_REQUEST[$bb]);
				if ($bb == 'rating') {
					$bbv .= '.';
				}
				if ($bb == 'imdb') {
					$browseby .= 'm.' . $bb . 'id = ' . $bbv . ' AND ';
				} else {
					$browseby .= 'm.' . $bb . $like . $this->db->escapeString('%' . $bbv . '%') . ') AND ';
				}
			}
		}
		return $browseby;
	}

	public function makeFieldLinks($data, $field)
	{
		if ($data[$field] == "") {
			return "";
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

	public function update($id, $title, $tagline, $plot, $year, $rating, $genre, $director, $actors, $language, $cover, $backdrop)
	{
		$this->db->queryExec(sprintf("UPDATE movieinfo SET title = %s, tagline = %s, plot = %s, year = %s, rating = %s, "
				. "genre = %s, director = %s, actors = %s, language = %s, cover = %d, backdrop = %d, updateddate = NOW() "
				. "WHERE imdbid = %d", $this->db->escapeString($title), $this->db->escapeString($tagline), $this->db->escapeString($plot), $this->db->escapeString($year), $this->db->escapeString($rating), $this->db->escapeString($genre), $this->db->escapeString($director), $this->db->escapeString($actors), $this->db->escapeString($language), $cover, $backdrop, $id));
	}

	public function updateMovieInfo($imdbId)
	{
		$ri = new ReleaseImage();

		if ($this->echooutput && $this->service != '') {
			$this->c->doEcho($this->c->primary("Fetching IMDB info from TMDB using IMDB ID: " . $imdbId));
		}

		//check themoviedb for imdb info
		$tmdb = $this->fetchTmdbProperties($imdbId);

		//check imdb for movie info
		$imdb = $this->fetchImdbProperties($imdbId);
		if (!$imdb && !$tmdb) {
			return false;
		}

		//check fanarttv for background
		$fanart = $this->fetchFanartTVProperties($imdbId);

		$mov = array();
		$mov['imdb_id'] = $imdbId;
		$mov['tmdb_id'] = (!isset($tmdb['tmdb_id']) || $tmdb['tmdb_id'] == '') ? "NULL" : $tmdb['tmdb_id'];

		//prefer fanarttv cover over, prefer tmdb cover over imdb cover
		$mov['cover'] = 0;
		if (isset($fanart['cover']) && $fanart['cover'] != '') {
			$mov['cover'] = $ri->saveImage($imdbId . '-cover', $fanart['cover'], $this->imgSavePath);
		} else if (isset($tmdb['cover']) && $tmdb['cover'] != '') {
			$mov['cover'] = $ri->saveImage($imdbId . '-cover', $tmdb['cover'], $this->imgSavePath);
		} else if (isset($imdb['cover']) && $imdb['cover'] != '') {
			$mov['cover'] = $ri->saveImage($imdbId . '-cover', $imdb['cover'], $this->imgSavePath);
		}

		//prefer fanart backdrop over tmdb backdrop
		$mov['backdrop'] = 0;
		if (isset($fanart['backdrop']) && $fanart['backdrop'] != '') {
			$mov['backdrop'] = $ri->saveImage($imdbId . '-backdrop', $fanart['backdrop'], $this->imgSavePath, 1920, 1024);
		} else if (isset($tmdb['backdrop']) && $tmdb['backdrop'] != '') {
			$mov['backdrop'] = $ri->saveImage($imdbId . '-backdrop', $tmdb['backdrop'], $this->imgSavePath, 1920, 1024);
		}

		$mov['title'] = '';
		if (isset($imdb['title']) && $imdb['title'] != '') {
			$mov['title'] = $imdb['title'];
		} else if (isset($tmdb['title']) && $tmdb['title'] != '') {
			$mov['title'] = $tmdb['title'];
		}
		$mov['title'] = html_entity_decode($mov['title'], ENT_QUOTES, 'UTF-8');

		$mov['rating'] = '';
		if (isset($imdb['rating']) && $imdb['rating'] != '') {
			$mov['rating'] = $imdb['rating'];
		} else if (isset($tmdb['rating']) && $tmdb['rating'] != '') {
			$mov['rating'] = $tmdb['rating'];
		}

		$mov['tagline'] = '';
		if (isset($imdb['tagline']) && $imdb['tagline'] != '') {
			$mov['tagline'] = html_entity_decode($imdb['tagline'], ENT_QUOTES, 'UTF-8');
		} else if (isset($tmdb['tagline']) && $tmdb['tagline'] != '') {
			$mov['tagline'] = $tmdb['tagline'];
		}

		$mov['plot'] = '';
		if (isset($imdb['plot']) && $imdb['plot'] != '') {
			$mov['plot'] = $imdb['plot'];
		} else if (isset($tmdb['plot']) && $tmdb['plot'] != '') {
			$mov['plot'] = $tmdb['plot'];
		}
		$mov['plot'] = html_entity_decode($mov['plot'], ENT_QUOTES, 'UTF-8');

		$mov['year'] = '';
		if (isset($imdb['year']) && $imdb['year'] != '') {
			$mov['year'] = $imdb['year'];
		} else if (isset($tmdb['year']) && $tmdb['year'] != '') {
			$mov['year'] = $tmdb['year'];
		}

		$mov['genre'] = '';
		if (isset($tmdb['genre']) && $tmdb['genre'] != '') {
			$mov['genre'] = $tmdb['genre'];
		} else if (isset($imdb['genre']) && $imdb['genre'] != '') {
			$mov['genre'] = $imdb['genre'];
		}
		if (is_array($mov['genre'])) {
			$mov['genre'] = implode(', ', array_unique($mov['genre']));
		}
		$mov['genre'] = html_entity_decode($mov['genre'], ENT_QUOTES, 'UTF-8');

		$mov['type'] = '';
		if (isset($imdb['type']) && $imdb['type'] != '') {
			$mov['type'] = $imdb['type'];
		}
		if (is_array($mov['type'])) {
			$mov['type'] = implode(', ', array_unique($mov['type']));
		}
		$mov['type'] = ucwords(preg_replace('/[\.\_]/', ' ', $mov['type']));
		$mov['type'] = html_entity_decode($mov['type'], ENT_QUOTES, 'UTF-8');

		$mov['director'] = '';
		if (isset($imdb['director']) && $imdb['director'] != '') {
			$mov['director'] = (is_array($imdb['director'])) ? implode(', ', array_unique($imdb['director'])) : $imdb['director'];
		}
		$mov['director'] = html_entity_decode($mov['director'], ENT_QUOTES, 'UTF-8');

		$mov['actors'] = '';
		if (isset($imdb['actors']) && $imdb['actors'] != '') {
			$mov['actors'] = (is_array($imdb['actors'])) ? implode(', ', array_unique($imdb['actors'])) : $imdb['actors'];
		}
		$mov['actors'] = html_entity_decode($mov['actors'], ENT_QUOTES, 'UTF-8');

		$mov['language'] = '';
		if (isset($imdb['language']) && $imdb['language'] != '') {
			$mov['language'] = (is_array($imdb['language'])) ? implode(', ', array_unique($imdb['language'])) : $imdb['language'];
		}
		$mov['language'] = html_entity_decode($mov['language'], ENT_QUOTES, 'UTF-8');

		$movtitle = str_replace(array('/', '\\'), '', $mov['title']);
		if ($this->db->dbSystem() === 'mysql') {
			$movieId = $this->db->queryInsert(sprintf("INSERT INTO movieinfo (imdbid, tmdbid, title, rating, tagline, plot, "
					. "year, genre, type, director, actors, language, cover, backdrop, createddate, updateddate) VALUES "
					. "(%d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, %d, NOW(), NOW()) ON DUPLICATE KEY UPDATE imdbid = %d, "
					. "tmdbid = %s, title = %s, rating = %s, tagline = %s, plot = %s, year = %s, genre = %s, type = %s, director = %s, "
					. "actors = %s, language = %s, cover = %d, backdrop = %d, updateddate = NOW()", $mov['imdb_id'], $mov['tmdb_id'], $this->db->escapeString($movtitle), $this->db->escapeString($mov['rating']), $this->db->escapeString($mov['tagline']), $this->db->escapeString($mov['plot']), $this->db->escapeString($mov['year']), $this->db->escapeString(substr($mov['genre'], 0, 64)), $this->db->escapeString($mov['type']), $this->db->escapeString($mov['director']), $this->db->escapeString($mov['actors']), $this->db->escapeString(substr($mov['language'], 0, 64)), $mov['cover'], $mov['backdrop'], $mov['imdb_id'], $mov['tmdb_id'], $this->db->escapeString($movtitle), $this->db->escapeString($mov['rating']), $this->db->escapeString($mov['tagline']), $this->db->escapeString($mov['plot']), $this->db->escapeString($mov['year']), $this->db->escapeString(substr($mov['genre'], 0, 64)), $this->db->escapeString($mov['type']), $this->db->escapeString($mov['director']), $this->db->escapeString($mov['actors']), $this->db->escapeString(substr($mov['language'], 0, 64)), $mov['cover'], $mov['backdrop']));
		} else if ($this->db->dbSystem() === 'pgsql') {
			$ckid = $this->db->queryOneRow(sprintf('SELECT id FROM movieinfo WHERE imdbid = %d', $mov['imdb_id']));
			if (!isset($ckid['id'])) {
				$movieId = $this->db->queryInsert(sprintf("INSERT INTO movieinfo (imdbid, tmdbid, title, rating, tagline, "
						. "plot, year, genre, type, director, actors, language, cover, backdrop, createddate, updateddate) "
						. "VALUES (%d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, %d, NOW(), NOW())", $mov['imdb_id'], $mov['tmdb_id'], $this->db->escapeString($movtitle), $this->db->escapeString($mov['rating']), $this->db->escapeString($mov['tagline']), $this->db->escapeString($mov['plot']), $this->db->escapeString($mov['year']), $this->db->escapeString($mov['genre']), $this->db->escapeString($mov['type']), $this->db->escapeString($mov['director']), $this->db->escapeString($mov['actors']), $this->db->escapeString($mov['language']), $mov['cover'], $mov['backdrop']));
			} else {
				$movieId = $ckid['id'];
				$this->db->queryExec(sprintf('UPDATE movieinfo SET tmdbid = %d, title = %s, rating = %s, tagline = %s, '
						. 'plot = %s, year = %s, genre = %s, type = %s, director = %s, actors = %s, language = %s, cover = %d, '
						. 'backdrop = %d, updateddate = NOW() WHERE id = %d', $mov['tmdb_id'], $this->db->escapeString($movtitle), $this->db->escapeString($mov['rating']), $this->db->escapeString($mov['tagline']), $this->db->escapeString($mov['plot']), $this->db->escapeString($mov['year']), $this->db->escapeString($mov['genre']), $this->db->escapeString($mov['type']), $this->db->escapeString($mov['director']), $this->db->escapeString($mov['actors']), $this->db->escapeString($mov['language']), $mov['cover'], $mov['backdrop'], $movieId));
			}
		}

		if ($movieId) {
			if ($this->echooutput && $this->service != '') {
				$this->c->doEcho(
					$this->c->headerOver("Added/updated movie: ") .
					$this->c->primary($movtitle .
						" (" .
						$mov['year'] .
						") - " .
						$mov['imdb_id']
					)
				);
			}
		} else {
			if ($this->echooutput && $this->service != '') {
				$this->c->doEcho(
					$this->c->headerOver("Nothing to update for movie: ") .
					$this->c->primary($movtitle .
						" (" .
						$mov['year'] .
						") - " .
						$mov['imdb_id']
					)
				);
			}
		}

		return $movieId;
	}

	/**
	 * @param $imdbId
	 *
	 * @return bool
	 */
	public function fetchFanartTVProperties($imdbId)
	{
		if ($this->fanartapikey != '') {
			$url = "http://api.fanart.tv/webservice/movie/" . $this->fanartapikey . "/tt" . $imdbId . "/xml/";
			$buffer = @file_get_contents($url);
			if ($buffer == 'null') {
				return false;
			}
			$art = @simplexml_load_string($buffer);
			if (!$art) {
				return false;
			}
			if (isset($art->movie->moviebackgrounds->moviebackground[0]['url'])) {
				$ret['backdrop'] = $art->movie->moviebackgrounds->moviebackground[0]['url'];
			} else if (isset($art->movie->moviethumbs->moviethumb[0]['url'])) {
				$ret['backdrop'] = $art->movie->moviethumbs->moviethumb[0]['url'];
			}
			if (isset($art->movie->movieposters->movieposter[0]['url'])) {
				$ret['cover'] = $art->movie->movieposters->movieposter[0]['url'];
			}
			if (!isset($ret['backdrop']) && !isset($ret['cover'])) {
				return false;
			}
			$ret['title'] = $imdbId;
			if (isset($art->movie['name'])) {
				$ret['title'] = $art->movie['name'];
			}
			if ($this->echooutput) {
				$this->c->doEcho($this->c->alternateOver("Fanart Found ") . $this->c->headerOver($ret['title']));
			}
			return $ret;
		} else {
			return false;
		}
	}

	public function fetchTmdbProperties($imdbId, $text = false)
	{
		$tmdb = new TMDb($this->apikey, $this->imdblanguage);
		if ($text == false) {
			$lookupId = 'tt' . $imdbId;
		} else {
			$lookupId = $imdbId;
		}

		try {
			$tmdbLookup = $tmdb->getMovie($lookupId);
		} catch (exception $e) {
			return false;
		}

		if (!$tmdbLookup) {
			return false;
		}
		if (isset($tmdbLookup['status_code']) && $tmdbLookup['status_code'] !== 1) {
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
			$ret['cover'] = "http://d3gtl9l2a4fn1j.cloudfront.net/t/p/w185" . $tmdbLookup['poster_path'];
		}
		if (isset($tmdbLookup['backdrop_path']) && sizeof($tmdbLookup['backdrop_path']) > 0) {
			$ret['backdrop'] = "http://d3gtl9l2a4fn1j.cloudfront.net/t/p/original" . $tmdbLookup['backdrop_path'];
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
	public function fetchImdbProperties($imdbId)
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

		$options = array(
			'http' => array(
				'method' => "GET",
				'header' => "Accept-language: en\r\n" .
				"Cookie: foo=bar\r\n" . // check function.stream-context-create on php.net
				"User-Agent: Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B334b Safari/531.21.102011-10-16 20:23:10\r\n" // i.e. An iPad
			)
		);

		$context = stream_context_create($options);

		if ($this->imdburl === false) {
			// i don't know how to use the language, but this is broken
			//$url = nzedb\utility\getUrl("http://www.imdb.com/title/tt$imdbId/", $this->imdblanguage);
			$url = "http://www.imdb.com/title/tt$imdbId/";
		} else {
			$url = "http://akas.imdb.com/title/tt$imdbId/";
		}
		$buffer = @file_get_contents($url, false, $context);
		// make sure we got some data
		if ($buffer !== false && strlen($buffer)) {
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

			//actors
			if (preg_match('/<table class="cast_list">(.+)<\/table>/s', $buffer, $hit)) {
				if (preg_match_all('/<a.*?href="\/name\/(nm\d{1,8})\/.+"name">(.+)<\/span>/i', $hit[0], $results, PREG_PATTERN_ORDER)) {
					$ret['actors'] = $results[2];
				}
			}

			//directors
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

	public function domovieupdate($buffer, $service, $id, $processImdb = 1)
	{
		$imdbId = $this->parseImdb($buffer);
		if ($imdbId !== false) {
			$this->service = $service;
			if ($this->echooutput && $this->service != '') {
				$this->c->doEcho($this->c->headerOver($service . ' found IMDBid: ') . $this->c->primary('tt' . $imdbId));
			}

			$this->db->queryExec(sprintf('UPDATE releases SET imdbid = %s WHERE id = %d', $this->db->escapeString($imdbId), $id));

			// If set, scan for imdb info.
			if ($processImdb == 1) {
				$movCheck = $this->getMovieInfo($imdbId);
				if ($movCheck === false || (isset($movCheck['updateddate']) && (time() - strtotime($movCheck['updateddate'])) > 2592000)) {
					if ($this->updateMovieInfo($imdbId) === false) {
						$this->db->queryExec(sprintf('UPDATE releases SET imdbid = %s WHERE id = %d', 0000000, $id));
					}
				}
			}
		}
		return $imdbId;
	}

	public function processMovieReleases($releaseToWork = '')
	{
		$trakt = new TraktTv();
		$googleban = false;
		$googlelimit = 0;
		$result = '';

		if ($releaseToWork == '') {
			$res = $this->db->query(sprintf("SELECT r.searchname AS name, r.id FROM releases r "
					. "WHERE r.imdbid IS NULL AND r.nzbstatus = 1 AND r.categoryid BETWEEN 2000 AND 2999 LIMIT %d", $this->movieqty));
			$moviecount = count($res);
		} else {
			$pieces = explode("           =+=            ", $releaseToWork);
			$res = array(array('name' => $pieces[0], 'id' => $pieces[1]));
			$moviecount = 1;
		}

		if ($moviecount > 0) {
			if ($this->echooutput && $moviecount > 1) {
				$this->c->doEcho($this->c->header("Processing " . $moviecount . " movie release(s)."));
			}

			$like = 'ILIKE';
			$inyear = 'year::int';
			if ($this->db->dbSystem() === 'mysql') {
				$like = 'LIKE';
				$inyear = 'year';
			}

			foreach ($res as $arr) {
				$parsed = $this->parseMovieSearchName($arr['name']);
				if ($parsed !== false) {
					$year = false;
					$this->currentTitle = $moviename = $parsed['title'];
					$movienameonly = $moviename;
					if ($parsed['year'] != '') {
						$year = true;
						$moviename .= ' (' . $parsed['year'] . ')';
					}

					// Check locally first.
					if ($year === true) {
						$start = (int) $parsed['year'] - 2;
						$end = (int) $parsed['year'] + 2;
						$ystr = '(';
						while ($start < $end) {
							$ystr .= $start . ',';
							$start ++;
						}
						$ystr .= $end . ')';
						$ckimdbid = $this->db->queryOneRow(sprintf('SELECT imdbid FROM movieinfo '
								. 'WHERE title %s %s AND %s IN %s', $like, "'%" . $parsed['title'] . "%'", $inyear, $ystr));
					} else {
						$ckimdbid = $this->db->queryOneRow(sprintf('SELECT imdbid FROM movieinfo '
								. 'WHERE title %s %s', $like, "'%" . $parsed['title'] . "%'"));
					}

					// Try lookup by %name%
					if (!isset($ckimdbid['imdbid'])) {
						$title = str_replace('er', 're', $parsed['title']);
						if ($title != $parsed['title']) {
							$ckimdbid = $this->db->queryOneRow(sprintf('SELECT imdbid FROM movieinfo WHERE title %s %s', $like, "'%" . $title . "%'"));
						}
						if (!isset($ckimdbid['imdbid'])) {
							$pieces = explode(' ', $parsed['title']);
							$title1 = '%';
							foreach ($pieces as $piece) {
								$title1 .= str_replace(array("'", "!", '"'), "", $piece) . '%';
							}
							$ckimdbid = $this->db->queryOneRow(sprintf("SELECT imdbid FROM movieinfo WHERE replace(replace(title, \"'\", ''), '!', '') %s %s", $like, $this->db->escapeString($title1)));
						}
						if (!isset($ckimdbid['imdbid'])) {
							$pieces = explode(' ', $title);
							$title2 = '%';
							foreach ($pieces as $piece) {
								$title2 .= str_replace(array("'", "!", '"'), "", $piece) . '%';
							}
							$ckimdbid = $this->db->queryOneRow(sprintf("SELECT imdbid FROM movieinfo WHERE replace(replace(replace(title, \"'\", ''), '!', ''), '\"', '') %s %s", $like, $this->db->escapeString($title2)));
						}
					}


					if (isset($ckimdbid['imdbid'])) {
						$imdbId = $this->domovieupdate('tt' . $ckimdbid['imdbid'], 'Local DB', $arr['id']);
						if ($imdbId === false) {
							$this->db->queryExec(sprintf("UPDATE releases SET imdbid = 0000000 WHERE id = %d", $arr["id"]));
						}

						if ($this->echooutput) {
							$this->c->doEcho($this->c->alternateOver("Found Local: ") . $this->c->headerOver($moviename), true);
						}
						continue;
					}

					if ($this->echooutput) {
						$this->c->doEcho($this->c->primaryOver("Looking up: ") . $this->c->headerOver($moviename), true);
					}

					// Check OMDbapi first
					if ($year === true && preg_match('/\d{4}/', $year)) {
						$url = 'http://www.omdbapi.com/?t=' . str_replace(' ', '%20', $movienameonly) . '&y=' . $year . '&r=json';
					} else {
						$url = 'http://www.omdbapi.com/?t=' . str_replace(' ', '%20', $movienameonly) . '&r=json';
					}
					$omdbData = nzedb\utility\getUrl($url);
					if ($omdbData !== false) {
						$omdbid = json_decode($omdbData);

						if (isset($omdbid->imdbID)) {
							$imdbId = $this->domovieupdate($omdbid->imdbID, 'OMDbAPI', $arr['id']);
							if ($imdbId !== false) {
								continue;
							}
						}
					}

					// Check on trakt.
					$traktimdbid = $trakt->traktMoviesummary($moviename);
					if ($traktimdbid !== false) {
						$imdbId = $this->domovieupdate($traktimdbid, 'Trakt', $arr['id']);
						if ($imdbId === false) {
							// No imdb id found, set to all zeros so we don't process again.
							$this->db->queryExec(sprintf("UPDATE releases SET imdbid = 0000000 WHERE id = %d", $arr["id"]));
						} else {
							continue;
						}
					}
					// Check on search engines.
					else if ($googleban == false && $googlelimit <= 40) {
						$moviename1 = str_replace(' ', '+', $moviename);
						$buffer = nzedb\utility\getUrl("https://www.google.com/search?hl=en&as_q=" . urlencode($moviename1) . "&as_epq=&as_oq=&as_eq=&as_nlo=&as_nhi=&lr=&cr=&as_qdr=all&as_sitesearch=imdb.com&as_occt=any&safe=images&tbs=&as_filetype=&as_rights=");

						// Make sure we got some data.
						if ($buffer !== false && strlen($buffer)) {
							$googlelimit++;
							if (!preg_match('/To continue, please type the characters below/i', $buffer)) {
								$imdbId = $this->domovieupdate($buffer, 'Google1', $arr['id']);
								if ($imdbId === false) {
									if (preg_match('/(?P<name>[\w+].+)(\+\(\d{4}\))/i', $moviename1, $result)) {
										$buffer = nzedb\utility\getUrl("https://www.google.com/search?hl=en&as_q=" . urlencode($result["name"]) . "&as_epq=&as_oq=&as_eq=&as_nlo=&as_nhi=&lr=&cr=&as_qdr=all&as_sitesearch=imdb.com&as_occt=any&safe=images&tbs=&as_filetype=&as_rights=");

										if ($buffer !== false && strlen($buffer)) {
											$googlelimit++;
											$imdbId = $this->domovieupdate($buffer, 'Google2', $arr["id"]);
											if ($imdbId === false) {
												//no imdb id found, set to all zeros so we don't process again
												$this->db->queryExec(sprintf("UPDATE releases SET imdbid = 0000000 WHERE id = %d", $arr["id"]));
											} else {
												continue;
											}
										} else {
											$googleban = true;
											if ($this->bingSearch($moviename, $arr["id"]) === true) {
												continue;
											} else if ($this->yahooSearch($moviename, $arr["id"]) === true) {
												continue;
											}
										}
									} else {
										$googleban = true;
										if ($this->bingSearch($moviename, $arr["id"]) === true) {
											continue;
										} else if ($this->yahooSearch($moviename, $arr["id"]) === true) {
											continue;
										}
									}
								} else {
									continue;
								}
							} else {
								$googleban = true;
								if ($this->bingSearch($moviename, $arr["id"]) === true) {
									continue;
								} else if ($this->yahooSearch($moviename, $arr["id"]) === true) {
									continue;
								}
							}
						} else {
							if ($this->bingSearch($moviename, $arr["id"]) === true) {
								continue;
							} else if ($this->yahooSearch($moviename, $arr["id"]) === true) {
								continue;
							}
						}
					} else if ($this->bingSearch($moviename, $arr["id"]) === true) {
						continue;
					} else if ($this->yahooSearch($moviename, $arr["id"]) === true) {
						continue;
					} else if (!isset($ckimdbid['imdbid']) && $year === true) {
						$ckimdbid = $this->db->queryOneRow(sprintf('SELECT imdbid FROM movieinfo WHERE title %s %s', $like, "'%" . $parsed['title'] . "%'"));
						if (isset($ckimdbid['imdbid'])) {
							$imdbId = $this->domovieupdate('tt' . $ckimdbid['imdbid'], 'Local DB', $arr['id']);
							if ($imdbId === false) {
								$this->db->queryExec(sprintf("UPDATE releases SET imdbid = 0000000 WHERE id = %d", $arr["id"]));
							}

							continue;
						}
					} else {
						if ($this->echooutput) {
							$this->c->doEcho($this->c->error("Exceeded request limits on google.com bing.com and yahoo.com."));
						}
						break;
					}
				} else {
					$this->db->queryExec(sprintf("UPDATE releases SET imdbid = 0000000 WHERE id = %d", $arr["id"]));
					continue;
				}
			}
		}
	}

	public function bingSearch($moviename, $relID)
	{
		$result = '';
		if ($this->binglimit <= 40) {
			$moviename = str_replace(' ', '+', $moviename);
			if (preg_match('/(?P<name>[\w+].+)(\+(?P<year>\(\d{4}\)))?/i', $moviename, $result)) {
				if (isset($result["year"]) && !empty($result["year"])) {
					$buffer = nzedb\utility\getUrl("http://www.bing.com/search?q=" . $result["name"] . urlencode($result["year"]) . "+" . urlencode("site:imdb.com") . "&qs=n&form=QBRE&pq=" . $result["name"] . urlencode($result["year"]) . "+" . urlencode("site:imdb.com") . "&sc=4-38&sp=-1&sk=");
					if ($buffer !== false && strlen($buffer)) {
						$this->binglimit++;
						$imdbId = $this->domovieupdate($buffer, 'Bing1', $relID);
						if ($imdbId === false) {
							$buffer = nzedb\utility\getUrl("http://www.bing.com/search?q=" . $result["name"] . "+" . urlencode("site:imdb.com") . "&qs=n&form=QBRE&pq=" . $result["name"] . "+" . urlencode("site:imdb.com") . "&sc=4-38&sp=-1&sk=");
							if ($buffer !== false && strlen($buffer)) {
								$this->binglimit++;
								$imdbId = $this->domovieupdate($buffer, 'Bing2', $relID);
								if ($imdbId === false) {
									$this->db->queryExec(sprintf("UPDATE releases SET imdbid = 0000000 WHERE id = %d", $relID));
									return true;
								} else {
									return true;
								}
							} else {
								return false;
							}
						} else {
							return true;
						}
					} else {
						return false;
					}
				} else {
					$buffer = nzedb\utility\getUrl("http://www.bing.com/search?q=" . $result["name"] . "+" . urlencode("site:imdb.com") . "&qs=n&form=QBRE&pq=" . $result["name"] . "+" . urlencode("site:imdb.com") . "&sc=4-38&sp=-1&sk=");
					if ($buffer !== false && strlen($buffer)) {
						$this->binglimit++;
						$imdbId = $this->domovieupdate($buffer, 'Bing2', $relID);
						if ($imdbId === false) {
							$this->db->queryExec(sprintf("UPDATE releases SET imdbid = 0000000 WHERE id = %d", $relID));
							return true;
						} else {
							return true;
						}
					} else {
						return false;
					}
				}
			} else {
				$this->db->queryExec(sprintf("UPDATE releases SET imdbid = 0000000 WHERE id = %d", $relID));
				return true;
			}
		} else {
			return false;
		}
	}

	public function yahooSearch($moviename, $relID)
	{
		$result = '';
		if ($this->yahoolimit <= 40) {
			$moviename = str_replace(' ', '+', $moviename);
			if (preg_match('/(?P<name>[\w+].+)(\+(?P<year>\(\d{4}\)))?/i', $moviename, $result)) {
				if (isset($result["year"]) && !empty($result["year"])) {
					$buffer = nzedb\utility\getUrl("http://search.yahoo.com/search?n=15&ei=UTF-8&va_vt=any&vo_vt=any&ve_vt=any&vp_vt=any&vf=all&vm=p&fl=0&fr=yfp-t-900&p=" . $result["name"] . "+" . urlencode($result["year"]) . "&vs=imdb.com");
					if ($buffer !== false && strlen($buffer)) {
						$this->yahoolimit++;
						$imdbId = $this->domovieupdate($buffer, 'Yahoo1', $relID);
						if ($imdbId === false) {
							$buffer = nzedb\utility\getUrl("http://search.yahoo.com/search?n=15&ei=UTF-8&va_vt=any&vo_vt=any&ve_vt=any&vp_vt=any&vf=all&vm=p&fl=0&fr=yfp-t-900&p=" . $result["name"] . "&vs=imdb.com");
							if ($buffer !== false && strlen($buffer)) {
								$this->yahoolimit++;
								$imdbId = $this->domovieupdate($buffer, 'Yahoo2', $relID);
								if ($imdbId === false) {
									$this->db->queryExec(sprintf("UPDATE releases SET imdbid = 0000000 WHERE id = %d", $relID));
									return true;
								} else {
									return true;
								}
							} else {
								return false;
							}
						} else {
							return true;
						}
					}
					return false;
				} else {
					$buffer = nzedb\utility\getUrl("http://search.yahoo.com/search?n=15&ei=UTF-8&va_vt=any&vo_vt=any&ve_vt=any&vp_vt=any&vf=all&vm=p&fl=0&fr=yfp-t-900&p=" . $result["name"] . "&vs=imdb.com");
					if ($buffer !== false && strlen($buffer)) {
						$this->yahoolimit++;
						$imdbId = $this->domovieupdate($buffer, 'Yahoo2', $relID);
						if ($imdbId === false) {
							$this->db->queryExec(sprintf("UPDATE releases SET imdbid = 0000000 WHERE id = %d", $relID));
							return true;
						} else {
							return true;
						}
					} else {
						return false;
					}
				}
			} else {
				$this->db->queryExec(sprintf("UPDATE releases SET imdbid = 0000000 WHERE id = %d", $relID));
				return true;
			}
		} else {
			return false;
		}
	}

	public function parseMovieSearchName($releasename)
	{
		// Check if it's foreign ?
		$cat = new Category();
		if (!$cat->isMovieForeign($releasename)) {
			$name = $year = '';
			$followingList = '[^\w]((1080|480|720)p|AC3D|Directors([^\w]CUT)?|DD5\.1|(DVD|BD|BR)(Rip)?|BluRay|divx|HDTV|iNTERNAL|LiMiTED|(Real\.)?Proper|RE(pack|Rip)|Sub\.?(fix|pack)|Unrated|WEB-DL|(x|H)[-._ ]?264|xvid)[^\w]';

			/* Initial scan of getting a year/name.
			 * [\w. -]+ Gets 0-9a-z. - characters, most scene movie titles contain these chars.
			 * ie: [61420]-[FULL]-[a.b.foreignEFNet]-[ Coraline.2009.DUTCH.INTERNAL.1080p.BluRay.x264-VeDeTT ]-[21/85] - "vedett-coralien-1080p.r04" yEnc
			 * Then we look up the year, (19|20)\d\d, so $matches[1] would be Coraline $matches[2] 2009
			 */
			if (preg_match('/(?P<name>[\w. -]+)[^\w](?P<year>(19|20)\d\d)/i', $releasename, $matches)) {
				$name = $matches['name'];
				$year = $matches['year'];

			/* If we didn't find a year, try to get a name anyways.
			 * Try to look for a title before the $followingList and after anything but a-z0-9 two times or more (-[ for example)
			 */
			} else if (preg_match('/([^\w]{2,})?(?P<name>[\w .-]+?)' . $followingList . '/i', $releasename, $matches)) {
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
						$this->c->doEcho("DB name: {$releasename}", true);
					}

					return array('title' => $name, 'year' => $year);
				}
			}
		}
		return false;
	}

	public function getUpcoming($type, $source = "rottentomato")
	{
		$sql = sprintf("SELECT * FROM upcoming WHERE source = %s AND typeid = %d", $this->db->escapeString($source), $type);
		$list = $this->db->queryOneRow($sql);
		if (!$list) {
			$this->updateUpcoming();
			$sql = sprintf("SELECT * FROM upcoming WHERE source = %s AND typeid = %d", $this->db->escapeString($source), $type);
			$list = $this->db->queryOneRow($sql);
		}
		return $list;
	}

	public function updateUpcoming()
	{
		$s = new Sites();
		$site = $s->get();
		if ($this->echooutput) {
			$this->c->doEcho($this->c->header("Updating movie schedule using rotten tomatoes."));
		}
		if (isset($site->rottentomatokey)) {
			$rt = new RottenTomato($site->rottentomatokey);

			$retbo = $rt->getBoxOffice();
			$test = @json_decode($retbo);
			if (!$test || $retbo === "") {
				sleep(1);
				$retbo = $rt->getBoxOffice();
				$test = @json_decode($retbo);
				if (!$test || $retbo === "") {
					if ($this->echooutput) {
						exit($this->c->error("\nUnable to fetch from Rotten Tomatoes, verify your API Key\n"));
					}
				}
			}
			if ($test) {
				$cnt1 = $this->updateInsUpcoming('rottentomato', Movie::SRC_BOXOFFICE, $retbo);
				if ($this->echooutput && $cnt1 > 0) {
					$this->c->doEcho($this->c->header("Added/updated movies to the box office list."));
				} else {
					if ($this->echooutput) {
						$this->c->doEcho($this->c->primary("No new updates for box office list."));
					}
				}
			}

			$rett = $rt->getInTheaters();
			$test = @json_decode($rett);
			if (!$test || $rett === "") {
				sleep(1);
				$rett = $rt->getInTheaters();
				$test = @json_decode($rett);
				if (!$test || $rett === "") {
					if ($this->echooutput) {
						exit($this->c->error("\nUnable to fetch from Rotten Tomatoes, verify your API Key\n"));
					}
				}
			}
			if ($test) {
				$cnt2 = $this->updateInsUpcoming('rottentomato', Movie::SRC_INTHEATRE, $rett);
				if ($this->echooutput && $cnt2 > 0) {
					echo $this->c->header("Added/updated movies to the theaters list.");
				} else {
					if ($this->echooutput) {
						$this->c->doEcho($this->c->primary("No new updates for theaters list."));
					}
				}
			}

			$reto = $rt->getOpening();
			$test = @json_decode($reto);
			if (!$test || $reto === "") {
				sleep(1);
				$reto = $rt->getOpening();
				$test = @json_decode($reto);
				if (!$test || $reto === "") {
					if ($this->echooutput) {
						exit($this->c->error("\nUnable to fetch from Rotten Tomatoes, verify your API Key\n"));
					}
				}
			}
			if ($test) {
				$cnt3 = $this->updateInsUpcoming('rottentomato', Movie::SRC_OPENING, $reto);
				if ($this->echooutput && $cnt3 > 0) {
					$this->c->doEcho($this->c->header("Added/updated movies to the opening list."));
				} else {
					if ($this->echooutput) {
						$this->c->doEcho($this->c->primary("No new updates for upcoming list."));
					}
				}
			}

			$retu = $rt->getUpcoming();
			$test = @json_decode($retu);
			if (!$test || $retu === "") {
				sleep(1);
				$retu = $rt->getUpcoming();
				$test = @json_decode($retu);
				if (!$test || $retu === "") {
					if ($this->echooutput) {
						exit($this->c->error("\nUnable to fetch from Rotten Tomatoes, verify your API Key\n"));
					}
				}
			}
			if ($test) {
				$cnt4 = $this->updateInsUpcoming('rottentomato', Movie::SRC_UPCOMING, $retu);
				if ($this->echooutput && $cnt4 > 0) {
					$this->c->doEcho($this->c->header("Added/updated movies to the upcoming list."));
				} else {
					if ($this->echooutput) {
						$this->c->doEcho($this->c->primary("No new updates for upcoming list."));
					}
				}
			}

			$retr = $rt->getDVDReleases();
			$test = @json_decode($retr);
			if (!$test || $retr === "") {
				sleep(1);
				$retr = $rt->getDVDReleases();
				$test = @json_decode($retr);
				if (!$test || $retr === "") {
					if ($this->echooutput) {
						exit($this->c->error("\nUnable to fetch from Rotten Tomatoes, verify your API Key\n"));
					}
				}
			}
			if ($test) {
				$cnt5 = $this->updateInsUpcoming('rottentomato', Movie::SRC_DVD, $retr);
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

	public function updateInsUpcoming($source, $type, $info)
	{
		if ($this->db->dbSystem() === 'mysql') {
			return $this->db->Exec(sprintf("INSERT INTO upcoming (source, typeid, info, updateddate) VALUES (%s, %d, %s, NOW()) ON DUPLICATE KEY UPDATE info = %s", $this->db->escapeString($source), $type, $this->db->escapeString($info), $this->db->escapeString($info)));
		} else {
			$ckid = $this->db->queryOneRow(sprintf('SELECT id FROM upcoming WHERE source = %s AND typeid = %d AND info = %s', $this->db->escapeString($source), $type, $this->db->escapeString($info)));
			if (!isset($ckid['id'])) {
				return $this->db->Exec(sprintf("INSERT INTO upcoming (source, typeid, info, updateddate) VALUES (%s, %d, %s, NOW())", $this->db->escapeString($source), $type, $this->db->escapeString($info)));
			} else {
				return $this->db->Exec(sprintf('UPDATE upcoming SET source = %s, typeid = %s, info = %s, updateddate = NOW() WHERE id = %d', $this->db->escapeString($source), $type, $this->db->escapeString($info), $ckid['id']));
			}
		}
	}

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
