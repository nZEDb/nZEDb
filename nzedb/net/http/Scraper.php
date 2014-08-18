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
 * @copyright 2014 nZEDb
 */
namespace nzedb\net\http;

abstract class Scraper
{
	/**
	 * If a directlink is given, store here.
	 *
	 * @var string
	 */
	protected $directLink = null;

	/**
	 * @var \libs\simple_html_dom
	 */
	protected $html;

	/**
	 * @var string
	 */
	protected $searchTerm;

	/**
	 * String to hold any cookie sent by the site.
	 *
	 * @var string
	 */
	protected $siteCookie;

	/**
	 * Title to search for?
	 *
	 * @var string
	 */
	protected $title = '';

	/**
	 * ID of the target item.
	 *
	 * @var int|string
	 */
	private $targetID;

	public function __construct()
	{
	}

	protected function _getID()
	{
		return $this->targetID;
	}

	abstract protected function _getURL();

	protected function _search()
	{
		$result = $this->_getURL();
		if ($result !== false) {
			//do stuff with result
		}

		return $result;
	}

	protected function _setID($value)
	{
		$this->targetID = $value;
	}
}
