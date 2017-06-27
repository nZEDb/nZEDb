<?php
namespace nzedb\processing\adult;

use nzedb\utility\Misc;

class Hotmovies extends AdultMovies
{

	/**
	 * Constant Urls used within this class
	 * Needed Search Queries Variables
	 */
	const EXTRASEARCH = '&complete=on&search_in=video_title';
	const HMURL = 'http://www.hotmovies.com';
	const IF18 = true;
	const TRAILINGSEARCH = '/search.php?words=';
	/**
	 * Keyword Search.
	 *
	 * @var string
	 */
	protected $searchTerm = '';
	/**
	 * Define a cookie location
	 *
	 * @var string
	 */
	public $cookie = '';
	/**
	 * If a direct link is set parse it instead of search for it.
	 *
	 * @var string
	 */
	protected $directLink = '';
	/**
	 * Sets the direct url in the getAll method
	 *
	 * @var string
	 */
	protected $_directUrl = '';

	/**
	 * Sets the link to get in curl
	 *
	 * @var string
	 */
	protected $_getLink = '';

	/**
	 * POST parameters used with curl
	 *
	 * @var array
	 */
	protected $_postParams = [];

	/**
	 * Results return from some methods
	 *
	 * @var array
	 */
	protected $_res = [];

	/**
	 * Raw Html from Curl
	 *
	 */
	protected $_response;

	/**
	 * Sets the title in the getAll method
	 *
	 * @var string
	 */
	protected $_title = '';

	/**
	 * Hotmovies constructor.
	 *
	 * @param array $options
	 *
	 * @throws \Exception
	 */
	public function __construct(array $options = [])
	{
		parent::__construct($options);
	}

	protected function trailers()
	{
		// TODO: Implement trailers() method.

		return false;
	}

	/**
	 * Gets the synopsis
	 *
	 * @return array
	 */
	protected function synopsis()
	{
		$this->_res['synopsis'] = 'N/A';
		if ($this->_html->find('.desc_link', 0)) {
			$ret = $this->_html->find('.video_description', 0);
			if ($ret !== false) {
				$this->_res['synopsis'] = trim($ret->innertext);
			}
		}

		return $this->_res;
	}

	/**Process ProductInfo
	 *
	 * @param bool $extras
	 *
	 * @return array
	 */
	protected function productInfo($extras = false)
	{
		$studio = false;
		$director = false;
		if ($ret = $this->_html->find('div.page_video_info', 0)) {
			foreach ($ret->find('text') as $e) {
				$e = trim($e->innertext);
				$rArray = [',', '...', '&nbsp:'];
				$e = str_replace($rArray, '', $e);
				if (stripos($e, 'Studio:') !== false) {
					$studio = true;
				}
				if (strpos($e, 'Director:') !== false) {
					$director = true;
					$e = null;
				}
				if ($studio === true) {
					if (stripos($e, 'Custodian of Records') === false) {
						if (stripos($e, 'Description') === false) {

							if ($director === true && !empty($e)) {
								$this->_res['director'] = $e;
								$e = null;
								$director = false;
							}
							if (!empty($e)) {
								$this->_res['productinfo'][] = $e;
							}
						} else {
							break;
						}
					} else {
						break;
					}
				}
			}
		}
		if (is_array($this->_res['productinfo'])) {
			$this->_res['productinfo'] = array_chunk($this->_res['productinfo'], 2, false);
		}

		return $this->_res;
	}

	/**
	 * Gets the cast members and director
	 *
	 * @return array
	 */
	protected function cast()
	{
		$cast = [];
		if ($this->_html->find('.stars bottom_margin')) {
			file_put_contents('hm_cast.txt', $this->_html->find('.stars bottom_margin'));
			foreach ($this->_html->find('a[title]') as $e) {
				$e = trim($e->title);
				$e = preg_replace('/\((.*)\)/', '', $e);
				$cast[] = trim($e);
			}
			$this->_res['cast'] = $cast;

		}

		return $this->_res;
	}

	/**
	 * Gets categories
	 *
	 * @return array
	 */
	protected function genres()
	{
		$genres = [];
		if ($ret = $this->_html->find('div.categories',0)) {
			foreach ($ret->find('a') as $e) {
				if (strpos($e->title, ' -> ') !== false) {
					$e = explode(' -> ',$e->plaintext);
					$genres[] = trim($e[1]);
				}
			}
			$this->_res['genres'] = $genres;
		}
		return $this->_res;
	}

	/**
	 * Get Box Cover Images
	 * @return bool|array - boxcover,backcover
	 */
	protected function covers()
	{
		if ($ret = $this->_html->find('div#large_cover, img#cover', 1)) {
			$this->_res['boxcover'] = trim($ret->src);
			$this->_res['backcover'] = str_ireplace('.cover', '.back', trim($ret->src));
		} else {
			return false;
		}

		return $this->_res;
	}

	/**
	 * Searches for match against xxx movie name
	 *
	 * @param string $movie
	 *
	 * @return bool , true if search >= 90%
	 */
	public function processSite($movie)
	{
		if (empty($movie)) {
			return false;
		}
		$this->_response = false;
		$this->_getLink = self::HMURL . self::TRAILINGSEARCH . urlencode($movie) . self::EXTRASEARCH;
		$this->_response = Misc::getRawHtml($this->_getLink, $this->cookie);
		if ($this->_response !== false) {
			$this->_html->load($this->_response);
			if ($ret = $this->_html->find('h3[class=title]', 0)) {
				if ($ret->find('a[title]', 0)) {
					$ret = $ret->find('a[title]', 0);
					$title = trim($ret->title);
					$title = str_replace('/XXX/', '', $title);
					$title = preg_replace('/\(.*?\)|[-._]/', ' ', $title);
					if (!empty($title)) {
						similar_text($movie, $title, $p);
						if ($p >= 90) {
							$this->_title = $title;
							$this->_getLink = trim($ret->href);
							$this->_directUrl = trim($ret->href);
							$this->_html->clear();
							unset($this->_response);
							if ($this->_getLink !== false) {
								$this->_response = Misc::getRawHtml($this->_getLink, $this->cookie);
								$this->_html->load($this->_response);
							} else {
								$this->_response = Misc::getRawHtml($this->_directUrl, $this->cookie);
								$this->_html->load($this->_response);
							}

							return true;
						}
					}
				}
			}
		} else {
			return false;
		}

		return false;
	}
}
