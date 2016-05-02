<?php
/**
 * TMDB API v3 PHP class - wrapper to API version 3 of 'themoviedb.org
 * API Documentation: https://help.themoviedb.org/kb/api/about-3
 * Documentation and usage in README file
 *
 * @pakage TMDB_V3_API_PHP
 * @author adangq <adangq@gmail.com>
 * @copyright 2012 pixelead0
 * @date 2012-02-12
 * @link http://www.github.com/pixelead
 * @version 0.0.2
 * @license BSD http://www.opensource.org/licenses/bsd-license.php
 *
 *
 * Portions of this file are based on pieces of TMDb PHP API class - API 'themoviedb.org'
 * @Copyright Jonas De Smet - Glamorous | https://github.com/glamorous/TMDb-PHP-API
 * Licensed under BSD (http://www.opensource.org/licenses/bsd-license.php)
 * @date 10.12.2010
 * @version 0.9.10
 * @author Jonas De Smet - Glamorous
 * @link {https://github.com/glamorous/TMDb-PHP-API}
 *
 * Mostly code cleaning and documentation
 * @Copyright Alvaro Octal | https://github.com/Alvaroctal/TMDB-PHP-API
 * Licensed under BSD (http://www.opensource.org/licenses/bsd-license.php)
 * @date 09/01/2015
 * @version 0.0.2.1
 * @author Alvaro Octal
 * @link {https://github.com/Alvaroctal/TMDB-PHP-API}
 *
 * Function List
 *   public function  __construct($apikey,$lang='en')
 *   public function setLang($lang="en")
 *   public function getLang()
 *   public function setImageURL($config)
 *   public function getImageURL($size="original")
 *   public function movieTitles($idMovie)
 *   public function movieTrans($idMovie)
 *   public function movieTrailer($idMovie,$source="")
 *   public function movieDetail($idMovie)
 *   public function moviePoster($idMovie)
 *   public function movieCast($idMovie)
 *   public function movieInfo($idMovie,$option="",$print=false)
 *   public function searchMovie($movieTitle)
 *   public function getConfig()
 *   public function latestMovie()
 *   public function nowPlayingMovies($page=1)
 *
 *   private function _getDataArray($action,$text,$lang="")
 *   private function setApikey($apikey)
 *   private function getApikey()
 *
 *
 * URL LIST:
 *   configuration		https://api.themoviedb.org/3/configuration
 * Image				https://cf2.imgobject.com/t/p/original/IMAGEN.jpg #### echar un ojo ####
 * Search Movie		https://api.themoviedb.org/3/search/movie
 * Search Person		https://api.themoviedb.org/3/search/person
 * Movie Info			https://api.themoviedb.org/3/movie/11
 * Casts				https://api.themoviedb.org/3/movie/11/casts
 * Posters				https://api.themoviedb.org/3/movie/11/images
 * Trailers			https://api.themoviedb.org/3/movie/11/trailers
 * translations		https://api.themoviedb.org/3/movie/11/translations
 * Alternative titles https://api.themoviedb.org/3/movie/11/alternative_titles
 *
 * // Collection Info https://api.themoviedb.org/3/collection/11
 * // Person images		https://api.themoviedb.org/3/person/287/images
 */

namespace libs\Tmdb;

use libs\Tmdb\Data\Collection;
use libs\Tmdb\Data\Episode;
use libs\Tmdb\Data\Movie;
use libs\Tmdb\Data\Person;
use libs\Tmdb\Data\Season;
use libs\Tmdb\Data\TVShow;
/**
 * Class TMDB
 */
class TmdbAPI {

	#@var string url of API TMDB
	const _API_URL_ = "https://api.themoviedb.org/3/";

	#@var string Version of this class
	const VERSION = '0.0.2.1';

	#@var string API KEY
	private $_apikey;

	#@var string Default language
	private $_lang;

	#@var array of TMDB config
    private $_config;

	#@var boolean for testing
	private $_debug;


	/**
	 * Construct Class
	 *
	 * @param string $apikey The API key token
	 * @param string $lang The languaje to work with, default is english
	 */
	public function __construct($apikey, $lang = 'en', $debug = false) {

		// Sets the API key
		$this->setApikey($apikey);

		// Setting Language
		$this->setLang($lang);

		// Set the debug mode
		$this->_debug = $debug;

		// Load the configuration
		if (! $this->_loadConfig()){
			echo "Unable to read configuration, verify that the API key is valid";
			exit;
		}
	}

	//------------------------------------------------------------------------------
	// Api Key
	//------------------------------------------------------------------------------

	/**
	 * Set the API key
	 *
	 * @param string $apikey
	 * @return void
	 */
	private function setApikey($apikey) {
		$this->_apikey = (string) $apikey;
	}

	/**
	 * Get the API key
	 *
	 * @return string
	 */
	private function getApikey() {
		return $this->_apikey;
	}

	//------------------------------------------------------------------------------
	// Language
	//------------------------------------------------------------------------------

	/**
	 *  Set the language
	 *	By default english
	 *
	 * @param string $lang
	 */
	public function setLang($lang = 'en') {
		$this->_lang = $lang;
	}

	/**
	 * Get the language
	 *
	 * @return string
	 */
	public function getLang() {
		return $this->_lang;
	}

	//------------------------------------------------------------------------------
	// Config
	//------------------------------------------------------------------------------

	/**
	 * Loads the configuration of the API
	 *
	 * @return boolean
	 */
	private function _loadConfig() {
		$this->_config = $this->_call('configuration', '');

		return ! empty($this->_config);
	}

	/**
	 * Get Configuration of the API (Revisar)
	 *
	 * @return array
	 */
	public function getConfig(){
		return $this->_config;
	}

	//------------------------------------------------------------------------------
	// Get Variables
	//------------------------------------------------------------------------------

	/**
	 *	Get the URL images
	 * You can specify the width, by default original
	 *
	 * @param String $size A String like 'w185' where you specify the image width
	 * @return string
	 */
	public function getImageURL($size = 'original') {
		return $this->_config['images']['base_url'] . $size;
	}

	/**
	 * Get Movie Info
	 * Gets part of the info of the Movie, mostly used for the lazy load
	 *
	 * @param int $idMovie The Movie id
	 *  @param string $option The request option
	 * @param string $append_request additional request
	 * @return array
	 *	@deprecated
	 */
	public function getMovieInfo($idMovie, $option = '', $append_request = ''){
		$option = (empty($option)) ? '' : '/' . $option;
		$params = 'movie/' . $idMovie . $option;
		$result = $this->_call($params, $append_request);

		return $result;
	}

	//------------------------------------------------------------------------------
	// Get Lists of Discover
	//------------------------------------------------------------------------------
	/**
	 * Get latest Movie
	 *	@add by tnsws
	 *
	 * @return Movie
	 */
	public function getDiscoverMovie($page = 1) {
		$movies = array();
		$result = $this->_call('discover/movie', 'page='. $page);

		if (is_array($result) && !empty($result)) {
			foreach ($result['results'] as $data) {
				$movies[] = new Movie($data);
			}
		}
		return $movies;
	}


	//------------------------------------------------------------------------------
	// Get Lists of Movies
	//------------------------------------------------------------------------------

	/**
	 * Get latest Movie
	 *
	 * @return Movie
	 */
	public function getLatestMovie() {
		return new Movie($this->_call('movie/latest',''));
	}

	/**
	 *  Now Playing Movies
	 *
	 * @param integer $page
	 * @return array
	 */
	public function nowPlayingMovies($page = 1) {

		$movies = array();

		$result = $this->_call('movie/now-playing', 'page='. $page);

		if (is_array($result) && !empty($result)) {
			foreach ($result['results'] as $data) {
				$movies[] = new Movie($data);
			}
		}

		return $movies;
	}

	/**
	 *  Top Rated Movies
	 *	@add by tnsws
	 *
	 * @param integer $page
	 * @return array
	 */
	public function topRatedMovies($page = 1) {
		$movies = array();
		$result = $this->_call('movie/top-rated', 'page='. $page);

		if (is_array($result) && !empty($result)) {
			foreach ($result['results'] as $data) {
				$movies[] = new Movie($data);
			}
		}
		return $movies;
	}
	/**
	 *  Upcoming Movies
	 *	@add by tnsws
	 *
	 * @param integer $page
	 * @return array
	 */
	public function upcomingMovies($page = 1) {
		$movies = array();
		$result = $this->_call('movie/upcoming', 'page='. $page);

		if (is_array($result) && !empty($result)) {
			foreach ($result['results'] as $data) {
				$movies[] = new Movie($data);
			}
		}
		return $movies;
	}


	//------------------------------------------------------------------------------
	// Get Lists of Persons
	//------------------------------------------------------------------------------

	/**
	 * Get latest Person
	 *
	 * @return Person
	 */
	public function getLatestPerson() {
		return new Person($this->_call('person/latest',''));
	}

	/**
	 * Get Popular Persons
	 *
	 * @return Person[]
	 */
	public function getPopularPersons($page = 1) {
		$persons = array();

		$result = $this->_call('person/popular','page='. $page);

		if (is_array($result) && !empty($result)) {
			foreach ($result['results'] as $data) {
				$persons[] = new Person($data);
			}
		}

		return $persons;
	}

	//------------------------------------------------------------------------------
	// API Call
	//------------------------------------------------------------------------------

	/**
	 * Makes the call to the API and retrieves the data as a JSON
	 *
	 * @param string $action	API specific function name for in the URL
	 * @param string $appendToResponse	The extra append of the request
	 * @return string
	 */
	private function _call($action, $appendToResponse){

		$url = self::_API_URL_.$action .'?api_key='. $this->getApikey() .'&language='. $this->getLang() .'&'.$appendToResponse;

		if ($this->_debug) {
			echo '<pre><a href="' . $url . '">check request</a></pre>';
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);

		$results = curl_exec($ch);

		curl_close($ch);

		return json_decode(($results), true);
	}

	//------------------------------------------------------------------------------
	// Get Data Objects
	//------------------------------------------------------------------------------

	/**
	 * Get a Movie
	 *
	 * @param int $idMovie The Movie id
	 * @param string $appendToResponse The extra append of the request, by default all
	 * @return Movie
	 */
	public function getMovie($idMovie, $appendToResponse = 'append_to_response=trailers,images,casts,translations'){
		return new Movie($this->_call('movie/' . $idMovie, $appendToResponse));
	}

	/**
	 * Get a TVShow
	 *
	 * @param int $idTVShow The TVShow id
	 * @param string $appendToResponse The extra append of the request, by default all
	 * @return TVShow
	 */
	public function getTVShow($idTVShow, $appendToResponse = 'append_to_response=trailers,images,casts,translations,keywords'){
		return new TVShow($this->_call('tv/' . $idTVShow, $appendToResponse));
	}

	/**
	 * Get a Season
	 *
	 *  @param int $idTVShow The TVShow id
	 *  @param int $numSeason The Season number
	 * @param string $appendToResponse The extra append of the request, by default all
	 * @return Season
	 */
	public function getSeason($idTVShow, $numSeason, $appendToResponse = 'append_to_response=trailers,images,casts,translations'){
		return new Season($this->_call('tv/'. $idTVShow .'/season/' . $numSeason, $appendToResponse), $idTVShow);
	}

	/**
	 * Get a Season by Number
	 *
	 *  @param int $idTVShow The TVShow id
	 *  @param int $numSeason The Season number
	 * @param string $appendToResponse The extra append of the request, by default all
	 * @return Season
	 */
	/*public function getSeasonByNumber($idTVShow, $numSeason, $appendToResponse = 'append_to_response=trailers,images,casts,translations'){
		return new Season($this->_call('tv/'. $idTVShow .'/season/' . $numSeason, $appendToResponse));
	}*/

	/**
	 * Get a Episode
	 *
	 *  @param int $idEpisode The Episode id
	 * @param string $appendToResponse The extra append of the request, by default all
	 * @return Episode
	 */
	/*public function getEpisode($idEpisode, $appendToResponse = 'append_to_response=trailers,images,casts,translations'){
		return new Episode($this->_call('tv/season/episode/' . $idEpisode, $appendToResponse));
	}*/

	/**
	 * Get a Episode
	 *
	 *  @param int $idTVShow The TVShow id
	 *  @param int $numSeason The Season number
	 *  @param int $numEpisode the Episode number
	 * @param string $appendToResponse The extra append of the request, by default all
	 * @return Episode
	 */
	public function getEpisode($idTVShow, $numSeason, $numEpisode, $appendToResponse = 'append_to_response=trailers,images,casts,translations'){
		return new Episode($this->_call('tv/'. $idTVShow .'/season/'. $numSeason .'/episode/'. $numEpisode, $appendToResponse), $idTVShow);
	}

	/**
	 * Get a Person
	 *
	 * @param int $idPerson The Person id
	 * @param string $appendToResponse The extra append of the request, by default all
	 * @return Person
	 */
	public function getPerson($idPerson, $appendToResponse = 'append_to_response=tv_credits,movie_credits'){
		return new Person($this->_call('person/' . $idPerson, $appendToResponse));
	}

	/**
	 * Get a Collection
	 *
	 * @param int $idCollection The Person id
	 * @param string $appendToResponse The extra append of the request, by default all
	 * @return Collection
	 */
	public function getCollection($idCollection, $appendToResponse = 'append_to_response=images'){
		return new Collection($this->_call('collection/' . $idCollection, $appendToResponse));
	}

	//------------------------------------------------------------------------------
	// Searches
	//------------------------------------------------------------------------------

	/**
	 *  Search Movie
	 *
	 * @param string $movieTitle The title of a Movie
	 * @return Movie[]
	 */
	public function searchMovie($movieTitle){

		$movies = array();

		$result = $this->_call('search/movie', 'query='. urlencode($movieTitle), $this->getLang());

		if (is_array($result) && !empty($result)) {
			foreach ($result['results'] as $data) {
				$movies[] = new Movie($data);
			}
		}

		return $movies;
	}

	/**
	 *  Search TVShow
	 *
	 * @param string $tvShowTitle The title of a TVShow
	 * @return TVShow[]
	 */
	public function searchTVShow($tvShowTitle)
	{

		$tvShows = array();

		$result = $this->_call('search/tv', 'query=' . urlencode($tvShowTitle), $this->getLang());

		if (is_array($result) && !empty($result)) {
			foreach ($result['results'] as $data) {
				$tvShows[] = new TVShow($data);
			}
		}

		return $tvShows;
	}

	/**
	 *  Search Person
	 *
	 * @param string $personName The name of the Person
	 * @return Person[]
	 */
	public function searchPerson($personName){

		$persons = array();

		$result = $this->_call('search/person', 'query='. urlencode($personName), $this->getLang());

		if (is_array($result) && !empty($result)) {
			foreach ($result['results'] as $data) {
				$persons[] = new Person($data);
			}
		}

		return $persons;
	}

	/**
	 *  Search Collection
	 *
	 * @param string $collectionName The name of the Collection
	 * @return Collection[]
	 */
	public function searchCollection($collectionName){

		$collections = array();

		$result = $this->_call('search/collection', 'query='. urlencode($collectionName), $this->getLang());

		if (is_array($result) && !empty($result)) {
			foreach ($result['results'] as $data) {
				$collections[] = new Collection($data);
			}
		}

		return $collections;
	}
}
?>
