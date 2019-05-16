<?php

use Cake\Datasource\ConnectionManager;
use Migrations\AbstractMigration;

class AlterUsersRenameRoleToRoleId extends AbstractMigration
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
		$table->addColumn('role_id', 'integer', ['after' => 'role', 'default' => 0, 'null' =>
			false, 'comment' => 'FK to roles.id'])
			->update();

		$dbc = ConnectionManager::get('default');
		$dbc->execute('UPDATE users SET role_id = role');

		$table = $this->table('users');
		$table->removeColumn('role')->update();
	}
}
