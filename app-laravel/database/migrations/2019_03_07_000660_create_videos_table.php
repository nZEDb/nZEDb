<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateVideosTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('videos')) {
			Schema::create('videos',
				function (Blueprint $table) {
					$table->increments('id')
						->comment('ID to be used in other tables as reference.');
					$table->boolean('type')
						->default(0)
						->comment('0 = Unknown, 1 = TV, 2 = Film, 3 = Made for TV Film');
					$table->string('title', 180)->comment('Name of the video.');
					$table->char('country_id', 2)
						->default('')
						->comment('Two character country code (FK to countries table).');
					$table->dateTime('started')
						->comment('Date (UTC) of production\'s first airing');
					$table->integer('anidb')
						->unsigned()
						->default(0)
						->comment('ID number for anidb site');
					$table->integer('imdb')
						->unsigned()
						->default(0)
						->index('ix_videos_imdb')
						->comment('ID number for IMDB site (without the \'tt\' prefix).');
					$table->integer('tmdb')
						->unsigned()
						->default(0)
						->index('ix_videos_tmdb')
						->comment('ID number for TMDB site.');
					$table->integer('trakt')
						->unsigned()
						->default(0)
						->index('ix_videos_trakt')
						->comment('ID number for TraktTV site.');
					$table->integer('tvdb')
						->unsigned()
						->default(0)
						->index('ix_videos_tvdb')
						->comment('ID number for TVDB site');
					$table->integer('tvmaze')
						->unsigned()
						->default(0)
						->index('ix_videos_tvmaze')
						->comment('ID number for TVMaze site.');
					$table->integer('tvrage')
						->unsigned()
						->default(0)
						->index('ix_videos_tvrage')
						->comment('ID number for TVRage site.');
					$table->boolean('source')
						->default(0)
						->comment('Which site did we use for info?');
					$table->index(['type', 'source'], 'ix_videos_type_source');
					$table->unique(['title', 'type', 'started', 'country_id'], 'ix_videos_title');
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
		Schema::dropIfExists('videos');
	}

}
?>
