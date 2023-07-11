<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
	use SoftDeletes;

	protected $table = 'services';

	protected $fillable = [
		'server_id', 'building_id', 'location', 'service_id', 'group_id', 'master_id', 'value', 'type'
	];

	protected $casts = [
		'location' => 'array'
	];

	public static $serviceTypes = ['dali_light', 'rgb.dt6', 'rgb.dt8', 'cct.dt6', 'cct.dt8', 'curtain', 'relay'];

	public function user()
	{
		return $this->join('buildings', 'services.building_id', 'buildings.id')
			->join('users', 'buildings.user_id', 'users.id')
			->select('users.project_id')
			->first();
	}

	/**
	 * Scope a query to only include Curtain services.
	 */
	public function scopeCurtain($query): \Illuminate\Database\Eloquent\Builder
	{
		return $query->where('type', 'curtain');
	}

	/**
	 * Scope a query to only include Relay services.
	 */
	public function scopeRelay($query): \Illuminate\Database\Eloquent\Builder
	{
		return $query->where('type', 'relay');
	}

	/**
	 * Relations
	 */
	public function building(): \Illuminate\Database\Eloquent\Relations\BelongsTo
	{
		return $this->belongsTo(Building::class);
	}

	public function styles(): \Illuminate\Database\Eloquent\Relations\BelongsTo
	{
		return $this->belongsTo(Style::class, 'style_id', 'id');
	}
}
