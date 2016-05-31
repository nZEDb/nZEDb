<?php

namespace nzedb\http;

use nzedb\db\Settings;
use nzedb\Releases;
use nzedb\Category;
use nzedb\NZB;

/**
 * Class RSS -- contains specific functions for RSS
 *
 * @package nzedb
 */
Class RSS extends Output
{
	/** Releases class
	 * @var Releases
	 */
	public $releases;

	/** Settings class
	 * @var \nzedb\db\Settings
	 */
	public $pdo;

	/**
	 * @param array $options
	 */
	public function __construct(array $options = [])
	{
		parent::__construct($options);
		$defaults = [
			'Settings' => null,
			'Releases' => null
		];
		$options += $defaults;

		$this->pdo = ($options['Settings'] instanceof Settings ? $options['Settings'] : new Settings());
		$this->releases = (
			$options['Releases'] instanceof Releases ? $options['Releases'] : new Releases(['Settings' => $this->pdo])
		);
	}

	/**
	 * Get releases for RSS.
	 *
	 * @param     $cat
	 * @param int $offset
	 * @param int $userID
	 * @param int $videosId
	 * @param int $aniDbID
	 * @param int $airDate
	 *
	 * @return array
	 */
	public function getRss($cat, $offset, $videosId, $aniDbID, $userID = 0, $airDate = -1)
	{
		$catSearch = $cartSearch = '';

		$catLimit = "AND r.categories_id BETWEEN " . Category::TV_ROOT . " AND " . Category::TV_OTHER;

		if (count($cat)) {
			if ($cat[0] == -2) {
				$cartSearch = sprintf('INNER JOIN users_releases ON users_releases.user_id = %d AND users_releases.releases_id = r.id', $userID);
			} else if ($cat[0] != -1) {
				$catSearch = $this->releases->categorySQL($cat);
			}
		}

		$sql = $this->pdo->query(
			sprintf(
				"SELECT r.*,
					m.cover, m.imdbid, m.rating, m.plot, m.year, m.genre, m.director, m.actors,
					g.name AS group_name,
					CONCAT(cp.title, ' > ', c.title) AS category_name,
					COALESCE(cp.id,0) AS parentid,
					mu.title AS mu_title, mu.url AS mu_url, mu.artist AS mu_artist,
					mu.publisher AS mu_publisher, mu.releasedate AS mu_releasedate,
					mu.review AS mu_review, mu.tracks AS mu_tracks, mu.cover AS mu_cover,
					mug.title AS mu_genre, co.title AS co_title, co.url AS co_url,
					co.publisher AS co_publisher, co.releasedate AS co_releasedate,
					co.review AS co_review, co.cover AS co_cover, cog.title AS co_genre,
					bo.cover AS bo_cover
					%s AS category_ids,
				FROM releases r
				INNER JOIN categories c ON c.id = r.categories_id
				INNER JOIN categories cp ON cp.id = c.parentid
				INNER JOIN groups g ON g.id = r.group_id
				LEFT OUTER JOIN movieinfo m ON m.imdbid = r.imdbid AND m.title != ''
				LEFT OUTER JOIN musicinfo mu ON mu.id = r.musicinfo_id
				LEFT OUTER JOIN genres mug ON mug.id = mu.genre_id
				LEFT OUTER JOIN consoleinfo co ON co.id = r.consoleinfo_id
				LEFT OUTER JOIN genres cog ON cog.id = co.genre_id %s
				LEFT OUTER JOIN tv_episodes tve ON tve.id = r.tv_episodes_id
				LEFT OUTER JOIN bookinfo bo ON bo.id = r.bookinfo_id
				WHERE r.passwordstatus %s
				AND r.nzbstatus = %d
				%s %s %s %s
				ORDER BY postdate DESC %s",
				$this->releases->getConcatenatedCategoryIDs(),
				$cartSearch,
				$this->releases->showPasswords,
				NZB::NZB_ADDED,
				$catSearch,
				($videosId > 0 ? sprintf('AND r.videos_id = %d %s', $videosId, ($catSearch == '' ? $catLimit : '')) : ''),
				($aniDbID > 0 ? sprintf('AND r.anidbid = %d %s', $aniDbID, ($catSearch == '' ? $catLimit : '')) : ''),
				($airDate > -1 ? sprintf('AND tve.firstaired >= DATE_SUB(CURDATE(), INTERVAL %d DAY)', $airDate) : ''),
				(' LIMIT 0,' . ($offset > 100 ? 100 : $offset))
			), true, nZEDb_CACHE_EXPIRY_MEDIUM
		);
		return $sql;
	}

	/**
	 * Get TV shows for RSS.
	 *
	 * @param int   $limit
	 * @param int   $userID
	 * @param array $excludedCats
	 * @param int   $airDate
	 *
	 * @return array
	 */
	public function getShowsRss($limit, $userID = 0, $excludedCats = [], $airDate = -1)
	{
		return $this->pdo->query(
			sprintf("
				SELECT r.*, v.id, v.title, g.name AS group_name,
					CONCAT(cp.title, '-', c.title) AS category_name,
					%s AS category_ids,
					COALESCE(cp.id,0) AS parentid
				FROM releases r
				INNER JOIN categories c ON c.id = r.categories_id
				INNER JOIN categories cp ON cp.id = c.parentid
				INNER JOIN groups g ON g.id = r.group_id
				LEFT OUTER JOIN videos v ON v.id = r.videos_id
				LEFT OUTER JOIN tv_episodes tve ON tve.id = r.tv_episodes_id
				WHERE %s %s %s
				AND r.nzbstatus = %d
				AND r.categories_id BETWEEN %d AND %d
				AND r.passwordstatus %s
				ORDER BY postdate DESC %s",
				$this->releases->getConcatenatedCategoryIDs(),
				$this->releases->uSQL(
					$this->pdo->query(
						sprintf('
							SELECT videos_id, categoryid
							FROM user_series
							WHERE user_id = %d',
							$userID
						),
						true
					),
					'videos_id'
				),
				(count($excludedCats) ? 'AND r.categories_id NOT IN (' . implode(',', $excludedCats) . ')' : ''),
				($airDate > -1 ? sprintf('AND tve.firstaired >= DATE_SUB(CURDATE(), INTERVAL %d DAY) ', $airDate) : ''),
				NZB::NZB_ADDED,
				Category::TV_ROOT,
				Category::TV_OTHER,
				$this->releases->showPasswords,
				(' LIMIT ' . ($limit > 100 ? 100 : $limit) . ' OFFSET 0')
			), true, nZEDb_CACHE_EXPIRY_MEDIUM
		);
	}

	/**
	 * Get movies for RSS.
	 *
	 * @param int   $limit
	 * @param int   $userID
	 * @param array $excludedCats
	 *
	 * @return array
	 */
	public function getMyMoviesRss($limit, $userID = 0, $excludedCats = [])
	{
		return $this->pdo->query(
			sprintf("
				SELECT r.*, mi.title AS releasetitle, g.name AS group_name,
					CONCAT(cp.title, '-', c.title) AS category_name,
					%s AS category_ids,
					COALESCE(cp.id,0) AS parentid
				FROM releases r
				INNER JOIN categories c ON c.id = r.categories_id
				INNER JOIN categories cp ON cp.id = c.parentid
				INNER JOIN groups g ON g.id = r.group_id
				LEFT OUTER JOIN movieinfo mi ON mi.imdbid = r.imdbid
				WHERE %s %s
				AND r.nzbstatus = %d
				AND r.categories_id BETWEEN ' . Category::MOVIE_ROOT . ' AND ' . Category::MOVIE_OTHER . '
				AND r.passwordstatus %s
				ORDER BY postdate DESC %s",
				$this->releases->getConcatenatedCategoryIDs(),
				$this->releases->uSQL(
					$this->pdo->query(
						sprintf('
							SELECT imdbid, categories_id
							FROM user_movies
							WHERE user_id = %d',
							$userID
						),
						true
					),
					'imdbid'
				),
				(count($excludedCats) ? ' AND r.categories_id NOT IN (' . implode(',', $excludedCats) . ')' : ''),
				NZB::NZB_ADDED,
				$this->releases->showPasswords,
				(' LIMIT ' . ($limit > 100 ? 100 : $limit) . ' OFFSET 0')
			),
			true,
			nZEDb_CACHE_EXPIRY_MEDIUM
		);
	}

	/**
	 * @param $column
	 * @param $table
	 *
	 * @param $order
	 *
	 * @return array|bool
	 */
	public function getFirstInstance($column, $table, $order)
	{
		return $this->pdo->queryOneRow(
			sprintf("
				SELECT %1\$s
				FROM %2\$s
				WHERE %1\$s > 0
				ORDER BY %3\$s ASC",
				$column,
				$table,
				$order
			)
		);
	}
}
