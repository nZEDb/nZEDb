<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateReleaseNfosTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('release_nfos')) {
			Schema::create('release_nfos',
				function (Blueprint $table) {
					$table->integer('releases_id')
						->unsigned()
						->primary()
						->comment('FK to releases.id');
					$table->binary('nfo', 65535)->nullable();
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
		Schema::dropIfExists('release_nfos');
	}

}
?>
