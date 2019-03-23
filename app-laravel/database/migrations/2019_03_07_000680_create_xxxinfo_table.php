<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateXxxinfoTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('xxxinfo')) {
			Schema::create('xxxinfo',
				function (Blueprint $table) {
					$table->increments('id');
					$table->string('title')->unique('ix_xxxinfo_title');
					$table->string('tagline', 1024);
					$table->binary('plot', 65535)->nullable();
					$table->string('genre', 64);
					$table->string('director', 64)->nullable();
					$table->string('actors', 2500);
					$table->text('extras', 65535)->nullable();
					$table->text('productinfo', 65535)->nullable();
					$table->text('trailers', 65535)->nullable();
					$table->string('directurl', 2000);
					$table->string('classused', 4)->default('ade');
					$table->boolean('cover')->default(0);
					$table->boolean('backdrop')->default(0);
					$table->dateTime('createddate');
					$table->dateTime('updateddate');
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
		Schema::dropIfExists('xxxinfo');
	}

}
?>
