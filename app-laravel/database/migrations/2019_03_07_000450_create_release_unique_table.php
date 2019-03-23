<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateReleaseUniqueTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		$tablename = 'release_unique';
		if (! Schema::hasTable($tablename)) {
			Schema::create($tablename,
				function (Blueprint $table) {
					$table->integer('releases_id')->unsigned()->comment('FK to releases.id.');
				});

			DB::statement("ALTER TABLE `$tablename` ADD COLUMN `uniqueid` BINARY(16) NOT NULL DEFAULT '0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0' COMMENT 'Unique_ID from mediainfo.' AFTER `releases_id`");
			DB::statement("ALTER TABLE `$tablename` ADD PRIMARY KEY (releases_id, uniqueid)");
		}
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('release_unique');
	}

}
?>
