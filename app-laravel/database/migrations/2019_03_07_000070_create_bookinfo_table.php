<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateBookinfoTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('bookinfo')) {
			Schema::create('bookinfo',
				function (Blueprint $table) {
					$table->increments('id');
					$table->string('title');
					$table->string('author');
					$table->string('asin', 128)->nullable()->unique('ix_bookinfo_asin');
					$table->string('isbn', 128)->nullable();
					$table->string('ean', 128)->nullable();
					$table->string('url', 1000)->nullable();
					$table->integer('salesrank')->unsigned()->nullable();
					$table->string('publisher')->nullable();
					$table->dateTime('publishdate')->nullable();
					$table->string('pages', 128)->nullable();
					$table->string('overview', 3000)->nullable();
					$table->string('genre');
					$table->boolean('cover')->default(0);
					$table->dateTime('createddate');
					$table->dateTime('updateddate');
					$table->index(['author', 'title'], 'ix_bookinfo_author_title_ft');
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
		Schema::dropIfExists('bookinfo');
	}

}
?>
