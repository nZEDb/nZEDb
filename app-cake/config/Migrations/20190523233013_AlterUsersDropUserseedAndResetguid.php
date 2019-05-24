<?php
use Migrations\AbstractMigration;

class AlterUsersDropUserseedAndResetguid extends AbstractMigration
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
		$table->removeColumn('userseed');
		$table->removeColumn('resetguid');
		$table->update();
    }
}
