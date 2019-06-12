<?php
use Cake\Datasource\ConnectionManager;
use Migrations\AbstractMigration;


class CreateUserInvitations extends AbstractMigration
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
		$table = $this->table('user_invitations', ['id' => false, 'primary_key' => ['uuid']])
			->addColumn('uuid', 'string', [
				'comment' => 'Universally Unique ID for the invitation',
				'default' => null,
				'limit'   => 32,
				'null'	=> false,
			])
			->addTimestamps('user_id', 'integer', [
				'comment'	=> 'FK to users.id Indicates the person issuing the invite.',
				'default'	=> 0,
				'limit'		=> 11,
				'null'		=> false,
			])
			->addColumn('created', 'datetime', [
				'comment' => 'timestamp of when created',
				'default' => 'CURRENT_TIMESTAMP',
				'null'	=> false
			]);
		$table->create();

		$connection = ConnectionManager::get('default');

		$connection->execute('INSERT INTO user_invitations (uuid, user_id, created) SELECT guid, user_id, createddate FROM invitations ORDER BY createddate');

		$table = $this->table('invitations');
		$table->drop()->update();
	}
}
