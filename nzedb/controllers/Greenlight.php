<?php

require_once nZEDb_LIBS . 'simple_html_dom.php';

class Greenlight
{

	public $searchTerm = null;
	public $cookie = null;


	const GREENLIGHTURL = "http://steamcommunity.com/greenlight/";
	const GREENLIGHTVARS = "&childpublishedfileid=0&section=items&appid=765&browsesort=textsearch";
	const AGECHECKURL = "http://store.steampowered.com/agecheck/app/";
	const DIRECTGAMEURL = "http://steamcommunity.com/sharedfiles/filedetails/?id=";

	/**
	 * @var
	 */
	protected $_ch;

	/**
	 * @var string
	 */
	protected $_directURL = '';

	/**
	 * @var simple_html_dom
	 */
	protected $_html;

	/**
	 * @var array
	 */
	protected $_postParams = array();

	/**
	 * @var array
	 */
	protected $_res = array();

	/**
	 * @var
	 */
	protected $_response;

	/**
	 * @var
	 */
	protected $_ret;

	/**
	 * @var
	 */
	protected $_greenlightGameID;

	/**
	 * @var string
	 */
	protected $_title = '';

	public function __construct()
	{
		$this->_html = new simple_html_dom();
		if (isset($this->cookie)) {
			$this->getUrl(self::GREENLIGHTURL);
		}
	}

	/**
	 * Free up memory prevent memory leaks
	 */
	public function __destruct()
	{
		$this->_html->clear();
		unset($this->_response);
	}

	/**
	 * Gets the game description of the game.
	 *
	 * @return array
	 */
	public function gameDescription()
	{
		if ($ret = $this->_html->find("div.workshopItemDescription", 0)) {
			$this->_res['description'] = trim(html_entity_decode($ret->plaintext));
		}

		return $this->_res;
	}

	/**
	 * Gets the images - cover and backdrop
	 *
	 * @return array
	 */
	public function images()
	{
			if ($ret = $this->_html->find("div.workshopItemPreviewImageMain", 0)) {
				if(preg_match('#\'(?<largeimage>.*)\'#i', $ret->outertext, $matches)){
				$this->_res['cover'] = trim($matches['largeimage']);
				}else{
				$this->_res['cover'] = $this->_html->find("img#previewImageMain", 0)->src;
				}
			}
			if ($ret = $this->_html->find("div.screenshot_holder", 0)) {
				if ($ret = $ret->find("a", 0)) {
					if(preg_match('#\'(?<backdropimage>.*)\'#', $ret->outertext, $matches)){
					$this->_res['backdrop'] = trim($matches['backdropimage']);
					}
				}
			}
		return $this->_res;
	}

	/**
	 * Return game details - Genre, Platform, Players
	 *
	 * @return array
	 */
	public function details()
	{
		if ($this->_html->find("div.workshopTags", 0)) {
			foreach ($this->_html->find("div.workshopTags") as $detail) {
				if ($ret = $detail->find("span.workshopTagsTitle", 0)) {
					$ret2 = trim($ret->next_sibling()->innertext);
					$ret = str_ireplace("&nbsp;", "", $ret->plaintext);
					$ret = rtrim(trim($ret), ":");
					if ($ret != "Languages") {
						if (count($detail->find("a")) > 1) {
							$ret3 = array();
							foreach ($detail->find("a") as $a) {
								$ret3[] = trim($a->innertext);
							}
							$joinedmultiple = join(",", $ret3);
							$this->_res['gamedetails'][$ret] = $joinedmultiple;
						} else {
							$this->_res['gamedetails'][$ret] = $ret2;
						}
					}
				}
			}
		}

		return $this->_res;
	}

	/**
	 * Gets the trailer for the game
	 *
	 * @return array
	 */
	public function trailer()
	{
			if (preg_match('#youtube_video_id: "(?<youtubeid>.*)",#i', $this->_response, $matches)) {
				$this->_res['trailer'] = "https://www.youtube.com/watch?v=" . trim($matches['youtubeid']);
			}

		return $this->_res;
	}

	/**
	 * Searches for a 100% match.
	 *
	 * @return bool
	 */
	public function search()
	{
		$result = false;
		if (!empty($this->searchTerm)) {
			$this->searchTerm = trim($this->searchTerm);
			if ($this->getUrl(self::GREENLIGHTURL . '?searchtext=' . urlencode($this->searchTerm) . self::GREENLIGHTVARS) !== false) {
				if ($ret = $this->_html->find("div.workshopItemTitle")) {
					if (count($ret) > 0) {
						foreach ($this->_html->find("div.workshopItemTitle") as $ret) {
							$this->_title = trim($ret->plaintext);
							//Sanitize both searchTerm and title for a positive 100% match
							if ($this->cleanTitles(strtolower($this->_title), strtolower($this->searchTerm)) === true) {
								if ($ret->parent()->outertext) {
									preg_match('#id?=(?<gameid>\d+)#', $ret->parent()->outertext, $matches);
									$this->_greenlightGameID = $matches['gameid'];
								}
								$this->_directURL = self::DIRECTGAMEURL . $this->_greenlightGameID;
								if ($this->getUrl($this->_directURL) !== false) {
									$result = true;
									break;
								}
							}else{
							$result = false;
							}
						}
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Gets all Information
	 *
	 * @return array
	 */
	public function getAll()
	{
		$results = array();
		if (!empty($this->_directURL)) {
			$results['greenlightgameid'] = $this->_greenlightGameID;
			$results['directurl'] = $this->_directURL;
			$results['title'] = $this->_title;
		}
		if (is_array($this->gameDescription())) {
			$results = array_merge($results, $this->gameDescription());
		}
		if (is_array($this->details())) {
			$results = array_merge($results, $this->details());
		}
		if (is_array($this->images())) {
			$results = array_merge($results, $this->images());
		}
		if (is_array($this->trailer())) {
			$results = array_merge($results, $this->trailer());
		}

		return $results;
	}

	/**
	 * Gets the raw html to parse
	 *
	 * @param string $fetchurl
	 * @param bool $usepost
	 *
	 * @return bool
	 */
	private function getUrl($fetchurl = "", $usepost = false)
	{
		if (!empty($fetchurl)) {
			$this->_ch = curl_init($fetchurl);
		}
		if ($usepost === true) {
			curl_setopt($this->_ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($this->_ch, CURLOPT_POST, 1);
			curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $this->_postParams);
		}
		curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->_ch, CURLOPT_REFERER, self::GREENLIGHTURL);
		curl_setopt($this->_ch, CURLOPT_HEADER, 0);
		curl_setopt($this->_ch, CURLOPT_VERBOSE, 0);
		curl_setopt($this->_ch, CURLOPT_USERAGENT, "Firefox/2.0.0.1");
		curl_setopt($this->_ch, CURLOPT_FAILONERROR, 1);
		if (isset($this->cookie)) {
			curl_setopt($this->_ch, CURLOPT_COOKIEJAR, $this->cookie);
			curl_setopt($this->_ch, CURLOPT_COOKIEFILE, $this->cookie);
		}
		curl_setopt_array($this->_ch, nzedb\utility\Utility::curlSslContextOptions());
		$this->_response = curl_exec($this->_ch);
		if (!$this->_response) {
			curl_close($this->_ch);

			return false;
		}
		curl_close($this->_ch);
		$this->_html->load($this->_response);

		return true;
	}

	/**
	 * Removes all but alphanumeric only and does a 100% match check
	 *
	 * @param string $title
	 * @param string $searchtitle
	 *
	 * @return bool
	 */
	protected function cleanTitles($title = "", $searchtitle = "")
	{
		$title = preg_replace('/[^\w]/', '', $title);
		$searchtitle = preg_replace('/[^\w]/', '', $searchtitle);
	    similar_text($title , $searchtitle, $p);
		if ($p == 100) {
			return true;
		} else {
			return false;
		}
	}
}
