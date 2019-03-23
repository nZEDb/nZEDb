<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateVideosAliasesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('videos_aliases')) {
			Schema::create('videos_aliases',
				function (Blueprint $table) {
					$table->integer('videos_id')
						->unsigned()
						->comment('FK to videos.id of the parent title.');
					$table->string('title', 180)->comment('AKA of the video.');
					$table->primary(['videos_id', 'title']);
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
		Schema::dropIfExists('videos_aliases');
	}

}
?>
