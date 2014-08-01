<?php

require_once nZEDb_LIBS . 'simple_html_dom.php';

/**
 * Class adultdvdempire
 */
class ADE
{
	/* Define ADE Url here */
	const ADE = "http://www.adultdvdempire.com";

	/* If a release matches define it as as true = gives callback to continue */
	public $found = null;

	/* Get and compare searchterm */
	public $searchterm = null;

	/* If a directlink is given retrieve and parse */
	public $directlink = null;

	/* Define param if trailing url is found get it and set it for future calls */
	/* Anything after the $ADE url is trailing */
	protected $urlfound = null;

	/* Sets the directurl for template */
	protected $directurl = null;

	/* Sets the true title found */
	protected $title = null;

	/* Trailing urls */
	protected $dvdquery = "/dvd/search?q=";
	protected $scenes = "/scenes";
	protected $boxcover = "/boxcover";
	protected $backcover = "/backcover";
	protected $reviews = "/reviews";
	protected $trailers = "/trailers";

	protected $url = null;
	protected $response = null;
	protected $res = array();
	protected $tmprsp = null;
	protected $html;
	protected $edithtml;
	protected $ch;

	public function __construct()
	{
		$this->html = new simple_html_dom();
		$this->edithtml = new simple_html_dom();
	}

	/*
	 * Remove from memory if they weren't removed
	 *
	 */
	public function __destruct(){
		$this->html->clear();
		$this->edithtml->clear();
		unset($this->response);
		unset($this->tmprsp);
	}

	/**
	 * Gets Trailer Movies -- Need layout change
	 * Todo: Make layout work with the player/Download swf?
	 * @return array|bool - url, streamid, basestreamingurl
	 */
	public function _trailers()
	{
		$this->_getadeurl($this->trailers . $this->urlfound);
		$this->html->load($this->response);
		if (preg_match("/(\"|')(?P<swf>[^\"']+.swf)(\"|')/i", $this->response, $matches)) {
			$this->res['trailers']['url'] = self::ADE . trim(trim($matches['swf']), '"');
			if (preg_match("#(?:streamID:\s\")(?P<streamid>[0-9A-Z]+)(?:\")#",
						   $this->response,
						   $matches)
			) {
				$this->res['trailers']['streamid'] = trim($matches['streamid']);
			}
			if (preg_match("#(?:BaseStreamingUrl:\s\")(?P<baseurl>[0-9]+.[0-9]+.[0-9]+.[0-9]+)(?:\")#",
						   $this->response,
						   $matches)
			) {
				$this->res['trailers']['baseurl'] = $matches['baseurl'];
			}
		} else {
			return false;
		}
		unset($matches);
		$this->html->clear();

		return $this->res;
	}

	/**
	 * Gets cover images for the xxx release
	 * @return array - Boxcover and backcover
	 */
	public function _covers()
	{
		$this->_getadeurl($this->boxcover . $this->urlfound);
		$this->html->load($this->response);
		foreach ($this->html->find("div[id=FrontBoxCover], img[itemprop=image]") as $img) {
			if (stristr($img->src, "h.jpg")) {
				$this->res['boxcover'] = $img->src;
				break;
			}
		}
		$this->_getadeurl($this->backcover . $this->urlfound);
		$this->html->load($this->response);
		foreach ($this->html->find("div[id=BackBoxCover], img[itemprop=image]") as $img) {
			if (stristr($img->src, "bh.jpg")) {
				$this->res['backcover'] = $img->src;
				break;
			}
		}
		unset($img);
		$this->html->clear();

		return $this->res;
	}

	/**
	 * Gets the sypnosis and tagline
	 *
	 * @param bool $tagline - Include tagline? true/false
	 *
	 * @return array - plot,tagline
	 */
	public function _sypnosis($tagline = false)
	{
		if ($tagline === true) {
			if($ret = $this->html->find("p.Tagline", 0)){
				if (!empty($ret->plaintext)) {
				$this->res['tagline'] = trim($ret->plaintext);
			}
			}
		}
		if ($ret = $this->html->find("p.Tagline", 0)->next_sibling()->next_sibling()) {
			$this->res['sypnosis'] = trim($ret->innertext);
		}

		return $this->res;
	}

	/**
	 * Gets the cast members and/or awards
	 *
	 * @param bool $awards - Include Awards? true/false
	 *
	 * @return array - cast,awards
	 */
	public function _cast($awards = false)
	{
		$this->tmprsp = str_ireplace("Section Cast", "scast", $this->response);
		$this->edithtml->load($this->tmprsp);


		if ($ret = $this->edithtml->find("div[class=scast]", 0)) {
		$this->tmprsp = trim($ret->outertext);
		$ret = $this->edithtml->load($this->tmprsp);
		foreach ($ret->find("a.PerformerName") as $a) {
			if ($a->plaintext != "(bio)") {
				if($a->plaintext != "(interview)"){
				$this->res['cast'][] = trim($a->plaintext);
				}
			}
		}
		if ($awards == true) {
			if ($ret->find("ul", 1)) {
				foreach ($ret->find("ul", 1)->find("li, strong") as $li) {
					$this->res['awards'][] = trim($li->plaintext);
				}
			}
		}

		//$this->res['director']= array_pop($this->res['cast']);
		$this->edithtml->clear();
		unset($ret);
		unset($this->tmprsp);

		return $this->res;
		}
	}

	/**
	 * Gets categories, if exists return array else return false
	 * @return mixed array|bool - Categories, false
	 */
	public function _genres()
	{
		$categories = null;
		$this->tmprsp = str_ireplace("Section Categories", "scat", $this->response);
		$this->edithtml->load($this->tmprsp);
		if($ret = $this->edithtml->find("div[class=scat]", 0)){
		$this->tmprsp = trim($ret->outertext);
		$ret = $this->edithtml->load($this->tmprsp);

		foreach ($ret->find("p, a") as $categories) {
			$categories = trim($categories->plaintext);
			if (stristr($categories, ",")) {
				$categories = explode(",", $categories);
				break;
			} else {
				return false;
			}
		}
		$categories = array_map('trim', $categories);
		$this->res['genres'] = $categories;
		}
		$this->edithtml->clear();
		unset($this->tmprsp);
		unset($ret);

		return $this->res;
	}

	/**
	 * Gets Product Information and/or Features
	 *
	 * @param bool $features Include features? true/false
	 *
	 * @return array - ProductInfo/Extras = features
	 */
	public function _productinfo($features = false)
	{
		$dofeature = null;
		$this->tmprsp = str_ireplace("Section ProductInfo", "spdinfo", $this->response);
		$this->edithtml->load($this->tmprsp);
		if($ret = $this->edithtml->find("div[class=spdinfo]", 0)){
		$this->tmprsp = trim($ret->outertext);
		$ret = $this->edithtml->load($this->tmprsp);
		foreach ($ret->find("text") as $strong) {
			if (trim($strong->innertext) == "Features") {
				$dofeature = true;
			}
			if ($dofeature != true) {
				if (trim($strong->innertext) != "&nbsp;") {
					$this->res['productinfo'][] = trim($strong->innertext);
				}
			} else {
				if ($features == true) {
					$this->res['extras'][] = trim($strong->innertext);
				}
			}
		}

		array_shift($this->res['productinfo']);
		array_shift($this->res['productinfo']);
		$this->res['productinfo'] = array_chunk($this->res['productinfo'], 2, false);
		}
		$this->edithtml->clear();
		unset($this->tmprsp);
		unset($ret);

		return $this->res;
	}

	/**
	 * Gets the direct link information and returns it
	 * @return array|bool
	 */
	public function getdirect()
	{
		if (isset($this->directlink)) {
			if ($this->_getadeurl() === false) {
				return false;
			} else {
				$this->html->load($this->response);
				return $this->_getall();
			}
		}
	}
	/**
	 * Searches xxx name.
	 * @return bool - True if releases has 90% match, else false
	 */
	public function search()
	{
		if (!isset($this->searchterm)) {
			return false;
		}
		if ($this->_getadeurl($this->dvdquery . rawurlencode($this->searchterm)) === false) {
			return false;
		} else {
			$this->html->load($this->response);
			if ($ret = $this->html->find("a.boxcover", 0)){
					$title = $ret->title;
					$ret = (string)trim($ret->href);
					similar_text($this->searchterm, $title, $p);
					if ($p >= 90) {
						$this->found = true;
						$this->urlfound = $ret;
						$this->directurl = self::ADE . $ret;
						$this->title = trim($title);
						unset($ret);
						$this->html->clear();
						$this->_getadeurl($this->urlfound);
						$this->html->load($this->response);
					} else {
						$this->found = false;

						return false;
					}
				} else {
					return false;
				}
		}
	}

	/**
	 * Gets raw html content using adeurl and any trailing url.
	 *
	 * @param null $trailing - required
	 *
	 * @return bool - true if page has content
	 */
	private function _getadeurl($trailing = null)
	{
		if (isset($trailing)) {
			$this->ch = curl_init(self::ADE . $trailing);
		}
		if (isset($this->directlink)) {
			$this->ch = curl_init($this->directlink);
			$this->directlink = null;
		}
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->ch, CURLOPT_HEADER, 0);
		curl_setopt($this->ch, CURLOPT_VERBOSE, 0);
		curl_setopt($this->ch, CURLOPT_USERAGENT, "Firefox/2.0.0.1");
		curl_setopt($this->ch, CURLOPT_FAILONERROR, 1);
		$this->response = curl_exec($this->ch);
		if (!$this->response) {
			curl_close($this->ch);

			return false;
		}
		curl_close($this->ch);
		return true;
	}



	/*
	 * Gets all Information.
	 *
	 * @return array
	 */
	public function _getall()
	{
		$results = array();
		if(isset($this->directurl)){
		$results['directurl'] = $this->directurl;
		$results['title'] = $this->title;
		}
		if (is_array($this->_sypnosis(true))) {
			$results = array_merge($results, $this->_sypnosis(true));
		}
		if (is_array($this->_productinfo(true))) {
			$results = array_merge($results, $this->_productinfo(true));
		}
		if (is_array($this->_cast(true))) {
			$results = array_merge($results, $this->_cast(true));
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
		return $results;
	}
}
