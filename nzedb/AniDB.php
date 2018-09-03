<?php
namespace nzedb;

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
	public function updateTitle($anidbID, $type, $startdate, $enddate, $related, $similar, $creators, $description, $rating, $categories, $characters)
	{
		// FIXME fix the missing variables for this query
		$this->pdo->queryExec(
			sprintf('
				UPDATE anidb_titles AS at INNER JOIN anidb_info ai USING (anidbid) SET title = %s, type = %s, startdate = %s, enddate = %s,
					related = %s, similar = %s, creators = %s, description = %s, rating = %s,
					categories = %s, characters = %s, epnos = %s, airdates = %s,
					episodetitles = %s, unixtime = %d WHERE anidbid = %d',
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
	 * Retrieves a list of Anime titles, optionally filtered by starting character and title
	 *
	 * @param string $letter
	 * @param string $animetitle
	 * @return array|bool
	 */
	public function getAnimeList($letter = '', $animetitle = '')
	{
		$rsql = $tsql = '';

		if ($letter != '') {
			if ($letter == '0-9') {
				$letter = '[0-9]';
			}
			$rsql .= sprintf('AND at.title REGEXP %s', $this->pdo->escapeString('^' . $letter));
		}

		if ($animetitle != '') {
			$tsql .= sprintf('AND at.title %s', $this->pdo->likeString($animetitle, true, true));
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
	}

	/**
	 * Retrieves a range of Anime titles for site display
	 *
	 * @param int $start
	 * @param int $num
	 * @param string $animetitle
	 * @return array|bool
	 */
	public function getAnimeRange(int $start, int $num, string $animetitle = '')
	{
		$limit = $start === 0 ? '' : ' LIMIT ' . $num . ' OFFSET ' . $start;

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
	 * @param string $animetitle
	 * @return int
	 */
	public function getAnimeCount($animetitle = '')
	{
		$rsql = '';
		if ($animetitle != '') {
			$rsql .= sprintf('AND at.title %s', $this->pdo->likeString($animetitle, true, true));
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
