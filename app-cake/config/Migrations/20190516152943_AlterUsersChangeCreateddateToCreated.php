<?php

use Cake\Datasource\ConnectionManager;
use Migrations\AbstractMigration;

class AlterUsersChangeCreateddateToCreated extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function change()
    {
        $table = $this->table('users');
		$table->addTimestamps('created', 'updated')->update();

        $dbc = ConnectionManager::get('default');
        $dbc->execute('UPDATE users SET created = createddate');

        $table = $this->table('users');
		$table->removeColumn('createddate')->update();
    }
}
