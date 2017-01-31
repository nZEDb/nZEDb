<?php

namespace app\models;

class CollectionRegexes extends Regexes
{
	public $_meta = [
		'connection' => 'default',
		'key'        => 'id',
		'source'     => 'collection_regexes',
	];

	public $validates = [];
}

?>
