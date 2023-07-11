<?php

namespace Database\Factories;

use App\Models\Scene;
use Illuminate\Database\Eloquent\Factories\Factory;

class SceneFactory extends Factory
{
    protected $model = Scene::class;

    public function definition(): array
    {
    	return [
    	    'name' => $this->faker->name,
    	    'scene_id' => $this->faker->numberBetween(0, 10),
    	    'master_id' => $this->faker->numberBetween(0, 2),
    	];
    }
}
