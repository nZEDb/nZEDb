<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateImdbTitlesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('imdb_titles')) {
			Schema::create('imdb_titles',
				function (Blueprint $table) {
					$table->char('id', 9)->default('tt0000000')->primary();
					$table->string('type', 20)->nullable();
					$table->string('primary_title', 180)->comment('Common name of the title.');
					$table->string('original_title', 180)
						->comment('Original name, in the original language, of the title.');
					$table->boolean('adult')
						->default(0)
						->comment('Is the title Adult: 0 - not adult, 1 - adult.');
					$table->char('started', 4)
						->comment('Release year of a title. In the case of TV Series, it is the series\' start year.');
					$table->char('ended', 4)
						->nullable()
						->comment('TV Series end year. NULL for all other types.');
					$table->smallInteger('runtime')
						->nullable()
						->comment('Main runtime of the title, in minutes.');
					$table->string('genres', 180)
						->nullable()
						->comment('Up to three genres associated with the title.');
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
		Schema::dropIfExists('imdb_titles');
	}

}
?>
