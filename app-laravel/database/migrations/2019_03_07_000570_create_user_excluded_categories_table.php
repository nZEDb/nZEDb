<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUserExcludedCategoriesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('user_excluded_categories')) {
			Schema::create('user_excluded_categories',
				function (Blueprint $table) {
					$table->increments('id');
					$table->integer('user_id');
					$table->integer('categories_id');
					$table->dateTime('createddate');
					$table->unique(['user_id', 'categories_id'], 'ix_userexcat_usercat');
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
		Schema::dropIfExists('user_excluded_categories');
	}

}
?>
