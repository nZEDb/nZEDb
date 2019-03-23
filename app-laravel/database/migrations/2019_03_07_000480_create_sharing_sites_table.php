<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSharingSitesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('sharing_sites')) {
			Schema::create('sharing_sites',
				function (Blueprint $table) {
					$table->increments('id');
					$table->string('site_name')->default('');
					$table->string('site_guid', 40)->default('');
					$table->dateTime('last_time')->nullable();
					$table->dateTime('first_time')->nullable();
					$table->boolean('enabled')->default(0);
					$table->integer('comments')->unsigned()->default(0);
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
		Schema::dropIfExists('sharing_sites');
	}

}
?>
