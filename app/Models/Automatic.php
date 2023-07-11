<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Automatic extends Model
{
	use SoftDeletes;

	protected $table = 'automatic_jobs';

	protected $fillable = [
		'user_id', 'job_id', 'job_type', 'every', 'time', 'date'
	];

	protected $casts = [
		'every' => 'array'
	];
}
