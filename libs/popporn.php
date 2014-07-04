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
	const popurl = "http://www.tlavideo.com";
	const trailingsearch = "/results/index.cfm?v=4&g=0&searchtext=";

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

	public function __construct($echooutput = true)
	{
		$this->echooutput = ($echooutput && nZEDb_ECHOCLI);
		$this->url = null;
		$this->response = array();
		$this->tmprsp = null;
		$this->html = new simple_html_dom();
		$this->edithtml = new simple_html_dom();
	}

	/**
	 * Get Box Cover Images
	 * @return array - boxcover,backcover
	 */
	public function covers()
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


public function sypnosis(){
	$res = array();
	if ($this->html->find('div[id=product-info] ,h3[class=highlight]', 1)) {
		$ret = $this->html->find('div[id=product-info] ,h3[class=highlight]', 1);
		if($ret->next_sibling()->plaintext){
		$res['sypnosis'] = trim($ret->next_sibling()->plaintext);
			}else{
			return false;
		}
	}else{
		return false;
	}
	return $res;

}
	/**
	 * Searches for match against searchterm
	 * @return bool, true if search = 100%
	 */
	public function search()
	{
		if (!isset($this->searchterm)) {
			return false;
		}
		$this->trailurl = self::trailingsearch . urlencode($this->searchterm);
		if ($this->getpopurl() === false) {
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
				if ($p >= 95) {
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
	 * Get raw html of an url that is passed to it.
	 * @return bool
	 */
	private function getpopurl()
	{
		if (isset($this->trailurl)) {
			$ch = curl_init(SELF::popurl . $this->trailurl);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_VERBOSE, 0);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
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
}
