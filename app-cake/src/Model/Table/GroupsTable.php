<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use zed\controllers\TableObject;


/**
 * Groups Model
 *
 * @property \App\Model\Table\ReleasesTable|\Cake\ORM\Association\BelongsToMany $Releases
 *
 * @method \App\Model\Entity\Group get($primaryKey, $options = [])
 * @method \App\Model\Entity\Group newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Group[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Group|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Group saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Group patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Group[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Group findOrCreate($search, callable $callback = null, $options = [])
 */
class GroupsTable extends Table
{
	/**
	 * Returns a rules checker object that will be used for validating
	 * application integrity.
	 *
	 * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
	 *
	 * @return \Cake\ORM\RulesChecker
	 */
	public function buildRules(RulesChecker $rules): RulesChecker
	{
		$rules->add($rules->isUnique(['name']));

		return $rules;
	}

	/**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
	public function initialize(array $config): void
	{
		parent::initialize($config);

		$this->setTable('groups');
		$this->setDisplayField('name');
		$this->setPrimaryKey('id');
		$this->belongsToMany('Releases', [
			'foreignKey' => 'group_id',
			'targetForeignKey' => 'release_id',
			'joinTable' => 'releases_groups'
		]);
	}

	public function getActive(): array
	{
		$query = $this->find()
			->select([
				'id',
				'name',
				'backfill_target',
				'first_record',
				'first_record_postdate',
				'last_record',
				'last_record_postdate',
				'last_updated',
				'minfilestoformrelease',
				'minsizetoformrelease',
				'active',
				'backfill',
				'description',
			])
			->where(['active' => true])
			->order(['name']);
		$set = $query->all();

		return $set->toArray();
	}

	public function getIDByName(string $name)
	{
		$query = $this->find()
			->select(['id'])
			->where(['name' => $name])
			->limit(1);

		$entity = $query->all()->first();
		return $entity === null ? '' : $entity->id;
	}

	/**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
	public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', 'create');

        $validator
            ->scalar('name')
			->regex('name','#^([\w-]+\.)+[\w-]+$#i', 'Group name failed regex requirement.', 'create')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->allowEmptyString('name', false,'Group name must be provided')
            ->add('name', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->integer('backfill_target')
            ->requirePresence('backfill_target', 'create')
            ->allowEmptyString('backfill_target', false);

        $validator
            ->requirePresence('first_record', 'create')
            ->allowEmptyString('first_record', false);

        $validator
            ->dateTime('first_record_postdate')
            ->allowEmptyDateTime('first_record_postdate');

        $validator
            ->requirePresence('last_record', 'create')
            ->allowEmptyString('last_record', false);

        $validator
            ->dateTime('last_record_postdate')
            ->allowEmptyDateTime('last_record_postdate');

        $validator
            ->dateTime('last_updated')
            ->allowEmptyDateTime('last_updated');

        $validator
            ->integer('minfilestoformrelease')
            ->allowEmptyFile('minfilestoformrelease');

        $validator
            ->allowEmptyString('minsizetoformrelease');

        $validator
            ->boolean('active')
            ->requirePresence('active', 'create')
            ->allowEmptyString('active', false);

        $validator
            ->boolean('backfill')
            ->requirePresence('backfill', 'create')
            ->allowEmptyString('backfill', false);

        $validator
            ->scalar('description')
            ->maxLength('description', 255)
            ->allowEmptyString('description');

        return $validator;
    }
}
