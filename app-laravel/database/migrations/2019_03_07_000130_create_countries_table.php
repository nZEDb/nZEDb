<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCountriesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('countries')) {
			Schema::create('countries',
				function (Blueprint $table) {
					$table->char('id', 2)->primary()->comment('2 character code.');
					$table->char('iso3', 3)->unique('code3')->comment('3 character code.');
					$table->string('country', 180)
						->unique('country')
						->comment('Name of the country.');
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
		Schema::dropIfExists('countries');
	}

}
?>
