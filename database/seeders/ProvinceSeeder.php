<?php

namespace Database\Seeders;

use App\Models\Province;
use Illuminate\Database\Seeder;

class ProvinceSeeder extends Seeder
{
    public function run(): void
    {
        $provinces = [
            ['name' => 'Western', 'code' => 'WP'],
            ['name' => 'Central', 'code' => 'CP'],
            ['name' => 'Southern', 'code' => 'SP'],
            ['name' => 'Northern', 'code' => 'NP'],
            ['name' => 'Eastern', 'code' => 'EP'],
            ['name' => 'North Western', 'code' => 'NWP'],
            ['name' => 'North Central', 'code' => 'NCP'],
            ['name' => 'Uva', 'code' => 'UP'],
            ['name' => 'Sabaragamuwa', 'code' => 'SAB'],
        ];

        foreach ($provinces as $province) {
            Province::firstOrCreate(
                ['code' => $province['code']],
                ['name' => $province['name']]
            );
        }
    }
}
