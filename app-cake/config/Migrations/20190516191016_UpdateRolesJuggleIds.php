<?php

use Cake\Datasource\ConnectionManager;
use Migrations\AbstractMigration;

class UpdateRolesJuggleIds extends AbstractMigration
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
		$roles = \Cake\ORM\TableRegistry::getTableLocator()->get('Roles');
		$users = \Cake\ORM\TableRegistry::getTableLocator()->get('Users');

		$roles->query()->update()
			->set(['id' => 10])
			->where(['id' => 0]) // Guest
			->execute();
		$users->query()->update()
			->set(['role_id' => 10])
			->where(['role_id' => 0])
			->execute();

		$roles->query()->update()
			->set(['id' => 0])
			->where(['id' => 3]) // Disabled
			->execute();
		$users->query()->update()
			->set(['role_id' => 0])
			->where(['role_id' => 3])
			->execute();

		$roles->query()->update()
			->set(['id' => 20])
			->where(['id' => 1]) // User
			->execute();
		$users->query()->update()
			->set(['role_id' => 20])
			->where(['role_id' => 1])
			->execute();

		$roles->query()->update()
			->set(['id' => 30])
			->where(['id' => 4]) // Moderator
			->execute();
		$users->query()->update()
			->set(['role_id' => 30])
			->where(['role_id' => 4])
			->execute();

		$roles->query()->update()
			->set(['id' => 40])
			->where(['id' => 5]) // Friend
			->execute();
		$users->query()->update()
			->set(['role_id' => 40])
			->where(['role_id' => 5])
			->execute();

		$roles->query()->update()
			->set(['id' => 100])
			->where(['id' => 2]) //Admin
			->execute();
		$users->query()->update()
			->set(['role_id' => 100])
			->where(['role_id' => 2])
			->execute();
	}
}
