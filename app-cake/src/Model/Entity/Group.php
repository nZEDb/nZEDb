<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Group Entity
 *
 * @property int $id
 * @property string $name
 * @property int $backfill_target
 * @property int $first_record
 * @property \Cake\I18n\FrozenTime|null $first_record_postdate
 * @property int $last_record
 * @property \Cake\I18n\FrozenTime|null $last_record_postdate
 * @property \Cake\I18n\FrozenTime|null $last_updated
 * @property int|null $minfilestoformrelease
 * @property int|null $minsizetoformrelease
 * @property bool $active
 * @property bool $backfill
 * @property string|null $description
 *
 * @property \App\Model\Entity\Release[] $releases
 */
class Group extends Entity
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
        'name' => true,
        'backfill_target' => true,
        'first_record' => true,
        'first_record_postdate' => true,
        'last_record' => true,
        'last_record_postdate' => true,
        'last_updated' => true,
        'minfilestoformrelease' => true,
        'minsizetoformrelease' => true,
        'active' => true,
        'backfill' => true,
        'description' => true,
        'releases' => true
    ];
}
