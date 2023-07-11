<?php

namespace App\Jobs;

use App\Models\MQTTClientMessage;
use App\Models\Scene;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\MqttClient;

class ScenePublishJob extends Job
{
	public $scene;

	/**
	 * Create a new job instance.
	 *
	 * @return void
	 */
	public function __construct(Scene $scene)
	{
		$this->scene = $scene;
	}

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle()
	{
		try {
			$user = $this->scene->user();

			if (!$user) return;

			$client_id = uniqid('mqtt_', true);

			$client = new MqttClient(env('MQTT_HOST'), env('MQTT_PORT', 1883), $client_id, MqttClient::MQTT_3_1, null);
			$client->connect(null, true);

			$topic = $user->project_id . "/" . $this->scene->master_id . "/Dali/In";
			$message = "{\"CMD\r\n\":\"GO_TO_SCENE_" . $this->scene->scene_id . "\r\n\",\"BROADCAST\r\n\":\"ALL\r\n\"}";
			$client->publish($topic, $message);

			/* Create MQTT log */
			try {
				$now = Carbon::now();
				MQTTClientMessage::create([
					'user_id' => $user->id,
					'topic' => $topic,
					'message' => $message,
					'created_at' => $now,
				]);
			} catch (\Exception $e) {
				Log::error("[Scene] [MQTTClientMessage] " . $e->getMessage());
			}
		} catch (\Exception $e) {
			Log::error("[MQTT] [Scene] " . $e->getMessage());
		} finally {
			$client->disconnect();
		}
	}
}
