<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SceneJob extends Model
{
	use SoftDeletes;

	protected $table = 'scene_jobs';

	protected $fillable = [
		'building_id', 'master_id', 'scene_id', 'curtains', 'name', 'run_at'
	];
}
