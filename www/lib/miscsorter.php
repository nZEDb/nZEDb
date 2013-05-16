<?php
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/category.php");
require_once(WWW_DIR."/lib/movie.php");
require_once(WWW_DIR."/lib/nfo.php");

class MiscSorter {

	// **********************************************************************
	function __construct($echooutput=false) {
		$qualities = array('(:?..)?tv', '480[ip]?', '640[ip]?', '720[ip]?', '1080[ip]?', 'ac3', 'audio_ts', 'avi', 'bd[\- ]?rip', 'bd25', 'bd50',
		'bdmv', 'blu ?ray', 'br[\- ]?disk', 'br[\- ]?rip', 'cam', 'cam[\- ]?rip', 'dc', 'directors.?cut', 'divx\d?', 'dts', 'dvd', 'dvd[\- ]?r',
		'dvd[\- ]?rip', 'dvd[\- ]?scr', 'extended', 'hd', 'hd[\- ]?tv', 'h264', 'hd[\- ]?cam', 'hd[\- ]?ts', 'iso', 'm2ts', 'mkv', 'mpeg(:?\-\d)?',
		'mpg', 'ntsc', 'pal', 'proper', 'ppv', 'ppv[\- ]?rip', 'r\d{1}', 'repack', 'repacked', 'scr', 'screener', 'tc', 'telecine', 'telesync', 'ts',
		'tv[\- ]?rip', 'unrated', 'video_ts', 'video ts', 'x264', 'xvid', 'web[\- ]?rip');

		$this->echooutput = $echooutput;
		$this->qty = 10000;
		$this->DEBUGGING = false;

		$this->db = new DB($this->echooutput);
		$this->cat = new Category($this->echooutput);
		$this->movie = new Movie($this->echooutput);
		$this->nfolib = new Nfo($this->echooutput);


		$res = $this->db->query("SET NAMES 'utf8'");
		$res = $this->db->query("SET CHARACTER SET 'utf8'");

		mb_internal_encoding("UTF-8");
		mb_regex_encoding("UTF-8");
		mb_http_output("UTF-8");
		mb_language("uni");
	}

	function getHash($name)
	{
		return false;
	}

	function doecho ($str = '', $type = '')
	{
		if ($this->echooutput && $str != '')
		{
			if ($this->DEBUGGING && $type == 'debug')
				echo "$str\n";
			elseif ($type != 'debug')
				echo "$str\n";
		}
	}

	function nfopos ($nfo, $str)
	{
		$pos = stripos($nfo, $str);
		if ($pos !== false)
			return $pos/strlen($nfo);
		else
			return false;
	}


	function getIDs ($cat)
	{
		$cats = $this->cat->getChildren($cat);
		$thecategory = array();
		foreach ($cats as $c)
			$thecategory[] = $c['ID'];

		$thecategory = implode(", ", $thecategory);

		$query = sprintf("SELECT ID FROM releases WHERE releases.categoryID IN ( %s ) limit %d", $thecategory, $this->qty);
		$res = $this->db->query($query);

		if (count($res) == 0)
			return false;

		$this->idarr = $res[0]['ID'];
		unset($res[0]);
		foreach($res as $r)
			$this->idarr = $this->idarr.", ".$r['ID'];

		if ($this->idarr == '')
			return false;

		return $this->idarr;
	}

	function doarray($matches)
	{
		$r = array();
		$i = 0;

		$matches = array_count_values($matches);
		$matches = array_change_key_case($matches, CASE_LOWER);

		foreach ($matches as $m=>$v)
		{
			$x = -1;

			if (strlen($m) < 50)
			{
				$str = preg_replace("/\s/iU", "", $m);

				$m = strtolower($str);

				$x = 0;

				if ($m == 'audiobook') {
					$x = -11;
				} else if ($m == 'anidb.net') {
					$x = -10;
				} else if ($m == 'upc') {
					$x = -9;
				} else if ($m == 'amazon.') {
					$x = -8;
				} else if ($m == 'asin' || $m == 'isbn' ) {
					$x = -7;
				}  else if ($m == 'tvrage') {
					$x = -6;
				} else if ($m == 'imdb') {
					$x = -5;
				}  else if ($m == 'os') {
					$x = -4;
				} else if ($m == 'mac' || $m == 'macintosh' || $m == 'dmg' || $m == 'macos' || $m == 'macosx' || $m == 'osx') {
					$x = -3;
				}  else if ($m == 'itunes.apple.com/') {
					$x = -2;
				}  else if ($m == 'documentaries' || $m == 'documentary' || $m == 'doku' ) {
					$x = -1;
				} else if (preg_match('/sport|deportes|nhl|nfl|\bnba/i', $m)) {
					$x = 1000;
				} else if (preg_match('/avi|xvid|divx|mkv/i', $m)) {
					$x = 1001;
				} else if (preg_match('/\.(?:rar|001)/i', $m)) {
					$x = 1002;
				} else if (preg_match('/pdf/i', $m)) {
					$x = 1003;
				}
			}

			if ($x == -1)
			{

			} elseif ($x == 0) {
				$r[$i++] = $m;
			} else if (isset($r[$x]))
			{
				$r[$x + rand(0,100)/100] = $m;
			} else {
				$r[$x] = $m;
			}
		}
		ksort($r);
		$r = array_values($r);
		return $r;
	}

	function cleanname($name)
	{
		if (is_array($name))
			return $name;

		do {
			$original = $name;
			$name = preg_replace("/[\x01-\x1f\_\!\?\[\{\}\]\/\:\|]+/iU", " ", $name);
			$name = preg_replace("/  +/iU", " ", $name);
			$name = preg_replace("/^[\s\.]+|[\s\.]{2,}$/iU", "", $name);
			$name = trim($name);
		} while ($original != $name);

		return $name;
	}

	function dodbupdate($id, $cat, $name = '', $typeid = 0, $type='', $debug = '')
	{
		if ($debug == '')
			$debug = $this->DEBUGGING;
		$query = "UPDATE `releases` SET  `categoryID` = $cat";

		if ($name != '')
		{
			$query = $query.", `name` = ".$this->db->escapeString($name).", `searchname` = ".$this->db->escapeString($name);
		}
		switch ($type) {
			case 'imdb':
				if ($typeid != 0)
				$query = $query.", `imdbID` = $typeid";
				break;
			case 'book':
				if ($typeid != 0)
				$query = $query.", `bookinfoID` = $typeid";
				break;
			case 'music':
				if ($typeid != 0)
				$query = $query.", `musicinfoID` = $typeid";
				break;
			case 'anime':
				if ($typeid != 0)
				$query = $query.", `anidbID` = $typeid";
				break;
			case 'tv':
				if ($typeid != 0)
				$query = $query.", `rageID` = $typeid";
				break;
			default:
				break;
		}

		$query = $query." where `ID` = $id";
		$this->doecho($query);
		if (!$debug)
		{
			if ($this->db->query($query) !== false)
				return true;
		} else {
			return true;
		}
		return false;
	}

	function doOS ($nfo, $id, $cat)
	{
		$ok = false;

		$nfo = preg_replace("/[^\x09-\x80]|\?/", "", $nfo);

//		$pattern = '/(?<!fine[ \-]|release[ \-])(?:\btitle|\bname|release)\b(?![ \-]type|[ \-]info(?:rmation)?|[ \-]date|[ \-]name|notes)(?:[\-\:\.\}\[\s]+?)([a-z0-9\.\- \(\)\']+?)/Ui';
		$pattern = '/(?<!fine[ \-\.])(?:\btitle|\bname|release)\b(?![ \-\.]type|[ \-\.]info(?:rmation)?|[ \-\.]date|[ \-\.]name|[ \-\.]notes)(?:[\-\:\.\}\[\s]+?)([a-z0-9\.\- \(\)\']+?)/Ui';
		$set = preg_split($pattern,  $nfo, 0, PREG_SPLIT_DELIM_CAPTURE);
		if (isset($set[1]))
			$pos = $this->nfopos ($nfo, $set[1]);
		else
			$pos = false;

		$pattern = '/[\s\_\.\:\xb0-\x{3000}]{2,}([a-z0-9].+v(?:er(?:sion)?)?[\.\s]*?\d+\.\d(?:\.\d+)?.+)(?:[\s\_\.\:\xb0-\x{3000}]{2,}|$)/Uui';
		$set1 = preg_split($pattern,  $nfo, 0, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
		if (isset($set1[1]))
			$pos1 = $this->nfopos ($nfo, $set1[1]);
		else
			$pos1 = false;
		if ((isset($set1[1]) && $pos1 !== false && (real) $pos > (real) $pos1) || $pos === false)
		{
			$set = $set1;
			$pos = $pos1;
		}

		$pattern = '/(?:[\*\?\-\=\|\;\:\.\[\}\]\( \xb0-\x{ff}\?]+)([a-z0-9\&].+\s*\(c\).+)(?:\s\s\s|$|\.\.\.)/Uui';
		$set1 = preg_split($pattern,  $nfo, 0, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
		if (isset($set1[1]))
			$pos1 = $this->nfopos ($nfo, $set1[1]);
		else
			$pos1 = false;
		if ((isset($set1[1]) && $pos1 !== false && (real) $pos > (real) $pos1) || $pos === false)
		{
			$set = $set1;
			$pos = $pos1;
		}

//var_dump($set);

		if (!isset($set[1]) || strlen($set[1]) < 3)
		{
			$pattern = '/(?:(?:presents?|p +r +e +s +e +n +t +s)(?:[^a-z0-9]+?))([a-z0-9 \.\-\_\']+?)/Ui';
			$set = preg_split($pattern,  $nfo, 0, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
		}

		if (isset($set[1]) && strlen($set[1]) < 64)
			$ok = $this->dodbupdate($id, $cat, $this->cleanname($set[1]));

		return $ok;

	}

	function matchnfo ($case, $nfo, $row)
	{
		$ok = false;

		switch (strtolower($case))
		{
			case 't r a c k':
			case 'track':
			case 'trax':
			case 'lame':
			case 'album':
			case 'music':
			case '44.1kHz':
			case 'm3u':
			case 'flac':
				if (preg_match('/(a\s?r\s?t\s?i\s?s\s?t|l\s?a\s?b\s?e\s?l|mp3|e\s?n\s?c\s?o\s?d\s?e\s?r|rip|stereo|mono|single charts)/i', $nfo))
				{
					if (!preg_match('/(\bavi\b|x\.?264|divx|mvk|xvid|install|Setup\.exe|unzip|unrar)/i', $nfo))
					{
						$artist = preg_split('/(?:a\s?r\s?t\s?i\s?s\s?t\b)+? *?(?!(?:[^ \.\:\}\]\*\xb0-\x{3000}\?] ?){2,}?\b)(?:[\*\?\-\=\|\;\:\.\[\}\]\(\s\xb0-\x{3000}\?]+?)[\s\.\>\:\(\)]((?!\:)[a-z0-9\&].+)(?:\s\s\s|$|\.\.\.)/Uuim', $nfo, 0, PREG_SPLIT_DELIM_CAPTURE);
						$title = preg_split('/(?:t\s?i\s?t\s?l\s?e\b|a\s?l\s?b\s?u\s?m\b)+? *?(?!(?:[^ \.\:\}\]\*\xb0-\x{3000}\?] ?){2,}?\b)(?:[\*\?\-\=\|\;\:\.\[\}\]\(\s\xb0-\x{3000}\?]+?)[\s\.\>\:\(\)]((?!\:)[a-z0-9\&].+)(?:\s\s\s|$|\.\.\.)/Uuim', $nfo, 0, PREG_SPLIT_DELIM_CAPTURE);
//var_dump($artist);
//var_dump($title);

						if (!isset($title[1]) || !isset($artist[1]))
						{
							if(preg_match('/presents[\W\. \xb0-\x{3000}]+? ([^\-]+?) \- ([a-z0-9]?(?!\:).+(?:\s\s\s))/iuUm', $nfo, $matches))
							{
								$artist[1] = $matches[1];
								$title[1] = $matches[2];
							}
							if (!isset($matches[2]) && preg_match('/[\h\_\.\:\xb0-\x{3000}]{2,}?([a-z].+) \- (.+)(?:[\s\_\.\:\xb0-\x{3000}]{2,}|$)/iu', $nfo, $matches))
							{

								$pos = $this->nfopos($nfo, $matches[1]." - ".$matches[2]);

								if ($pos !== false && $pos < 0.4 && !preg_match('/\:\d\d$/', $matches[2]) && strlen($matches[1]) < 48 && strlen($matches[2]) < 48)
								{
									if (!preg_match('/title/i', $matches[1]) && !preg_match('/title/i', $matches[2]))
									{
										$artist[1] = $matches[1];
										$title[1] = $matches[2];
									}
								}
							}
						}
						if (isset($artist[1]) && $artist[1] == " ")
							$artist[1] = $artist[3];

						if (isset($title[1]) && isset($artist[1]))
						{
							$ok = $this->dodbupdate($row['ID'], Category::CAT_MUSIC_MP3, $this->cleanname($artist[1])." - ".$this->cleanname($title[1]));
						}
					}
				}
				break;
			case 'dmg':
			case 'mac':
			case 'macintosh':
			case 'macos':
			case 'macosx':
			case 'osx':
				$ok = $this-> doOS($nfo, $row['ID'], Category::CAT_PC_MAC);
				break;

			case 'windows':
			case 'win':
			case 'winall':
			case 'winxp':
			case 'plugin':
			case 'crack':
			case 'linux':
				$ok = $this-> doOS($nfo, $row['ID'], Category::CAT_PC_0DAY);
				break;

			case 'android':
				$ok = $this-> doOS($nfo, $row['ID'], Category::CAT_PC_PHONE_ANDROID);
				break;

			case 'ios':
			case 'iphone':
			case 'ipad':
			case 'ipod':
				$ok = $this-> doOS($nfo, $row['ID'], Category::CAT_PC_PHONE_IOS);
				break;

			case 'game':
					$set = preg_split('/\>(.*)\</Ui',  $nfo, 0, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);

					if (isset($set[1]))
						$ok = $this->dodbupdate($row['ID'], Category::CAT_PC_GAMES, $this->cleanname($set[1]));
					else
						$ok = $this-> doOS($nfo, $row['ID'], Category::CAT_PC_GAMES);
				break;

			case 'imdb':
				$imdb = $this->nfolib->parseImdb($nfo);
				if ($imdb !== false)
				{
					$movie = $this->movie->getMovieInfo($imdb);
					if ($movie !== false)
					{
						if (preg_match('/sport/iU', $movie['genre']))
							$cat = Category::CAT_TV_SPORT;
						elseif (preg_match('/docu/iU', $movie['genre']))
							$cat = Category::CAT_TV_DOCUMENTARY;
						elseif (preg_match('/talk\-show/iU', $movie['genre']))
							$cat = Category::CAT_TV_OTHER;
						elseif (preg_match('/tv/iU', $movie['type']) || preg_match('/episode/iU', $movie['type']) || preg_match('/reality/iU', $movie['type']))
							$cat = Category::CAT_TV_OTHER;
						else
							$cat = Category::CAT_MOVIE_OTHER;
					} else
						$cat = Category::CAT_MOVIE_OTHER;

					$ok = $this->dodbupdate($row['ID'], $cat, '', $imdb, 'imdb');
				}
				break;
			case 'audiobook':
			case 'audible':
			case 'recordedbooks':
			case 'spokenbook':
			case 'readby':
			case 'narratedby':
			case 'narrator':
			case 'speech':
				$author = preg_split('/(?:a\s?u\s?t\s?h\s?o\s?r\b)+? *?(?!(?:[^ \.\:\}\]\*\xb0-\x{3000}\?] ?){2,}?\b)(?:[\*\?\-\=\|\;\:\.\[\}\]\(\s\xb0-\x{3000}\?]+?)[\s\.\>\:\(\)]((?!\:)[a-z0-9\&].+)(?:\s\s\s|$|\.\.\.)/Uuim', $nfo, 0, PREG_SPLIT_DELIM_CAPTURE);
				$title = preg_split('/(?:t\s?i\s?t\s?l\s?e\b|b\s?o\s?o\s?k\b)+? *?(?!(?:[^ \.\:\}\]\*\xb0-\x{3000}\?] ?){2,}?\b)(?:[\*\?\-\=\|\;\:\.\[\}\]\(\s\xb0-\x{3000}\?]+?)[\s\.\>\:\(\)]((?!\:)[a-z0-9\&].+)(?:\s\s\s|$|\.\.\.)/Uuim', $nfo, 0, PREG_SPLIT_DELIM_CAPTURE);
//var_dump($author);
//var_dump($title);
				if (isset($author[1]) && isset($title[1]))
					$ok = $this->dodbupdate($row['ID'], Category::CAT_MUSIC_AUDIOBOOK, $this->cleanname($author[1]." - ".$title[1]));
				elseif (preg_match('/[\h\_\.\:\xb0-\x{3000}]{2,}?([a-z].+) \- (.+)(?:[\s\_\.\:\xb0-\x{3000}]{2,}|$)/iu', $nfo, $matches))
				{
					$pos = $this->nfopos($nfo, $matches[1]." - ".$matches[2]);
					if ($pos !== false && $pos < 0.4 && !preg_match('/\:\d\d$/', $matches[2]) && strlen($matches[1]) < 48 && strlen($matches[2]) < 48)
						if (!preg_match('/title/i', $matches[1]) && !preg_match('/title/i', $matches[2]))
							$ok = $this->dodbupdate($row['ID'], Category::CAT_MUSIC_AUDIOBOOK, $this->cleanname($matches[1]." - ".$matches[2]));
				}

				break;





		}
		return $ok;
	}


	function nfosorter ($category = Category::CAT_PARENT_MISC, $id = 0)
	{
		$this->idarr = $this->getIDs ($category);
		if ($id != 0)
			$this->idarr = $id;

		$query = "SELECT uncompress(releasenfo.nfo) AS nfo, releases.ID, releases.guid, releases.`fromname`, releases.`name`,
	releases.searchname, groups.`name` AS gname FROM releasenfo INNER JOIN releases ON releasenfo.releaseID =
	releases.ID INNER JOIN groups ON releases.groupID = groups.ID WHERE releases.ID in ($this->idarr) order by RAND()";

		$res = $this->db->queryDirect($query);
		while ($row =  $this->db->fetchAssoc($res))
		{
			$hash = $this->getHash($row['name']);
			if ($hash !== false)
				$row['name'] = $hash;

			$nfo = utf8_decode($row['nfo']);

			if (strlen($nfo) > 100)
			{
				$pattern = '/.+(\.rar|\.001) [0-9a-f]{6,10}?|(imdb)\.[a-z0-9\.\_\-\/]+?(?:tt|\?)\d+?\/?|(tvrage)\.com\/|(\bASIN)|(isbn)|(UPC\b)|(comic book)|(comix)|(tv series)|(\bos\b)|(documentaries)|(documentary)|(doku)|(macintosh)|(dmg)|(mac[ _\.\-]??os[ _\.\-]??x??)|(\bos\b\s??x??)|(\bosx\b)';
				$pattern = $pattern . '|(\bios\b)|(iphone)|(ipad)|(ipod)|(pdtv)|(hdtv)|(video streams)|(movie)|(audiobook)|(audible)|(recorded books)|(spoken book)|(speech)|(read by)\:?|(narrator)\:?|(narrated by)';
				$pattern = $pattern . '|(dvd)|(ntsc)|(m4v)|(mov\b)|(avi\b)|(xvid)|(divx)|(mkv)|(amazon\.)[a-z]{2,3}.*\/dp\/|(anidb.net).*aid=|(\blame\b)|(\btrack)|(trax)|(t r a c k)|(music)|(44.1kHz)|video (game)|type:(game)|(game) Type|(game)[ \.]+|(platform)|(console)|\b(win(?:dows|all|xp)\b)|(\bwin\b)';
				$pattern = $pattern . '|(m3u)|(flac\b)|(application)|(plugin)|(\bcrack\b)|(install)|(setup)|(magazin)|(x264)|(h264)|(itunes\.apple\.com\/)|(sport)|(deportes)|(nhl)|(nfl)|(\bnba)|(ncaa)|(album)|(\bepub\b)|(mobi)|format\W+?[^\r]*(pdf)/iU';

				$matches = preg_split($pattern, $nfo, -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);

				array_shift($matches);

				$matches = $this->doarray($matches);

				foreach($matches as $m)
				{
					if (isset($m))
						$case = preg_replace('/ /', '', $m);
					else
						$case = '';

					if (($m == 'os' || $m == 'platform' || $m == 'console') && preg_match('/(?:\bos\b(?: type)??|platform|console)[ \.\:\}]+(\w+?).??(\w*?)/iU', $nfo, $set))
					{

						if (isset($set[1]))
						{
			//	var_dump($set);
							$case = strtolower($set[1]);
						}
						if (strlen($set[2]) && (stripos($set[2], 'mac') !== false || stripos($set[2], 'osx') !== false))
						{
							$case = strtolower($set[2]);
						}
					}

					$pos = $this->nfopos ($nfo, $m);

					if ($pos !== false && $pos > 0.55 && $case != 'imdb')
						continue;

					echo "$case ".round($pos/strlen($nfo)*100, 0)." ".$row['guid']."\n";

					if ($this->matchnfo($case, $nfo, $row))
						continue(2);
				}
			}
		}
	}
}
