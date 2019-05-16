<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\CouchpotatoTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\CouchpotatoTable Test Case
 */
class CouchpotatoTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\CouchpotatoTable
     */
    public $Couchpotato;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Couchpotato'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('Couchpotato') ? [] : ['className' => CouchpotatoTable::class];
        $this->Couchpotato = TableRegistry::getTableLocator()->get('Couchpotato', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Couchpotato);

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
