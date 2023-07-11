<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Building extends Model
{
	use SoftDeletes;

	protected $table = 'buildings';

	protected $fillable = [
		'server_id', 'user_id', 'name', 'type', 'area', 'image'
	];

	public function services(): \Illuminate\Database\Eloquent\Relations\HasMany
	{
		return $this->hasMany(Service::class);
	}

	public function thermostats(): \Illuminate\Database\Eloquent\Relations\HasMany
	{
		return $this->hasMany(Thermostat::class);
	}

	public function scenes(): \Illuminate\Database\Eloquent\Relations\HasMany
	{
		return $this->hasMany(Scene::class);
	}
}
