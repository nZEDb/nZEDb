<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAnidbInfoTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('anidb_info')) {
			Schema::create('anidb_info',
				function (Blueprint $table) {
					$table->integer('anidbid')
						->unsigned()
						->primary()
						->comment('ID of title from AniDB');
					$table->string('type', 32)->nullable();
					$table->date('startdate')->nullable();
					$table->date('enddate')->nullable();
					$table->timestamp('updated')->default(DB::raw('CURRENT_TIMESTAMP'));
					$table->string('related', 1024)->nullable();
					$table->string('similar', 1024)->nullable();
					$table->string('creators', 1024)->nullable();
					$table->text('description', 65535)->nullable();
					$table->string('rating', 5)->nullable();
					$table->string('picture')->nullable();
					$table->string('categories', 1024)->nullable();
					$table->string('characters', 1024)->nullable();
					$table->index(['startdate', 'enddate', 'updated'], 'ix_anidb_info_datetime');
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
		Schema::dropIfExists('anidb_info');
	}

}
?>
