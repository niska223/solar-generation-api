<?php

namespace Tests\Feature\Api;

use App\Models\Device;
use App\Models\SolarInstallation;
use PHPUnit\Framework\Attributes\Test;

class ReadingApiTest extends ApiTestCase
{
    private SolarInstallation $installation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->installation = SolarInstallation::findOrFail(1);
    }

    #[Test]
    public function readings_index_is_paginated_and_sorted_descending_by_recorded_at_by_default(): void
    {
        $response = $this->authGet('/api/installations/1/readings?per_page=3');

        $response->assertOk()
            ->assertJsonPath('meta.total', 672)
            ->assertJsonCount(3, 'data');

        $dates = $response->json('data.*.recorded_at');

        $this->assertSame($dates, array_values(array_unique($dates)));
        $this->assertSame($dates, collect($dates)->sortDesc()->values()->all());
    }

    #[Test]
    public function readings_can_be_sorted_ascending(): void
    {
        $response = $this->authGet('/api/installations/1/readings?per_page=5&sort=recorded_at&direction=asc');

        $response->assertOk()
            ->assertJsonPath('data.0.recorded_at', '2026-10-01T00:00:00.000000Z');

        $dates = $response->json('data.*.recorded_at');
        $this->assertSame($dates, collect($dates)->sort()->values()->all());
    }

    #[Test]
    public function readings_time_window_filter_is_scoped_and_returns_the_expected_count(): void
    {
        $response = $this->authGet(
            '/api/installations/1/readings?recorded_at_from=2026-10-05T00:00:00Z&recorded_at_to=2026-10-05T23:59:59Z'
        );

        $response->assertOk()
            ->assertJsonPath('meta.total', 96);

        foreach ($response->json('data.*.recorded_at') as $date) {
            $this->assertStringStartsWith('2026-10-05', $date);
        }
    }

    #[Test]
    public function invalid_time_window_parameters_return_400(): void
    {
        $this->authGet('/api/installations/1/readings?recorded_at_from=not-a-date')
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'bad_request');

        $this->authGet('/api/installations/1/readings?recorded_at_from=2026-10-06T00:00:00Z&recorded_at_to=2026-10-05T00:00:00Z')
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'bad_request');
    }

    #[Test]
    public function reading_show_requires_the_reading_to_belong_to_the_installation(): void
    {
        $reading = $this->installation->generationReadings()->first();

        $this->authGet('/api/installations/1/readings/'.$reading->id)
            ->assertOk()
            ->assertJsonPath('data.id', $reading->id)
            ->assertJsonPath('data.solar_installation_id', 1);
    }

    #[Test]
    public function reading_show_returns_404_when_the_reading_belongs_to_another_installation(): void
    {
        $foreignReading = SolarInstallation::findOrFail(2)->generationReadings()->first();

        $this->authGet('/api/installations/1/readings/'.$foreignReading->id)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');
    }

    #[Test]
    public function reading_show_returns_404_for_missing_reading(): void
    {
        $this->authGet('/api/installations/1/readings/999999')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');
    }

    #[Test]
    public function readings_cannot_be_edited_or_deleted(): void
    {
        $reading = $this->installation->generationReadings()->first();

        $this->putJson('/api/installations/1/readings/'.$reading->id, [
            'power_kw' => 9.9,
        ])->assertStatus(405);

        $this->patchJson('/api/installations/1/readings/'.$reading->id, [
            'power_kw' => 9.9,
        ])->assertStatus(405);

        $this->deleteJson('/api/installations/1/readings/'.$reading->id)
            ->assertStatus(405);
    }

    #[Test]
    public function ingestion_returns_401_when_no_credentials_are_present(): void
    {
        $this->postJson('/api/installations/1/readings', $this->validReading())
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthorized');
    }

    #[Test]
    public function ingestion_rejects_a_token_for_a_deactivated_device(): void
    {
        $inactive = Device::factory()->forInstallation(3)->inactive()->create();

        $this->postJson('/api/installations/3/readings', $this->validReading(), $this->deviceHeaders($inactive))
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthorized');
    }

    #[Test]
    public function ingestion_returns_401_for_an_invalid_token(): void
    {
        $this->postJson('/api/installations/1/readings', $this->validReading(), [
            'Authorization' => 'Bearer not-a-real-jwt',
        ])->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthorized');
    }

    #[Test]
    public function ingestion_returns_403_for_cross_installation_tokens(): void
    {
        $this->postJson('/api/installations/1/readings', $this->validReading(), $this->deviceHeaders($this->foreignDevice))
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');
    }

    public function test_ingestion_creates_a_reading_with_201_and_location_header(): void
    {
        $response = $this->postJson('/api/installations/1/readings', $this->validReading(), $this->deviceHeaders());

        $response->assertCreated()
            ->assertJsonPath('data.solar_installation_id', 1)
            ->assertJsonPath('data.recorded_at', '2026-10-08T00:00:00.000000Z');

        $location = $response->headers->get('Location');
        $this->assertNotNull($location);
        $this->assertStringEndsWith('/api/installations/1/readings/'.$response->json('data.id'), $location);

        $this->assertDatabaseHas('generation_readings', [
            'solar_installation_id' => 1,
            'recorded_at' => '2026-10-08 00:00:00',
        ]);
    }

    #[Test]
    public function ingestion_returns_409_for_a_duplicate_reading_timestamp(): void
    {
        $payload = $this->validReading(['recorded_at' => '2026-10-07T23:45:00Z', 'energy_kwh' => 210.0]);

        $this->postJson('/api/installations/1/readings', $payload, $this->deviceHeaders())
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'conflict');
    }

    #[Test]
    public function ingestion_returns_422_for_invalid_payloads(): void
    {
        $headers = $this->deviceHeaders();

        $this->postJson('/api/installations/1/readings', $this->validReading(['voltage' => -5]), $headers)
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonPath('error.details.voltage.0', 'The voltage field must be at least 0.');

        $this->postJson('/api/installations/1/readings', $this->validReading(['power_kw' => 'abc']), $headers)
            ->assertUnprocessable()
            ->assertJsonPath('error.details.power_kw.0', 'The power kw field must be a number.');

        $this->postJson('/api/installations/1/readings', $this->validReading(['recorded_at' => null]), $headers)
            ->assertUnprocessable();
    }

    #[Test]
    public function ingestion_returns_422_when_energy_is_not_monotonic(): void
    {
        $this->postJson('/api/installations/1/readings', $this->validReading(['energy_kwh' => 100.0]), $this->deviceHeaders())
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed');
    }

    #[Test]
    public function ingestion_returns_422_when_power_exceeds_capacity(): void
    {
        $this->postJson('/api/installations/1/readings', $this->validReading(['power_kw' => 500.0]), $this->deviceHeaders())
            ->assertUnprocessable()
            ->assertJsonPath('error.details.power_kw.0', 'The power_kw field must not exceed the installation capacity of 3.20 kW.');
    }

    #[Test]
    public function ingestion_rejects_a_hijacked_installation_id_in_the_body(): void
    {
        $this->postJson('/api/installations/1/readings', $this->validReading([
            'solar_installation_id' => 2,
        ]), $this->deviceHeaders())
            ->assertUnprocessable()
            ->assertJsonPath('error.details.solar_installation_id.0', 'The solar installation id field is prohibited.');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validReading(array $overrides = []): array
    {
        return array_merge([
            'recorded_at' => '2026-10-08T00:00:00Z',
            'power_kw' => 2.5,
            'energy_kwh' => 205.0,
            'voltage' => 230.5,
        ], $overrides);
    }
}
