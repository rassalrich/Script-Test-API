<?php

namespace App\Console\Commands;

use App\Models\Automatic;
use App\Models\Mood;
use App\Models\Scene;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use PhpMqtt\Client\MqttClient;
use Illuminate\Support\Facades\Log;

class ExecuteAutomaticJobsCommand extends Command
{
	protected $client;

	protected $projectId;

	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'execute:automatic-jobs';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Execute automatic_jobs that are scheduled to run now.';

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
			Log::error("[MQTT] [AutomaticJobs]: " . $e->getMessage());
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
			$now = Carbon::now();
			$time = $now->format('H:i') . ":00";
			[$date, $dayName] = explode(' ', $now->format('Y-m-d l'));

			$automaticJobs = Automatic::where("time", $time)
				->where("date", $date)
				->orWhere("every", $dayName)
				->get(['user_id', 'job_id', 'job_type', 'every', 'time', 'date']);

			foreach ($automaticJobs as $job) {
				$this->getProjectId($job->user_id);
				$this->dispatchJob($job);
			}

			$this->client->disconnect();
		} catch (\Exception $e) {
			Log::error("[Execute] [AutomaticJobs]: " . $e->getMessage());
		}
	}

	public function getProjectId(int $user_id)
	{
		try {
			$this->projectId = User::find($user_id)->project_id;
		} catch (\Exception $e) {
			Log::error("[Scene] [AutomaticJobs]: " . $e->getMessage());
		}
	}

	public function dispatchJob(Automatic $job)
	{
		if ($job->job_type === 'SCENE') {
			$scene = Scene::find($job->job_id);
			if ($scene) $this->dispatchScene($scene);
		} else {
			$mood = Mood::find($job->job_id);
			if ($mood) $this->dispatchMood($mood);
		}
	}

	public function dispatchScene(Scene $scene)
	{
		try {
			$masterIds = explode(",", $scene->master_id);

			foreach ($masterIds as $masterId) {
				$this->client->publish(
					`{$this->projectId}/{$masterId}/Dali/In`,
					"{\"CMD\r\n\":\"GO_TO_SCENE_" . $scene->scene_id . "\r\n\",\"BROADCAST\r\n\":\"ALL\r\n\"}"
				);
			}
		} catch (\Exception $e) {
			Log::error("[Scene] [AutomaticJobs] [Scene]: " . $e->getMessage());
		}
	}

	public function dispatchMood(Mood $mood)
	{
		try {
			//
		} catch (\Exception $e) {
			Log::error("[Scene] [AutomaticJobs] [Mood]: " . $e->getMessage());
		}
	}
}
