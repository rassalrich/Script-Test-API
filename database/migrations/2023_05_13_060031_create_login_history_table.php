<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoginHistoryTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('login_history', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('user_id');
			$table->enum('type', ['login', 'logout']);
			$table->boolean('sync')->nullable();
			$table->string('ip_address', 15);
			$table->timestamp('created_at');

			$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('login_history');
	}
}
