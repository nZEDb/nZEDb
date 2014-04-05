<?php
require_once nZEDb_LIB . 'utility' . DS . 'Utility.php';

use nzedb\db\DB;

class AniDB
{

	function __construct($echooutput = false)
	{
		$s = new Sites();
		$site = $s->get();

		$this->aniqty = (!empty($site->maxanidbprocessed)) ? $site->maxanidbprocessed : 100;
		$this->echooutput = ($echooutput && nZEDb_ECHOCLI);
		$this->imgSavePath = nZEDb_COVERS . 'anime' . DS;
		$this->db = new DB();
		$this->c = new ColorCLI();
	}

	public function animetitlesUpdate()
	{
		// this should not be run as it should be handled by populate_anidb
		if ($this->echooutput) {
			$this->c->doEcho("Skipped update aniTitles as it is handled by populate_anidb in misc/testing/DB", true);
		}
		return;
	}

	public function addTitle($AniDBAPIArray)
	{
		$db = $this->db;
		$db->queryInsert(sprintf("INSERT INTO anidb VALUES (%d, 0, 0, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d)", $AniDBAPIArray['anidbid'], $db->escapeString($AniDBAPIArray['title']), $db->escapeString($AniDBAPIArray['type']), $db->escapeString($AniDBAPIArray['startdate']), $db->escapeString($AniDBAPIArray['enddate']), $db->escapeString($AniDBAPIArray['related']), $db->escapeString($AniDBAPIArray['creators']), $db->escapeString($AniDBAPIArray['description']), $db->escapeString($AniDBAPIArray['rating']), $db->escapeString($AniDBAPIArray['picture']), $db->escapeString($AniDBAPIArray['categories']), $db->escapeString($AniDBAPIArray['characters']), $db->escapeString($AniDBAPIArray['epnos']), $db->escapeString($AniDBAPIArray['airdates']), $db->escapeString($AniDBAPIArray['episodetitles']), time()));
	}

	public function updateTitle($anidbID, $title, $type, $startdate, $enddate, $related, $creators, $description, $rating, $categories, $characters, $epnos, $airdates, $episodetitles)
	{
		$db = $this->db;
		$db->queryExec(sprintf('UPDATE anidb SET title = %s, type = %s, startdate = %s, enddate = %s, related = %s, creators = %s, description = %s, rating = %s, categories = %s, characters = %s, epnos = %s, airdates = %s, episodetitles = %s, unixtime = %d WHERE anidbid = %d', $db->escapeString($title), $db->escapeString($type), $db->escapeString($startdate), $db->escapeString($enddate), $db->escapeString($related), $db->escapeString($creators), $db->escapeString($description), $db->escapeString($rating), $db->escapeString($categories), $db->escapeString($characters), $db->escapeString($epnos), $db->escapeString($airdates), $db->escapeString($episodetitles), $anidbID, time()));
	}

	public function deleteTitle($anidbID)
	{
		$db = $this->db;
		$db->queryExec(sprintf('DELETE FROM anidb WHERE anidbid = %d', $anidbID));
	}

	public function getanidbID($title)
	{
		$db = $this->db;
		$anidbID = "";
		if ($db->dbSystem() === 'mysql') {
			$query = sprintf('SELECT anidbid as anidbid FROM animetitles WHERE title REGEXP %s LIMIT 1', $db->escapeString('^' . $title . '$'));
			$anidbID = $db->queryOneRow($query);

			// if the first query failed try it again using like as we have a change for a match
			if ($anidbID == False) {
				$query = sprintf('SELECT anidbid as anidbid FROM animetitles WHERE title LIKE %s LIMIT 1', $db->escapeString('%' . $title . '%'));
				$anidbID = $db->queryOneRow($query);
			}
		} else {
			$query = sprintf('SELECT anidbid as anidbid FROM animetitles WHERE title ~ %s LIMIT 1', $db->escapeString('^' . $title . '$'));
			$anidbID = $db->queryOneRow($query);
		}

		return $anidbID['anidbid'];
	}

	public function getAnimeList($letter = '', $animetitle = '')
	{
		$db = $this->db;

		if ($db->dbSystem() === 'mysql') {
			$regex = 'REGEXP';
			$like = 'LIKE';
		} else {
			$regex = '~';
			$like = 'ILIKE';
		}

		$rsql = '';
		if ($letter != '') {
			if ($letter == '0-9')
				$letter = '[0-9]';

			$rsql .= sprintf('AND anidb.title %s %s', $regex, $db->escapeString('^' . $letter));
		}

		$tsql = '';
		if ($animetitle != '')
			$tsql .= sprintf('AND anidb.title %s %s', $like, $db->escapeString('%' . $animetitle . '%'));

		return $db->query(sprintf('SELECT anidb.anidbid, anidb.title, anidb.type, anidb.categories, anidb.rating, anidb.startdate, anidb.enddate FROM anidb WHERE anidb.anidbid > 0 %s %s GROUP BY anidb.anidbid ORDER BY anidb.title ASC', $rsql, $tsql));
	}

	public function getAnimeRange($start, $num, $animetitle = '')
	{
		$db = $this->db;

		if ($start === false)
			$limit = '';
		else
			$limit = ' LIMIT ' . $num . ' OFFSET ' . $start;

		$rsql = '';
		if ($animetitle != '') {
			if ($db->dbSystem() === 'mysql')
				$rsql = sprintf('AND anidb.title LIKE %s', $db->escapeString('%' . $animetitle . '%'));
			else
				$rsql = sprintf('AND anidb.title ILIKE %s', $db->escapeString('%' . $animetitle . '%'));
		}

		return $db->query(sprintf('SELECT anidbid, title, description FROM anidb WHERE 1=1 %s ORDER BY anidbid ASC' . $limit, $rsql));
	}

	public function getAnimeCount($animetitle = '')
	{
		$db = $this->db;

		$rsql = '';
		if ($animetitle != '') {
			if ($db->dbSystem() === 'mysql')
				$rsql .= sprintf('AND anidb.title LIKE %s', $db->escapeString('%' . $animetitle . '%'));
			else
				$rsql .= sprintf('AND anidb.title ILIKE %s', $db->escapeString('%' . $animetitle . '%'));
		}

		$res = $db->queryOneRow(sprintf('SELECT COUNT(anidbid) AS num FROM anidb WHERE 1=1 %s', $rsql));

		return $res['num'];
	}

	public function getAnimeInfo($anidbID)
	{
		$db = $this->db;
		$animeInfo = $db->query(sprintf('SELECT * FROM anidb WHERE anidbid = %d', $anidbID));

		return isset($animeInfo[0]) ? $animeInfo[0] : false;
	}

	public function cleanFilename($searchname)
	{
		$noforeign = 'English|Japanese|German|Danish|Flemish|Dutch|French|Swe(dish|sub)|Deutsch|Norwegian';

		$searchname = preg_replace('/^Arigatou[._ ]|\]BrollY\]|[._ ]v(er[._ ]?)?\d|Complete[._ ](?=Movie)|((HD)?DVD|B(luray|[dr])(rip)?)|Rs?\d|[xh][._ ]?264|A(C3|52)| \d+[pi]\s|[SD]ub(bed)?|Creditless/i', ' ', $searchname);

		$searchname = preg_replace('/(\[|\()(?!\d{4}\b)[^\]\)]+(\]|\))/', '', $searchname);
		$searchname = (preg_match_all('/[._ ]-[._ ]/', $searchname, $count) >= 2) ? preg_replace('/[^-]+$/i', '', $searchname) : $searchname;
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

		$cleanFilename_org = $cleanFilename;
		preg_match('/^(?P<title>.+) (?P<epno>(?:NC)?(?:[A-Z](?=\d)|[A-Z]{2,3})?(?![A-Z]| [A-Z]|$) ?(?:(?<![&+] | and | v| ver|\w Movie )\d{1,3}(?!\d)(?:-\d{1,3}(?!\d))?)(?:[a-z])?)/i', $cleanFilename, $cleanFilename);

		// if this did not return anything use the previous string as found as the title
		if (!isset($cleanFilename['title']))
			$cleanFilename['title'] = $cleanFilename_org;

		// extra cleanup to get a valid title
		// remove trailing and leading spaces from teh title
		$cleanFilename['title'] = trim($cleanFilename['title']);
		// remove trailing -
		$cleanFilename['title'] = preg_replace('/-$/i', '', $cleanFilename['title']);
		// replace multiple spaces with a single one
		$cleanFilename['title'] = preg_replace('/\s+/i', ' ', $cleanFilename['title']);
		// remove any remaining "'s
		$cleanFilename['title'] = preg_replace('/"/i', ' ', $cleanFilename['title']);
		// remove remaing numbers since they are suppose to be part of the epno
		$cleanFilename['title'] = preg_replace('/\d\d+/i', '', $cleanFilename['title']);
		// remove video extentions
		$cleanFilename['title'] = preg_replace('/(avi|mp4|mkv|ogv|wmv)/i', '', $cleanFilename['title']);
		// language as that is never part of an anime title
		$cleanFilename['title'] = preg_replace('/( |\()(english|eng|french|fre|italian|ita|japanese|jap|chinese|ch)(\)| )/i', '', $cleanFilename['title']);

		// there is a case were we have title  tile in this case we want only the first instance, normally these are seperated by mutiple spaces
		$mypieces = explode("   ", $cleanFilename['title']);
		if (isset($mypieces[0])) {
			trim($mypieces[0]);
			$cleanFilename['title'] = $mypieces[0];
		}
		// if we end up with something like S2 as in Season 2, try removing the S as anidb want it to read 'title 2'
		$cleanFilename['title'] = preg_replace('/S(\d+)/i', '$1', $cleanFilename['title']);
		// extra cleanup to get a valid title

		$cleanFilename['title'] = (isset($cleanFilename['title'])) ? trim($cleanFilename['title']) : trim($searchname);
		$cleanFilename['title'] = preg_replace('/([^a-z0-9\s])/i', '[${1}]?', $cleanFilename['title']);
		$cleanFilename['title'] = preg_replace('/( (The |Movie|O[AV][AV]|TV|\[\(\]\d{4}\[\)\]|Ep(isode)?|Vol(ume)?|Part|Phase|Chapter|Mission|(Director[`\']?s )?(Un)?C(ut|hoice)|Rem(aster|[iu]xx?)(ed)?|' . $noforeign . '))/i', '(${1})?', $cleanFilename['title']);

		$cleanFilename['epno'] = (isset($cleanFilename['epno'])) ? preg_replace('/^(NC|E(?!D)p?0*)|(?=^|-)0+|v(er)?(\d+)?$/i', '', $cleanFilename['epno']) : 1;
		if (preg_match('/S\d+ ?[ED]\d+/i', $searchname)) {
			//TODO: thetvdb lookup for absolute #?
			preg_match('/S(\d+) ?([ED])(\d+)/i', $searchname, $epno);
			$cleanFilename['epno'] = 'S' . (int) $epno[1] . $epno[2] . (int) $epno[3];
		} else if (preg_match('/^[a-z]/i', $cleanFilename['epno'])) {
			preg_match('/([^\d]+)(\d+)/i', $cleanFilename['epno'], $epno);
			$cleanFilename['epno'] = $epno[1] . (int) $epno[2];
		}

		return $cleanFilename;
	}

	// the release name is dieefrent from teh title, but it is what we want to store as the searchname
	public function getReleaseName($searchname)
	{
		// extra cleanup to get a more valid title
		// extract the part of the string in quotes (normally the name if present)
		$namematch = "";
		preg_match('/".*?"/', $searchname, $namematch);

		// if "'s are  present process
		// in a quick check of 2000 anime entires this works 98.5% of the time without falling back
		if (isset($namematch[0])) {
			// start by getting rid of the exterior "'s
			$searchname = $namematch[0];
			$searchname = preg_replace('/(^"|"$)/', '', $searchname);

			// remove any _'s and replace with spaces
			$searchname = preg_replace('/_/i', ' ', $searchname);
			// remove any multiple spaces
			$searchname = preg_replace('/\s+/i', ' ', $searchname);
			// remove extentions such as .001 or .010
			$searchname = preg_replace('/\.\d+$/i', '', $searchname);

			// to make this compatable with cleanFilename return an array
			$cleanFilename['title'] = $searchname;

			// The episode number is normally a set of numbers surrounded by . or spaces
			preg_match('/(\.d+\.| \d+ )/', $searchname, $episodematch);
			// if numbers are  present
			if (isset($episodematch[0])) {
				// remove any spaces or .'s surrounding it
				$episodematch[0] = trim($episodematch[0], "\. ");
				$episodematch[0] = (int) $episodematch[0];

				$cleanFilename['epno'] = $episodematch[0];
			}

			return $cleanFilename;
		} else {
			if ($this->echooutput) {
				$this->c->doEcho("\tFalling back to Pure REGEX method to determine name.", true);
			}

			// if no "'s were found then fall back to cleanFilename;
			return $this->cleanFilename($searchname);
		}
	}

	// determine if given an ID it is ANIME or not, this should be moved to postprocess to be cleaner but for now as to not touch another file leave it here
	public function checkIfAnime($releaseid)
	{
		$db = $this->db;
		$result = $db->query(sprintf('SELECT categoryid FROM releases WHERE id = %d', $releaseid));

		if (isset($result[0]['categoryid']) && $result[0]['categoryid'] == "5070")
			return True;
		else
			return False;
	}

	function processAnAnimeRelease($results)
	{
		$db = $this->db;
		$ri = new ReleaseImage();
		$site = new Sites();

		if (count($results) > 0) {
			if ($this->echooutput) {
				$this->c->doEcho('Processing ' . count($results) . " anime releases.", true);
			}

			foreach ($results as $arr) {

				// clean up the release name to ensure we get a good chance at getting a valid filename
				$cleanFilename = $this->cleanFilename($arr['searchname']);

				// Get a release name to update the DB with, this is more than the title as it includes group size ... or worst case the same as the title
				$getReleaseName = $this->getReleaseName($arr['searchname']);

				if ($this->echooutput) {
					$this->c->doEcho("\tProcessing Anime entitled: " . $getReleaseName['title'], true);
				}

				// get anidb number for the title of the naime
				$anidbID = $this->getanidbID($cleanFilename['title']);
				if (!$anidbID) {
					// no anidb ID found so set what we know and exit
					$db->queryExec(sprintf('UPDATE releases SET searchname = %s, anidbid = %d, rageid = %d WHERE id = %d', $db->escapeString($getReleaseName['title']), -1, -2, $arr['id']));
					continue;
				}

				if ($this->echooutput) {
					$this->c->doEcho('Looking up: ' . $arr['searchname'], true);
				}

				$AniDBAPIArray = $this->getAnimeInfo($anidbID);

				if ($AniDBAPIArray['anidbid']) {
					// if this anime is found postprocess it
					$epno = explode('|', $AniDBAPIArray['epnos']);
					$airdate = explode('|', $AniDBAPIArray['airdates']);
					$episodetitle = explode('|', $AniDBAPIArray['episodetitles']);

					// locate the episode if possible
					for ($i = 0; $i < count($epno); $i++) {
						if ($cleanFilename['epno'] == $epno[$i]) {
							$offset = $i;
							break;
						} else
							$offset = -1;
					}

					// update the airdate if teh episode is found
					$airdate = isset($airdate[$offset]) ? $airdate[$offset] : $AniDBAPIArray['startdate'];
					// update the episode title if teh episdoe is found
					$episodetitle = isset($episodetitle[$offset]) ? $episodetitle[$offset] : $cleanFilename['epno'];
					//set the TV title to that of the episode
					$tvtitle = ($episodetitle !== 'Complete Movie' && $episodetitle !== $cleanFilename['epno']) ? $cleanFilename['epno'] . ' - ' . $episodetitle : $episodetitle;

					if ($this->echooutput) {
						$this->c->doEcho('- found ' . $AniDBAPIArray['anidbid'], true);
					}

					// lastly update the information, we also want a better readable name, AKA search name so we can use the title we cleaned
					$db->queryExec(sprintf('UPDATE releases SET searchname = %s, episode = %s, tvtitle = %s, tvairdate = %s, anidbid = %d, rageid = %d WHERE id = %d', $db->escapeString($getReleaseName['title']), $db->escapeString($cleanFilename['epno']), $db->escapeString($tvtitle), $db->escapeString($airdate), $AniDBAPIArray['anidbid'], -2, $arr['id']));
				}
				else {
					// if the anime was not found, just simply update the search name
					$db->queryExec(sprintf('UPDATE releases SET searchname = %s, anidbid = %d WHERE id = %d', $db->escapeString($getReleaseName['title']), $AniDBAPIArray['anidbid'], $arr['id']));
				}
			} // foreach

			if ($this->echooutput) {
				$this->c->doEcho('Processed ' . count($results) . " anime releases.", true);
			}
		} // if
		else {
			if ($this->echooutput) {
				$this->c->doEcho($this->c->header('No anime releases to process.'));
			}
		}
	}

	// process a group of previously unprcoessed Anime Releases, as in postprocess
	public function processAnimeReleases($hours = 0)
	{
		$db = $this->db;
		if ($hours == 0)
			$results = $db->query(sprintf('SELECT searchname, id FROM releases WHERE nzbstatus = 1 AND anidbid IS NULL AND categoryid IN (SELECT id FROM category WHERE categoryid = %d) ORDER BY postdate DESC LIMIT %d', Category::CAT_TV_ANIME, $this->aniqty));
		else
		// only select items within 6 hours
			$results = $db->query(sprintf('SELECT searchname, id FROM releases WHERE nzbstatus = 1 AND anidbid IS NULL AND categoryid IN (SELECT id FROM category WHERE categoryid = %d) adddate > ( NOW( ) - INTERVAL 6 HOUR ) ORDER BY postdate DESC LIMIT %d', Category::CAT_TV_ANIME, $this->aniqty));


		// process the resulting set
		$this->processAnAnimeRelease($results);
	}

	// process a single Anime Release based on teh release ID, such a realtime
	public function processSingleAnime($releaseid)
	{
		$db = $this->db;
		// get full information on a single release
		$results = $db->query(sprintf('SELECT searchname, id FROM releases WHERE id = %d', $releaseid));

		// process the resulting set in this case 1
		$this->processAnAnimeRelease($results);
	}

}
