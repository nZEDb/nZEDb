<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDnzbFailuresTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('dnzb_failures')) {
			Schema::create('dnzb_failures',
				function (Blueprint $table) {
					$table->integer('release_id')->unsigned();
					$table->integer('userid')->unsigned();
					$table->integer('failed')->unsigned()->default(0);
					$table->primary(['release_id', 'userid']);
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
		Schema::dropIfExists('dnzb_failures');
	}

}
?>
