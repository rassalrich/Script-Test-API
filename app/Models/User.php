<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
{
	use Authenticatable, Authorizable, HasApiTokens, SoftDeletes;

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'name', 'email', 'password', 'mqtt_ip', 'mqtt_port', 'project_id', 'image'
	];

	/**
	 * The attributes excluded from the model's JSON form.
	 *
	 * @var array
	 */
	protected $hidden = [
		'password'
	];

	public function buildings()
	{
		return $this->hasMany(Building::class);
	}

	public function scenes()
	{
		return $this->hasManyThrough(Scene::class, Building::class);
	}

	public function services()
	{
		return $this->hasManyThrough(Service::class, Building::class);
	}

	public function thermostats()
	{
		return $this->hasManyThrough(Thermostat::class, Building::class);
	}
}
