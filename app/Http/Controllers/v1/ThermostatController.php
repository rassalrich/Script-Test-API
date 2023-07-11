<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateThermostatRequest;
use App\Jobs\ThermostatPublishJob;
use App\Models\Thermostat;
use Illuminate\Support\Facades\Log;
use App\Models\MQTTClientMessage;
use App\Models\User;
use PhpMqtt\Client\MqttClient;

class ThermostatController extends Controller
{
	private $columns = ['id', 'building_id', 'location', 'service_id', 'group_id', 'level', 'off', 'value', 'to_value'];

	public function index(): \Illuminate\Http\JsonResponse
	{
		$user = request()->user();
		$thermostats = $user->thermostats($this->columns)
			->get()
			->map(function ($item) {
				$item->location = [
					(float) $item->location[0],
					(float) $item->location[1]
				];
				return $item;
			})
			->toArray();

		return resJson($thermostats);
	}

	public function findOne(int $thID): \Illuminate\Http\JsonResponse
	{
		$user = request()->user();
		$th = $user->thermostats($this->columns)
			->where('thermostats.id', $thID)
			->get()
			->map(function ($item) {
				$item->location = [
					(float) $item->location[0],
					(float) $item->location[1]
				];
				return $item;
			})
			->first();

		return resJson($th);
	}

	public function getBuildingThermostats(int $buildingId)
	{
		$user = request()->user();
		$thermostats = $user->thermostats($this->columns)
			->where('thermostats.building_id', $buildingId)
			->get()
			->map(function ($item) {
				$item->location = [
					(float) $item->location[0],
					(float) $item->location[1]
				];
				return $item;
			})
			->toArray();

		return resJson($thermostats);
	}

	public function update(UpdateThermostatRequest $request, int $thID): \Illuminate\Http\JsonResponse
	{
		try {
			$user = request()->user();

			$thermostat = Thermostat::find($thID);
			if (!$thermostat) return resJson([], 'Thermostat not found.', false);

			$buildings = $user->buildings()->pluck('id')->toArray();
			if (!in_array($thermostat->building_id, $buildings)) return resJson([], 'Forbidden.', false);

			$fields = [];

			if ($request->has('value') && $request->value !== (int) $thermostat->to_value) {
				$fields[] = 'value';
				$thermostat->to_value = (int) $request->value;
			}

			if ($request->has('level') && $thermostat->level !== $request->level) {
				$fields[] = 'level';
				$thermostat->level = $request->level;
			}

			if ($request->has('off') && $thermostat->off !== (int) $request->off) {
				$fields[] = 'off';
				$thermostat->off = (int) $request->off;
			}

			$thermostat->save();

			$this->publishOnMQTT($thermostat, $fields, $user);
			/* try {
				dispatch(new ThermostatPublishJob($thermostat, $fields, $user))->onQueue('mqtt');
			} catch (\Exception $e) {
				Log::error("[MQTT] [Thermostat] " . $e->getMessage());
			} */

			return resJson($request->only('value', 'to_value', 'level', 'off'));
		} catch (\Exception $e) {
			return resJson(['value' => 0], 'Something went wrong.', false);
		}
	}

	// MQTT
	public function publishOnMQTT(Thermostat $thermostat, array $fields, User $user)
	{
		try {
			$client_id = uniqid('mqtt_', true);

			$client = new MqttClient(env('MQTT_HOST'), env('MQTT_PORT', 1883), $client_id, MqttClient::MQTT_3_1, null);
			$client->connect(null, true);

			$now = now();
			$topic = $user->project_id . "/Thermo/In";
			$row = ['user_id' => $user->id, 'topic' => $topic, 'created_at' => $now];
			$commands = [];

			/* OFF */
			if (in_array('off', $fields)) {
				$off = ((bool) $thermostat->off) ? "off" : "on";
				$message = "{\"id\":\"" . $thermostat->service_id . "\",\"command\":\"" . $off . "\"}";
				$commands[] = array_merge($row, ["message" => $message]);

				$client->publish($topic, $message);
			}

			/* VALUE */
			if (in_array('value', $fields)) {
				$message = "{\"id\":\"" . $thermostat->service_id . "\",\"command\":\"settemp\",\"value\":\"" . $thermostat->to_value . "\"}";
				$commands[] = array_merge($row, ["message" => $message]);

				$client->publish($topic, $message);
			}

			/* LEVEL */
			if (in_array('level', $fields)) {
				$message = "{\"id\":\"" . $thermostat->service_id . "\",\"command\":\"" . $thermostat->level . "\"}";
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
