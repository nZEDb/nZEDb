<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAudioDataTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('audio_data')) {
			Schema::create('audio_data',
				function (Blueprint $table) {
					$table->increments('id');
					$table->integer('releases_id')->unsigned()->comment('FK to releases.id');
					$table->integer('audioid')->unsigned();
					$table->string('audioformat', 50)->nullable();
					$table->string('audiomode', 50)->nullable();
					$table->string('audiobitratemode', 50)->nullable();
					$table->string('audiobitrate', 10)->nullable();
					$table->string('audiochannels', 25)->nullable();
					$table->string('audiosamplerate', 25)->nullable();
					$table->string('audiolibrary', 50)->nullable();
					$table->string('audiolanguage', 50)->nullable();
					$table->string('audiotitle', 50)->nullable();
					$table->unique(['releases_id', 'audioid'], 'ix_releaseaudio_releaseid_audioid');
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
		Schema::dropIfExists('audio_data');
	}

}
?>
