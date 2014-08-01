<?php

require_once nZEDb_LIBS . 'simple_html_dom.php';

class IAFD {

	public $searchterm = null;
	public $cookie = null;
	public $classused = null;
	public $directurl = null;
	public $title = null;

	const IAFDSEARCHURL = "http://www.iafd.com/results.asp?searchtype=title&searchstring=";
	const IAFDURL = "http://www.iafd.com";
	const ADE = "Adult DVD Empire";
	const HM = "Hot Movies";


	protected $dosearch = false;
	protected $dvdfound = false;
	protected $getredirect = null;
	protected $response = null;
	protected $res = array();
	protected $html;

	public function __construct()
	{
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
									$this->classused = "ade";
									$this->getredirect = self::IAFDURL . trim($alink->href);
									$this->directurl = $this->_geturl();
									$this->directurl = preg_replace('/\?(.*)/', '', $this->directurl);
									$this->dvdfound = false;
									break;
								}
								if ($compare === self::HM && !empty($compare)) {
									$this->classused = "hm";
									$this->getredirect = self::IAFDURL . trim($alink->href);
									$this->directurl = $this->_geturl();
									$this->directurl = preg_replace('/\?(.*)/', '', $this->directurl);
									$this->dvdfound = false;
									break;
							}
					}
				}
		}
		}
	}
			if (!isset($this->classused)) {
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
			$firsttitle = null;
			$secondtitle = null;
			if($ret = $this->html->find("div#moviedata, h2, dt", 0)){
			if($ret->find("h2",0)){
				$firsttitle = $ret->find("h2",0)->innertext;
				if(preg_match("/Movie Titles/",$firsttitle)){
					return false;
				}
				}
			if($ret->find("dt",0)){
			    $secondtitle = $ret->find("dd", 0)->innertext;
			}
				unset($ret);
				if(isset($secondtitle) OR isset($firsttitle)){
					$firsttitle = preg_replace("/\(([0-9]+)\)/","",$firsttitle);
					$secondtitle = preg_replace("/\(([0-9]+)\)/", "", $secondtitle);
					similar_text($this->searchterm, trim($firsttitle), $p);
					if ($p >= 90) {
						$this->title = trim($firsttitle);
						return true;
					} else {
						similar_text($this->searchterm, trim($secondtitle), $p);
						if($p >= 90) {
							$this->title = trim($secondtitle);
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
	private function _geturl()
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
