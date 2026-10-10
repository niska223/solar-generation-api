<?php

namespace Tests\Feature\Api;

use App\Models\District;
use App\Models\SolarInstallation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;

class GenerationSummaryApiTest extends ApiTestCase
{
    #[Test]
    public function summary_returns_a_complete_and_correct_structure(): void
    {
        $response = $this->authGet('/api/districts/1/generation-summary?date=2026-10-02');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'district' => ['id', 'name'],
                    'province' => ['id', 'code', 'name'],
                    'utc_day',
                    'day_start_utc',
                    'day_end_utc',
                    'as_of',
                    'installation_count',
                    'current_total_power_kw',
                    'today_generated_energy_kwh',
                ],
            ])
            ->assertJsonPath('data.district.name', 'Colombo')
            ->assertJsonPath('data.province.code', 'WP')
            ->assertJsonPath('data.utc_day', '2026-10-02')
            ->assertJsonPath('data.day_start_utc', '2026-10-02T00:00:00+00:00')
            ->assertJsonPath('data.day_end_utc', '2026-10-02T23:59:59+00:00')
            ->assertJsonPath('data.installation_count', 18);
    }

    #[Test]
    public function summary_current_power_and_today_energy_match_an_independent_recomputation(): void
    {
        $date = '2026-10-02';
        $response = $this->authGet('/api/districts/1/generation-summary?date='.$date)->assertOk();

        $expected = $this->recomputeExpected($date);

        $this->assertEqualsWithDelta(
            $expected['current_total_power_kw'],
            (float) $response->json('data.current_total_power_kw'),
            0.001,
            'current total power mismatch'
        );

        $this->assertEqualsWithDelta(
            $expected['today_generated_energy_kwh'],
            (float) $response->json('data.today_generated_energy_kwh'),
            0.001,
            'today generated energy mismatch'
        );
    }

    #[Test]
    public function default_summary_uses_today_utc_day_boundary(): void
    {
        $response = $this->authGet('/api/districts/1/generation-summary');

        $response->assertOk();
        $this->assertSame(Carbon::now('UTC')->toDateString(), $response->json('data.utc_day'));
        $this->assertStringStartsWith($response->json('data.utc_day'), $response->json('data.day_start_utc'));
    }

    #[Test]
    public function no_readings_in_the_day_yield_zero_energy_without_double_counting(): void
    {
        // Seeded data ends 2026-10-07; the 2026-10-09 day has no readings.
        $response = $this->authGet('/api/districts/1/generation-summary?date=2026-10-09');

        $response->assertOk()
            ->assertJsonPath('data.today_generated_energy_kwh', 0);

        $this->assertTrue($response->json('data.current_total_power_kw') >= 0);
    }

    #[Test]
    public function summary_returns_400_for_an_invalid_date(): void
    {
        $this->authGet('/api/districts/1/generation-summary?date=10-02-2026')
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'bad_request');
    }

    #[Test]
    public function summary_returns_404_for_an_unknown_district(): void
    {
        $this->authGet('/api/districts/999999/generation-summary')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');
    }

    /**
     * Independently recompute the summary using straightforward per-installation
     * queries so the endpoint's aggregate SQL is cross-checked.
     *
     * @return array{current_total_power_kw: float, today_generated_energy_kwh: float}
     */
    private function recomputeExpected(string $date): array
    {
        $dayStart = Carbon::parse($date, 'UTC')->startOfDay();
        $asOf = $dayStart->copy()->endOfDay();

        $substationIds = District::findOrFail(1)->gridSubstations()->pluck('grid_substations.id');
        $installationIds = SolarInstallation::whereIn('grid_substation_id', $substationIds)->pluck('id');

        $currentTotal = 0.0;
        $todayEnergy = 0.0;

        foreach ($installationIds as $id) {
            $latestPower = DB::table('generation_readings')
                ->where('solar_installation_id', $id)
                ->where('recorded_at', '<=', $asOf)
                ->orderByDesc('recorded_at')
                ->value('power_kw');

            $currentTotal += (float) ($latestPower ?? 0);

            $endEnergy = DB::table('generation_readings')
                ->where('solar_installation_id', $id)
                ->where('recorded_at', '>=', $dayStart)
                ->where('recorded_at', '<=', $asOf)
                ->orderByDesc('recorded_at')
                ->value('energy_kwh');

            $baseline = DB::table('generation_readings')
                ->where('solar_installation_id', $id)
                ->where('recorded_at', '<', $dayStart)
                ->orderByDesc('recorded_at')
                ->value('energy_kwh');

            $firstToday = DB::table('generation_readings')
                ->where('solar_installation_id', $id)
                ->where('recorded_at', '>=', $dayStart)
                ->where('recorded_at', '<=', $asOf)
                ->orderBy('recorded_at')
                ->value('energy_kwh');

            if ($endEnergy === null) {
                continue;
            }

            $baseline ??= $firstToday;

            if ($baseline === null) {
                continue;
            }

            $todayEnergy += max(0.0, (float) $endEnergy - (float) $baseline);
        }

        return [
            'current_total_power_kw' => round($currentTotal, 3),
            'today_generated_energy_kwh' => round($todayEnergy, 3),
        ];
    }
}
