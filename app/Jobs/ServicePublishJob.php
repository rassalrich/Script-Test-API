<?php

namespace App\Jobs;

use App\Models\MQTTClientMessage;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\MqttClient;

class ServicePublishJob extends Job
{
	public $service;

	public $client;

	public $user;

	public $masterId;

	/**
	 * Create a new job instance.
	 *
	 * @return void
	 */
	public function __construct(Service $service, User $user)
	{
		$this->service = $service;
		$this->user = $user;
		$this->masterId = $service->master_id;
	}

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle()
	{
		try {
			$client_id = uniqid('mqtt_', true);

			$this->client = new MqttClient(env('MQTT_HOST'), env('MQTT_PORT', 1883), $client_id, MqttClient::MQTT_3_1, null);
			$this->client->connect(null, true);

			/* Service type handler */
			$type = $this->service->type;

			if ($type === "dali_light") return $this->daliLight();

			if ($type === "rgb.dt6") return $this->rgbDT6();

			if ($type === "rgb.dt8") return $this->rgbDT8();

			if ($type === "cct.dt6") return $this->cctDT6();

			if ($type === "cct.dt8") return $this->cctDT8();

			if ($type === "curtain") return $this->curtain();

			if ($type === "relay") return $this->relay();
		} catch (\Exception $e) {
			Log::error("[MQTT] [Service] " . $e->getMessage());
		} finally {
			$this->client->disconnect();
		}
	}

	/* Type of services */
	public function daliLight(): void
	{
		try {
			$id = $this->service->group_id === null ? $this->service->service_id : $this->service->group_id;
			$address = $this->service->group_id === null ? "SHORT_ADDRESS" : "GROUP_ADDRESS";

			$topic = $this->user->project_id . "/" . $this->masterId . "/Dali/In";
			$command = "{\"DAPC\r\n\":\"" . $this->service->value . "\r\n\",\"" . $address . "\r\n\":\"" . $id . "\r\n\"}";

			$this->client->publish($topic, $command);
		} catch (\Exception $e) {
			Log::error("[MQTT] [DaliLight] " . $e->getMessage());
		}
	}

	public function rgbDT6(): void
	{
		try {
			$id = (int) $this->service->service_id;
			$topic = $this->user->project_id . "/" . $this->masterId . "/Dali/In";
			$messages = [];

			[$r, $g, $b] = explode(",", $this->service->value);

			$messages[0] = "{\"DAPC\r\n\":\"" . $r . "\r\n\",\"SHORT_ADDRESS\r\n\":\"" . $id . "\r\n\"}";
			$this->client->publish($topic, $messages[0]);

			$messages[1] = "{\"DAPC\r\n\":\"" . $g . "\r\n\",\"SHORT_ADDRESS\r\n\":\"" . ($id + 1) . "\r\n\"}";
			$this->client->publish($topic, $messages[1]);

			$messages[2] = "{\"DAPC\r\n\":\"" . $b . "\r\n\",\"SHORT_ADDRESS\r\n\":\"" . ($id + 2) . "\r\n\"}";
			$this->client->publish($topic, $messages[2]);

			$this->createMultiLog(
				["topic" => $topic, "message" => $messages[0]],
				["topic" => $topic, "message" => $messages[1]],
				["topic" => $topic, "message" => $messages[2]]
			);
		} catch (\Exception $e) {
			Log::error("[MQTT] [RGB.DT6] " . $e->getMessage());
		}
	}

	public function rgbDT8(): void
	{
		try {
			$id = $this->service->group_id === null ? $this->service->service_id : $this->service->group_id;
			$address = $this->service->group_id === null ? "SHORT_ADDRESS" : "GROUP_ADDRESS";
			$topic = $this->user->project_id . "/" . $this->masterId . "/Dali/In";
			$messages = [];

			[$r, $g, $b, $o, $a] = explode(",", $this->service->value);

			$messages[0] = "{\"DAPC\r\n\":\"" . $a . "\r\n\",\"" . $address . "\r\n\":\"" . $id . "\r\n\"}";
			$this->client->publish($topic, $messages[0]);

			$hex = dechex($r) . dechex($g) . dechex($b);
			$dec = hexdec($hex);
			$messages[1] = "{\"SET_TEMPORARY_RGB_DIM_LEVEL\r\n\":\"" . $dec . "\r\n\",\"" . $address . "\r\n\":\"" . $id . "\r\n\"}";

			$this->client->publish($topic, $messages[1]);

			$this->createMultiLog(
				["topic" => $topic, "message" => $messages[0]],
				["topic" => $topic, "message" => $messages[1]]
			);
		} catch (\Exception $e) {
			Log::error("[MQTT] [RGB.DT8] " . $e->getMessage());
		}
	}

	public function cctDT6(): void
	{
		try {
			$id = $this->service->group_id === null ? $this->service->service_id : $this->service->group_id;
			$address = $this->service->group_id === null ? "SHORT_ADDRESS" : "GROUP_ADDRESS";
			$topic = $this->user->project_id . "/" . $this->masterId . "/Dali/In";
			$messages = [];

			[$cct, $brightness] = explode(",", $this->service->value);

			$messages[0] = "{\"DAPC\r\n\":\"" . $brightness . "\r\n\",\"" . $address . "\r\n\":\"" . $id . "\r\n\"}";
			$this->client->publish($topic, $messages[0]);

			$messages[1] = "{\"SET_TEMPORARY_COLOUR_TEMPERATURE_TC\r\n\":\"" . floor($cct) . "\r\n\",\"SHORT_ADDRESS\r\n\":\"" . $id . "\r\n\"}";
			$this->client->publish($topic, $messages[1]);

			$this->createMultiLog(
				["topic" => $topic, "message" => $messages[0]],
				["topic" => $topic, "message" => $messages[1]]
			);
		} catch (\Exception $e) {
			Log::error("[MQTT] [CCT.DT6] " . $e->getMessage());
		}
	}

	public function cctDT8(): void
	{
		$this->cctDT6();
	}

	public function curtain(): void
	{
		try {
			$value = $this->service->value ? 'open' : 'close';
			$topic = $this->user->project_id . "/" . $this->masterId . "/Curtain/In";
			$command = "{\"id\":\"" . $this->service->service_id . "\",\"command\":\"" . $value . "\"}";

			$this->client->publish($topic, $command);

			$this->createLog($topic, $command);
		} catch (\Exception $e) {
			Log::error("[MQTT] [Curtain] " . $e->getMessage());
		}
	}

	public function relay(): void
	{
		try {
			$value = $this->service->value ? 'on' : 'off';
			$topic = $this->user->project_id . "/" . $this->masterId . "/Relay/In";
			$command = "{\"command\":\"" . $this->service->service_id . "\",\"id\":\"" . $value . "\"}";

			$this->client->publish($topic, $command);

			$this->createLog($topic, $command);
		} catch (\Exception $e) {
			Log::error("[MQTT] [Relay] " . $e->getMessage());
		}
	}

	public function createLog(string $topic, string $message): void
	{
		try {
			$now = Carbon::now();
			MQTTClientMessage::create([
				'user_id' => $this->user->project_id,
				'topic' => $topic,
				'message' => $message,
				'created_at' => $now,
			]);
		} catch (\Exception $e) {
			Log::error("[Service] [MQTTClientMessage] [Single] " . $e->getMessage());
		}
	}

	public function createMultiLog(...$messages): void
	{
		try {
			$now = Carbon::now();
			$row = [
				'user_id' => $this->user->id,
				'created_at' => $now,
			];

			$insert = [];
			foreach ($messages as $message) {
				array_push($insert, array_merge($row, $message));
			}

			MQTTClientMessage::insert($insert);
		} catch (\Exception $e) {
			Log::error("[Service] [MQTTClientMessage] [Multi] " . $e->getMessage());
		}
	}
}
