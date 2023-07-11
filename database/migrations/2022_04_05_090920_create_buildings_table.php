<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBuildingsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('buildings', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('server_id');
			$table->unsignedBigInteger('user_id');
			$table->string('name', 32);
			$table->enum('type', ['room', 'floor']);
			$table->smallInteger('area');
			$table->string('image')->nullable();
			$table->timestamps();
			$table->softDeletes();

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
		Schema::dropIfExists('buildings');
	}
}
