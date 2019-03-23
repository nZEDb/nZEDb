<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateVideoDataTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('video_data')) {
			Schema::create('video_data',
				function (Blueprint $table) {
					$table->integer('releases_id')
						->unsigned()
						->primary()
						->comment('FK to releases.id');
					$table->string('containerformat', 50)->nullable();
					$table->string('overallbitrate', 20)->nullable();
					$table->string('videoduration', 20)->nullable();
					$table->string('videoformat', 50)->nullable();
					$table->string('videocodec', 50)->nullable();
					$table->integer('videowidth')->nullable();
					$table->integer('videoheight')->nullable();
					$table->string('videoaspect', 10)->nullable();
					$table->float('videoframerate', 7, 4)->nullable();
					$table->string('videolibrary', 50)->nullable();
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
		Schema::dropIfExists('video_data');
	}

}
?>
