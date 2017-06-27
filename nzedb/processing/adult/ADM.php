<?php
namespace nzedb\processing\adult;

use nzedb\utility\Misc;

class ADM extends AdultMovies
{
	/**
	 * Override if 18 years+ or older
	 * Define Adult DVD Marketplace url
	 * Needed Search Queries Constant
	 */
	const ADMURL = 'http://www.adultdvdmarketplace.com';
	const IF18 = 'http://www.adultdvdmarketplace.com/xcart/adult_dvd/disclaimer.php?action=enter&site=intl&return_url=';
	const TRAILINGSEARCH = '/xcart/adult_dvd/advanced_search.php?sort_by=relev&title=';

	/**
	 * Define a cookie file location for curl
	 * @var string string
	 */
	public $cookie = '';

	/**
	 * Direct Link given from outside url doesn't do a search
	 * @var string
	 */
	protected $directLink = '';

	/**
	 * Set this for what you are searching for.
	 * @var string
	 */
	protected $searchTerm = '';

	/**
	 * Sets the directurl for the return results array
	 * @var string
	 */
	protected $_directUrl = '';

	/**
	 * Results returned from each method
	 *
	 * @var array
	 */
	protected $_res = [];

	/**
	 * Curl Raw Html
	 */
	protected $_response;

	/**
	 * Add this to popurl to get results
	 * @var string
	 */
	protected $_trailUrl = '';

	/**
	 * This is set in the getAll method
	 *
	 * @var string
	 */
	protected $_title = '';

	public function __construct(array $options = [])
	{
		parent::__construct($options);
	}

	/**
	 * Get Box Cover Images
	 * @return array - boxcover,backcover
	 */
	protected function covers()
	{
		$baseUrl = 'http://www.adultdvdmarketplace.com/';
		if ($ret = $this->_html->find('a[rel=fancybox-button]', 0)) {
			if (isset($ret->href) && preg_match('/images\/.*[\d]+\.jpg/i', $ret->href, $matches)) {
				$this->_res['boxcover'] = $baseUrl . $matches[0];
				$this->_res['backcover'] = $baseUrl . str_ireplace('/front/i', 'back', $matches[0]);
			}
		} elseif ($ret = $this->_html->find('img[rel=license]', 0)) {
			if (preg_match('/images\/.*[\d]+\.jpg/i', $ret->src, $matches)) {
				$this->_res['boxcover'] = $baseUrl . $matches[0];
			}
		}
		return $this->_res;
	}

	/**
	 * Gets the synopsis
	 *
	 * @return array
	 */
	protected function synopsis()
	{
		$this->_res['synopsis'] = 'N/A';
		foreach ($this->_html->find('h3') as $heading) {
			if (trim($heading->plaintext) === 'Description') {
				$this->_res['synopsis'] = trim($heading->next_sibling()->plaintext);
			}
		}

		return $this->_res;
	}

	/**
	 * Get Product Information and Director
	 *
	 *
	 * @param bool $extras
	 *
	 * @return array
	 */
	protected function productInfo($extras = false)
	{

		foreach ($this->_html->find('ul.list-unstyled li') as $li) {
			$category = explode(':', $li->plaintext);
			switch (trim($category[0])) {
				case 'Director':
					$this->_res['director'] = trim($category[1]);
					break;
				case 'Format':
				case 'Studio':
				case 'Released':
				case 'SKU':
					$this->_res['productinfo'][trim($category[0])] = trim($category[1]);
			}
		}

		return $this->_res;
	}

	/**
	 * Gets the cast members
	 * @return array
	 */
	protected function cast()
	{
		$cast = [];
		foreach ($this->_html->find('h3') as $heading) {
			if (trim($heading->plaintext) === 'Cast') {
				for ($next = $heading->next_sibling(); $next && $next->nodeName !== 'h3'; $next = $next->next_sibling()) {
					if (preg_match_all('/search_performerid/', $next->href, $matches)) {
						$cast[] = trim($next->plaintext);
					}
				}
			}
		}
		$this->_res['cast'] = array_unique($cast);

		return $this->_res;
	}

	/**
	 * Gets categories
	 * @return array
	 */
	protected function genres()
	{
		$genres = [];
		foreach ($this->_html->find('ul.list-unstyled') as $li) {
			$category = explode(':', $li->plaintext);
			if (trim($category[0]) === 'Category') {
				$genre = explode(',', $category[1]);
				foreach($genre as $g) {
					$genres[] = trim($g);
				}
				$this->_res['genres'] = $genres;
			}
		}

		return $this->_res;
	}

	/**
	 * Searches for match against searchterm
	 *
	 * @param $movie
	 *
	 * @return bool - true if search = 100%
	 */
	public function processSite($movie)
	{
		$result = false;
		if (!empty($movie)) {
			$this->_trailUrl = self::TRAILINGSEARCH . urlencode($movie);
			$this->_response = Misc::getRawHtml(self::ADMURL . $this->_trailUrl, $this->cookie);
			if ($this->_response !== false) {
				$this->_html->load($this->_response);
				if ($ret = $this->_html->find('img[rel=license]')) {
					if (count($ret) > 0) {
						foreach ($this->_html->find('img[rel=license]') as $ret) {
							if (isset($ret->alt)) {
								$title = trim($ret->alt, '"');
								$title = str_replace('/XXX/', '', $title);
								$comparetitle = preg_replace('/[\W]/', '', $title);
								$comparesearch = preg_replace('/[\W]/', '', $movie);
								similar_text($comparetitle, $comparesearch, $p);
								if ($p >= 90) {
									if (preg_match('/\/(?<sku>\d+)\.jpg/i', $ret->src, $matches)) {
										$this->_title = trim($title);
										$this->_trailUrl = '/dvd_view_' . (string)$matches['sku'] . '.html';
										$this->_directUrl = self::ADMURL . $this->_trailUrl;
										$this->_html->clear();
										unset($this->_response);
										$this->_response = Misc::getRawHtml($this->_directUrl, $this->cookie);
										$this->_html->load($this->_response);
										$result = true;
									}
								}
							}
						}
					}
				}
			}
		}
		return $result;
	}


	protected function trailers()
	{
		// TODO: Implement trailers() method.

		return false;
	}
}
