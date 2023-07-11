<?php

namespace App\Jobs;

use App\Models\Thermostat;
use Illuminate\Support\Facades\Log;
use App\Models\MQTTClientMessage;
use App\Models\User;
use Carbon\Carbon;
use PhpMqtt\Client\MqttClient;

class ThermostatPublishJob extends Job
{
	public $thermostat;

	public $fields;

	public $user;

	/**
	 * Create a new job instance.
	 *
	 * @return void
	 */
	public function __construct(Thermostat $thermostat, array $fields, User $user)
	{
		$this->thermostat = $thermostat;
		$this->fields = $fields;
		$this->user = $user;
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

			$client = new MqttClient(env('MQTT_HOST'), env('MQTT_PORT', 1883), $client_id, MqttClient::MQTT_3_1, null);
			$client->connect(null, true);

			$now = Carbon::now();
			$topic = $this->user->project_id . "/Thermo/In";
			$row = ['user_id' => $this->user->id, 'topic' => $topic, 'created_at' => $now];
			$commands = [];

			/* OFF */
			if (in_array('off', $this->fields)) {
				$off = ((bool) $this->thermostat->off) ? "off" : "on";
				$message = "{\"id\":\"" . $this->thermostat->service_id . "\",\"command\":\"" . $off . "\"}";
				$commands[] = array_merge($row, ["message" => $message]);

				$client->publish($topic, $message);
			}

			/* VALUE */
			if (in_array('value', $this->fields)) {
				$message = "{\"id\":\"" . $this->thermostat->service_id . "\",\"command\":\"settemp\",\"value\":\"" . $this->thermostat->to_value . "\"}";
				$commands[] = array_merge($row, ["message" => $message]);

				$client->publish($topic, $message);
			}

			/* LEVEL */
			if (in_array('level', $this->fields)) {
				$message = "{\"id\":\"" . $this->thermostat->service_id . "\",\"command\":\"" . $this->thermostat->level . "\"}";
				$commands[] = array_merge($row, ["message" => $message]);

				$client->publish($topic, $message);
			}

			/* Create MQTT log */
			try {
				if (count($commands) > 0) MQTTClientMessage::insert($commands);
			} catch (\Exception $e) {
				Log::error("[Thermostat] [MQTTClientMessage] " . $e->getMessage());
			}
		} catch (\Exception $e) {
			Log::error("[MQTT] [Thermostat] " . $e->getMessage());
		} finally {
			$client->disconnect();
		}
	}
}
