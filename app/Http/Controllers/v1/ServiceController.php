<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateGroupServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Jobs\ServicePublishJob;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\MQTTClientMessage;
use PhpMqtt\Client\MqttClient;

class ServiceController extends Controller
{
	private $user;

	private $service;

	private $masterId;

	private $client;

	private $columns = ['id', 'building_id', 'location', 'service_id', 'group_id', 'master_id', 'value', 'type'];

	public function index(): \Illuminate\Http\JsonResponse
	{
		$user = request()->user();
		$services = $user->services($this->columns)
			->get()
			->map(function ($item) {
				$item->location = [
					(string) $item->location[0],
					(string) $item->location[1]
				];
				return $item;
			})
			->toArray();

		return resJson($services);
	}

	public function getBuildingServices(int $buildingId)
	{
		$user = request()->user();
		$services = $user->services($this->columns)
			->where('services.building_id', $buildingId)
			->get()
			->map(function ($item) {
				$item->location = [
					(string) $item->location[0],
					(string) $item->location[1]
				];
				return $item;
			})
			->toArray();

		return resJson($services);
	}

	public function setDim(UpdateServiceRequest $request): \Illuminate\Http\JsonResponse
	{
		try {
			$valueAsString = is_array($request->value) ? implode(',', $request->value) : $request->value;

			$service = Service::find($request->id);
			if (!$service) return resJson([], 'Service not found.', false);

			if ($request->has('value') && $service->value !== $valueAsString) {
				$service->value = $valueAsString;
				$service->save();

				try {
					$client_id = uniqid('mqtt_', true);

					$this->client = new MqttClient(env('MQTT_HOST'), env('MQTT_PORT', 1883), $client_id, MqttClient::MQTT_3_1, null);
					$this->client->connect(null, true);
				} catch (\Exception $e) {
					Log::error("[MQTT] [Service] " . $e->getMessage());
				}

				$this->publishOnMQTT($service, request()->user());

				/* try {
					$user = request()->user();

					dispatch(new ServicePublishJob($service, $user))->onQueue('mqtt');
				} catch (\Exception $e) {
					Log::error($e->getMessage());
				} */
			}

			return resJson(['value' => $request->value]);
		} catch (\Exception $e) {
			return resJson(['value' => 0], 'Something went wrong.');
		} finally {
			if ($this->client) $this->client->disconnect();
		}
	}

	public function setGroupDim(UpdateGroupServiceRequest $request): \Illuminate\Http\JsonResponse
	{
		DB::beginTransaction();
		try {
			$reqServices = $request->all();
			$updatedValues = [];

			$user = request()->user();

			try {
				$client_id = uniqid('mqtt_', true);

				$this->client = new MqttClient(env('MQTT_HOST'), env('MQTT_PORT', 1883), $client_id, MqttClient::MQTT_3_1, null);
				$this->client->connect(null, true);
			} catch (\Exception $e) {
				Log::error("[MQTT] [Service] " . $e->getMessage());
			}

			foreach ($reqServices as $sv) {
				$valueAsString = is_array($sv['value']) ? implode(',', $sv['value']) : $sv['value'];

				$service = Service::find($sv['id']);

				if ($service->value !== $valueAsString) {
					$service->value = $valueAsString;
					$service->save();

					$this->publishOnMQTT($service, $user);
					/* try {
						dispatch(new ServicePublishJob($service, $user))->onQueue('mqtt');
					} catch (\Exception $e) {
						Log::error($e->getMessage());
					} */
				}

				array_push($updatedValues, ['id' => $sv['id'], 'value' => $sv['value']]);
			}

			DB::commit();
			return resJson($updatedValues);
		} catch (\Exception $e) {
			DB::rollBack();
			return resJson([], 'Something went wrong.');
		} finally {
			if ($this->client) $this->client->disconnect();
		}
	}

	// MQTT
	public function publishOnMQTT(Service $service, User $user)
	{
		try {
			$this->user = $user;

			$this->service = $service;

			$this->masterId = $service->master_id;

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
		}
	}

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
			MQTTClientMessage::create([
				'user_id' => $this->user->project_id,
				'topic' => $topic,
				'message' => $message,
				'created_at' => now(),
			]);
		} catch (\Exception $e) {
			Log::error("[Service] [MQTTClientMessage] [Single] " . $e->getMessage());
		}
	}

	public function createMultiLog(...$messages): void
	{
		try {
			$row = [
				'user_id' => $this->user->id,
				'created_at' => now(),
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
