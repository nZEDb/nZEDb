<?php

require_once nZEDb_LIBS . 'simple_html_dom.php';

class ADM
{
	/**
	 * Override if 18 years+ or older
	 * Define Adult DVD Marketplace url
	 * Needed Search Queries Constant
	 */
	const ADMURL			= "http://www.adultdvdmarketplace.com";
	const IF18				= "http://www.adultdvdmarketplace.com/xcart/adult_dvd/disclaimer.php?action=enter&site=intl&return_url=";
	const TRAILINGSEARCH	= "/xcart/adult_dvd/advanced_search.php?sort_by=relev&title=";

	/**
	 * Define a cookie file location for curl
	 * @var string string
	 */
	public $cookie = "";

	/**
	 * Direct Link given from outside url doesn't do a search
	 * @var string
	 */
	public $directLink = "";

	/**
	 * Set this for what you are searching for.
	 * @var string
	 */
	public $searchTerm = "";

	/**
	 * Sets the directurl for the return results array
	 * @var string
	 */
	protected $_directUrl = "";

	/**
	 * Simple Html Dom Object
	 *
	 * @var simple_html_dom
	 */
	protected $_html;

	/**
	 * POST Paramaters for getUrl Method
	 */
	protected $_postParams;

	/**
	 * Results returned from each method
	 *
	 * @var array
	 */
	protected $_res = array();

	/**
	 * Curl Raw Html
	 */
	protected $_response;

	/**
	 * Add this to popurl to get results
	 * @var string
	 */
	protected $_trailUrl = "";

	/**
	 * This is set in the getAll method
	 *
	 * @var string
	 */
	protected $_title = "";

	public function __construct()
	{
		$this->_html = new \simple_html_dom();
		if (isset($this->cookie)) {
			$this->getUrl();
		}
	}

	/**
	 * Remove from memory.
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
		if ($ret = $this->_html->find('img[rel=license]', 0)) {
			if (isset($ret->next_sibling()->href)) {
				if (preg_match('/filename\=(?<covers>(.*))\'/i', $ret->next_sibling()->href, $matches)
				) {
					$this->_res['boxcover'] = $matches['covers'];
					$this->_res['backcover'] = preg_replace('/front/i', 'back', $matches['covers']);
				}
			}
		}

		return $this->_res;
	}

	/**
	 * Gets the sypnosis
	 * @return array
	 */
	public function sypnosis()
	{
		if ($ret = $this->_html->find('span[itemprop=description]', 0)) {
			if(preg_match('/(?<tagline>\<b\>(.*)\<\/b\>)/i', $ret->innertext, $matches)){
				$this->_res['tagline'] = trim(strip_tags($matches['tagline']));
				$ret->plaintext = str_replace($matches['tagline'], '', $ret->innertext);
			}
			$this->_res['sypnosis'] = trim(strip_tags($ret->plaintext,"<br>"));
		} else {
			$this->_res['sypnosis'] = "N/A";
		}

		return $this->_res;
	}

	/**
	 * Get Product Informtion and Director
	 *
	 *
	 * @return array
	 */
	public function productInfo()
	{
		foreach ($this->_html->find('td.DarkGrayTable') as $category) {
			switch (trim($category->plaintext)) {
				case "DIRECTOR":
					$this->_res['director'] = trim($category->next_sibling()->plaintext);
					break;
				case "FORMAT":
				case "STUDIO":
				case "RELEASED":
				case "SKU":
					$this->_res['productinfo'][$category->plaintext] = trim($category->next_sibling()->plaintext);
					break;
			}
		}

		return $this->_res;
	}

	/**
	 * Gets the cast members
	 * @return array
	 */
	public function cast()
	{
		$cast = array();
		foreach ($this->_html->find('td.section_heading') as $category) {
			if (trim($category->plaintext) == "CAST") {
				foreach ($this->_html->find('td.GrayDialogBody') as $td) {
					foreach ($td->find('a') as $actor) {
						if (preg_match_all('/search_performerid/', $actor->href, $matches)) {
							$cast[] = trim($actor->plaintext);
						}
					}
				}
			}
		}
		$this->_res['cast'] = array_unique($cast);

		return $this->_res;
	}

	/**
	 * Gets categories
	 * @return array
	 */
	public function genres()
	{
		$genres = array();
		foreach ($this->_html->find('td.DarkGrayTable') as $category) {
			if (trim($category->plaintext) == "CATEGORY") {
				foreach ($category->next_sibling()->find('a') as $e) {
					$genres[] = trim($e->plaintext);
				}
				$this->_res['genres'] = & $genres;
			}
		}

		return $this->_res;
	}

	/**
	 * Searches for match against searchterm
	 * @return bool, true if search = 100%
	 */
	public function search()
	{
		$result = false;
		if (isset($this->searchTerm)) {
			$this->_trailUrl = self::TRAILINGSEARCH . urlencode($this->searchTerm);
			if ($this->getUrl() !== false) {
				if ($ret = $this->_html->find('img[rel=license]')) {
					if (count($ret) > 0) {
						foreach ($this->_html->find('img[rel=license]') as $ret) {
							if (isset($ret->alt)) {
								$title = trim($ret->alt, '"');
								$title = preg_replace('/XXX/', '', $title);
								$comparetitle = preg_replace('/[^\w]/', '', $title);
								$comparesearch = preg_replace('/[^\w]/', '', $this->searchTerm);
								similar_text($comparetitle, $comparesearch, $p);
								if ($p == 100) {
									if(preg_match('/\/(?<sku>\d+)\.jpg/i', $ret->src, $matches)){
									$this->_title = trim($title);
									$this->_trailUrl = "/dvd_view_" . (string)$matches['sku'] . ".html";
									$this->_directUrl = self::ADMURL . $this->_trailUrl;
										if($this->getUrl() !== false){
										$result = true;
										}
									}
								}
							}
						}
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Gets all information
	 * @return array
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

		$results = empty($results) ? false : $results;
		return $results;
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
		if (isset($this->_trailUrl)) {
			$ch = curl_init(self::ADMURL . $this->_trailUrl);
		} else {
			$ch = curl_init(self::IF18);
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

		curl_setopt_array($ch, nzedb\utility\Utility::curlSslContextOptions());
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
