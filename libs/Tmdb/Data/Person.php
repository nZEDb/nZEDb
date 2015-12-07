<?php
/**
 * This class handles all the data you can get from a Person
 *
 * @author Alvaro Octal | <a href="https://twitter.com/Alvaro_Octal">Twitter</a>
 * @version 0.1
 * @date 11/01/2015
 * @link https://github.com/Alvaroctal/TMDB-PHP-API
 * @copyright Licensed under BSD (http://www.opensource.org/licenses/bsd-license.php)
 */

namespace libs\Tmdb\Data;

use libs\Tmdb\Data\Roles\TVShowRole;
use libs\Tmdb\Data\Roles\MovieRole;

/**
 * Class Person
 *
 * @package libs\Tmdb\Data
 */
class Person{

    //------------------------------------------------------------------------------
    // Class Variables
    //------------------------------------------------------------------------------

    public $_data;

    /**
     * Construct Class
     *
     * @param array $data An array with the data of the Person
     */
    public function __construct($data) {
        $this->_data = $data;
    }

    //------------------------------------------------------------------------------
    // Get Variables
    //------------------------------------------------------------------------------

    /**
     *  Get the Person's name
     *
     *  @return string
     */
    public function getName() {
        return $this->_data['name'];
    }

    /**
     *  Get the Person's id
     *
     *  @return int
     */
    public function getID() {
        return $this->_data['id'];
    }

    /**
     *  Get the Person's profile image
     *
     *  @return string
     */
    public function getProfile() {
        return $this->_data['profile_path'];
    }

    /**
     *  Get the Person's birthday
     *
     *  @return string
     */
    public function getBirthday() {
        return $this->_data['birthday'];
    }

    /**
     *  Get the Person's place of birth
     *
     *  @return string
     */
    public function getPlaceOfBirth() {
        return $this->_data['place_of_birth'];
    }

    /**
     *  Get the Person's imdb id
     *
     *  @return string
     */
    public function getImbdID() {
        return $this->_data['imdb_id'];
    }

    /**
     *  Get the Person's popularity
     *
     *  @return int
     */
    public function getPopularity() {
        return $this->_data['popularity'];
    }

    /**
     *  Get the Person's MovieRoles
     *
     *  @return \libs\Tmdb\Data\Roles\MovieRole[]
     */
    public function getMovieRoles() {
        $movieRoles = array();

        foreach($this->_data['movie_credits']['cast'] as $data){
            $movieRoles[] = new MovieRole($data, $this->getID());
        }

        return $movieRoles;
    }

    /**
     *  Get the Person's TVShowRoles
     *
     *  @return \libs\Tmdb\Data\Roles\TVShowRole[]
     */
    public function getTVShowRoles() {
        $tvShowRole = array();

        foreach($this->_data['tv_credits']['cast'] as $data){
            $tvShowRole[] = new TVShowRole($data, $this->getID());
        }

        return $tvShowRole;
    }

    /**
     *  Get Generic.<br>
     *  Get a item of the array, you should not get used to use this, better use specific get's.
     *
     *  @param string $item The item of the $data array you want
     *  @return array
     */
    public function get($item = ''){
        return (empty($item)) ? $this->_data : $this->_data[$item];
    }

    //------------------------------------------------------------------------------
    // Export
    //------------------------------------------------------------------------------

    /**
     *  Get the JSON representation of the Episode
     *
     *  @return string
     */
    public function getJSON() {
        return json_encode($this->_data, JSON_PRETTY_PRINT);
    }
}
?>
