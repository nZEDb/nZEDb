<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateShortGroupsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('short_groups')) {
			Schema::create('short_groups',
				function (Blueprint $table) {
					$table->integer('id', true);
					$table->string('name')->default('')->index('ix_shortgroups_name');
					$table->bigInteger('first_record')->unsigned()->default(0);
					$table->bigInteger('last_record')->unsigned()->default(0);
					$table->dateTime('updated')->nullable();
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
		Schema::dropIfExists('short_groups');
	}

}
?>
