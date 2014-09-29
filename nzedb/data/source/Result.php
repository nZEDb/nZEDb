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
namespace nzedb\data\model\source;

abstract class Result extends \nzedb\Object implements \Iterator
{
	/**
	 * Autoconfig.
	 */
	protected $_autoConfig = array('resource');

	/**
	 * Contains the current element of the result set.
	 */
	protected $_current = false;

	/**
	 * Contains the cached result set.
	 */
	protected $_cache = null;

	/**
	 * If the result resource has been initialized
	 */
	protected $_init = false;

	/**
	 * The current position of the iterator.
	 */
	protected $_iterator = 0;

	/**
	 * If the result resource has been initialized
	 */
	protected $_key = null;

	/**
	 * The bound resource.
	 */
	protected $_resource = null;

	/**
	 * Setted to `true` when the collection has begun iterating.
	 *
	 * @var integer
	 */
	protected $_started = false;

	/**
	 * Indicates whether the current position is valid or not.
	 *
	 * @var boolean
	 * @see lithium\data\source\Result::valid()
	 */
	protected $_valid = false;


	/**
	 * Close the resource.
	 */
	public function close()
	{
		unset($this->_resource);
		$this->_resource = null;
	}

	/**
	 * Contains the current result.
	 *
	 * @return array The current result (or `null` if there is none).
	 */
	public function current()
	{
		if (!$this->_init) {
			$this->_fetch();
		}
		$this->_started = true;
		return $this->_current;
	}

	/**
	 * Returns the current key position on the result.
	 *
	 * @return integer The current iterator position.
	 */
	public function key()
	{
		if (!$this->_init) {
			$this->_fetch();
		}
		$this->_started = true;
		return $this->_key;
	}

	/**
	 * Fetches the next element from the resource.
	 *
	 * @return mixed The next result (or `false` if there is none).
	 */
	public function next()
	{
		if ($this->_started === false) {
			return $this->current();
		}
		$this->_valid = $this->_fetch();
		if (!$this->_valid) {
			$this->_key     = null;
			$this->_current = false;
		}
		return $this->current();
	}

	/**
	 * Fetches the previous element from the cache.
	 *
	 * @return mixed The previous result (or `false` if there is none).
	 */
	public function prev()
	{
		if (!$this->_cache) {
			return;
		}
		if (isset($this->_cache[--$this->_iterator - 1])) {
			$this->_key = $this->_iterator - 1;
			return $this->_current = $this->_cache[$this->_iterator - 1];
		}
		return false;
	}

	/**
	 * Returns the used resource.
	 */
	public function resource()
	{
		return $this->_resource;
	}

	/**
	 * Rewinds the result set to the first position.
	 */
	public function rewind()
	{
		$this->_iterator = 0;
		$this->_started  = false;
		$this->_key      = null;
		$this->_current  = false;
		$this->_init     = false;
	}

	/**
	 * Checks if current position is valid.
	 *
	 * @return boolean `true` if valid, `false` otherwise.
	 */
	public function valid()
	{
		if (!$this->_init) {
			$this->_valid = $this->_fetch();
		}
		return $this->_valid;
	}

	/**
	 * Fetches the current element from the resource.
	 *
	 * @return boolean Return `true` on success or `false` otherwise.
	 */
	protected function _fetch()
	{
		$this->_init = true;
		if ($this->_fetchFromCache() || $this->_fetchFromResource()) {
			return true;
		}
		return false;
	}

	abstract protected function _fetchFromResource();

	/**
	 * Returns the result from the primed cache.
	 *
	 * @return boolean Return `true` on success or `false` if it has not been cached yet.
	 */
	protected function _fetchFromCache()
	{
		if ($this->_iterator < count($this->_cache)) {
			$this->_key     = $this->_iterator;
			$this->_current = $this->_cache[$this->_iterator++];
			return true;
		}
		return false;
	}

	/**
	 * The destructor.
	 */
	public function __destruct()
	{
		$this->close();
	}
}

?>
