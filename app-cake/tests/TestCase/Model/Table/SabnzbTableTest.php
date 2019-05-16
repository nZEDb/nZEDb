<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\SabnzbTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\SabnzbTable Test Case
 */
class SabnzbTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\SabnzbTable
     */
    public $Sabnzb;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Sabnzb'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Sabnzb') ? [] : ['className' => SabnzbTable::class];
        $this->Sabnzb = TableRegistry::getTableLocator()->get('Sabnzb', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Sabnzb);

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
