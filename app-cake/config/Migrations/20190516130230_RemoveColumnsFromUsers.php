<?php
use Migrations\AbstractMigration;

class RemoveColumnsFromUsers extends AbstractMigration
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
        $table = $this->table('users');
        $table->removeColumn('cp_url');
        $table->removeColumn('cp_api');
        $table->removeColumn('nzbgeturl');
        $table->removeColumn('nzbgetusername');
        $table->removeColumn('nzbgetpassword');
        $table->removeColumn('saburl');
        $table->removeColumn('sabapikey');
        $table->removeColumn('sabapikeytype');
        $table->removeColumn('sabpriority');
        $table->update();
    }
}
