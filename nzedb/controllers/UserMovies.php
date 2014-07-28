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
	public function __construct(array $options = array())
	{
		$defaults = [
			'Settings' => null,
		];
		$defaults = array_replace($defaults, $options);

		$this->pdo = ($defaults['Settings'] instanceof Settings ? $defaults['Settings'] : new Settings());
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
	public function addMovie($uid, $imdbid, $catid=array())
	{
		$catid = (!empty($catid)) ? $this->pdo->escapeString(implode('|', $catid)) : "NULL";
		return $this->pdo->queryInsert(sprintf("INSERT INTO usermovies (userid, imdbid, categoryid, createddate) VALUES (%d, %d, %s, NOW())", $uid, $imdbid, $catid));
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
		return $this->pdo->query(sprintf("SELECT usermovies.*, movieinfo.year, movieinfo.plot, movieinfo.cover, movieinfo.title FROM usermovies LEFT OUTER JOIN movieinfo ON movieinfo.imdbid = usermovies.imdbid WHERE userid = %d ORDER BY movieinfo.title ASC", $uid));
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
		return $this->pdo->queryExec(sprintf("DELETE FROM usermovies WHERE userid = %d AND imdbid = %d ", $uid, $imdbid));
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
		return $this->pdo->queryOneRow(sprintf("SELECT usermovies.*, movieinfo.title FROM usermovies LEFT OUTER JOIN movieinfo ON movieinfo.imdbid = usermovie.imdbid WHERE usermovies.userid = %d AND usermovies.imdbid = %d", $uid, $imdbid));
	}

	/**
	 * Delete all movies for a user.
	 *
	 * @param int $uid
	 */
	public function delMovieForUser($uid)
	{
		$this->pdo->queryExec(sprintf("DELETE FROM usermovies WHERE userid = %d", $uid));
	}

	/**
	 * Update a movie for a user.
	 *
	 * @param int   $uid
	 * @param int   $imdbid
	 * @param array $catid
	 */
	public function updateMovie($uid, $imdbid, $catid=array())
	{
		$catid = (!empty($catid)) ? $this->pdo->escapeString(implode('|', $catid)) : "NULL";
		$this->pdo->queryExec(sprintf("UPDATE usermovies SET categoryid = %s WHERE userid = %d AND imdbid = %d", $catid, $uid, $imdbid));
	}
}
