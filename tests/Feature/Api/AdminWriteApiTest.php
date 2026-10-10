<?php

namespace Tests\Feature\Api;

use App\Models\Province;
use PHPUnit\Framework\Attributes\Test;

class AdminWriteApiTest extends ApiTestCase
{
    #[Test]
    public function administrator_can_create_a_province(): void
    {
        $response = $this->postJson('/api/provinces', [
            'name' => 'ZTest Province',
            'code' => 'ZTP',
        ], $this->operatorHeaders());

        $response->assertCreated()
            ->assertJsonPath('data.name', 'ZTest Province')
            ->assertJsonPath('data.code', 'ZTP');

        $this->assertNotNull($response->headers->get('Location'));

        $this->assertDatabaseHas('provinces', ['code' => 'ZTP']);
    }

    #[Test]
    public function create_province_with_a_duplicate_code_returns_409(): void
    {
        $this->postJson('/api/provinces', [
            'name' => 'Western Again',
            'code' => 'WP',
        ], $this->operatorHeaders())
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'conflict');
    }

    #[Test]
    public function create_province_with_invalid_data_returns_422(): void
    {
        $this->postJson('/api/provinces', [
            'name' => '',
            'code' => '',
        ], $this->operatorHeaders())
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed');
    }

    #[Test]
    public function administrator_can_update_a_province_with_put_and_patch(): void
    {
        $this->putJson('/api/provinces/1', ['name' => 'Western Updated'], $this->operatorHeaders())
            ->assertOk()
            ->assertJsonPath('data.name', 'Western Updated');

        $this->patchJson('/api/provinces/1', ['code' => 'WPR'], $this->operatorHeaders())
            ->assertOk()
            ->assertJsonPath('data.code', 'WPR');
    }

    #[Test]
    public function administrator_can_delete_a_province_with_no_dependencies(): void
    {
        $province = Province::create(['name' => 'ZTest Delete', 'code' => 'ZTD']);

        $this->deleteJson('/api/provinces/'.$province->id, [], $this->operatorHeaders())
            ->assertNoContent(204);

        $this->getJson('/api/provinces/'.$province->id)
            ->assertNotFound();
    }

    #[Test]
    public function deleting_a_province_that_has_districts_returns_409(): void
    {
        $this->deleteJson('/api/provinces/1', [], $this->operatorHeaders())
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'conflict');
    }

    #[Test]
    public function administrator_can_create_update_and_delete_a_district(): void
    {
        $create = $this->postJson('/api/districts', [
            'province_id' => 1,
            'name' => 'ZTest District',
        ], $this->operatorHeaders());

        $create->assertCreated()->assertJsonPath('data.name', 'ZTest District');
        $id = $create->json('data.id');

        $this->patchJson('/api/districts/'.$id, ['province_id' => 2], $this->operatorHeaders())
            ->assertOk()
            ->assertJsonPath('data.province_id', 2);

        $this->deleteJson('/api/districts/'.$id, [], $this->operatorHeaders())
            ->assertNoContent(204);

        $this->getJson('/api/districts/'.$id)->assertNotFound();
    }

    #[Test]
    public function administrator_can_create_update_and_delete_a_grid_substation(): void
    {
        $create = $this->postJson('/api/grid-substations', [
            'district_id' => 1,
            'name' => 'ZTest Substation',
            'code' => 'Z-SUB-1',
            'location' => 'ZTest',
        ], $this->operatorHeaders());

        $create->assertCreated()->assertJsonPath('data.code', 'Z-SUB-1');
        $id = $create->json('data.id');

        $this->patchJson('/api/grid-substations/'.$id, ['name' => 'ZTest Substation Renamed'], $this->operatorHeaders())
            ->assertOk();

        $this->deleteJson('/api/grid-substations/'.$id, [], $this->operatorHeaders())
            ->assertNoContent(204);

        $this->getJson('/api/grid-substations/'.$id)->assertNotFound();
    }

    #[Test]
    public function administrator_can_create_update_and_delete_a_solar_installation(): void
    {
        $create = $this->postJson('/api/installations', [
            'grid_substation_id' => 1,
            'meter_id' => 'Z-MTR-999999',
            'capacity_kw' => 5.0,
            'address' => 'ZTest',
            'status' => 'active',
        ], $this->operatorHeaders());

        $create->assertCreated()->assertJsonPath('data.meter_id', 'Z-MTR-999999');
        $id = $create->json('data.id');

        $this->patchJson('/api/installations/'.$id, ['status' => 'maintenance'], $this->operatorHeaders())
            ->assertOk()
            ->assertJsonPath('data.status', 'maintenance');

        $this->deleteJson('/api/installations/'.$id, [], $this->operatorHeaders())
            ->assertNoContent(204);

        $this->getJson('/api/installations/'.$id)->assertNotFound();
    }

    #[Test]
    public function update_with_invalid_status_returns_422(): void
    {
        $this->patchJson('/api/installations/1', ['status' => 'broken'], $this->operatorHeaders())
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed');
    }

    #[Test]
    public function reading_count_is_available_on_collection_resources(): void
    {
        $response = $this->getJson('/api/grid-substations/1/installations?per_page=50');

        $response->assertOk();
        $this->assertSame(9, count($response->json('data')));
        $this->assertNotSame(0, $response->json('data.0.readings_count'));
    }
}
