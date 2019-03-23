<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use zed\database\MigrationWithLoadData;


class CreateBinaryblacklistTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		$tablename = 'binaryblacklist';
		if (! Schema::hasTable($tablename)) {
			Schema::create($tablename,
				function (Blueprint $table) {
					$table->increments('id');
					$table->string('groupname')
						->nullable()
						->index('ix_binaryblacklist_groupname');
					$table->string('regex', 2000);
					$table->integer('msgcol')
						->unsigned()
						->default(1);
					$table->integer('optype')
						->unsigned()
						->default(1);
					$table->integer('status')
						->unsigned()
						->default(1)
						->index('ix_binaryblacklist_status');
					$table->string('description', 1000)
						->nullable();
					$table->date('last_activity')
						->nullable();
				});

			// set the AUTO INCREMENT value, so user entries leave plenty of space for new defaults
			DB::statement("ALTER TABLE `$tablename` AUTO_INCREMENT=1000001");
		}
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('binaryblacklist');
	}

}
?>
