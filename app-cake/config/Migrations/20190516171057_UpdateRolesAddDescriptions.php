<?php
use Migrations\AbstractMigration;

class UpdateRolesAddDescriptions extends AbstractMigration
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
    	$table = \Cake\ORM\TableRegistry::getTableLocator()->get('Roles');

		$query = $table->query();
		$query->update()
			->set(['description' => 'Any user that is not logged in'])
			->where(['id' => 0])
			->execute();

		$query = $table->query();
		$query->update()
			->set(['description' => 'Normal user. Limited amount of requests and invites.'])
			->where(['id' => 1])
			->execute();

		$query = $table->query();
		$query->update()
			->set(['description' => 'Administrators have complete control over the site.'])
			->where(['id' => 2])
			->execute();

		$query = $table->query();
		$query->update()
			->set(['description' => 'Disabled users cannot perform any actions or view any pages.'])
			->where(['id' => 3])
			->execute();

		$query = $table->query();
		$query->update()
			->set(['description' => 'Moderators get much higher levels for requests and invites.'])
			->where(['id' => 4])
			->execute();

		$query = $table->query();
		$query->update()
			->set(['description' => 'Friends get additional levels requests and invites.'])
			->where(['id' => 5])
			->execute();
	}
}
