<?php

namespace Tests\Feature\Api;

use PHPUnit\Framework\Attributes\Test;

class GridSubstationApiTest extends ApiTestCase
{
    #[Test]
    public function list_grid_substations_with_district_and_province_filters(): void
    {
        $byDistrict = $this->getJson('/api/grid-substations?district=Colombo&per_page=50');
        $byDistrict->assertOk()->assertJsonPath('meta.total', 2);

        $this->assertSame(
            ['Colombo', 'Colombo'],
            $byDistrict->json('data.*.district.name')
        );

        $byProvince = $this->getJson('/api/grid-substations?province=WP&per_page=50');
        $byProvince->assertOk()->assertJsonPath('meta.total', 4);

        $this->assertSame(
            ['WP', 'WP', 'WP', 'WP'],
            $byProvince->json('data.*.district.province.code')
        );
    }

    #[Test]
    public function show_accepts_substation_code_as_route_key(): void
    {
        $this->getJson('/api/grid-substations/SUB-COL-01')
            ->assertOk()
            ->assertJsonPath('data.code', 'SUB-COL-01')
            ->assertJsonPath('data.district.name', 'Colombo')
            ->assertJsonPath('data.installations_count', 9);
    }

    #[Test]
    public function show_returns_404_for_missing_substation(): void
    {
        $this->getJson('/api/grid-substations/SUB-XXX-99')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');
    }

    #[Test]
    public function installations_nested_route_is_scoped_to_the_substation(): void
    {
        $response = $this->getJson('/api/grid-substations/SUB-COL-01/installations?per_page=50');

        $response->assertOk()
            ->assertJsonPath('meta.total', 9);

        $substationIds = array_unique($response->json('data.*.grid_substation_id'));

        $this->assertSame([1], $substationIds);
    }

    #[Test]
    public function search_by_code_or_name(): void
    {
        $this->getJson('/api/grid-substations?q=Negombo')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'SUB-GAM-01');
    }
}
