<?php

namespace app\models;

class Group extends \lithium\data\Model
{
	public $validates = [
		'name' => [
			[
				'required' => true
			],
			[
				'notEmpty',
				'message' => 'The group\'s name is required to create a new entry.'
			]
		],
	];

	public static function findIdFromName($name, array $options = [])
	{
		$defaults = [
			'create'      => false,
			'description' => 'Auto-created by Group::' . __METHOD__ . ' model',
		];
		$options += $defaults;

		$group = self::find('first',
			[
				'fields'     => ['id'],
				'conditions' => ['name' => $name],
			]
		);

		if ($group === null && $options['create'] === true) {
			$group = self::createMissing($name,
				[
					'description'	=> $options['description']
				]
			);
		}

		return $group;
	}

	protected static function createMissing(array $data)
	{
		$defaults = [
			'active'   => false,
			'backfill' => false,
			'description' => 'Auto-created by Group::' . __METHOD__,
		];
		$data += $defaults;

		if (!isset($data['name'])) {
			throw new \InvalidArgumentException("");
		}

		$group = self::create($data);

		$group->save();

		return $group;
	}
}

?>
