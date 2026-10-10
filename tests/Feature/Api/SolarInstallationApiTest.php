<?php

namespace Tests\Feature\Api;

use PHPUnit\Framework\Attributes\Test;

class SolarInstallationApiTest extends ApiTestCase
{
    #[Test]
    public function list_installations_is_paginated(): void
    {
        $this->getJson('/api/installations')
            ->assertOk()
            ->assertJsonPath('meta.total', 216)
            ->assertJsonCount(15, 'data')
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'grid_substation_id',
                    'meter_id',
                    'capacity_kw',
                    'status',
                    'readings_count',
                ]],
            ]);
    }

    #[Test]
    public function filter_installations_by_grid_substation(): void
    {
        $this->getJson('/api/grid-substations/SUB-COL-01/installations')
            ->assertOk()
            ->assertJsonPath('meta.total', 9);

        $response = $this->getJson('/api/installations?grid_substation=SUB-COL-01');
        $response->assertOk()->assertJsonPath('meta.total', 9);

        $this->assertSame(1, count(array_unique($response->json('data.*.grid_substation_id'))));
    }

    #[Test]
    public function filter_installations_by_district_and_province(): void
    {
        $byDistrict = $this->getJson('/api/installations?district=Colombo&per_page=100');
        $byDistrict->assertOk()->assertJsonPath('meta.total', 18);

        $this->assertSame(
            ['Colombo'],
            array_values(array_unique($byDistrict->json('data.*.grid_substation.district.name')))
        );

        $byProvince = $this->getJson('/api/installations?province=WP&per_page=100');
        $byProvince->assertOk()->assertJsonPath('meta.total', 36);

        $codes = array_unique($byProvince->json('data.*.grid_substation.district.province.code'));
        $this->assertSame(['WP'], array_values($codes));
    }

    #[Test]
    public function filter_installations_by_status(): void
    {
        $active = $this->getJson('/api/installations?status=active&per_page=100');
        $active->assertOk();

        $this->assertSame(
            ['active'],
            array_values(array_unique($active->json('data.*.status')))
        );
    }

    #[Test]
    public function invalid_status_filter_returns_400(): void
    {
        $this->getJson('/api/installations?status=decommissioned')
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'bad_request');
    }

    #[Test]
    public function show_returns_installation_with_latest_reading(): void
    {
        $response = $this->getJson('/api/installations/MTR-000001');

        $response->assertOk()
            ->assertJsonPath('data.id', 1)
            ->assertJsonPath('data.meter_id', 'MTR-000001');

        $this->assertNotNull($response->json('data.latest_reading.recorded_at'));
    }

    #[Test]
    public function show_returns_404_for_missing_installation(): void
    {
        $this->getJson('/api/installations/does-not-exist')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');
    }

    #[Test]
    public function latest_reading_endpoint_returns_the_most_recent_reading(): void
    {
        $response = $this->getJson('/api/installations/1/latest-reading');

        $response->assertOk()
            ->assertJsonPath('data.solar_installation_id', 1)
            ->assertJsonPath('data.recorded_at', '2026-10-07T23:45:00.000000Z');
    }
}
