<?php
/**
 * This class handles all the data you can get from a Movie
 *
 * @author Alvaro Octal | <a href="https://twitter.com/Alvaro_Octal">Twitter</a>
 * @version 0.1
 * @date 09/01/2015
 * @link https://github.com/Alvaroctal/TMDB-PHP-API
 * @copyright Licensed under BSD (http://www.opensource.org/licenses/bsd-license.php)
 */

namespace libs\Tmdb\Data;

use libs\Tmdb\TMDB;

/**
 * Class Movie
 *
 * @package libs\Tmdb\Data
 */
class Movie{

	//------------------------------------------------------------------------------
	// Class Variables
	//------------------------------------------------------------------------------

	public $_data;
	private $_tmdb;

	/**
	 * Construct Class
	 *
	 * @param array $data An array with the data of the Movie
	 */
	public function __construct($data) {
		$this->_data = $data;
	}

	//------------------------------------------------------------------------------
	// Get Variables
	//------------------------------------------------------------------------------

	/**
	 * Get the Movie's id
	 *
	 * @return int
	 */
	public function getID() {
		return $this->_data['id'];
	}

	/**
	 * Get the Movie's title
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->_data['title'];
	}

	/**
	 * Get the Movie's tagline
	 *
	 * @return string
	 */
	public function getTagline() {
		return $this->_data['tagline'];
	}

	/**
	 * Get the Movie's Poster
	 *
	 * @return string
	 */
	public function getPoster() {
		return $this->_data['poster_path'];
	}

	/**
	 * Get the Movie's vote average
	 *
	 * @return int
	 */
	public function getVoteAverage() {
		return $this->_data['vote_average'];
	}

	/**
	 * Get the Movie's vote count
	 *
	 * @return int
	 */
	public function getVoteCount() {
		return $this->_data['vote_count'];
	}

	/**
	 * Get the Movie's trailers
	 *
	 * @return array
	 */
	public function getTrailers() {

		if (empty($this->_data['trailers']) && isset($this->_tmdb)){
			$this->loadTrailer();
		}

		return $this->_data['trailers'];
	}

	/**
	 * Get the Movie's trailer
	 *
	 * @return string
	 */
	public function getTrailer() {
		return $this->getTrailers()['youtube'][0]['source'];
	}

	/**
	 *  Get Generic.<br>
	 *  Get a item of the array, you should not get used to use this, better use specific get's.
	 *
	 * @param string $item The item of the $data array you want
	 * @return array
	 */
	public function get($item = ''){
		return (empty($item)) ? $this->_data : $this->_data[$item];
	}

	//------------------------------------------------------------------------------
	// Load Variables
	//------------------------------------------------------------------------------

	/**
	 * Load the images of the Movie
	 *	Used in a Lazy load technique
	 */
	public function loadImages(){
		$this->_data['images'] = $this->_tmdb->getMovieInfo($this->getID(), 'images', false);
	}

	/**
	 * Load the trailer of the Movie
	 *	Used in a Lazy load technique
	 */
	public function loadTrailer() {
		$this->_data['trailers'] = $this->_tmdb->getMovieInfo($this->getID(), 'trailers', false);
	}

	/**
	 * Load the casting of the Movie
	 *	Used in a Lazy load technique
	 */
	public function loadCasting(){
		$this->_data['casts'] = $this->_tmdb->getMovieInfo($this->getID(), 'casts', false);
	}

	/**
	 * Load the translations of the Movie
	 *	Used in a Lazy load technique
	 */
	public function loadTranslations(){
		$this->_data['translations'] = $this->_tmdb->getMovieInfo($this->getID(), 'translations', false);
	}

	//------------------------------------------------------------------------------
	// Import an API instance
	//------------------------------------------------------------------------------

	/**
	 *	Set an instance of the API
	 *
	 *	@param TMDB $tmdb An instance of the api, necessary for the lazy load
	 */
	public function setAPI($tmdb){
		$this->_tmdb = $tmdb;
	}

	//------------------------------------------------------------------------------
	// Export
	//------------------------------------------------------------------------------

	/**
	 * Get the JSON representation of the Movie
	 *
	 * @return string
	 */
	public function getJSON() {
		return json_encode($this->_data, JSON_PRETTY_PRINT);
	}
}
?>
