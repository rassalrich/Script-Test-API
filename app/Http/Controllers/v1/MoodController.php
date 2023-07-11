<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateMoodRequest;
use App\Http\Requests\UpdateMoodRequest;
use App\Models\Building;
use App\Models\Mood;
use Illuminate\Support\Facades\DB;

class MoodController extends Controller
{
	public function index()
	{
		$user = request()->user();
		$moods = Mood::join('buildings', 'moods.building_id', 'buildings.id')
			->where('buildings.user_id', $user->id)
			->select(
				'moods.id',
				'moods.building_id',
				'moods.name',
				'moods.services'
			)
			->get();

		$moods = $moods->map(function ($mood) {
			$mood['services'] = json_decode($mood['services'], 1);
			return $mood;
		});

		return resJson($moods->toArray());
	}

	public function store(CreateMoodRequest $request, int $buildingId)
	{
		DB::beginTransaction();

		try {
			/* Building exists */
			$building = Building::find($buildingId);
			if (!$building) return resJson([], 'Building was not found.', false);

			/* Create project */
			$mood = Mood::create([
				'building_id' => $buildingId,
				'name' => $request->name,
				'services' => json_encode($request->services)
			]);

			DB::commit();

			$mood->services = $request->services;
			return resJson($mood);
		} catch (\Exception $e) {
			DB::rollBack();
			return resJson([], 'Something went wrong.', false);
		}
	}

	public function update(UpdateMoodRequest $request, int $moodId)
	{
		DB::beginTransaction();

		try {
			/* Mood exists */
			$mood = Mood::find($moodId);
			if (!$mood) return resJson([], 'Mood was not found.', false);

			$mood->name = $request->name;
			$mood->save();

			DB::commit();
			return resJson($mood);
		} catch (\Exception $e) {
			DB::rollBack();
			return resJson([], 'Something went wrong.', false);
		}
	}

	public function destroy(int $moodId)
	{
		try {
			Mood::where('id', $moodId)->delete();
		} catch (\Exception $e) {
			//
		}

		return resJson([], 'Mood successfully deleted.');
	}
}
