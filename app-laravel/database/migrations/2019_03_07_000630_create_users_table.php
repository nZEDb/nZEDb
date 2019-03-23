<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUsersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasTable('users')) {
			Schema::create('users',
				function (Blueprint $table) {
					$table->increments('id');
					$table->string('username', 50);
					$table->string('firstname')->nullable();
					$table->string('lastname')->nullable();
					$table->string('email');
					$table->string('password');
					$table->integer('role')->default(1)->index('ix_role');
					$table->string('host', 40)->nullable();
					$table->integer('grabs')->default(0);
					$table->string('rsstoken', 32);
					$table->dateTime('createddate');
					$table->string('resetguid', 50)->nullable();
					$table->dateTime('lastlogin')->nullable();
					$table->dateTime('apiaccess')->nullable();
					$table->integer('invites')->default(0);
					$table->integer('invitedby')->nullable();
					$table->integer('movieview')->default(1);
					$table->integer('xxxview')->default(1);
					$table->integer('musicview')->default(1);
					$table->integer('consoleview')->default(1);
					$table->integer('bookview')->default(1);
					$table->integer('gameview')->default(1);
					$table->string('saburl')->nullable();
					$table->string('sabapikey')->nullable();
					$table->boolean('sabapikeytype')->nullable();
					$table->boolean('sabpriority')->nullable();
					$table->boolean('queuetype')
						->default(1)
						->comment('Type of queue, Sab or NZBGet');
					$table->string('nzbgeturl')->nullable();
					$table->string('nzbgetusername')->nullable();
					$table->string('nzbgetpassword')->nullable();
					$table->string('userseed', 50);
					$table->string('cp_url')->nullable();
					$table->string('cp_api')->nullable();
					$table->string('style')->nullable();
					$table->index(['rsstoken', 'role'], 'ix_rsstoken_role');
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
		Schema::dropIfExists('users');
	}

}
?>
