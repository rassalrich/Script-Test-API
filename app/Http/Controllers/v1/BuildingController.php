<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Building;

class BuildingController extends Controller
{
	private $columns = ['id', 'name', 'type', 'area', 'image'];

	public function index(): \Illuminate\Http\JsonResponse
	{
		$user = request()->user();
		$buildings = Building::where('user_id', $user->id)->get($this->columns);

		return resJson($buildings->toArray());
	}
}
