<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSharingTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('sharing')) {
			Schema::create('sharing',
				function (Blueprint $table) {
					$table->string('site_guid', 40)->default('')->primary();
					$table->string('site_name')->default('');
					$table->string('username')->default('');
					$table->boolean('enabled')->default(0);
					$table->boolean('posting')->default(0);
					$table->boolean('fetching')->default(1);
					$table->boolean('auto_enable')->default(1);
					$table->boolean('start_position')->default(0);
					$table->boolean('hide_users')->default(1);
					$table->bigInteger('last_article')->unsigned()->default(0);
					$table->integer('max_push')->unsigned()->default(40);
					$table->integer('max_download')->unsigned()->default(150);
					$table->integer('max_pull')->unsigned()->default(20000);
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
		Schema::dropIfExists('sharing');
	}

}
?>
