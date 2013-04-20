<?php
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/site.php");

/* 
*	Lookup information from trak.tv using their API.
*/
Class Trakttv
{
	function Trakttv()
	{
		$s = new Sites();
		$site = $s->get();
		$this->APIKEY = $site->trakttvkey;
	}
	
	//
	// Fetches information from trakt.tv for the TV show using the title.
	//
	public function traktTVlookup($showtitle='')
	{
		$chars = array(' ', '_', '.');
		$showtitle = str_replace($chars, '-', $showtitle);
		$TVurl = 'http://api.trakt.tv/show/summary.json/'.$this->APIKEY.'/'.$showtitle;
		$TVjson = file_get_contents($TVurl, 0, null, null);
		$TVarray = json_decode($TVjson, true);
		
		print_r($TVarray);
	}
	
	//
	// Fetches information from trakt.tv for the TV show using the title/season/episode.
	//
	public function traktTVSElookup($showtitle='', $season='', $ep='')
	{
		$chars = array(' ', '_', '.');
		$showtitle = str_replace($chars, '-', $showtitle);
		$TVurl = 'http://api.trakt.tv/show/episode/summary.json/'.$this->APIKEY.'/'.$showtitle.'/'.$season.'/'.$ep;
		$TVjson = file_get_contents($TVurl, 0, null, null);
		$TVarray = json_decode($TVjson, true);
		
		print_r($TVarray);
	}
	
	//
	// Fetches information from trakt.tv for the TV show using a TVDB ID.
	//
	public function traktTVDBlookup($tvdbid='')
	{
		$TVurl = 'http://api.trakt.tv/show/summary.json/'.$this->APIKEY.'/'.$tvdbid;
		$TVjson = file_get_contents($TVurl, 0, null, null);
		$TVarray = json_decode($TVjson, true);
		
		print_r($TVarray);
	}
	
	//
	// Fetches information from trakt.tv for the movie.
	// Accept a title (the-big-lebowski-1998), a IMDB id, or a TMDB id.
	//
	public function trakttMovieLookup($movie='')
	{
		$chars = array(' ', '_', '.');
		$movie = str_replace($chars, '-', $movie);
		$Movieurl = 'http://api.trakt.tv/show/movie/summary.json/'.$this->APIKEY.'/'.$movie;
		$Moviejson = file_get_contents($Movieurl, 0, null, null);
		$Moviearray = json_decode($Moviejson, true);
		
		print_r($Moviearray);
	}
}
