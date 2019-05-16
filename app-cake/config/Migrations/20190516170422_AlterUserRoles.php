<?php
use Migrations\AbstractMigration;

class AlterUserRoles extends AbstractMigration
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
        $table = $this->table('user_roles');
        $table->addColumn('description', 'text', ['null' => false]);
        $table->rename('roles');
        $table->update();
    }
}
