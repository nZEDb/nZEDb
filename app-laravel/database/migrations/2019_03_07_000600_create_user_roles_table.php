<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUserRolesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('user_roles')) {
			Schema::create('user_roles',
				function (Blueprint $table) {
					$table->integer('id', true);
					$table->string('name', 32);
					$table->integer('apirequests')->unsigned();
					$table->integer('downloadrequests')->unsigned();
					$table->integer('defaultinvites')->unsigned();
					$table->boolean('isdefault')->default(0);
					$table->boolean('canpreview')->default(0);
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
		Schema::dropIfExists('user_roles');
	}

}
?>
