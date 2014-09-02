<?php

use nzedb\db\Settings;

class AniDB
{
	/**
	 * @var nzedb\db\Settings
	 */
	public $pdo;

	/**
	 * @param array $options Class instances / Echo to cli.
	 */
	public function __construct(array $options = array())
	{
		$defaults = [
			'Echo'     => false,
			'Settings' => null,
		];
		$options += $defaults;

		$this->pdo = ($options['Settings'] instanceof Settings ? $options['Settings'] : new Settings());
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
		$this->pdo->queryExec(
					sprintf('
						UPDATE anidb a INNER JOIN anidb_info ai USING (anidb_id) SET title = %s, type = %s, startdate = %s, enddate = %s,
							related = %s, similar = %s, creators = %s, description = %s, rating = %s,
							categories = %s, characters = %s, epnos = %s, airdates = %s,
							episodetitles = %s, unixtime = %d WHERE anidb_id = %d',
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
						DELETE a, ai, ae
						FROM anidb a
						LEFT OUTER JOIN anidb_info ai USING (anidb_id)
						LEFT OUTER JOIN anidb_episodes ae USING (anidb_id)
						WHERE anidb_id = %d',
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
		$regex = 'REGEXP';
		$rsql = '';

		if ($letter != '') {
			if ($letter == '0-9') {
				$letter = '[0-9]';
			}
			$rsql .= sprintf('AND a.title %s %s', $regex, $this->pdo->escapeString('^' . $letter));
		}

		$tsql = '';
		if ($animetitle != '') {
			$tsql .= sprintf('AND a.title %s', $this->pdo->likeString($animetitle, true, true));
		}

		return $this->pdo->queryDirect(
						sprintf('
							SELECT a.anidb_id, a.title, ai.type, ai.categories,
								ai.rating, ai.startdate, ai.enddate
							FROM anidb a
							INNER JOIN anidb_info ai USING (anidb_id)
							WHERE a.anidb_id > 0 %s %s
							GROUP BY a.anidb_id
							ORDER BY a.title ASC',
							$rsql,
							$tsql
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
	public function getAnimeRange($start, $num, $animetitle = '')
	{
		if ($start === false) {
			$limit = '';
		} else {
			$limit = ' LIMIT ' . $num . ' OFFSET ' . $start;
		}

		$rsql = '';
		if ($animetitle != '') {
			$rsql = sprintf('AND a.title %s', $this->pdo->likeString($animetitle, true, true));
		}

		return $this->pdo->query(
					sprintf('
						SELECT a.anidb_id, a.title, ai.description
						FROM anidb a
						INNER JOIN anidb_info ai USING (anidb_id)
						WHERE 1=1 %s
						ORDER BY a.anidb_id ASC %s',
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
			$rsql .= sprintf('AND a.title %s', $this->pdo->likeString($animetitle, true, true));
		}

		$res = $this->pdo->queryOneRow(
						sprintf('
							SELECT COUNT(a.anidb_id) AS num
							FROM anidb a
							INNER JOIN anidb_info ai USING (anidb_id)
							WHERE 1=1 %s',
							$rsql
						)
		);

		return $res['num'];
	}

	/**
	 * Retrieves all info for a specific AniDB ID
	 *
	 * @param int $anidbID
	 * @return
	 */
	public function getAnimeInfo($anidbID)
	{
		$animeInfo = $this->pdo->queryDirect(
							sprintf('
								SELECT a.*, ai.*
								FROM anidb a
								INNER JOIN anidb_info ai USING (anidb_id)
								WHERE a.anidb_id = %d',
								$anidbID
							)
		);

		return isset($animeInfo[0]) ? $animeInfo[0] : false;
	}

}
