<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUserRequestsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('user_requests')) {
			Schema::create('user_requests',
				function (Blueprint $table) {
					$table->increments('id');
					$table->integer('user_id')->index('userid');
					$table->string('request');
					$table->dateTime('timestamp')->index('timestamp');
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
		Schema::dropIfExists('user_requests');
	}

}
?>
