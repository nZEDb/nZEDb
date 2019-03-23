<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUserDownloadsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('user_downloads')) {
			Schema::create('user_downloads',
				function (Blueprint $table) {
					$table->increments('id');
					$table->integer('user_id')->index('userid');
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
		Schema::dropIfExists('user_downloads');
	}

}
?>
