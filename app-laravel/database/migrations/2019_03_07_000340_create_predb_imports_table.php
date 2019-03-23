<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePredbImportsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{

		if (! Schema::hasTable('predb_imports')) {
			Schema::create('predb_imports',
				function (Blueprint $table) {
					$table->string('title')
						->default('');
					$table->string('nfo')
						->nullable();
					$table->string('size', 50)
						->nullable();
					$table->string('category')
						->nullable();
					$table->timestamp('created')
						->default(DB::raw('CURRENT_TIMESTAMP'));
					$table->dateTime('updated')
						->nullable();
					$table->string('source', 50)
						->default('');
					$table->integer('requestid')
						->unsigned()
						->default(0);
					$table->integer('groups_id')
						->unsigned()
						->default(0)
						->comment('FK to groups');
					$table->boolean('nuked')
						->default(0)
						->comment('Is this pre nuked? 0 no 2 yes 1 un nuked 3 mod nuked');
					$table->string('nukereason')
						->nullable()
						->comment('If this pre is nuked, what is the reason?');
					$table->string('files', 50)
						->nullable()
						->comment('How many files does this pre have ?');
					$table->string('filename')
						->default('');
					$table->boolean('searched')
						->default(0);
					$table->string('groupname')
						->nullable();
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
		Schema::dropIfExists('predb_imports');
	}

}
?>
