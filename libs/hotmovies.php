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

class hotmovies
{

	/**
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
	 * Define HotMovies url http://www.hotmovies.com/search.php?words=bangin+the+boss&complete=on&search_in=video_title
	 * Needed Search Queries Variables
	*/
	const HMURL = "http://www.hotmovies.com";
	const TRAILINGSEARCH = "/search.php?words=";
	const EXTRASEARCH = "&complete=on&search_in=video_title";
	const IF18 = true;

	// Sets Post fields for trailers
	protected $postparams = null;

	// Sets the found title and returns it in the array
	protected $title = null;

	// Sets the directurl for the template and returns it in the array
	protected $directurl = null;

	// Sets the URl to retrieve in _gethmurl
	protected $getlink = null;

	public function __construct($echooutput = true)
	{
		$this->response = array();
		$this->res = array();
		$this->html = new simple_html_dom();

		// Set a cookie to override +18 warning.
		if (isset($this->cookie)) {
			@$this->_gethmurl();
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
		if ($this->html->find('div#large_cover, img#cover', 1)) {
			$ret = $this->html->find('div#large_cover, img#cover', 1);
			$this->res['boxcover'] = trim($ret->src);
			$this->res['backcover'] = str_ireplace(".cover",".back",trim($ret->src));
		}else{
			return false;
		}
		return $this->res;
	}

	/**
	 * Gets the sypnosis
	 * @return array|bool
	 */
	public function _sypnosis()
	{
		if ($this->html->find('.desc_link', 0)) {
			preg_match("/var descfullcontent = (?<content>.*)/",$this->response,$matches);
			if (is_array($matches)) {
				$this->res['sypnosis'] = rawurldecode($matches['content']);
			} else {
				return false;
			}
		} else {
			return false;
		}
		return $this->res;
	}

	/**Process ProductInfo
	 *
	 * @return array|bool
	 */
	public function _productinfo()
	{
		$studio = false;
		$director = false;
		if ($this->html->find('div.page_video_info', 0)) {
			$ret = $this->html->find('div.page_video_info', 0);
			foreach ($ret->find("text") as $e) {
				$e = trim($e->innertext);
				$e = str_replace(",", "", $e);
				$e = str_replace("...", "", $e);
				$e = str_replace("&nbsp;", "", $e);
				if (stristr($e, "Studio:")) {
					$studio = true;
				}
				if (stristr($e,"Director:")){
				$director = true;
				$e = null;
				}
				if ($studio === true) {
					if (!stristr($e, "Custodian of Records")) {
						if(!stristr($e,"Description")){

							if ($director === true && !empty($e)) {
								$this->res['director'] = $e;
								$e = null;
								$director = false;
							}
						if (!empty($e)) {
							$this->res['productinfo'][] = $e;
						}
						}else {
							break;
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
		return $this->res;
	}

	/**
	 * Gets the cast members and director
	 * @return array|bool
	 */
	public function _cast()
	{
		$cast = null;
		if ($this->html->find('a[itemprop=actor]')) {
			foreach ($this->html->find('a[itemprop=actor]') as $e) {
				$e= trim($e->title);
				$e = preg_replace("/\((.*)\)/","",$e);
				$cast[] = trim($e);
				}
			$this->res['cast'] = & $cast;
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
		if ($this->html->find('div.categories',0)) {
			$ret = $this->html->find('div.categories',0);
			foreach ($ret->find('a') as $e) {
				if(stristr($e->title,"->")){
				$e = explode("->",$e->plaintext);
				$genres[] = trim($e[1]);
			}
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
		$this->getlink = self::HMURL . self::TRAILINGSEARCH . urlencode($this->searchterm) . self::EXTRASEARCH;
		if ($this->_gethmurl() === false) {
			return false;
		} else {
			if ($this->html->find('h3[class=title]', 0)) {
				$ret = $this->html->find('h3[class=title]', 0);
				if($ret->find('a[title]',0)){
					$ret = $ret->find('a[title]', 0);
					$title = trim($ret->title);
					$this->title = $title;
					$this->getlink = trim($ret->href);
					$this->directurl = trim($ret->href);
				   }
			} else {
				return false;
			}
			if (isset($title)) {
				similar_text($this->searchterm, $title, $p);
				if ($p >= 90) {
					$this->title = $title;
					// 90$ match found, load the url to start parsing
					$this->_gethmurl();
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
	private function _gethmurl($usepost = false)
	{
		if (isset($this->getlink)) {
			$ch = curl_init($this->getlink);
		} else {
			$ch = curl_init(self::HMURL);
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
		$this->html->load($this->response);
		curl_close($ch);
	}
}
