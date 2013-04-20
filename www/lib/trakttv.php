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
	
	public function trakTVlookup($showtitle='', $season='', $eptitle='')
	{
		$chars = array(' ', '_', '.');
		$showtitle = str_replace($chars, '-', $showtitle);
		$TVurl = 'http://api.trakt.tv/show/episode/summary.json/'.$this->APIKEY.'/'.$showtitle.'/'.$season.'/'.$eptitle;
		$TVjson = file_get_contents($TVurl, 0, null, null);
		$TVarray = json_decode($TVjson, true);
		
		print_r($TVarray);
	}
}
