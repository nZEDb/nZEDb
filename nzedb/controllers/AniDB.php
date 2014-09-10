<?php
require_once nZEDb_LIB . 'utility' . DS . 'Utility.php';

use nzedb\db\Settings;

class AniDB
{
	/**
	 * @var nzedb\db\Settings
	 */
	public $pdo;

	/**
	 * @param array $options Class instances / Echo to cli.
	 */
	public function __construct(array $options = array())
	{
		$defaults = [
			'Echo'     => false,
			'Settings' => null,
		];
		$options += $defaults;

		$this->echooutput = ($options['Echo'] && nZEDb_ECHOCLI);
		$this->pdo = ($options['Settings'] instanceof Settings ? $options['Settings'] : new Settings());

		$qty = $this->pdo->getSetting('maxanidbprocessed');
		$this->aniqty = !empty($qty) ? $qty : 100;
		$this->imgSavePath = nZEDb_COVERS . 'anime' . DS;
	}

	public function animetitlesUpdate()
	{
		// this should not be run as it should be handled by populate_anidb
		if ($this->echooutput) {
			$this->pdo->log->doEcho("Skipped update aniTitles as it is handled by populate_anidb in misc/testing/DB", true);
		}
		return;
	}

	public function addTitle($AniDBAPIArray)
	{
		$pdo = $this->pdo;
		$pdo->queryInsert(sprintf("INSERT INTO anidb VALUES (%d, 0, 0, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d)", $AniDBAPIArray['anidbid'], $pdo->escapeString($AniDBAPIArray['title']), $pdo->escapeString($AniDBAPIArray['type']), $pdo->escapeString($AniDBAPIArray['startdate']), $pdo->escapeString($AniDBAPIArray['enddate']), $pdo->escapeString($AniDBAPIArray['related']), $pdo->escapeString($AniDBAPIArray['creators']), $pdo->escapeString($AniDBAPIArray['description']), $pdo->escapeString($AniDBAPIArray['rating']), $pdo->escapeString($AniDBAPIArray['picture']), $pdo->escapeString($AniDBAPIArray['categories']), $pdo->escapeString($AniDBAPIArray['characters']), $pdo->escapeString($AniDBAPIArray['epnos']), $pdo->escapeString($AniDBAPIArray['airdates']), $pdo->escapeString($AniDBAPIArray['episodetitles']), time()));
	}

	public function updateTitle($anidbID, $title, $type, $startdate, $enddate, $related, $creators, $description, $rating, $categories, $characters, $epnos, $airdates, $episodetitles)
	{
		$pdo = $this->pdo;
		$pdo->queryExec(sprintf('UPDATE anidb SET title = %s, type = %s, startdate = %s, enddate = %s, related = %s, creators = %s, description = %s, rating = %s, categories = %s, characters = %s, epnos = %s, airdates = %s, episodetitles = %s, unixtime = %d WHERE anidbid = %d', $pdo->escapeString($title), $pdo->escapeString($type), $pdo->escapeString($startdate), $pdo->escapeString($enddate), $pdo->escapeString($related), $pdo->escapeString($creators), $pdo->escapeString($description), $pdo->escapeString($rating), $pdo->escapeString($categories), $pdo->escapeString($characters), $pdo->escapeString($epnos), $pdo->escapeString($airdates), $pdo->escapeString($episodetitles), $anidbID, time()));
	}

	public function deleteTitle($anidbID)
	{
		$pdo = $this->pdo;
		$pdo->queryExec(sprintf('DELETE FROM anidb WHERE anidbid = %d', $anidbID));
	}

	public function getanidbID($title)
	{
		$pdo = $this->pdo;
		$query = sprintf('SELECT anidbid as anidbid FROM animetitles WHERE title REGEXP %s LIMIT 1', $pdo->escapeString('^' . $title . '$'));
		$anidbID = $pdo->queryOneRow($query);

		// if the first query failed try it again using like as we have a change for a match
		if ($anidbID == False) {
			$query = sprintf('SELECT anidbid as anidbid FROM animetitles WHERE title LIKE %s LIMIT 1', $pdo->escapeString('%' . $title . '%'));
			$anidbID = $pdo->queryOneRow($query);
		}

		return $anidbID['anidbid'];
	}

	public function getAnimeList($letter = '', $animetitle = '')
	{
		$pdo = $this->pdo;

		$regex = 'REGEXP';
		$like = 'LIKE';

		$rsql = '';
		if ($letter != '') {
			if ($letter == '0-9')
				$letter = '[0-9]';

			$rsql .= sprintf('AND anidb.title %s %s', $regex, $pdo->escapeString('^' . $letter));
		}

		$tsql = '';
		if ($animetitle != '')
			$tsql .= sprintf('AND anidb.title %s %s', $like, $pdo->escapeString('%' . $animetitle . '%'));

		return $pdo->query(sprintf('SELECT anidb.anidbid, anidb.title, anidb.type, anidb.categories, anidb.rating, anidb.startdate, anidb.enddate FROM anidb WHERE anidb.anidbid > 0 %s %s GROUP BY anidb.anidbid ORDER BY anidb.title ASC', $rsql, $tsql));
	}

	public function getAnimeRange($start, $num, $animetitle = '')
	{
		$pdo = $this->pdo;

		if ($start === false)
			$limit = '';
		else
			$limit = ' LIMIT ' . $num . ' OFFSET ' . $start;

		$rsql = '';
		if ($animetitle != '') {
			$rsql = sprintf('AND anidb.title LIKE %s', $pdo->escapeString('%' . $animetitle . '%'));
		}

		return $pdo->query(sprintf('SELECT anidbid, title, description FROM anidb WHERE 1=1 %s ORDER BY anidbid ASC' . $limit, $rsql));
	}

	public function getAnimeCount($animetitle = '')
	{
		$pdo = $this->pdo;

		$rsql = '';
		if ($animetitle != '') {
			$rsql .= sprintf('AND anidb.title LIKE %s', $pdo->escapeString('%' . $animetitle . '%'));
		}

		$res = $pdo->queryOneRow(sprintf('SELECT COUNT(anidbid) AS num FROM anidb WHERE 1=1 %s', $rsql));

		return $res['num'];
	}

	public function getAnimeInfo($anidbID)
	{
		$pdo = $this->pdo;
		$animeInfo = $pdo->query(sprintf('SELECT * FROM anidb WHERE anidbid = %d', $anidbID));

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
				$this->pdo->log->doEcho("\tFalling back to Pure REGEX method to determine name.", true);
			}

			// if no "'s were found then fall back to cleanFilename;
			return $this->cleanFilename($searchname);
		}
	}

	// determine if given an ID it is ANIME or not, this should be moved to postprocess to be cleaner but for now as to not touch another file leave it here
	public function checkIfAnime($releaseid)
	{
		$pdo = $this->pdo;
		$result = $pdo->query(sprintf('SELECT categoryid FROM releases WHERE id = %d', $releaseid));

		if (isset($result[0]['categoryid']) && $result[0]['categoryid'] == "5070")
			return True;
		else
			return False;
	}

	function processAnAnimeRelease($results)
	{
		$pdo = $this->pdo;
		$ri = new \ReleaseImage($this->pdo);

		if (count($results) > 0) {
			if ($this->echooutput) {
				$this->pdo->log->doEcho('Processing ' . count($results) . " anime releases.", true);
			}

			$sphinx = new \SphinxSearch();
			foreach ($results as $arr) {

				// clean up the release name to ensure we get a good chance at getting a valid filename
				$cleanFilename = $this->cleanFilename($arr['searchname']);

				// Get a release name to update the DB with, this is more than the title as it includes group size ... or worst case the same as the title
				$getReleaseName = $this->getReleaseName($arr['searchname']);

				if ($this->echooutput) {
					$this->pdo->log->doEcho("\tProcessing Anime entitled: " . $getReleaseName['title'], true);
				}

				// get anidb number for the title of the naime
				$anidbID = $this->getanidbID($cleanFilename['title']);
				if (!$anidbID) {
					$newTitle = $pdo->escapeString($getReleaseName['title']);
					// no anidb ID found so set what we know and exit
					$pdo->queryExec(sprintf('UPDATE releases SET searchname = %s, anidbid = %d, rageid = %d WHERE id = %d', $newTitle, -1, -2, $arr['id']));
					$sphinx->updateReleaseSearchName($newTitle, $arr['id']);
					continue;
				}

				if ($this->echooutput) {
					$this->pdo->log->doEcho('Looking up: ' . $arr['searchname'], true);
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
						$this->pdo->log->doEcho('- found ' . $AniDBAPIArray['anidbid'], true);
					}

					// lastly update the information, we also want a better readable name, AKA search name so we can use the title we cleaned
					$newTitle = $pdo->escapeString($getReleaseName['title']);
					$pdo->queryExec(sprintf('UPDATE releases SET searchname = %s, episode = %s, tvtitle = %s, tvairdate = %s, anidbid = %d, rageid = %d WHERE id = %d', $newTitle, $pdo->escapeString($cleanFilename['epno']), $pdo->escapeString($tvtitle), $pdo->escapeString($airdate), $AniDBAPIArray['anidbid'], -2, $arr['id']));
					$sphinx->updateReleaseSearchName($newTitle, $arr['id']);
				}
				else {
					// if the anime was not found, just simply update the search name
					$newTitle = $pdo->escapeString($getReleaseName['title']);
					$sphinx->updateReleaseSearchName($newTitle, $arr['id']);
					$pdo->queryExec(sprintf('UPDATE releases SET searchname = %s, anidbid = %d WHERE id = %d', $newTitle, $AniDBAPIArray['anidbid'], $arr['id']));
				}
			} // foreach

			if ($this->echooutput) {
				$this->pdo->log->doEcho('Processed ' . count($results) . " anime releases.", true);
			}
		} // if
		else {
			if ($this->echooutput) {
				$this->pdo->log->doEcho($this->pdo->log->header('No anime releases to process.'));
			}
		}
	}

	// process a group of previously unprcoessed Anime Releases, as in postprocess
	public function processAnimeReleases($hours = 0)
	{
		$pdo = $this->pdo;
		if ($hours == 0)
			$results = $pdo->query(sprintf('SELECT searchname, id FROM releases WHERE nzbstatus = 1 AND anidbid IS NULL AND categoryid IN (SELECT id FROM category WHERE categoryid = %d) ORDER BY postdate DESC LIMIT %d', \Category::CAT_TV_ANIME, $this->aniqty));
		else
		// only select items within 6 hours
			$results = $pdo->query(sprintf('SELECT searchname, id FROM releases WHERE nzbstatus = 1 AND anidbid IS NULL AND categoryid IN (SELECT id FROM category WHERE categoryid = %d) adddate > ( NOW( ) - INTERVAL 6 HOUR ) ORDER BY postdate DESC LIMIT %d', \Category::CAT_TV_ANIME, $this->aniqty));


		// process the resulting set
		$this->processAnAnimeRelease($results);
	}

	// process a single Anime Release based on teh release ID, such a realtime
	public function processSingleAnime($releaseid)
	{
		$pdo = $this->pdo;
		// get full information on a single release
		$results = $pdo->query(sprintf('SELECT searchname, id FROM releases WHERE id = %d', $releaseid));

		// process the resulting set in this case 1
		$this->processAnAnimeRelease($results);
	}

}
