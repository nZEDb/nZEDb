<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Binaryblacklist Entity
 *
 * @property int $id
 * @property string|null $groupname
 * @property string $regex
 * @property int $msgcol
 * @property int $optype
 * @property int $status
 * @property string|null $description
 * @property \Cake\I18n\FrozenDate|null $last_activity
 */
class Binaryblacklist extends Entity
{
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
        'groupname' => true,
        'regex' => true,
        'msgcol' => true,
        'optype' => true,
        'status' => true,
        'description' => true,
        'last_activity' => true
    ];
}
