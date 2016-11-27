<?php
/**
 * This class handles all the data you can get from a Role
 *
 * @author Alvaro Octal | <a href="https://twitter.com/Alvaro_Octal">Twitter</a>
 * @version 0.1
 * @date 11/01/2015
 * @link https://github.com/Alvaroctal/TMDB-PHP-API
 * @copyright Licensed under BSD (http://www.opensource.org/licenses/bsd-license.php)
 */

namespace libs\Tmdb\Data;

/**
 * Class Role
 *
 * @package libs\Tmdb\Data
 */
class Role{

    //------------------------------------------------------------------------------
    // Class Variables
    //------------------------------------------------------------------------------

    public $_data;

    /**
     * Construct Class
     *
     * @param array $data An array with the data of a Role
     */
    protected function __construct($data, $ipPerson) {
        $this->_data = $data;
        $this->_data['person_id'] = $ipPerson;
    }

    //------------------------------------------------------------------------------
    // Get Variables
    //------------------------------------------------------------------------------

    /**
     *  Get the Role's character
     *
     *  @return string
     */
    public function getCharacter() {
        return $this->_data['character'];
    }

    /**
     *  Get the Movie's poster
     *
     *  @return string
     */
    public function getPoster() {
        return $this->_data['poster_path'];
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
}
?>
