<?php
use Phinx\Migration\AbstractMigration;


class ResizeImdbidColumns extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    addCustomColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Any other destructive changes will result in an error when trying to
     * rollback the migration.
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
    	$this->execute('UPDATE movieinfo SET imdbid = -1 WHERE imdbid IS NULL');
		$this->execute('ALTER TABLE movieinfo CHANGE COLUMN imdbid imdbid MEDIUMINT(8) UNSIGNED ZEROFILL NOT NULL');

		$this->execute('UPDATE releases SET imdbid = -1 WHERE imdbid IS NULL');
		$this->execute('ALTER TABLE releases CHANGE COLUMN imdbid imdbid MEDIUMINT(8) UNSIGNED ZEROFILL NOT NULL');

		$this->execute('UPDATE user_movies SET imdbid = -1 WHERE imdbid IS NULL');
		$this->execute('ALTER TABLE user_movies CHANGE COLUMN imdbid imdbid MEDIUMINT(8) UNSIGNED ZEROFILL NOT NULL');
    }
}

?>
