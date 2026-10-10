<?php

namespace Tests\Feature\Api;

use PHPUnit\Framework\Attributes\Test;

class ProvinceApiTest extends ApiTestCase
{
    #[Test]
    public function list_provinces_is_paginated_with_total_and_links(): void
    {
        $response = $this->authGet('/api/provinces');

        $response->assertOk()
            ->assertJsonCount(9, 'data')
            ->assertJsonPath('meta.total', 9)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('links.prev', null)
            ->assertJsonPath('links.next', null)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'code', 'districts_count']],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'from', 'last_page', 'per_page', 'to', 'total'],
            ]);
    }

    #[Test]
    public function pagination_parameters_are_honoured(): void
    {
        $response = $this->authGet('/api/provinces?per_page=4&page=2');

        $response->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.last_page', 3);
    }

    #[Test]
    public function invalid_page_parameter_returns_400(): void
    {
        $this->authGet('/api/provinces?page=abc')
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'bad_request');

        $this->authGet('/api/provinces?per_page=99999')
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'bad_request');
    }

    #[Test]
    public function show_returns_a_province_by_id(): void
    {
        $this->authGet('/api/provinces/1')
            ->assertOk()
            ->assertJsonPath('data.id', 1)
            ->assertJsonPath('data.code', 'WP')
            ->assertJsonPath('data.name', 'Western');
    }

    #[Test]
    public function show_accepts_the_province_code_as_route_key(): void
    {
        $this->authGet('/api/provinces/WP')
            ->assertOk()
            ->assertJsonPath('data.id', 1)
            ->assertJsonPath('data.name', 'Western');
    }

    #[Test]
    public function show_returns_404_for_a_missing_province(): void
    {
        $this->authGet('/api/provinces/999999')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found')
            ->assertJsonStructure(['error' => ['status', 'code', 'message', 'details']]);
    }

    #[Test]
    public function districts_nested_route_is_scoped_to_the_province(): void
    {
        $response = $this->authGet('/api/provinces/WP/districts?per_page=50');

        $response->assertOk()
            ->assertJsonPath('meta.total', 3);

        $provinceIds = array_unique($response->json('data.*.province_id'));

        $this->assertSame([1], $provinceIds);
        $this->assertContains('Colombo', $response->json('data.*.name'));
    }

    #[Test]
    public function show_response_includes_entity_tag_and_last_modified_headers(): void
    {
        $response = $this->authGet('/api/provinces/1');

        $response->assertOk();
        $this->assertNotNull($response->headers->get('ETag'));
        $this->assertNotNull($response->headers->get('Last-Modified'));
    }
}
