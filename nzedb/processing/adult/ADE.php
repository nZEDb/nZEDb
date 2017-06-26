<?php
namespace nzedb\processing\adult;

use nzedb\utility\Misc;

/**
 * Class adultdvdempire
 */
class ADE extends AdultMovies
{
	/**
	 * If a direct link is given parse it rather then search
	 * @var string
	 */
	public $directLink = '';

	/**
	 * If a string is found do call back.
	 * @var bool
	 */
	public $found = false;

	/**
	 * Search keyword
	 * @var string
	 */
	public $searchTerm = '';

	/**
	 * Define ADE Url here
	 */
	const ADE = 'http://www.adultdvdempire.com';

	/**
	 * Direct Url returned in getAll method
	 *
	 * @var string
	 */
	protected $_directUrl = '';

	/**
	 * If a url is found that matches the keyword
	 *
	 */
	protected $_urlFound;

	/**
	 * Sets the title in the getAll method
	 *
	 * @var string
	 */
	protected $_title = '';

	/** Trailing urls */
	protected $_dvdQuery = '/dvd/search?q=';
	protected $_scenes = '/scenes';
	protected $_boxCover = '/boxcover';
	protected $_backCover = '/backcover';
	protected $_reviews = '/reviews';
	protected $_trailers = '/trailers';


	protected $_url;
	protected $_response;
	protected $_res = [];
	protected $_tmpResponse;
	protected $_ch;

	public function __construct(array $options = [])
	{
		parent::__construct($options);
	}

	/**
	 * Gets Trailer Movies
	 * @return array - url, streamid, basestreamingurl
	 */
	public function trailers()
	{
		$this->_response = Misc::getRawHtml(self::ADE . $this->_trailers . $this->_directUrl);
		$this->_html->load($this->_response);
		if (preg_match("/(\"|')(?P<swf>[^\"']+.swf)(\"|')/i", $this->_response, $matches)) {
			$this->_res['trailers']['url'] = self::ADE . trim(trim($matches['swf']), '"');
			if (preg_match('#(?:streamID:\s\")(?P<streamid>[0-9A-Z]+)(?:\")#',
				$this->_response,
				$matches)
			) {
				$this->_res['trailers']['streamid'] = trim($matches['streamid']);
			}
			if (preg_match('#(?:BaseStreamingUrl:\s\")(?P<baseurl>[\d]+.[\d]+.[\d]+.[\d]+)(?:\")#',
				$this->_response,
				$matches)
			) {
				$this->_res['trailers']['baseurl'] = $matches['baseurl'];
			}
		}

		return $this->_res;
	}

	/**
	 * Gets cover images for the xxx release
	 * @return array - Boxcover and backcover
	 */
	public function covers()
	{
		if ($ret = $this->_html->find('div#Boxcover, img[itemprop=image]', 1)) {
			$this->_res['boxcover'] = preg_replace('/m\.jpg/', 'h.jpg', $ret->src);
			$this->_res['backcover'] = preg_replace('/m\.jpg/', 'bh.jpg', $ret->src);
		}

		return $this->_res;
	}

	/**
	 * Gets the synopsis
	 *
	 * @return array - plot
	 */
	public function synopsis()
	{
		$ret = $this->_html->find('meta[name=og:description]', 0)->content;
		if ($ret !== false) {
			$this->_res['synopsis'] = trim($ret);
		}

		return $this->_res;
	}

	/**
	 * Gets the cast members and/or awards
	 *
	 *
	 * @return array - cast, awards
	 */
	public function cast()
	{
		$cast = [];
		foreach ($this->_html->find('[Label="Performers - detail"]') as $a) {
			if ($a->plaintext !== false) {
				$cast[] = trim($a->plaintext);
				}
			}
		$this->_res['cast'] = $cast;
		return $this->_res;
	}

	/**
	 * Gets Genres, if exists return array else return false
	 * @return mixed array - Genres
	 */
	public function genres()
	{
		$genres = [];
		foreach ($this->_html->find('[Label="Category"]') as $a) {
			if ($a->plaintext !== false) {
				$genres[] = trim($a->plaintext);
			}
		}
		$this->_res['genres'] = $genres;
		return $this->_res;
	}

	/**
	 * Gets Product Information and/or Features
	 *
	 * @param bool $features Include features? true/false
	 *
	 * @return array - ProductInfo/Extras = features
	 */
	public function productInfo($features = false)
	{
		$dofeature = null;
		$this->_tmpResponse = str_ireplace('Section ProductInfo', 'spdinfo', $this->_response);
		$this->_html->load($this->_tmpResponse);
		if ($ret = $this->_html->find('div[class=spdinfo]', 0)) {
			$this->_tmpResponse = trim($ret->outertext);
			$ret                = $this->_html->load($this->_tmpResponse);
			foreach ($ret->find("text") as $strong) {
				if (trim($strong->innertext) === 'Features') {
					$dofeature = true;
				}
				if ($dofeature !== true) {
					if (trim($strong->innertext) !== '&nbsp;') {
						$this->_res['productinfo'][] = trim($strong->innertext);
					}
				} else {
					if ($features === true) {
						$this->_res['extras'][] = trim($strong->innertext);
					}
				}
			}

			array_shift($this->_res['productinfo']);
			array_shift($this->_res['productinfo']);
			$this->_res['productinfo'] = array_chunk($this->_res['productinfo'], 2, false);
		}

		return $this->_res;
	}

	/**
	 * Searches xxx name.
	 *
	 * @param string $movie
	 *
	 * @return bool - True if releases has 90% match, else false
	 */
	public function processSite($movie)
	{
		if (empty($movie)) {
			return false;
		}
		$this->_response = Misc::getRawHtml(self::ADE . $this->_dvdQuery . rawurlencode($movie));
		if ($this->_response !== false) {
			$this->_html->load($this->_response);
			if ($res = $this->_html->find('a[class=boxcover]')) {
				foreach ($res as $ret) {
					$title = $ret->title;
					$title = str_replace('/XXX/', '', $title);
					$title = preg_replace('/\(.*?\)|[-._]/', ' ', $title);
					$url = (string)trim($ret->href);
					similar_text(strtolower($movie), strtolower($title), $p);
					if ($p >= 90) {
						$this->_directUrl = self::ADE . $url;
						$this->_title = trim($title);
						$this->_html->clear();
						unset($this->_response);
						$this->_response = Misc::getRawHtml($this->_directUrl);
						$this->_html->load($this->_response);
						return true;
					}
					continue;
				}
				return false;
			}
			return false;
		}
		return false;
	}

	/**
	 * Gets All Information from the methods
	 *
	 * @return array
	 */
	protected function getAll()
	{
		$results = [];
		if (!empty($this->_directUrl)) {
			$results['directurl'] = $this->_directUrl;
			$results['title']     = $this->_title;
		}
		if (is_array($this->synopsis())) {
			$results = array_merge($results, $this->synopsis());
		}
		if (is_array($this->productInfo(true))) {
			$results = array_merge($results, $this->productInfo(true));
		}
		if (is_array($this->cast())) {
			$results = array_merge($results, $this->cast());
		}
		if (is_array($this->genres())) {
			$results = array_merge($results, $this->genres());
		}
		if (is_array($this->covers())) {
			$results = array_merge($results, $this->covers());
		}
		if (is_array($this->trailers())) {
			$results = array_merge($results, $this->trailers());
		}
		return $results;
	}
}
