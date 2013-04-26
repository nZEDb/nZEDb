<?php
require_once(WWW_DIR."/lib/util.php");
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/category.php");
require_once(WWW_DIR."/lib/releaseimage.php");

class AniDB
{
	const CLIENT	= 'newznab';
	const CLIENTVER = 1;

	function AniDB($echooutput=false)
	{
		$s = new Sites();
		$site = $s->get();
		$this->aniqty = (!empty($site->maxanidbprocessed)) ? $site->maxanidbprocessed : 100;
		$this->echooutput = $echooutput;
		$this->imgSavePath = WWW_DIR.'covers/anime/';
	}

	public function animetitlesUpdate()
	{
		$db = new DB();

		$lastUpdate = $db->queryOneRow("SELECT unixtime as utime FROM animetitles LIMIT 1");
		if(isset($lastUpdate['utime']) && (time() - $lastUpdate['utime']) < 604800)
			return;

		if ($this->echooutput)
			echo "Updating animetitles.";

		$zh = gzopen('http://anidb.net/api/animetitles.dat.gz', 'r');

		preg_match_all('/(\d+)\|\d\|.+\|(.+)/', gzread($zh, '10000000'), $animetitles);
		if(!$animetitles)
			return false;

		if ($this->echooutput)
			echo ".";

		$db->query("DELETE FROM animetitles WHERE anidbID IS NOT NULL");

		for($i = 0; $i < count($animetitles[1]); $i++) {
			$db->queryInsert(sprintf("INSERT INTO animetitles (anidbID, title, unixtime) VALUES (%d, %s, %d)",
			$animetitles[1][$i], $db->escapeString(html_entity_decode($animetitles[2][$i], ENT_QUOTES, 'UTF-8')), time()));
		}

		$db = NULL;

		gzclose($zh);

		if ($this->echooutput)
			echo " done.\n";
	}

	public function addTitle($AniDBAPIArray)
	{
		$db = new DB();

		$db->queryInsert(sprintf("INSERT INTO anidb VALUES ('', %d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d)",
		$AniDBAPIArray['anidbID'], $db->escapeString($AniDBAPIArray['title']), $db->escapeString($AniDBAPIArray['type']), $db->escapeString($AniDBAPIArray['startdate']),
		$db->escapeString($AniDBAPIArray['enddate']), $db->escapeString($AniDBAPIArray['related']), $db->escapeString($AniDBAPIArray['creators']),
		$db->escapeString($AniDBAPIArray['description']), $db->escapeString($AniDBAPIArray['rating']), $db->escapeString($AniDBAPIArray['picture']),
		$db->escapeString($AniDBAPIArray['categories']), $db->escapeString($AniDBAPIArray['characters']), $db->escapeString($AniDBAPIArray['epnos']),
		$db->escapeString($AniDBAPIArray['airdates']), $db->escapeString($AniDBAPIArray['episodetitles']), time()));
	}

	public function updateTitle($anidbID, $title, $type, $startdate, $enddate, $related, $creators, $description, $rating, $categories, $characters, $epnos, $airdates, $episodetitles)
	{
		$db = new DB();

		$db->query(sprintf("UPDATE anidb
		SET title=%s, type=%s, startdate=%s, enddate=%s, related=%s, creators=%s, description=%s, rating=%s, categories=%s, characters=%s, epnos=%s, airdates=%s, episodetitles=%s, unixtime=%d
		WHERE anidbID = %d", $db->escapeString($title), $db->escapeString($type), $db->escapeString($startdate), $db->escapeString($enddate), $db->escapeString($related),
		$db->escapeString($creators), $db->escapeString($description), $db->escapeString($rating), $db->escapeString($categories), $db->escapeString($characters),
		$db->escapeString($epnos), $db->escapeString($airdates), $db->escapeString($episodetitles), $anidbID, time()));
	}

	public function deleteTitle($anidbID)
	{
		$db = new DB();

		$db->query(sprintf("DELETE FROM anidb WHERE anidbID = %d", $anidbID));
	}

	public function getanidbID($title)
	{
		$db = new DB();

		$anidbID = $db->queryOneRow(sprintf("SELECT anidbID as anidbID FROM animetitles WHERE title REGEXP %s LIMIT 1", $db->escapeString('^'.$title.'$')));

		return $anidbID['anidbID'];
	}

	public function getAnimeList($letter='', $animetitle='')
	{
		$db = new DB();

		$rsql = '';
		if ($letter != '')
		{
			if ($letter == '0-9')
				$letter = '[0-9]';

			$rsql .= sprintf("AND anidb.title REGEXP %s", $db->escapeString('^'.$letter));
		}

		$tsql = '';
		if ($animetitle != '')
		{
			$tsql .= sprintf("AND anidb.title LIKE %s", $db->escapeString("%".$animetitle."%"));
		}

		$sql = sprintf(" SELECT anidb.ID, anidb.anidbID, anidb.title, anidb.type, anidb.categories, anidb.rating, anidb.startdate, anidb.enddate
			FROM anidb WHERE anidb.anidbID > 0 %s %s GROUP BY anidb.anidbID ORDER BY anidb.title ASC", $rsql, $tsql);

		return $db->query($sql);
	}

	public function getAnimeRange($start, $num, $animetitle='')
	{
		$db = new DB();

		if ($start === false)
			$limit = '';
		else
			$limit = " LIMIT ".$start.",".$num;

		$rsql = '';
		if ($animetitle != '')
			$rsql .= sprintf("AND anidb.title LIKE %s ", $db->escapeString("%".$animetitle."%"));

		return $db->query(sprintf(" SELECT ID, anidbID, title, description FROM anidb WHERE 1=1 %s ORDER BY anidbID ASC".$limit, $rsql));
	}

	public function getAnimeCount($animetitle='')
	{
		$db = new DB();

		$rsql = '';
		if ($animetitle != '')
			$rsql .= sprintf("AND anidb.title LIKE %s ", $db->escapeString("%".$animetitle."%"));

		$res = $db->queryOneRow(sprintf("SELECT count(ID) AS num FROM anidb where 1=1 %s ", $rsql));

		return $res["num"];
	}

	public function getAnimeInfo($anidbID)
	{
		$db = new DB();
		$animeInfo = $db->query(sprintf("SELECT * FROM anidb WHERE anidbID = %d", $anidbID));

		return isset($animeInfo[0]) ? $animeInfo[0] : false;
	}

	public function cleanFilename($searchname)
	{
		$noforeign = 'English|Japanese|German|Danish|Flemish|Dutch|French|Swe(dish|sub)|Deutsch|Norwegian';

		$searchname = preg_replace('/^Arigatou[._ ]|\]BrollY\]|[._ ]v(er[._ ]?)?\d|Complete[._ ](?=Movie)|((HD)?DVD|B(luray|[dr])(rip)?)|Rs?\d|[xh][._ ]?264|A(C3|52)| \d+[pi]\s|[SD]ub(bed)?|Creditless/i', ' ', $searchname);

		$searchname = preg_replace('/(\[|\()(?!\d{4}\b)[^\]\)]+(\]|\))/', '', $searchname);
		$searchname = (preg_match_all("/[._ ]-[._ ]/", $searchname, $count) >= 2) ? preg_replace('/[^-]+$/i', '', $searchname) : $searchname;
		$searchname = preg_replace('/[._]| ?~ ?|\s{2,}|[-:]+ | (Part|CD) ?\d*( ?(of|\/|\|) ?)?\d* ?$/i', ' ', $searchname);
		$searchname = preg_replace('/( S\d+ ?E\d+|Movie ?(\d+|[ivx]+))(.*$)/i', '${1}', $searchname);
		$searchname = preg_replace('/ ([12][890]\d{2})\b/i', ' (${1})', $searchname);
		$searchname = str_ireplace('\'', '`', $searchname);

		$cleanFilename = preg_replace('/ (NC)?Opening ?/i', ' OP', $searchname);
		$cleanFilename = preg_replace('/ (NC)?(Ending|Credits|Closing) ?/i', ' ED', $cleanFilename);
		$cleanFilename = preg_replace('/ (Trailer|TR(?= ?\d)) ?/i', ' T', $cleanFilename);
		$cleanFilename = preg_replace('/ (Promo|P(?= ?\d)) ?/i', ' PV', $cleanFilename);
		$cleanFilename = preg_replace('/ (Special|Extra|SP(?= ?\d)) ?(?! ?[a-z])/i', ' S', $cleanFilename);
		$cleanFilename = preg_replace('/ Vol(ume)? ?(?=\d)/i', ' Vol', $cleanFilename);
		$cleanFilename = preg_replace('/ (?:NC)?(OP|ED|[ST](?! ?[a-z])|PV)( ?v(er)? ?\d)? (?!\d )/i', ' ${1}1', $cleanFilename);
		$cleanFilename = preg_replace('/ (?:NC)?(OP|ED|[STV]|PV|O[AV][AV])(?: ?v(?:er)? ?\d+)? (?:(?:[A-Z]{2,3}(?:i?sode)?)?(\d+[a-z]?))/i', ' ${1}${2}', $cleanFilename);

		preg_match('/^(?P<title>.+) (?P<epno>(?:NC)?(?:[A-Z](?=\d)|[A-Z]{2,3})?(?![A-Z]| [A-Z]|$) ?(?:(?<![&+] | and | v| ver|\w Movie )\d{1,3}(?!\d)(?:-\d{1,3}(?!\d))?)(?:[a-z])?)/i', $cleanFilename, $cleanFilename);

		$cleanFilename['title'] = (isset($cleanFilename['title'])) ? trim($cleanFilename['title']) : trim($searchname);
		$cleanFilename['title'] = preg_replace('/([^a-z0-9\s])/i', '[${1}]?', $cleanFilename['title']);
		$cleanFilename['title'] = preg_replace('/( (The |Movie|O[AV][AV]|TV|\[\(\]\d{4}\[\)\]|Ep(isode)?|Vol(ume)?|Part|Phase|Chapter|Mission|(Director[`\']?s )?(Un)?C(ut|hoice)|Rem(aster|[iu]xx?)(ed)?|'.$noforeign.'))/i', '(${1})?', $cleanFilename['title']);
		
		$cleanFilename['epno'] = (isset($cleanFilename['epno'])) ? preg_replace('/^(NC|E(?!D)p?0*)|(?=^|-)0+|v(er)?(\d+)?$/i', '', $cleanFilename['epno']) : 1;
		if(preg_match('/S\d+ ?[ED]\d+/i', $searchname)) {
			//TODO: thetvdb lookup for absolute #?
			preg_match('/S(\d+) ?([ED])(\d+)/i', $searchname, $epno);
			$cleanFilename['epno'] = 'S'.(int) $epno[1].$epno[2].(int) $epno[3];
		}
		else if(preg_match('/^[a-z]/i', $cleanFilename['epno'])) {
			preg_match('/([^\d]+)(\d+)/i', $cleanFilename['epno'], $epno);
			$cleanFilename['epno'] = $epno[1].(int) $epno[2];
		}
		
		return $cleanFilename;
	}

	public function processAnimeReleases()
	{
		$db = new DB();
		$ri = new ReleaseImage();

		$results = $db->queryDirect(sprintf("SELECT searchname, ID FROM releases WHERE anidbID is NULL AND categoryID IN ( SELECT ID FROM category WHERE categoryID = %d limit %d )", Category::CAT_TV_ANIME, $this->aniqty));

		if ($db->getNumRows($results) > 0) {
			if ($this->echooutput)
				echo "Processing ".$db->getNumRows($results)." anime releases\n";

			while ($arr = $db->fetchAssoc($results)) {

				$cleanFilename = $this->cleanFilename($arr['searchname']);
				$anidbID = $this->getanidbID($cleanFilename['title']);
				if(!$anidbID) {
					$db->query(sprintf("UPDATE releases SET anidbID = %d, rageID = %d WHERE ID = %d", -1, -2, $arr["ID"]));
					continue;
				}

				if ($this->echooutput)
					echo 'Looking up: '.$arr['searchname']."\n";

				$AniDBAPIArray = $this->getAnimeInfo($anidbID);
				$lastUpdate = ((isset($AniDBAPIArray['unixtime']) && (time() - $AniDBAPIArray['unixtime']) > 604800));

				if (!$AniDBAPIArray || $lastUpdate) {
					$AniDBAPIArray = $this->AniDBAPI($anidbID);

					if(! $lastUpdate)
						$this->addTitle($AniDBAPIArray);
					else {
						$this->updateTitle($AniDBAPIArray['anidbID'], $AniDBAPIArray['title'], $AniDBAPIArray['type'], $AniDBAPIArray['startdate'],
							$AniDBAPIArray['enddate'], $AniDBAPIArray['related'], $AniDBAPIArray['creators'], $AniDBAPIArray['description'],
							$AniDBAPIArray['rating'], $AniDBAPIArray['categories'], $AniDBAPIArray['characters'], $AniDBAPIArray['epnos'],
							$AniDBAPIArray['airdates'], $AniDBAPIArray['episodetitles']);
					}

					if($AniDBAPIArray['picture'])
						$ri->saveImage($AniDBAPIArray['anidbID'], 'http://img7.anidb.net/pics/anime/'.$AniDBAPIArray['picture'], $this->imgSavePath);
				}

				if ($AniDBAPIArray['anidbID']) {
					$epno = explode('|', $AniDBAPIArray['epnos']);
					$airdate = explode('|', $AniDBAPIArray['airdates']);
					$episodetitle = explode('|', $AniDBAPIArray['episodetitles']);

					for($i = 0; $i < count($epno); $i++) {
						if($cleanFilename['epno'] == $epno[$i]) {
							$offset = $i;
							break;
						}
						else $offset = -1;
					}

					$airdate = isset($airdate[$offset]) ? $airdate[$offset] : $AniDBAPIArray['startdate'];
					$episodetitle = isset($episodetitle[$offset]) ? $episodetitle[$offset] : $cleanFilename['epno'];
					$tvtitle = ($episodetitle !== 'Complete Movie' && $episodetitle !== $cleanFilename['epno']) ? $cleanFilename['epno']." - ".$episodetitle : $episodetitle;

					if ($this->echooutput)
						echo '- found '.$AniDBAPIArray['anidbID']."\n";

					$db->query(sprintf("UPDATE releases SET episode=%s, tvtitle=%s, tvairdate=%s, anidbID=%d, rageID=%d WHERE ID = %d",
					$db->escapeString($cleanFilename['epno']), $db->escapeString($tvtitle), $db->escapeString($airdate), $AniDBAPIArray['anidbID'], -2, $arr["ID"]));
				}
			}

			if ($this->echooutput)
				echo "Processed ".$db->getNumRows($results)." anime releases.\n";
		}
	}

	public function AniDBAPI($anidbID)
	{
		$ch = curl_init('http://api.anidb.net:9001/httpapi?request=anime&client='.self::CLIENT.'&clientver='.self::CLIENTVER.'&protover=1&aid='.$anidbID);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip');

		$apiresponse = curl_exec($ch);
		if(!$apiresponse)
			return false;
		curl_close($ch);

		//TODO: SimpleXML - maybe not.

		$AniDBAPIArray['anidbID'] = $anidbID;

		preg_match_all('/<title xml:lang="x-jat" type="(?:official|main)">(.+)<\/title>/i', $apiresponse, $title);
		$AniDBAPIArray['title'] = isset($title[1][0]) ? $title[1][0] : '';

		preg_match_all('/<(type|(?:start|end)date)>(.+)<\/\1>/i', $apiresponse, $type_startenddate);
		$AniDBAPIArray['type'] = isset($type_startenddate[2][0]) ? $type_startenddate[2][0] : '';
		$AniDBAPIArray['startdate'] = isset($type_startenddate[2][1]) ? $type_startenddate[2][1] : '';
		$AniDBAPIArray['enddate'] = isset($type_startenddate[2][2]) ? $type_startenddate[2][2] : '';

		preg_match_all('/<anime id="\d+" type=".+">([^<]+)<\/anime>/is', $apiresponse, $related);
		$AniDBAPIArray['related'] = isset($related[1]) ? implode($related[1], '|') : '';

		preg_match_all('/<name id="\d+" type=".+">([^<]+)<\/name>/is', $apiresponse, $creators);
		$AniDBAPIArray['creators'] = isset($creators[1]) ? implode($creators[1], '|') : '';

		preg_match('/<description>([^<]+)<\/description>/is', $apiresponse, $description);
		$AniDBAPIArray['description'] = isset($description[1]) ? $description[1] : '';

		preg_match('/<permanent count="\d+">(.+)<\/permanent>/i', $apiresponse, $rating);
		$AniDBAPIArray['rating'] = isset($rating[1]) ? $rating[1] : '';

		preg_match('/<picture>(.+)<\/picture>/i', $apiresponse, $picture);
		$AniDBAPIArray['picture'] = isset($picture[1]) ? $picture[1] : '';

		preg_match_all('/<category id="\d+" parentid="\d+" hentai="(?:true|false)" weight="\d+">\s+<name>([^<]+)<\/name>/is', $apiresponse, $categories);
		$AniDBAPIArray['categories'] = isset($categories[1]) ? implode($categories[1], '|') : '';

		preg_match_all('/<character id="\d+" type=".+" update="\d{4}-\d{2}-\d{2}">\s+<name>([^<]+)<\/name>/is', $apiresponse, $characters);
		$AniDBAPIArray['characters'] = isset($characters[1]) ? implode($characters[1], '|') : '';

		preg_match('/<episodes>\s+<episode.+<\/episodes>/is', $apiresponse, $episodes);
		preg_match_all('/<epno>(.+)<\/epno>/i', $episodes[0], $epnos);
		$AniDBAPIArray['epnos'] = isset($epnos[1]) ? implode($epnos[1], '|') : '';
		preg_match_all('/<airdate>(.+)<\/airdate>/i', $episodes[0], $airdates);
		$AniDBAPIArray['airdates'] = isset($airdates[1]) ? implode($airdates[1], '|') : '';
		preg_match_all('/<title xml:lang="en">(.+)<\/title>/i', $episodes[0], $episodetitles);
		$AniDBAPIArray['episodetitles'] = isset($episodetitles[1]) ? implode($episodetitles[1], '|') : '';

		sleep(2); //to comply with flooding rule.

		return $AniDBAPIArray;
	}
}

?>
