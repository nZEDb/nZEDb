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

namespace nzedb\http;

/**
 * Class JSON_Response
 *
 * @package nzedb\http
 */
class JSON_Response
{
	/**
	 * @var array Columns that are returned based on their values being greater than zero
	 */
	protected $extendedColumns;

	/**
	 * @var int The current key in the array we're modifying
	 */
	protected $key;

	/**
	 * @var mixed The parameters from the web request
	 */
	protected $parameters;

	/**
	 * @var array Columns that should always be returned and always set
	 */
	protected $persistentColumns;

	/**
	 * @var array The release we are formatting
	 */
	protected $release;

	/**
	 * @var mixed The array of releases to be formatted
	 */
	protected $releases;

	/**
	 * @var array The associative array to be JSON encoded and returned
	 */
	protected $return;

	/**
	 * JSON_Response constructor.
	 *
	 * @param array $options Array of options for JSON parameters
	 */
	public function __construct($options = array())
	{
		$defaults = [
			'Parameters' => null,
			'Data'       => null,
		];
		$options += $defaults;

		$this->parameters = $options['Parameters'];
		$this->releases = $options['Data'];
		//$this->server = $options['Server'];
		//$this->type = $options['Type'];

		$this->persistentColumns = [
			'title'         => 'searchname',
			'id'            => 'guid',
			'pubdate'       => 'adddate',
			'category_name' => 'category_name',
			'category'      => 'categories_id',
			'size'          => 'size',
			'files'         => 'totalpart',
			'poster'        => 'fromname',
			'grabs'         => 'grabs',
			'comments'      => 'comments',
			'usenetdate'    => 'postdate',
			'group'         => 'group_name',
		];

		$this->extendedColumns = [
			'imdb'          => 'imdbid',
			'anidbdid'      => 'anidbid',
			'prematch'      => 'predb_id',
			'password'      => 'passwordstatus',
			'tvtitle'       => 'title',
			'tvairdate'     => 'firstaired',
			'season'        => 'series',
			'episode'       => 'episode',
			'tvdbid'        => 'tvdb',
			'traktid'       => 'trakt',
			'tvrageid'      => 'tvrage',
			'rageid'        => 'tvrage',
			'tvmazeid'      => 'tvmaze',
			'imdbid'        => 'imdb',
			'tmdbid'        => 'tmdb'
		];
	}

	/**
	 * Formats release data into a JSON response
	 *
	 * @return bool|string
	 */
	public function returnJSON()
	{
		$this->return = array();
		$this->key = 0;

		foreach ($this->releases AS $this->release) {
			foreach ($this->persistentColumns AS $apicol => $dbcol) {
				if (isset($this->release[$dbcol])) {
					if ($dbcol === 'adddate') {
						$this->release[$dbcol] = date(DATE_RSS, strtotime($this->release[$dbcol]));
					}
					$this->return[$this->key][$apicol] = $this->release[$dbcol];
				}
			}
			if ($this->parameters['extended'] == 1) {
				$this->addExtendedInfo();
			}
			$this->key++;
		}

		if (count($this->return) > 0 && array_walk_recursive($this->return, '\nzedb\Utility\Text::cp437toUTF')) {
			return json_encode($this->return, JSON_PRETTY_PRINT);
		} else {
			return false;
		}
	}

	/**
	 * Adds extended info to the return
	 */
	protected function addExtendedInfo()
	{
		foreach ($this->extendedColumns AS $apicol => $dbcol) {
			if (isset($this->release[$dbcol]) && !empty((string)$this->release[$dbcol])) {
				if (is_numeric($this->release[$dbcol]) && $this->release[$dbcol] > 0) {
					if ($dbcol === 'predb_id') {
						$this->release[$dbcol] = 1;
					} else if ($dbcol === 'imdb') {
						$this->release[$dbcol] = str_pad($this->release[$dbcol], 7, '0', STR_PAD_LEFT);
					}
					$this->return[$this->key][$apicol] = $this->release[$dbcol];
				} else if (!is_numeric($this->release[$dbcol])) {
					$this->return[$this->key][$apicol] = $this->release[$dbcol];
				}
			}
		}
	}
}