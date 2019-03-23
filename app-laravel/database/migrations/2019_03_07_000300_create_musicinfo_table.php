<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMusicinfoTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('musicinfo')) {
			Schema::create('musicinfo',
				function (Blueprint $table) {
					$table->increments('id');
					$table->string('title');
					$table->string('asin', 128)->nullable()->unique('ix_musicinfo_asin');
					$table->string('url', 1000)->nullable();
					$table->integer('salesrank')->unsigned()->nullable();
					$table->string('artist')->nullable();
					$table->string('publisher')->nullable();
					$table->dateTime('releasedate')->nullable();
					$table->string('review', 3000)->nullable();
					$table->string('year', 4);
					$table->integer('genre_id')->nullable();
					$table->string('tracks', 3000)->nullable();
					$table->boolean('cover')->default(0);
					$table->dateTime('createddate');
					$table->dateTime('updateddate');
					$table->index(['artist', 'title'], 'ix_musicinfo_artist_title_ft');
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
		Schema::dropIfExists('musicinfo');
	}

}
?>
