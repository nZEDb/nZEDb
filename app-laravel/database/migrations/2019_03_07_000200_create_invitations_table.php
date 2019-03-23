<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateInvitationsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('invitations')) {
			Schema::create('invitations',
				function (Blueprint $table) {
					$table->increments('id');
					$table->string('guid', 50);
					$table->integer('user_id')->unsigned();
					$table->dateTime('createddate');
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
		Schema::dropIfExists('invitations');
	}

}
?>
