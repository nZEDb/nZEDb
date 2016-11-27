<?php
namespace nzedb;

use nzedb\db\DB;

class UserMovies
{
	/**
	 * @var \nzedb\db\Settings
	 */
	public $pdo;

	/**
	 * @param array $options Class instances.
	 */
	public function __construct(array $options = [])
	{
		$defaults = [
			'Settings' => null,
		];
		$options += $defaults;

		$this->pdo = ($options['Settings'] instanceof DB ? $options['Settings'] : new DB());
	}

	/**
	 * Add a movie to the users movie list.
	 *
	 * @param int   $uid
	 * @param int   $imdbid
	 * @param array $catid
	 *
	 * @return bool|int
	 */
	public function addMovie($uid, $imdbid, $catid = [])
	{
		return $this->pdo->queryInsert(sprintf(
			"INSERT INTO user_movies (user_id, imdbid, categories, createddate)
			VALUES (%d, %d, %s, NOW())",
			$uid,
			$imdbid,
			(!empty($catid)) ? $this->pdo->escapeString(implode('|', $catid)) : "NULL"
			)
		);
	}

	/**
	 * Get all movies for a user.
	 *
	 * @param int $uid
	 *
	 * @return array
	 */
	public function getMovies($uid)
	{
		return $this->pdo->query(sprintf(
			"SELECT um.*, mi.year, mi.plot, mi.cover, mi.title
			FROM user_movies um
			LEFT OUTER JOIN movieinfo mi ON mi.imdbid = um.imdbid
			WHERE user_id = %d
			ORDER BY mi.title ASC",
			$uid
			)
		);
	}

	/**
	 * Delete a movie for a user.
	 *
	 * @param int $uid
	 * @param int $imdbid
	 *
	 * @return bool
	 */
	public function delMovie($uid, $imdbid)
	{
		return $this->pdo->queryExec(sprintf(
			"DELETE FROM user_movies
			WHERE user_id = %d
			AND imdbid = %d ",
			$uid,
			$imdbid
			)
		);
	}

	/**
	 * Get a movie for a user.
	 *
	 * @param int $uid
	 * @param int $imdbid
	 *
	 * @return array|bool
	 */
	public function getMovie($uid, $imdbid)
	{
		return $this->pdo->queryOneRow(sprintf(
			"SELECT um.*, mi.title
			FROM user_movies um
			LEFT OUTER JOIN movieinfo mi ON mi.imdbid = um.imdbid
			WHERE um.user_id = %d
			AND um.imdbid = %d",
			$uid,
			$imdbid
			)
		);
	}

	/**
	 * Delete all movies for a user.
	 *
	 * @param int $uid
	 */
	public function delMovieForUser($uid)
	{
		$this->pdo->queryExec(sprintf(
			"DELETE FROM user_movies
			WHERE user_id = %d",
			$uid
			)
		);
	}

	/**
	 * Update a movie for a user.
	 *
	 * @param int   $uid
	 * @param int   $imdbid
	 * @param array $catid
	 */
	public function updateMovie($uid, $imdbid, $catid = [])
	{
		$this->pdo->queryExec(sprintf(
			"UPDATE user_movies
			SET categories = %s
			WHERE user_id = %d
			AND imdbid = %d",
			(!empty($catid)) ? $this->pdo->escapeString(implode('|', $catid)) : "NULL",
			$uid,
			$imdbid
			)
		);
	}
}
