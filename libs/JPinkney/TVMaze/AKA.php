<?php

namespace libs\JPinkney\TVMaze;

Class AKA
{
	/**
	 * @param $aka_data
	 */
	function __construct($aka_data)
	{
		if(!empty($aka_data['name'])) {
			$this->akas = $aka_data['name'];
		} else {
			$this->akas = '';
		}
	}
}
