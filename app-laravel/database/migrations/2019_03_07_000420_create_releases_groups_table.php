<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateReleasesGroupsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('releases_groups')) {
			Schema::create('releases_groups',
				function (Blueprint $table) {
					$table->integer('releases_id')
						->unsigned()
						->default(0)
						->comment('FK to releases.id');
					$table->integer('groups_id')
						->unsigned()
						->default(0)
						->comment('FK to groups.id');
					$table->primary(['releases_id', 'groups_id']);
				});
		}
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('releases_groups');
	}

}
?>
