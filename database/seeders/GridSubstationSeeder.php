<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\GridSubstation;
use Illuminate\Database\Seeder;

class GridSubstationSeeder extends Seeder
{
    public function run(): void
    {
        $substations = [
            ['code' => 'SUB-COL-01', 'district' => 'Colombo', 'name' => 'Colombo Fort Substation', 'location' => 'Colombo Fort'],
            ['code' => 'SUB-COL-02', 'district' => 'Colombo', 'name' => 'Pettah Substation', 'location' => 'Pettah'],
            ['code' => 'SUB-GAM-01', 'district' => 'Gampaha', 'name' => 'Negombo Substation', 'location' => 'Negombo'],
            ['code' => 'SUB-KAL-01', 'district' => 'Kalutara', 'name' => 'Kalutara Substation', 'location' => 'Kalutara'],
            ['code' => 'SUB-KAN-01', 'district' => 'Kandy', 'name' => 'Dumbara Substation', 'location' => 'Kandy'],
            ['code' => 'SUB-MAT-01', 'district' => 'Matale', 'name' => 'Matale Town Substation', 'location' => 'Matale'],
            ['code' => 'SUB-NUW-01', 'district' => 'Nuwara Eliya', 'name' => 'Nuwara Eliya Substation', 'location' => 'Nuwara Eliya'],
            ['code' => 'SUB-GAL-01', 'district' => 'Galle', 'name' => 'Galle Fort Substation', 'location' => 'Galle Fort'],
            ['code' => 'SUB-MTR-01', 'district' => 'Matara', 'name' => 'Matara Substation', 'location' => 'Matara'],
            ['code' => 'SUB-HAM-01', 'district' => 'Hambantota', 'name' => 'Hambantota Substation', 'location' => 'Hambantota'],
            ['code' => 'SUB-JAF-01', 'district' => 'Jaffna', 'name' => 'Jaffna Town Substation', 'location' => 'Jaffna'],
            ['code' => 'SUB-VAV-01', 'district' => 'Vavuniya', 'name' => 'Vavuniya Substation', 'location' => 'Vavuniya'],
            ['code' => 'SUB-MAN-01', 'district' => 'Mannar', 'name' => 'Mannar Island Substation', 'location' => 'Mannar'],
            ['code' => 'SUB-TRI-01', 'district' => 'Trincomalee', 'name' => 'Trincomalee Harbour Substation', 'location' => 'Trincomalee'],
            ['code' => 'SUB-BAT-01', 'district' => 'Batticaloa', 'name' => 'Batticaloa Substation', 'location' => 'Batticaloa'],
            ['code' => 'SUB-AMP-01', 'district' => 'Ampara', 'name' => 'Ampara Substation', 'location' => 'Ampara'],
            ['code' => 'SUB-KUR-01', 'district' => 'Kurunegala', 'name' => 'Kurunegala Rock Substation', 'location' => 'Kurunegala'],
            ['code' => 'SUB-PUT-01', 'district' => 'Puttalam', 'name' => 'Puttalam Substation', 'location' => 'Puttalam'],
            ['code' => 'SUB-ANU-01', 'district' => 'Anuradhapura', 'name' => 'Anuradhapura New Town Substation', 'location' => 'Anuradhapura'],
            ['code' => 'SUB-POL-01', 'district' => 'Polonnaruwa', 'name' => 'Polonnaruwa Substation', 'location' => 'Polonnaruwa'],
            ['code' => 'SUB-BAD-01', 'district' => 'Badulla', 'name' => 'Badulla Town Substation', 'location' => 'Badulla'],
            ['code' => 'SUB-MON-01', 'district' => 'Monaragala', 'name' => 'Monaragala Substation', 'location' => 'Monaragala'],
            ['code' => 'SUB-RAT-01', 'district' => 'Ratnapura', 'name' => 'Ratnapura Gem City Substation', 'location' => 'Ratnapura'],
            ['code' => 'SUB-KEG-01', 'district' => 'Kegalle', 'name' => 'Kegalle Substation', 'location' => 'Kegalle'],
        ];

        foreach ($substations as $substation) {
            $district = District::where('name', $substation['district'])->firstOrFail();

            GridSubstation::firstOrCreate(
                ['code' => $substation['code']],
                [
                    'district_id' => $district->id,
                    'name' => $substation['name'],
                    'location' => $substation['location'],
                ]
            );
        }
    }
}
