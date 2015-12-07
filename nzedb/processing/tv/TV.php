<?php
namespace nzedb\processing\tv;

use nzedb\processing\Videos;
use nzedb\utility\Country;
use nzedb\utility\Text;

/**
 * Class TV -- abstract extension of Videos
 * Contains functions suitable for re-use in all TV scrapers
 */
abstract class TV extends Videos
{
	// Television Sources
	const SOURCE_NONE    = 0;   // No Scrape source
	const SOURCE_TVDB    = 1;   // Scrape source was TVDB
	const SOURCE_TVMAZE  = 2;   // Scrape source was TVMAZE
	const SOURCE_TMDB    = 3;   // Scrape source was TMDB
	const SOURCE_TRAKT   = 4;   // Scrape source was Trakt
	const SOURCE_IMDB    = 5;   // Scrape source was IMDB
	const SOURCE_TVRAGE  = 6;   // Scrape source was TvRage

	// Anime Sources
	const SOURCE_ANIDB   = 10;   // Scrape source was AniDB

	// Processing signifiers
	const PROCESS_TVDB   =  0;   // Process TVDB First
	const PROCESS_TVMAZE = -1;   // Process TVMaze Second
	const PROCESS_TMDB   = -2;   // Process TMDB Third
	const PROCESS_TRAKT  = -3;   // Process Trakt Fourth
	const PROCESS_IMDB   = -4;   // Process IMDB Fifth
	const PROCESS_TVRAGE = -5;   // Process TvRage Sixth
	const NO_MATCH_FOUND = -6;   // Failed All Methods
	const FAILED_PARSE   = -100; // Failed Parsing

	/**
	 * @var int
	 */
	public $tvqty;

	/**
	 * @string Path to Save Images
	 */
	public $imgSavePath;

	/**
	 * @var array Site ID columns for TV
	 */
	public $siteColumns;

	/**
	 * @param array $options Class instances / Echo to CLI.
	 */
	public function __construct(array $options = [])
	{
		parent::__construct($options);
		$this->catWhere = 'categoryid BETWEEN 5000 AND 5999 AND categoryid NOT IN (5070)';
		$this->tvqty = ($this->pdo->getSetting('maxrageprocessed') != '') ? $this->pdo->getSetting('maxrageprocessed') : 75;
		$this->imgSavePath = nZEDb_COVERS . 'tvshows' . DS;
		$this->siteColumns = ['tvdb', 'trakt', 'tvrage', 'tvmaze', 'imdb', 'tmdb'];
	}

	/**
	 * Retrieve banner image from site using its API.
	 *
	 * @param $videoID
	 * @param $siteId
	 *
	 * @return mixed
	 */
	abstract protected function getBanner($videoID, $siteId);

	/**
	 * Retrieve info of TV episode from site using its API.
	 *
	 * @param integer $siteId
	 * @param integer $series
	 * @param integer $episode
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
	 * @param string $name Title of programme to look up. Usually a cleaned up version from releases table.
	 *
	 * @return array|false    False on failure, an array of information fields otherwise.
	 */
	abstract protected function getShowInfo($name);

	/**
	 * Assigns API show response values to a formatted array for insertion
	 * Returns the formatted array
	 *
	 * @param $show
	 *
	 * @return array
	 */
	abstract protected function formatShowInfo($show);

	/**
	 * Assigns API episode response values to a formatted array for insertion
	 * Returns the formatted array
	 *
	 * @param $episode
	 *
	 * @return array
	 */
	abstract protected function formatEpisodeInfo($episode);

		/**
	 * Retrieve releases for TV processing
	 * Returns a PDO Object of rows or false if none found
	 *
	 * @param string $groupID -- ID of the usenet group to process
	 * @param string $guidChar -- threading method by first guid character
	 * @param int    $lookupSetting -- whether or not to use the API
	 * @param int    $status -- release processing status of tv_episodes_id
	 *
	 * @return false|int|\PDOStatement
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
				AND r.videos_id = 0
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
	 * Updates the release when match for the current scraper is found
	 *
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
	 * Updates the release tv_episodes_id status when scraper match is not found
	 *
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
	 * Inserts a new video ID into the database for TV shows
	 * If a duplicate is found it is handle by calling update instead
	 *
	 * @param array $show
	 *
	 * @return int
	 */
	public function add(array $show = [])
	{
		$videoId = false;

		// Check if the country is not a proper code and retrieve if not
		if ($show['country'] !== '' && strlen($show['country']) > 2) {
			$show['country'] = Country::countryCode($show['country'], $this->pdo);
		}

		// Check if video already exists based on site ID info
		// if that fails be sure we're not inserting duplicates by checking the title
		foreach ($this->siteColumns AS $column) {
			if ($show[$column] > 0) {
				$videoId = $this->getVideoIDFromSiteID($column, $show[$column]);
			}
			if ($videoId !== false) {
				break;
			}
		}

		if ($videoId === false) {
			// Insert the Show
			$videoId = $this->pdo->queryInsert(
				sprintf('
					INSERT INTO videos
					(type, title, countries_id, started, source, tvdb, trakt, tvrage, tvmaze, imdb, tmdb)
					VALUES (%d, %s, %s, %s, %d, %d, %d, %d, %d, %d, %d)',
					$show['type'],
					$this->pdo->escapeString($show['title']),
					$this->pdo->escapeString((isset($show['country']) ? $show['country'] : '')),
					$this->pdo->escapeString($show['started']),
					$show['source'],
					$show['tvdb'],
					$show['trakt'],
					$show['tvrage'],
					$show['tvmaze'],
					$show['imdb'],
					$show['tmdb']
				)
			);
			// Insert the supplementary show info
			$this->pdo->queryInsert(
				sprintf("
					INSERT INTO tv_info (videos_id, summary, publisher, localzone)
					VALUES (%d, %s, %s, %s)",
					$videoId,
					$this->pdo->escapeString($show['summary']),
					$this->pdo->escapeString($show['publisher']),
					$this->pdo->escapeString($show['localzone'])
				)
			);
			// If we have AKAs\aliases, insert those as well
			if (!empty($show['aliases'])) {
				$this->addAliases($videoId, $show['aliases']);
			}
		} else {
			// If a local match was found, just update missing video info
			$this->update($videoId, $show);
		}
		return (int)$videoId;
	}

	/**
	 * Inserts a new TV episode into the tv_episodes table following a match to a Video ID
	 *
	 * @param int   $videoId
	 * @param array $episode
	 *
	 * @return false|int|string
	 */
	public function addEpisode($videoId, array $episode = [])
	{
		$episodeId = $this->getBySeasonEp($videoId, $episode['series'], $episode['episode'], $episode['firstaired']);

		if ($episodeId === false) {
			$episodeId = $this->pdo->queryInsert(
				sprintf('
					INSERT INTO tv_episodes (videos_id, series, episode, se_complete, title, firstaired, summary)
					VALUES (%d, %d, %d, %s, %s, %s, %s)
					ON DUPLICATE KEY update se_complete = %s',
					$videoId,
					$episode['series'],
					$episode['episode'],
					$this->pdo->escapeString($episode['se_complete']),
					$this->pdo->escapeString($episode['title']),
					($episode['firstaired'] != "" ? $this->pdo->escapeString($episode['firstaired']) : "null"),
					$this->pdo->escapeString($episode['summary']),
					$this->pdo->escapeString($episode['se_complete'])
				)
			);
		}
		return $episodeId;
	}

	/**
	 * Updates the show info with data from the supplied array
	 * Only called when a duplicate show is found during insert
	 *
	 * @param int   $videoId
	 * @param array $show
	 */
	public function update($videoId, array $show = [])
	{
		if ($show['country'] !== '') {
			$show['country'] = Country::countryCode($show['country'], $this->pdo);
		}

		$ifStringID = 'IF(%s = 0, %s, %s)';
		$ifStringInfo = "IF(%s = '', %s, %s)";

		$this->pdo->queryExec(
			sprintf('
				UPDATE videos v
				LEFT JOIN tv_info tvi ON v.id = tvi.videos_id
				SET v.countries_id = %s, v.tvdb = %s, v.trakt = %s, v.tvrage = %s,
					v.tvmaze = %s, v.imdb = %s, v.tmdb = %s,
					tvi.summary = %s, tvi.publisher = %s, tvi.localzone = %s
				WHERE v.id = %d',
				sprintf($ifStringInfo, 'v.countries_id', $this->pdo->escapeString($show['country']), 'v.countries_id'),
				sprintf($ifStringID, 'v.tvdb', $show['tvdb'], 'v.tvdb'),
				sprintf($ifStringID, 'v.trakt', $show['trakt'], 'v.trakt'),
				sprintf($ifStringID, 'v.tvrage', $show['tvrage'], 'v.tvrage'),
				sprintf($ifStringID, 'v.tvmaze', $show['tvmaze'], 'v.tvmaze'),
				sprintf($ifStringID, 'v.imdb', $show['imdb'], 'v.imdb'),
				sprintf($ifStringID, 'v.tmdb', $show['tmdb'], 'v.tmdb'),
				sprintf($ifStringInfo, 'tvi.summary', $this->pdo->escapeString($show['summary']), 'tvi.summary'),
				sprintf($ifStringInfo, 'tvi.publisher', $this->pdo->escapeString($show['publisher']), 'tvi.publisher'),
				sprintf($ifStringInfo, 'tvi.localzone', $this->pdo->escapeString($show['localzone']), 'tvi.localzone'),
				$videoId
			)
		);
		if (!empty($show['aliases'])) {
			$this->addAliases($videoId, $show['aliases']);
		}
	}

	/**
	 * Deletes a TV show entirely from all child tables via the Video ID
	 *
	 * @param $id
	 *
	 * @return \PDOStatement|false
	 */
	public function delete($id)
	{
		return $this->pdo->queryExec(
			sprintf("
				DELETE v, tvi, tve, va
				FROM videos v
				LEFT JOIN tv_info tvi ON v.id = tvi.videos_id
				LEFT JOIN tv_episodes tve ON v.id = tve.videos_id
				LEFT JOIN videos_aliases va ON v.id = va.videos_id
				WHERE v.id = %d",
				$id
			)
		);
	}

	/**
	 * Sets the TV show's image column to found (1)
	 *
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
	 * Get site ID from a Video ID and the site's respective column.
	 * Returns the ID value or false if none found
	 *
	 * @param string $column
	 * @param int $id
	 *
	 * @return \PDOStatement|false
	 */
	public function getSiteByID($column, $id)
	{
		$return = false;
		$video = $this->pdo->queryOneRow(
			sprintf("
				SELECT %s
				FROM videos
				WHERE id = %d",
				$column,
				$id
			)
		);
		if ($column === '*') {
			$return = $video;
		} else if ($column !== '*' && isset($video[$column])) {
			$return = $video[$column];
		}
		return $return;
	}

	/**
	 * Retrieves the Episode ID using the Video ID and either:
	 * season/episode numbers OR the airdate
	 *
	 * Returns the Episode ID or false if not found
	 *
	 * @param        $id
	 * @param        $series
	 * @param        $episode
	 * @param string $airdate
	 *
	 * @return int|false
	 */
	public function getBySeasonEp($id, $series, $episode, $airdate = '')
	{
		if ($episode > 0) {
			$queryString = sprintf('series = %d AND episode = %d', $series, $episode);
		} else if (!empty($airdate) && $airdate !== '') {
			$queryString = sprintf('DATE(firstaired) = %s', $this->pdo->escapeString(date('Y-m-d', strtotime($airdate))));
		} else {
			return false;
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
	 * Returns (true) if episodes for a given Video ID exist or don't (false)
	 *
	 * @param $videoId
	 *
	 * @return bool
	 */
	public function countEpsByVideoID($videoId)
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
	 * Parses a release searchname for specific TV show data
	 * Returns an array of show data
	 *
	 * @param $relname
	 *
	 * @return array|false
	 */
	public function parseInfo($relname)
	{
		$showInfo['name'] = $this->parseName($relname);

		if (!empty($showInfo['name'])) {

			// Retrieve the country from the cleaned name
			$showInfo['country'] = $this->parseCountry($showInfo['name']);

			// Clean show name.
			$showInfo['cleanname'] = preg_replace('/ - \d{1,}$/i', '', $this->cleanName($showInfo['name']));

			// Get the Season/Episode/Airdate
			$showInfo += $this->parseSeasonEp($relname);

			if ((isset($showInfo['season']) && isset($showInfo['episode'])) || isset($showInfo['airdate'])) {
				if (!isset($showInfo['airdate'])) {
					// If year is present in the release name, add it to the cleaned name for title search
					if (preg_match('/[^a-z0-9](?P<year>(19|20)(\d{2}))[^a-z0-9]/i', $relname, $yearMatch)) {
						$showInfo['cleanname'] .= ' (' . $yearMatch['year'] . ')';
					}
					// Check for multi episode release.
					if (is_array($showInfo['episode'])) {
						$showInfo['episode'] = $showInfo['episode'][0];
					}
					$showInfo['airdate'] = '';
				}

				return $showInfo;
			}
		}
		if (nZEDb_DEBUG) {
			$this->pdo->log->doEcho('Failed to parse release: ' . $relname, true);
		}
		return false;
	}

	/**
	 * Parses the release searchname and returns a show title
	 *
	 * @param string $relname
	 *
	 * @return string
	 */
	private function parseName($relname)
	{
		$showName = '';

		$following = '[^a-z0-9](\d\d-\d\d|\d{1,3}x\d{2,3}|\(?(19|20)\d{2}\)?|(480|720|1080)[ip]|AAC2?|BD-?Rip|Blu-?Ray|D0?\d' .
				'|DD5|DiVX|DLMux|DTS|DVD(-?Rip)?|E\d{2,3}|[HX][-_. ]?26[45]|ITA(-ENG)?|HEVC|[HPS]DTV|PROPER|REPACK|Season|Episode|' .
				'S\d+[^a-z0-9]?((E\d+)[abr]?)*|WEB[-_. ]?(DL|Rip)|XViD)[^a-z0-9]?';

		// For names that don't start with the title.
		if (preg_match('/^([^a-z0-9]{2,}|(sample|proof|repost)-)(?P<name>[\w .-]*?)' . $following . '/i', $relname, $matches)) {
			$showName = $matches['name'];
		} else if (preg_match('/^(?P<name>[a-z0-9][\w\' .-]*?)' . $following . '/i', $relname, $matches)) {
			// For names that start with the title.
			$showName = $matches['name'];
		}
		// If we still have any of the words in $following, remove them.
		$showName = preg_replace('/' . $following . '/i', ' ', $showName);
		// Remove leading date if present
		$showName = preg_replace('/^\d{6}/', '', $showName);
		// Remove periods, underscored, anything between parenthesis.
		$showName = preg_replace('/\(.*?\)|[._]/i', ' ', $showName);
		// Finally remove multiple spaces and trim leading spaces.
		$showName = trim(preg_replace('/\s{2,}/', ' ', $showName));
		return $showName;
	}

	/**
	 * Parses the release searchname for the season/episode/airdate information
	 *
	 * @param $relname
	 *
	 * @return array
	 */
	private function parseSeasonEp($relname)
	{
		$episodeArr = [];

		// S01E01-E02 and S01E01-02
		if (preg_match('/^(.*?)[^a-z0-9]s(\d{1,2})[^a-z0-9]?e(\d{1,3})(?:[e-])(\d{1,3})[^a-z0-9]/i', $relname, $matches)) {
			$episodeArr['season'] = intval($matches[2]);
			$episodeArr['episode'] = [intval($matches[3]), intval($matches[4])];
		}
		//S01E0102 and S01E01E02 - lame no delimit numbering, regex would collide if there was ever 1000 ep season.
		else if (preg_match('/^(.*?)[^a-z0-9]s(\d{2})[^a-z0-9]?e(\d{2})e?(\d{2})[^a-z0-9]/i', $relname, $matches)) {
			$episodeArr['season'] = intval($matches[2]);
			$episodeArr['episode'] = intval($matches[3]);
		}
		// S01E01 and S01.E01
		else if (preg_match('/^(.*?)[^a-z0-9]s(\d{1,2})[^a-z0-9]?e(\d{1,3})[abr]?[^a-z0-9]/i', $relname, $matches)) {
			$episodeArr['season'] = intval($matches[2]);
			$episodeArr['episode'] = intval($matches[3]);
		}
		// S01
		else if (preg_match('/^(.*?)[^a-z0-9]s(\d{1,2})[^a-z0-9]/i', $relname, $matches)) {
			$episodeArr['season'] = intval($matches[2]);
			$episodeArr['episode'] = 'all';
		}
		// S01D1 and S1D1
		else if (preg_match('/^(.*?)[^a-z0-9]s(\d{1,2})[^a-z0-9]?d\d{1}[^a-z0-9]/i', $relname, $matches)) {
			$episodeArr['season'] = intval($matches[2]);
			$episodeArr['episode'] = 'all';
		}
		// 1x01 and 101
		else if (preg_match('/^(.*?)[^a-z0-9](\d{1,2})x(\d{1,3})[^a-z0-9]/i', $relname, $matches)) {
			$episodeArr['season'] = intval($matches[2]);
			$episodeArr['episode'] = intval($matches[3]);
		}
		// 2009.01.01 and 2009-01-01
		else if (preg_match('/^(.*?)[^a-z0-9](?P<airdate>(19|20)(\d{2})[.\/-](\d{2})[.\/-](\d{2}))[^a-z0-9]/i', $relname, $matches)) {
			$episodeArr['season'] = $matches[4] . $matches[5];
			$episodeArr['episode'] = $matches[5] . '/' . $matches[6];
			$episodeArr['airdate'] = date('Y-m-d', strtotime(preg_replace('/[^0-9]/i', '/', $matches['airdate']))); //yyyy-mm-dd
		}
		// 01.01.2009
		else if (preg_match('/^(.*?)[^a-z0-9](?P<airdate>(\d{2})[^a-z0-9](\d{2})[^a-z0-9](19|20)(\d{2}))[^a-z0-9]/i', $relname, $matches)) {
			$episodeArr['season'] = $matches[5] . $matches[6];
			$episodeArr['episode'] = $matches[3] . '/' . $matches[4];
			$episodeArr['airdate'] = date('Y-m-d', strtotime(preg_replace('/[^0-9]/i', '/', $matches['airdate']))); //yyyy-mm-dd
		}
		// 01.01.09
		else if (preg_match('/^(.*?)[^a-z0-9](\d{2})[^a-z0-9](\d{2})[^a-z0-9](\d{2})[^a-z0-9]/i', $relname, $matches)) {
			// Add extra logic to capture the proper YYYY year
			$episodeArr['season'] = $matches[4] = ($matches[4] <= 99 && $matches[4] > 15) ? '19' . $matches[4] : '20' . $matches[4];
			$episodeArr['episode'] = $matches[2] . '/' . $matches[3];
			$tmpAirdate = $episodeArr['season'] . '/' . $episodeArr['episode'];
			$episodeArr['airdate'] = date('Y-m-d', strtotime(preg_replace('/[^0-9]/i', '/', $tmpAirdate))); //yyyy-mm-dd
		}
		// 2009.E01
		else if (preg_match('/^(.*?)[^a-z0-9]20(\d{2})[^a-z0-9](\d{1,3})[^a-z0-9]/i', $relname, $matches)) {
			$episodeArr['season'] = '20' . $matches[2];
			$episodeArr['episode'] = intval($matches[3]);
		}
		// 2009.Part1
		else if (preg_match('/^(.*?)[^a-z0-9](19|20)(\d{2})[^a-z0-9]Part(\d{1,2})[^a-z0-9]/i', $relname, $matches)) {
			$episodeArr['season'] = $matches[2] . $matches[3];
			$episodeArr['episode'] = intval($matches[4]);
		}
		// Part1/Pt1
		else if (preg_match('/^(.*?)[^a-z0-9](?:Part|Pt)[^a-z0-9](\d{1,2})[^a-z0-9]/i', $relname, $matches)) {
			$episodeArr['season'] = 1;
			$episodeArr['episode'] = intval($matches[2]);
		}
		//The.Pacific.Pt.VI.HDTV.XviD-XII / Part.IV
		else if (preg_match('/^(.*?)[^a-z0-9](?:Part|Pt)[^a-z0-9]([ivx]+)/i', $relname, $matches)) {
			$episodeArr['season'] = 1;
			$epLow = strtolower($matches[2]);
			$episodeArr['episode'] = Text::convertRomanToInt($epLow);
		}
		// Band.Of.Brothers.EP06.Bastogne.DVDRiP.XviD-DEiTY
		else if (preg_match('/^(.*?)[^a-z0-9]EP?[^a-z0-9]?(\d{1,3})/i', $relname, $matches)) {
			$episodeArr['season'] = 1;
			$episodeArr['episode'] = intval($matches[2]);
		}
		// Season.1
		else if (preg_match('/^(.*?)[^a-z0-9]Seasons?[^a-z0-9]?(\d{1,2})/i', $relname, $matches)) {
			$episodeArr['season'] = intval($matches[2]);
			$episodeArr['episode'] = 'all';
		}
		return $episodeArr;
	}

	/**
	 * Parses the cleaned release name to determine if it has a country appended
	 *
	 * @param string $showName
	 *
	 * @return string
	 */
	private function parseCountry($showName)
	{
		// Country or origin matching.
		if (preg_match('/[^a-z0-9](US|UK|AU|NZ|CA|NL|Canada|Australia|America|United[^a-z0-9]States|United[^a-z0-9]Kingdom)/i', $showName, $countryMatch)) {
			$currentCountry = strtolower($countryMatch[1]);
			if ($currentCountry == 'canada') {
				$country = 'CA';
			} else if ($currentCountry == 'australia') {
				$country = 'AU';
			} else if ($currentCountry == 'america' || $currentCountry == 'united states') {
				$country = 'US';
			} else if ($currentCountry == 'united kingdom') {
				$country = 'UK';
			} else {
				$country = strtoupper($countryMatch[1]);
			}
		} else {
			$country = '';
		}
		return $country;
	}

	/**
	 * Supplementary to parseInfo
	 * Cleans a derived local 'showname' for better matching probability
	 * Returns the cleaned string
	 *
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
	 * Simple function that compares two strings of text
	 * Returns percentage of similarity
	 *
	 * @param $ourName
	 * @param $scrapeName
	 * @param $probability
	 *
	 * @return int|float
	 */
	public function checkMatch($ourName, $scrapeName, $probability)
	{
		similar_text($ourName, $scrapeName, $matchpct);

		if (nZEDb_DEBUG) {
			echo PHP_EOL . sprintf('Match Percentage: %d percent between %s and %s', $matchpct, $ourName, $scrapeName) . PHP_EOL;
		}

		if ($matchpct >= $probability) {
			return $matchpct;
		} else {
			return 0;
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

	/**
	 * Checks API response returns have all REQUIRED attributes set
	 * Returns true or false
	 *
	 * @param $array
	 * @param int $type
	 *
	 * @return bool
	 */
	public function checkRequiredAttr($array, $type)
	{
		$required = ['failedToMatchType'];

		switch ($type) {
			case 'tvdbS':
				$required = ['id', 'name', 'overview', 'firstAired'];
				break;
			case 'tvdbE':
				$required = ['name', 'season', 'number', 'firstAired', 'overview'];
				break;
			case 'tvmazeS':
				$required = ['id', 'name', 'summary', 'premiered', 'country'];
				break;
			case 'tvmazeE':
				$required = ['name', 'season', 'number', 'airdate', 'summary'];
				break;
			case 'tmdbS':
				$required = ['id', 'original_name', 'overview', 'first_air_date', 'origin_country'];
				break;
			case 'tmdbE':
				$required = ['name', 'season_number', 'episode_number', 'air_date', 'overview'];
				break;
			case 'traktS':
				$required = ['title', 'ids', 'overview', 'first_aired', 'airs', 'country'];
				break;
			case 'traktE':
				$required = ['title', 'season', 'number', 'overview', 'first_aired'];
				break;
		}

		if (is_array($required)) {
			foreach ($required as $req) {
				if (!in_array($type, ['tmdbS', 'tmdbE', 'traktS', 'traktE'])){
					if (!isset($array->$req)) {
						return false;
					}
				} else {
					if (!isset($array[$req])) {
						return false;
					}
				}
			}
		}
		return true;
	}
}
