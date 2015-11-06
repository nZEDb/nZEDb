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
 * @author    niel
 * @copyright 2015 nZEDb
 */
namespace nzedb\processing;

use nzedb\db\Settings;

/**
 * Parent class for TV/Film and any similar classes to inherit from.
 *
 * @package nzedb\processing
 */
abstract class Videos
{
	// Video Type Identifiers
	const TYPE_TV		= 0; // Type of video is a TV Programme/Show
	const TYPE_FILM		= 1; // Type of video is a Film/Movie
	const TYPE_ANIME	= 2; // Type of video is a Anime

	/**
	 * @var \nzedb\db\Settings
	 */
	public $pdo;

	/**
	 * @var array	sites	The sites that we have an ID columns for in our video table.
	 */
	private $sites = ['imdb', 'tmdb', 'trakt', 'tvdb', 'tvmaze', 'tvrage'];


	public function __construct(array $options = [])
	{
		$defaults = [
			'Echo'     => false,
			'Settings' => null,
		];
		$options += $defaults;

		// Sets the default timezone for this script (and its children).
		date_default_timezone_set('UTC');

		$this->pdo = ($options['Settings'] instanceof Settings ? $options['Settings'] : new Settings());
	}

	/**
	 * Get video info from a Site ID and column.
	 *
	 * @param string  $siteColumn
	 * @param integer $videoID
	 *
	 * @return array|false    False if invalid site, or ID not found; Site id value otherwise.
	 */
	protected function getSiteIDFromVideoID($siteColumn, $videoID)
	{
		if (in_array($siteColumn, $this->sites)) {
			$result = $this->pdo->queryOneRow("SELECT $siteColumn FROM videos WHERE id = $videoID");

			return isset($result[$siteColumn]) ? $result[$siteColumn] : false;
		}

		return false;
	}

	/**
	 * Get video info from a Site ID and column.
	 *
	 * @param string	$siteColumn
	 * @param integer	$siteID
	 *
	 * @return array|false	False if invalid site, or ID not found; video.id value otherwise.
	 */
	protected function getVideoIDFromSiteID($siteColumn, $siteID)
	{
		if (in_array($siteColumn, $this->sites)) {
			$result = $this->pdo->queryOneRow("SELECT id FROM videos WHERE $siteColumn = $siteID");

			return isset($result['id']) ? $result['id'] : false;
		}
		return false;
	}

	/**
	 * Attempt a local lookup via the title first by exact match and then by like.
	 * Returns a false for no match or the Video ID of the match.
	 *
	 * @param $title
	 * @param $type
	 *
	 * @return bool
	 */
	public function getByTitle($title, $type)
	{
		// Check if we already have an entry for this show.
		$res = $this->getByTitleQuery($title, $type);
		if (isset($res['id'])) {
			return $res['id'];
		}

		$title2 = str_replace(' and ', ' & ', $title);
		if ($title != $title2) {
			$res = $this->getByTitleQuery($title2, $type);
			if (isset($res['id'])) {
				return $res['id'];
			}
			$pieces = explode(' ', $title2);
			$title4 = '%';
			foreach ($pieces as $piece) {
				$title4 .= str_replace(["'", "!"], "", $piece) . '%';
			}
			$res = $this->getByTitleLikeQuery($title4, $type);
			if (isset($res['id'])) {
				return $res['id'];
			}
		}

		// Some words are spelled correctly 2 ways
		// example theatre and theater
		$title3 = str_replace('er', 're', $title);
		if ($title != $title3) {
			$res = $this->getByTitleQuery($title3, $type);
			if (isset($res['id'])) {
				return $res['id'];
			}
			$pieces = explode(' ', $title3);
			$title4 = '%';
			foreach ($pieces as $piece) {
				$title4 .= str_replace(["'", "!"], "", $piece) . '%';
			}
			$res = $this->getByTitleLikeQuery($title4, $type);
			if (isset($res['id'])) {
				return $res['id'];
			}
		}

		// If there was not an exact title match, look for title with missing chars
		// example release name :Zorro 1990, tvrage name Zorro (1990)
		// Only search if the title contains more than one word to prevent incorrect matches
		$pieces = explode(' ', $title);
		if (count($pieces) > 1) {
			$title4 = '%';
			foreach ($pieces as $piece) {
				$title4 .= str_replace(["'", "!"], "", $piece) . '%';
			}
			$res = $this->getByTitleLikeQuery($title4, $type);
			if (isset($res['id'])) {
				return $res['id'];
			}
		}
		return false;
	}

	/**
	 * Supplementary function for getByTitle that queries for exact match
	 *
	 * @param $title
	 * @param $type
	 *
	 * @return array|bool
	 */
	public function getByTitleQuery($title, $type)
	{
		$return = false;
		if ($title) {
			$return = $this->pdo->queryOneRow(
				sprintf("
					SELECT v.id
					FROM videos v
					LEFT JOIN videos_akas va ON v.id = va.videos_id
					WHERE (v.title = %1\$s OR va.title = %1\$s)
					AND v.type = %d",
					$this->pdo->escapeString($title),
					$type
				)
			);
		}
		return $return;
	}

	/**
	 * Supplementary function for getByTitle that queries for a like match
	 *
	 * @param $title
	 * @param $type
	 *
	 * @return array|bool
	 */
	public function getByTitleLikeQuery($title, $type)
	{
		$return = false;

		if ($title) {
			$return = $this->pdo->queryOneRow(
				sprintf("
					SELECT v.id
					FROM videos v
					LEFT JOIN videos_akas va ON v.id = va.videos_id
					WHERE (v.title %1\$s
					OR va.title %1\$s)
					AND type = %2\$d",
					$this->pdo->likeString(rtrim($title, '%'), false, false),
					$type
				)
			);
		}
		return $return;
	}

	/**
	 * Inserts aliases for videos
	 *
	 * @param       $videoId
	 * @param array $aliasArr
	 */
	public function addAliases($videoId, $aliasArr = array())
	{
		echo '***';
		if (!empty($aliasArr) && $videoId > 0) {
			foreach ($aliasArr AS $key => $title) {
				// Check if we have the AKA already
				$check = $this->getAliases(0, $title);

				if ($check === false) {
					$this->pdo->queryInsert(
						sprintf('
							INSERT INTO videos_akas
							(videos_id, title)
							VALUES (%d, %s)',
							$videoId,
							$this->pdo->escapeString($title)
						)
					);
				}
			}
		}
	}

	/**
	 * Retrieves all aliases for given VideoID or VideoID for a given alias
	 *
	 * @param int    $videoId
	 * @param string $alias
	 *
	 * @return bool|\PDOStatement
	 */
	public function getAliases($videoId = 0, $alias = '')
	{
		$return = false;
		$sql = '';

		if ($videoId > 0) {
			$sql = 'videos_id = ' . $videoId;
		} else if ($alias !== '') {
			$sql = 'title = ' . $this->pdo->escapeString($alias);
		}

		if ($sql !== '') {
			$return = $this->pdo->query('
				SELECT *
				FROM videos_akas
				WHERE ' . $sql, true
			);
		}
		return (empty($return) ? false : $return);
	}

	/**
	 * This function turns a roman numeral into an integer
	 *
	 * @param string $string
	 *
	 * @return int $e
	 */
	public function convertRomanToInt($string) {
		switch ($string) {
			case 'i': $e = 1;
				break;
			case 'ii': $e = 2;
				break;
			case 'iii': $e = 3;
				break;
			case 'iv': $e = 4;
				break;
			case 'v': $e = 5;
				break;
			case 'vi': $e = 6;
				break;
			case 'vii': $e = 7;
				break;
			case 'viii': $e = 8;
				break;
			case 'ix': $e = 9;
				break;
			case 'x': $e = 10;
				break;
			case 'xi': $e = 11;
				break;
			case 'xii': $e = 12;
				break;
			case 'xiii': $e = 13;
				break;
			case 'xiv': $e = 14;
				break;
			case 'xv': $e = 15;
				break;
			case 'xvi': $e = 16;
				break;
			case 'xvii': $e = 17;
				break;
			case 'xviii': $e = 18;
				break;
			case 'xix': $e = 19;
				break;
			case 'xx': $e = 20;
				break;
			default:
				$e = 0;
		}
		return $e;
	}

	/**
	 * Get a country code for a country name.
	 *
	 * @param string $country
	 *
	 * @return mixed
	 */
	public function countryCode($country)
	{
		if (!is_array($country) && strlen($country) > 2) {
			$code = $this->pdo->queryOneRow(
				sprintf('
					SELECT id
					FROM countries
					WHERE country = %s',
					$this->pdo->escapeString($country)
				)
			);
			if (isset($code['id'])) {
				return $code['id'];
			}
		}
		return '';
	}
}
