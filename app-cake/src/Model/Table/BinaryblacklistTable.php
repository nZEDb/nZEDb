<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Binaryblacklist Model
 *
 * @method \App\Model\Entity\Binaryblacklist get($primaryKey, $options = [])
 * @method \App\Model\Entity\Binaryblacklist newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Binaryblacklist[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Binaryblacklist|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Binaryblacklist saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Binaryblacklist patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Binaryblacklist[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Binaryblacklist findOrCreate($search, callable $callback = null, $options = [])
 */
class BinaryblacklistTable extends Table
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

        $this->setTable('binaryblacklist');
        $this->setDisplayField('groupname');
        $this->setPrimaryKey('id');
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
            ->nonNegativeInteger('id')
            ->allowEmptyString('id', 'create');

        $validator
            ->scalar('groupname')
            ->maxLength('groupname', 255)
            ->allowEmptyString('groupname', false, 'Group must be a valid usenet group/regex');

        $validator
            ->scalar('regex')
            ->maxLength('regex', 2000)
            ->requirePresence('regex', 'create')
            ->allowEmptyString('regex', false, 'Regex cannot be empty');

        $validator
            ->nonNegativeInteger('msgcol')
            ->allowEmptyString('msgcol', false);

        $validator
            ->nonNegativeInteger('optype')
            ->allowEmptyString('optype', false);

        $validator
            ->nonNegativeInteger('status')
            ->allowEmptyString('status', false);

        $validator
            ->scalar('description')
            ->maxLength('description', 1000)
            ->allowEmptyString('description');

        $validator
            ->date('last_activity')
            ->allowEmptyDate('last_activity');

        return $validator;
    }
}
