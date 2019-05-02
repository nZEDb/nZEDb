<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * BinaryblacklistFixture
 */
class BinaryblacklistFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public $table = 'binaryblacklist';
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'integer', 'length' => 11, 'unsigned' => true, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
        'groupname' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'regex' => ['type' => 'string', 'length' => 2000, 'null' => false, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'msgcol' => ['type' => 'integer', 'length' => 11, 'unsigned' => true, 'null' => false, 'default' => '1', 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'optype' => ['type' => 'integer', 'length' => 11, 'unsigned' => true, 'null' => false, 'default' => '1', 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'status' => ['type' => 'integer', 'length' => 11, 'unsigned' => true, 'null' => false, 'default' => '1', 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'description' => ['type' => 'string', 'length' => 1000, 'null' => true, 'default' => null, 'collate' => 'utf8mb4_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'last_activity' => ['type' => 'date', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        '_indexes' => [
            'groupname' => ['type' => 'index', 'columns' => ['groupname'], 'length' => []],
            'status' => ['type' => 'index', 'columns' => ['status'], 'length' => []],
        ],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
        ],
        '_options' => [
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_general_ci'
        ],
    ];
    // @codingStandardsIgnoreEnd
    /**
     * Init method
     *
     * @return void
     */
    public function init()
    {
        $this->records = [
            [
                'id' => 1,
                'groupname' => 'Lorem ipsum dolor sit amet',
                'regex' => 'Lorem ipsum dolor sit amet',
                'msgcol' => 1,
                'optype' => 1,
                'status' => 1,
                'description' => 'Lorem ipsum dolor sit amet',
                'last_activity' => '2019-04-28'
            ],
        ];
        parent::init();
    }
}
