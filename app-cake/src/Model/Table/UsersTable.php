<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Users Model
 *
 * @property \App\Model\Table\ForumPostsTable|\Cake\ORM\Association\HasMany $ForumPosts
 * @property \App\Model\Table\InvitationsTable|\Cake\ORM\Association\HasMany $Invitations
 * @property \App\Model\Table\ReleaseCommentsTable|\Cake\ORM\Association\HasMany $ReleaseComments
 * @property \App\Model\Table\UserDownloadsTable|\Cake\ORM\Association\HasMany $UserDownloads
 * @property \App\Model\Table\UserExcludedCategoriesTable|\Cake\ORM\Association\HasMany $UserExcludedCategories
 * @property \App\Model\Table\UserMoviesTable|\Cake\ORM\Association\HasMany $UserMovies
 * @property \App\Model\Table\UserRequestsTable|\Cake\ORM\Association\HasMany $UserRequests
 * @property \App\Model\Table\UserSeriesTable|\Cake\ORM\Association\HasMany $UserSeries
 * @property \App\Model\Table\ReleasesTable|\Cake\ORM\Association\BelongsToMany $Releases
 *
 * @method \App\Model\Entity\User get($primaryKey, $options = [])
 * @method \App\Model\Entity\User newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\User[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\User|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\User saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\User patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\User[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\User findOrCreate($search, callable $callback = null, $options = [])
 */
class UsersTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('users');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->hasMany('ForumPosts', [
            'foreignKey' => 'user_id'
        ]);
        $this->hasMany('Invitations', [
            'foreignKey' => 'user_id'
        ]);
        $this->hasMany('ReleaseComments', [
            'foreignKey' => 'user_id'
        ]);
        $this->hasMany('UserDownloads', [
            'foreignKey' => 'user_id'
        ]);
        $this->hasMany('UserExcludedCategories', [
            'foreignKey' => 'user_id'
        ]);
        $this->hasMany('UserMovies', [
            'foreignKey' => 'user_id'
        ]);
        $this->hasMany('UserRequests', [
            'foreignKey' => 'user_id'
        ]);
        $this->hasMany('UserSeries', [
            'foreignKey' => 'user_id'
        ]);
        $this->belongsToMany('Releases', [
            'foreignKey' => 'user_id',
            'targetForeignKey' => 'release_id',
            'joinTable' => 'users_releases'
        ]);

		$this->hasOne('Couchpotato', [
			'foreignKey' => 'user_id'
		]);

		$this->hasOne('Nzbget', [
			'foreignKey' => 'user_id'
		]);

		$this->hasOne('Roles', [
			'foreignKey' => 'role_id'
		]);

		$this->hasOne('Sabnzb', [
			'foreignKey' => 'user_id'
		]);
	}

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->nonNegativeInteger('id')
            ->allowEmptyString('id', 'create');

        $validator
            ->scalar('username')
            ->maxLength('username', 50)
            ->requirePresence('username', 'create')
            ->allowEmptyString('username', 'You must provide a username', false);

        $validator
            ->scalar('firstname')
            ->maxLength('firstname', 255)
            ->allowEmptyString('firstname');

        $validator
            ->scalar('lastname')
            ->maxLength('lastname', 255)
            ->allowEmptyString('lastname');

        $validator
            ->email('email')
            ->requirePresence('email', 'create')
            ->allowEmptyString('email', 'An e-mail is required', false);

        $validator
            ->scalar('password')
            ->maxLength('password', 255)
            ->requirePresence('password', 'create')
            ->allowEmptyString('password', 'A password is required', false);

        $validator
            ->integer('role')
            ->allowEmptyString('role', 'Role is required', false);

        $validator
            ->scalar('host')
            ->maxLength('host', 40)
            ->allowEmptyString('host');

        $validator
            ->integer('grabs')
            ->allowEmptyString('grabs', 'Required', false);

        $validator
            ->scalar('rsstoken')
            ->maxLength('rsstoken', 32)
            ->requirePresence('rsstoken', 'create')
            ->allowEmptyString('rsstoken', 'Required', false);

        $validator
            ->dateTime('created')
            ->requirePresence('created', 'create')
            ->allowEmptyDateTime('created');

        $validator
            ->scalar('resetguid')
            ->maxLength('resetguid', 50)
            ->allowEmptyString('resetguid');

        $validator
            ->dateTime('lastlogin')
            ->allowEmptyDateTime('lastlogin');

        $validator
            ->dateTime('apiaccess')
            ->allowEmptyDateTime('apiaccess');

        $validator
            ->integer('invites')
            ->allowEmptyString('invites');

        $validator
            ->integer('invitedby')
            ->allowEmptyString('invitedby');

        $validator
            ->integer('movieview')
            ->allowEmptyString('movieview');

        $validator
            ->integer('xxxview')
            ->allowEmptyString('xxxview');

        $validator
            ->integer('musicview')
            ->allowEmptyString('musicview');

        $validator
            ->integer('consoleview')
            ->allowEmptyString('consoleview');

        $validator
            ->integer('bookview')
            ->allowEmptyString('bookview');

        $validator
            ->integer('gameview')
            ->allowEmptyString('gameview');

        $validator
            ->boolean('queuetype')
            ->allowEmptyString('queuetype');

        $validator
            ->scalar('userseed')
            ->maxLength('userseed', 50)
            ->requirePresence('userseed', 'create')
            ->allowEmptyString('userseed', 'Required', false);

        $validator
            ->scalar('style')
            ->maxLength('style', 255)
            ->allowEmptyString('style');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->isUnique(['username']));
        $rules->add($rules->isUnique(['email']));

        return $rules;
    }
}
