<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateReleaseFilesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('release_files')) {
			Schema::create('release_files',
				function (Blueprint $table) {
					$table->integer('releases_id')->unsigned()->comment('FK to releases.id');
					$table->string('name')->default('');
					$table->bigInteger('size')->unsigned()->default(0);
					$table->boolean('ishashed')->default(0)->index('ix_releasefiles_ishashed');
					$table->dateTime('createddate')->nullable();
					$table->boolean('passworded')->default(0);
					$table->primary(['releases_id', 'name']);
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
		Schema::dropIfExists('release_files');
	}

}
?>
