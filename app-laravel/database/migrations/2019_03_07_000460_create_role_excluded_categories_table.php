<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateRoleExcludedCategoriesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('role_excluded_categories')) {
			Schema::create('role_excluded_categories',
				function (Blueprint $table) {
					$table->increments('id');
					$table->integer('role');
					$table->integer('categories_id')->nullable();
					$table->dateTime('createddate');
					$table->unique(['role', 'categories_id'], 'ix_roleexcat_rolecat');
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
		Schema::dropIfExists('role_excluded_categories');
	}

}
?>
