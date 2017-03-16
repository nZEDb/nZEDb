<?php

namespace app\models;

class ReleaseComments extends \lithium\data\Model
{
	public $belongsTo = [
		'Releases' 	=> [
			'to'		=> 'Releases',
			'key'		=> 'releases_id',
			'fields'	=> 'guid',
		]
	];

	public $validates = [];

	public static function findRange($page = 1, $limit = ITEMS_PER_PAGE)
	{
		$options = [
			'limit' => $limit,
			'order' => ['createddate' => 'ASC'],
			'page'  => (int)$page,
			'with'	=> 'Releases',
		];

		return ReleaseComments::find('all', $options);
	}
}
?>
