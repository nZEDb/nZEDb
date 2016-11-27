<?php
/**
 * This class handles all the data you can get from a MovieRole
 *
 * @author Alvaro Octal | <a href="https://twitter.com/Alvaro_Octal">Twitter</a>
 * @version 0.1
 * @date 11/01/2015
 * @link https://github.com/Alvaroctal/TMDB-PHP-API
 * @copyright Licensed under BSD (http://www.opensource.org/licenses/bsd-license.php)
 */

namespace libs\Tmdb\Data\Roles;
use libs\Tmdb\Data\Role;

class MovieRole extends Role{

    //------------------------------------------------------------------------------
    // Class Variables
    //------------------------------------------------------------------------------

    public $_data;

    /**
     * Construct Class
     *
     * @param array $data An array with the data of a MovieRole
     */
    public function __construct($data, $idPerson) {
        $this->_data = $data;

        parent::__construct($data, $idPerson);
    }

    //------------------------------------------------------------------------------
    // Get Variables
    //------------------------------------------------------------------------------

    /**
     *  Get the Movie's title of the role
     *
     *  @return string
     */
    public function getMovieTitle() {
        return $this->_data['title'];
    }

    /**
     *  Get the Movie's id
     *
     *  @return int
     */
    public function getMovieID() {
        return $this->_data['id'];
    }

    /**
     *  Get the Movie's original title of the role
     *
     *  @return string
     */
    public function getMovieOriginalTitle() {
        return $this->_data['original_title'];
    }

    /**
     *  Get the Movie's release date of the role
     *
     *  @return string
     */
    public function getMovieReleaseDate() {
        return $this->_data['release_date'];
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
