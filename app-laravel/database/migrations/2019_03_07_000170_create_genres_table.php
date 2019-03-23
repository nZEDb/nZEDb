<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateGenresTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('genres')) {
			Schema::create('genres',
				function (Blueprint $table) {
					$table->integer('id', true);
					$table->string('title');
					$table->integer('type')->nullable();
					$table->boolean('disabled')->default(0);
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
		Schema::dropIfExists('genres');
	}

}
?>
