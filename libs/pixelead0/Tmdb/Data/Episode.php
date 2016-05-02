<?php
/**
 * This class handles all the data you can get from a Episode
 *
 * @author Alvaro Octal | <a href="https://twitter.com/Alvaro_Octal">Twitter</a>
 * @version 0.1
 * @date 11/01/2015
 * @link https://github.com/Alvaroctal/TMDB-PHP-API
 * @copyright Licensed under BSD (http://www.opensource.org/licenses/bsd-license.php)
 */

namespace libs\Tmdb\Data;

/**
 * Class Episode
 *
 * @package libs\Tmdb\Data
 */
class Episode{

    //------------------------------------------------------------------------------
    // Class Variables
    //------------------------------------------------------------------------------

    public $_data;

    /**
     * Construct Class
     *
     * @param array $data An array with the data of the Episode
     */
    public function __construct($data, $idTVShow) {
        $this->_data = $data;
        $this->_data['tvshow_id'] = $idTVShow;
    }

    //------------------------------------------------------------------------------
    // Get Variables
    //------------------------------------------------------------------------------

    /**
     * Get the episode's id
     *
     * @return int
     */
    public function getID() {
        return $this->_data['id'];
    }

    /**
     * Get the Episode's name
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
     *  Get the Season's number
     *
     *  @return int
     */
    public function getSeasonNumber() {
        return $this->_data['season_number'];
    }

    /**
     * Get the Episode's number
     *
     * @return string
     */
    public function getEpisodeNumber() {
        return $this->_data['episode_number'];
    }

    /**
     *  Get the Episode's Overview
     *
     *  @return string
     */
    public function getOverview() {
        return $this->_data['overview'];
    }

    /**
     * Get the Seasons's Still
     *
     * @return string
     */
    public function getStill() {
        return $this->_data['still_path'];
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
     * Get the Episode's vote average
     *
     * @return int
     */
    public function getVoteAverage() {
        return $this->_data['vote_average'];
    }

    /**
     * Get the Episode's vote count
     *
     * @return int
     */
    public function getVoteCount() {
        return $this->_data['vote_count'];
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
    // Load
    //------------------------------------------------------------------------------

    /**
     *  Reload the content of this class.<br>
     *  Could be used to update or complete the information.
     *
     *  @param \newznab\libraries\TmdbAPI $tmdb An instance of the API handler, necesary to make the API call.
     */
    public function reload($tmdb) {
        $tmdb->getEpisode($this->getTVShowID(), $this->getSeasonNumber(), $this->getEpisodeNumber());
    }

    //------------------------------------------------------------------------------
    // Export
    //------------------------------------------------------------------------------

    /**
     * Get the JSON representation of the Episode
     *
     * @return string
     */
    public function getJSON() {
        return json_encode($this->_data, JSON_PRETTY_PRINT);
    }
}
?>
