<?php

require_once nZEDb_LIBS . 'simple_html_dom.php';

class Popporn
{

	/**
	 * Define a cookie file location for curl
	 * @var string string
	 */
	public $cookie = "";

	/**
	 * Set this for what you are searching for.
	 * @var string
	 */
	public $searchTerm = "";

	/**
	 * Override if 18 years+ or older
	 * Define Popporn url
	 * Needed Search Queries Constant
	*/
	const IF18 = "http://www.popporn.com/popporn/4";
	const POPURL = "http://www.popporn.com";
	const TRAILINGSEARCH = "/results/index.cfm?v=4&g=0&searchtext=";

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
	 * POST Paramaters for Trailers Method
	 */
	protected $_postParams;

	/**
	 * Curl Raw Html
	 */
	protected $_response;

	/**
	 * Results returned from each method
	 *
	 * @var array
	 */
	protected $_res = array();

	/**
	 * This is set in the getAll method
	 *
	 * @var string
	 */
	protected $_title = "";

	/**
	 * Add this to popurl to get results
	 * @var string
	 */
	protected $_trailUrl = "";

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
		if ($ret = $this->_html->find('div[id=box-art], a[rel=box-art]', 1)) {
			$this->_res['boxcover'] = trim($ret->href);
			if (stristr(trim($ret->href), "_aa")) {
				$this->_res['backcover'] = str_ireplace("_aa", "_bb", trim($ret->href));
			} else {
				$this->_res['backcover'] = str_ireplace(".jpg", "_b.jpg", trim($ret->href));
			}
		} else {
			if ($ret = $this->_html->find('img.front', 0)) {
				$this->_res['boxcover'] = $ret->src;
			}
			if ($ret = $this->_html->find('img.back', 0)) {
				$this->_res['backcover'] = $ret->src;
		}
		}

		return $this->_res;
	}

	/**
	 * Gets the sypnosis
	 * @return array|bool
	 */
	public function sypnosis()
	{
		if ($ret = $this->_html->find('div[id=product-info] ,h3[class=highlight]', 1)) {
			if ($ret->next_sibling()->plaintext) {
				if (!stristr(trim($ret->next_sibling()->plaintext), "POPPORN EXCLUSIVE")) {
					$this->_res['sypnosis'] = trim($ret->next_sibling()->plaintext);
				} else {
					if ($ret->next_sibling()->next_sibling()->next_sibling()->plaintext) {
						$this->_res['sypnosis'] = trim($ret->next_sibling()->next_sibling()->next_sibling()->plaintext);
					} else {
						$this->_res['sypnosis'] = "N/A";
					}
				}
			}
		}

		return $this->_res;
	}

	/**
	 * Gets trailer video
	 * @return array|bool
	 */
	public function trailers()
	{
		if ($ret = $this->_html->find('input#thickbox-trailer-link', 0)) {
			$ret->value = trim($ret->value);
			$ret->value = str_replace("..", "", $ret->value);
			$tmprsp = $this->_response;
			$this->_trailUrl = $ret->value;
			$this->getUrl();
			if (preg_match_all('/productID="\+(?<id>[0-9]+),/', $this->_response, $matches)) {
				$productid = $matches['id'][0];
				$random = ((float)rand() / (float)getrandmax()) * 5400000000000000;
				$this->_trailUrl = "/com/tlavideo/vod/FlvAjaxSupportService.cfc?random=" . $random;
				$this->_postParams = "method=pipeStreamLoc&productID=" . $productid;
				$this->getUrl(true);
				$ret = json_decode(json_decode($this->_response, true), true);
				$this->_res['trailers']['baseurl'] = self::POPURL . "/flashmediaserver/trailerPlayer.swf";
				$this->_res['trailers']['flashvars'] = "subscribe=false&image=&file=" . self::POPURL . "/" . $ret['LOC'] . "&autostart=false";
				unset($this->_response);
				$this->_response = $tmprsp;
			}
		}

		return $this->_res;
	}

	/**
	 * Process ProductInfo And/or Extras
	 *
	 * @param bool $extras
	 *
	 * @return array|bool
	 */
	public function productInfo($extras = true)
	{
		$country = false;
		if ($ret = $this->_html->find('div#lside', 0)) {
			foreach ($ret->find("text") as $e) {
				$e = trim($e->innertext);
				$e = str_replace(",", "", $e);
				$e = str_replace("...", "", $e);
				$e = str_replace("&nbsp;", "", $e);
				if (stristr($e, "Country:")) {
					$country = true;
				}
				if ($country === true) {
					if (!stristr($e, "addthis_config")) {
						if (!empty($e)) {
							$this->_res['productinfo'][] = $e;
						}
					} else {
						break;
					}
				}
			}
		}

		$this->_res['productinfo'] = array_chunk($this->_res['productinfo'], 2, false);

		if ($extras === true) {
			$features = false;
			if ($this->_html->find('ul.stock-information', 0)) {
				foreach ($this->_html->find('ul.stock-information') as $ul) {
					foreach ($ul->find('li') as $e) {
						$e = trim($e->plaintext);
						if ($e == "Features:") {
							$features = true;
							$e = null;
						}
						if ($features == true) {
							if (!empty($e)) {
								$this->_res['extras'][] = $e;
							}
						}
					}
				}
			}
		}

		return $this->_res;
	}

	/**
	 * Gets the cast members and director
	 * @return array|bool
	 */
	public function cast()
	{
		$cast = false;
		$director = false;
		$er = array();
		if ($ret = $this->_html->find('div#lside', 0)) {
			foreach ($ret->find("text") as $e) {
				$e = trim($e->innertext);
				$e = str_replace(",", "", $e);
				$e = str_replace("&nbsp;", "", $e);
				if (stristr($e, "Cast")) {
					$cast = true;
				}
				$e = str_replace("Cast:", "", $e);
				if ($cast === true) {
					if (stristr($e, "Director:")) {
						$director = true;
						$e = null;
					}

					if ($director === true) {
						if (!empty($e)) {
							$this->_res['director'] = $e;
							$director = false;
							$e = null;
						}
					}
					if (!stristr($e, "Country:")) {
						if (!empty($e)) {
							$er[] = $e;
						}
					} else {
						break;
					}
				}
			}
			$this->_res['cast'] = & $er;
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
		if ($ret = $this->_html->find('div[id=thekeywords], p[class=keywords]', 1)) {
			foreach ($ret->find('a') as $e) {
				$genres[] = trim($e->plaintext);
			}
			$this->_res['genres'] = & $genres;
		}

		return $this->_res;
	}

	/**
	 * Searches for match against searchterm
	 * @return bool, true if search >= 90%
	 */
	public function search()
	{
		$result = false;
		if (isset($this->searchTerm)) {
			$this->_trailUrl = self::TRAILINGSEARCH . urlencode($this->searchTerm);
			if ($this->getUrl() !== false) {
				if ($ret = $this->_html->find('div.product-info, div.title', 1)) {
					$this->_title = trim($ret->plaintext);
					$title = preg_replace('/XXX/', '', $ret->plaintext);
					$title = preg_replace('/\(.*?\)|[-._]/i', ' ', $title);
					$title = trim($title);
					if ($ret = $ret->find('a', 0)) {
						$this->_trailUrl = trim($ret->href);
						if ($this->getUrl() !== false) {
							if ($ret = $this->_html->find('#link-to-this', 0)) {
								$this->_directUrl = trim($ret->href);
							}
							similar_text(strtolower($this->searchTerm), strtolower($title), $p);
							if ($p >= 90) {
								$result = true;
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
		if(isset($this->_directUrl)){
		$results['title'] = $this->_title;
		$results['directurl'] = $this->_directUrl;
		}
		if (is_array($this->sypnosis())) {
			$results = array_merge($results, $this->sypnosis());
		}
		if (is_array($this->productInfo(true))) {
			$results = array_merge($results, $this->productInfo(true));
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
		if (isset($this->_trailUrl)) {
			$ch = curl_init(self::POPURL . $this->_trailUrl);
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
