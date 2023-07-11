<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginPostRequest;
use App\Jobs\FetchUserDataJob;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProjectImagesJob;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AuthController extends Controller
{
	private $user;

	private $token;

	private $userIsExists = true;

	public function login(LoginPostRequest $request): \Illuminate\Http\JsonResponse
	{
		try {
			if ($request->get('sync', true)) {
				$this->onlineLogin($request);
				dispatch(new FetchUserDataJob($this->user, $this->token, $this->userIsExists))->onQueue('redis');
			} else {
				$this->offlineLogin($request);
			}

			/* Tokens */
			$token = $this->user->createToken('login')->accessToken;

			try {
				$now = now();
				DB::table('login_history')->insert([
					"user_id" => $this->user->id,
					"type" => "login",
					"sync" => $request->get('sync', true), // sync true = ONLINE
					"ip_address" => request()->getClientIp(),
					"created_at" => $now,
				]);
			} catch (\Exception $e) {
				Log::error("[Login] [CREATE_LOG]: " . $e);
			}

			return resJson([
				'accessToken' => $token,
				'name' => $this->user->name,
				'email' => $this->user->email,
				'mqtt_ip' => $this->user->mqtt_ip,
				'mqtt_port' => $this->user->mqtt_port,
				'project_id' => $this->user->project_id,
				'image' => $this->user->image
			]);
		} catch (\Exception $e) {
			Log::error("[Login]: " . $e);

			return resJson(null, $e->getMessage(), false);
		}
	}

	public function logout(): \Illuminate\Http\JsonResponse
	{
		try {
			$user = auth()->user();

			if ($user) {
				$client = new \GuzzleHttp\Client();
				$client->post(apiRoute('/oauth/logout'));

				$token = $user->token();
				if ($token) $token->revoke();

				try {
					$now = now();
					DB::table('login_history')->insert([
						"user_id" => $user->id,
						"type" => "logout",
						"sync" => null,
						"ip_address" => request()->getClientIp(),
						"created_at" => $now,
					]);
				} catch (\Exception $e) {
					Log::error("[Logout] [CREATE_LOG] " . $e->getMessage());
				}
			}
		} catch (\Exception $e) {
			Log::error("LOGOUT: " . $e);
		}

		return resJson([], 'Logout was successful.');
	}

	/* Offline login without sync */
	private function offlineLogin(Request $request): void
	{
		$this->user = User::where('email', $request->email)->first();
		if ($this->user === null) throw new \Exception('User has not registered with email "' . $request->email . '"');
	}

	/* Online login with sync */
	private function onlineLogin(Request $request): AuthController
	{
		$client = new \GuzzleHttp\Client();
		$response = $client->post(apiRoute('/oauth/login'), [
			'form_params' => $request->only('email', 'password')
		]);
		$result = json_decode($response->getBody()->getContents(), true);

		if ($result['succeeded'] === false) throw new \Exception($result['message']);

		$data = $result['data'];

		$this->token = $data['accessToken'];
		$this->user = User::where('email', $data['email'])->first();

		if ($this->user === null) {
			$imageURL = $this->uploadProjectImage($data['image']);

			$this->userIsExists = false;
			$this->user = User::create([
				'name' => $data['name'],
				'email' => $data['email'],
				'password' => Hash::make($request->password),
				'project_id' => $data['mqtt_id'],
				'image' => $imageURL
			]);
		}

		return $this;
	}

	/* UPLOAD IMAGE */
	private function uploadProjectImage(string $url)
	{
		$name = getNameFromURL($url);

		dispatch(new ProjectImagesJob($url, $name))->onQueue('images');

		return Storage::url('projects/' . $name);
	}
}
