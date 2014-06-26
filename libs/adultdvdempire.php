<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program (see LICENSE.txt in the base directory.  If
 * not, see:
 *
 * @link <http://www.gnu.org/licenses/>.
 * @author mike
 * @copyright 2014 nZEDb
 */


//namespace adultdvdempire;

require_once 'simple_html_dom.php';

class adultdvdempire {

	/* If a release matches define it as as true = gives callback to continue */
	public $found = null;

	/* Get and compare searchterm */
	public $searchterm = null;

	/* Define param if trailing url is found get it and set it for future calls */
	/* Anything after the $ade url is trailing */
	protected $urlfound = null;

	/* Define ADE Url here */
	protected $ade = "http://www.adultdvdempire.com";

	/* Tabbed variables in urls */
	protected $allquery = "/allsearch/search?q=";
	protected $scenes = "/scenes";
	protected $boxcover = "/boxcover";
	protected $reviews = "/reviews";
	protected $trailers = "/trailers";


	public function __construct($echooutput = true){
		$this->echooutput = ($echooutput && nZEDb_ECHOCLI);
		$this->url = null;
		$this->response = array();
		$this->html = new simple_html_dom();

}
	public function sypnosis($tagline=true){
	$this->getadeurl($this->urlfound);
	$this->html->load($this->response);
	$res = array();
	if($tagline === true){
		$ret = $this->html->find("p.Tagline",0);
		$res[] = trim($ret->plaintext);
	}
		$ret = $this->html->find("p.Tagline",0)->next_sibling()->next_sibling();
		$res[] = trim($ret->innertext);
	return $res;
	}

	public function search(){
		if(!isset($this->searchterm)){
			return false;
		}
	if($this->getadeurl($this->allquery.rawurlencode($this->searchterm)) === false){
		return false;
	}else{
		$this->html->load($this->response);
		unset($this->response);
		$ret = $this->html->find("span.sub strong",0);
		$ret = (int)$ret->plaintext;
		if(isset($ret)){
		if($ret >=1){
			$ret = $this->html->find("a.boxcover",0);
			$title = $ret->title;
			$ret = (string)trim($ret->href);
			similar_text($this->searchterm, $title, $p);
			if ($p >= 70){
			$this->found = true;
			$this->urlfound=$ret;
			unset($ret);
			$this->html->clear();
			}else{
				$this->found= false;
				return false;
			}
		}else{
			return false;
		}
		}else{
			return false;
		}

	}


	}
	private function getadeurl($trailing=null){
		if(isset($trailing)){
		$ch = curl_init($this->ade . $trailing);
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
	}else{
		return false;
		}
	}
}
