<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUpcomingReleasesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('upcoming_releases')) {
			Schema::create('upcoming_releases',
				function (Blueprint $table) {
					$table->integer('id', true);
					$table->string('source', 20);
					$table->integer('typeid');
					$table->text('info', 65535)->nullable();
					$table->timestamp('updateddate')->default(DB::raw('CURRENT_TIMESTAMP'));
					$table->unique(['source', 'typeid'], 'ix_upcoming_source');
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
	Schema::dropIfExists('upcoming_releases');
	}

}
?>
