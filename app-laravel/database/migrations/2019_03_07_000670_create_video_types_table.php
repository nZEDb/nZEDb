<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateVideoTypesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('video_types')) {
			Schema::create('video_types',
				function (Blueprint $table) {
					$table->smallInteger('id')
						->unsigned()
						->primary()
						->comment('Value to use in other tables.');
					$table->string('type', 20)->index('ux_type')->comment('Type of video.');
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
		Schema::dropIfExists('video_types');
	}

}
?>
