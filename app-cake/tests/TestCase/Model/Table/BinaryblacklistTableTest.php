<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\BinaryblacklistTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\BinaryblacklistTable Test Case
 */
class BinaryblacklistTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\BinaryblacklistTable
     */
    public $Binaryblacklist;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Binaryblacklist'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Binaryblacklist') ? [] : ['className' => BinaryblacklistTable::class];
        $this->Binaryblacklist = TableRegistry::getTableLocator()->get('Binaryblacklist', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Binaryblacklist);

        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
