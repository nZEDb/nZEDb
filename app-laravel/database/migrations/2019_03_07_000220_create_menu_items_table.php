<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMenuItemsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('menu_items')) {
			Schema::create('menu_items',
				function (Blueprint $table) {
					$table->increments('id');
					$table->string('href', 2000)->default('');
					$table->string('title', 2000)->default('');
					$table->integer('newwindow')->unsigned()->default(0);
					$table->string('tooltip', 2000)->default('');
					$table->integer('role')->unsigned();
					$table->integer('ordinal')->unsigned();
					$table->string('menueval', 2000)->default('');
					$table->index(['role', 'ordinal'], 'ix_role_ordinal');
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
		Schema::dropIfExists('menu_items');
	}

}
?>
