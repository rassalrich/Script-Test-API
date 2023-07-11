<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateThermostatsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('thermostats', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('server_id');
			$table->unsignedBigInteger('building_id');
			$table->json('location');
			$table->string('service_id');
			$table->tinyInteger('group_id')->nullable();
			$table->enum('level', ['low', 'medium', 'high', 'auto']);
			$table->boolean('off');
			$table->smallInteger('value');
			$table->smallInteger('to_value');
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
		Schema::dropIfExists('thermostats');
	}
}
