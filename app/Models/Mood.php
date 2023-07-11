<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mood extends Model
{
	use SoftDeletes;

	protected $table = 'moods';

	protected $fillable = [
		'building_id', 'name', 'services'
	];

	public function building(): \Illuminate\Database\Eloquent\Relations\BelongsTo
	{
		return $this->belongsTo(Building::class);
	}

	public function scopeAutomatic()
	{
		return $this->where('automatic_jobs.job_type', 'MOOD');
	}
}
