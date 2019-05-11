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
            ->allowEmptyString('username', false);

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
            ->allowEmptyString('email', false);

        $validator
            ->scalar('password')
            ->maxLength('password', 255)
            ->requirePresence('password', 'create')
            ->allowEmptyString('password', false);

        $validator
            ->integer('role')
            ->allowEmptyString('role', false);

        $validator
            ->scalar('host')
            ->maxLength('host', 40)
            ->allowEmptyString('host');

        $validator
            ->integer('grabs')
            ->allowEmptyString('grabs', false);

        $validator
            ->scalar('rsstoken')
            ->maxLength('rsstoken', 32)
            ->requirePresence('rsstoken', 'create')
            ->allowEmptyString('rsstoken', false);

        $validator
            ->dateTime('createddate')
            ->requirePresence('createddate', 'create')
            ->allowEmptyDateTime('createddate', false);

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
            ->allowEmptyString('invites', false);

        $validator
            ->integer('invitedby')
            ->allowEmptyString('invitedby');

        $validator
            ->integer('movieview')
            ->allowEmptyString('movieview', false);

        $validator
            ->integer('xxxview')
            ->allowEmptyString('xxxview', false);

        $validator
            ->integer('musicview')
            ->allowEmptyString('musicview', false);

        $validator
            ->integer('consoleview')
            ->allowEmptyString('consoleview', false);

        $validator
            ->integer('bookview')
            ->allowEmptyString('bookview', false);

        $validator
            ->integer('gameview')
            ->allowEmptyString('gameview', false);

        $validator
            ->scalar('saburl')
            ->maxLength('saburl', 255)
            ->allowEmptyString('saburl');

        $validator
            ->scalar('sabapikey')
            ->maxLength('sabapikey', 255)
            ->allowEmptyString('sabapikey');

        $validator
            ->boolean('sabapikeytype')
            ->allowEmptyString('sabapikeytype');

        $validator
            ->boolean('sabpriority')
            ->allowEmptyString('sabpriority');

        $validator
            ->boolean('queuetype')
            ->allowEmptyString('queuetype', false);

        $validator
            ->scalar('nzbgeturl')
            ->maxLength('nzbgeturl', 255)
            ->allowEmptyString('nzbgeturl');

        $validator
            ->scalar('nzbgetusername')
            ->maxLength('nzbgetusername', 255)
            ->allowEmptyString('nzbgetusername');

        $validator
            ->scalar('nzbgetpassword')
            ->maxLength('nzbgetpassword', 255)
            ->allowEmptyString('nzbgetpassword');

        $validator
            ->scalar('userseed')
            ->maxLength('userseed', 50)
            ->requirePresence('userseed', 'create')
            ->allowEmptyString('userseed', false);

        $validator
            ->scalar('cp_url')
            ->maxLength('cp_url', 255)
            ->allowEmptyString('cp_url');

        $validator
            ->scalar('cp_api')
            ->maxLength('cp_api', 255)
            ->allowEmptyString('cp_api');

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
