<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSphinxTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		/* TODO detect manticor/sphinx .env values and only add table if they exist.
		if (!Schema::hasTable('releases_se')) {
			Schema::create('releases_se',
				function (Blueprint $table) {
					$table->bigInteger('id')
						->unsigned();
					$table->integer('weight');
					$table->string('query', 1024)
						->index('query');
					$table->string('name')
						->default('');
					$table->string('searchname')
						->default('');
					$table->string('fromname')
						->nullable();
					$table->string('filename', 1000)
						->nullable();
				});
		}
		*/
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('releases_se');
	}
}

?>
