<?php
namespace nzedb\libraries;

use nzedb\utility\Misc;

/**
 * Class TraktAPI
 * Retrive info from the Trakt API.
 */
Class TraktAPI {

	const API_URL = 'https://api-v2launch.trakt.tv/';

	/**
	 * The Trakt.tv API v2 Client ID (SHA256 hash - 64 characters long string). Used for movie and tv lookups.
	 * Create one here: https://trakt.tv/oauth/applications/new
	 *
	 * @var array|bool|string
	 */
	private $clientId;

	/**
	 * List of headers to send to Trakt.tv when making a request.
	 *
	 * @see http://docs.trakt.apiary.io/#introduction/required-headers
	 * @var array
	 */
	private $requestHeaders;

	/**
	 * Construct. Set up API key.
	 *
	 * @param array $options Class instances.
	 *
	 * @access public
	 */
	public function __construct(array $options = [])
	{
		$defaults = [
			'clientId' => '',
		];
		$options += $defaults;

		if (empty($options['clientId'])) {
			// Can't work without an ID.
			return null;
		}
		$this->clientId = $options['clientId'];
		$this->requestHeaders = <<<HEADERS
Content-Type: application/json
trakt-api-version: 2
trakt-api-key: $this->clientId
HEADERS;
	}

	/**
	 * Fetches summary from trakt.tv for the TV show using the title/season/episode.
	 *
	 * @param string $title
	 * @param string $season
	 * @param string $ep
	 * @param string $type
	 *
	 * @return array|bool
	 * @see    http://docs.trakt.apiary.io/#reference/episodes/summary/get-a-single-episode-for-a-show
	 *
	 * @access public
	 */
	public function episodeSummary($title = '', $season = '', $ep = '', $type = 'min')
	{
		switch($type) {
			case 'full':
			case 'images':
			case 'full,images':
				$extended = $type;
				break;
			default:
				$extended = 'min';
		}

		$array = $this->getJsonArray(
			self::API_URL . 'shows/' .
			$this->slugify($title) .
			'/seasons/' . str_replace(['S', 's'], '', $season) .
			'/episodes/' . str_replace(['E', 'e'], '', $ep),
			$extended
		);
		if (!$array) {
			return false;
		}
		return $array;
	}

/**
 * Generate and return a slug for a given ``$phrase``.
 */
	public function slugify($phrase)
	{
		$result = strtolower($phrase);
		$result = preg_replace('#[^a-z0-9\s-]#', '', $result);
		$result = trim(preg_replace('#[\s-]+#', ' ', $result));
		$result = preg_replace('#\s#', '-', $result);

		return $result;
	}


	/**
	 * Fetches summary from trakt.tv for the movie.
	 * Accept a title (the-big-lebowski-1998), a IMDB id, or a TMDB id.
	 *
	 * @param string $movie Title or IMDB id.
	 * @param string $type  imdbID:      Return only the IMDB ID (returns string)
	 *                      full:        Return all extended properties (minus images). (returns array)
	 *                      images:      Return extended images properties (returns array)
	 *                      full,images: Return all extended properties (plus images). (returns array)
	 *
	 * @see http://docs.trakt.apiary.io/#reference/movies/summary/get-a-movie
	 *
	 * @return bool|array|string
	 *
	 * @access public
	 */
	public function movieSummary($movie = '', $type = 'imdbID')
	{
		switch($type) {
			case 'full':
			case 'images':
			case 'full,images':
				$extended = $type;
				break;
			case 'imdbID':
			default:
				$extended = 'min';
		}
		$array = $this->getJsonArray(self::API_URL . 'movies/' . $this->slugify($movie),	$extended);
		if (!$array) {
			return false;
		} else if ($type === 'imdbID' && isset($array['ids']['imdb'])) {
			return $array['ids']['imdb'];
		}
		return $array;
	}

	/**
	 * Fetches shows calendar from trakt.tv .
	 *
	 * @param string     $start Start date of calendar ie. 2015-09-01.Default value is today.
	 * @param int $days  Number of days to lookup ahead. Default value is 7 days
	 *
	 * @return array|bool
	 * @see    http://docs.trakt.apiary.io/#reference/calendars/all-shows/get-shows
	 *
	 * @access public
	 */
	public function getCalendar($start = '', $days = 7)
	{
		$array = $this->getJsonArray(
			self::API_URL . 'calendars/all/shows/' . $start . '/' . $days
		);
		if (!$array) {
			return false;
		}
		return $array;
	}

	/**
	 * Fetches weekend box office data from trakt.tv, updated every monday.
	 *
	 * @return array|bool
	 * @see    http://docs.trakt.apiary.io/#reference/movies/box-office/get-the-weekend-box-office
	 *
	 * @access public
	 */
	public function getBoxOffice()
	{
		$array = $this->getJsonArray(
			self::API_URL . 'movies/boxoffice'
		);
		if (!$array) {
			return false;
		}
		return $array;
	}

	/**
	 * Fetches summary from trakt.tv for the show.
	 * Accept a trakt slug (game-of-thrones), a IMDB id, or Trakt id.
	 *
	 * @param string $show Title or IMDB id.
	 * @param string $type  full:        Return all extended properties (minus images). (returns array)
	 *                      images:      Return extended images properties (returns array)
	 *                      full,images: Return all extended properties (plus images). (returns array)
	 *
	 * @see http://docs.trakt.apiary.io/#reference/shows/summary/get-a-single-show
	 *
	 * @return bool|array|string
	 *
	 * @access public
	 */
	public function showSummary($show = '', $type = 'full')
	{
		if (empty($show)) {
			return null;
		}
		$showUrl = self::API_URL . 'shows/' . $this->slugify($show);

		switch($type) {
			case 'images':
			case 'full,images':
				$extended = $type;
				break;
			case 'full':
				$extended = 'full';
				break;
			default:
				$extended = '';
		}
		return $this->getJsonArray($showUrl, $extended);
	}

	/**
	 * Fetches summary from trakt.tv for the show by doing a search.
	 * Accepts a search string
	 *
	 * @param string $show title
	 * @param string $type show
	 *
	 * @see http://docs.trakt.apiary.io/#reference/search/get-text-query-results
	 *
	 * @return bool|array|string
	 *
	 * @access public
	 */
	public function showSearch($show = '', $type = 'show')
	{
		$searchUrl = self::API_URL . 'search?query=' . str_replace([' ', '_', '.'], '-', str_replace(['(', ')'], '', $show)) . '&type=' . $type;
		return $this->getJsonArray($searchUrl, '');
	}

	/**
	 * Download JSON from Trakt, convert to array.
	 *
	 * @param string $URI URI to download.
	 * @param string $extended Extended info from trakt tv.
	 *                         Valid values:
	 *                         'min'         Returns enough info to match locally. (Default)
	 *                         'images'      Minimal info and all images.
	 *                         'full'        Complete info for an item.
	 *                         'full,images' Complete info and all images.
	 *
	 * @return array|false
	 */
	private function getJsonArray($URI, $extended = 'min')
	{
var_dump($extended);
		if ($extended === '') {
			$extendedString = '';
		} else {
			$extendedString = "&extended=" . $extended;
		}
var_dump($URI . $extendedString);
		if (!empty($this->clientId)) {
			$json = Misc::getUrl([
					'url'            => $URI . $extendedString,
					'requestheaders' => $this->requestHeaders
				]
			);

			if ($json !== false) {
				$json = json_decode($json, true);
				if (!is_array($json) || (isset($json['status']) && $json['status'] === 'failure')) {
					return false;
				}
				return $json;
			}
		}
		return false;
	}
}
