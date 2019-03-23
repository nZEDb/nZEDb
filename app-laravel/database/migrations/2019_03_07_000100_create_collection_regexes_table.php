<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCollectionRegexesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('collection_regexes')) {
			Schema::create('collection_regexes',
				function (Blueprint $table) {
					$table->increments('id');
					$table->string('group_regex')
						->default('')
						->index('ix_collection_regexes_group_regex')
						->comment('This is a regex to match against usenet groups');
					$table->string('regex', 5000)
						->default('')
						->comment('Regex used for collection grouping');
					$table->boolean('status')
						->default(1)
						->index('ix_collection_regexes_status')
						->comment('1=ON 0=OFF');
					$table->string('description', 1000)
						->comment('Optional extra details on this regex');
					$table->integer('ordinal')
						->default(0)
						->index('ix_collection_regexes_ordinal')
						->comment('Order to run the regex in');
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
		Schema::dropIfExists('collection_regexes');
	}

}
?>
