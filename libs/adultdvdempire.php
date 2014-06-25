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
public $url;
public $echooutput = false;
public $response = array();

	public function search(){
		$this->geturl($this->url);

	}
	private function geturl(){
		if(isset($this->url)){
		$ch = curl_init($this->url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
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
