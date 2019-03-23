<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAnidbTitlesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('anidb_titles')) {
			Schema::create('anidb_titles',
				function (Blueprint $table) {
					$table->integer('anidbid')->unsigned()->comment('ID of title from AniDB');
					$table->string('type', 25)->comment('type of title.');
					$table->string('lang', 25);
					$table->string('title');
					$table->primary(['anidbid', 'type', 'lang', 'title']);
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
		Schema::dropIfExists('anidb_titles');
	}

}
?>
