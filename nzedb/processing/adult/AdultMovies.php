<?php

namespace nzedb\processing\adult;


abstract class AdultMovies
{
	/**
	 * @var \simple_html_dom
	 */
	protected $_html;

	/**
	 * @var string
	 */
	protected $_title;

	/**
	 * @var string
	 */
	protected $_directUrl;

	/**
	 * AdultMovies constructor.
	 *
	 * @param array $options
	 *
	 * @throws \Exception
	 */
	public function __construct(array $options = [])
	{
		$this->_html = new \simple_html_dom();
	}

	/**
	 * @param bool $extras
	 *
	 * @return mixed
	 */
	abstract protected function productInfo($extras = false);

	/**
	 * @return mixed
	 */
	abstract protected function covers();

	/**
	 * @return mixed
	 */
	abstract protected function synopsis();

	/**
	 * @return mixed
	 */
	abstract protected function cast();

	/**
	 * @return mixed
	 */
	abstract protected function genres();

	/**
	 * @param string $movie
	 *
	 * @return mixed
	 */
	abstract public function processSite($movie);

	/**
	 * @return mixed
	 */
	abstract protected function trailers();

	/**
	 * Gets all information
	 *
	 * @return array|bool
	 */
	public function getAll()
	{
		$results = [];
		if ($this->_directUrl !== null) {
			$results['title'] = $this->_title;
			$results['directurl'] = $this->_directUrl;
		}

		$dummy = $this->synopsis();
		if (is_array($dummy)) {
			$results = array_merge($results, $dummy);
		}

		$dummy = $this->productInfo(true);
		if (is_array($dummy)) {
			$results = array_merge($results, $dummy);
		}

		$dummy = $this->cast();
		if (is_array($dummy)) {
			$results = array_merge($results, $dummy);
		}

		$dummy = $this->genres();
		if (is_array($dummy)) {
			$results = array_merge($results, $dummy);
		}

		$dummy = $this->covers();
		if (is_array($dummy)) {
			$results = array_merge($results, $dummy);
		}

		$dummy = $this->trailers();
		if (is_array($dummy)) {
			$results = array_merge($results, $dummy);
		}
		if (empty($results)) {
			return false;
		}

		return $results;
	}
}