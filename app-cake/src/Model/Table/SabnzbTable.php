<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;


/**
 * Sabnzb Model
 *
 * @method \App\Model\Entity\Sabnzb get($primaryKey, $options = [])
 * @method \App\Model\Entity\Sabnzb newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Sabnzb[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Sabnzb|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Sabnzb saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Sabnzb patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Sabnzb[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Sabnzb findOrCreate($search, callable $callback = null, $options = [])
 */
class SabnzbTable extends Table
{
	/**
	 * Fields that can be mass assigned using newEntity() or patchEntity().
	 * Note that when '*' is set to true, this allows all unspecified fields to
	 * be mass assigned. For security purposes, it is advised to set '*' to false
	 * (or remove it), and explicitly make individual fields accessible as needed.
	 *
	 * @var array
	 */
	protected $_accessible = [
		'saburl'                   => true,
		'sabapikey'                => true,
		'sabapikeytype'            => true,
		'sabpriority'              => true,
	];

	/**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('sabnzb');
        $this->setDisplayField('user_id');
        $this->setPrimaryKey('user_id');

		$this->belongsTo('Users');
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
            ->integer('user_id')
            ->allowEmptyString('user_id', 'create');

        $validator
            ->scalar('url')
            ->maxLength('url', 255)
            ->requirePresence('url', 'create')
            ->allowEmptyString('url', false);

        $validator
            ->scalar('api_key')
            ->maxLength('api_key', 255)
            ->requirePresence('api_key', 'create')
            ->allowEmptyString('api_key', false);

        $validator
            ->boolean('api_key_type')
            ->requirePresence('api_key_type', 'create')
            ->allowEmptyString('api_key_type', false);

        $validator
            ->boolean('priority')
            ->requirePresence('priority', 'create')
            ->allowEmptyString('priority', false);

        return $validator;
    }
}
