<?php

use nzedb\db\DB;

class UserMovies
{
	function __construct()
	{
		$this->db = new DB();
	}

	public function addMovie($uid, $imdbid, $catid=array())
	{
		$db = $this->db;
		$catid = (!empty($catid)) ? $db->escapeString(implode('|', $catid)) : "NULL";
		return $db->queryInsert(sprintf("INSERT INTO usermovies (userid, imdbid, categoryid, createddate) VALUES (%d, %d, %s, NOW())", $uid, $imdbid, $catid));
	}

	public function getMovies($uid)
	{
		$db = $this->db;
		return $db->query(sprintf("SELECT usermovies.*, movieinfo.year, movieinfo.plot, movieinfo.cover, movieinfo.title FROM usermovies LEFT OUTER JOIN movieinfo ON movieinfo.imdbid = usermovies.imdbid WHERE userid = %d ORDER BY movieinfo.title ASC", $uid));
	}

	public function delMovie($uid, $imdbid)
	{
		$db = $this->db;
		$db->queryExec(sprintf("DELETE FROM usermovies WHERE userid = %d AND imdbid = %d ", $uid, $imdbid));
	}

	public function getMovie($uid, $imdbid)
	{
		$db = $this->db;
		return $db->queryOneRow(sprintf("SELECT usermovies.*, movieinfo.title FROM usermovies LEFT OUTER JOIN movieinfo ON movieinfo.imdbid = usermovie.imdbid WHERE usermovies.userid = %d AND usermovies.imdbid = %d", $uid, $imdbid));
	}

	public function delMovieForUser($uid)
	{
		$db = $this->db;
		$db->queryExec(sprintf("DELETE FROM usermovies WHERE userid = %d", $uid));
	}

	public function updateMovie($uid, $imdbid, $catid=array())
	{
		$db = $this->db;
		$catid = (!empty($catid)) ? $db->escapeString(implode('|', $catid)) : "NULL";
		$db->queryExec(sprintf("UPDATE usermovies SET categoryid = %s WHERE userid = %d AND imdbid = %d", $catid, $uid, $imdbid));
	}
}
