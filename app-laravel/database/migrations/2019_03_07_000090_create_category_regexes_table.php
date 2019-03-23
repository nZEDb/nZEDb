<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCategoryRegexesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('category_regexes')) {
			Schema::create('category_regexes',
				function (Blueprint $table) {
					$table->increments('id');
					$table->string('group_regex')
						->default('')
						->index('ix_category_regexes_group_regex')
						->comment('This is a regex to match against usenet groups');
					$table->string('regex', 5000)
						->default('')
						->comment('Regex used to match a release name to categorize it');
					$table->boolean('status')
						->default(1)
						->index('ix_category_regexes_status')
						->comment('1=ON 0=OFF');
					$table->string('description', 1000)
						->default('')
						->comment('Optional extra details on this regex');
					$table->integer('ordinal')
						->default(0)
						->index('ix_category_regexes_ordinal')
						->comment('Order to run the regex in');
					$table->smallInteger('categories_id')
						->unsigned()
						->default(10)
						->index('ix_category_regexes_categories_id')
						->comment('Which categories id to put the release in');
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
		Schema::dropIfExists('category_regexes');
	}

}
?>
