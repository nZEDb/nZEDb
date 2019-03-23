<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;


class CreateTmuxTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (! Schema::hasTable('tmux')) {
			Schema::create('tmux',
				function (Blueprint $table) {
					$table->increments('id');
					$table->string('setting', 64)
						->unique('ux_tmux_setting');
					$table->string('value', 1000)
						->nullable();
					$table->timestamp('updateddate')
						->default(DB::raw('CURRENT_TIMESTAMP'));
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
		Schema::dropIfExists('tmux');
	}
}

?>
