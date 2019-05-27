<?php
namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

/**
 * Settings Model
 *
 * @method \App\Model\Entity\Setting get($primaryKey, $options = [])
 * @method \App\Model\Entity\Setting newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Setting[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Setting|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Setting saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Setting patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Setting[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Setting findOrCreate($search, callable $callback = null, $options = [])
 */
class SettingsTable extends Table
{
	/**
	 * Returns the value of the specified setting, or null.
	 *
	 * @param string|array $setting Section, subsection, and name values as dotted string or an array.
	 *
	 * @return string|null	The value of the specified setting, or null.
	 */
	public static function getValue($setting): ?string
	{
		$table = TableRegistry::getTableLocator()->get('Settings');
		$query = $table->find()
			->select(['value'])
			->where(self::dottedToArray($setting))
			->first();

		return $query->value;
	}

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('settings');
        $this->setDisplayField('value');
        $this->setPrimaryKey(['section', 'subsection', 'name']);
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
            ->scalar('section')
            ->maxLength('section', 25)
            ->allowEmptyString('section', 'create');

        $validator
            ->scalar('subsection')
            ->maxLength('subsection', 25)
            ->allowEmptyString('subsection', 'create');

        $validator
            ->scalar('name')
            ->maxLength('name', 25)
            ->allowEmptyString('name', 'create');

        $validator
            ->scalar('value')
            ->maxLength('value', 1000)
            ->requirePresence('value', 'create')
            ->allowEmptyString('value', false);

        $validator
            ->scalar('hint')
            ->requirePresence('hint', 'create')
            ->allowEmptyString('hint', false);

        $validator
            ->scalar('setting')
            ->maxLength('setting', 64)
            ->requirePresence('setting', 'create')
            ->allowEmptyString('setting', false)
            ->add('setting', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

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
        $rules->add($rules->isUnique(['setting']));

        return $rules;
    }

	/**
	 * @param $setting
	 *
	 * @return array|bool
	 */
	protected static function dottedToArray($setting)
	{
		$result = [];
		switch (true) {
			case is_string($setting):
				$array = explode('.', $setting);
				$count = count($array);
				if ($count > 3) {
					$result = false;
				} else {

					while (3 - $count > 0) {
						array_unshift($array, '');
						$count++;
					}

					[$result['section'], $result['subsection'], $result['name']] = $array;
				}
				break;
			case \is_array($setting):
				$result = $setting;
				break;
			default:
				$result = false;
				break;
		}

		return $result;
	}
}
