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

	public function updateTitle($anidbID, $title, $type, $startdate, $enddate, $related, $creators, $description, $rating, $categories, $characters, $epnos, $airdates, $episodetitles)
	{
		$this->pdo->queryExec(
					sprintf('
						UPDATE anidb a INNER JOIN anidb_info ai USING (anidb_id) SET title = %s, type = %s, startdate = %s, enddate = %s,
							related = %s, creators = %s, description = %s, rating = %s,
							categories = %s, characters = %s, epnos = %s, airdates = %s,
							episodetitles = %s, unixtime = %d WHERE anidb_id = %d',
						$this->pdo->escapeString($title),
						$this->pdo->escapeString($type),
						$this->pdo->escapeString($startdate),
						$this->pdo->escapeString($enddate),
						$this->pdo->escapeString($related),
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

	public function deleteTitle($anidbID)
	{
		$this->pdo->queryExec(sprintf('DELETE FROM anidb a INNER JOIN anidb_info ai USING (anidb_id) WHERE anidb_id = %d', $anidbID));
	}

	public function getanidbID($title)
	{
		$anidbID = $this->pdo->queryOneRow(
						sprintf('
							SELECT anidb_id
							FROM animetitles
							WHERE title REGEXP %s
							LIMIT 1',
							$this->pdo->escapeString('^' . $title . '$')
						)
		);

		// if the first query failed try it again using like as we have a change for a match
		if ($anidbID == false) {
			$anidbID = $this->pdo->queryOneRow(
								sprintf('
									SELECT anidb_id
									FROM anidb
									WHERE title %s
									LIMIT 1',
									$this->pdo->likeString($title, true, true)
								)
			);
		}

		return (empty($anidbID['anidb_id']) ? false : $anidbID['anidb_id']);
	}

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

		return $this->pdo->query(
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

	public function getAnimeInfo($anidbID)
	{
		$animeInfo = $this->pdo->query(
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
