<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMovieinfoTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('movieinfo')) {
			Schema::create('movieinfo',
				function (Blueprint $table) {
					$table->increments('id');
					$table->integer('imdbid')->unsigned()->unique('ix_movieinfo_imdbid');
					$table->integer('tmdbid')->unsigned()->default(0);
					$table->string('title')->default('')->index('ix_movieinfo_title');
					$table->string('tagline', 1024)->default('');
					$table->string('rating', 4)->default('');
					$table->string('plot', 1024)->default('');
					$table->string('year', 4)->default('');
					$table->string('genre', 64)->default('');
					$table->string('type', 32)->default('');
					$table->string('director', 64)->default('');
					$table->string('actors', 2000)->default('');
					$table->string('language', 64)->default('');
					$table->boolean('cover')->default(0);
					$table->boolean('backdrop')->default(0);
					$table->dateTime('createddate');
					$table->dateTime('updateddate');
					$table->string('trailer')->default('');
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
		Schema::dropIfExists('movieinfo');
	}

}
?>
