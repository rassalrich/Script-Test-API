<?php

namespace App\Jobs;

use App\Jobs\BuildingImagesJob;
use App\Jobs\SceneImagesJob;
use App\Models\Building;
use App\Models\Scene;
use App\Models\Service;
use App\Models\Thermostat;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

class FetchUserDataJob extends Job
{
	private $buildings;

	private $userIsExists;

	private $user;

	private $token;

	/**
	 * Create a new job instance.
	 *
	 * @return void
	 */
	public function __construct(User $user, string $token, bool $userIsExists)
	{
		$this->user = $user;
		$this->token = $token;
		$this->userIsExists = $userIsExists;
	}

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle()
	{
		$this->fetchBuilding();
		$this->fetchScenes();
		$this->fetchServices();
		$this->fetchThermostats();
	}

	/* Fetch from installer */
	private function fetchBuilding()
	{
		try {
			/* Insert */
			$result = $this->apiRequest('/buildings');

			if ($result['succeeded'] === false) throw new \Exception($result['message']);

			$buildings = $result['data'];

			$this->buildings = [];

			if (!$this->userIsExists) {
				foreach ($buildings as $b) {
					$imageURL = $this->uploadBuildingsImage($b['image']);

					$building = Building::create([
						'user_id' => $this->user->id,
						'server_id' => $b['id'],
						'name' => $b['name'],
						'type' => $b['type'],
						'area' => $b['area'],
						'image' => $imageURL
					]);
					$this->buildings[$b['id']] = $building->id;
				}
			} else {
				$existedBuildings = Building::where('user_id', $this->user->id)->get(['id', 'server_id', 'name', 'type', 'area'])->toArray();

				$list = $this->compareLists($existedBuildings, $buildings, 'server_id', 'id', ['name', 'type', 'area']);

				/* Update buildings */
				$currentBuildings = array_merge($existedBuildings, $list['CREATE']);
				$deletedBuildings = collect($list['DELETE'])->pluck('id')->toArray();

				for ($i = 0; $i < count($currentBuildings); $i++) {
					$b = $currentBuildings[$i];
					$index = (isset($b['server_id'])) ? 'server_id' : 'id';
					if (!in_array($b['id'], $deletedBuildings)) $this->buildings[$b[$index]] = $b['id'];
				}

				/* Delete buildings */
				if (count($deletedBuildings) > 0) $this->deleteBuildings($deletedBuildings);

				/* Insert new buildings */
				if (count($list['CREATE']) > 0) {
					$now = now();

					Building::insert(
						collect($list['CREATE'])
							->map(function ($b) use ($now) {
								$imageURL = $this->uploadBuildingsImage($b['image']);

								return [
									'user_id' => $this->user->id,
									'server_id' => $b['id'],
									'name' => $b['name'],
									'type' => $b['type'],
									'area' => $b['area'],
									'image' => $imageURL,
									'created_at' => $now,
									'updated_at' => $now
								];
							})
							->toArray()
					);
				}

				if (count($list['UPDATE']) > 0) {
					$now = now();

					foreach ($list['UPDATE'] as $row) {
						$building = Building::firstWhere('server_id', $row['id']);

						if ($building) {
							$imageURL = $this->uploadBuildingsImage($row['image']);

							$building->name = $row['name'];
							$building->type = $row['type'];
							$building->area = $row['area'];
							$building->image = $imageURL;

							$building->save();
						}
					}
				}
			}
		} catch (\Exception $e) {
			Log::error("BUILDING: " . $e);
		}
	}

	private function fetchScenes()
	{
		try {
			/* Insert */
			$result = $this->apiRequest('/scenes');

			if ($result['succeeded'] === false) throw new \Exception($result['message']);

			$scenes = $result['data'];

			if (!$this->userIsExists) {
				foreach ($scenes as $s) {
					$imageURL = $this->uploadScenesImage($s['image']);

					Scene::create([
						'server_id' => $s['id'],
						'building_id' => $this->buildings[$s['building_id']],
						'master_id' => $s['master_id'],
						'scene_id' => $s['scene_id'],
						'name' => $s['name'],
						'image' => $imageURL
					]);
				}
			} else {
				$existedScenes = Scene::join('buildings', 'scenes.building_id', 'buildings.id')
					->where([
						['buildings.user_id', $this->user->id],
						['scenes.deleted_at', null]
					])
					->select('scenes.id', 'scenes.server_id', 'scenes.building_id', 'scenes.master_id', 'scenes.scene_id', 'scenes.name')
					->get()
					->toArray();

				$list = $this->compareLists($existedScenes, $scenes, 'server_id', 'id', ['master_id', 'scene_id', 'name']);

				/* Delete scenes */
				if (count($list['DELETE']) > 0) {
					$deletedScenes = collect($list['DELETE'])->pluck('id')->toArray();
					$this->deleteScenes($deletedScenes);
				}

				/* Insert new scenes */
				if (count($list['CREATE']) > 0) {
					$now = now();

					Scene::insert(
						collect($list['CREATE'])
							->map(function ($s) use ($now) {
								$imageURL = $this->uploadScenesImage($s['image']);

								return [
									'server_id' => $s['id'],
									'building_id' => $this->buildings[$s['building_id']],
									'master_id' => $s['master_id'],
									'scene_id' => $s['scene_id'],
									'name' => $s['name'],
									'image' => $imageURL,
									'created_at' => $now,
									'updated_at' => $now
								];
							})
							->toArray()
					);
				}

				if (count($list['UPDATE']) > 0) {
					$now = now();

					foreach ($list['UPDATE'] as $row) {
						$scene = Scene::firstWhere('server_id', $row['id']);

						if ($scene) {
							$imageURL = $this->uploadScenesImage($row['image']);

							$scene->name = $row['name'];
							$scene->scene_id = $row['scene_id'];
							$scene->master_id = $row['master_id'];
							$scene->image = $imageURL;

							$scene->save();
						}
					}
				}
			}
		} catch (\Exception $e) {
			Log::error("SCENE: " . $e);
		}
	}

	private function fetchServices()
	{
		try {
			/* Insert */
			$result = $this->apiRequest('/services');

			if ($result['succeeded'] === false) throw new \Exception($result['message']);

			$services = $result['data'];
			if (!$this->userIsExists) {
				foreach ($services as $service) {
					$location = $this->roundLocation($service['location']);
					Service::create([
						'server_id' => $service['id'],
						'building_id' => $this->buildings[$service['building_id']],
						'location' => $location,
						'service_id' => $service['service_id'],
						'group_id' => $service['group_id'],
						'master_id' => $service['master_id'],
						'value' => $service['value'],
						'type' => $service['type']
					]);
				}
			} else {
				$existedServices = Service::join('buildings', 'services.building_id', 'buildings.id')
					->where([
						['buildings.user_id', $this->user->id],
						['services.deleted_at', null]
					])
					->select('services.id', 'services.server_id', 'services.building_id', 'services.location', 'services.service_id', 'services.group_id', 'services.master_id')
					->get()
					->toArray();

				$list = $this->compareLists($existedServices, $services, 'server_id', 'id', ['location']);

				/* Delete services */
				if (count($list['DELETE']) > 0) {
					$deletedServices = collect($list['DELETE'])->pluck('id')->toArray();
					$this->deleteServices($deletedServices);
				}

				/* Insert new scenes */
				if (count($list['CREATE']) > 0) {
					$now = now();

					$services = collect($list['CREATE'])
						->map(function ($service) use ($now) {
							$location = $this->roundLocation($service['location']);
							return [
								'server_id' => $service['id'],
								'building_id' => $this->buildings[$service['building_id']],
								'location' => json_encode($location),
								'service_id' => $service['service_id'],
								'group_id' => $service['group_id'],
								'master_id' => $service['master_id'],
								'value' => $service['value'],
								'type' => $service['type'],
								'created_at' => $now,
								'updated_at' => $now
							];
						})
						->toArray();

					Service::insert($services);
				}

				if (count($list['UPDATE']) > 0) {
					$now = now();

					foreach ($list['UPDATE'] as $row) {
						$service = Service::firstWhere('server_id', $row['id']);

						if ($service) {
							$serviceLocation = (gettype($service['location']) === 'array') ? $this->roundLocation($service['location']) : $this->roundLocation(json_decode($service['location']));
							$rowLocation = $this->roundLocation($row['location']);

							if ($serviceLocation[0] !== $rowLocation[0] || $serviceLocation[1] !== $rowLocation[1]) {
								$service->location = $rowLocation;
								$service->save();
							}
						}
					}
				}
			}
		} catch (\Exception $e) {
			Log::error("SERVICE: " . $e);
		}
	}

	private function fetchThermostats()
	{
		try {
			/* Insert */
			$result = $this->apiRequest('/thermostats');

			if ($result['succeeded'] === false) throw new \Exception($result['message']);

			$thermostats = $result['data'];

			if (!$this->userIsExists) {
				foreach ($thermostats as $th) {
					Thermostat::create([
						'server_id' => $th['id'],
						'building_id' => $this->buildings[$th['building_id']],
						'location' => $th['location'],
						'service_id' => $th['service_id'],
						'group_id' => $th['group_id'],
						'level' => $th['level'],
						'off' => (bool) $th['off'],
						'value' => $this->thermostatValueGetter((int) $th['value']),
						'to_value' => $this->thermostatValueGetter((int) $th['value'])
					]);
				}
			} else {
				$existedThermostats = Thermostat::join('buildings', 'thermostats.building_id', 'buildings.id')
					->where([
						['buildings.user_id', $this->user->id],
						['thermostats.deleted_at', null]
					])
					->select('thermostats.id', 'thermostats.server_id', 'thermostats.building_id', 'thermostats.location', 'thermostats.service_id', 'thermostats.group_id', 'thermostats.level', 'thermostats.off', 'thermostats.value', 'thermostats.to_value')
					->get()
					->toArray();

				$list = $this->compareLists($existedThermostats, $thermostats, 'server_id', 'id', ['location']);

				/* Delete services */
				if (count($list['DELETE']) > 0) {
					$deletedServices = collect($list['DELETE'])->pluck('id')->toArray();
					$this->deleteThermostats($deletedServices);
				}

				/* Insert new scenes */
				if (count($list['CREATE']) > 0) {
					$now = now();

					$thermostats = collect($list['CREATE'])
						->map(function ($th) use ($now) {
							return [
								'server_id' => $th['id'],
								'building_id' => $this->buildings[$th['building_id']],
								'location' => json_encode($th['location']),
								'service_id' => $th['service_id'],
								'group_id' => $th['group_id'],
								'level' => $th['level'],
								'off' => (bool) $th['off'],
								'value' => $this->thermostatValueGetter((int) $th['value']),
								'to_value' => $this->thermostatValueGetter((int) $th['value']),
								'created_at' => $now,
								'updated_at' => $now
							];
						})
						->toArray();

					Thermostat::insert($thermostats);
				}

				if (count($list['UPDATE']) > 0) {
					$now = now();

					foreach ($list['UPDATE'] as $row) {
						$thermostat = Thermostat::firstWhere('server_id', $row['id']);

						if ($thermostat) {
							$serviceLocation = (gettype($thermostat['location']) === 'array') ? $this->roundLocation($thermostat['location']) : $this->roundLocation(json_decode($thermostat['location']));
							$rowLocation = $this->roundLocation($row['location']);

							if ($serviceLocation[0] !== $rowLocation[0] || $serviceLocation[1] !== $rowLocation[1]) {
								$thermostat->location = $rowLocation;
								$thermostat->save();
							}
						}
					}
				}
			}
		} catch (\Exception $e) {
			Log::error("SERVICE: " . $e);
		}
	}

	private function compareLists(array $a, array $b, string $aKey, string $bKey, $updates)
	{
		$aLength = count($a);
		$bLength = count($b);
		$length = $aLength > $bLength ? $aLength : $bLength;

		$status = [
			'UPDATE' => [],
			'DELETE' => [],
			'CREATE' => []
		];
		for ($i = 0; $i < $length; $i++) {
			/* UPDATE */
			if (isset($a[$i]) && isset($b[$i])) {
				$aIdex = $a[$i];
				$aValue = $aIdex[$aKey];
				$bIdex = $b[$i];
				$bValue = $bIdex[$bKey];

				if ($aValue === $bValue) {
					if ($this->updateIsExists($aIdex, $bIdex, $updates)) $status['UPDATE'][] = $bIdex;
				} else {
					$aInB = find_in($b, function ($value) use ($bKey, $aValue) {
						return $value[$bKey] === $aValue;
					});

					if ($aInB) {
						if ($this->updateIsExists($aIdex, $aInB, $updates)) $status['UPDATE'][] = $aInB;
					} else $status['DELETE'][] = $aIdex;

					$bInA = find_in($a, function ($value) use ($aKey, $bValue) {
						return $value[$aKey] === $bValue;
					});

					if (!$bInA) $status['CREATE'][] = $bIdex;
				}
			}

			/* CREATE */ else if (!isset($a[$i]) && isset($b[$i])) $status['CREATE'][] = $b[$i];

			/* DELETE */
			else if (isset($a[$i]) && !isset($b[$i])) $status['DELETE'][] = $a[$i];
		}

		return $status;
	}

	private function updateIsExists($col1, $col2, $updates)
	{
		for ($i = 0; $i < count($updates); $i++) {
			$updateName = $updates[$i];

			if (json_encode($col1[$updateName]) !== json_encode($col2[$updateName])) return true;
		}
		return false;
	}

	/* UPLOAD IMAGE */
	private function uploadBuildingsImage(string $url)
	{
		$name = getNameFromURL($url);

		dispatch(new BuildingImagesJob($url, $name))->onQueue('images');

		return Storage::url('buildings/' . $name);
	}

	private function uploadScenesImage(string $url)
	{
		$name = getNameFromURL($url);

		dispatch(new SceneImagesJob($url, $name))->onQueue('images');

		return Storage::url('scenes/' . $name);
	}

	/* DELETE */
	private function deleteBuildings(array $id)
	{
		try {
			Building::whereIn('id', $id)->delete();
		} catch (\Exception $e) {
			Log::error("Can't DELETE BUILDING for USER " . $this->user->id . ". " . $e);
		}
	}

	private function deleteScenes(array $scenesID)
	{
		try {
			Scene::join('buildings', 'scenes.building_id', 'buildings.id')
				->where([
					['buildings.user_id', $this->user->id],
					['services.deleted_at', null]
				])
				->whereIn('scenes.id', $scenesID)
				->delete();
		} catch (\Exception $e) {
			Log::error("Can't DELETE SCENES for USER " . $this->user->id . ". " . $e);
		}
	}

	private function deleteServices(array $servicesID)
	{
		try {
			Service::join('buildings', 'services.building_id', 'buildings.id')
				->where([
					['buildings.user_id', $this->user->id],
					['services.deleted_at', null]
				])
				->whereIn('services.id', $servicesID)
				->delete();
		} catch (\Exception $e) {
			Log::error("Can't DELETE SERVICES for USER " . $this->user->id . ". " . $e);
		}
	}

	private function deleteThermostats(array $thermostatsID)
	{
		try {
			Thermostat::join('buildings', 'thermostats.building_id', 'buildings.id')
				->where([
					['buildings.user_id', $this->user->id],
					['thermostats.deleted_at', null]
				])
				->whereIn('thermostats.id', $thermostatsID)
				->delete();
		} catch (\Exception $e) {
			Log::error("Can't DELETE THERMOSTATS for USER " . $this->user->id . ". " . $e);
		}
	}

	private function apiRequest(string $path)
	{
		$client = new \GuzzleHttp\Client();
		$response = $client->request(
			'GET',
			apiRoute($path),
			[
				'headers' => ['Authorization' => "Bearer {$this->token}"]
			]
		);

		return json_decode($response->getBody()->getContents(), true);
	}

	/* PRIVATE METHODS */
	private function roundLocation(array $location)
	{
		try {
			$location[0] = (double) toFixed($location[0], 1);
			$location[1] = (double) toFixed($location[1], 1);

			return $location;
		} catch (\Exception $e) {
			return $location;
		}
	}

	private function thermostatValueGetter(int $value)
	{
		if ($value < 150) return 150;
		if ($value > 350) return 350;

		return $value;
	}
}
