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
	 * @var bool
	 */
	public $echooutput;

	/**
	 * @var array	sites	The sites that we have an ID columns for in our video table.
	 */
	private $sites = ['imdb', 'tmdb', 'trakt', 'tvdb', 'tvmaze', 'tvrage'];

	/**
	 * @var array Temp Array of cached failed lookups
	 */
	public $titleCache;

	public function __construct(array $options = [])
	{
		$defaults = [
			'Echo'     => false,
			'Settings' => null,
		];
		$options += $defaults;

		// Sets the default timezone for this script (and its children).
		//date_default_timezone_set('UTC'); TODO: Make this a DTO instead and use as needed

		$this->echooutput = ($options['Echo'] && nZEDb_ECHOCLI);
		$this->pdo = ($options['Settings'] instanceof Settings ? $options['Settings'] : new Settings());
		$this->titleCache = [];
	}

	/**
	 * Main processing director function for scrapers
	 * Calls work query function and initiates processing
	 *
	 * @param      $groupID
	 * @param      $guidChar
	 * @param      $process
	 * @param bool $local
	 */
	abstract protected function processSite($groupID, $guidChar, $process, $local = false);

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
	 * @return int|false	False if invalid site, or ID not found; video.id value otherwise.
	 */
	protected function getVideoIDFromSiteID($siteColumn, $siteID)
	{
		if (in_array($siteColumn, $this->sites)) {
			$result = $this->pdo->queryOneRow("SELECT id FROM videos WHERE $siteColumn = $siteID");

			return isset($result['id']) ? (int)$result['id'] : false;
		}
		return false;
	}

	/**
	 * Attempt a local lookup via the title first by exact match and then by like.
	 * Returns a false for no match or the Video ID of the match.
	 *
	 * @param        $title
	 * @param        $type
	 * @param int    $source
	 *
	 * @return false|int
	 */
	public function getByTitle($title, $type, $source = 0)
	{
		// Check if we already have an entry for this show.
		$res = $this->getTitleExact($title, $type, $source);
		if (isset($res['id'])) {
			return $res['id'];
		}

		$title2 = str_replace(' and ', ' & ', $title);
		if ($title != $title2) {
			$res = $this->getTitleExact($title2, $type, $source);
			if (isset($res['id'])) {
				return $res['id'];
			}
			$pieces = explode(' ', $title2);
			$title2 = '%';
			foreach ($pieces as $piece) {
				$title2 .= str_replace(["'", "!"], "", $piece) . '%';
			}
			$res = $this->getTitleLoose($title2, $type, $source);
			if (isset($res['id'])) {
				return $res['id'];
			}
		}

		// Some words are spelled correctly 2 ways
		// example theatre and theater
		$title2 = str_replace('er', 're', $title);
		if ($title != $title2) {
			$res = $this->getTitleExact($title2, $type, $source);
			if (isset($res['id'])) {
				return $res['id'];
			}
			$pieces = explode(' ', $title2);
			$title2 = '%';
			foreach ($pieces as $piece) {
				$title2 .= str_replace(["'", "!"], "", $piece) . '%';
			}
			$res = $this->getTitleLoose($title2, $type, $source);
			if (isset($res['id'])) {
				return $res['id'];
			}
		}

		// If there was not an exact title match, look for title with missing chars
		// example release name :Zorro 1990, tvrage name Zorro (1990)
		// Only search if the title contains more than one word to prevent incorrect matches
		$pieces = explode(' ', $title);
		if (count($pieces) > 1) {
			$title2 = '%';
			foreach ($pieces as $piece) {
				$title2 .= str_replace(["'", "!"], "", $piece) . '%';
			}
			$res = $this->getTitleLoose($title2, $type, $source);
			if (isset($res['id'])) {
				return $res['id'];
			}
		}
		return false;
	}

	/**
	 * Supplementary function for getByTitle that queries for exact match
	 *
	 * @param        $title
	 * @param        $type
	 * @param int    $source
	 *
	 * @return array|false
	 */
	public function getTitleExact($title, $type, $source = 0)
	{
		$return = false;
		if (!empty($title)) {
			$return = $this->pdo->queryOneRow(
				sprintf("
					SELECT v.id
					FROM videos v
					LEFT JOIN videos_aliases va ON v.id = va.videos_id
					WHERE (v.title = %1\$s OR va.title = %1\$s)
					AND v.type = %2\$d %3\$s",
					$this->pdo->escapeString($title),
					$type,
					($source > 0 ? 'AND v.source = ' . $source : '')
				)
			);
		}
		return $return;
	}

	/**
	 * Supplementary function for getByTitle that queries for a like match
	 *
	 * @param        $title
	 * @param        $type
	 * @param int    $source
	 *
	 * @return array|false
	 */
	public function getTitleLoose($title, $type, $source = 0)
	{
		$return = false;

		if (!empty($title)) {
			$return = $this->pdo->queryOneRow(
				sprintf("
					SELECT v.id
					FROM videos v
					LEFT JOIN videos_aliases va ON v.id = va.videos_id
					WHERE (v.title %1\$s
					OR va.title %1\$s)
					AND type = %2\$d %3\$s",
					$this->pdo->likeString(rtrim($title, '%'), false, false),
					$type,
					($source > 0 ? 'AND v.source = ' . $source : '')
				)
			);
		}
		return $return;
	}

	/**
	 * Inserts aliases for videos
	 *
	 * @param       $videoId
	 * @param array $aliases
	 */
	public function addAliases($videoId, array $aliases = [])
	{
		if (!empty($aliases) && $videoId > 0) {
			foreach ($aliases AS $key => $title) {
				// Check for tvmaze style aka
				if (is_array($title) && !empty($title['name'])) {
					$title = $title['name'];
				}
				// Check if we have the AKA already
				$check = $this->getAliases(0, $title);

				if ($check === false) {
					$this->pdo->queryInsert(
						sprintf('
							INSERT IGNORE INTO videos_aliases
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
	 * @return \PDOStatement|false
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
				FROM videos_aliases
				WHERE ' . $sql, true, nZEDb_CACHE_EXPIRY_MEDIUM
			);
		}
		return (empty($return) ? false : $return);
	}
}
