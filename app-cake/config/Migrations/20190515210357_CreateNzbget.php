<?php
use Migrations\AbstractMigration;

class CreateNzbget extends AbstractMigration
{
	/**
	 * Change Method.
	 *
	 * More information on this method is available here:
	 * http://docs.phinx.org/en/latest/migrations.html#the-change-method
	 * @return void
	 */
	public function change(): void
	{
		$table = $this->table('nzbget', ['id' => false, 'primary_key' => ['user_id']])
			->addColumn('user_id', 'integer', [
				'comment' => 'FK to users.id',
				'default' => null,
				'limit' => 11,
				'null' => false,
			])
			->addColumn('url', 'string', [
				'comment' => 'url to NZBGet server',
				'default' => null,
				'limit' => 255,
				'null' => false,
			])
			->addColumn('username', 'string', [
				'default' => null,
				'limit' => 255,
				'null' => false,
			])
			->addColumn('password', 'string', [
				'default' => null,
				'limit' => 255,
				'null' => false,
			])
			->addPrimaryKey(['user_id']);
		$table->create();
	}
}
