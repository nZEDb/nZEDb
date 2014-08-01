<?php

require_once nZEDb_LIBS . 'simple_html_dom.php';

class AEBN
{
	// Sets a Cookie
	public $cookie = null;

	// Sets a Searchterm
	public $searchterm = null;

	// URLS used in functions
	const AEBNSURL = "http://straight.theater.aebn.net";
	const AEBNGURL = "http://gay.theater.aebn.net";
	const TRAILINGSEARCH = "/dispatcher/fts?theaterId=13992&genreId=101&locale=en&count=30&imageType=Large&targetSearchMode=basic&isAdvancedSearch=false&isFlushAdvancedSearchCriteria=false&sortType=Relevance&userQuery=title%3A+%2B";
	const IF18 = "http://straight.theater.aebn.net/dispatcher/frontDoor?genreId=101&theaterId=13992&locale=en&refid=AEBN-000001";
	const TRAILERURL = "/dispatcher/previewPlayer?locale=en&theaterId=13992&genreId=101&movieId=";

	// Title is set in search()
	protected $title = null;

	// Directurl is set in search()
	protected $directurl = null;

	// Post Paramaters if any
	protected $postparams = null;

	// Trailing URL defined above
	protected $trailurl = null;

	// Specifies which site to search "straight or gay" defined in __construct
	protected $currentsite = "straight";

	// IF a search name matched.
	protected $searchfound = false;

	protected $whichsite = array();
	protected $response = null;
	protected $res = array();
	protected $html;

	/**
	 * Sets the variables that used throughout the class
	 *
	 */
	public function __construct()
	{
		$this->whichsite = array("straight" => self::AEBNSURL, "gay" => self::AEBNGURL);
		$this->html = new simple_html_dom();
		if (isset($this->cookie)) {
			@$this->_geturl();
		}
	}

	/**
	 * If they arent' removed from memory. Force them.
	 */
	public function __destruct()
	{
		$this->html->clear();
		unset($this->response);
		unset($this->res);
	}

	/**
	 * Gets Trailer URL .. will be processed in XXX insertswf
	 *
	 * @return array|bool
	 */
	public function _trailers()
	{
		if (!isset($this->response)) {
			return false;
		}
		$movieid = null;
		if ($ret = $this->html->find("a[itemprop=trailer]", 0)) {
			preg_match("/movieId=(?<movieid>\d+)&/", trim($ret->href), $matches);
			$movieid = $matches['movieid'];
			$this->res['trailers']['url'] =	$this->whichsite[$this->currentsite] . self::TRAILERURL . $movieid;
		} else {
			return false;
		}

		return $this->res;
	}

	/**
	 * Gets the front and back cover of the box
	 *
	 * @return array|bool
	 */
	public function _covers()
	{
		if (!isset($this->response)) {
			return false;
		}
		if ($ret = $this->html->find("img#boxImage", 0)) {
			$ret = trim($ret->src);
			$this->res['boxcover'] = str_replace("160w.jpg", "xlf.jpg", $ret);
			$this->res['backcover'] = str_replace("160w.jpg", "xlb.jpg", $ret);
		} else {
			return false;
		}

		return $this->res;
	}

	/**
	 * Gets the Genres "Categories".
	 *
	 * @return array|bool
	 */
	public function _genres()
	{
		if (!isset($this->response)) {
			return false;
		}
		if ($ret = $this->html->find("div.md-detailsCategories", 0)) {
			foreach ($ret->find("a[itemprop=genre]") as $genre) {
				$this->res['genres'][] = trim($genre->plaintext);
			}
		} else {
			return false;
		}

		return $this->res;
	}

	/**
	 * Gets the Cast Members "Stars" and Director if any
	 *
	 * @return array|bool
	 */
	public function _cast()
	{
		if (!isset($this->response)) {
			return false;
		}
		if ($ret = $this->html->find("div.starsFull", 0)) {
			foreach ($ret->find("span[itemprop=name]") as $star) {
				$this->res['cast'][] = trim($star->plaintext);
			}
		} else {
			if ($ret = $this->html->find("div.detailsLink", 0)) {
				foreach ($ret->find("span") as $star) {
					if (!preg_match("/More/", $star->plaintext) && !preg_match("/Stars/", $star->plaintext)) {
						$this->res['cast'][] = trim($star->plaintext);
					}
				}
			} else {
				return false;
			}
		}

		return $this->res;
	}

	/**
	 * Gets the product information
	 *
	 * @return array|bool
	 */
	public function _productinfo()
	{
		if (!isset($this->response)) {
			return false;
		}
		if ($ret = $this->html->find("div#md-detailsLeft", 0)) {
			foreach ($ret->find("div") as $div) {
				foreach ($div->find("span") as $span) {
					$span->plaintext = rawurldecode($span->plaintext);
					$span->plaintext = preg_replace("/&nbsp;/", "", $span->plaintext);
					$this->res['productinfo'][] = trim($span->plaintext);
				}
			}
			if (false !== $key = array_search("Running Time:", $this->res['productinfo'])) {
				unset($this->res['productinfo'][$key + 2]);
			}
			if (false !== $key = array_search("Director:", $this->res['productinfo'])) {
				$this->res['director'] = $this->res['productinfo'][$key + 1];
				unset($this->res['productinfo'][$key]);
				unset($this->res['productinfo'][$key + 1]);
			}
			$this->res['productinfo'] = array_chunk($this->res['productinfo'], 2, false);
		} else {
			return false;
		}

		return $this->res;
	}

	/**
	 * Gets the sypnosis "plot"
	 *
	 * @return array|bool
	 *
	 */
	public function _sypnosis()
	{
		if (!isset($this->response)) {
			return false;
		}
		if ($ret = $this->html->find("span[itemprop=about]", 0)) {
			if (is_null($ret)) {
				if ($ret = $this->html->find("div.movieDetailDescription", 0)) {
					$this->res['sypnosis'] = trim($ret->plaintext);
					$this->res['sypnosis'] = preg_replace("/Description:\s/", "", $this->res['plot']);
				} else {
					return false;
				}
			} else {
				$this->res['sypnosis'] = trim($ret->plaintext);
			}
		}

		return $this->res;
	}

	/**
	 * Searches for a XXX name
	 *
	 * @return bool
	 */
	public function search()
	{
		if (!isset($this->searchterm)) {
			return false;
		}
		$this->trailurl = self::TRAILINGSEARCH . urlencode($this->searchterm);
		if ($this->_geturl(false, $this->currentsite) === false) {
			return false;
		} else {
			if ($count = count($this->html->find("div.movie"))) {
				$i = 1;

				foreach ($this->html->find("div.movie") as $movie) {
					$string = "a#FTSMovieSearch_link_title_detail_" . $i;
					if ($ret = $movie->find($string, 0)) {
						similar_text($this->searchterm, trim($ret->title), $p);
						if ($p >= 90) {
							$this->title = trim($ret->title);
							$this->trailurl = $ret->href;
							$this->directurl = $this->whichsite[$this->currentsite] . $this->trailurl;
							$this->_geturl(false, $this->currentsite);
							break;
						} else {
							continue;
						}
					}
					$i = $i + 1;
				}
				if ($i === $count OR $count === 0) {
					if ($this->currentsite === "gay") {
						return false;
					}
					$this->currentsite = "gay";
					$this->search();
				}
			} else {
				return false;
			}
		}
		if(!isset($this->title)){
		return false;
		}
	}

	/**
	 * Gets all the information
	 *
	 * @return array|bool
	 */
	public function _getall()
	{
		$results = array();
		if (isset($this->directurl)) {
			$results['title'] = $this->title;
			$results['directurl'] = $this->directurl;
		}
		if (is_array($this->_sypnosis())) {
			$results = array_merge($results, $this->_sypnosis());
		}
		if (is_array($this->_productinfo())) {
			$results = array_merge($results, $this->_productinfo());
		}
		if (is_array($this->_cast())) {
			$results = array_merge($results, $this->_cast());
		}
		if (is_array($this->_genres())) {
			$results = array_merge($results, $this->_genres());
		}
		if (is_array($this->_covers())) {
			$results = array_merge($results, $this->_covers());
		}
		if (is_array($this->_trailers())) {
			$results = array_merge($results, $this->_trailers());
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
	private function _geturl($usepost = false, $site = "straight")
	{
		if (isset($this->trailurl)) {
			$ch = curl_init($this->whichsite[$site] . $this->trailurl);
		} else {
			$ch = curl_init(self::IF18);
		}

		if ($usepost === true) {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $this->postparams);
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
		$this->response = curl_exec($ch);
		if (!$this->response) {
			curl_close($ch);

			return false;
		}
		curl_close($ch);
		$this->html->load($this->response);

		return true;
	}
}
