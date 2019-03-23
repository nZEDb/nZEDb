<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateReleaseSubtitlesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('release_subtitles')) {
			Schema::create('release_subtitles',
				function (Blueprint $table) {
					$table->increments('id');
					$table->integer('releases_id')->unsigned()->comment('FK to releases.id');
					$table->integer('subsid')->unsigned();
					$table->string('subslanguage', 50);
					$table->unique(['releases_id', 'subsid'], 'ix_releasesubs_releases_id_subsid');
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
		Schema::dropIfExists('release_subtitles');
	}

}
?>
