<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
    	return [
    	    'building_id' => $this->faker->numberBetween(1, 6),
    	    'type' => $this->faker->randomElement(['light', 'curtain', 'relay']),
    	    'service_id' => $this->faker->numberBetween(1, 20),
    	    'master_id' => $this->faker->numberBetween(1, 20),
    	    'is_group' => $this->faker->boolean,
    	    'dim' => $this->faker->randomElement([0, 50, 100, 150, 200, 254]),
    	    'location' => json_encode($this->faker->latitude, $this->faker->latitude),
    	    'attributes' => json_encode($this->faker),
    	];
    }
}
