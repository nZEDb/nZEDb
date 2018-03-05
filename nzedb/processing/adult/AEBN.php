<?php
namespace nzedb\processing\adult;

use nzedb\utility\Misc;

class AEBN extends AdultMovies
{
	/**
	 * Keyword to search
	 *
	 * @var string
	 */
	public $searchTerm = '';

	/**
	 * Url Constants used within this class
	 */
	const AEBNSURL = 'http://straight.theater.aebn.net';
	const IF18 = 'http://straight.theater.aebn.net/dispatcher/frontDoor?genreId=101&theaterId=13992&locale=en&refid=AEBN-000001';
	const TRAILINGSEARCH = '/dispatcher/fts?theaterId=13992&genreId=101&locale=en&count=30&imageType=Large&targetSearchMode=basic&isAdvancedSearch=false&isFlushAdvancedSearchCriteria=false&sortType=Relevance&userQuery=title%3A+%2B';
	const TRAILERURL = '/dispatcher/previewPlayer?locale=en&theaterId=13992&genreId=101&movieId=';

	/**
	 * Direct Url in getAll method
	 *
	 * @var string
	 */
	protected $_directUrl = '';

	/**
	 * Raw Html response from curl
	 *
	 */
	protected $_response;

	/**
	 * @var string
	 */
	protected $_trailerUrl = '';

	/**
	 * Returned results in all methods except search/geturl
	 *
	 * @var array|false
	 */
	protected $_res = [
		'backcover'   => [],
		'boxcover'    => [],
		'cast'        => [],
		'director'    => [],
		'genres'      => [],
		'productinfo' => [],
		'synopsis'    => [],
		'trailers'    => ['url' => []],
	];

	/**
	 * Sets title in getAll method
	 *
	 * @var string
	 */
	protected $_title = '';


	/**
	 * Sets the variables that used throughout the class
	 *
	 * @param array $options
	 *
	 * @throws \Exception
	 */
	public function __construct(array $options = [])
	{
		parent::__construct($options);
	}

	/**
	 * Gets Trailer URL .. will be processed in XXX insertswf
	 *
	 * @return array|bool
	 */
	protected function trailers()
	{
		$ret = $this->_html->find('a[itemprop=trailer]', 0);
		if (!empty($ret) && preg_match('/movieId=(?<movieid>\d+)&/', trim($ret->href), $matches)) {
			$movieid = $matches['movieid'];
			$this->_res['trailers']['url'] = self::AEBNSURL . self::TRAILERURL . $movieid;
		}

		return $this->_res;
	}

	/**
	 * Gets the front and back cover of the box
	 *
	 * @return array
	 */
	protected function covers()
	{
		$ret = $this->_html->find('div#md-boxCover, img[itemprop=thumbnailUrl]', 1);
		if ($ret !== false) {
			$ret = trim($ret->src);
			if (strpos($ret, '//') === 0) {
				$ret = 'http:' . $ret;
			}
			$this->_res['boxcover'] = str_ireplace('160w.jpg', 'xlf.jpg', $ret);
			$this->_res['backcover'] = str_ireplace('160w.jpg', 'xlb.jpg', $ret);
		}

		return $this->_res;
	}

	/**
	 * Gets the Genres "Categories".
	 *
	 * @return array
	 */
	protected function genres()
	{
		if ($ret = $this->_html->find('div.md-detailsCategories', 0)) {
			foreach ($ret->find('a[itemprop=genre]') as $genre) {
				$this->_res['genres'][] = trim($genre->plaintext);
			}
		}
		if (!empty($this->_res['genres'])) {
			$this->_res['genres'] = array_unique($this->_res['genres']);
		}
		return $this->_res;
	}

	/**
	 * Gets the Cast Members "Stars" and Director if any
	 *
	 * @return array
	 */
	protected function cast()
	{
		$this->_res = false;
		if ($ret = $this->_html->find('div.starsFull', 0)) {
			foreach ($ret->find('span[itemprop=name]') as $star) {
				$this->_res['cast'][] = trim($star->plaintext);
			}
		} else {
			if ($ret = $this->_html->find('div.detailsLink', 0)) {
				foreach ($ret->find('span') as $star) {
					if (strpos($star->plaintext, '/More/') !== false && strpos($star->plaintext, '/Stars/') !== false) {
						$this->_res['cast'][] = trim($star->plaintext);
					}
				}
			}
		}

		return $this->_res;
	}

	/**
	 * Gets the product information
	 *
	 * @param bool $extras
	 *
	 * @return array
	 */
	protected function productInfo($extras = false)
	{
		if ($ret = $this->_html->find('div#md-detailsLeft', 0)) {
			foreach ($ret->find('div') as $div) {
				foreach ($div->find('span') as $span) {
					$span->plaintext = rawurldecode($span->plaintext);
					$span->plaintext = preg_replace('/&nbsp;/', '', $span->plaintext);
					$this->_res['productinfo'][] = trim($span->plaintext);
				}
			}
			if (false !== $key = array_search('Running Time:', $this->_res['productinfo'], false)) {
				unset($this->_res['productinfo'][$key + 2]);
			}
			if (false !== $key = array_search('Director:' , $this->_res['productinfo'], false)) {
				$this->_res['director'] = $this->_res['productinfo'][$key + 1];
				unset($this->_res['productinfo'][$key], $this->_res['productinfo'][$key + 1]);
			}
			$this->_res['productinfo'] = array_chunk($this->_res['productinfo'], 2, false);
		}

		return $this->_res;
	}

	/**
	 * Gets the synopsis "plot"
	 *
	 * @return array
	 *
	 */
	protected function synopsis()
	{
		if ($ret = $this->_html->find('span[itemprop=about]', 0)) {
			if ($ret === null) {
				if ($ret = $this->_html->find('div.movieDetailDescription', 0)) {
					$this->_res['synopsis'] = preg_replace('/Description:\s/', '', $this->_res['plot']);
				}
			} else {
				$this->_res['synopsis'] = trim($ret->plaintext);
			}
		}

		return $this->_res;
	}

	/**
	 * Searches for a XXX name
	 *
	 * @param string $movie
	 *
	 * @return bool
	 */
	public function processSite($movie)
	{
		if (empty($movie)) {
			return false;
		}
		$this->_trailerUrl = self::TRAILINGSEARCH . urlencode($movie);
		$this->_response = Misc::getRawHtml(self::AEBNSURL . $this->_trailerUrl, $this->cookie);
		if ($this->_response !== false) {
			$this->_html->load($this->_response);
			$i = 1;
			foreach ($this->_html->find('div.movie') as $mov) {
				$string = 'a#FTSMovieSearch_link_title_detail_' . $i;
				if ($ret = $mov->find($string, 0)) {
					$title = str_replace('/XXX/', '', $ret->title);
					$title = preg_replace('/\(.*?\)|[-._]/', ' ', $title);
					$title = trim($title);
					similar_text(strtolower($movie), strtolower($title), $p);
					if ($p >= 90) {
						$this->_title = trim($ret->title);
						$this->_trailerUrl = html_entity_decode($ret->href);
						$this->_directUrl = self::AEBNSURL . $this->_trailerUrl;
						$this->_html->clear();
						unset($this->_response);
						$this->_response = Misc::getRawHtml(self::AEBNSURL . $this->_trailerUrl, $this->cookie);
						$this->_html->load($this->_response);

						return true;
					}
					continue;
				}
				$i++;
			}
		}

		return false;
	}
}
