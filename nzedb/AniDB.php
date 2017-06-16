<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program (see LICENSE.txt in the base directory.  If
 * not, see:
 *
 * @link      <http://www.gnu.org/licenses/>.
 * @author    niel
 * @copyright 2017 nZEDb
 */
namespace nzedb;

use app\models\AnidbTitles;
use nzedb\db\DB;

class AniDB
{
	/**
	 * @var \nzedb\db\Settings
	 */
	public $pdo;

	/**
	 * @param array $options Class instances / Echo to cli.
	 */
	public function __construct(array $options = [])
	{
		$defaults = [
			'Echo'     => false,
			'Settings' => null,
		];
		$options += $defaults;

		$this->pdo = ($options['Settings'] instanceof DB ? $options['Settings'] : new DB());
	}

	/**
	 * Updates stored AniDB entries in the database
	 *
	 * @param int $anidbID
	 * @param string $type
	 * @param string $startdate
	 * @param string $enddate
	 * @param string $related
	 * @param string $similar
	 * @param string $creators
	 * @param string $description
	 * @param string $rating
	 * @param string $categories
	 * @param string $characters
	 */
	public function updateTitle($anidbID,
								$type,
								$startdate,
								$enddate,
								$related,
								$similar,
								$creators,
								$description,
								$rating,
								$categories,
								$characters,
								$title = null,
								$epnos = null,
								$airdates = null,
								$episodetitles = null,
								$lang = null)
	{
		/*
		$options = [
				'conditions'	=>
					[
						'anidbid'	=> $anidbID,
						'type'		=> $type,
					],
			];
		if ($lang !== null) {
			$options['conditions']['lang'] = $lang;
		}
		if ($title !== null) {
			$options['conditions']['title'] = $title;
		}

		$result = AnidbTitles::find('first', $options);
		if ($result === null) {
			// TODO check for existing anidbid entry. If it exists, create the anidbid, type
			// entry and continue, else return false.
			return false;
		}

		*/

		// FIXME fix the missing variables for this query
		$this->pdo->queryExec(
			sprintf('
				UPDATE anidb_titles AS at INNER JOIN anidb_info ai USING (anidbid)
				SET title = %s, ai.type = %s, startdate = %s, enddate = %s, related = %s, similar = %s,
					creators = %s, description = %s, rating = %s, categories = %s, characters = %s,
					epnos = %s, airdates = %s, episodetitles = %s, unixtime = %d WHERE anidbid = %d',
				$this->pdo->escapeString($title),
				$this->pdo->escapeString($type),
				$this->pdo->escapeString($startdate),
				$this->pdo->escapeString($enddate),
				$this->pdo->escapeString($related),
				$this->pdo->escapeString($similar),
				$this->pdo->escapeString($creators),
				$this->pdo->escapeString($description),
				$this->pdo->escapeString($rating),
				$this->pdo->escapeString($categories),
				$this->pdo->escapeString($characters),
				$this->pdo->escapeString($epnos),
				$this->pdo->escapeString($airdates),
				$this->pdo->escapeString($episodetitles),
				$anidbID,
				time()
			)
		);
	}

	/**
	 * Deletes stored AniDB entries in the database
	 *
	 * @param int $anidbID
	 */
	public function deleteTitle($anidbID)
	{
		$this->pdo->queryExec(
			sprintf('
				DELETE at, ai, ae
				FROM anidb_titles AS at
				LEFT OUTER JOIN anidb_info ai USING (anidbid)
				LEFT OUTER JOIN anidb_episodes ae USING (anidbid)
				WHERE anidbid = %d',
				$anidbID
			)
		);
	}

	/**
	 * Retrieves a list of Anime titles, optionally filtered by starting character or title.
	 *
	 * If $title is supplied, then $initial is ignored (as it might contradict the first
	 * character of the title.
	 *
	 * @param string $initial
	 * @param string $title
	 *
	 * @return array|bool
	 */
	public function getAnimeList($initial = '', $title = '')
	{
		$options = [];

		if (!empty($title)) {
			$where = ['LIKE' => "%$title%"];
		} else if (! empty($initial)) {
			$initial = ($initial == '0-9') ? '[0-9]' : $initial;
			$where = ['REGEXP' => "^$initial"];
		}

		$options['conditions'] = isset($where) ? ['title' => $where] : null;
		$options['fields'] = 'anidbid, title, type, categories, rating, startdate, enddate';
		$options['group'] = 'anidbid';
		$options['joins'] = [
			'LEFT JOIN' => 'anidb_info',
			'STRAIGHT_JOIN' => 'releases'
		];
		$options['order'] = 'title ASC';

		return AnidbTitles::find('all', $options);
////////////////////////////////////////////////////////////////////////////////////////////////////
	/*
		$rsql = $tsql = '';

		if ($initial != '') {
			if ($initial == '0-9') {
				$initial = '[0-9]';
			}
			$rsql .= sprintf('AND at.title REGEXP %s', $this->pdo->escapeString('^' . $initial));
		}

		if ($title != '') {
			$tsql .= sprintf('AND at.title %s', $this->pdo->likeString($title, true, true));
		}
		return $this->pdo->queryDirect(
			sprintf('
				SELECT at.anidbid, at.title,
					ai.type, ai.categories, ai.rating, ai.startdate, ai.enddate
				FROM anidb_titles at
				LEFT JOIN anidb_info ai USING (anidbid)
				STRAIGHT_JOIN releases r ON at.anidbid = r.anidbid
				WHERE at.anidbid > 0 %s %s
				AND r.categories_id = %d
				GROUP BY at.anidbid
				ORDER BY at.title ASC',
				$rsql,
				$tsql,
				Category::TV_ANIME
			)
		);
	*/
	}

	/**
	 * Retrieves a range of Anime titles for site display
	 *
	 * @param int $start
	 * @param int $num
	 * @param string $animetitle
	 * @return array|bool
	 */
	public function getAnimeRange($start, $num, $animetitle = '')
	{
		if ($start === false) {
			$limit = '';
		} else {
			$limit = ' LIMIT ' . $num . ' OFFSET ' . $start;
		}

		$rsql = '';
		if ($animetitle != '') {
			$rsql = sprintf('AND at.title %s', $this->pdo->likeString($animetitle, true, true));
		}

		return $this->pdo->query(
			sprintf("
				SELECT at.anidbid, GROUP_CONCAT(at.title SEPARATOR ', ') AS title,
					ai.description
				FROM anidb_titles AS at
				LEFT JOIN anidb_info AS ai USING (anidbid)
				WHERE 1=1 %s
				AND at.lang = 'en'
				GROUP BY at.anidbid
				ORDER BY at.anidbid ASC %s",
				$rsql,
				$limit
			)
		);
	}

	/**
	 * Retrives the count of Anime titles for pager functions optionally filtered by title
	 *
	 * @param string $title
	 *
	 * @return int
	 */
	public function getAnimeCount($title = '')
	{
		$options = empty($title) ? [] : ['conditions' => ['title' => ['LIKE' => "%$title%"]]];

		return AnidbTitles::find('count', $options);
////////////////////////////////////////////////////////////////////////////////////////////////////
/*
		$rsql = '';
		if ($title != '') {
			$rsql .= sprintf('AND at.title %s', $this->pdo->likeString($title, true, true));
		}

		$res = $this->pdo->queryOneRow(
			sprintf('
				SELECT COUNT(DISTINCT at.anidbid) AS num
				FROM anidb_titles AS at
				LEFT JOIN anidb_info AS ai USING (anidbid)
				WHERE 1=1
				%s',
				$rsql
			)
		);

		return $res['num'];
*/
	}

	/**
	 * Retrieves all info for a specific AniDB ID
	 *
	 * @param int $anidbID
	 * @return array|boolean
	 */
	public function getAnimeInfo($anidbID)
	{
		$animeInfo = $this->pdo->query(
			sprintf('
				SELECT at.anidbid, at.lang, at.title,
					ai.startdate, ai.enddate, ai.updated, ai.related, ai.creators, ai.description,
					ai.rating, ai.picture, ai.categories, ai.characters, ai.type, ai.similar
				FROM anidb_titles AS at
				LEFT JOIN anidb_info AS ai USING (anidbid)
				WHERE at.anidbid = %d',
				$anidbID
			)
		);

		return isset($animeInfo[0]) ? $animeInfo[0] : false;
	}

}
