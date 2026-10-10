<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\GenerationReading;
use App\Models\GridSubstation;
use App\Models\Province;
use App\Models\SolarInstallation;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;

class SeedDataVerificationTest extends TestCase
{
    public function test_provinces_are_exactly_the_nine_real_sri_lankan_provinces(): void
    {
        $expected = [
            'Western',
            'Central',
            'Southern',
            'Northern',
            'Eastern',
            'North Western',
            'North Central',
            'Uva',
            'Sabaragamuwa',
        ];

        $this->assertSame(9, Province::count());

        $actual = Province::pluck('name')->sort()->values()->all();
        sort($expected);
        $this->assertSame($expected, $actual);
    }

    public function test_districts_are_twenty_five_and_correctly_assigned(): void
    {
        $this->assertSame(25, District::count());
        $this->assertSame(0, District::whereDoesntHave('province')->count());

        $assignments = [
            'Colombo' => 'Western',
            'Kalutara' => 'Western',
            'Kandy' => 'Central',
            'Nuwara Eliya' => 'Central',
            'Galle' => 'Southern',
            'Jaffna' => 'Northern',
            'Mullaitivu' => 'Northern',
            'Batticaloa' => 'Eastern',
            'Kurunegala' => 'North Western',
            'Anuradhapura' => 'North Central',
            'Badulla' => 'Uva',
            'Ratnapura' => 'Sabaragamuwa',
            'Kegalle' => 'Sabaragamuwa',
        ];

        foreach ($assignments as $districtName => $provinceName) {
            $district = District::where('name', $districtName)->first();
            $this->assertNotNull($district, "Missing district: {$districtName}");
            $this->assertSame($provinceName, $district->province->name);
        }

        $this->assertSame(5, Province::where('code', 'NP')->first()->districts()->count());
    }

    public function test_substations_are_complete_and_linked_to_districts(): void
    {
        $this->assertGreaterThanOrEqual(20, GridSubstation::count());
        $this->assertSame(24, GridSubstation::count());
        $this->assertSame(0, GridSubstation::whereDoesntHave('district')->count());
        $this->assertSame(GridSubstation::count(), GridSubstation::distinct('code')->count());
    }

    public function test_solar_installations_are_complete_and_linked(): void
    {
        $this->assertGreaterThanOrEqual(200, SolarInstallation::count());
        $this->assertSame(0, SolarInstallation::whereDoesntHave('gridSubstation')->count());
        $this->assertSame(SolarInstallation::count(), SolarInstallation::distinct('meter_id')->count());
        $this->assertSame(0, SolarInstallation::where('capacity_kw', '<=', 0)->count());
        $this->assertSame(0, SolarInstallation::whereNotIn('status', ['active', 'maintenance', 'suspended'])->count());
    }

    public function test_generation_readings_are_complete_and_consistent(): void
    {
        $installations = SolarInstallation::count();
        $expectedPerInstallation = 7 * 24 * 4; // 7 days, quarter-hourly
        $expectedTotal = $installations * $expectedPerInstallation;

        $this->assertGreaterThanOrEqual(134400, $expectedTotal);
        $this->assertSame($expectedTotal, GenerationReading::count());

        $this->assertSame(0, GenerationReading::whereDoesntHave('solarInstallation')->count());

        $unique = DB::table('generation_readings')
            ->distinct()
            ->count(['solar_installation_id', 'recorded_at']);
        $this->assertSame(GenerationReading::count(), $unique);

        $perDay = DB::table('generation_readings')
            ->selectRaw('DATE(recorded_at) AS day, COUNT(*) AS count')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $this->assertCount(7, $perDay);
        foreach ($perDay as $row) {
            $this->assertSame($installations * $expectedPerInstallation / 7, (int) $row->count, "Day {$row->day}");
        }
    }

    public function test_readings_are_physically_plausible(): void
    {
        $overCapacity = DB::table('generation_readings as r')
            ->join('solar_installations as i', 'i.id', '=', 'r.solar_installation_id')
            ->whereRaw('r.power_kw > i.capacity_kw')
            ->count();
        $this->assertSame(0, $overCapacity);

        $this->assertSame(0, DB::table('generation_readings')->where('power_kw', '<', 0)->count());

        $nighttimePower = DB::table('generation_readings')
            ->where('power_kw', '>', 0)
            ->whereRaw('((HOUR(recorded_at) * 60) + MINUTE(recorded_at) + 330) % 1440 < 360')
            ->orWhere('power_kw', '>', 0)
            ->whereRaw('((HOUR(recorded_at) * 60) + MINUTE(recorded_at) + 330) % 1440 > 1110')
            ->count();
        $this->assertSame(0, $nighttimePower);

        $this->assertGreaterThan(0, DB::table('generation_readings')->where('power_kw', '>', 0)->count());

        $voltages = DB::table('generation_readings')
            ->selectRaw('MIN(voltage) AS min_v, MAX(voltage) AS max_v')
            ->first();
        $this->assertGreaterThanOrEqual(215.0, (float) $voltages->min_v);
        $this->assertLessThanOrEqual(245.0, (float) $voltages->max_v);
    }

    public function test_cumulative_energy_is_monotonic_per_installation(): void
    {
        $sample = SolarInstallation::query()->limit(10)->pluck('id');

        foreach ($sample as $id) {
            $energy = DB::table('generation_readings')
                ->where('solar_installation_id', $id)
                ->orderBy('recorded_at')
                ->pluck('energy_kwh');

            $previous = null;
            foreach ($energy as $value) {
                if ($previous !== null) {
                    $this->assertGreaterThanOrEqual((float) $previous, (float) $value);
                }
                $previous = $value;
            }
            $this->assertNotNull($previous);
        }
    }

    public function test_relationships_resolve_across_the_chain(): void
    {
        $province = Province::where('code', 'WP')->first();
        $this->assertTrue($province->districts()->where('name', 'Colombo')->exists());

        $district = District::where('name', 'Colombo')->first();
        $this->assertTrue($district->gridSubstations()->exists());

        $substation = GridSubstation::where('code', 'SUB-COL-01')->first();
        $this->assertSame('Colombo', $substation->district->name);
        $this->assertTrue($substation->solarInstallations()->exists());

        $installation = SolarInstallation::query()->first();
        $this->assertInstanceOf(GridSubstation::class, $installation->gridSubstation);
        $this->assertTrue($installation->generationReadings()->exists());
        $this->assertNotNull($installation->latestReading);

        $latest = DB::table('generation_readings')
            ->where('solar_installation_id', $installation->id)
            ->orderByDesc('recorded_at')
            ->first();
        $this->assertSame($latest->id, $installation->latestReading->id);

        $reading = GenerationReading::query()->first();
        $this->assertSame($reading->solar_installation_id, $reading->solarInstallation->id);
    }

    public function test_users_table_is_empty_by_design(): void
    {
        $this->assertSame(0, User::count());
    }
}
