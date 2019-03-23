<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUsersReleasesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('users_releases')) {
			Schema::create('users_releases',
				function (Blueprint $table) {
					$table->increments('id');
					$table->integer('user_id');
					$table->integer('releases_id')->comment('FK to releases.id');
					$table->dateTime('createddate');
					$table->unique(['user_id', 'releases_id'], 'ix_usercart_userrelease');
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
		Schema::dropIfExists('users_releases');
	}

}
?>
