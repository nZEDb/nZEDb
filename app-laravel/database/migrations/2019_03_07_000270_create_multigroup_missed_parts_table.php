<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMultigroupMissedPartsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('multigroup_missed_parts')) {
			Schema::create('multigroup_missed_parts',
				function (Blueprint $table) {
					$table->increments('id');
					$table->bigInteger('numberid')->unsigned();
					$table->integer('groups_id')
						->unsigned()
						->default(0)
						->comment('FK to groups.id');
					$table->boolean('attempts')->default(0)->index('ix_missed_parts_attempts');
					$table->index(['groups_id', 'attempts'], 'ix_missed_parts_groupsid_attempts');
					$table->index(['numberid', 'groups_id', 'attempts'],
						'ix_missed_parts_numberid_groupsid_attempts');
					$table->unique(['numberid', 'groups_id'], 'ux_missed_parts_numberid_groupsid');
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
		Schema::dropIfExists('multigroup_missed_parts');
	}

}
?>
