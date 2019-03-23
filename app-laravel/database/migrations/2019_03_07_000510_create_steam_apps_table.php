<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSteamAppsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('steam_apps')) {
			Schema::create('steam_apps',
				function (Blueprint $table) {
					$table->string('name')
						->default('')
						->index('ix_name_ft')
						->comment('Steam application name');
					$table->integer('appid')
						->unsigned()
						->default(0)
						->comment('Steam application id');
					$table->primary(['appid', 'name']);
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
		Schema::dropIfExists('steam_apps');
	}

}
?>
