<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSceneJobsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('scene_jobs', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('building_id');
			$table->tinyInteger('scene_id');
			$table->string('master_id');
			$table->string('curtains')->nullable()->default(null);
			$table->string('name');
			$table->time('run_at');
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
		Schema::dropIfExists('scene_jobs');
	}
}
