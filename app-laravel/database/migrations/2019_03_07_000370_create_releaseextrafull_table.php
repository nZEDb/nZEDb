<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateReleaseextrafullTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('releaseextrafull')) {
			Schema::create('releaseextrafull',
				function (Blueprint $table) {
					$table->integer('releases_id')
						->unsigned()
						->primary()
						->comment('FK to releases.id');
					$table->text('mediainfo', 65535)->nullable();
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
		Schema::dropIfExists('releaseextrafull');
	}

}
?>
