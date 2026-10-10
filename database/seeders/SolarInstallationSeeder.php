<?php

namespace Database\Seeders;

use App\Models\GridSubstation;
use App\Models\SolarInstallation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SolarInstallationSeeder extends Seeder
{
    private const INSTALLATIONS_PER_SUBSTATION = 9;

    private const CAPACITIES_KW = [3.2, 4.0, 5.0, 5.4, 6.0, 7.2, 8.0, 9.0, 10.0];

    private const STATUSES = [
        'active',
        'active',
        'active',
        'active',
        'active',
        'active',
        'active',
        'maintenance',
        'suspended',
    ];

    public function run(): void
    {
        $substations = GridSubstation::query()->with('district.province')->orderBy('id')->get();

        if ($substations->isEmpty()) {
            $this->command?->warn('No grid substations found; run GridSubstationSeeder first.');

            return;
        }

        $index = 0;
        $installedStart = Carbon::create(2023, 1, 15, 0, 0, 0, 'UTC');

        foreach ($substations as $substation) {
            for ($n = 1; $n <= self::INSTALLATIONS_PER_SUBSTATION; $n++) {
                $index++;

                SolarInstallation::firstOrCreate(
                    ['meter_id' => sprintf('MTR-%06d', $index)],
                    [
                        'grid_substation_id' => $substation->id,
                        'capacity_kw' => self::CAPACITIES_KW[($n - 1) % count(self::CAPACITIES_KW)],
                        'address' => sprintf(
                            '%s District, %s Province, Sri Lanka',
                            $substation->district->name,
                            $substation->district->province->name
                        ),
                        'installed_at' => $installedStart->copy()->addDays($index % 730)->format('Y-m-d'),
                        'status' => self::STATUSES[$index % count(self::STATUSES)],
                    ]
                );
            }
        }

        $this->command?->info(sprintf('Seeded %d solar installations across %d substations.', $index, $substations->count()));
    }
}
