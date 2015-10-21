<?php
namespace nzedb\processing\tv;

use nzedb\db\Settings;
use nzedb\processing\Videos;

/**
 * Class TV
 */
abstract class TV extends Videos
{
	// Television Sources
	const SOURCE_NONE    = 0; // No Scrape source
	const SOURCE_TVDB    = 1; // Scrape source was TVDB
	const SOURCE_TRAKT   = 2; // Scrape source was TraktTV
	const SOURCE_TVRAGE  = 3; // Scrape source was TvRage
	const SOURCE_TVMAZE  = 4; // Scrape source was TVMAZE
	const SOURCE_IMDB    = 5; // Scrape source was IMDB
	const SOURCE_TMDB    = 6; // Scrape source was TMDB

	// Anime Sources
	const SOURCE_ANIDB   = 10; // Scrape source was AniDB

	// Processing signifiers
	const PROCESS_TVDB   =  0; // Process TVDB First
	const PROCESS_TRAKT  = -1; // Process Trakt Second
	const PROCESS_TVRAGE = -2; // Process TvRage Third
	const PROCESS_TVMAZE = -3; // Process TVMaze Fourth
	const PROCESS_IMDB   = -4; // Process IMDB Fifth
	const PROCESS_TMDB   = -5; // Process TMDB Sixth
	const NO_MATCH_FOUND = -6; // Failed All Methods

	// Video Type Identifiers
	const TYPE_TV        =  0; // Type of video is a TV Show
	const TYPE_FILM      =  1; // Type of video is a TV Show
	const TYPE_ANIME     =  2; // Type of video is a TV Show

	/**
	 * @var bool
	 */
	public $echooutput;

	/**
	 * @var int
	 */
	public $rageqty;

	/**
	 * @param array $options Class instances / Echo to CLI.
	 */
	public function __construct(array $options = [])
	{
		parent::__construct($options);
		$defaults = [
			'Echo'     => false,
			'Settings' => null,
		];
		$options += $defaults;

		$this->echooutput = ($options['Echo'] && nZEDb_ECHOCLI);
		$this->catWhere = 'categoryid BETWEEN 5000 AND 5999 AND categoryid NOT IN (5070)';
		$this->tvqty = ($this->pdo->getSetting('maxrageprocessed') != '') ? $this->pdo->getSetting('maxrageprocessed') : 75;
	}

	abstract protected function getBanner($videoID, $siteId);

	/**
	 * Retrieve info of TV episode from site using its API.
	 *
	 * @param integer	$siteId
	 * @param integer	$series
	 * @param integer	$episode
	 *
	 * @return array|false    False on failure, an array of information fields otherwise.
	 */
	abstract protected function getEpisodeInfo($siteId, $series, $episode);

	/**
	 * Retrieve poster image for TV episode from site using its API.
	 *
	 * @param integer $videoId ID from videos table.
	 * @param integer $siteId  ID that this site uses for the programme.
	 *
	 * @return null
	 */
	abstract protected function getPoster($videoId, $siteId);

	/**
	 * Retrieve info of TV programme from site using it's API.
	 *
	 * @param string $name		Title of programme to look up. Usually a cleaned up version from releases table.
	 *
	 * @return array|false	False on failure, an array of information fields otherwise.
	 */
	abstract protected function getShowInfo($name);

	/**
	 * @param string $groupID
	 * @param string $guidChar
	 * @param int    $lookupSetting
	 * @param int    $status
	 *
	 * @return bool|int|\PDOStatement
	 */
	public function getTvReleases($groupID = '', $guidChar = '', $lookupSetting = 1, $status = 0)
	{
		$ret = 0;
		if ($lookupSetting == 0) {
			return $ret;
		}

		$res = $this->pdo->queryDirect(
			sprintf("
				SELECT SQL_NO_CACHE r.searchname, r.id
				FROM releases r
				WHERE r.nzbstatus = 1
				AND r.tv_episodes_id = %d
				AND r.size > 1048576
				AND %s
				%s %s %s
				ORDER BY r.postdate DESC
				LIMIT %d",
				$status,
				$this->catWhere,
				($groupID === '' ? '' : 'AND r.group_id = ' . $groupID),
				($guidChar === '' ? '' : 'AND r.guid ' . $this->pdo->likeString($guidChar, false, true)),
				($lookupSetting == 2 ? 'AND r.isrenamed = 1' : ''),
				$this->tvqty
			)
		);
		return $res;
	}

	/**
	 * @param     $videoId
	 * @param     $releaseId
	 * @param int $episodeId
	 */
	public function setVideoIdFound($videoId, $releaseId, $episodeId) {
		$this->pdo->queryExec(
				sprintf('
					UPDATE releases
					SET videos_id = %d, tv_episodes_id = %d
					WHERE %s
					AND id = %d',
					$videoId,
					$episodeId,
					$this->catWhere,
					$releaseId
				)
		);
	}

	/**
	 * @param $status
	 * @param $Id
	 */
	public function setVideoNotFound($status, $Id)
	{
		$this->pdo->queryExec(
					sprintf('
						UPDATE releases
						SET tv_episodes_id = %d
						WHERE %s
						AND id = %d',
						$status,
						$this->catWhere,
						$Id
					)
		);
	}

	/**
	 * @param     $column
	 * @param     $siteId
	 * @param     $title
	 * @param     $summary
	 * @param     $country
	 * @param     $started
	 * @param     $publisher
	 * @param     $source
	 *
	 * @param int $imdbId
	 *
	 * @return int
	 */
	public function add($column, $siteId, $title, $summary, $country, $started, $publisher, $source, $imdbId = 0)
	{
		if ($country !== '') {
			$country = $this->countryCode($country);
		}

		// Check if video already exists based on site info
		// if that fails be sure we're not inserting duplicates by checking the title

		$videoId = $this->getVideoIDFromSiteID($column, $siteId);

		if ($videoId === false) {
			$videoId = $this->getByTitleQuery($title);
		}

		if ($videoId === false) {
			$videoId = $this->pdo->queryInsert(
										sprintf('
											INSERT INTO videos (%s, type, title, countries_id, started, source, imdb)
											VALUES (%s, 0, %s, %s, %s, %d, %d)',
											$column,
											$siteId,
											$this->pdo->escapeString($title),
											$this->pdo->escapeString((isset($country) ? $country : '')),
											$this->pdo->escapeString($started),
											$source,
											$imdbId
										)
			);
			$this->pdo->queryInsert(
					sprintf("
						INSERT INTO tv_info (videos_id, summary, publisher)
						VALUES (%d, %s, %s)",
						$videoId,
						$this->pdo->escapeString($summary),
						$this->pdo->escapeString($publisher)
					)
			);
		} else {
			$this->update($videoId, $column, $siteId, $country, $imdbId);
		}
		return (int)$videoId;
	}

	/**
	 * @param $videoId
	 * @param $seriesNo
	 * @param $episodeNo
	 * @param $seComplete
	 * @param $title
	 * @param $firstaired
	 * @param $summary
	 *
	 * @return false|int|string
	 */
	public function addEpisode($videoId, $seriesNo, $episodeNo, $seComplete, $title, $firstaired, $summary)
	{
		$episodeId = $this->getBySeasonEp($videoId, $seriesNo, $episodeNo, $firstaired);

		if ($episodeId === false) {
			$episodeId = $this->pdo->queryInsert(
				sprintf('
						INSERT INTO tv_episodes (videos_id, series, episode, se_complete, title, firstaired, summary)
						VALUES (%d, %d, %d, %s, %s, %s, %s)',
					$videoId,
					$seriesNo,
					$episodeNo,
					$this->pdo->escapeString($seComplete),
					$this->pdo->escapeString($title),
					$this->pdo->escapeString($firstaired),
					$this->pdo->escapeString($summary)
				)
			);
		}
		return $episodeId;
	}

	// If the video already exists, update the site specific column to collect its ID for that scrape
	/**
	 * @param        $videoId
	 * @param        $column
	 * @param        $siteId
	 * @param string $country
	 * @param        $imdbId
	 */
	public function update($videoId, $column, $siteId, $country = '', $imdbId = 0)
	{
		if ($country !== '') {
			$country = $this->countryCode($country);
		}

		$this->pdo->queryExec(
				sprintf('
					UPDATE videos
					SET %s = %d, countries_id = %s, imdb = %d
					WHERE id = %d',
					$column,
					$siteId,
					$this->pdo->escapeString((isset($country) ? $country : '')),
					($imdbId > 0 ? $imdbId : 0),
					$videoId
				)
		);
	}

	/**
	 * @param $id
	 *
	 * @return bool|\PDOStatement
	 */
	public function delete($id)
	{
		return $this->pdo->queryExec(
				sprintf("
					DELETE v, tvi, tve
					FROM videos v
					LEFT JOIN tv_info tvi ON v.id = tvi.videos_id
					LEFT JOIN tv_episodes tve ON v.id = tve.videos_id
					WHERE id = %d",
					$id
				)
		);
	}

	/**
	 * @param $videoId
	 */
	public function setCoverFound($videoId)
	{
		$this->pdo->queryExec(
			sprintf("
					UPDATE tv_info
					SET image = 1
					WHERE videos_id = %d",
					$videoId
			)
		);
	}

	/**
	 * Get videos info for a title.
	 *
	 * @param $title
	 *
	 * @return bool
	 */
	public function getByTitle($title)
	{
		// Check if we already have an entry for this show.
		$res = $this->getByTitleQuery($title);
		if (isset($res['id'])) {
			return $res['id'];
		}

		$title2 = str_replace(' and ', ' & ', $title);
		if ($title != $title2) {
			$res = $this->getByTitleQuery($title2);
			if (isset($res['id'])) {
				return $res['id'];
			}
			$pieces = explode(' ', $title2);
			$title4 = '%';
			foreach ($pieces as $piece) {
				$title4 .= str_replace(["'", "!"], "", $piece) . '%';
			}
			$res = $this->getByTitleLikeQuery($title4 . '%');
			if (isset($res['id'])) {
				return $res['id'];
			}
		}

		// Some words are spelled correctly 2 ways
		// example theatre and theater
		$title3 = str_replace('er', 're', $title);
		if ($title != $title3) {
			$res = $this->getByTitleQuery($title3);
			if (isset($res['id'])) {
				return $res['id'];
			}
			$pieces = explode(' ', $title3);
			$title4 = '%';
			foreach ($pieces as $piece) {
				$title4 .= str_replace(["'", "!"], "", $piece) . '%';
			}
			$res = $this->getByTitleLikeQuery($title4);
			if (isset($res['id'])) {
				return $res['id'];
			}
		}

		// If there was not an exact title match, look for title with missing chars
		// example release name :Zorro 1990, tvrage name Zorro (1990)
		// Only search if the title contains more than one word to prevent incorrect matches
		$pieces = explode(' ', $title);
		if (count($pieces) > 1) {
			$title4 = '%';
			foreach ($pieces as $piece) {
				$title4 .= str_replace(["'", "!"], "", $piece) . '%';
			}
			$res = $this->getByTitleLikeQuery($title4);
			if (isset($res['id'])) {
				return $res['id'];
			}
		}
		return false;
	}

	/**
	 * @param $title
	 *
	 * @return array|bool
	 */
	public function getByTitleQuery($title)
	{
		if ($title) {
			return $this->pdo->queryOneRow(
						sprintf("
							SELECT id
							FROM videos
							WHERE title = %s
							AND type = 0",
							$this->pdo->escapeString($title)
						)
			);
		}
	}

	/**
	 * @param $title
	 *
	 * @return array|bool
	 */
	public function getByTitleLikeQuery($title)
	{
		$string = '"\'"';
		if ($title) {
			return $this->pdo->queryOneRow(
						sprintf("
							SELECT id
							FROM videos
							WHERE REPLACE(REPLACE(title, %s, ''), '!', '') %s
							AND type = 0",
							$string,
							$this->pdo->likeString(rtrim($title, '%'), false, false)
						)
			);
		}
	}

	/**
	 * Get site column from a Video ID.
	 *
	 * @param string $column
	 * @param int $id
	 *
	 * @return array|bool
	 */
	public function getSiteByID($column, $id)
	{
		$videoArr = $this->pdo->queryOneRow(
						sprintf("
							SELECT %s
							FROM videos
							WHERE id = %d",
							$column,
							$id
						)
		);
		return (isset($videoArr[$column]) ? $videoArr[$column] : false);
	}

	/**
	 * @param $id
	 * @param $series
	 * @param $episode
	 *
	 * @return bool
	 */
	public function getBySeasonEp($id, $series, $episode, $airdate = '')
	{
		if ($airdate === '') {
			$queryString = sprintf('series = %d AND episode = %d', $series, $episode);
		} else {
			$queryString = sprintf('DATE(firstaired) = %s', $this->pdo->escapeString($airdate));
		}
		$episodeArr = $this->pdo->queryOneRow(
							sprintf("
								SELECT id
								FROM tv_episodes
								WHERE videos_id = %d
								AND %s",
								$id,
								$queryString
							)
		);
		return (isset($episodeArr['id']) ? $episodeArr['id'] : false);
	}

	/**
	 * Get a country code for a country name.
	 *
	 * @param string $country
	 *
	 * @return mixed
	 */
	public function countryCode($country)
	{
		if (!is_array($country) && strlen($country) > 2) {
			$code = $this->pdo->queryOneRow(
							sprintf('
								SELECT code
								FROM countries
								WHERE name = %s',
								$this->pdo->escapeString($country)
							)
			);
			if (isset($code['code'])) {
				return $code['code'];
			}
		}
		return '';
	}

	/**
	 * @param $videoID
	 *
	 * @return array|bool
	 */
	public function checkIfNoEpisodes($videoId)
	{
		$count = $this->pdo->queryOneRow(
					sprintf('
						SELECT count(id) AS num
						FROM tv_episodes
						WHERE videos_id = %d',
						$videoId
					)
		);
		return (isset($count['num']) && (int)$count['num'] > 0 ? true : false);
	}

	/**
	 * @param $str
	 *
	 * @return string
	 */
	public function cleanName($str)
	{
		$str = str_replace(['.', '_'], ' ', $str);

		$str = str_replace(['à', 'á', 'â', 'ã', 'ä', 'æ', 'À', 'Á', 'Â', 'Ã', 'Ä'], 'a', $str);
		$str = str_replace(['ç', 'Ç'], 'c', $str);
		$str = str_replace(['Σ', 'è', 'é', 'ê', 'ë', 'È', 'É', 'Ê', 'Ë'], 'e', $str);
		$str = str_replace(['ì', 'í', 'î', 'ï', 'Ì', 'Í', 'Î', 'Ï'], 'i', $str);
		$str = str_replace(['ò', 'ó', 'ô', 'õ', 'ö', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö'], 'o', $str);
		$str = str_replace(['ù', 'ú', 'û', 'ü', 'ū', 'Ú', 'Û', 'Ü', 'Ū'], 'u', $str);
		$str = str_replace('ß', 'ss', $str);

		$str = str_replace('&', 'and', $str);
		$str = preg_replace('/^(history|discovery) channel/i', '', $str);
		$str = str_replace(['\'', ':', '!', '"', '#', '*', '’', ',', '(', ')', '?'], '', $str);
		$str = str_replace('$', 's', $str);
		$str = preg_replace('/\s{2,}/', ' ', $str);

		$str = trim($str, '\"');
		return trim($str);
	}

	/**
	 * @param $relname
	 *
	 * @return array|bool
	 */
	public function parseNameEpSeason($relname)
	{
		$showInfo = ['name' => '', 'season' => '', 'episode' => '', 'seriesfull' => '', 'airdate' => '', 'country' => '', 'year' => '', 'cleanname' => ''];
		$matches = '';

		$following = 	'[^a-z0-9](\d\d-\d\d|\d{1,2}x\d{2,3}|(19|20)\d\d|(480|720|1080)[ip]|AAC2?|BDRip|BluRay|D0?\d' .
				'|DD5|DiVX|DLMux|DTS|DVD(Rip)?|E\d{2,3}|[HX][-_. ]?264|ITA(-ENG)?|[HPS]DTV|PROPER|REPACK|Season|Episode|' .
				'S\d+[^a-z0-9]?(E\d+)?|WEB[-_. ]?(DL|Rip)|XViD)[^a-z0-9]';

		// For names that don't start with the title.
		if (preg_match('/[^a-z0-9]{2,}(?P<name>[\w .-]*?)' . $following . '/i', $relname, $matches)) {
			$showInfo['name'] = $matches[1];
		} else if (preg_match('/^(?P<name>[a-z0-9][\w .-]*?)' . $following . '/i', $relname, $matches)) {
		// For names that start with the title.
			$showInfo['name'] = $matches[1];
		}

		if (!empty($showInfo['name'])) {
			// S01E01-E02 and S01E01-02
			if (preg_match('/^(.*?)[^a-z0-9]s(\d{1,2})[^a-z0-9]?e(\d{1,3})(?:[e-])(\d{1,3})[^a-z0-9]/i', $relname, $matches)) {
				$showInfo['season'] = intval($matches[2]);
				$showInfo['episode'] = [intval($matches[3]), intval($matches[4])];
			}
			//S01E0102 - lame no delimit numbering, regex would collide if there was ever 1000 ep season.
			else if (preg_match('/^(.*?)[^a-z0-9]s(\d{2})[^a-z0-9]?e(\d{2})(\d{2})[^a-z0-9]/i', $relname, $matches)) {
				$showInfo['season'] = intval($matches[2]);
				$showInfo['episode'] = [intval($matches[3]), intval($matches[4])];
			}
			// S01E01 and S01.E01
			else if (preg_match('/^(.*?)[^a-z0-9]s(\d{1,2})[^a-z0-9]?e(\d{1,3})[ab]?[^a-z0-9]/i', $relname, $matches)) {
				$showInfo['season'] = intval($matches[2]);
				$showInfo['episode'] = intval($matches[3]);
			}
			// S01
			else if (preg_match('/^(.*?)[^a-z0-9]s(\d{1,2})[^a-z0-9]/i', $relname, $matches)) {
				$showInfo['season'] = intval($matches[2]);
				$showInfo['episode'] = 'all';
			}
			// S01D1 and S1D1
			else if (preg_match('/^(.*?)[^a-z0-9]s(\d{1,2})[^a-z0-9]?d\d{1}[^a-z0-9]/i', $relname, $matches)) {
				$showInfo['season'] = intval($matches[2]);
				$showInfo['episode'] = 'all';
			}
			// 1x01
			else if (preg_match('/^(.*?)[^a-z0-9](\d{1,2})x(\d{1,3})[^a-z0-9]/i', $relname, $matches)) {
				$showInfo['season'] = intval($matches[2]);
				$showInfo['episode'] = intval($matches[3]);
			}
			// 2009.01.01 and 2009-01-01
			else if (preg_match('/^(.*?)[^a-z0-9](?P<airdate>(19|20)(\d{2})[.\/-](\d{2})[.\/-](\d{2}))[^a-z0-9]/i', $relname, $matches)) {
				$showInfo['season'] = $matches[2] . $matches[3];
				$showInfo['episode'] = $matches[4] . '/' . $matches[5];
				$showInfo['airdate'] = date('Y-m-d', strtotime(preg_replace('/[^0-9]/i', '/', $matches['airdate']))); //yyyy-mm-dd
			}
			// 01.01.2009
			else if (preg_match('/^(.*?)[^a-z0-9](\d{2})[^a-z0-9](\d{2})[^a-z0-9](19|20)(\d{2})[^a-z0-9]/i', $relname, $matches)) {
				$showInfo['season'] = $matches[4] . $matches[5];
				$showInfo['episode'] = $matches[2] . '/' . $matches[3];
				$showInfo['airdate'] = $matches[4] . $matches[5] . '-' . $matches[2] . '-' . $matches[3]; //yy-m-d
			}
			// 01.01.09
			else if (preg_match('/^(.*?)[^a-z0-9](\d{2})[^a-z0-9](\d{2})[^a-z0-9](\d{2})[^a-z0-9]/i', $relname, $matches)) {
				$showInfo['season'] = ($matches[4] <= 99 && $matches[4] > 15) ? '19' . $matches[4] : '20' . $matches[4];
				$showInfo['episode'] = $matches[2] . '/' . $matches[3];
				$showInfo['airdate'] = $showInfo['season'] . '-' . $matches[2] . '-' . $matches[3]; //yy-m-d
			}
			// 2009.E01
			else if (preg_match('/^(.*?)[^a-z0-9]20(\d{2})[^a-z0-9](\d{1,3})[^a-z0-9]/i', $relname, $matches)) {
				$showInfo['season'] = '20' . $matches[2];
				$showInfo['episode'] = intval($matches[3]);
			}
			// 2009.Part1
			else if (preg_match('/^(.*?)[^a-z0-9](19|20)(\d{2})[^a-z0-9]Part(\d{1,2})[^a-z0-9]/i', $relname, $matches)) {
				$showInfo['season'] = $matches[2] . $matches[3];
				$showInfo['episode'] = intval($matches[4]);
			}
			// Part1/Pt1
			else if (preg_match('/^(.*?)[^a-z0-9](?:Part|Pt)[^a-z0-9](\d{1,2})[^a-z0-9]/i', $relname, $matches)) {
				$showInfo['season'] = 1;
				$showInfo['episode'] = intval($matches[2]);
			}
			//The.Pacific.Pt.VI.HDTV.XviD-XII / Part.IV
			else if (preg_match('/^(.*?)[^a-z0-9](?:Part|Pt)[^a-z0-9]([ivx]+)/i', $relname, $matches)) {
				$showInfo['season'] = 1;
				$epLow = strtolower($matches[2]);
				switch ($epLow) {
					case 'i': $e = 1;
						break;
					case 'ii': $e = 2;
						break;
					case 'iii': $e = 3;
						break;
					case 'iv': $e = 4;
						break;
					case 'v': $e = 5;
						break;
					case 'vi': $e = 6;
						break;
					case 'vii': $e = 7;
						break;
					case 'viii': $e = 8;
						break;
					case 'ix': $e = 9;
						break;
					case 'x': $e = 10;
						break;
					case 'xi': $e = 11;
						break;
					case 'xii': $e = 12;
						break;
					case 'xiii': $e = 13;
						break;
					case 'xiv': $e = 14;
						break;
					case 'xv': $e = 15;
						break;
					case 'xvi': $e = 16;
						break;
					case 'xvii': $e = 17;
						break;
					case 'xviii': $e = 18;
						break;
					case 'xix': $e = 19;
						break;
					case 'xx': $e = 20;
						break;
					default:
						$e = 0;
				}
				$showInfo['episode'] = $e;
			}
			// Band.Of.Brothers.EP06.Bastogne.DVDRiP.XviD-DEiTY
			else if (preg_match('/^(.*?)[^a-z0-9]EP?[^a-z0-9]?(\d{1,3})/i', $relname, $matches)) {
				$showInfo['season'] = 1;
				$showInfo['episode'] = intval($matches[2]);
			}
			// Season.1
			else if (preg_match('/^(.*?)[^a-z0-9]Seasons?[^a-z0-9]?(\d{1,2})/i', $relname, $matches)) {
				$showInfo['season'] = intval($matches[2]);
				$showInfo['episode'] = 'all';
			}

			$countryMatch = $yearMatch = '';
			// Country or origin matching.
			if (preg_match('/\W(US|UK|AU|NZ|CA|NL|Canada|Australia|America|United[^a-z0-9]States|United[^a-z0-9]Kingdom)\W/', $showInfo['name'], $countryMatch)) {
				$currentCountry = strtolower($countryMatch[1]);
				if ($currentCountry == 'canada') {
					$showInfo['country'] = 'CA';
				} else if ($currentCountry == 'australia') {
					$showInfo['country'] = 'AU';
				} else if ($currentCountry == 'america' || $currentCountry == 'united states') {
					$showInfo['country'] = 'US';
				} else if ($currentCountry == 'united kingdom') {
					$showInfo['country'] = 'UK';
				} else {
					$showInfo['country'] = strtoupper($countryMatch[1]);
				}
			}

			// Clean show name.
			$showInfo['cleanname'] = preg_replace('/ - \d{1,}$/i', '', $this->cleanName($showInfo['name']));

			// Check for dates instead of seasons.
			if (strlen($showInfo['season']) == 4) {
				$showInfo['seriesfull'] = $showInfo['season'] . "/" . $showInfo['episode'];
			} else {
				// Get year if present (not for releases with dates as seasons).
				if (preg_match('/[^a-z0-9](19|20)(\d{2})/i', $relname, $yearMatch)) {
					$showInfo['year'] = $yearMatch[1] . $yearMatch[2];
				}

				$showInfo['season'] = sprintf('S%02d', $showInfo['season']);
				// Check for multi episode release.
				if (is_array($showInfo['episode'])) {
					$tmpArr = [];
					foreach ($showInfo['episode'] as $ep) {
						$tmpArr[] = sprintf('E%02d', $ep);
					}
					$showInfo['episode'] = implode('', $tmpArr);
				} else {
					$showInfo['episode'] = sprintf('E%02d', $showInfo['episode']);
				}

				$showInfo['seriesfull'] = $showInfo['season'] . $showInfo['episode'];
			}
			$showInfo['airdate'] = (!empty($showInfo['airdate']) ? $showInfo['airdate'] : '');
			return $showInfo;
		}
		return false;
	}

	/**
	 * @param $ourName
	 * @param $tvrName
	 *
	 * @return bool|float
	 */
	public function checkMatch($ourName, $tvrName)
	{
		// Clean up name ($ourName is already clean).
		$tvrName = $this->cleanName($tvrName);
		$tvrName = preg_replace('/ of /i', '', $tvrName);
		$ourName = preg_replace('/ of /i', '', $ourName);

		// Create our arrays.
		$ourArr = explode(' ', $ourName);
		$tvrArr = explode(' ', $tvrName);

		// Set our match counts.
		$numMatches = 0;
		$totalMatches = sizeof($ourArr) + sizeof($tvrArr);

		// Loop through each array matching again the opposite value, if they match increment!
		foreach ($ourArr as $oname) {
			if (preg_match('/ ' . preg_quote($oname, '/') . ' /i', ' ' . $tvrName . ' ')) {
				$numMatches++;
			}
		}
		foreach ($tvrArr as $tname) {
			if (preg_match('/ ' . preg_quote($tname, '/') . ' /i', ' ' . $ourName . ' ')) {
				$numMatches++;
			}
		}

		// Check what we're left with.
		if ($numMatches <= 0) {
			return false;
		} else {
			$matchpct = ($numMatches / $totalMatches) * 100;
		}

		if ($matchpct >= TvRage::MATCH_PROBABILITY) {
			return $matchpct;
		} else {
			return false;
		}
	}

	//
	/**
	 * Convert 2012-24-07 to 2012-07-24, there is probably a better way
	 *
	 * This shouldn't ever happen as I've never heard of a date starting with year being followed by day value.
	 * Could this be a mistake? i.e. trying to solve the mm-dd-yyyy/dd-mm-yyyy confusion into a yyyy-mm-dd?
	 *
	 * @param string $date
	 *
	 * @return string
	 */
	public function checkDate($date)
	{
		if (!empty($date)) {
			$chk = explode(" ", $date);
			$chkd = explode("-", $chk[0]);
			if ($chkd[1] > 12) {
				$date = date('Y-m-d H:i:s', strtotime($chkd[1] . " " . $chkd[2] . " " . $chkd[0]));
			}
		} else {
			$date = null;
		}
		return $date;
	}
}
