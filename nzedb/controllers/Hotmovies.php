<?php

require_once nZEDb_LIBS . 'simple_html_dom.php';

class Hotmovies
{

	/**
	* Keyword Search.
	* @var string
	*/
	public $searchTerm = "";

	/**
	 * Define a cookie location
	 *
	 * @var string
	 */
	public $cookie = "";

	/**
	 * If a direct link is set parse it instead of search for it.
	 *
	 * @var string
	 */
    public $directLink = "";

	/**
	 * Constant Urls used within this class
	 * Needed Search Queries Variables
	*/
	const EXTRASEARCH = "&complete=on&search_in=video_title";
	const HMURL = "http://www.hotmovies.com";
	const IF18 = true;
	const TRAILINGSEARCH = "/search.php?words=";

	/**
	 * Sets the direct url in the getAll method
	 * @var string
	 */
	protected $_directUrl = "";

	/**
	 * Sets the link to get in curl
	 * @var string
	 */
	protected $_getLink = "";

	/**
	 * Simple Html Dom Object
	 * @var simple_html_dom
	 */
	protected $_html;

	/**
	 * POST parameters used with curl
	 *
	 * @var array
	 */
	protected $_postParams = array();

	/**
	 * Results return from some methods
	 * @var array
	 */
	protected $_res = array();

	/**
	 * Raw Html from Curl
	 *
	 */
	protected $_response;

	/**
	 * Sets the title in the getAll method
	 * @var string
	 */
	protected $_title = "";



	public function __construct()
	{
		$this->_html = new \simple_html_dom();

		// Set a cookie to override +18 warning.
		if (isset($this->cookie)) {
			@$this->getUrl();
		}
	}

	/*
	 * Remove from memory if it still exists
	 */
	public function __destruct()
	{
	$this->_html->clear();
	unset($this->_response);
	unset($this->_res);
	}
	/**
	 * Get Box Cover Images
	 * @return array - boxcover,backcover
	 */
	public function covers()
	{
		if ($ret = $this->_html->find('div#large_cover, img#cover', 1)) {
			$this->_res['boxcover'] = trim($ret->src);
			$this->_res['backcover'] = str_ireplace(".cover",".back",trim($ret->src));
		}else{
			return false;
		}
		return $this->_res;
	}

	/**
	 * Gets the sypnosis
	 * @return array
	 */
	public function sypnosis()
	{
		if ($this->_html->find('.desc_link', 0)) {
			preg_match("/var descfullcontent = (?<content>.*)/",$this->_response,$matches);
			if (is_array($matches)) {
				$this->_res['sypnosis'] = rawurldecode($matches['content']);
			}
		}
		return $this->_res;
	}

	/**Process ProductInfo
	 *
	 * @return array
	 */
	public function productInfo()
	{
		$studio = false;
		$director = false;
		if ($ret = $this->_html->find('div.page_video_info', 0)) {
			foreach ($ret->find("text") as $e) {
				$e = trim($e->innertext);
				$e = str_replace(",", "", $e);
				$e = str_replace("...", "", $e);
				$e = str_replace("&nbsp;", "", $e);
				if (stristr($e, "Studio:")) {
					$studio = true;
				}
				if (stristr($e, "Director:")) {
					$director = true;
					$e = null;
				}
				if ($studio === true) {
					if (!stristr($e, "Custodian of Records")) {
						if (!stristr($e, "Description")) {

							if ($director === true && !empty($e)) {
								$this->_res['director'] = $e;
								$e = null;
								$director = false;
							}
							if (!empty($e)) {
								$this->_res['productinfo'][] = $e;
							}
						} else {
							break;
						}
					} else {
						break;
					}
				}
			}
		}
		if (is_array($this->_res['productinfo'])) {
			$this->_res['productinfo'] = array_chunk($this->_res['productinfo'], 2, false);
		}

		return $this->_res;
	}

	/**
	 * Gets the cast members and director
	 * @return array
	 */
	public function cast()
	{
		$cast = null;
		if ($this->_html->find('a[itemprop=actor]')) {
			foreach ($this->_html->find('a[itemprop=actor]') as $e) {
				$e= trim($e->title);
				$e = preg_replace('/\((.*)\)/',"",$e);
				$cast[] = trim($e);
				}
			$this->_res['cast'] = & $cast;

		}

		return $this->_res;
	}

	/**
	 * Gets categories
	 * @return array
	 */
	public function genres()
	{
		$genres = array();
		if ($ret = $this->_html->find('div.categories',0)) {
			foreach ($ret->find('a') as $e) {
				if(stristr($e->title,"->")){
				$e = explode("->",$e->plaintext);
				$genres[] = trim($e[1]);
			}
			}
			$this->_res['genres'] = & $genres;

		}

		return $this->_res;
	}

	/**
	 * Directly gets the link if directlink is set, and parses it.
	 *
	 * @return array
	 */
	public function getDirect()
	{
		if (isset($this->directlink)) {
			if ($this->getUrl() === false) {
				return false;
			} else {
				return $this->getAll();
			}
		}
		return false;
	}


	/**
	 * Searches for match against searchterm
	 * @return bool, true if search >= 90%
	 */
	public function search()
	{
		if (!isset($this->searchTerm)) {
			return false;
		}
		$this->_getLink = self::HMURL . self::TRAILINGSEARCH . urlencode($this->searchTerm) . self::EXTRASEARCH;
		if ($this->getUrl() === false) {
			return false;
		} else {
			if ($ret = $this->_html->find('h3[class=title]', 0)) {
				if($ret->find('a[title]',0)){
					$ret = $ret->find('a[title]', 0);
					$title = trim($ret->title);
					$title = preg_replace('/XXX/', '', $title);
					$title = preg_replace('/\(.*?\)|[-._]/i', ' ', $title);
					$this->_getLink = trim($ret->href);
					$this->_directUrl = trim($ret->href);
				   }
			} else {
				return false;
			}
			if (isset($title)) {
				similar_text($this->searchTerm, $title, $p);
				if ($p >= 90) {
					$this->_title = $title;
					// 90$ match found, load the url to start parsing
					$this->getUrl();
					unset($ret);

					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}
	}

	/**
	 * Gets all information
	 * @return array
	 */
	public function getAll()
	{
		$results = array();
		if(isset($this->_directUrl)){
		$results['title'] = $this->_title;
		$results['directurl'] = $this->_directUrl;
		}
		if (is_array($this->sypnosis())) {
			$results = array_merge($results, $this->sypnosis());
		}
		if (is_array($this->productInfo())) {
			$results = array_merge($results, $this->productInfo());
		}
		if (is_array($this->cast())) {
			$results = array_merge($results, $this->cast());
		}
		if (is_array($this->genres())) {
			$results = array_merge($results, $this->genres());
		}
		if (is_array($this->covers())) {
			$results = array_merge($results, $this->covers());
		}

		if(empty($results) === true){
		return false;
		}else{
		return $results;
		}
	}

	/**
	 * Get Raw html of webpage
	 *
	 * @param bool $usepost
	 *
	 * @return bool
	 */
	private function getUrl($usepost = false)
	{
		if (isset($this->_getLink)) {
			$ch = curl_init($this->_getLink);
		} else {
			$ch = curl_init(self::HMURL);
		}
		if(isset($this->directLink)){
			$ch = curl_init($this->directLink);
			$this->directLink = "";
		}
		if($usepost === true){
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_postParams);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_VERBOSE, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_USERAGENT, "Firefox/2.0.0.1");
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);
		if (isset($this->cookie)) {
		curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie);
		}
		$this->_response = curl_exec($ch);
		if (!$this->_response) {
			curl_close($ch);

			return false;
		}
		$this->_html->load($this->_response);
		curl_close($ch);
		return true;
	}
}
