<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program (see LICENSE.txt in the base directory.  If
 * not, see:
 *
 * @link <http://www.gnu.org/licenses/>.
 * @author niel
 */
namespace nzedb\data\model\source\database\adapter\pdo;

use \PDO;
use \PDOStatement;
use \PDOException;

/**
 * This class is a wrapper around the MySQL result returned and can be used to iterate over it.
 *
 * It also provides a simple caching mechanism which stores the result after the first load.
 * You are then free to iterate over the result back and forth through the provided methods
 * and don't have to think about hitting the database too often.
 *
 * On initialization, it needs a `PDOStatement` to operate on. You are then free to use all
 * methods provided by the `Iterator` interface.
 *
 * @link http://php.net/manual/de/class.pdostatement.php The PDOStatement class.
 * @link http://php.net/manual/de/class.iterator.php The Iterator interface.
 */
class Result extends \nzedb\data\source\Result
{
	public $named = false;

	/**
	 * Fetches the result from the resource and caches it.
	 *
	 * @return boolean Return `true` on success or `false` if it is not valid.
	 */
	protected function _fetchFromResource()
	{
		if ($this->_resource instanceof PDOStatement) {
			try {
				$mode = $this->named ? PDO::FETCH_NAMED : PDO::FETCH_NUM;
				if ($result = $this->_resource->fetch($mode)) {
					$this->_key                                         = $this->_iterator;
					$this->_current = $this->_cache[$this->_iterator++] = $result;
					return true;
				}
			} catch (PDOException $e) {
			}
		}
		$this->_resource = null;
		return false;
	}

	public function __destruct()
	{
		$this->close();
	}
}

?>
