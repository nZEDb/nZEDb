<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMultigroupPartsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('multigroup_parts')) {
			Schema::create('multigroup_parts',
				function (Blueprint $table) {
					$table->bigInteger('binaries_id')->unsigned()->default(0);
					$table->string('messageid')->default('');
					$table->bigInteger('number')->unsigned()->default(0);
					$table->integer('partnumber')->unsigned()->default(0);
					$table->integer('size')->unsigned()->default(0);
					$table->primary(['binaries_id', 'number']);
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
		Schema::dropIfExists('multigroup_parts');
	}

}
?>
