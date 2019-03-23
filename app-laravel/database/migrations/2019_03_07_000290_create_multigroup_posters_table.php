<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMultigroupPostersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('multigroup_posters')) {
			Schema::create('multigroup_posters',
				function (Blueprint $table) {
					$table->increments('id')->comment('Primary key');
					$table->string('poster')
						->default('')
						->unique('poster')
						->comment('Name of the poster to track');
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
		Schema::dropIfExists('multigroup_posters');
	}

}
?>
