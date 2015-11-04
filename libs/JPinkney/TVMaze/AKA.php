<?php

namespace libs\JPinkney\TVMaze;

Class AKA
{
	/**
	 * @param $aka_data
	 */
	function __construct($aka_data)
	{
		$this->akas = $aka_data['name'];
	}
}