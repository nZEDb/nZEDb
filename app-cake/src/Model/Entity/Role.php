<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * UserRole Entity
 *
 * @property int $id
 * @property string $name
 * @property int $apirequests
 * @property int $downloadrequests
 * @property int $defaultinvites
 * @property bool $isdefault
 * @property bool $canpreview
 */
class Role extends Entity
{
	public const DISABLED = 0;

	public const GUEST = 10;

	public const USER = 20;

	public const MODERATOR = 30;

	public const FRIEND = 40;

	public const ADMIN = 100;

	/**
	 * Fields that can be mass assigned using newEntity() or patchEntity().
	 *
	 * Note that when '*' is set to true, this allows all unspecified fields to
	 * be mass assigned. For security purposes, it is advised to set '*' to false
	 * (or remove it), and explicitly make individual fields accessible as needed.
	 *
	 * @var array
	 */
	protected $_accessible = [
		'name' => true,
		'apirequests' => true,
		'downloadrequests' => true,
		'defaultinvites' => true,
		'isdefault' => true,
		'canpreview' => true
	];
}
