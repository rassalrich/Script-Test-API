<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Jobs\ScenePublishJob;
use App\Models\Scene;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class SceneController extends Controller
{
	public function index(): \Illuminate\Http\JsonResponse
	{
		$user = request()->user();
		$scenes = Scene::join('buildings', 'scenes.building_id', 'buildings.id')
			->where('buildings.user_id', $user->id)
			->select('scenes.id', 'scenes.building_id', 'scenes.master_id', 'scenes.scene_id', 'scenes.name', 'scenes.image')
			->get();

		return resJson($scenes);
	}

	public function publish(int $sceneId)
	{
		try {
			$scene = Scene::find($sceneId);
			if (!$scene) return resJson(false, "Scene does not exists.");

			dispatch(new ScenePublishJob($scene))->onQueue('mqtt');
		} catch (\Exception $e) {
			Log::error("[Scene] [Publish]: " . $e->getMessage());
		} finally {
			return resJson(true);
		}
	}
}
