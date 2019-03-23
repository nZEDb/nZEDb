<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUserMoviesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('user_movies')) {
			Schema::create('user_movies',
				function (Blueprint $table) {
					$table->increments('id');
					$table->integer('user_id');
					$table->integer('imdbid')->unsigned()->nullable();
					$table->string('categories', 64)
						->nullable()
						->comment('List of categories for user movies');
					$table->dateTime('createddate');
					$table->index(['user_id', 'imdbid'], 'ix_usermovies_userid');
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
		Schema::dropIfExists('user_movies');
	}

}
?>
