<?php
/**
 * This class handles all the data you can get from a TVShowRole
 *
 * @author Alvaro Octal | <a href="https://twitter.com/Alvaro_Octal">Twitter</a>
 * @version 0.1
 * @date 11/01/2015
 * @link https://github.com/Alvaroctal/TMDB-PHP-API
 * @copyright Licensed under BSD (http://www.opensource.org/licenses/bsd-license.php)
 */

namespace libs\Tmdb\Data\Roles;

use libs\Tmdb\Data\Role;

class TVShowRole extends Role{

    //------------------------------------------------------------------------------
    // Class Variables
    //------------------------------------------------------------------------------

    public $_data;

    /**
     * Construct Class
     *
     * @param array $data An array with the data of a TVShowRole
     */
    public function __construct($data, $idPerson) {
        $this->_data = $data;

        parent::__construct($data, $idPerson);
    }

    //------------------------------------------------------------------------------
    // Get Variables
    //------------------------------------------------------------------------------

    /**
     *  Get the TVShow's title of the role
     *
     *  @return string
     */
    public function getTVShowName() {
        return $this->_data['name'];
    }

    /**
     *  Get the TVShow's id
     *
     *  @return int
     */
    public function getTVShowID() {
        return $this->_data['id'];
    }

    /**
     *  Get the TVShow's original title of the role
     *
     *  @return string
     */
    public function getTVShowOriginalTitle() {
        return $this->_data['original_name'];
    }

    /**
     *  Get the TVShow's release date of the role
     *
     *  @return string
     */
    public function getTVShowFirstAirDate() {
        return $this->_data['first_air_date'];
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
