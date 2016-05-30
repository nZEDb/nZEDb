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
 * @link      <http://www.gnu.org/licenses/>.
 * @author    ruhllatio
 * @copyright 2016 nZEDb
 */
namespace nzedb;

use nzedb\db\Settings;
use nzedb\utility\Misc;

/**
 * Class API
 *
 * @package nzedb
 */
class API {

	/** Settings class
	 * @var \nzedb\db\Settings
	 */
	public $pdo;

	/**
	 * @var array $_GET The get request from the web server
	 */
	public $getRequest;

	/**
	 * @param array $options
	 */
	public function __construct(array $options = [])
	{
		$defaults = [
			'Settings' => null,
			'Request'  => null,
		];
		$options += $defaults;

		$this->pdo = ($options['Settings'] instanceof Settings ? $options['Settings'] : new Settings());
		$this->getRequest = $options['Request'];
	}

	/**
	 * Add language from media info XML to release search names (Used by API)
	 * @param array $releases
	 */
	public function addLanguage(&$releases)
	{
		if ($releases && count($releases)) {
			foreach ($releases as $key => $release) {
				if (isset($release['id'])) {
					$language = $this->pdo->queryOneRow("
						SELECT audiolanguage
						FROM audio_data
						WHERE releases_id = {$release['id']}"
					);
					if ($language !== false) {
						$releases[$key]['searchname'] = $releases[$key]['searchname'] . ' ' . $language['audiolanguage'];
					}
				}
			}
		}
	}

	/**
	 * Verify maxage parameter.
	 *
	 * @return int $maxAge The maximum age of the release
	 */
	public function maxAge()
	{
		$maxAge = -1;
		if (isset($this->getRequest['maxage'])) {
			if ($this->getRequest['maxage'] == '') {
				Misc::showApiError(201, 'Incorrect parameter (maxage must not be empty)');
			} elseif (!is_numeric($this->getRequest['maxage'])) {
				Misc::showApiError(201, 'Incorrect parameter (maxage must be numeric)');
			} else {
				$maxAge = (int)$this->getRequest['maxage'];
			}
		}
		return $maxAge;
	}

	/**
	 * Verify cat parameter.
	 * @return array
	 */
	public function categoryID()
	{
		$categoryID[] = -1;
		if (isset($this->getRequest['cat'])) {
			$categoryIDs = urldecode($this->getRequest['cat']);
			// Append Web-DL category ID if HD present for SickBeard / Sonarr compatibility.
			if (strpos($categoryIDs, (string)Category::TV_HD) !== false &&
				strpos($categoryIDs, (string)Category::TV_WEBDL) === false) {
				$categoryIDs .= (',' . Category::TV_WEBDL);
			}
			$categoryID = explode(',', $categoryIDs);
		}
		return $categoryID;
	}

	/**
	 * Verify limit parameter.
	 * @return int
	 */
	public function limit()
	{
		$limit = 100;
		if (isset($this->getRequest['limit']) && is_numeric($this->getRequest['limit']) && $this->getRequest['limit'] < 100) {
			$limit = (int)$this->getRequest['limit'];
		}
		return $limit;
	}

	/**
	 * Verify offset parameter.
	 * @return int
	 */
	public function offset()
	{
		$offset = 0;
		if (isset($this->getRequest['offset']) && is_numeric($this->getRequest['offset'])) {
			$offset = (int)$this->getRequest['offset'];
		}
		return $offset;
	}

	/**
	 * Check if a parameter is empty.
	 * @param string $parameter
	 */
	public function verifyEmptyParameter($parameter)
	{
		if (isset($this->getRequest[$parameter]) && $this->getRequest[$parameter] == '') {
			Misc::showApiError(201, 'Incorrect parameter (' . $parameter . ' must not be empty)');
		}
	}

	/**
	 * Inject the coverurl
	 *
	 * @param          $releases
	 * @param callable $getCoverURL
	 */
	public function addCoverURL(&$releases, callable $getCoverURL)
	{
		if ($releases && count($releases)) {
			foreach ($releases as $key => $release) {
				$coverURL = $getCoverURL($release);
				$releases[$key]['coverurl'] = $coverURL;
			}
		}
	}
}
