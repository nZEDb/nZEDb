<?php
require_once(WWW_DIR."/lib/framework/db.php");

class UserMovies
{
	public function addMovie($uid, $imdbid, $catid=array())
	{			
		$db = new DB();
		
		$catid = (!empty($catid)) ? $db->escapeString(implode('|', $catid)) : "null";
		
		$sql = sprintf("INSERT IGNORE INTO usermovies (userID, imdbID, categoryID, createddate) values (%d, %d, %s, now())", $uid, $imdbid, $catid);
		return $db->queryInsert($sql);		
	}	

	public function getMovies($uid)
	{			
		$db = new DB();
		$sql = sprintf("select usermovies.*, movieinfo.year, movieinfo.plot, movieinfo.cover, movieinfo.title from usermovies left outer join movieinfo on movieinfo.imdbID = usermovies.imdbID where userID = %d order by movieinfo.title asc", $uid);
		return $db->query($sql);		
	}	

	public function delMovie($uid, $imdbid)
	{			
		$db = new DB();
		$db->query(sprintf("delete from usermovies where userID = %d and imdbID = %d ", $uid, $imdbid));		
	}
	
	public function getMovie($uid, $imdbid)
	{			
		$db = new DB();
		$sql = sprintf("select usermovies.*, movieinfo.title from usermovies left outer join movieinfo on movieinfo.imdbID = usermovie.imdbID where usermovies.userID = %d and usermovies.imdbID = %d ", $uid, $imdbid);
		return $db->queryOneRow($sql);		
	}		

	public function delMovieForUser($uid)
	{			
		$db = new DB();
		$db->query(sprintf("delete from usermovies where userID = %d", $uid));		
	}	

	public function updateMovie($uid, $imdbid, $catid=array())
	{			
		$db = new DB();
		
		$catid = (!empty($catid)) ? $db->escapeString(implode('|', $catid)) : "null";
		
		$sql = sprintf("update usermovies set categoryID = %s where userID = %d and imdbID = %d", $catid, $uid, $imdbid);
		$db->query($sql);		
	}
}
