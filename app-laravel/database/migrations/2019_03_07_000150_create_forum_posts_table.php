<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateForumPostsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('forum_posts')) {
			Schema::create('forum_posts',
				function (Blueprint $table) {
					$table->increments('id');
					$table->integer('forumid')->default(1);
					$table->integer('parentid')->default(0)->index('parentid');
					$table->integer('user_id')->unsigned()->index('userid');
					$table->string('subject');
					$table->text('message', 65535);
					$table->boolean('locked')->default(0);
					$table->boolean('sticky')->default(0);
					$table->integer('replies')->unsigned()->default(0);
					$table->dateTime('createddate')->index('createddate');
					$table->dateTime('updateddate')->index('updateddate');
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
		Schema::dropIfExists('forum_posts');
	}

}
?>
