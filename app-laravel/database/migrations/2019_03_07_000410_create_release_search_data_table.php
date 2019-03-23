<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateReleaseSearchDataTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('release_search_data')) {
			Schema::create('release_search_data',
				function (Blueprint $table) {
					$table->increments('id');
					$table->integer('releases_id')
						->unsigned()
						->index('ix_releasesearch_releases_id')
						->comment('FK to releases.id');
					$table->string('guid', 50)->index('ix_releasesearch_guid');
					$table->string('name')->default('')->index('ix_releasesearch_name_ft');
					$table->string('searchname')
						->default('')
						->index('ix_releasesearch_searchname_ft');
					$table->string('fromname')->nullable()->index('ix_releasesearch_fromname_ft');
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
		Schema::dropIfExists('release_search_data');
	}

}
?>
