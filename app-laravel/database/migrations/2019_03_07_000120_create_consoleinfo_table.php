<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateConsoleinfoTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('consoleinfo')) {
			Schema::create('consoleinfo',
				function (Blueprint $table) {
					$table->increments('id');
					$table->string('title');
					$table->string('asin', 128)->nullable()->unique('ix_consoleinfo_asin');
					$table->string('url', 1000)->nullable();
					$table->integer('salesrank')->unsigned()->nullable();
					$table->string('platform')->nullable();
					$table->string('publisher')->nullable();
					$table->integer('genre_id')->nullable();
					$table->string('esrb')->nullable();
					$table->dateTime('releasedate')->nullable();
					$table->string('review', 3000)->nullable();
					$table->boolean('cover')->default(0);
					$table->dateTime('createddate');
					$table->dateTime('updateddate');
					$table->index(['title', 'platform'], 'ix_consoleinfo_title_platform_ft');
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
		Schema::dropIfExists('consoleinfo');
	}

}
?>
