<?php
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/TMDb.php");
require_once(WWW_DIR."/lib/category.php");
require_once(WWW_DIR."/lib/nfo.php");
require_once(WWW_DIR."/lib/site.php");
require_once(WWW_DIR."/lib/util.php");
require_once(WWW_DIR."/lib/releaseimage.php");
require_once(WWW_DIR."/lib/rottentomato.php");
require_once(WWW_DIR."/lib/trakttv.php");

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
		$s = new Sites();
		$site = $s->get();
		$this->apikey = $site->tmdbkey;
		$this->movieqty = (!empty($site->maximdbprocessed)) ? $site->maximdbprocessed : 100;
		
		$this->imgSavePath = WWW_DIR.'covers/movies/';
	}
	
	public function getMovieInfo($imdbId)
	{
		$db = new DB();
		return $db->queryOneRow(sprintf("SELECT * FROM movieinfo where imdbID = %d", $imdbId));
	}
	
	public function getMovieInfoMultiImdb($imdbIds)
	{
		$db = new DB();
		$allids = implode(",", $imdbIds);
		$sql = sprintf("SELECT DISTINCT movieinfo.*, releases.imdbID AS relimdb FROM movieinfo LEFT OUTER JOIN releases ON releases.imdbID = movieinfo.imdbID WHERE movieinfo.imdbID IN (%s)", $allids);
		return $db->query($sql);
	}	
	
	public function getRange($start, $num)
	{		
		$db = new DB();
		
		if ($start === false)
			$limit = "";
		else
			$limit = " LIMIT ".$start.",".$num;
		
		return $db->query(" SELECT * FROM movieinfo ORDER BY createddate DESC".$limit);		
	}
	
	public function getCount()
	{			
		$db = new DB();
		$res = $db->queryOneRow("select count(ID) as num from movieinfo");		
		return $res["num"];
	}
	
	public function getMovieCount($cat, $maxage=-1, $excludedcats=array())
	{
		$db = new DB();
		
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
							$chlist.=", ".$child["ID"];

						if ($chlist != "-99")
								$catsrch .= " r.categoryID in (".$chlist.") or ";
					}
					else
					{
						$catsrch .= sprintf(" r.categoryID = %d or ", $category);
					}
				}
			}
			$catsrch.= "1=2 )";
		}			

		if ($maxage > 0)
			$maxage = sprintf(" and r.postdate > now() - interval %d day ", $maxage);
		else
			$maxage = "";		
		
		$exccatlist = "";
		if (count($excludedcats) > 0)
			$exccatlist = " and r.categoryID not in (".implode(",", $excludedcats).")";
		
		$sql = sprintf("select count(distinct r.imdbID) as num from releases r inner join movieinfo m on m.imdbID = r.imdbID and m.title != '' where r.passwordstatus <= (select value from site where setting='showpasswordedrelease') and %s %s %s %s ", $browseby, $catsrch, $maxage, $exccatlist);
		$res = $db->queryOneRow($sql);		
		return $res["num"];	
	}	
	
	public function getMovieRange($cat, $start, $num, $orderby, $maxage=-1, $excludedcats=array())
	{	
		$db = new DB();
		
		$browseby = $this->getBrowseBy();
		
		if ($start === false)
			$limit = "";
		else
			$limit = " LIMIT ".$start.",".$num;
		
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
							$chlist.=", ".$child["ID"];

						if ($chlist != "-99")
								$catsrch .= " r.categoryID in (".$chlist.") or ";
					}
					else
					{
						$catsrch .= sprintf(" r.categoryID = %d or ", $category);
					}
				}
			}
			$catsrch.= "1=2 )";
		}	
		
		$maxage = "";
		if ($maxage > 0)
			$maxage = sprintf(" and r.postdate > now() - interval %d day ", $maxage);

		$exccatlist = "";
		if (count($excludedcats) > 0)
			$exccatlist = " and r.categoryID not in (".implode(",", $excludedcats).")";
			
		$order = $this->getMovieOrder($orderby);
		$sql = sprintf(" SELECT GROUP_CONCAT(r.ID ORDER BY r.postdate desc SEPARATOR ',') as grp_release_id, GROUP_CONCAT(r.rarinnerfilecount ORDER BY r.postdate desc SEPARATOR ',') as grp_rarinnerfilecount, GROUP_CONCAT(r.haspreview ORDER BY r.postdate desc SEPARATOR ',') as grp_haspreview, GROUP_CONCAT(r.passwordstatus ORDER BY r.postdate desc SEPARATOR ',') as grp_release_password, GROUP_CONCAT(r.guid ORDER BY r.postdate desc SEPARATOR ',') as grp_release_guid, GROUP_CONCAT(rn.ID ORDER BY r.postdate desc SEPARATOR ',') as grp_release_nfoID, GROUP_CONCAT(groups.name ORDER BY r.postdate desc SEPARATOR ',') as grp_release_grpname, GROUP_CONCAT(r.searchname ORDER BY r.postdate desc SEPARATOR '#') as grp_release_name, GROUP_CONCAT(r.postdate ORDER BY r.postdate desc SEPARATOR ',') as grp_release_postdate, GROUP_CONCAT(r.size ORDER BY r.postdate desc SEPARATOR ',') as grp_release_size, GROUP_CONCAT(r.totalpart ORDER BY r.postdate desc SEPARATOR ',') as grp_release_totalparts, GROUP_CONCAT(r.comments ORDER BY r.postdate desc SEPARATOR ',') as grp_release_comments, GROUP_CONCAT(r.grabs ORDER BY r.postdate desc SEPARATOR ',') as grp_release_grabs, m.*, groups.name as group_name, rn.ID as nfoID from releases r left outer join groups on groups.ID = r.groupID inner join movieinfo m on m.imdbID = r.imdbID and m.title != '' left outer join releasenfo rn on rn.releaseID = r.ID and rn.nfo is not null where r.passwordstatus <= (select value from site where setting='showpasswordedrelease') and %s %s %s %s group by m.imdbID order by %s %s".$limit, $browseby, $catsrch, $maxage, $exccatlist, $order[0], $order[1]);
		return $db->query($sql);		
	}
	
	public function getMovieOrder($orderby)
	{
		$order = ($orderby == '') ? 'max(r.postdate)' : $orderby;
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
		$db = new Db;
		
		$browseby = ' ';
		$browsebyArr = $this->getBrowseByOptions();
		foreach ($browsebyArr as $bb) {
			if (isset($_REQUEST[$bb]) && !empty($_REQUEST[$bb])) {
				$bbv = stripslashes($_REQUEST[$bb]);
				if ($bb == 'rating') { $bbv .= '.'; }
				if ($bb == 'imdb') {
					$browseby .= "m.{$bb}ID = $bbv AND ";
				} else {
					$browseby .= "m.$bb LIKE(".$db->escapeString('%'.$bbv.'%').") AND ";
				}
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
		$db = new DB();
		
		$db->query(sprintf("UPDATE movieinfo SET title=%s, tagline=%s, plot=%s, year=%s, rating=%s, genre=%s, director=%s, actors=%s, language=%s, cover=%d, backdrop=%d, updateddate=NOW() WHERE imdbID = %d", 
			$db->escapeString($title), $db->escapeString($tagline), $db->escapeString($plot), $db->escapeString($year), $db->escapeString($rating), $db->escapeString($genre), $db->escapeString($director), $db->escapeString($actors), $db->escapeString($language), $cover, $backdrop, $id));		
	}
	
	public function updateMovieInfo($imdbId)
	{
		$ri = new ReleaseImage();
		
		if ($this->echooutput)
			echo "Fetching IMDB info from TMDB using IMDB ID: ".$imdbId."\n";
		
		//check themoviedb for imdb info
		$tmdb = $this->fetchTmdbProperties($imdbId);
		if (!$tmdb) 
		{
			if ($this->echooutput)
				echo "Release not found in TMDB.\n";
		}
		
		//check imdb for movie info
		$imdb = $this->fetchImdbProperties($imdbId);
		if (!$imdb) 
		{
			if ($this->echooutput)
				echo "Unable to get movie information from IMDB ID: ".$imdbId."\n";
		}
										
		if (!$imdb && !$tmdb) {
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

		$db = new DB();
		$query = sprintf("
			INSERT INTO movieinfo 
				(imdbID, tmdbID, title, rating, tagline, plot, year, genre, director, actors, language, cover, backdrop, createddate, updateddate)
			VALUES 
				(%d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, %d, NOW(), NOW())
			ON DUPLICATE KEY UPDATE
				imdbID=%d, tmdbID=%s, title=%s, rating=%s, tagline=%s, plot=%s, year=%s, genre=%s, director=%s, actors=%s, language=%s, cover=%d, backdrop=%d, updateddate=NOW()", 
		$mov['imdb_id'], $mov['tmdb_id'], $db->escapeString($mov['title']), $db->escapeString($mov['rating']), $db->escapeString($mov['tagline']), $db->escapeString($mov['plot']), $db->escapeString($mov['year']), $db->escapeString($mov['genre']), $db->escapeString($mov['director']), $db->escapeString($mov['actors']), $db->escapeString($mov['language']), $mov['cover'], $mov['backdrop'],
		$mov['imdb_id'], $mov['tmdb_id'], $db->escapeString($mov['title']), $db->escapeString($mov['rating']), $db->escapeString($mov['tagline']), $db->escapeString($mov['plot']), $db->escapeString($mov['year']), $db->escapeString($mov['genre']), $db->escapeString($mov['director']), $db->escapeString($mov['actors']), $db->escapeString($mov['language']), $mov['cover'], $mov['backdrop']);
		
		$movieId = $db->queryInsert($query);

		if ($movieId) {
			if ($this->echooutput)
				echo "Added/updated movie: ".$mov['title']." (".$mov['year'].") - ".$mov['imdb_id'].".\n";
		} else {
			if ($this->echooutput)
				echo "Nothing to update for movie: ".$mov['title']." (".$mov['year'].") - ".$mov['imdb_id']."\n";
		}
		
		return $movieId;
	}
	
	public function fetchTmdbProperties($imdbId, $text=false)
	{
		$tmdb = new TMDb($this->apikey, 'en'/*language, make it configurable in the future*/);
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
		if (isset($tmdbLookup['vote_average'])) {$ret['rating'] = ($tmdbLookup['vote_average'] == 0) ? '' : $tmdbLookup['vote_average'];}
		if (isset($tmdbLookup['tagline']))		{$ret['plot'] = $tmdbLookup['tagline'];}
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
		{
			$ret['cover'] = "http://d3gtl9l2a4fn1j.cloudfront.net/t/p/w185".$tmdbLookup['poster_path'];
		}
		if (isset($movie->backdrops) && sizeof($movie->backdrops) > 0) 
		{
			$ret['backdrop'] = "http://d3gtl9l2a4fn1j.cloudfront.net/t/p/original".$tmdbLookup['backdrop_path'];
		}
		return $ret;
	}
	
	public function fetchImdbProperties($imdbId)
	{
		$imdb_regex = array(
			'title'	=> '/<title>(.*?)\s?\(.*?<\/title>/i',
			'tagline'  => '/taglines:<\/h4>\s([^<]+)/i',
			'plot'	 => '/<p>\s<p>(.*?)\s<\/p>\s<\/p>/i',
			'rating'   => '/"ratingValue">([\d.]+)<\/span>/i',
			'year'	 => '/<title>.*?\(.*?(\d{4}).*?<\/title>/i',
			'cover'	=> '/<a.*?href="\/media\/.*?><img src="(.*?)"/i'
		);
		
		$imdb_regex_multi = array(
			'genre'	=> '/href="\/genre\/(.*?)\?/i',
			'language' => '/<a href="\/language\/.+\'url\'>(.+)<\/a>/i'
		);

		$buffer = getUrl("http://www.imdb.com/title/tt$imdbId/");

		// make sure we got some data
		if ($buffer !== false && strlen($buffer))
		{
			$ret = array();
			foreach ($imdb_regex as $field => $regex)
			{
				if (preg_match($regex, $buffer, $matches))
				{
					$match = $matches[1];
					$match = strip_tags(trim(rtrim(addslashes($match))));
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
	
	public function processMovieReleases($threads=0)
	{
		$threads--;
		$db = new DB();
		// Using name.
		$this->doprocessMovieReleases($db->queryDirect(sprintf("SELECT name, ID from releases where imdbID IS NULL and nzbstatus >= 0 and categoryID in ( select ID from category where parentID = %d ) limit %d,%d", Category::CAT_PARENT_MOVIE, floor(($this->movieqty) * ($threads * 1.5)), $this->movieqty)), "name");
		// Using searchname.
		$this->doprocessMovieReleases($db->queryDirect(sprintf("SELECT searchname as name, ID from releases where imdbID IS NULL and nzbstatus >= 0 and relnamestatus = 2 and categoryID in ( select ID from category where parentID = %d ) limit %d", Category::CAT_PARENT_MOVIE, floor(($this->movieqty) * ($threads * 1.5)), $this->movieqty)), "searchname");
	}
	
	public function doprocessMovieReleases($res, $type)
	{
		$ret = 0;
		$db = new DB();
		$nfo = new Nfo;
		$trakt = new Trakttv();
		$googleban = false;
		$googlelimit = 0;
		$binglimit = 0;
		$yahoolimit = 0;
		
		if ($db->getNumRows($res) > 0)
		{	
			if ($this->echooutput)
			{
				if($type == "name")
					echo "Processing ".$db->getNumRows($res)." movie release(s) using usenet subject.\n";
				if($type == "searchname")
					echo "Processing ".$db->getNumRows($res)." movie release(s) using fixed named from fixReleaseNames.php.\n";
			}
		
			while ($arr = $db->fetchAssoc($res)) 
			{
				if($type == "name")		
					$moviename = $this->parseMovieName($arr['name']);
				if($type == "searchname")
					$moviename = $this->parseMovieSearchName($arr['name']);
				if ($moviename !== false)
				{
					if ($this->echooutput)
						echo 'Looking up: '.$moviename."\n";
						
					$traktimdbid = $trakt->traktMoviesummary($moviename, "imdbid");
					if ($traktimdbid !== false)
					{
						$traktimdbid = str_replace('tt', '', $traktimdbid);
						if ($this->echooutput)
							echo 'Tratkt Found IMDBid: tt'.$traktimdbid."\n";
						
						$db->query(sprintf("UPDATE releases SET imdbID = %s WHERE ID = %d", $db->escapeString($traktimdbid), $arr["ID"]));
						
						$movCheck = $this->getMovieInfo($traktimdbid);
						if ($movCheck === false || (isset($movCheck['updateddate']) && (time() - strtotime($movCheck['updateddate'])) > 2592000))
						{
							$movieId = $this->updateMovieInfo($traktimdbid);
						}
					}
					else if ($googleban == false && $googlelimit <= 40)
					{
						$moviename1 = str_replace(' ', '+', $moviename);
						$buffer = getUrl("https://www.google.com/search?hl=en&as_q=".urlencode($moviename1)."&as_epq=&as_oq=&as_eq=&as_nlo=&as_nhi=&lr=&cr=&as_qdr=all&as_sitesearch=imdb.com&as_occt=any&safe=images&tbs=&as_filetype=&as_rights=");
						
						// make sure we got some data
						if ($buffer !== false && strlen($buffer))
						{
							$googlelimit++;
							if (!preg_match('/To continue, please type the characters below/i', $buffer))
							{
								$imdbId = $nfo->parseImdb($buffer);
								if ($imdbId !== false) 
								{
									if ($this->echooutput)
										echo 'Google1 found IMDBid: tt'.$imdbId."\n";
							
									//update release with imdb id
									$db->query(sprintf("UPDATE releases SET imdbID = %s WHERE ID = %d", $db->escapeString($imdbId), $arr["ID"]));
							
									//check for existing movie entry
									$movCheck = $this->getMovieInfo($imdbId);
									if ($movCheck === false || (isset($movCheck['updateddate']) && (time() - strtotime($movCheck['updateddate'])) > 2592000))
									{
										$movieId = $this->updateMovieInfo($imdbId);
									}
								}
								else 
								{
									preg_match('/(?P<name>[\w+].+)(\+\(\d{4}\))/i', $moviename1, $result);
									$buffer = getUrl("https://www.google.com/search?hl=en&as_q=".urlencode($result["name"])."&as_epq=&as_oq=&as_eq=&as_nlo=&as_nhi=&lr=&cr=&as_qdr=all&as_sitesearch=imdb.com&as_occt=any&safe=images&tbs=&as_filetype=&as_rights=");
									
									if ($buffer !== false && strlen($buffer))
									{
										$googlelimit++;
										$imdbId = $nfo->parseImdb($buffer);
										if ($imdbId !== false) 
										{
											if ($this->echooutput)
												echo 'Google2 found IMDBid: tt'.$imdbId."\n";
											
											$db->query(sprintf("UPDATE releases SET imdbID = %s WHERE ID = %d", $db->escapeString($imdbId), $arr["ID"]));
											
											$movCheck = $this->getMovieInfo($imdbId);
											if ($movCheck === false || (isset($movCheck['updateddate']) && (time() - strtotime($movCheck['updateddate'])) > 2592000))
											{
												$movieId = $this->updateMovieInfo($imdbId);
											}
										}
										else
										{
											//no imdb id found, set to all zeros so we dont process again
											$db->query(sprintf("UPDATE releases SET imdbID = %d WHERE ID = %d", 0, $arr["ID"]));
										}
									}
								}
							}
							else
							{
								echo "Exceeded google.com request limit.. Using bing.com until next run.\n";
								$googleban = true;
							}
						}
					}
					else if ($binglimit <= 40)
					{
						$moviename = str_replace(' ', '+', $moviename);
						preg_match('/(?P<name>[\w+].+)(\+(?P<year>\(\d{4}\)))/i', $moviename, $result);
						$buffer = getUrl("http://www.bing.com/search?q=".$result["name"].urlencode($result["year"])."+".urlencode("site:imdb.com")."&qs=n&form=QBRE&pq=".$result["name"].urlencode($result["year"])."+".urlencode("site:imdb.com")."&sc=4-38&sp=-1&sk=");
						
						if ($buffer !== false && strlen($buffer))
						{
							$binglimit++;
							$imdbId = $nfo->parseImdb($buffer);
							if ($imdbId !== false) 
							{
								if ($this->echooutput)
									echo 'Bing1 found IMDBid: tt'.$imdbId."\n";
									
								$db->query(sprintf("UPDATE releases SET imdbID = %s WHERE ID = %d", $db->escapeString($imdbId), $arr["ID"]));
									
								$movCheck = $this->getMovieInfo($imdbId);
								if ($movCheck === false || (isset($movCheck['updateddate']) && (time() - strtotime($movCheck['updateddate'])) > 2592000))
								{
									$movieId = $this->updateMovieInfo($imdbId);
								}
							}
							else 
							{
								$buffer = getUrl("http://www.bing.com/search?q=".$result["name"]."+".urlencode("site:imdb.com")."&qs=n&form=QBRE&pq=".$result["name"]."+".urlencode("site:imdb.com")."&sc=4-38&sp=-1&sk=");
								if ($buffer !== false && strlen($buffer))
								{
									$binglimit++;
									$imdbId = $nfo->parseImdb($buffer);
									if ($imdbId !== false) 
									{
										if ($this->echooutput)
											echo 'Bing2 found IMDBid: tt'.$imdbId."\n";
										
										$db->query(sprintf("UPDATE releases SET imdbID = %s WHERE ID = %d", $db->escapeString($imdbId), $arr["ID"]));
										
										$movCheck = $this->getMovieInfo($imdbId);
										if ($movCheck === false || (isset($movCheck['updateddate']) && (time() - strtotime($movCheck['updateddate'])) > 2592000))
										{
											$movieId = $this->updateMovieInfo($imdbId);
										}
									}
									else
									{
										//no imdb id found, set to all zeros so we dont process again
										$db->query(sprintf("UPDATE releases SET imdbID = %d WHERE ID = %d", 0, $arr["ID"]));
									}
								}
							}
						}
					}
					else if ($yahoolimit <= 40)
					{
						$moviename = str_replace(' ', '+', $moviename);
						preg_match('/(?P<name>[\w+].+)(\+(?P<year>\(\d{4}\)))/i', $moviename, $result);
						$buffer = getUrl("http://search.yahoo.com/search?n=15&ei=UTF-8&va_vt=any&vo_vt=any&ve_vt=any&vp_vt=any&vf=all&vm=p&fl=0&fr=yfp-t-900&p=".$result["name"]."+".urlencode($result["year"])."&vs=imdb.com");
						
						if ($buffer !== false && strlen($buffer))
						{
							$yahoolimit++;
							$imdbId = $nfo->parseImdb($buffer);
							if ($imdbId !== false) 
							{
								if ($this->echooutput)
									echo 'Yahoo1 found IMDBid: tt'.$imdbId."\n";
									
								$db->query(sprintf("UPDATE releases SET imdbID = %s WHERE ID = %d", $db->escapeString($imdbId), $arr["ID"]));
									
								$movCheck = $this->getMovieInfo($imdbId);
								if ($movCheck === false || (isset($movCheck['updateddate']) && (time() - strtotime($movCheck['updateddate'])) > 2592000))
								{
									$movieId = $this->updateMovieInfo($imdbId);
								}
							}
							else 
							{
								$buffer = getUrl("http://search.yahoo.com/search?n=15&ei=UTF-8&va_vt=any&vo_vt=any&ve_vt=any&vp_vt=any&vf=all&vm=p&fl=0&fr=yfp-t-900&p=".$result["name"]."&vs=imdb.com");
								if ($buffer !== false && strlen($buffer))
								{
									$yahoolimit++;
									$imdbId = $nfo->parseImdb($buffer);
									if ($imdbId !== false) 
									{
										if ($this->echooutput)
											echo 'Yahoo2 found IMDBid: tt'.$imdbId."\n";
										
										$db->query(sprintf("UPDATE releases SET imdbID = %s WHERE ID = %d", $db->escapeString($imdbId), $arr["ID"]));
										
										$movCheck = $this->getMovieInfo($imdbId);
										if ($movCheck === false || (isset($movCheck['updateddate']) && (time() - strtotime($movCheck['updateddate'])) > 2592000))
										{
											$movieId = $this->updateMovieInfo($imdbId);
										}
									}
									else
									{
										//no imdb id found, set to all zeros so we dont process again
										$db->query(sprintf("UPDATE releases SET imdbID = %d WHERE ID = %d", 0, $arr["ID"]));
									}
								}
							}
						}
					}
					else
					{
						echo "Exceeded request limits on google.com bing.com and yahoo.com.\n";
					}
				}
				else
				{
					//no valid movie name found, set to all zeros so we dont process again
					$db->query(sprintf("UPDATE releases SET imdbID = %d WHERE ID = %d", 0, $arr["ID"]));
				}				
			}
		}
	}
	
	public function parseMovieName($releasename)
	{
				$cat = new Category;
		if (!$cat->isMovieForeign($releasename)) 
		{
			if (preg_match('/[\s\[](?:("|\s|\d{1,4}\/\d{1.4}}))(?P<name>.*?)(?:(dvd.+|xvid))?[\.\-_\( ](?P<year>(19|20)\d\d)/i', $releasename, $matches))
			{
				if (!isset($matches['year'])) 
				{
					preg_match('/[\s\[](?P<name>.*)[\.\-_ ](?:dvdrip|bdrip|brrip|bluray|hdtv|divx|xvid|proper|repack|real\.proper|sub\.?fix|sub\.?pack|ac3d|unrated|1080i|1080p|720p)/i', $releasename, $matches);
				}
			
				if (isset($matches['name'])) 
				{
					$name = preg_replace('/\(.*?\)|\.|_/i', ' ', $matches['name']);
					$name = str_replace('-', ' ', $name);
					$name = str_replace(array(':', '!', '<', '>', '*', '(', ')'), '', $name);
					$name = preg_replace('/\s{1,}/', ' ', $name);
					$year = (isset($matches['year'])) ? ' ('.$matches['year'].')' : '';
					return trim($name).$year;
				}
			}
			else if (preg_match('/"(?P<name>.*?)[\.\-_\( ](?P<year>(19|20)\d\d)/i', $releasename, $matches))
			{
				if (!isset($matches['year'])) 
				{
					preg_match('/"(?P<name>.*)[\.\-_ ](?:dvdrip|bdrip|brrip|bluray|hdtv|divx|xvid|proper|repack|real\.proper|sub\.?fix|sub\.?pack|ac3d|unrated|1080i|1080p|720p)/i', $releasename, $matches);
				}
			
				if (isset($matches['name'])) 
				{
					$name = preg_replace('/\(.*?\)|\.|_/i', ' ', $matches['name']);
					$name = str_replace('-', ' ', $name);
					$name = str_replace(array(':', '!', '<', '>', '*', '(', ')'), '', $name);
					$name = preg_replace('/\s{1,}/', ' ', $name);
					$year = (isset($matches['year'])) ? ' ('.$matches['year'].')' : '';
					return trim($name).$year;
				}
			}
			else if (preg_match('/^(?P<name>.*)[\.\-_\( ](?P<year>(19|20)\d\d)/i', $releasename, $matches))
			{
				if (!isset($matches['year'])) 
				{
					preg_match('/^(?P<name>.*)[\.\-_ ](?:dvdrip|bdrip|brrip|bluray|hdtv|divx|xvid|proper|repack|real\.proper|sub\.?fix|sub\.?pack|ac3d|unrated|1080i|1080p|720p)/i', $releasename, $matches);
				}
			
				if (isset($matches['name'])) 
				{
					$name = preg_replace('/\(.*?\)|\.|_/i', ' ', $matches['name']);
					$name = str_replace('-', ' ', $name);
					$name = str_replace(array(':', '!', '<', '>', '*', '(', ')'), '', $name);
					$name = preg_replace('/\s{1,}/', ' ', $name);
					$year = (isset($matches['year'])) ? ' ('.$matches['year'].')' : '';
					return trim($name).$year;
				}
			}
		}
		return false;
	}
  
  	public function parseMovieSearchName($releasename)
	{
		$cat = new Category;
		if (!$cat->isMovieForeign($releasename)) {
			preg_match('/^(?P<name>.*)[\.\-_\( ](?P<year>19\d{2}|20\d{2})/i', $releasename, $matches);
			if (!isset($matches['year'])) {
				preg_match('/^(?P<name>.*)[\.\-_ ](?:dvdrip|bdrip|brrip|bluray|hdtv|divx|xvid|proper|repack|real\.proper|sub\.?fix|sub\.?pack|ac3d|unrated|1080i|1080p|720p)/i', $releasename, $matches);
			}
			
			if (isset($matches['name'])) {
				$name = preg_replace('/\(.*?\)|\.|_/i', ' ', $matches['name']);
				$year = (isset($matches['year'])) ? ' ('.$matches['year'].')' : '';
				return trim($name).$year;
			}
		}
		return false;
	}
  
  public function getUpcoming($type, $source="rottentomato")
  {
  	$db = new DB();
  	$sql = sprintf("select * from upcoming where source = %s and typeid = %d", $db->escapeString($source), $type);
  	return $db->queryOneRow($sql);
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
	  		$this->updateInsUpcoming('rottentomato', Movie::SRC_BOXOFFICE, $ret);

	  	$ret = $rt->getInTheaters();
	  	if ($ret != "")
	  		$this->updateInsUpcoming('rottentomato', Movie::SRC_INTHEATRE, $ret);

	  	$ret = $rt->getOpening();
	  	if ($ret != "")
	  		$this->updateInsUpcoming('rottentomato', Movie::SRC_OPENING, $ret);

	  	$ret = $rt->getUpcoming();
	  	if ($ret != "")
	  		$this->updateInsUpcoming('rottentomato', Movie::SRC_UPCOMING, $ret);

	  	$ret = $rt->getDVDReleases();
	  	if ($ret != "")
	  		$this->updateInsUpcoming('rottentomato', Movie::SRC_DVD, $ret);
	  	if ($this->echooutput)
			echo "Updated successfully.\n";
	  }
  }
	
	public function updateInsUpcoming($source, $type, $info)
	{
		$db = new DB();
		$sql = sprintf("INSERT into upcoming (source,typeID,info,updateddate) VALUES (%s, %d, %s, null)
				ON DUPLICATE KEY UPDATE info = %s", $db->escapeString($source), $type, $db->escapeString($info), $db->escapeString($info));
		$db->query($sql);
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
?>
