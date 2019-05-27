<?php
use Migrations\AbstractMigration;


class CreatePasswordResets extends AbstractMigration
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
        $table = $this->table('password_resets', ['id' => false, 'primary_key' => ['user_id']])
			->addColumn('user_id',
				'integer', [
					'comment' => 'FK to users.id',
					'default' => null,
					'limit'   => 11,
					'null'    => false,
					'signed' => false,
				]
			)
			->addColumn('uid',
				'string', [
					'comment' => 'Unique ID created for reset process',
					'default' => '',
					'limit'   => 32,
					'null'    => false,
				]
			)
			->addColumn('created', 'datetime', [
				'comment' => 'timestamp of when created',
				'default' => 'CURRENT_TIMESTAMP',
				'null' => false
			])
			->addPrimaryKey(['user_id'])
			->addIndex(['uid'], ['unique' => true, 'name' => 'ux_uid'])
			->addIndex(['created'], ['unique' => false, 'name' => 'ix_created']);

		$table->create();
    }
}
