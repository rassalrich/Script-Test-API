<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MQTTClientMessage extends Model
{
	protected $table = 'mqtt_messages';

	protected $fillable = [
		'user_id', 'topic', 'message', 'created_at'
	];
}
