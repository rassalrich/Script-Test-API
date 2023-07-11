<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServicesTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('services', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('server_id');
			$table->unsignedBigInteger('building_id');
			$table->json('location');
			$table->string('service_id');
			$table->tinyInteger('group_id')->nullable();
			$table->tinyInteger('master_id')->nullable();
			$table->string('value');
			$table->enum('type', ['dali_light', 'rgb.dt6', 'rgb.dt8', 'cct.dt6', 'cct.dt8', 'curtain', 'relay']);
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
		Schema::dropIfExists('services');
	}
}
