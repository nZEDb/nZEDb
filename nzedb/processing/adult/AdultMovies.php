<?php

namespace nzedb\processing\adult;

use nzedb\XXX;

abstract class AdultMovies extends XXX
{
	const PROCESS_AEBN          =  0;   // Process AEBN First
	const PROCESS_ADE           = -1;   // Process ADE Second
	const PROCESS_POPPORN       = -2;   // Process POPPORN Third
	const PROCESS_HOTMOVIES     = -3;   // Process HOTMOVIES Fourth
	const PROCESS_ADM           = -4;   // Process ADM Fifth
	const NO_MATCH_FOUND        = -6;   // Failed All Methods
	const FAILED_PARSE          = -100; // Failed Parsing

	const MATCH_PERCENT = 90;

	/**
	 * AdultMovies constructor.
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