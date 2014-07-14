<?php

use nzedb\db\Settings;

class UserMovies
{
	function __construct()
	{
		$this->pdo = new Settings();
	}

	public function addMovie($uid, $imdbid, $catid=array())
	{
		$pdo = $this->pdo;
		$catid = (!empty($catid)) ? $pdo->escapeString(implode('|', $catid)) : "NULL";
		return $pdo->queryInsert(sprintf("INSERT INTO usermovies (userid, imdbid, categoryid, createddate) VALUES (%d, %d, %s, NOW())", $uid, $imdbid, $catid));
	}

	public function getMovies($uid)
	{
		$pdo = $this->pdo;
		return $pdo->query(sprintf("SELECT usermovies.*, movieinfo.year, movieinfo.plot, movieinfo.cover, movieinfo.title FROM usermovies LEFT OUTER JOIN movieinfo ON movieinfo.imdbid = usermovies.imdbid WHERE userid = %d ORDER BY movieinfo.title ASC", $uid));
	}

	public function delMovie($uid, $imdbid)
	{
		$pdo = $this->pdo;
		$pdo->queryExec(sprintf("DELETE FROM usermovies WHERE userid = %d AND imdbid = %d ", $uid, $imdbid));
	}

	public function getMovie($uid, $imdbid)
	{
		$pdo = $this->pdo;
		return $pdo->queryOneRow(sprintf("SELECT usermovies.*, movieinfo.title FROM usermovies LEFT OUTER JOIN movieinfo ON movieinfo.imdbid = usermovie.imdbid WHERE usermovies.userid = %d AND usermovies.imdbid = %d", $uid, $imdbid));
	}

	public function delMovieForUser($uid)
	{
		$pdo = $this->pdo;
		$pdo->queryExec(sprintf("DELETE FROM usermovies WHERE userid = %d", $uid));
	}

	public function updateMovie($uid, $imdbid, $catid=array())
	{
		$pdo = $this->pdo;
		$catid = (!empty($catid)) ? $pdo->escapeString(implode('|', $catid)) : "NULL";
		$pdo->queryExec(sprintf("UPDATE usermovies SET categoryid = %s WHERE userid = %d AND imdbid = %d", $catid, $uid, $imdbid));
	}
}
