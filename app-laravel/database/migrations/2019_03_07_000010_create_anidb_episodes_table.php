<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAnidbEpisodesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('anidb_episodes')) {
			Schema::create('anidb_episodes',
				function (Blueprint $table) {
					$table->integer('anidbid')->unsigned()->comment('ID of title from AniDB');
					$table->integer('episodeid')
						->unsigned()
						->default(0)
						->comment('anidb id for this episode');
					$table->smallInteger('episode_no')
						->unsigned()
						->comment('Numeric version of episode (leave 0 for combined episodes).');
					$table->string('episode_title')->comment('Title of the episode (en, x-jat)');
					$table->date('airdate');
					$table->primary(['anidbid', 'episodeid']);
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
		Schema::dropIfExists('anidb_episodes');
	}

}
?>
