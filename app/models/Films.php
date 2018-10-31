<?php

namespace app\models;

class Films extends \app\extensions\data\Model
{
	protected $_meta = [
		'source'     => 'movies',
	];

	public $validates = [];
}

?>
