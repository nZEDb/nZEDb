<?php

use nzedb\db\Settings;

class UserMovies
{
	/**
	 * @var nzedb\db\Settings
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

		$this->pdo = ($options['Settings'] instanceof Settings ? $options['Settings'] : new Settings());
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
		$catid = (!empty($catid)) ? $this->pdo->escapeString(implode('|', $catid)) : "NULL";
		return $this->pdo->queryInsert(sprintf("INSERT INTO user_movies (user_id, imdbid, categoryid, createddate) VALUES (%d, %d, %s, NOW())", $uid, $imdbid, $catid));
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
		return $this->pdo->query(sprintf("SELECT user_movies.*, movieinfo.year, movieinfo.plot, movieinfo.cover, movieinfo.title FROM user_movies LEFT OUTER JOIN movieinfo ON movieinfo.imdbid = user_movies.imdbid WHERE user_id = %d ORDER BY movieinfo.title ASC", $uid));
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
		return $this->pdo->queryExec(sprintf("DELETE FROM user_movies WHERE user_id = %d AND imdbid = %d ", $uid, $imdbid));
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
		return $this->pdo->queryOneRow(sprintf("SELECT user_movies.*, movieinfo.title FROM user_movies LEFT OUTER JOIN movieinfo ON movieinfo.imdbid = usermovie.imdbid WHERE user_movies.user_id = %d AND user_movies.imdbid = %d", $uid, $imdbid));
	}

	/**
	 * Delete all movies for a user.
	 *
	 * @param int $uid
	 */
	public function delMovieForUser($uid)
	{
		$this->pdo->queryExec(sprintf("DELETE FROM user_movies WHERE user_id = %d", $uid));
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
		$catid = (!empty($catid)) ? $this->pdo->escapeString(implode('|', $catid)) : "NULL";
		$this->pdo->queryExec(sprintf("UPDATE user_movies SET categoryid = %s WHERE user_id = %d AND imdbid = %d", $catid, $uid, $imdbid));
	}
}
