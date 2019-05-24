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
    public function change()
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
					'limit'   => 36,
					'null'    => false,
				]
			)
			->addPrimaryKey(['user_id'])
			->addIndex(['uid'], ['unique' => true, 'name' => 'ux_uid'])
		;
        $table->create();
    }
}
