<?php
/**
 * This class handles all the data you can get from a Collection
 *
 * @author Alvaro Octal | <a href="https://twitter.com/Alvaro_Octal">Twitter</a>
 * @version 0.1
 * @date 11/01/2015
 * @link https://github.com/Alvaroctal/TMDB-PHP-API
 * @copyright Licensed under BSD (http://www.opensource.org/licenses/bsd-license.php)
 */

namespace libs\Tmdb\Data;

/**
 * Class Collection
 *
 * @package libs\Tmdb\Data
 */
class Collection {

    //------------------------------------------------------------------------------
    // Class Variables
    //------------------------------------------------------------------------------

    public $_data;

    /**
     * Construct Class
     *
     * @param array $data An array with the data of a Collection
     */
    public function __construct($data) {
        $this->_data = $data;
    }

    //------------------------------------------------------------------------------
    // Get Variables
    //------------------------------------------------------------------------------

    /**
     *  Get the Collection's name
     *
     *  @return string
     */
    public function getName() {
        return $this->_data['name'];
    }

    /**
     *  Get the Collection's id
     *
     *  @return int
     */
    public function getID() {
        return $this->_data['id'];
    }

    /**
     *  Get the Collection's overview
     *
     *  @return string
     */
    public function getOverview() {
        return $this->_data['overview'];
    }

    /**
     *  Get the Collection's poster
     *
     *  @return string
     */
    public function getPoster() {
        return $this->_data['poster_path'];
    }

    /**
     *  Get the Collection's backdrop
     *
     *  @return string
     */
    public function getBackdrop() {
        return $this->_data['backdrop_path'];
    }

    /**
     *  Get the Collection's Movies
     *
     *  @return Movie[]
     */
    public function getMovies() {
        $movies = array();

        foreach($this->_data['parts'] as $data){
            $movies[] = new Movie($data);
        }

        return $movies;
    }

    /**
     *  Get Generic.<br>
     *  Get a item of the array, you should not get used to use this, better use specific get's.
     *
     *  @param string $item The item of the $data array you want
     *  @return array
     */
    public function get($item = '') {
        return (empty($item)) ? $this->_data : $this->_data[$item];
    }
}
?>
