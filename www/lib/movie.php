<?php
require_once(WWW_DIR.'lib/framework/db.php');
require_once(WWW_DIR.'lib/TMDb.php');
require_once(WWW_DIR.'lib/category.php');
require_once(WWW_DIR.'lib/nfo.php');
require_once(WWW_DIR.'lib/site.php');
require_once(WWW_DIR.'lib/util.php');
require_once(WWW_DIR.'lib/releaseimage.php');
require_once(WWW_DIR.'lib/releases.php');
require_once(WWW_DIR.'lib/rottentomato.php');
require_once(WWW_DIR.'lib/trakttv.php');

class Movie
{
	const SRC_BOXOFFICE = 1;
	const SRC_INTHEATRE = 2;
	const SRC_OPENING = 3;
	const SRC_UPCOMING = 4;
	const SRC_DVD = 5;

	function Movie($echooutput=false)
	{
		$this->echooutput = $echooutput;
		$this->db = new DB();
		$s = new Sites();
		$site = $s->get();
		$this->apikey = $site->tmdbkey;
		$this->binglimit = $this->yahoolimit = 0;
		$this->debug = ($site->debuginfo == "0") ? false : true;
		$this->imdburl = ($site->imdburl == "0") ? false : true;
		$this->imdblanguage = (!empty($site->imdblanguage)) ? $site->imdblanguage : "en";
		$this->imgSavePath = WWW_DIR.'covers/movies/';
		$this->movieqty = (!empty($site->maximdbprocessed)) ? $site->maximdbprocessed : 100;
		$this->service = '';
	}

	public function getMovieInfo($imdbId)
	{
		return $this->db->queryOneRow(sprintf("SELECT * FROM movieinfo WHERE imdbid = %d", $imdbId));
	}

	public function getMovieInfoMultiImdb($imdbIds)
	{
		$allids = str_replace(',,', ',', str_replace(array('(,', ' ,', ', )', ',)'), '', implode(',', $imdbIds)));
		$sql = sprintf("SELECT DISTINCT movieinfo.*, releases.imdbid AS relimdb FROM movieinfo LEFT OUTER JOIN releases ON releases.imdbid = movieinfo.imdbid WHERE movieinfo.imdbid IN (%s)", $allids);
		return $this->db->query($sql);
	}

	public function getRange($start, $num)
	{
		if ($start === false)
			$limit = "";
		else
			$limit = " LIMIT ".$num." OFFSET ".$start;

		return $this->db->query(" SELECT * FROM movieinfo ORDER BY createddate DESC".$limit);
	}

	public function getCount()
	{
		$res = $this->db->queryOneRow("SELECT COUNT(id) AS num FROM movieinfo");
		return $res["num"];
	}

	public function getMovieCount($cat, $maxage=-1, $excludedcats=array())
	{
		$browseby = $this->getBrowseBy();

		$catsrch = "";
		if (count($cat) > 0 && $cat[0] != -1)
		{
			$catsrch = " (";
			foreach ($cat as $category)
			{
				if ($category != -1)
				{
					$categ = new Category();
					if ($categ->isParent($category))
					{
						$children = $categ->getChildren($category);
						$chlist = "-99";
						foreach ($children as $child)
							$chlist.=", ".$child["id"];

						if ($chlist != "-99")
								$catsrch .= " r.categoryid IN (".$chlist.") OR ";
					}
					else
					{
						$catsrch .= sprintf(" r.categoryid = %d OR ", $category);
					}
				}
			}
			$catsrch.= "1=2 )";
		}

		if ($maxage > 0)
		{
			if ($this->db->dbSystem() == 'mysql')
				$maxage = sprintf(' AND r.postdate > NOW() - INTERVAL %d DAY ', $maxage);
			else if ($this->db->dbSystem() == 'pgsql')
				$maxage = sprintf(" AND r.postdate > NOW() - INTERVAL '%d DAYS' ", $maxage);
		}
		else
			$maxage = '';

		$exccatlist = "";
		if (count($excludedcats) > 0)
			$exccatlist = " AND r.categoryid NOT IN (".implode(",", $excludedcats).")";

		$rel = new Releases();

		$sql = sprintf("SELECT COUNT(DISTINCT r.imdbid) AS num FROM releases r INNER JOIN movieinfo m ON m.imdbid = r.imdbid AND m.title != '' WHERE r.passwordstatus <= %d AND %s %s %s %s ", $rel->showPasswords(), $browseby, $catsrch, $maxage, $exccatlist);
		$res = $this->db->queryOneRow($sql);
		return $res["num"];
	}

	public function getMovieRange($cat, $start, $num, $orderby, $maxage=-1, $excludedcats=array())
	{
		$browseby = $this->getBrowseBy();

		if ($start === false)
			$limit = "";
		else
			$limit = " LIMIT ".$num." OFFSET ".$start;

		$catsrch = "";
		if (count($cat) > 0 && $cat[0] != -1)
		{
			$catsrch = " (";
			foreach ($cat as $category)
			{
				if ($category != -1)
				{
					$categ = new Category();
					if ($categ->isParent($category))
					{
						$children = $categ->getChildren($category);
						$chlist = "-99";
						foreach ($children as $child)
							$chlist.=", ".$child["id"];

						if ($chlist != "-99")
								$catsrch .= " r.categoryid IN (".$chlist.") OR ";
					}
					else
					{
						$catsrch .= sprintf(" r.categoryid = %d OR ", $category);
					}
				}
			}
			$catsrch.= "1=2 )";
		}

		$maxage = '';
		if ($maxage > 0)
		{
			if ($this->db->dbSystem() == 'mysql')
				$maxage = sprintf(' AND r.postdate > NOW() - INTERVAL %d DAY ', $maxage);
			else
				$maxage = sprintf(" AND r.postdate > NOW() - INTERVAL '%d DAYS' ", $maxage);
		}

		$exccatlist = "";
		if (count($excludedcats) > 0)
			$exccatlist = " AND r.categoryid NOT IN (".implode(",", $excludedcats).")";

		$order = $this->getMovieOrder($orderby);
		if ($this->db->dbSystem() == 'mysql')
			$sql = sprintf("SELECT GROUP_CONCAT(r.id ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_id, GROUP_CONCAT(r.rarinnerfilecount ORDER BY r.postdate DESC SEPARATOR ',') as grp_rarinnerfilecount, GROUP_CONCAT(r.haspreview ORDER BY r.postdate DESC SEPARATOR ',') AS grp_haspreview, GROUP_CONCAT(r.passwordstatus ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_password, GROUP_CONCAT(r.guid ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_guid, GROUP_CONCAT(rn.id ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_nfoid, GROUP_CONCAT(groups.name ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_grpname, GROUP_CONCAT(r.searchname ORDER BY r.postdate DESC SEPARATOR '#') AS grp_release_name, GROUP_CONCAT(r.postdate ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_postdate, GROUP_CONCAT(r.size ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_size, GROUP_CONCAT(r.totalpart ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_totalparts, GROUP_CONCAT(r.comments ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_comments, GROUP_CONCAT(r.grabs ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_grabs, m.*, groups.name AS group_name, rn.id as nfoid FROM releases r LEFT OUTER JOIN groups ON groups.id = r.groupid INNER JOIN movieinfo m ON m.imdbid = r.imdbid and m.title != '' LEFT OUTER JOIN releasenfo rn ON rn.releaseid = r.id AND rn.nfo IS NOT NULL WHERE r.passwordstatus <= (SELECT value FROM site WHERE setting='showpasswordedrelease') AND %s %s %s %s GROUP BY m.imdbid ORDER BY %s %s".$limit, $browseby, $catsrch, $maxage, $exccatlist, $order[0], $order[1]);
		else
		{
			$rel = new Releases();
			$sql = sprintf("SELECT STRING_AGG(r.id::text, ',' ORDER BY r.postdate DESC) AS grp_release_id, STRING_AGG(r.rarinnerfilecount::text, ',' ORDER BY r.postdate DESC) as grp_rarinnerfilecount, STRING_AGG(r.haspreview::text, ',' ORDER BY r.postdate DESC) AS grp_haspreview, STRING_AGG(r.passwordstatus::text, ',' ORDER BY r.postdate) AS grp_release_password, STRING_AGG(r.guid, ',' ORDER BY r.postdate DESC) AS grp_release_guid, STRING_AGG(rn.id::text, ',' ORDER BY r.postdate DESC) AS grp_release_nfoid, STRING_AGG(groups.name, ',' ORDER BY r.postdate DESC) AS grp_release_grpname, STRING_AGG(r.searchname, '#' ORDER BY r.postdate) AS grp_release_name, STRING_AGG(r.postdate::text, ',' ORDER BY r.postdate DESC) AS grp_release_postdate, STRING_AGG(r.size::text, ',' ORDER BY r.postdate DESC) AS grp_release_size, STRING_AGG(r.totalpart::text, ',' ORDER BY r.postdate DESC) AS grp_release_totalparts, STRING_AGG(r.comments::text, ',' ORDER BY r.postdate DESC) AS grp_release_comments, STRING_AGG(r.grabs::text, ',' ORDER BY r.postdate DESC) AS grp_release_grabs, m.*, groups.name AS group_name, rn.id as nfoid FROM releases r LEFT OUTER JOIN groups ON groups.id = r.groupid INNER JOIN movieinfo m ON m.imdbid = r.imdbid and m.title != '' LEFT OUTER JOIN releasenfo rn ON rn.releaseid = r.id AND rn.nfo IS NOT NULL WHERE r.passwordstatus <= %s AND %s %s %s %s GROUP BY m.imdbid, m.id, groups.name, rn.id ORDER BY %s %s".$limit, $rel->showPasswords(), $browseby, $catsrch, $maxage, $exccatlist, $order[0], $order[1]);
		}
		return $this->db->query($sql);
	}

	public function getMovieOrder($orderby)
	{
		$order = ($orderby == '') ? 'MAX(r.postdate)' : $orderby;
		$orderArr = explode("_", $order);
		switch($orderArr[0]) {
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
		if ($this->db->dbSystem() == 'mysql')
			$like = ' LIKE(';
		foreach ($browsebyArr as $bb)
		{
			if (isset($_REQUEST[$bb]) && !empty($_REQUEST[$bb]))
			{
				$bbv = stripslashes($_REQUEST[$bb]);
				if ($bb == 'rating')
					$bbv .= '.';
				if ($bb == 'imdb')
					$browseby .= 'm.'.$bb.'id = '.$bbv.' AND ';
				else
					$browseby .= 'm.'.$bb.$like.$this->db->escapeString('%'.$bbv.'%').') AND ';
			}
		}
		return $browseby;
	}

	public function makeFieldLinks($data, $field)
	{
		if ($data[$field] == "")
			return "";

		$tmpArr = explode(', ',$data[$field]);
		$newArr = array();
		$i = 0;
		foreach($tmpArr as $ta) {
			if ($i > 5) { break; } //only use first 6
			$newArr[] = '<a href="'.WWW_TOP.'/movies?'.$field.'='.urlencode($ta).'" title="'.$ta.'">'.$ta.'</a>';
			$i++;
		}
		return implode(', ', $newArr);
	}

	public function update($id, $title, $tagline, $plot, $year, $rating, $genre, $director, $actors, $language, $cover, $backdrop)
	{
		$this->db->queryExec(sprintf("UPDATE movieinfo SET title = %s, tagline = %s, plot = %s, year = %s, rating = %s, genre = %s, director = %s, actors = %s, language = %s, cover = %d, backdrop = %d, updateddate = NOW() WHERE imdbid = %d", $this->db->escapeString($title), $this->db->escapeString($tagline), $this->db->escapeString($plot), $this->db->escapeString($year), $this->db->escapeString($rating), $this->db->escapeString($genre), $this->db->escapeString($director), $this->db->escapeString($actors), $this->db->escapeString($language), $cover, $backdrop, $id));
	}

	public function updateMovieInfo($imdbId)
	{
		$ri = new ReleaseImage();

		if ($this->echooutput && $this->service != '')
			echo "Fetching IMDB info from TMDB using IMDB ID: ".$imdbId."\n";

		//check themoviedb for imdb info
		$tmdb = $this->fetchTmdbProperties($imdbId);

		//check imdb for movie info
		$imdb = $this->fetchImdbProperties($imdbId);

		if (!$imdb && !$tmdb)
		{
			if ($this->echooutput && $this->service != '')
				echo "Unable to get movie information for IMDB ID: ".$imdbId." on tmdb or imdb.com\n";
			return false;
		}

		$mov = array();
		$mov['imdb_id'] = $imdbId;
		$mov['tmdb_id'] = (!isset($tmdb['tmdb_id']) || $tmdb['tmdb_id'] == '') ? "NULL" : $tmdb['tmdb_id'];

		//prefer tmdb cover over imdb cover
		$mov['cover'] = 0;
		if (isset($tmdb['cover']) && $tmdb['cover'] != '') {
			$mov['cover'] = $ri->saveImage($imdbId.'-cover', $tmdb['cover'], $this->imgSavePath);
		} elseif (isset($imdb['cover']) && $imdb['cover'] != '') {
			$mov['cover'] = $ri->saveImage($imdbId.'-cover', $imdb['cover'], $this->imgSavePath);
		}

		$mov['backdrop'] = 0;
		if (isset($tmdb['backdrop']) && $tmdb['backdrop'] != '') {
			$mov['backdrop'] = $ri->saveImage($imdbId.'-backdrop', $tmdb['backdrop'], $this->imgSavePath, 1024, 768);
		}

		$mov['title'] = '';
		if (isset($imdb['title']) && $imdb['title'] != '') {
			$mov['title'] = $imdb['title'];
		} elseif (isset($tmdb['title']) && $tmdb['title'] != '') {
			$mov['title'] = $tmdb['title'];
		}
		$mov['title'] = html_entity_decode($mov['title'], ENT_QUOTES, 'UTF-8');

		$mov['rating'] = '';
		if (isset($imdb['rating']) && $imdb['rating'] != '') {
			$mov['rating'] = $imdb['rating'];
		} elseif (isset($tmdb['rating']) && $tmdb['rating'] != '') {
			$mov['rating'] = $tmdb['rating'];
		}

		$mov['tagline'] = '';
		if (isset($imdb['tagline']) && $imdb['tagline'] != '') {
			$mov['tagline'] = html_entity_decode($imdb['tagline'], ENT_QUOTES, 'UTF-8');
		} elseif (isset($tmdb['tagline']) && $tmdb['tagline'] != '') {
			$mov['tagline'] = $tmdb['tagline'];
		}

		$mov['plot'] = '';
		if (isset($imdb['plot']) && $imdb['plot'] != '') {
			$mov['plot'] = $imdb['plot'];
		} elseif (isset($tmdb['plot']) && $tmdb['plot'] != '') {
			$mov['plot'] = $tmdb['plot'];
		}
		$mov['plot'] = html_entity_decode($mov['plot'], ENT_QUOTES, 'UTF-8');

		$mov['year'] = '';
		if (isset($imdb['year']) && $imdb['year'] != '') {
			$mov['year'] = $imdb['year'];
		} elseif (isset($tmdb['year']) && $tmdb['year'] != '') {
			$mov['year'] = $tmdb['year'];
		}

		$mov['genre'] = '';
		if (isset($tmdb['genre']) && $tmdb['genre'] != '') {
			$mov['genre'] = $tmdb['genre'];
		} elseif (isset($imdb['genre']) && $imdb['genre'] != '') {
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
		if ($this->db->dbSystem() == 'mysql')
		{
			$movieId = $this->db->queryInsert(sprintf("INSERT INTO movieinfo (imdbid, tmdbid, title, rating, tagline, plot, year, genre, type, director, actors, language, cover, backdrop, createddate, updateddate) VALUES (%d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, %d, NOW(), NOW()) ON DUPLICATE KEY UPDATE imdbid = %d, tmdbid = %s, title = %s, rating = %s, tagline = %s, plot = %s, year = %s, genre = %s, type = %s, director = %s, actors = %s, language = %s, cover = %d, backdrop = %d, updateddate = NOW()",
				$mov['imdb_id'], $mov['tmdb_id'], $this->db->escapeString($movtitle), $this->db->escapeString($mov['rating']), $this->db->escapeString($mov['tagline']), $this->db->escapeString($mov['plot']), $this->db->escapeString($mov['year']), $this->db->escapeString(substr($mov['genre'], 0, 64)), $this->db->escapeString($mov['type']), $this->db->escapeString($mov['director']), $this->db->escapeString($mov['actors']), $this->db->escapeString(substr($mov['language'], 0, 64)), $mov['cover'], $mov['backdrop'],
				$mov['imdb_id'], $mov['tmdb_id'], $this->db->escapeString($movtitle), $this->db->escapeString($mov['rating']), $this->db->escapeString($mov['tagline']), $this->db->escapeString($mov['plot']), $this->db->escapeString($mov['year']), $this->db->escapeString(substr($mov['genre'], 0, 64)), $this->db->escapeString($mov['type']), $this->db->escapeString($mov['director']), $this->db->escapeString($mov['actors']), $this->db->escapeString(substr($mov['language'], 0, 64)), $mov['cover'], $mov['backdrop']));
		}
		else if ($this->db->dbSystem() == 'pgsql')
		{
			$ckid = $this->db->queryOneRow(sprintf('SELECT id FROM movieinfo WHERE imdbid = %d', $mov['imdb_id']));
			if (!isset($ckid['id']))
				$movieId = $this->db->queryInsert(sprintf("INSERT INTO movieinfo (imdbid, tmdbid, title, rating, tagline, plot, year, genre, type, director, actors, language, cover, backdrop, createddate, updateddate) VALUES (%d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, %d, NOW(), NOW())", $mov['imdb_id'], $mov['tmdb_id'], $this->db->escapeString($movtitle), $this->db->escapeString($mov['rating']), $this->db->escapeString($mov['tagline']), $this->db->escapeString($mov['plot']), $this->db->escapeString($mov['year']), $this->db->escapeString($mov['genre']), $this->db->escapeString($mov['type']), $this->db->escapeString($mov['director']), $this->db->escapeString($mov['actors']), $this->db->escapeString($mov['language']), $mov['cover'], $mov['backdrop']));
			else
			{
				$movieId = $ckid['id'];
				$this->db->queryExec(sprintf('UPDATE movieinfo SET tmdbid = %d, title = %s, rating = %s, tagline = %s, plot = %s, year = %s, genre = %s, type = %s, director = %s, actors = %s, language = %s, cover = %d, backdrop = %d, updateddate = NOW() WHERE id = %d', $mov['tmdb_id'], $this->db->escapeString($movtitle), $this->db->escapeString($mov['rating']), $this->db->escapeString($mov['tagline']), $this->db->escapeString($mov['plot']), $this->db->escapeString($mov['year']), $this->db->escapeString($mov['genre']), $this->db->escapeString($mov['type']), $this->db->escapeString($mov['director']), $this->db->escapeString($mov['actors']), $this->db->escapeString($mov['language']), $mov['cover'], $mov['backdrop'], $movieId));
			}
		}

		if ($movieId)
		{
			if ($this->echooutput && $this->service != '')
				echo "Added/updated movie: ".$movtitle." (".$mov['year'].") - ".$mov['imdb_id'].".\n";
		}
		else
		{
			if ($this->echooutput && $this->service != '')
				echo "Nothing to update for movie: ".$movtitle." (".$mov['year'].") - ".$mov['imdb_id']."\n";
		}

		return $movieId;
	}

	public function fetchTmdbProperties($imdbId, $text=false)
	{
		$tmdb = new TMDb($this->apikey, $this->imdblanguage);
		if ($text == false)
			$lookupId = 'tt'.$imdbId;
		else
			$lookupId = $imdbId;

		try {$tmdbLookup = $tmdb->getMovie($lookupId);}
		catch (exception $e) {return false;}

		if (!$tmdbLookup) {return false;};
		if (isset($tmdbLookup['status_code']) && $tmdbLookup['status_code'] !== 1) { return false;}

		$ret = array();
		$ret['title'] = $tmdbLookup['title'];
		$ret['tmdb_id'] = $tmdbLookup['id'];
		$ImdbID = str_replace('tt','',$tmdbLookup['imdb_id']);
		$ret['imdb_id'] = $ImdbID;
		if (isset($tmdbLookup['vote_average']))	{$ret['rating'] = ($tmdbLookup['vote_average'] == 0) ? '' : $tmdbLookup['vote_average'];}
		if (isset($tmdbLookup['overview']))		{$ret['plot'] = $tmdbLookup['overview'];}
		if (isset($tmdbLookup['tagline']))		{$ret['tagline'] = $tmdbLookup['tagline'];}
		if (isset($tmdbLookup['release_date'])) {$ret['year'] = date("Y", strtotime($tmdbLookup['release_date']));}
		if (isset($tmdbLookup['genres']) && sizeof($tmdbLookup['genres']) > 0)
		{
			$genres = array();
			foreach($tmdbLookup['genres'] as $genre)
			{
				$genres[] = $genre['name'];
			}
			$ret['genre'] = $genres;
		}
		if (isset($tmdbLookup['poster_path']) && sizeof($tmdbLookup['poster_path']) > 0)
			$ret['cover'] = "http://d3gtl9l2a4fn1j.cloudfront.net/t/p/w185".$tmdbLookup['poster_path'];
		if (isset($movie->backdrops) && sizeof($movie->backdrops) > 0)
			$ret['backdrop'] = "http://d3gtl9l2a4fn1j.cloudfront.net/t/p/original".$tmdbLookup['backdrop_path'];
		return $ret;
	}

	public function fetchImdbProperties($imdbId)
	{
		$imdb_regex = array(
			'title'	=> '/<title>(.*?)\s?\(.*?<\/title>/i',
			'tagline'  => '/taglines:<\/h4>\s([^<]+)/i',
			'plot'	 => '/<p itemprop="description">\s*?(.*?)\s*?<\/p>/i',
			'rating'   => '/"ratingValue">([\d.]+)<\/span>/i',
			'year'	 => '/<title>.*?\(.*?(\d{4}).*?<\/title>/i',
			'cover'	=> '/<a.*?href="\/media\/.*?><img src="(.*?)"/i'
		);

		$imdb_regex_multi = array(
			'genre'	=> '/href="\/genre\/(.*?)\?/i',
			'language' => '/<a href="\/language\/.+\'url\'>(.+)<\/a>/i',
			'type' => '/<meta property=\'og\:type\' content=\"(.+)\" \/>/i'
		);

		if ($this->imdburl === false)
			$buffer = getUrl("http://www.imdb.com/title/tt$imdbId/", $this->imdblanguage);
		else
			$buffer = getUrl("http://akas.imdb.com/title/tt$imdbId/");

		// make sure we got some data
		if ($buffer !== false && strlen($buffer))
		{
			$ret = array();
			foreach ($imdb_regex as $field => $regex)
			{
				if (preg_match($regex, $buffer, $matches))
				{
					$match = $matches[1];
					$match = strip_tags(trim(rtrim($match)));
					$ret[$field] = $match;
				}
			}

			foreach ($imdb_regex_multi as $field => $regex)
			{
				if (preg_match_all($regex, $buffer, $matches))
				{
					$match = $matches[1];
					$match = array_map("trim", $match);
					$ret[$field] = $match;
				}
			}

			//actors
			if (preg_match('/<table class="cast_list">(.+)<\/table>/s', $buffer, $hit))
			{
				if (preg_match_all('/<a.*?href="\/name\/(nm\d{1,8})\/.+"name">(.+)<\/span>/i', $hit[0], $results, PREG_PATTERN_ORDER))
				{
					$ret['actors'] = $results[2];
				}
			}

			//directors
			if (preg_match('/Directors?:([\s]+)?<\/h4>(.+)<\/div>/sU', $buffer, $hit))
			{
				if (preg_match_all('/"name">(.*?)<\/span>/is', $hit[0], $results, PREG_PATTERN_ORDER))
				{
					$ret['director'] = $results[1];
				}
			}

			return $ret;
		}
		return false;
	}

	public function domovieupdate($buffer, $service, $id, $processImdb = 1)
	{
		$nfo = new Nfo();
		$imdbId = $nfo->parseImdb($buffer);
		if ($imdbId !== false)
		{
			if ($service == 'nfo')
				$this->service = 'nfo';
			if ($this->echooutput && $this->service != '')
				echo "\n".$service.' found IMDBid: tt'.$imdbId."\n";

			$this->db->queryExec(sprintf('UPDATE releases SET imdbid = %s WHERE id = %d', $this->db->escapeString($imdbId), $id));

			// If set, scan for imdb info.
			if ($processImdb == 1)
			{
				$movCheck = $this->getMovieInfo($imdbId);
				if ($movCheck === false || (isset($movCheck['updateddate']) && (time() - strtotime($movCheck['updateddate'])) > 2592000))
					$movieId = $this->updateMovieInfo($imdbId);
			}
		}
		unset($nfo);
		return $imdbId;
	}

	public function processMovieReleases($releaseToWork = '')
	{
		$ret = 0;
		$trakt = new Trakttv();
		$googleban = false;
		$googlelimit = 0;
		$site = new Sites();

		if ($releaseToWork == '')
		{
			$res = $this->db->query(sprintf("SELECT searchname AS name, id FROM releases WHERE imdbid IS NULL AND nzbstatus = 1 AND categoryid IN (SELECT id FROM category WHERE parentid = %d) AND id IN ( SELECT id FROM releases ORDER BY postdate DESC ) LIMIT %d", Category::CAT_PARENT_MOVIE, $this->movieqty));
			$moviecount = count($res);
		}
		else
		{
			$pieces = explode("           =+=            ", $releaseToWork);
			$res = array(array('name' => $pieces[0], 'id' => $pieces[1]));
			$moviecount = 1;
		}

		if ($moviecount > 0)
		{
			if ($this->echooutput && $moviecount > 1)
				echo "Processing ".$moviecount." movie release(s)."."\n";

			$like = 'ILIKE';
			$inyear = 'year::int';
			if ($this->db->dbSystem() == 'mysql')
			{
				$like = 'LIKE';
				$inyear = 'year';
			}

			foreach ($res as $arr)
			{
				$parsed = $this->parseMovieSearchName($arr['name']);
				if ($parsed !== false)
				{
					$year = false;
					$moviename = $parsed['title'];
					if ($parsed['year'] != '')
					{
						$year = true;
						$moviename .= ' ('.$parsed['year'].')';
					}
					if ($this->echooutput && $releaseToWork == '')
						echo 'Looking up: '.$moviename."\n";
					else
						 echo "\n".'Looking up: '.$moviename."\n";

					// Check locally first.
					if ($year === true)
					{
						$start = (int) $parsed['year'] - 2;
						$end = (int) $parsed['year'] + 2;
						$ystr = '(';
						while ($start < $end)
						{
							$ystr .= $start.',';
							$start ++;
						}
						$ystr .= $end.')';
						$ckimdbid = $this->db->queryOneRow(sprintf('SELECT imdbid FROM movieinfo WHERE title %s %s AND %s IN %s', $like, "'%".$parsed['title']."%'", $inyear, $ystr));
					}
					else
						$ckimdbid = $this->db->queryOneRow(sprintf('SELECT imdbid FROM movieinfo WHERE title %s %s', $like, "'%".$parsed['title']."%'"));

					if (isset($ckimdbid['imdbid']))
					{
						$imdbId = $this->domovieupdate('tt'.$ckimdbid['imdbid'], 'Local DB', $arr['id']);
						if ($imdbId === false)
							$this->db->queryExec(sprintf("UPDATE releases SET imdbid = 0000000 WHERE id = %d", $arr["id"]));

						continue;
					}

					// Check on trakt.
					$traktimdbid = $trakt->traktMoviesummary($moviename, 'imdbid');
					if ($traktimdbid !== false)
					{
						$imdbId = $this->domovieupdate($traktimdbid, 'Trakt', $arr['id']);
						if ($imdbId === false)
						{
							// No imdb id found, set to all zeros so we dont process again.
							$this->db->queryExec(sprintf("UPDATE releases SET imdbid = 0000000 WHERE id = %d", $arr["id"]));
						}
						else
							continue;
					}
					// Check on search engines.
					else if ($googleban == false && $googlelimit <= 40)
					{
						$moviename1 = str_replace(' ', '+', $moviename);
						$buffer = getUrl("https://www.google.com/search?hl=en&as_q=".urlencode($moviename1)."&as_epq=&as_oq=&as_eq=&as_nlo=&as_nhi=&lr=&cr=&as_qdr=all&as_sitesearch=imdb.com&as_occt=any&safe=images&tbs=&as_filetype=&as_rights=");

						// Make sure we got some data.
						if ($buffer !== false && strlen($buffer))
						{
							$googlelimit++;
							if (!preg_match('/To continue, please type the characters below/i', $buffer))
							{
								$imdbId = $this->domovieupdate($buffer, 'Google1', $arr['id']);
								if ($imdbId === false)
								{
									if (preg_match('/(?P<name>[\w+].+)(\+\(\d{4}\))/i', $moviename1, $result))
									{
										$buffer = getUrl("https://www.google.com/search?hl=en&as_q=".urlencode($result["name"])."&as_epq=&as_oq=&as_eq=&as_nlo=&as_nhi=&lr=&cr=&as_qdr=all&as_sitesearch=imdb.com&as_occt=any&safe=images&tbs=&as_filetype=&as_rights=");

										if ($buffer !== false && strlen($buffer))
										{
											$googlelimit++;
											$imdbId = $this->domovieupdate($buffer, 'Google2', $arr["id"]);
											if ($imdbId === false)
											{
												//no imdb id found, set to all zeros so we dont process again
												$this->db->queryExec(sprintf("UPDATE releases SET imdbid = 0000000 WHERE id = %d", $arr["id"]));
											}
											else
												continue;
										}
										else
										{
											$googleban = true;
											if ($this->bingSearch($moviename, $arr["id"]) === true)
												continue;
											else if ($this->yahooSearch($moviename, $arr["id"]) === true)
												continue;
										}
									}
									else
									{
										$googleban = true;
										if ($this->bingSearch($moviename, $arr["id"]) === true)
											continue;
										else if ($this->yahooSearch($moviename, $arr["id"]) === true)
											continue;
									}
								}
								else
									continue;
							}
							else
							{
								$googleban = true;
								if ($this->bingSearch($moviename, $arr["id"]) === true)
									continue;
								else if ($this->yahooSearch($moviename, $arr["id"]) === true)
									continue;
							}
						}
						else
						{
							if ($this->bingSearch($moviename, $arr["id"]) === true)
								continue;
							else if ($this->yahooSearch($moviename, $arr["id"]) === true)
								continue;
						}
					}
					else if ($this->bingSearch($moviename, $arr["id"]) === true)
						continue;
					else if ($this->yahooSearch($moviename, $arr["id"]) === true)
						continue;
					else if (!isset($ckimdbid['imdbid']) && $year === true)
					{
						$ckimdbid = $this->db->queryOneRow(sprintf('SELECT imdbid FROM movieinfo WHERE title %s %s', $like, "'%".$parsed['title']."%'"));
						if (isset($ckimdbid['imdbid']))
						{
							$imdbId = $this->domovieupdate('tt'.$ckimdbid['imdbid'], 'Local DB', $arr['id']);
							if ($imdbId === false)
								$this->db->queryExec(sprintf("UPDATE releases SET imdbid = 0000000 WHERE id = %d", $arr["id"]));

							continue;
						}
					}
					else
					{
						echo "Exceeded request limits on google.com bing.com and yahoo.com.\n";
						break;
					}
				}
				else
				{
					$this->db->queryExec(sprintf("UPDATE releases SET imdbid = 0000000 WHERE id = %d", $arr["id"]));
					continue;
				}
			}
		}
	}

	public function bingSearch($moviename, $relID)
	{
		if ($this->binglimit <= 40)
		{
			$moviename = str_replace(' ', '+', $moviename);
			if (preg_match('/(?P<name>[\w+].+)(\+(?P<year>\(\d{4}\)))?/i', $moviename, $result))
			{
				if (isset($result["year"]) && !empty($result["year"]))
				{
					$buffer = getUrl("http://www.bing.com/search?q=".$result["name"].urlencode($result["year"])."+".urlencode("site:imdb.com")."&qs=n&form=QBRE&pq=".$result["name"].urlencode($result["year"])."+".urlencode("site:imdb.com")."&sc=4-38&sp=-1&sk=");
					if ($buffer !== false && strlen($buffer))
					{
						$this->binglimit++;
						$imdbId = $this->domovieupdate($buffer, 'Bing1', $relID);
						if ($imdbId === false)
						{
							$buffer = getUrl("http://www.bing.com/search?q=".$result["name"]."+".urlencode("site:imdb.com")."&qs=n&form=QBRE&pq=".$result["name"]."+".urlencode("site:imdb.com")."&sc=4-38&sp=-1&sk=");
							if ($buffer !== false && strlen($buffer))
							{
								$this->binglimit++;
								$imdbId = $this->domovieupdate($buffer, 'Bing2', $relID);
								if ($imdbId === false)
								{
									$this->db->queryExec(sprintf("UPDATE releases SET imdbid = 0000000 WHERE id = %d", $relID));
									return true;
								}
								else
									return true;
							}
							else
								return false;
						}
						else
							return true;
					}
					else
						return false;
				}
				else
				{
					$buffer = getUrl("http://www.bing.com/search?q=".$result["name"]."+".urlencode("site:imdb.com")."&qs=n&form=QBRE&pq=".$result["name"]."+".urlencode("site:imdb.com")."&sc=4-38&sp=-1&sk=");
					if ($buffer !== false && strlen($buffer))
					{
						$this->binglimit++;
						$imdbId = $this->domovieupdate($buffer, 'Bing2', $relID);
						if ($imdbId === false)
						{
							$this->db->queryExec(sprintf("UPDATE releases SET imdbid = 0000000 WHERE id = %d", $relID));
							return true;
						}
						else
							return true;
					}
					else
						return false;
				}
			}
			else
			{
				$this->db->queryExec(sprintf("UPDATE releases SET imdbid = 0000000 WHERE id = %d", $relID));
				return true;
			}
		}
		else
			return false;
	}

	public function yahooSearch($moviename, $relID)
	{
		if ($this->yahoolimit <= 40)
		{
			$moviename = str_replace(' ', '+', $moviename);
			if(preg_match('/(?P<name>[\w+].+)(\+(?P<year>\(\d{4}\)))?/i', $moviename, $result))
			{
				if (isset($result["year"]) && !empty($result["year"]))
				{
					$buffer = getUrl("http://search.yahoo.com/search?n=15&ei=UTF-8&va_vt=any&vo_vt=any&ve_vt=any&vp_vt=any&vf=all&vm=p&fl=0&fr=yfp-t-900&p=".$result["name"]."+".urlencode($result["year"])."&vs=imdb.com");
					if ($buffer !== false && strlen($buffer))
					{
						$this->yahoolimit++;
						$imdbId = $this->domovieupdate($buffer, 'Yahoo1', $relID);
						if ($imdbId === false)
						{
							$buffer = getUrl("http://search.yahoo.com/search?n=15&ei=UTF-8&va_vt=any&vo_vt=any&ve_vt=any&vp_vt=any&vf=all&vm=p&fl=0&fr=yfp-t-900&p=".$result["name"]."&vs=imdb.com");
							if ($buffer !== false && strlen($buffer))
							{
								$this->yahoolimit++;
								$imdbId = $this->domovieupdate($buffer, 'Yahoo2', $relID);
								if ($imdbId === false)
								{
									$this->db->queryExec(sprintf("UPDATE releases SET imdbid = 0000000 WHERE id = %d", $relID));
									return true;
								}
								else
								{
									return true;
								}
							}
							else
								return false;
						}
						else
							return true;
					}
					return false;
				}
				else
				{
					$buffer = getUrl("http://search.yahoo.com/search?n=15&ei=UTF-8&va_vt=any&vo_vt=any&ve_vt=any&vp_vt=any&vf=all&vm=p&fl=0&fr=yfp-t-900&p=".$result["name"]."&vs=imdb.com");
					if ($buffer !== false && strlen($buffer))
					{
						$this->yahoolimit++;
						$imdbId = $this->domovieupdate($buffer, 'Yahoo2', $relID);
						if ($imdbId === false)
						{
							$this->db->queryExec(sprintf("UPDATE releases SET imdbid = 0000000 WHERE id = %d", $relID));
							return true;
						}
						else
							return true;
					}
					else
						return false;
				}
			}
			else
			{
				$this->db->queryExec(sprintf("UPDATE releases SET imdbid = 0000000 WHERE id = %d", $relID));
				return true;
			}
		}
		else
		{
			return false;
		}
	}

  	public function parseMovieSearchName($releasename)
	{
		if (preg_match('/\b[Ss]\d+[-._Ee]|\bE\d+\b/', $releasename))
			return false;
		$cat = new Category();
		if (!$cat->isMovieForeign($releasename))
		{
			preg_match('/(?P<name>[\w. -]+)[-._( ](?P<year>(19|20)\d\d)/i', $releasename, $matches);
			if (!isset($matches['year']))
				preg_match('/^(?P<name>[\w. -]+[-._ ]((bd|br|dvd)rip|bluray|hdtv|divx|xvid|proper|repack|real\.proper|sub\.?(fix|pack)|ac3d|unrated|1080[ip]|720p))/i', $releasename, $matches);

			if (isset($matches['name']))
			{
				$name = preg_replace('/\(.*?\)|[._]/i', ' ', $matches['name']);
				$year = (isset($matches['year'])) ? $matches['year'] : '';
				if (strlen($name) > 4 && !preg_match('/^\d+$/', $name))
				{
					if ($this->debug && $this->echooutput)
						echo "DB name: {$releasename}\n";
					return array('title' => trim($name), 'year' => $year);
				}
			}
		}
		return false;
	}

  public function getUpcoming($type, $source="rottentomato")
  {
  	$sql = sprintf("SELECT * FROM upcoming WHERE source = %s AND typeid = %d", $this->db->escapeString($source), $type);
  	return $this->db->queryOneRow($sql);
  }

  public function updateUpcoming()
  {
		$s = new Sites();
		$site = $s->get();
		if ($this->echooutput)
			echo "Updating movie schedule using rotten tomatoes.\n";
		if (isset($site->rottentomatokey))
		{
			$rt = new RottenTomato($site->rottentomatokey);

			$ret = $rt->getBoxOffice();
			if ($ret != "")
			{
				$cnt = $this->updateInsUpcoming('rottentomato', Movie::SRC_BOXOFFICE, $ret);
				if ($this->echooutput && $cnt > 0)
					echo "Added/updated movies to the box office list.\n";
			}
			else
			{
				if ($this->echooutput)
					echo "No new updates for box office list.\n";
			}

			$ret = $rt->getInTheaters();
			if ($ret != "")
			{
				$cnt = $this->updateInsUpcoming('rottentomato', Movie::SRC_INTHEATRE, $ret);
				if ($this->echooutput && $cnt > 0)
					echo "Added/updated movies to the theaters list.\n";
			}
			else
			{
				if ($this->echooutput)
					echo "No new updates for theaters list.\n";
			}

			$ret = $rt->getOpening();
			if ($ret != "")
			{
				$cnt = $this->updateInsUpcoming('rottentomato', Movie::SRC_OPENING, $ret);
				if ($this->echooutput && $cnt > 0)
					echo "Added/updated movies to the opening list.\n";
			}
			else
			{
				if ($this->echooutput)
					echo "No new updates for opening list.\n";
			}

			$ret = $rt->getUpcoming();
			if ($ret != "")
			{
				$cnt = $this->updateInsUpcoming('rottentomato', Movie::SRC_UPCOMING, $ret);
				if ($this->echooutput && $cnt > 0)
					echo "Added/updated movies to the upcoming list.\n";
			}
			else
			{
				if ($this->echooutput)
					echo "No new updates for upcoming list.\n";
			}

			$ret = $rt->getDVDReleases();
			if ($ret != "")
			{
				$cnt = $this->updateInsUpcoming('rottentomato', Movie::SRC_DVD, $ret);
				if ($this->echooutput && $cnt > 0)
					echo "Added/updated movies to the DVD list.\n";
			}
			else
			{
				if ($this->echooutput)
					echo "No new updates for upcoming list.\n";
			}

			if ($this->echooutput)
				echo "Updated successfully.\n";
	  }
  }

	public function updateInsUpcoming($source, $type, $info)
	{
		if ($this->db->dbSystem() == 'mysql')
			return $this->db->Exec(sprintf("INSERT INTO upcoming (source, typeid, info, updateddate) VALUES (%s, %d, %s, NOW()) ON DUPLICATE KEY UPDATE info = %s", $this->db->escapeString($source), $type, $this->db->escapeString($info), $this->db->escapeString($info)));
		else
		{
			$ckid = $this->db->queryOneRow(sprintf('SELECT id FROM upcoming WHERE source = %s AND typeid = %d AND info = %s', $this->db->escapeString($source), $type, $this->db->escapeString($info)));
			if (!isset($ckid['id']))
				return $this->db->Exec(sprintf("INSERT INTO upcoming (source, typeid, info, updateddate) VALUES (%s, %d, %s, NOW())", $this->db->escapeString($source), $type, $this->db->escapeString($info)));
			else
				return $this->db->Exec(sprintf('UPDATE upcoming SET source = %s, typeid = %s, info = %s, updateddate = NOW() WHERE id = %d', $this->db->escapeString($source), $type, $this->db->escapeString($info), $ckid['id']));
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
