<?php

namespace Tests\Feature\Api;

use PHPUnit\Framework\Attributes\Test;

class DistrictApiTest extends ApiTestCase
{
    #[Test]
    public function list_districts_is_paginated(): void
    {
        $this->authGet('/api/districts')
            ->assertOk()
            ->assertJsonPath('meta.total', 25)
            ->assertJsonCount(15, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'province_id', 'name', 'province']],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'last_page', 'total'],
            ]);
    }

    #[Test]
    public function filter_districts_by_province(): void
    {
        $response = $this->authGet('/api/districts?province=WP&per_page=50');

        $response->assertOk()
            ->assertJsonPath('meta.total', 3);

        $this->assertSame(
            ['WP', 'WP', 'WP'],
            $response->json('data.*.province.code'),
            'Every district must belong to province WP when filtered by province=WP.'
        );
    }

    #[Test]
    public function show_accepts_district_name_as_route_key(): void
    {
        $this->authGet('/api/districts/Colombo')
            ->assertOk()
            ->assertJsonPath('data.id', 1)
            ->assertJsonPath('data.name', 'Colombo')
            ->assertJsonPath('data.province.code', 'WP');
    }

    #[Test]
    public function show_returns_404_for_missing_district(): void
    {
        $this->authGet('/api/districts/does-not-exist')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');
    }

    #[Test]
    public function grid_substations_nested_route_is_scoped_to_the_district(): void
    {
        $response = $this->authGet('/api/districts/1/grid-substations?per_page=50');

        $response->assertOk()
            ->assertJsonPath('meta.total', 2);

        $this->assertSame(
            [1, 1],
            $response->json('data.*.district_id'),
            'Every substation must reference the Colombo district.'
        );
    }

    #[Test]
    public function unknown_province_filter_returns_empty_result(): void
    {
        $this->authGet('/api/districts?province=NOPE')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }
}
