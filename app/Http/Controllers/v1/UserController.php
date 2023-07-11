<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
	public function index(Request $request)
	{
		$user = $request->user();
		$user = collect($user)->except('created_at', 'updated_at', 'deleted_at', 'id');

		return resJson($user);
	}

	public function updateMqttInformation(Request $request)
	{
		try {
			$validator = Validator::make($request->all(), [
				'ip' => 'required|ip',
				'port' => 'required|numeric',
			]);

			if ($validator->fails()) return resJson([], 'IP or PORT invalid.', false);

			$user = $request->user();

			$user->mqtt_ip = $request->ip;
			$user->mqtt_port = $request->port;
			$user->save();

			return resJson(
				collect($user)->except('created_at', 'updated_at')
			);
		} catch (\Exception $e) {
			Log::error($e->getMessage());
			return resJson([], 'Something went wrong.', false);
		}
	}

	public function ping()
	{
		return resJson('PONG');
	}
}
