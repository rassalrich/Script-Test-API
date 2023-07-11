<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Scene extends Model
{
	use SoftDeletes;

	protected $table = 'scenes';

	protected $fillable = [
		'server_id', 'building_id', 'master_id', 'scene_id', 'name', 'image'
	];

	public function building(): \Illuminate\Database\Eloquent\Relations\BelongsTo
	{
		return $this->belongsTo(Building::class);
	}

	public function user()
	{
		return $this->join('buildings', 'thermostats.building_id', 'buildings.id')
			->join('users', 'buildings.user_id', 'users.id')
			->select('users.project_id')
			->first();
	}

	public function scopeAutomatic()
	{
		return $this
			->join('automatic_jobs as aj', 'scenes.id', 'aj.job_id')
			->select('aj.every', 'aj.date', 'aj.time', 'scenes.*');
	}
}
