<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScenesTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('scenes', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('server_id');
			$table->unsignedBigInteger('building_id');
			$table->tinyInteger('scene_id');
			$table->string('master_id');
			$table->string('name', 32);
			$table->string('image')->nullable();
			$table->timestamps();
			$table->softDeletes();

			$table->foreign('building_id')->references('id')->on('buildings')->onDelete('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('scenes');
	}
}
