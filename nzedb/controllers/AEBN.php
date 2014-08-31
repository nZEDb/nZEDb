<?php

require_once nZEDb_LIBS . 'simple_html_dom.php';

class AEBN
{
	/**
	 * Cookie File location used in curl
	 *
	 * @var string
	 */
	public $cookie = "";

	/**
	 * Keyword to search
	 *
	 * @var string
	 */
	public $searchTerm = "";

	/**
	 * Url Constants used within this class
	 */
	const AEBNGURL = "http://gay.theater.aebn.net";
	const AEBNSURL = "http://straight.theater.aebn.net";
	const IF18 = "http://straight.theater.aebn.net/dispatcher/frontDoor?genreId=101&theaterId=13992&locale=en&refid=AEBN-000001";
	const TRAILINGSEARCH = "/dispatcher/fts?theaterId=13992&genreId=101&locale=en&count=30&imageType=Large&targetSearchMode=basic&isAdvancedSearch=false&isFlushAdvancedSearchCriteria=false&sortType=Relevance&userQuery=title%3A+%2B";
	const TRAILERURL = "/dispatcher/previewPlayer?locale=en&theaterId=13992&genreId=101&movieId=";

	/**
	 * Sets the current site to search
	 * @var string
	 */
	protected $_currentSite = "straight";

	/**
	 * Direct Url in getAll method
	 * @var string
	 */
	protected $_directUrl = "";

	/**
	 * Simple Html Dom Object
	 * @var simple_html_dom
	 */
	protected $_html;

	/**
	 * Post Parameters to use with curl
	 *
	 * @var array
	 */
	protected $_postParams = array();

	/**
	 * Raw Html response from curl
	 *
	 */
	protected $_response;

	/**
	 * Returned results in all methods except search/geturl
	 *
	 * @var array
	 */
	protected $_res = array();

	/**
	 * If searchTerm is found
	 * @var bool
	 */
	protected $_searchFound = false;

	/**
	 * Sets title in getAll method
	 * @var string
	 */
	protected $_title = "";

	/**
	 * Trailing Url
	 * @var string
	 */
	protected $_trailUrl = "";

	/**
	 * Used in __construct
	 * @var array - straight, gay
	 */
	protected $_whichSite = array();



	/**
	 * Sets the variables that used throughout the class
	 *
	 */
	public function __construct()
	{
		$this->_whichSite = array("straight" => self::AEBNSURL, "gay" => self::AEBNGURL);
		$this->_html = new \simple_html_dom();
		if (isset($this->cookie)) {
			$this->getUrl();
		}
	}

	/**
	 * If they arent' removed from memory. Force them.
	 */
	public function __destruct()
	{
		$this->_html->clear();
		unset($this->_response);
		unset($this->_res);
	}

	/**
	 * Gets Trailer URL .. will be processed in XXX insertswf
	 *
	 * @return array|bool
	 */
	public function trailers()
	{
		if (!isset($this->_response)) {
			return false;
		}
		$movieid = null;
		if ($ret = $this->_html->find("a[itemprop=trailer]", 0)) {
			preg_match('/movieId=(?<movieid>\d+)&/', trim($ret->href), $matches);
			$movieid = $matches['movieid'];
			$this->_res['trailers']['url'] = $this->_whichSite[$this->_currentSite] . self::TRAILERURL . $movieid;
		} else {
			return false;
		}

		return $this->_res;
	}

	/**
	 * Gets the front and back cover of the box
	 *
	 * @return array
	 */
	public function covers()
	{
		if ($ret = $this->_html->find("img#boxImage, img[itemprop=thumbnailUrl]", 1)) {
			$ret = trim($ret->src);
			$this->_res['boxcover'] = str_ireplace("160w.jpg", "xlf.jpg", $ret);
			$this->_res['backcover'] = str_ireplace("160w.jpg", "xlb.jpg", $ret);
		}

		return $this->_res;
	}

	/**
	 * Gets the Genres "Categories".
	 *
	 * @return array
	 */
	public function genres()
	{
		if ($ret = $this->_html->find("div.md-detailsCategories", 0)) {
			foreach ($ret->find("a[itemprop=genre]") as $genre) {
				$this->_res['genres'][] = trim($genre->plaintext);
			}
		}

		return $this->_res;
	}

	/**
	 * Gets the Cast Members "Stars" and Director if any
	 *
	 * @return array
	 */
	public function cast()
	{
		if ($ret = $this->_html->find("div.starsFull", 0)) {
			foreach ($ret->find("span[itemprop=name]") as $star) {
				$this->_res['cast'][] = trim($star->plaintext);
			}
		} else {
			if ($ret = $this->_html->find("div.detailsLink", 0)) {
				foreach ($ret->find("span") as $star) {
					if (!preg_match("/More/", $star->plaintext) && !preg_match("/Stars/", $star->plaintext)) {
						$this->_res['cast'][] = trim($star->plaintext);
					}
				}
			}
		}

		return $this->_res;
	}

	/**
	 * Gets the product information
	 *
	 * @return array
	 */
	public function productInfo()
	{
		if ($ret = $this->_html->find("div#md-detailsLeft", 0)) {
			foreach ($ret->find("div") as $div) {
				foreach ($div->find("span") as $span) {
					$span->plaintext = rawurldecode($span->plaintext);
					$span->plaintext = preg_replace("/&nbsp;/", "", $span->plaintext);
					$this->_res['productinfo'][] = trim($span->plaintext);
				}
			}
			if (false !== $key = array_search("Running Time:", $this->_res['productinfo'])) {
				unset($this->_res['productinfo'][$key + 2]);
			}
			if (false !== $key = array_search("Director:", $this->_res['productinfo'])) {
				$this->_res['director'] = $this->_res['productinfo'][$key + 1];
				unset($this->_res['productinfo'][$key]);
				unset($this->_res['productinfo'][$key + 1]);
			}
			$this->_res['productinfo'] = array_chunk($this->_res['productinfo'], 2, false);
		}

		return $this->_res;
	}

	/**
	 * Gets the sypnosis "plot"
	 *
	 * @return array
	 *
	 */
	public function sypnosis()
	{
		if ($ret = $this->_html->find("span[itemprop=about]", 0)) {
			if (is_null($ret)) {
				if ($ret = $this->_html->find("div.movieDetailDescription", 0)) {
					$this->_res['sypnosis'] = trim($ret->plaintext);
					$this->_res['sypnosis'] = preg_replace('/Description:\s/', "", $this->_res['plot']);
				}
			} else {
				$this->_res['sypnosis'] = trim($ret->plaintext);
			}
		}

		return $this->_res;
	}

	/**
	 * Searches for a XXX name
	 *
	 * @return bool
	 */
	public function search()
	{
		if (!isset($this->searchTerm)) {
			return false;
		}
		$this->_trailUrl = self::TRAILINGSEARCH . urlencode($this->searchTerm);
		if ($this->getUrl(false, $this->_currentSite) === false) {
			return false;
		} else {
			if ($count = count($this->_html->find("div.movie"))) {
				$i = 1;
				foreach ($this->_html->find("div.movie") as $movie) {
					$string = "a#FTSMovieSearch_link_title_detail_" . $i;
					if ($ret = $movie->find($string, 0)) {
						$title = trim($ret->title);
						$title = preg_replace('/XXX/', '', $title);
						$title = preg_replace('/\(.*?\)|[-._]/i', ' ', $title);
						similar_text($this->searchTerm, $title, $p);
						if ($p >= 90) {
							$this->_title = trim($ret->title);
							$this->_trailUrl = $ret->href;
							$this->_directUrl = $this->_whichSite[$this->_currentSite] . $this->_trailUrl;
							$this->getUrl(false, $this->_currentSite);
							break;
						} else {
							continue;
						}
					}
					$i = $i + 1;
				}
				if ($i === $count OR $count === 0) {
					if ($this->_currentSite === "gay") {
						return false;
					}
					$this->_currentSite = "gay";
					$this->search();
				}
			} else {
				return false;
			}
		}

		return false;
	}

	/**
	 * Gets all the information
	 *
	 * @return array|bool
	 */
	public function getAll()
	{
		$results = array();
		if (isset($this->_directUrl)) {
			$results['title'] = $this->_title;
			$results['directurl'] = $this->_directUrl;
		}
		if (is_array($this->sypnosis())) {
			$results = array_merge($results, $this->sypnosis());
		}
		if (is_array($this->productinfo())) {
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
		if (is_array($this->trailers())) {
			$results = array_merge($results, $this->trailers());
		}
		if (empty($results) === true) {
			return false;
		} else {
			return $results;
		}
	}

	/**
	 * Get Raw html of webpage
	 *
	 * @param bool $usepost
	 * @param string $site
	 *
	 * @return bool
	 */
	private function getUrl($usepost = false, $site = "straight")
	{
		if (isset($this->_trailUrl)) {
			$ch = curl_init($this->_whichSite[$site] . $this->_trailUrl);
		} else {
			$ch = curl_init(self::IF18);
		}

		if ($usepost === true) {
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
		curl_close($ch);
		$this->_html->load($this->_response);

		return true;
	}
}
