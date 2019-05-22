<?php

use Cake\Datasource\ConnectionManager;
use Migrations\AbstractMigration;

class InsertDummyUser extends AbstractMigration
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
		$connection = ConnectionManager::get('default');

		$result = $connection->execute("INSERT INTO users 
			(id, username, firstname, lastname, email, password, rsstoken, userseed)
			VALUES (0, 'No One', 'No', 'One', 'never@home.now', 'no password', '', '')");

		$id = $result->lastInsertId();
		if ($id !== 0) {
			$connection->execute('UPDATE users SET id = 0 WHERE id = ' . $id);
		}
	}
}
