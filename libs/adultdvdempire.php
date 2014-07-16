<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program (see LICENSE.txt in the base directory.  If
 * not, see:
 * @link      <http://www.gnu.org/licenses/>.
 * @author    mike
 * @copyright 2014 nZEDb
 */

require_once 'simple_html_dom.php';

/**
 * Class adultdvdempire
 */
class adultdvdempire
{

	/* If a release matches define it as as true = gives callback to continue */
	public $found = null;

	/* Get and compare searchterm */
	public $searchterm = null;

	/* Define param if trailing url is found get it and set it for future calls */
	/* Anything after the $ade url is trailing */
	protected $urlfound = null;

	/* Define ADE Url here */
	const ade = "http://www.adultdvdempire.com";

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

	public function __construct($echooutput = true)
	{
		$this->echooutput = ($echooutput && nZEDb_ECHOCLI);
		$this->url = null;
		$this->response = array();
		$this->res = array();
		$this->tmprsp = null;
		$this->html = new simple_html_dom();
		$this->edithtml = new simple_html_dom();
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
			$this->res['trailers']['url'] = SELF::ade . trim(trim($matches['swf']), '"');
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
			if($this->html->find("p.Tagline", 0)){
				$ret = $this->html->find("p.Tagline", 0);
			if (!empty($ret->plaintext)) {
				$this->res['tagline'] = trim($ret->plaintext);
			}
			}
		}
		if ($this->html->find("p.Tagline", 0)->next_sibling()->next_sibling()) {
			$ret = $this->html->find("p.Tagline", 0)->next_sibling()->next_sibling();
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


		if ($this->edithtml->find("div[class=scast]", 0)) {
		$ret = $this->edithtml->find("div[class=scast]", 0);
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
		$this->tmprsp = str_ireplace("Section Categories", "scat", $this->response);
		$this->edithtml->load($this->tmprsp);
		$ret = $this->edithtml->find("div[class=scat]", 0);
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
		$ret = $this->edithtml->find("div[class=spdinfo]", 0);
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
		$this->edithtml->clear();
		unset($this->tmprsp);
		unset($ret);

		return $this->res;
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
			unset($this->response);
			$ret = $this->html->find("span.sub strong", 0);
			$ret = (int)$ret->plaintext;
			if (isset($ret)) {
				if ($ret >= 1) {
					$ret = $this->html->find("a.boxcover", 0);
					$title = $ret->title;
					$ret = (string)trim($ret->href);
					similar_text($this->searchterm, $title, $p);
					if ($p >= 90) {
						$this->found = true;
						$this->urlfound = $ret;
						$this->directurl = self::ade.$ret;
						$this->title = $title;
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
			$ch = curl_init(SELF::ade . $trailing);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_VERBOSE, 0);
			curl_setopt($ch, CURLOPT_USERAGENT, "Firefox/2.0.0.1");
			curl_setopt($ch, CURLOPT_FAILONERROR, 1);
			$this->response = curl_exec($ch);
			if (!$this->response) {
				curl_close($ch);

				return false;
			}
			curl_close($ch);
		} else {
			return false;
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
