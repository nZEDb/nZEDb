<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;


/**
 * Couchpotato Model
 *
 * @method \App\Model\Entity\Couchpotato get($primaryKey, $options = [])
 * @method \App\Model\Entity\Couchpotato newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Couchpotato[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Couchpotato|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Couchpotato saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Couchpotato patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Couchpotato[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Couchpotato findOrCreate($search, callable $callback = null, $options = [])
 */
class CouchpotatoTable extends Table
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
		'cp_url'				   => true,
		'cp_api'				   => true,
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

		$this->setTable('couchpotato');
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
			->scalar('api')
			->maxLength('api', 255)
			->requirePresence('api', 'create')
			->allowEmptyString('api', false);

		return $validator;
	}
}
