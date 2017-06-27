<?php
namespace nzedb\processing\adult;

use nzedb\utility\Misc;

class Popporn extends AdultMovies
{

	/**
	 * Define a cookie file location for curl
	 *
	 * @var string string
	 */
	public $cookie = '';

	/**
	 * Set this for what you are searching for.
	 *
	 * @var string
	 */
	protected $searchTerm = '';

	/**
	 * Override if 18 years+ or older
	 * Define Popporn url
	 * Needed Search Queries Constant
	 */
	const IF18 = 'http://www.popporn.com/popporn/4';
	const POPURL = 'http://www.popporn.com';
	const TRAILINGSEARCH = '/results/index.cfm?v=4&g=0&searchtext=';

	/**
	 * Sets the directurl for the return results array
	 *
	 * @var string
	 */
	protected $_directUrl = '';

	/**
	 * Curl Raw Html
	 */
	protected $_response;

	/**
	 * Results returned from each method
	 *
	 * @var array
	 */
	protected $_res = [];

	/**
	 * This is set in the getAll method
	 *
	 * @var string
	 */
	protected $_title = '';

	/**
	 * Add this to popurl to get results
	 *
	 * @var string
	 */
	protected $_trailUrl = '';

	public function __construct(array $options = [])
	{
		parent::__construct($options);
	}

	/**
	 * Get Box Cover Images
	 *
	 * @return array - boxcover,backcover
	 */
	protected function covers()
	{
		if ($ret = $this->_html->find('div[id=box-art], a[rel=box-art]', 1)) {
			$this->_res['boxcover'] = trim($ret->href);
			if (false !== stripos(trim($ret->href), '_aa')) {
				$this->_res['backcover'] = str_ireplace('_aa', '_bb', trim($ret->href));
			} else {
				$this->_res['backcover'] = str_ireplace('.jpg', '_b.jpg', trim($ret->href));
			}
		} else {
			if ($ret = $this->_html->find('img.front', 0)) {
				$this->_res['boxcover'] = $ret->src;
			}
			if ($ret = $this->_html->find('img.back', 0)) {
				$this->_res['backcover'] = $ret->src;
			}
		}

		return $this->_res;
	}

	/**
	 * Gets the synopsis
	 *
	 * @return array|bool
	 */
	protected function synopsis()
	{
		if ($ret = $this->_html->find('div[id=product-info] ,h3[class=highlight]', 1)) {
			if ($ret->next_sibling()->plaintext) {
				if (stripos(trim($ret->next_sibling()->plaintext), 'POPPORN EXCLUSIVE') === false) {
					$this->_res['synopsis'] = trim($ret->next_sibling()->plaintext);
				} else {
					if ($ret->next_sibling()->next_sibling()) {
						$this->_res['synopsis'] = trim($ret->next_sibling()->next_sibling()->next_sibling()->plaintext);
					} else {
						$this->_res['synopsis'] = 'N/A';
					}
				}
			}
		}

		return $this->_res;
	}

	/**
	 * Gets trailer video
	 *
	 * @return array|bool
	 */
	protected function trailers()
	{
		if ($ret = $this->_html->find('input#thickbox-trailer-link', 0)) {
			$ret->value = trim($ret->value);
			$ret->value = str_replace('..', '', $ret->value);
			$tmprsp = $this->_response;
			$this->_trailUrl = $ret->value;
			if (preg_match_all('/productID="\+(?<id>[0-9]+),/', $this->_response, $matches)) {
				$productid = $matches['id'][0];
				$random = ((float)mt_rand() / (float)mt_getrandmax()) * 5400000000000000;
				$this->_trailUrl = '/com/tlavideo/vod/FlvAjaxSupportService.cfc?random=' . $random;
				$this->_postParams = 'method=pipeStreamLoc&productID=' . $productid;
				$ret = json_decode(json_decode($this->_response, true), true);
				$this->_res['trailers']['baseurl'] = self::POPURL . '/flashmediaserver/trailerPlayer.swf';
				$this->_res['trailers']['flashvars'] = 'subscribe=false&image=&file=' . self::POPURL . '/' . $ret['LOC'] . '&autostart=false';
				unset($this->_response);
				$this->_response = $tmprsp;
			}
		}

		return $this->_res;
	}

	/**
	 * Process ProductInfo And/or Extras
	 *
	 * @param bool $extras
	 *
	 * @return array|bool
	 */
	protected function productInfo($extras = false)
	{
		$country = false;
		if ($ret = $this->_html->find('div#lside', 0)) {
			foreach ($ret->find("text") as $e) {
				$e = trim($e->innertext);
				$e = str_replace([', ', '...', '&nbsp;'], '', $e);
				if (stripos($e, 'Country:') !== false) {
					$country = true;
				}
				if ($country === true) {
					if (stripos($e, 'addthis_config') === false) {
						if (!empty($e)) {
							$this->_res['productinfo'][] = $e;
						}
					} else {
						break;
					}
				}
			}
		}

		$this->_res['productinfo'] = array_chunk($this->_res['productinfo'], 2, false);

		if ($extras === true) {
			$features = false;
			if ($this->_html->find('ul.stock-information', 0)) {
				foreach ($this->_html->find('ul.stock-information') as $ul) {
					foreach ($ul->find('li') as $e) {
						$e = trim($e->plaintext);
						if ($e === 'Features:') {
							$features = true;
							$e = null;
						}
						if ($features === true) {
							if (!empty($e)) {
								$this->_res['extras'][] = $e;
							}
						}
					}
				}
			}
		}

		return $this->_res;
	}

	/**
	 * Gets the cast members and director
	 *
	 * @return array|bool
	 */
	protected function cast()
	{
		$cast = false;
		$director = false;
		$er = [];
		if ($ret = $this->_html->find('div#lside', 0)) {
			foreach ($ret->find('text') as $e) {
				$e = trim($e->innertext);
				$e = str_replace(',', '', $e);
				$e = str_replace('&nbsp;', '', $e);
				if (stripos($e, 'Cast') !== false) {
					$cast = true;
				}
				$e = str_replace('Cast:', '', $e);
				if ($cast === true) {
					if (stripos($e, 'Director:') !== false) {
						$director = true;
						$e = null;
					}

					if ($director === true) {
						if (!empty($e)) {
							$this->_res['director'] = $e;
							$director = false;
							$e = null;
						}
					}
					if (stripos($e, 'Country:') === false) {
						if (!empty($e)) {
							$er[] = $e;
						}
					} else {
						break;
					}
				}
			}
		}
		$this->_res['cast'] = $er;

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
		if ($ret = $this->_html->find('div[id=thekeywords], p[class=keywords]', 1)) {
			foreach ($ret->find('a') as $e) {
				$genres[] = trim($e->plaintext);
			}
		}
		$this->_res['genres'] = $genres;

		return $this->_res;
	}

	/**
	 * Searches for match against searchterm
	 *
	 * @param string $movie
	 *
	 * @return bool , true if search >= 90%
	 */
	public function processSite($movie)
	{
		if (!empty($movie)) {
			$this->_trailUrl = self::TRAILINGSEARCH . $movie;
			$this->_response = Misc::getRawHtml(self::POPURL . $this->_trailUrl, $this->cookie);
			if ($this->_response !== false) {
				$this->_html->load($this->_response);
				if ($ret = $this->_html->find('div.product-info, div.title', 1)) {
					$this->_title = trim($ret->plaintext);
					$title = str_replace('XXX', '', $ret->plaintext);
					$title = preg_replace('/\(.*?\)|[-._]/i', ' ', $title);
					$title = trim($title);
					similar_text(strtolower($movie), strtolower($title), $p);
					if ($p >= 90) {
						if ($ret = $ret->find('a', 0)) {
							$this->_trailUrl = trim($ret->href);
							$this->_html->clear();
							unset($this->_response);
							$this->_response = Misc::getRawHtml(self::POPURL . $this->_trailUrl, $this->cookie);
							if ($this->_response !== false) {
								$this->_html->load($this->_response);
								if ($ret = $this->_html->find('#link-to-this', 0)) {
									$this->_directUrl = trim($ret->href);
									$this->_html->clear();
									unset($this->_response);
									$this->_response = Misc::getRawHtml($this->_directUrl, $this->cookie);
									$this->_html->load($this->_response);

									return true;
								}

								return false;
							}
						}

						return true;
					}
				}
			} else {
				$this->_response = Misc::getRawHtml(self::IF18, $this->cookie);
				if ($this->_response !== false) {
					$this->_html->load($this->_response);

					return true;
				}

				return false;
			}

			return false;
		}

		return false;
	}
}
