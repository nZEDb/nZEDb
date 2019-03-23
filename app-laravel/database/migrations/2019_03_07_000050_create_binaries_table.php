<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


class CreateBinariesTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		$tablename = 'binaries';
		if (!Schema::hasTable($tablename)) {
			Schema::create($tablename,
				function (Blueprint $table) {
					$table->bigInteger('id', true)
						->unsigned();
					$table->string('name', 1000)
						->default('');
					$table->integer('collections_id')
						->unsigned()
						->default(0)
						->index('ix_binaries_collections');
					$table->integer('filenumber')
						->unsigned()
						->default(0);
					$table->integer('totalparts')
						->unsigned()
						->default(0);
					$table->integer('currentparts')
						->unsigned()
						->default(0);
					$table->boolean('partcheck')
						->default(0)
						->index('ix_binaries_partcheck');
					$table->bigInteger('partsize')
						->unsigned()
						->default(0);
				});

			DB::statement("ALTER TABLE `$tablename` ADD COLUMN `binaryhash` BINARY(16) NOT NULL DEFAULT '0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0' AFTER `currentparts`");
			DB::statement("ALTER TABLE `$tablename` ADD UNIQUE INDEX ux_binary_binaryhash (binaryhash)");
		}
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('binaries');
	}

}
?>
