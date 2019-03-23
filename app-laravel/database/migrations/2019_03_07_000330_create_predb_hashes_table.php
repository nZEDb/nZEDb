<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePredbHashesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		$tablename = 'predb_hashes';
		if (! Schema::hasTable($tablename)) {
			Schema::create($tablename,
				function (Blueprint $table) {
					$table->integer('predb_id')
						->unsigned()
						->default(0)
						->comment('id, of the predb entry, this hash belongs to');
				});

			DB::statement("ALTER TABLE `$tablename` ADD COLUMN `hash` BINARY(20) NOT NULL DEFAULT '0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0' AFTER `predb_id`");
			DB::statement("ALTER TABLE `$tablename` ADD PRIMARY KEY (hash)");
		}
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('predb_hashes');
	}

}
?>
