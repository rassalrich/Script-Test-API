<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Thermostat extends Model
{
	use SoftDeletes;

	protected $table = 'thermostats';

	protected $fillable = [
		'server_id', 'building_id', 'location', 'service_id', 'group_id', 'level', 'off', 'value', 'to_value'
	];

	protected $casts = [
		'location' => 'array'
	];

	public function building()
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
}
