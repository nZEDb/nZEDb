<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateLoggingTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('logging')) {
			Schema::create('logging',
				function (Blueprint $table) {
					$table->increments('id');
					$table->dateTime('time')->nullable();
					$table->string('username', 50)->nullable();
					$table->string('host', 40)->nullable();
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
		Schema::dropIfExists('logging');
	}

}
?>
