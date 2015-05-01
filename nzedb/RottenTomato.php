<?php

/**
 * Fetches and returns JSON documents from the Rotten Tomato API using a supplied API key.
 *
 * Class RottenTomato
 */
class RottenTomato
{
	/**
	 * Main link for all the methods.
	 */
	const API_URL = 'http://api.rottentomatoes.com/api/public/v1.0/';

	/**
	 * RT Api key.
	 * @var string
	 */
	private $_apikey;

	/**
	 * @param string $apiKey RT Api Key.
	 */
	public function __construct($apiKey)
	{
		$this->_apikey = $apiKey;
	}

	/**
	 * Displays Top Box Office Earning Movies, Sorted by Most Recent Weekend Gross Ticket Sales.
	 *
	 * @param int    $limit   Limits the number of box office movies returned.
	 * @param string $country Provides localized data for the selected country (ISO 3166-1 alpha-2) if available.
	 *                        Otherwise, returns US data.
	 *
	 * @return string
	 */
	public function getBoxOffice($limit = 10, $country = 'us')
	{
		return $this->_makeCall(
			'lists/movies/box_office.json',
			array(
				'limit'   => $limit,
				'country' => $country
			)
		);
	}

	/**
	 * Retrieves movies currently in theaters.
	 *
	 * @param int    $limit   The amount of movies in theaters to show per page.
	 * @param int    $page    The selected page of in theaters movies.
	 * @param string $country Provides localized data for the selected country (ISO 3166-1 alpha-2) if available.
	 *                        Otherwise, returns US data.
	 *
	 * @return string
	 */
	public function getInTheaters($limit = 16, $page = 1, $country = 'us')
	{
		return $this->_makeCall(
			'lists/movies/in_theaters.json',
			array(
				'page_limit' => $limit,
				'page'       => $page,
				'country'    => $country
			)
		);
	}

	/**
	 * Retrieves current opening movies.
	 *
	 * @param int    $limit   Limits the number of opening movies returned.
	 * @param string $country Provides localized data for the selected country (ISO 3166-1 alpha-2) if available.
	 *                        Otherwise, returns US data.
	 *
	 * @return string
	 */
	public function getOpening($limit = 16, $country = 'us')
	{
		return $this->_makeCall(
			'lists/movies/opening.json',
			array(
				'limit'   => $limit,
				'country' => $country
			)
		);
	}

	/**
	 * Retrieves upcoming movies. Results are paginated if they go past the specified page limit.
	 *
	 * @param int    $limit   The amount of upcoming movies to show per page.
	 * @param int    $page    The selected page of upcoming movies.
	 * @param string $country Provides localized data for the selected country (ISO 3166-1 alpha-2) if available.
	 *                        Otherwise, returns US data.
	 *
	 * @return string
	 */
	public function getUpcoming($limit = 16, $page = 1, $country = 'us')
	{
		return $this->_makeCall(
			'lists/movies/upcoming.json',
			array(
				'page_limit' => $limit,
				'page'       => $page,
				'country'    => $country
			)
		);
	}

	/**
	 * Shows the DVD lists RT has available.
	 *
	 * @return string
	 */
	public function getDVDReleases()
	{
		return $this->_makeCall('lists/dvds/new_releases.json');
	}

	/**
	 * The movies search endpoint for plain text queries. Allows you to search for movies!
	 *
	 * @param string $title The plain text search query to search for a movie. Remember to URI encode this!
	 * @param int    $limit The amount of movie search results to show per page.
	 * @param int    $page  The selected page of movie search results.
	 *
	 * @return string
	 */
	public function searchMovie($title, $limit = 50, $page = 1)
	{
		return $this->_makeCall(
			'movies.json',
			array(
				'q'          => $title,
				'page_limit' => $limit,
				'page'       => $page
			)
		);
	}

	/**
	 * Detailed information on a specific movie specified by Id.
	 * You can use the movies search endpoint or peruse the lists of movies/dvds to get the urls to movies.
	 *
	 * @param int $ID The RT ID.
	 *
	 * @return string
	 */
	public function getMovie($ID)
	{
		return $this->_makeCall('movies/' . $ID . '.json');
	}

	/**
	 * Retrieves the reviews for a movie.
	 * Results are paginated if they go past the specified page limit.
	 *
	 * @param int    $ID      The RT ID.
	 * @param string $type    Three different review types are possible:
	 *                        "all", "top_critic" and "dvd".
	 *                        "top_critic" shows all the Rotten Tomatoes designated top critics.
	 *                        "dvd" pulls the reviews given on the DVD of the movie.
	 *                        "all" as the name implies retrieves all reviews.
	 * @param int $limit      The number of reviews to show per page.
	 * @param int $page       The selected page of reviews.
	 * @param string $country Provides localized data for the selected country (ISO 3166-1 alpha-2) if available.
	 *                        Otherwise, returns US data.
	 *
	 * @return string
	 */
	public function getReviews($ID, $type = 'top_critic', $limit = 20, $page = 1, $country = 'us')
	{
		return $this->_makeCall(
			'movies/' . $ID . '/reviews.json',
			array(
				'review_type' => $type,
				'page_limit'  => $limit,
				'page'        => $page,
				'country'     => $country
			)
		);
	}

	/**
	 * Pulls the complete movie cast for a movie.
	 *
	 * @param int $ID The RT ID.
	 *
	 * @return string
	 */
	public function getCast($ID)
	{
		return $this->_makeCall('movies/' . $ID . '/cast.json');
	}

	/**
	 * Make a request to RT.
	 *
	 * @param string  $function The type of request.
	 * @param array   $params   Extra HTTP parameters.
	 *
	 * @return string JSON data from RT.
	 */
	private function _makeCall($function, $params = [])
	{
		return trim(
			nzedb\utility\Utility::getUrl([
					'url' => \RottenTomato::API_URL .
						$function .
						'?limit=' .
						mt_rand(15, 20) .
						'&apikey=' .
						$this->_apikey .
						(!empty($params) ? ('&' . http_build_query($params)) : '')
				]
			)
		);
	}

	/**
	 * Get the RT api key.
	 *
	 * @return string
	 */
	public function getApiKey()
	{
		return $this->_apikey;
	}
}
