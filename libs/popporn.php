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
	 * Define Popporn url
	 * Needed Search Queries Variables
	*/
	const popurl = "http://www.popporn.com";
	const trailingsearch = "/results/index.cfm?v=4&g=0&searchtext=";
	const if18 = "http://www.popporn.com/popporn/4";

	/**
	 * Add this to popurl to get results
	 * @var string null
	 */
	protected $trailurl = null;

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

	public function __construct($echooutput = true)
	{
		$this->response = array();
		$this->html = new simple_html_dom();
		if (isset($this->cookie)) {
			@$this->_getpopurl();
		}
	}

	/**
	 * Get Box Cover Images
	 * @return array - boxcover,backcover
	 */
	public function _covers()
	{
		$res = array();
		if ($this->html->find('div[id=box-art], img[class=front]', 1)) {
			$ret = $this->html->find('div[id=box-art], img[class=front]', 1);
			$res['boxcover'] = trim($ret->src);
		}
		if ($this->html->find('div[id=box-art], img[class=back]', 1)) {
			$ret = $this->html->find('div[id=box-art], img[class=back]', 1);
			$res['backcover'] = trim($ret->src);
		}

		return $res;
	}

	/**
	 * Gets the sypnosis
	 * @return array|bool
	 */
	public function _sypnosis()
	{
		$res = array();
		if ($this->html->find('div[id=product-info] ,h3[class=highlight]', 1)) {
			$ret = $this->html->find('div[id=product-info] ,h3[class=highlight]', 1);
			if ($ret->next_sibling()->plaintext) {
				if (!stristr(trim($ret->next_sibling()->plaintext), "POPPORN EXCLUSIVE")) {
					$res['sypnosis'] = trim($ret->next_sibling()->plaintext);
				} else {
					if ($ret->next_sibling()->next_sibling()->next_sibling()->plaintext) {
						$res['sypnosis'] = trim($ret->next_sibling()->next_sibling()->next_sibling()->plaintext);
					} else {
						$res['sypnosis'] = "N/A";
					}
				}
			} else {
				return false;
			}
		} else {
			return false;
		}

		return $res;
	}

	/**
	 * Gets trailer video
	 * @return array|bool
	 */
	public function _trailers()
	{
		$res = array();
		if ($this->html->find('input#thickbox-trailer-link', 0)) {
			$ret = $this->html->find('input#thickbox-trailer-link', 0);
			$ret->value = trim($ret->value);
			$ret->value = str_replace("..", SELF::popurl, $ret->value);
			$res['trailers'] = $ret->value;
		} else {
			return false;
		}

		return $res;
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
		$res = array();
		$country = false;
		if ($this->html->find('div#lside', 0)) {
			$ret = $this->html->find('div#lside', 0);
			foreach ($ret->find("text") as $e) {
				$e = trim($e->innertext);
				$e = str_replace(",", "", $e);
				$e = str_replace("&nbsp;", "", $e);
				if (stristr($e, "Country:")) {
					$country = true;
				}
				if ($country === true) {
					if (!stristr($e, "addthis_config")) {
						if (!empty($e)) {
							$res['productinfo'][] = $e;
						}
					} else {
						break;
					}
				}
			}
		} else {
			return false;
		}

		$res['productinfo'] = array_chunk($res['productinfo'], 2, false);

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
								$res['extras'][] = $e;
							}
						}
					}
				}
			}
		}

		return $res;
	}

	/**
	 * Gets the cast members and director
	 * @return array|bool
	 */
	public function _cast()
	{
		$res = array();
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
							$res['director'] = $e;
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
			$res['cast'] = & $er;

			return $res;
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
		$res = array();
		if ($this->html->find('div[id=thekeywords], p[class=keywords]', 1)) {
			$ret = $this->html->find('div[id=thekeywords], p[class=keywords]', 1);
			foreach ($ret->find('a') as $e) {
				$genres[] = trim($e->plaintext);
			}
			$res['genres'] = & $genres;

			return $res;
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
		$this->trailurl = self::trailingsearch . urlencode($this->searchterm);
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
			if (isset($title)) {
				similar_text($this->searchterm, $title, $p);
				if ($p >= 90) {
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
	 * Get raw html of an url that is passed to it.
	 * @return bool
	 */
	private function _getpopurl()
	{
		if (isset($this->trailurl)) {
			$ch = curl_init(SELF::popurl . $this->trailurl);
		} else {
			$ch = curl_init(SELF::if18);
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
	}
}
