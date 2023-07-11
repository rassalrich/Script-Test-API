<?php

namespace App\Console\Commands;

use App\Models\Building;
use App\Models\SceneJob;
use Carbon\Carbon;
use Illuminate\Console\Command;
use PhpMqtt\Client\MqttClient;
use Illuminate\Support\Facades\Log;

class ExecuteSceneJobsCommand extends Command
{
	protected $client;

	protected $projectId;

	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'execute:scene-jobs';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Execute scene_jobs that are scheduled to run now.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();

		try {
			$client_id = uniqid('mqtt_', true);

			$this->client = new MqttClient(env('MQTT_HOST'), env('MQTT_PORT', 1883), $client_id, MqttClient::MQTT_3_1, null);
			$this->client->connect(null, true);
		} catch (\Exception $e) {
			Log::error("[MQTT] [SceneJobs]: " . $e->getMessage());
		}
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{
		if (!$this->client) return;

		try {
			$time = Carbon::now()->format('H:i') . ":00";
			$sceneJobs = SceneJob::where("run_at", $time)->get(['building_id', 'scene_id', 'master_id', 'curtains', 'run_at']);

			foreach ($sceneJobs as $job) {
				$this->getProjectId($job->building_id);
				$this->scene($job->scene_id, explode(",", $job->master_id));
				$this->curtain(explode(";", $job->curtains));
			}

			$this->client->disconnect();
		} catch (\Exception $e) {
			Log::error("[Execute] [SceneJobs]: " . $e->getMessage());
		}
	}

	public function scene(int $scene_id, array $masterIds)
	{
		if (!$this->projectId) return;

		try {
			foreach ($masterIds as $masterId) {
				$this->client->publish(
					`{$this->projectId}/{$masterId}/Dali/In`,
					"{\"CMD\r\n\":\"GO_TO_SCENE_" . $scene_id . "\r\n\",\"BROADCAST\r\n\":\"ALL\r\n\"}"
				);
			}
		} catch (\Exception $e) {
			Log::error("[Scene] [SceneJobs]: " . $e->getMessage());
		}
	}

	public function curtain(array $curtains)
	{
		if (!$this->projectId) return;

		try {
			foreach ($curtains as $curtain) {
				[$curtainId, $value] = explode(',', $curtain);
				$this->client->publish(
					`{$this->projectId}/Curtain/In`,
					"{\"id\":\"" . $curtainId . "\",\"command\":\"" . $value . "\"}"
				);
			}
		} catch (\Exception $e) {
			Log::error("[Scene] [SceneJobs]: " . $e->getMessage());
		}
	}

	public function getProjectId(int $building_id)
	{
		try {
			$this->projectId = Building::where('buildings.id', $building_id)
				->join('users', 'buildings.user_id', 'users.id')
				->select('users.project_id')
				->first()
				->project_id;
		} catch (\Exception $e) {
			Log::error("[Scene] [SceneJobs]: " . $e->getMessage());
		}
	}
}
