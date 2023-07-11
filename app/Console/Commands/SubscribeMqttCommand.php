<?php

namespace App\Console\Commands;

use App\Models\Thermostat;
use Illuminate\Console\Command;
use PhpMqtt\Client\MqttClient;
use Illuminate\Support\Facades\Log;

class SubscribeMqttCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'mqtt:subscribe';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Subscribe MQTT topics';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{
		try {
			$client_id = uniqid('mqtt_', true);

			try {
				// Create a new instance of an MQTT client and configure it to use the shared broker host and port.
				$client = new MqttClient(env('MQTT_HOST'), env('MQTT_PORT', 1883), $client_id, MqttClient::MQTT_3_1, null);

				// Connect to the broker without specific connection settings but with a clean session.
				$client->connect(null, true);

				try {
					$client->subscribe("#", function (string $tpc, string $message) {
						$topicDetails = explode("/", $tpc);
						$length = count($topicDetails);
						$topic = [
							"project_id" => $topicDetails[0],
							"master_id" => $length === 4 ? $topicDetails[1] : -1,
							"name" => $topicDetails[$length - 2],
							"type" => $topicDetails[$length - 1],
						];

						$availableTopicNames = ["Dali", "Thermo", "Relay", "Curtain"];

						if ($topic["type"] === "Out" && in_array($topic['name'], $availableTopicNames)) {
							if ($topic["name"] === "Dali") $this->dali($topic['project_id'], $topic['master_id'], $message);
							if ($topic["name"] === "Thermo") $this->thermostat($topic['project_id'], $message);
							if ($topic["name"] === "Curtain") $this->curtain($topic['project_id'], $message);
							if ($topic["name"] === "Relay") $this->relay($topic['project_id'], $topic['master_id'], $message);
						}
					}, 1);
					$client->loop(true);
				} catch (\Exception $e) {
					Log::error("[MQTT] [ERROR] " . $e->getMessage());
				}
			} catch (\Exception $e) {
				Log::error("[MQTT] [ERROR] " . $e->getMessage());
			}

			$client->disconnect();
		} catch (\Exception $e) {
			Log::error("[MQTT] [ERROR] " . $e->getMessage());
		}
	}

	public function fastScan(int $project_id, int $master_id, string $message)
	{
		try {
			//
		} catch (\Exception $e) {
			Log::warning("[MQTT] [FAST_SCAN] " . $e->getMessage());
		}
	}

	public function dali(int $project_id)
	{
		try {
			//
		} catch (\Exception $e) {
			Log::warning("[MQTT] [DECODE] " . $e->getMessage());
		}
	}

	public function thermostat(int $project_id, string $message)
	{
		try {
			$lines = explode("\n", $message);
			if (count($lines) <= 2) return;

			$service_id = $lines[4];

			if (!$project_id || !$service_id) return;

			$thermostat = Thermostat::where('thermostats.service_id', $service_id)
				->join('buildings', 'thermostats.building_id', "buildings.id")
				->join('users', 'buildings.user_id', 'users.id')
				->where('users.project_id', $project_id)
				->select('thermostats.*')
				->first();

			if (!$thermostat) return;

			if ($lines[1] === "CURRENT_STATUS") {
				[$command, $value] = explode(" ", $lines[2]);

				if ($command === 'FAN') $thermostat->level = strtolower($value);
				else if ($command === 'THERMO') $thermostat->off = $value === "OFF" ? 1 : 0;
			} else if ($lines[1] === "CURRENT_TEMPERATURE") {
				$thermostat->value = $lines[2];
			} else if ($lines[1] === "TEMPERATURE_SET_AS") {
				$thermostat->to_value = $lines[2];
			}

			$thermostat->save();
		} catch (\Exception $e) {
			Log::warning("[MQTT] [DECODE] " . $e->getMessage());
		}
	}

	public function curtain(int $project_id, string $message)
	{
		try {
			//
		} catch (\Exception $e) {
			Log::warning("[MQTT] [DECODE] " . $e->getMessage());
		}
	}

	public function relay(int $project_id, int $master_id, string $message)
	{
		try {
			//
		} catch (\Exception $e) {
			Log::warning("[MQTT] [DECODE] " . $e->getMessage());
		}
	}
}
