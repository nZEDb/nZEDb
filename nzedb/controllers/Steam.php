<?php

require_once nZEDb_LIBS . 'simple_html_dom.php';

Class Steam
{

	#http://store.steampowered.com/search/?snr=1_7_7_151_12&term=ghost%20ship&category1=998&os=win&sort_order=ASC&page=1

	const STEAMURL = "http://store.steampowered.com";
	const STEAMVARS = "/search/?category1=998&os=win&sort_order=ASC&page=1&term=";
	const AGECHECKURL = "http://store.steampowered.com/agecheck/app/";
	const GAMEURL = "http://store.steampowered.com/app/";
	const CDNURL = "http://cdn.akamai.steamstatic.com/steam/apps/";

	public $searchterm = null;
	public $cookie = null;

	protected $title = null;
	protected $directurl = null;
	protected $ch;
	protected $totalresults = 0;
	protected $ret;
	protected $html;
	protected $edithtml;
	protected $response;
	protected $steamgameid;
	protected $postparams = array();
	protected $res = array();
	protected $birthtime = false;
	protected $lastagecheck = false;
	protected $indirecturl = null;

	public function __construct()
	{
		$this->html = new simple_html_dom();
		$this->edithtml = new simple_html_dom();
		if (isset($this->cookie)) {
			@_geturl(self::STEAMURL);
		}
	}

	/*
	 * Remove from memory if they weren't removed
	 *
	 */
	public function __destruct()
	{
		$this->html->clear();
		$this->edithtml->clear();
		unset($this->response);
		unset($this->tmprsp);
	}

	public function gamedescription()
	{
		if (isset($this->response) && isset($this->title)) {
			if ($ret = $this->html->find("div.game_description_snippet", 0)) {
				$this->res['description'] = trim($ret->plaintext);

				return $this->res;
			}
		}
	}

	public function gamerequirements()
	{
		if (isset($this->response) && isset($this->title)) {
			if ($ret = $this->html->find("div#game_area_sys_req_leftCol", 0)) {
				foreach ($ret->find("li") as $req) {
					$this->res['gamerequirements']['minimum'][] = trim($req->plaintext);
				}
				if (false !== $key = preg_grep("/Partner Requirements/",
											   $this->res['gamerequirements']['minimum'])
				) {
					$key = array_keys($key);
					if(isset($key[0])){
					unset($this->res['gamerequirements']['minimum'][$key[0]]);
					}
				}
			}

			if ($ret = $this->html->find("div#game_area_sys_req_rightCol", 0)) {
				foreach ($ret->find("li") as $req) {
					$this->res['gamerequirements']['recommended'][] = trim($req->plaintext);
				}
			}

			return $this->res;
		}
	}

	public function rating()
	{
		if (isset($this->response) && isset($this->title)) {
			if ($ret = $this->html->find("div#game_area_metascore", 0)) {
				$this->res['rating'] = (int)$ret->plaintext;
			}
		}

		return $this->res;
	}

	public function images()
	{
		if (isset($this->response) && isset($this->title)) {
			if ($ret = $this->html->find("img.game_header_image", 0)) {
				$this->res['cover'] = $ret->src;
			}
			if ($ret = $this->html->find("div.screenshot_holder", 0)) {
				if ($ret = $ret->find("a", 0)) {
					if(preg_match('/\?url\=(?<imgurl>.*)/', $ret->href, $matches)){
					$this->res['backdrop'] = trim($matches['imgurl']);
					}else{
					$this->res['backdrop'] = trim($ret->href);
					}

				}
			}
		}

		return $this->res;
	}

	public function details()
	{
		if (isset($this->response) && isset($this->title)) {
			if ($ret = $this->html->find("div.details_block", 0)) {
				foreach ($ret->find("br") as $b) {
					$ret = rtrim($b->next_sibling()->innertext, ":");
					$ret2 = trim($b->next_sibling()->next_sibling()->innertext);
					if ($ret !== "Languages") {
						if ($ret === "Release Date") {
							preg_match('/(?<releasedate>[0-9]{1,2}\s+(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+[0-9]{4})/i', $this->response, $matches);
							if(isset($matches['releasedate'])){
							$ret2 = $matches['releasedate'];
							}else{
								$ret2 = "Unknown";
							}
						}
						$this->res['gamedetails'][$ret] = $ret2;
					} else {
						break;
					}
				}
			}

			return $this->res;
		}
	}

	public function trailer()
	{
		if (isset($this->response) && isset($this->title)) {
			if (preg_match('/store.steampowered.com\/video\//', $this->response)) {
				$this->res['trailer'] = self::STEAMURL . '/video/' . $this->steamgameid;
				@$this->_geturl($this->res['trailer']);
				if (preg_match('@FILENAME\:\s+(?<videourl>\"\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))")@',
							   $this->response,
							   $matches)
				) {
					if (isset($matches['videourl'])) {
						$this->res['trailer'] = trim($matches['videourl'], '"');
					}
				}
			}else{
			if(preg_match('/movie480.webm\?t\=(?<videoidentifier>\d+)/', $this->response, $matches)){
				$this->res['trailer'] = self::CDNURL . $this->steamgameid . '/movie480.webm?t=' . trim($matches['videoidentifier'],'"');
			}
			}
		}

		return $this->res;
	}

	public function search()
	{
		if (!isset($this->searchterm)) {
			return false;
		}

		if ($this->_geturl(self::STEAMURL . self::STEAMVARS . rawurlencode($this->searchterm)) !==
			false
		) {
			if ($ret = $this->html->find("div.search_pagination_left", 1)) {
				if (preg_match('/\d+ of (?<total>\d+)/', trim($ret->plaintext), $matches)) {
					$this->totalresults = (int)$matches['total'];
				}
				if ($this->totalresults > 0) {
					foreach ($this->html->find("a.search_result_row") as $result) {
						if (preg_match('/\<h4\>(?<name>.*)\<\/h4\>/i',
									   $result->innertext,
									   $matches)
						) {
							$title = $matches['name'];
						}
						if (isset($title)) {
							similar_text($title, $this->searchterm, $p);
							if ($p > 90) {
								$this->title = $title;
								if (isset($result->href)) {
									preg_match('/\/app\/(?<id>\d+)\//', $result->href, $matches);
									$this->steamgameid = $matches['id'];
									$this->directurl = self::GAMEURL . $this->steamgameid . '/';
									@$this->_geturl($result->href);
									@$this->agecheck();
									break;
								} else {
									return false;
								}
							}
						}
					}
					if(empty($this->title)){
						return false;
					}
				} else {
					return false;
				}
			} else {
				return false;
			}
		}
	}


		/*
	 * Gets all Information.
	 *
	 * @return array
	 */
	public function _getall()
	{
		$results = array();
		if (isset($this->directurl)) {
			$results['steamgameid'] = $this->steamgameid;
			$results['directurl'] = $this->directurl;
			$results['title'] = $this->title;
		}
		if (is_array($this->gamedescription())) {
			$results = array_merge($results, $this->gamedescription());
		}
		if (is_array($this->gamerequirements())) {
			$results = array_merge($results, $this->gamerequirements());
		}
		if (is_array($this->details())) {
			$results = array_merge($results, $this->details());
		}
		if (is_array($this->rating())) {
			$results = array_merge($results, $this->rating());
		}
		if (is_array($this->images())) {
			$results = array_merge($results, $this->images());
		}
		if (is_array($this->trailer())) {
			$results = array_merge($results, $this->trailer());
		}

		return $results;
	}

	/**
	 * Do a agecheck Verification.
	 * If cookie has the lastagecheckage and birthtime values load game url

	 */
	private function agecheck()
	{
		if (isset($this->cookie)) {
			if ($this->extractCookies(file_get_contents($this->cookie)) === false) {
				$this->postparams = array(
					"snr" => "1_agecheck_agecheck__age-gate",
					"ageDay" => "1",
					"ageMonth" => "May",
					"ageYear" => "1966"
				);
				// Do twice so steam can set a birthtime/lastagecheckage cookie value
				@$this->_geturl(self::AGECHECKURL . $this->steamgameid . '/', true);
				@$this->_geturl(self::AGECHECKURL . $this->steamgameid . '/', true);
			}
		}
		@$this->_geturl(self::GAMEURL . $this->steamgameid . '/');
	}

	private function _geturl($fetchurl = null, $usepost = false)
	{
		if (isset($fetchurl)) {
			$this->ch = curl_init($fetchurl);
		}
		if ($usepost === true) {
			curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($this->ch, CURLOPT_POST, 1);
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->postparams);
		}
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->ch, CURLOPT_HEADER, 0);
		curl_setopt($this->ch, CURLOPT_VERBOSE, 0);
		curl_setopt($this->ch, CURLOPT_USERAGENT, "Firefox/2.0.0.1");
		curl_setopt($this->ch, CURLOPT_FAILONERROR, 1);
		if (isset($this->cookie)) {
			curl_setopt($this->ch, CURLOPT_COOKIEJAR, $this->cookie);
			curl_setopt($this->ch, CURLOPT_COOKIEFILE, $this->cookie);
		}
		$this->response = curl_exec($this->ch);
		if (!$this->response) {
			curl_close($this->ch);

			return false;
		}
		curl_close($this->ch);
		$this->html->load($this->response);

		return true;
	}

	/**
	 * Extract values from a cookie and sets the values needed with the information.
	 *
	 * @param string $string The contents of the cookie file.
	 *
	 * @return bool True/False if lastagecheckage and birthtime exists.
	 */
	private function extractCookies($string)
	{
		$cookies = array();

		$lines = explode("\n", $string);

		// iterate over lines
		foreach ($lines as $line) {

			// we only care for valid cookie def lines
			if (isset($line[0]) && substr_count($line, "\t") == 6) {

				// get tokens in an array
				$tokens = explode("\t", $line);

				// trim the tokens
				$tokens = array_map('trim', $tokens);

				$cookie = array();

				// Extract the data
				if ($tokens[0] == "store.steampowered.com") {
					if ($tokens[5] == 'birthtime') {
						$this->birthtime = true;
					}
					if ($tokens[5] == 'lastagecheckage') {
						$this->lastagecheck = true;
					}
				}
			}
		}
		if ($this->birthtime === true && $this->lastagecheck === true) {
			return true;
		} else {
			return false;
		}
	}
}
