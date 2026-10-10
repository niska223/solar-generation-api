<?php

namespace Database\Seeders;

use App\Models\GenerationReading;
use App\Models\SolarInstallation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GenerationReadingSeeder extends Seeder
{
    private const START_UTC = '2026-10-01 00:00:00';

    private const DAYS = 7;

    private const INTERVALS_PER_DAY = 96;

    private const CHUNK_SIZE = 500;

    private const SRI_LANKA_UTC_OFFSET_MINUTES = 330;

    private const SUNRISE_LOCAL_MINUTES = 360;

    private const SUNSET_LOCAL_MINUTES = 1110;

    public function run(): void
    {
        $start = Carbon::createFromFormat('Y-m-d H:i:s', self::START_UTC, 'UTC');
        $installations = SolarInstallation::query()->orderBy('id')->get(['id', 'meter_id', 'capacity_kw']);

        if ($installations->isEmpty()) {
            $this->command?->warn('No solar installations found; run SolarInstallationSeeder first.');

            return;
        }

        $readingsPerInstallation = self::DAYS * self::INTERVALS_PER_DAY;

        $this->command?->info(sprintf(
            'Generating %d quarter-hourly readings for %d installations...',
            $installations->count() * $readingsPerInstallation,
            $installations->count()
        ));

        $generated = 0;
        $skipped = 0;

        foreach ($installations as $installation) {
            if (GenerationReading::query()->where('solar_installation_id', $installation->id)->exists()) {
                $skipped++;

                continue;
            }

            $rows = [];
            $energy = $this->baselineEnergy($installation->id);
            $lastEnergy = $energy;

            mt_srand(($installation->id * 7919) + 104729);

            for ($i = 0; $i < $readingsPerInstallation; $i++) {
                $recordedAt = $start->copy()->addMinutes($i * 15);
                $power = $this->simulatePower($recordedAt, (float) $installation->capacity_kw);

                $energy += round($power * 0.25, 3);
                $energy = max($energy, $lastEnergy);
                $lastEnergy = $energy;

                $rows[] = [
                    'solar_installation_id' => $installation->id,
                    'recorded_at' => $recordedAt->format('Y-m-d H:i:s'),
                    'power_kw' => round($power, 3),
                    'energy_kwh' => $energy,
                    'voltage' => round(mt_rand(22400, 24500) / 100, 2),
                ];

                if (count($rows) >= self::CHUNK_SIZE) {
                    DB::table('generation_readings')->insert($rows);
                    $generated += count($rows);
                    $rows = [];
                }
            }

            if ($rows !== []) {
                DB::table('generation_readings')->insert($rows);
                $generated += count($rows);
            }
        }

        $this->command?->info(sprintf(
            'Done: %d readings generated, %d installations skipped (already seeded).',
            $generated,
            $skipped
        ));
    }

    private function baselineEnergy(int $installationId): float
    {
        return round((($installationId * 37) % 4000) + 12.5, 3);
    }

    private function simulatePower(Carbon $recordedAt, float $capacity): float
    {
        $utcMinutesOfDay = ($recordedAt->hour * 60) + $recordedAt->minute;
        $localMinutes = ($utcMinutesOfDay + self::SRI_LANKA_UTC_OFFSET_MINUTES) % 1440;

        if ($localMinutes < self::SUNRISE_LOCAL_MINUTES || $localMinutes > self::SUNSET_LOCAL_MINUTES) {
            return 0.0;
        }

        $fraction = sin(M_PI * ($localMinutes - self::SUNRISE_LOCAL_MINUTES)
            / (self::SUNSET_LOCAL_MINUTES - self::SUNRISE_LOCAL_MINUTES));

        $dayCloudFactor = round(0.82 + (mt_rand(0, 18) / 100), 2);
        $intervalCloudFactor = round(0.88 + (mt_rand(0, 12) / 100), 2);

        return min($capacity, $capacity * $fraction * $dayCloudFactor * $intervalCloudFactor);
    }
}
