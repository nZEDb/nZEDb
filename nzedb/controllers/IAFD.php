<?php

require_once nZEDb_LIBS . 'simple_html_dom.php';

class IAFD {


	public $classUsed = "";
	public $cookie = "";
	public $directUrl;
	public $searchTerm = "";
	public $title = "";

	const ADE = "Adult DVD Empire";
	const ADM = "AdultDVDMarketplace";
	const IAFDSEARCHURL = "http://www.iafd.com/results.asp?searchtype=title&searchstring=";
	const IAFDURL = "http://www.iafd.com";

	protected $_dvdFound = false;
	protected $_doSearch = false;
	protected $_getRedirect;
	protected $_html;
	protected $_res = array();
	protected $_response;


	public function __construct()
	{
		$this->_html = new \simple_html_dom();
		if (isset($this->cookie)) {
			@$this->getUrl();
		}
	}

	public function __destruct()
	{
		$this->_html->clear();
		unset($this->response);
		unset($this->res);
	}

	public function findme()
	{
		if ($this->search() === true) {
			if ($this->_html->find("div#commerce", 0)) {
				foreach ($this->_html->find("div#commerce") as $e) {
					foreach ($e->find("h4, p.item") as $h4) {
						//echo ($h4->innertext) ."\n";
						if (trim($h4->plaintext) == "DVD") {
							$this->_dvdFound = true;
							$h4 = null;
						}
						if ($this->_dvdFound === true && isset($h4)) {
							foreach ($h4->find("a") as $alink) {
								$compare = trim($alink->innertext);
								if ($compare === self::ADE && !empty($compare)) {
									$this->classUsed = "ade";
									$this->_getRedirect = self::IAFDURL . trim($alink->href);
									$this->directUrl = $this->getUrl();
									$this->directUrl = preg_replace('/\?(.*)/', '', $this->directUrl);
									$this->_dvdFound = false;
									break;
								}
								if ($compare === self::ADM && !empty($compare)) {
									$this->classUsed = "adm";
									$this->_getRedirect = self::IAFDURL . trim($alink->href);
									$this->directUrl = $this->getUrl();
									$this->directUrl = preg_replace('/\?(.*)/',
																	'',
																	$this->directUrl);
									$this->_dvdFound = false;
									break;
								}
					}
				}
		}
		}
	}
			if (!isset($this->classUsed)) {
				return false;
			} else {
				return true;
			}
		} else {
			return false;
		}
	}

	private function search(){

		if (!isset($this->searchTerm)) {
			return false;
		}
		$this->_doSearch = true;
		if ($this->getUrl() === false) {
			return false;
		} else {
			$firsttitle = null;
			$secondtitle = null;
			if($ret = $this->_html->find("div#moviedata, h2, dt", 0)){
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
					$firsttitle = preg_replace('/\(([0-9]+)\)/',"",$firsttitle);
					$firsttitle = preg_replace('/XXX/', '', $firsttitle);
					$firsttitle = preg_replace('/\(.*?\)|[-._]/i', ' ', $firsttitle);
					$secondtitle = preg_replace('/\(([0-9]+)\)/', "", $secondtitle);
					$secondtitle = preg_replace('/XXX/', '', $secondtitle);
					$secondtitle = preg_replace('/\(.*?\)|[-._]/i', ' ', $secondtitle);
					similar_text(strtolower($this->searchTerm), strtolower(trim($firsttitle)), $p);
					if ($p >= 90) {
						$this->title = trim($firsttitle);
						return true;
					} else {
						similar_text(strtolower($this->searchTerm), strtolower(trim($secondtitle)), $p);
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
	private function getUrl()
	{
		if ($this->_doSearch === true) {
			$ch = curl_init(self::IAFDSEARCHURL . urlencode($this->searchTerm));
		} else {
			if (empty($this->_getRedirect)){
				$ch = curl_init(self::IAFDURL);
			} else {
				$ch = curl_init($this->_getRedirect);
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
		curl_setopt_array($ch, nzedb\utility\Utility::curlSslContextOptions());
		$this->_response = curl_exec($ch);
		if(!empty($this->_getRedirect)){
			$this->_getRedirect = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
			curl_close($ch);
			return $this->_getRedirect;
		}
		if (!$this->_response) {
			curl_close($ch);

			return false;
		}
		curl_close($ch);
		if($this->_doSearch === true){
			$this->_html->load($this->_response);
			$this->_doSearch = false;
		}
		return true;
	}



}
