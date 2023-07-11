<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateSceneJobRequest;
use App\Models\Building;
use App\Models\Scene;
use App\Models\SceneJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SceneJobController extends Controller
{
	public function index()
	{
		$user = request()->user();

		$jobs = SceneJob::join('buildings', 'scene_jobs.building_id', 'buildings.id')
			->where('buildings.user_id', $user->id)
			->select(
				'scene_jobs.id',
				'scene_jobs.building_id',
				'scene_jobs.name',
				'scene_jobs.scene_id',
				'scene_jobs.master_id',
				'scene_jobs.curtains',
				'scene_jobs.run_at'
			)
			->get();

		return resJson($jobs);
	}

	public function store(CreateSceneJobRequest $request)
	{
		DB::beginTransaction();

		try {
			$scene = Scene::find($request->scene_id);

			/* Create Scene Job */
			$job = SceneJob::create([
				'building_id' => $scene->building_id,
				'master_id' => $scene->master_id,
				'scene_id' => $scene->scene_id,
				'curtains' => $request->curtains,
				'name' => $request->name,
				'run_at' => $request->run_at
			]);

			DB::commit();
			return resJson($job);
		} catch (\Exception $e) {
			DB::rollBack();

			Log::error($e->getMessage());
			return resJson([], 'Something went wrong.', false);
		}
	}

	public function destroy(int $jobId)
	{
		$userId = request()->user()->id;
		try {

			/* User Buildings */
			$userBuildings = Building::where('user_id', $userId)->get()->pluck('id')->toArray();

			/* Delete */
			SceneJob::whereIn('building_id', $userBuildings)
				->where([
					['deleted_at', null],
					['id', $jobId]
				])
				->delete();

			/* Get */
			$jobs = SceneJob::whereIn('building_id', $userBuildings)
				->get(['id', 'building_id', 'name', 'scene_id', 'master_id', 'curtains', 'run_at']);

			return resJson($jobs);
		} catch (\Exception $e) {
			Log::error("Can't delete scene job for user " . $userId . ". " . $e->getMessage());
			return resJson([], 'Something went wrong.', false);
		}
	}
}
