<?php

namespace nzedb\processing\adult;

use nzedb\XXX;

abstract class AdultMovies extends XXX
{

	/**
	 * @var \simple_html_dom
	 */
	protected $_html;

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
		parent::__construct($options);
	}

	/**
	 * @return mixed
	 */
	abstract protected function productInfo();

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
	abstract protected function processSite($movie);

	/**
	 * @return mixed
	 */
	abstract protected function getAll();

	/**
	 * @return mixed
	 */
	abstract protected function trailers();
}