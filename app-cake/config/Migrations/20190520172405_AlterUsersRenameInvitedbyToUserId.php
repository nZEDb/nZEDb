<?php

use Cake\Datasource\ConnectionManager;
use Migrations\AbstractMigration;

class AlterUsersRenameInvitedbyToUserId extends AbstractMigration
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
		$table->addColumn('user_id',
			'integer',
			[
				'after'   => 'invites',
				'default' => 0,
				'null'    => false,
				'comment' => 'FK to users.id for who invited this user'
			])
			->update();

		$dbc = ConnectionManager::get('default');
		$dbc->execute('UPDATE users SET user_id = invitedby');

		$table = $this->table('users');
		$table->removeColumn('invitedby')->update();
	}
}
