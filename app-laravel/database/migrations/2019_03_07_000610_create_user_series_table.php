<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUserSeriesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('user_series')) {
			Schema::create('user_series',
				function (Blueprint $table) {
					$table->increments('id');
					$table->integer('user_id');
					$table->integer('videos_id')->comment('FK to videos.id');
					$table->string('categories', 64)
						->nullable()
						->comment('List of categories for user tv shows');
					$table->dateTime('createddate');
					$table->index(['user_id', 'videos_id'], 'ix_userseries_videos_id');
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
		Schema::dropIfExists('user_series');
	}

}
?>
