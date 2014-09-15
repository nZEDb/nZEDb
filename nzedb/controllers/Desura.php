<?php

require_once nZEDb_LIBS . 'simple_html_dom.php';

class Desura
{

	/**
	 * @var
	 */
	public $cookie;

	/**
	 * @var
	 */
	public $searchTerm;

	const DESURAURL = "http://www.desura.com";

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
	protected $_desuraGameID;

	/**
	 * @var string
	 */
	protected $_title = '';


	public function __construct()
	{
		$this->_html = new simple_html_dom();
		if (isset($this->cookie)) {
			$this->getUrl(self::DESURAURL);
		}
	}

	/**
	 * Remove object/resources from memory
	 *
	 */
	public function __destruct()
	{
		$this->_html->clear();
		unset($this->_response);
	}

	/**
	 * Game Description
	 *
	 * @return array
	 */
	public function gameDescription()
	{
			if ($this->_ret = $this->_html->find("div.headernormalbox, div.inner, div.body", 4)) {
				$this->_res['description'] = trim($this->_ret->plaintext);
			}
		return $this->_res;
	}

	/**
	 * Gets the Rating
	 *
	 * @return array
	 */
	public function rating()
	{
		if (isset($this->_response) && isset($this->_title)) {
			if ($this->_ret = $this->_html->find("div.score", 0)) {
				$this->_res['rating'] = $this->_ret->plaintext;
			}
		}

		return $this->_res;
	}

	/**
	 * Gets the (cover image)
	 *
	 * @return array
	 */
	public function images()
	{
			if ($this->_ret = $this->_html->find("img[alt=Boxshot]", 0)) {
				$this->_ret->src = preg_replace('#cache/#', '', $this->_ret->src);
				$this->_ret->src = preg_replace('#thumb_150x150/#','',$this->_ret->src);
				$this->_res['cover'] = $this->_ret->src;
			}

			// backcover will be with trailers where it will get loaded last
		return $this->_res;
	}

	/**
	 * Get Details of the game (Genre, Platform(s), Developer, Publisher)
	 *
	 * @return array
	 */
	public function details()
	{
		if ($this->_ret = $this->_html->find("div.info", 0)) {
			foreach ($this->_ret->find("div.row") as $row) {
				if ($this->_ret = $row->find("h5", 0)) {
					switch (trim($this->_ret->plaintext)) {
						case "Genre" :
						case "Platform" :
						case "Platforms" :
						case "Developer" :
						case "Publisher" :
							$this->_res['gamedetails'][$this->_ret->plaintext] = trim($this->_ret->next_sibling()->plaintext);
							break;
					}
				}
			}
		}

		return $this->_res;
	}

	/**
	 * Gets the Video and backdrop image for the game
	 *
	 * @return array
	 */
	public function trailer()
	{
		if ($this->getUrl(self::DESURAURL . '/games/' . $this->searchTerm . '/videos') !== false) {
			if (preg_match('#"file": "(?<trailerurl>.*)",#i', $this->_response, $matches)) {
				$this->_res['trailer'] = trim($matches['trailerurl']);
			}
		}

		if ($this->getUrl(self::DESURAURL . '/games/' . $this->searchTerm . '/images') !== false) {
			if ($this->_ret = $this->_html->find("div.holder", 0)) {
				if ($this->_ret = $this->_ret->find("img", 0)) {
					$this->_res['backdrop'] = trim($this->_ret->src);
				}
			}
		}

		return $this->_res;
	}

	/**
	 * Goes directly to site if site doesn't return error, search is found.
	 *
	 * @return bool
	 */
	public function search()
	{
		$result = false;
		if (!empty($this->searchTerm)) {
			$this->_title = $this->searchTerm;
			// Remove periods, underscored, anything between parenthesis.
			$this->searchTerm = preg_replace('#\(.*?\)|[-._]#i', ' ', $this->searchTerm);
			// Remove multiple spaces and trim leading spaces.
			$this->searchTerm = trim(preg_replace('#\s{2,}#', ' ', $this->searchTerm));
			// Replace whitespace with a - for desura game urls
			$this->searchTerm = preg_replace('#\s#', '-', strtolower($this->searchTerm));
			if ($this->getUrl(self::DESURAURL . '/games/' . $this->searchTerm) !== false) {
				if (!preg_match('#(Games system error)#i', $this->_response)) {
					if($this->_ret = $this->_html->find("a#watchtoggle", 0)){
						if(preg_match('#siteareaid=(?<gameid>\d+)#', $this->_ret->href, $matches)){
							$this->_desuraGameID = $matches['gameid'];
							$this->_directURL = self::DESURAURL . '/games/' . $this->searchTerm;
							$result = true;
						}
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Gets all Information for the game.
	 *
	 * @return array
	 */
	public function getAll()
	{
		$results = array();
		if (isset($this->_directURL)) {
			$results['desuragameid'] = $this->_desuraGameID;
			$results['directurl'] = $this->_directURL;
			$results['title'] = $this->_title;
		}
		if (is_array($this->gameDescription())) {
			$results = array_merge($results, $this->gameDescription());
		}
		if (is_array($this->details())) {
			$results = array_merge($results, $this->details());
		}
		if (is_array($this->rating())) {
			$results = array_merge($results, $this->rating());
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
	 * Gets Raw Html
	 *
	 * @param string $fetchURL
	 * @param bool $usePost
	 *
	 * @return bool
	 */
	private function getUrl($fetchURL, $usePost = false)
	{
		if (isset($fetchURL)) {
			$this->_ch = curl_init($fetchURL);
		}
		if ($usePost === true) {
			curl_setopt($this->_ch, CURLOPT_POST, 1);
			curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $this->_postParams);
		}
			curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($this->_ch, CURLOPT_HEADER, 0);
			curl_setopt($this->_ch, CURLOPT_VERBOSE, 0);
			curl_setopt($this->_ch, CURLOPT_USERAGENT, "Firefox/2.0.0.1");
			curl_setopt($this->_ch, CURLOPT_FAILONERROR, 0);
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
}
