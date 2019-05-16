<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\NzbgetTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\NzbgetTable Test Case
 */
class NzbgetTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\NzbgetTable
     */
    public $Nzbget;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Nzbget'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Nzbget') ? [] : ['className' => NzbgetTable::class];
        $this->Nzbget = TableRegistry::getTableLocator()->get('Nzbget', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Nzbget);

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

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
