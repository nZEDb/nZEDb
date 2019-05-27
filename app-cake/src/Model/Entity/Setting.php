<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Setting Entity
 *
 * @property string $section
 * @property string $subsection
 * @property string $name
 * @property string $value
 * @property string $hint
 * @property string $setting
 */
class Setting extends Entity
{
	public const REGISTER_STATUS_OPEN = 0;

	public const REGISTER_STATUS_INVITE = 1;

	public const REGISTER_STATUS_CLOSED = 2;

	public const REGISTER_STATUS_API_ONLY = 3;

	public const ERR_BADUNRARPATH = -1;

	public const ERR_BADFFMPEGPATH = -2;

	public const ERR_BADMEDIAINFOPATH = -3;

	public const ERR_BADNZBPATH = -4;

	public const ERR_DEEPNOUNRAR = -5;

	public const ERR_BADTMPUNRARPATH = -6;

	public const ERR_BADNZBPATH_UNREADABLE = -7;

	public const ERR_BADNZBPATH_UNSET = -8;

	public const ERR_BAD_COVERS_PATH = -9;

	public const ERR_BAD_YYDECODER_PATH = -10;

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
        'value' => true,
        'hint' => true,
        'setting' => true
    ];
}
