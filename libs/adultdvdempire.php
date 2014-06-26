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

	/* Define param if trailing url is found get it and set it for future calls */
	/* Anything after the $ade url is trailing */

	protected $urlfound = null;

	/* Define ADE Url here */
	protected $ade = "http://www.adultdvdempire.com";


	public function __construct($echooutput = true){
		$this->echooutput = ($echooutput && nZEDb_ECHOCLI);
		$this->url = null;
		$this->response = array();
		$this->html = new simple_html_dom();

}
	public function search(){
	if($this->getadeurl($this->url) === false){
		return false;
	}else{
		$this->html->load($this->response);
		$ret = $this->html->find("span.sub strong",0);
		$ret = (int)$ret->plaintext;
		if(isset($ret)){
		if($ret >=1){
			$ret = $this->html->find("a.boxcover",0);
			$ret = (string)trim($ret->href);
			$this->found = $ret;
		}else{
			return false;
		}
		}else{
			return false;
		}

	}


	}
	private function getadeurl(){
		if(isset($this->url)){
		$ch = curl_init($this->ade . $this->url);
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
