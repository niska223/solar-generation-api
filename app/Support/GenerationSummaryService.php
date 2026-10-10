<?php

namespace App\Support;

use App\Models\District;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Computes a district generation summary.
 *
 * Definitions:
 *  - The reporting day is a UTC calendar day: [00:00:00, 23:59:59] UTC.
 *  - current_total_power_kw is the sum of each installation's most recent
 *    power reading at or before the "as of" instant (defaults to now).
 *  - today_generated_energy_kwh uses cumulative counter differences: for each
 *    installation, the latest cumulative energy today minus the cumulative
 *    energy recorded strictly before the day began. If an installation has no
 *    reading before the day, the first reading of the day is used as baseline
 *    so only energy truly generated today is counted. Each installation is
 *    counted once, so there is no double counting.
 *
 * A single aggregate query with correlated subqueries is issued, avoiding N+1
 * queries even for large districts.
 */
class GenerationSummaryService
{
    /**
     * @return array<string, mixed>
     */
    public function summarize(District $district, Carbon|string|null $asOf = null): array
    {
        $asOf = $asOf === null
            ? Carbon::now('UTC')
            : Carbon::parse($asOf)->utc();

        $dayStart = $asOf->copy()->startOfDay();
        $dayEnd = $asOf->copy()->endOfDay();

        $startBoundary = $dayStart->format('Y-m-d H:i:s');
        $endBoundary = $asOf->format('Y-m-d H:i:s');

        $rows = DB::table('solar_installations as i')
            ->join('grid_substations as s', 's.id', '=', 'i.grid_substation_id')
            ->where('s.district_id', $district->id)
            ->select('i.id')
            ->selectRaw(
                '(SELECT r.power_kw FROM generation_readings r
                    WHERE r.solar_installation_id = i.id AND r.recorded_at <= ?
                    ORDER BY r.recorded_at DESC, r.id DESC LIMIT 1) AS latest_power_kw',
                [$endBoundary]
            )
            ->selectRaw(
                '(SELECT r.energy_kwh FROM generation_readings r
                    WHERE r.solar_installation_id = i.id AND r.recorded_at >= ? AND r.recorded_at <= ?
                    ORDER BY r.recorded_at DESC, r.id DESC LIMIT 1) AS end_energy_kwh',
                [$startBoundary, $endBoundary]
            )
            ->selectRaw(
                '(SELECT r.energy_kwh FROM generation_readings r
                    WHERE r.solar_installation_id = i.id AND r.recorded_at < ?
                    ORDER BY r.recorded_at DESC, r.id DESC LIMIT 1) AS baseline_energy_kwh',
                [$startBoundary]
            )
            ->selectRaw(
                '(SELECT r.energy_kwh FROM generation_readings r
                    WHERE r.solar_installation_id = i.id AND r.recorded_at >= ? AND r.recorded_at <= ?
                    ORDER BY r.recorded_at ASC, r.id ASC LIMIT 1) AS first_energy_today_kwh',
                [$startBoundary, $endBoundary]
            )
            ->get();

        $currentTotalPower = 0.0;
        $todayEnergy = 0.0;

        foreach ($rows as $row) {
            $currentTotalPower += (float) ($row->latest_power_kw ?? 0);

            if ($row->end_energy_kwh === null) {
                continue;
            }

            $baseline = $row->baseline_energy_kwh ?? $row->first_energy_today_kwh;

            if ($baseline === null) {
                continue;
            }

            $todayEnergy += max(0.0, (float) $row->end_energy_kwh - (float) $baseline);
        }

        $province = $district->province;

        return [
            'district' => [
                'id' => $district->id,
                'name' => $district->name,
            ],
            'province' => $province === null ? null : [
                'id' => $province->id,
                'code' => $province->code,
                'name' => $province->name,
            ],
            'utc_day' => $dayStart->toDateString(),
            'day_start_utc' => $dayStart->toIso8601String(),
            'day_end_utc' => $dayEnd->toIso8601String(),
            'as_of' => $asOf->toIso8601String(),
            'installation_count' => $rows->count(),
            'current_total_power_kw' => round($currentTotalPower, 3),
            'today_generated_energy_kwh' => round($todayEnergy, 3),
        ];
    }
}
