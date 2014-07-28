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

class iafd {

	public $searchterm = null;
	public $cookie = null;
	public $classfound = false;
	public $directurl = null;
	public $title = null;

	const IAFDSEARCHURL = "http://www.iafd.com/results.asp?searchtype=title&searchstring=";
	const IAFDURL = "http://www.iafd.com";
	const ADE = "Adult DVD Empire";
	const HM = "Hot Movies";


	protected $dosearch = false;
	protected $dvdfound = false;
	protected $getredirect = null;

	public function __construct()
	{
		$this->response = array();
		$this->res = array();
		$this->html = new simple_html_dom();
		if (isset($this->cookie)) {
			@$this->_geturl();
		}
	}

	public function __destruct()
	{
		$this->html->clear();
		unset($this->response);
		unset($this->res);
	}

	public function findme()
	{
		if ($this->_search() === true) {
			if ($this->html->find("div#commerce", 0)) {
				foreach ($this->html->find("div#commerce") as $e) {
					foreach ($e->find("h4, p.item") as $h4) {
						//echo ($h4->innertext) ."\n";
						if (trim($h4->plaintext) == "DVD") {
							$this->dvdfound = true;
							$h4 = null;
						}
						if ($this->dvdfound === true && isset($h4)) {
							foreach ($h4->find("a") as $alink) {
								$compare = trim($alink->innertext);
								if ($compare === self::ADE && !empty($compare)) {
									$this->classfound = "ade";
									$this->getredirect = self::IAFDURL . trim($alink->href);
									$this->directurl = $this->_geturl();
									$this->directurl = preg_replace("/\?(.*)/", "", $this->directurl);
									$this->dvdfound = false;
									break;
								}
								if ($compare === self::HM && !empty($compare)) {
									$this->classfound = "hm";
									$this->getredirect = self::IAFDURL . trim($alink->href);
									$this->directurl = $this->_geturl();
									$this->directurl = preg_replace("/\?(.*)/", "", $this->directurl);
									$this->dvdfound = false;
									break;
							}
					}
				}
		}
		}
	}
			if (!isset($this->classfound)) {
				return false;
			} else {
				return true;
			}
		} else {
			return false;
		}
	}

	private function _search(){

		if (!isset($this->searchterm)) {
			return false;
		}
		$this->dosearch = true;
		if ($this->_geturl() === false) {
			return false;
		} else {
			if($ret = $this->html->find("div#moviedata, h2, dt", 0)){
			if($ret->find("h2",0)){
				$firsttitle = $ret->find("h2",0)->innertext;
				}
			if($ret->find("dt",0)){
			    $secondtitle = $ret->find("dd", 0)->innertext;
			}
				unset($ret);
				if(isset($secondtitle) OR isset($firsttitle)){
					$firsttitle = preg_replace("/\(([0-9]+)\)/","",$firsttitle);
					$secondtitle = preg_replace("/\(([0-9]+)\)/", "", $secondtitle);
					similar_text($this->searchterm, $firsttitle, $p);
					if ($p >= 90) {
						$this->title = $firsttitle;
						return true;
					} else {
						similar_text($this->searchterm, $secondtitle, $p);
						if($p >= 90) {
							$this->title = $secondtitle;
							return true;
						}else{
							return false;
						}
					}
				}else{
					return false;
				}
			}else{
				return false;
			}
		}

	}
	private function _geturl($usepost = false)
	{
		if ($this->dosearch === true) {
			$ch = curl_init(self::IAFDSEARCHURL . urlencode($this->searchterm));
		} else {
			if(empty($this->getredirect)){
			$ch = curl_init(self::IAFDURL);
			}else{
			$ch = curl_init($this->getredirect);
			}
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
		if(!empty($this->getredirect)){
		$this->getredirect = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
		curl_close($ch);
			return $this->getredirect;
		}
		if (!$this->response) {
			curl_close($ch);

			return false;
		}
		curl_close($ch);
		if($this->dosearch === true){
		$this->html->load($this->response);
		$this->dosearch = false;
		}
		return true;
	}



}
