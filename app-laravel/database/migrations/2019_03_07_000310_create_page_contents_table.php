<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePageContentsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('page_contents')) {
			Schema::create('page_contents',
				function (Blueprint $table) {
					$table->integer('id', true);
					$table->string('title');
					$table->string('url', 2000)->nullable();
					$table->text('body', 65535)->nullable();
					$table->string('metadescription', 1000);
					$table->string('metakeywords', 1000);
					$table->integer('contenttype');
					$table->integer('showinmenu');
					$table->integer('status');
					$table->integer('ordinal')->nullable();
					$table->integer('role')->default(0);
					$table->index(['showinmenu', 'status', 'contenttype', 'role'],
						'ix_showinmenu_status_contenttype_role');
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
		Schema::dropIfExists('page_contents');
	}

}
?>
