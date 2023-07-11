<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\Image;
use App\Models\Scene;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::factory()->count(1)->create();

        Building::factory()->count(6)->create();

        Service::factory()->count(30)->create();

        Scene::factory()->count(3)->create();
    }
}
