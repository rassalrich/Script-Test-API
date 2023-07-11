<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateJobRequest;
use App\Models\Automatic;
use App\Models\Mood;
use App\Models\Scene;
use Illuminate\Support\Facades\DB;

class JobController extends Controller
{
	public function index()
	{
		$user = request()->user();
		$jobs = Automatic::where('user_id', $user->id)->get(['job_id', 'job_type', 'every', 'time', 'date']);

		return resJson($jobs->toArray());
	}

	public function store(CreateJobRequest $request, string $type, int $jobId)
	{
		DB::beginTransaction();
		$uType = strtolower($type) === 'mood' ? 'MOOD' : 'SCENE';

		/* Exists mood/scene */
		$instanceOfType = $uType === 'MOOD' ? Mood::find($jobId) : Scene::find($jobId);
		if (!$instanceOfType) return resJson([], $uType . ' was not found.', false);

		try {
			$user = request()->user();

			/* Create project */
			$job = Automatic::create([
				'user_id' => $user->id,
				'job_id' => $jobId,
				'job_type' => $uType,
				'time' => $request->time,
				'every' => $request->has('every') ? $request->every : null,
				'date' => $request->has('date') ? $request->date : null
			]);

			DB::commit();
			return resJson($job);
		} catch (\Exception $e) {
			DB::rollBack();
			return resJson([], 'Something went wrong.', false);
		}
	}

	public function destroy(int $jobId)
	{
		try {
			$user = request()->user();
			Automatic::where([
				'id' => $jobId,
				'user_id' => $user->id
			])->delete();
		} catch (\Exception $e) {
			//
		}

		return resJson([], 'Job successfully deleted.');
	}
}
