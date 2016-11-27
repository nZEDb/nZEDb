<?php
/**
 * This class handles all the data you can get from a Season
 *
 * @author Alvaro Octal | <a href="https://twitter.com/Alvaro_Octal">Twitter</a>
 * @version 0.1
 * @date 11/01/2015
 * @link https://github.com/Alvaroctal/TMDB-PHP-API
 * @copyright Licensed under BSD (http://www.opensource.org/licenses/bsd-license.php)
 */

namespace libs\Tmdb\Data;


/**
 * Class Season
 *
 * @package libs\Tmdb\Data
 */
class Season{

    //------------------------------------------------------------------------------
    // Class Variables
    //------------------------------------------------------------------------------

    public $_data;
    private $_idTVShow;

    /**
     * Construct Class
     *
     * @param array $data An array with the data of the Season
     */
    public function __construct($data, $idTVShow) {
        $this->_data = $data;
        $this->_data['tvshow_id'] = $idTVShow;
    }

    //------------------------------------------------------------------------------
    // Get Variables
    //------------------------------------------------------------------------------

    /**
     * Get the Season's id
     *
     * @return int
     */
    public function getID() {
        return $this->_data['id'];
    }

    /**
     * Get the Season's name
     *
     * @return string
     */
    public function getName() {
        return $this->_data['name'];
    }

    /**
     *  Get the Season's TVShow id
     *
     *  @return int
     */
    public function getTVShowID() {
        return $this->_data['tvshow_id'];
    }

    /**
     * Get the Season's number
     *
     * @return int
     */
    public function getSeasonNumber() {
        return $this->_data['season_number'];
    }

    /**
     * Get the Season's number of episodes
     *
     * @return int
     */
    public function getNumEpisodes() {
        return count($this->_data['episodes']);
    }

    /**
     *  Get a Seasons's Episode
     *
     *  @param int $numEpisode The episode number
     * @return int
     */
    public function getEpisode($numEpisode) {
        return new Episode($this->_data['episodes'],$numEpisode);
    }

    /**
     *  Get the Season's Episodes
     *
     * @return Episode[]
     */
    public function getEpisodes() {
        $episodes = array();

        foreach($this->_data['episodes'] as $data){
            $episodes[] = new Episode($data, $this->getTVShowID());
        }

        return $episodes;
    }

    /**
     * Get the Seasons's Poster
     *
     * @return string
     */
    public function getPoster() {
        return $this->_data['poster_path'];
    }

    /**
     * Get the Season's AirDate
     *
     * @return string
     */
    public function getAirDate() {
        return $this->_data['air_date'];
    }

    /**
     *  Get Generic.<br>
     *  Get a item of the array, you should not get used to use this, better use specific get's.
     *
     * @param string $item The item of the $data array you want
     * @return array
     */
    public function get($item = '') {
        return (empty($item)) ? $this->_data : $this->_data[$item];
    }

    //------------------------------------------------------------------------------
    // Load
    //------------------------------------------------------------------------------

    /**
     *  Reload the content of this class.<br>
     *  Could be used to update or complete the information.
     *
     *  @param \newznab\libraries\Tmdb $tmdb An instance of the API handler, necesary to make the API call.
     */
    public function reload($tmdb) {
        $tmdb->getSeason($this->getTVShowID(), $this->getSeasonNumber());
    }

    //------------------------------------------------------------------------------
    // Export
    //------------------------------------------------------------------------------

    /**
     * Get the JSON representation of the Season
     *
     * @return string
     */
    public function getJSON() {
        return json_encode($this->_data, JSON_PRETTY_PRINT);
    }
}
?>
