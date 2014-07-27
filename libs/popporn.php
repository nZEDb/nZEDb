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

class popporn
{
	/*
	 * Set this for what you are searching for.
	 * @var string null
	 */
	public $searchterm = null;

	/**
	 * Define a cookie file
	 * @var string null
	 */
	public $cookie = null;

	/*
	 * Define Popporn url
	 * Needed Search Queries Variables
	*/
	const POPURL = "http://www.popporn.com";
	const TRAILINGSEARCH = "/results/index.cfm?v=4&g=0&searchtext=";
	const IF18 = "http://www.popporn.com/popporn/4";

	/**
	 * Add this to popurl to get results
	 * @var string null
	 */
	protected $trailurl = null;

	// Sets Post fields for trailers
	protected $postparams = null;

	// Sets the found title and returns it in the array
	protected $title = null;

	// Sets the directurl for the template and returns it in the array
	protected $directurl = null;

	public function __construct()
	{
		$this->response = array();
		$this->res = array();
		$this->html = new simple_html_dom();
		if (isset($this->cookie)) {
			@$this->_getpopurl();
		}
	}

	/*
	 * Remove from memory if it still exists
	 */
	public function __destruct()
	{
	$this->html->clear();
	unset($this->response);
	unset($this->res);
	}

	/**
	 * Get Box Cover Images
	 * @return array - boxcover,backcover
	 */
	public function _covers()
	{
		if ($this->html->find('div[id=box-art], a[rel=box-art]', 1)) {
			$ret = $this->html->find('div[id=box-art], a[rel=box-art]', 1);
			$this->res['boxcover'] = trim($ret->href);
			$this->res['backcover'] = str_ireplace("_aa","_bb",trim($ret->href));
		}
		return $this->res;
	}

	/**
	 * Gets the sypnosis
	 * @return array|bool
	 */
	public function _sypnosis()
	{
		if ($this->html->find('div[id=product-info] ,h3[class=highlight]', 1)) {
			$ret = $this->html->find('div[id=product-info] ,h3[class=highlight]', 1);
			if ($ret->next_sibling()->plaintext) {
				if (!stristr(trim($ret->next_sibling()->plaintext), "POPPORN EXCLUSIVE")) {
					$this->res['sypnosis'] = trim($ret->next_sibling()->plaintext);
				} else {
					if ($ret->next_sibling()->next_sibling()->next_sibling()->plaintext) {
						$this->res['sypnosis'] = trim($ret->next_sibling()->next_sibling()->next_sibling()->plaintext);
					} else {
						$this->res['sypnosis'] = "N/A";
					}
				}
			} else {
				return false;
			}
		} else {
			return false;
		}

		return $this->res;
	}

	/**
	 * Gets trailer video
	 * @return array|bool
	 */
	public function _trailers()
	{
		if ($this->html->find('input#thickbox-trailer-link', 0)) {
			$ret = $this->html->find('input#thickbox-trailer-link', 0);
			$ret->value = trim($ret->value);
			$ret->value = str_replace("..", "", $ret->value);
			$tmprsp = $this->response;
			$this->trailurl = $ret->value;
			$this->_getpopurl();
			if (preg_match_all('/productID="\+(?<id>[0-9]+),/', $this->response, $matches)) {
				$productid = $matches['id'][0];
				$random = ((float)rand() / (float)getrandmax()) * 5400000000000000;
				$this->trailurl = "/com/tlavideo/vod/FlvAjaxSupportService.cfc?random=" . $random;
				$this->postparams = "method=pipeStreamLoc&productID=" . $productid;
				$this->_getpopurl(true);
				$ret = json_decode(json_decode($this->response, true), true);
				$this->res['trailers']['baseurl'] = self::POPURL . "/flashmediaserver/trailerPlayer.swf";
				$this->res['trailers']['flashvars'] = "subscribe=false&image=&file=" . self::POPURL . "/" . $ret['LOC'] . "&autostart=false";
				unset($this->response);
				$this->response = $tmprsp;
			}
		return $this->res;
		}
	}

	/**
	 * Process ProductInfo And/or Extras
	 *
	 * @param bool $extras
	 *
	 * @return array|bool
	 */
	public function _productinfo($extras = true)
	{
		$country = false;
		if ($this->html->find('div#lside', 0)) {
			$ret = $this->html->find('div#lside', 0);
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
							$this->res['productinfo'][] = $e;
						}
					} else {
						break;
					}
				}
			}
		} else {
			return false;
		}

		$this->res['productinfo'] = array_chunk($this->res['productinfo'], 2, false);

		if ($extras === true) {
			$features = false;
			if ($this->html->find('ul.stock-information', 0)) {
				foreach ($this->html->find('ul.stock-information') as $ul) {
					foreach ($ul->find('li') as $e) {
						$e = trim($e->plaintext);
						if ($e == "Features:") {
							$features = true;
							$e = null;
						}
						if ($features == true) {
							if (!empty($e)) {
								$this->res['extras'][] = $e;
							}
						}
					}
				}
			}
		}

		return $this->res;
	}

	/**
	 * Gets the cast members and director
	 * @return array|bool
	 */
	public function _cast()
	{
		$cast = false;
		$director = false;
		if ($this->html->find('div#lside', 0)) {
			$ret = $this->html->find('div#lside', 0);
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
							$this->res['director'] = $e;
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
			$this->res['cast'] = & $er;

			return $this->res;
		} else {
			return false;
		}
	}

	/**
	 * Gets categories
	 * @return array|bool
	 */
	public function _genres()
	{
		if ($this->html->find('div[id=thekeywords], p[class=keywords]', 1)) {
			$ret = $this->html->find('div[id=thekeywords], p[class=keywords]', 1);
			foreach ($ret->find('a') as $e) {
				$genres[] = trim($e->plaintext);
			}
			$this->res['genres'] = & $genres;

			return $this->res;
		} else {
			return false;
		}
	}

	/**
	 * Searches for match against searchterm
	 * @return bool, true if search >= 90%
	 */
	public function search()
	{
		if (!isset($this->searchterm)) {
			return false;
		}
		$this->trailurl = self::TRAILINGSEARCH . urlencode($this->searchterm);
		if ($this->_getpopurl() === false) {
			return false;
		} else {
			$this->html->load($this->response);
			if ($this->html->find('h2[class=title]', 0)) {
				$ret = $this->html->find('h2[class=title]', 0);
				$title = trim($ret->innertext);
			} else {
				return false;
			}
			if($this->html->find('#link-to-this',0)){
			$ret = $this->html->find('#link-to-this', 0);
			$ret = trim($ret->href);
			$this->directurl = $ret;
			}
			if (isset($title)) {
				similar_text($this->searchterm, $title, $p);
				if ($p >= 90) {
					$this->title = $title;
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
	public function _getall()
	{
		$results = array();
		if(isset($this->directurl)){
		$results['title'] = $this->title;
		$results['directurl'] = $this->directurl;
		}
		if (is_array($this->_sypnosis())) {
			$results = array_merge($results, $this->_sypnosis());
		}
		if (is_array($this->_productinfo(true))) {
			$results = array_merge($results, $this->_productinfo(true));
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
	private function _getpopurl($usepost = false)
	{
		if (isset($this->trailurl)) {
			$ch = curl_init(self::POPURL . $this->trailurl);
		} else {
			$ch = curl_init(self::IF18);
		}

		if($usepost === true){
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
		return true;
	}
}
