<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateGroupsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('groups')) {
			Schema::create('groups',
				function (Blueprint $table) {
					$table->integer('id', true);
					$table->string('name')->default('')->unique('ix_groups_name');
					$table->integer('backfill_target')->default(1);
					$table->bigInteger('first_record')->unsigned()->default(0);
					$table->dateTime('first_record_postdate')->nullable();
					$table->bigInteger('last_record')->unsigned()->default(0);
					$table->dateTime('last_record_postdate')->nullable();
					$table->dateTime('last_updated')->nullable();
					$table->integer('minfilestoformrelease')->nullable();
					$table->bigInteger('minsizetoformrelease')->nullable();
					$table->boolean('active')->default(0)->index('active');
					$table->boolean('backfill')->default(0);
					$table->string('description')->nullable()->default('');
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
		Schema::dropIfExists('groups');
	}

}
?>
