<?php
namespace App\Model\Entity;

use Authentication\IdentityInterface;
use Cake\Auth\DefaultPasswordHasher;
use Cake\ORM\Entity;
use Ramsey\Uuid\Uuid;


/**
 * User Entity
 *
 * @property int $id
 * @property string $username
 * @property string|null $firstname
 * @property string|null $lastname
 * @property string $email
 * @property string $password
 * @property int $role
 * @property string|null $host
 * @property int $grabs
 * @property string $rsstoken
 * @property \Cake\I18n\FrozenTime $createddate
 * @property string|null $resetguid
 * @property \Cake\I18n\FrozenTime|null $lastlogin
 * @property \Cake\I18n\FrozenTime|null $apiaccess
 * @property int $invites
 * @property int|null $invitedby
 * @property int $movieview
 * @property int $xxxview
 * @property int $musicview
 * @property int $consoleview
 * @property int $bookview
 * @property int $gameview
 * @property string|null $saburl
 * @property string|null $sabapikey
 * @property bool|null $sabapikeytype
 * @property bool|null $sabpriority
 * @property bool $queuetype
 * @property string|null $nzbgeturl
 * @property string|null $nzbgetusername
 * @property string|null $nzbgetpassword
 * @property string $userseed
 * @property string|null $cp_url
 * @property string|null $cp_api
 * @property string|null $style
 *
 * @property \App\Model\Entity\ForumPost[] $forum_posts
 * @property \App\Model\Entity\Invitation[] $invitations
 * @property \App\Model\Entity\ReleaseComment[] $release_comments
 * @property \App\Model\Entity\UserDownload[] $user_downloads
 * @property \App\Model\Entity\UserExcludedCategory[] $user_excluded_categories
 * @property \App\Model\Entity\UserMovie[] $user_movies
 * @property \App\Model\Entity\UserRequest[] $user_requests
 * @property \App\Model\Entity\UserSeries[] $user_series
 * @property \App\Model\Entity\Release[] $releases
 */
class User extends Entity implements IdentityInterface
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
		'username' => true,
		'firstname' => true,
		'lastname' => true,
		'email' => true,
		'password' => true,
		'role' => true,
		'host' => true,
		'grabs' => true,
		'rsstoken' => true,
		'createddate' => true,
		'resetguid' => true,
		'lastlogin' => true,
		'apiaccess' => true,
		'invites' => true,
		'invitedby' => true,
		'movieview' => true,
		'xxxview' => true,
		'musicview' => true,
		'consoleview' => true,
		'bookview' => true,
		'gameview' => true,
		'userseed' => true,
		'cp_url' => true,
		'cp_api' => true,
		'style' => true,
		'forum_posts' => true,
		'invitations' => true,
		'release_comments' => true,
		'user_downloads' => true,
		'user_excluded_categories' => true,
		'user_movies' => true,
		'user_requests' => true,
		'user_series' => true,
		'releases' => true
	];

	/**
	 * Fields that are excluded from JSON versions of the entity.
	 *
	 * @var array
	 */
	protected $_hidden = [
		'password'
	];

	/**
	 * User constructor.
	 *
	 * @param array $properties
	 * @param array $options
	 */
	public function __construct(array $properties = [], array $options = [])
	{
		parent::__construct($properties, $options);

		if (empty($this->rsstoken) || !Uuid::isValid(Uuid::fromString($this->rsstoken)))
		{
			$this->rsstoken = Uuid::uuid1()->getHex();
		}
	}

	/**
	 * Authentication\IdentityInterface method
	 */
	public function getIdentifier()
	{
		return $this->id;
	}

	/**
	 * Authentication\IdentityInterface method
	 */
	public function getOriginalData()
	{
		return $this;
	}

	protected function _setPassword($value)
	{
		if ($value !== '') {
			$hasher = new DefaultPasswordHasher();

			return $hasher->hash($value);
		}
	}
}
