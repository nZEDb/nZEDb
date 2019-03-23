<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMultigroupCollectionsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('multigroup_collections')) {
			Schema::create('multigroup_collections',
				function (Blueprint $table) {
					$table->increments('id')->comment('Primary key');
					$table->string('subject')->default('')->comment('Collection subject');
					$table->string('fromname')
						->default('')
						->index('fromname')
						->comment('Collection poster');
					$table->dateTime('date')
						->nullable()
						->index('date')
						->comment('Collection post date');
					$table->string('xref', 510)
						->default('')
						->comment('Groups collection is posted in');
					$table->integer('totalfiles')
						->unsigned()
						->default(0)
						->comment('Total number of files');
					$table->integer('groups_id')
						->unsigned()
						->default(0)
						->index('group_id')
						->comment('FK to groups.id');
					$table->string('collectionhash')
						->default('0')
						->unique('ix_collection_collectionhash')
						->comment('MD5 hash of the collection');
					$table->integer('collection_regexes_id')
						->default(0)
						->comment('FK to collection_regexes.id');
					$table->dateTime('dateadded')
						->nullable()
						->index('ix_collection_dateadded')
						->comment('Date collection is added');
					$table->timestamp('added')->default(DB::raw('CURRENT_TIMESTAMP'));
					$table->boolean('filecheck')
						->default(0)
						->index('ix_collection_filecheck')
						->comment('Status of the collection');
					$table->bigInteger('filesize')
						->unsigned()
						->default(0)
						->comment('Total calculated size of the collection');
					$table->integer('releases_id')
						->nullable()
						->index('ix_collection_releaseid')
						->comment('FK to releases.id');
					$table->char('noise', 32)->default('');
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
		Schema::dropIfExists('multigroup_collections');
	}

}
?>
