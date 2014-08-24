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
	 * PSR-3 compliant Logger object eventually, ColorCLI for now.
	 *
	 * @var \ColorCLI
	 */
	public $log;

	/**
	 * Base URL for target site
	 *
	 * @var string
	 */
	protected $_baseURL;

	/**
	 * @var resource
	 */
	protected $_curlHandle;

	/**
	 * Data structure containing all collected info. Methods to populate/retrieve this data will be
	 * category specific (i.e. Books, Games, etc).
	 *
	 * @var array
	 */
	protected $_data;

	/**
	 * If a direct link to the item is given, store here.
	 *
	 * @var string
	 */
	protected $_directURL = null;

	/**
	 * @var \libs\simple_html_dom
	 */
	protected $_dom;

	/**
	 * Raw HTML as returned by curl.
	 *
	 * @var string
	 */
	protected $_html;

	/**
	 * Path to save any fetched images (covers, posters, etc.)
	 *
	 * @var string
	 */
	protected $_coversPath;

	/**
	 * @var string
	 */
	protected $_searchTerm;

	/**
	 * String to hold any cookie sent by the site.
	 *
	 * @var string
	 */
	protected $_siteCookie;

	/**
	 * ID we're trying to discover.
	 *
	 * @var int|string
	 */
	private $itemID;

	/**
	 * Name we're trying to discover ;-)
	 *
	 * @var
	 */
	private $itemName;

	public function __construct(array $options = [])
	{
		$defaults = [
			'baseURL'	=> null,
			'db'		=> null,
			'log'		=> null,
		];
		$options += $defaults;

		if (empty($options['baseURL'])) {
			throw new \InvalidArgumentException("Web page scrapers must have a base url");
		}

		$options['log'] = ($options['log'] instanceof \ColorCLI) ? $options['log'] : new \ColorCLI();

	}

	protected function _getID()
	{
		return $this->itemID;
	}

	protected function _getName()
	{
		return $this->itemName;
	}

	/**
	 * Handle initial connection to site using curl.
	 */
	protected function _getURL($url, $post = false)
	{
		//TODO reuse Utility getURL
	}

	protected function _search()
	{
		$result = $this->_getURL();
		if ($result !== false) {
			//do stuff with result which is Site specific
		}

		return $result;
	}

	protected function _setID($value)
	{
		$this->itemID = $value;
	}

	protected function _setName($value)
	{
		$this->itemName = $value;
	}
}
