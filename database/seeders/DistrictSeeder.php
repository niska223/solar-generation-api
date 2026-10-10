<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Province;
use Illuminate\Database\Seeder;

class DistrictSeeder extends Seeder
{
    public function run(): void
    {
        $byProvince = [
            'WP' => ['Colombo', 'Gampaha', 'Kalutara'],
            'CP' => ['Kandy', 'Matale', 'Nuwara Eliya'],
            'SP' => ['Galle', 'Matara', 'Hambantota'],
            'NP' => ['Jaffna', 'Kilinochchi', 'Mannar', 'Vavuniya', 'Mullaitivu'],
            'EP' => ['Trincomalee', 'Batticaloa', 'Ampara'],
            'NWP' => ['Kurunegala', 'Puttalam'],
            'NCP' => ['Anuradhapura', 'Polonnaruwa'],
            'UP' => ['Badulla', 'Monaragala'],
            'SAB' => ['Ratnapura', 'Kegalle'],
        ];

        foreach ($byProvince as $code => $districts) {
            $province = Province::where('code', $code)->firstOrFail();

            foreach ($districts as $district) {
                District::firstOrCreate(
                    ['name' => $district],
                    ['province_id' => $province->id]
                );
            }
        }
    }
}
