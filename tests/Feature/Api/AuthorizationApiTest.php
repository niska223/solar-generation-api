<?php

namespace Tests\Feature\Api;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;

class AuthorizationApiTest extends ApiTestCase
{
    #[Test]
    public function reading_a_protected_resource_without_a_token_returns_401(): void
    {
        $this->getJson('/api/provinces')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthorized');
    }

    #[Test]
    public function a_national_operator_can_read(): void
    {
        $this->getJson('/api/provinces', $this->operatorHeaders())
            ->assertOk();
    }

    #[Test]
    public function an_administrator_can_read(): void
    {
        $this->getJson('/api/provinces', $this->administratorHeaders())
            ->assertOk();
    }

    #[Test]
    public function a_device_cannot_read_operator_endpoints(): void
    {
        $this->getJson('/api/provinces', $this->deviceHeaders())
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');
    }

    #[Test]
    public function an_operator_cannot_create_reference_data(): void
    {
        $this->postJson('/api/provinces', ['name' => 'Nope', 'code' => 'NOP'], $this->operatorHeaders())
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');
    }

    #[Test]
    public function an_operator_cannot_update_reference_data(): void
    {
        $this->putJson('/api/provinces/1', ['name' => 'Nope'], $this->operatorHeaders())
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');
    }

    #[Test]
    public function a_device_cannot_create_reference_data(): void
    {
        $this->postJson('/api/provinces', ['name' => 'Nope', 'code' => 'NOP'], $this->deviceHeaders())
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');
    }

    #[Test]
    public function an_administrator_can_create_reference_data(): void
    {
        $this->postJson('/api/provinces', ['name' => 'Admin Created', 'code' => 'ADC'], $this->administratorHeaders())
            ->assertCreated()
            ->assertJsonPath('data.code', 'ADC');
    }

    #[Test]
    public function an_operator_cannot_ingest_readings(): void
    {
        $this->postJson('/api/installations/1/readings', [
            'recorded_at' => '2026-10-08T00:00:00Z',
            'power_kw' => 2.5,
            'energy_kwh' => 205.0,
            'voltage' => 230.5,
        ], $this->operatorHeaders())
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');
    }

    #[Test]
    public function an_administrator_cannot_ingest_readings(): void
    {
        $this->postJson('/api/installations/1/readings', [
            'recorded_at' => '2026-10-08T00:00:00Z',
            'power_kw' => 2.5,
            'energy_kwh' => 205.0,
            'voltage' => 230.5,
        ], $this->administratorHeaders())
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');
    }

    #[Test]
    public function a_token_belonging_to_a_deleted_account_is_rejected(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_NATIONAL]);
        $token = $this->tokenFor($user);

        $user->delete();

        $this->getJson('/api/provinces', ['Authorization' => 'Bearer '.$token])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthorized');
    }

    #[Test]
    public function a_token_belonging_to_a_deactivated_account_is_rejected(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_NATIONAL]);
        $token = $this->tokenFor($user);

        $user->forceFill(['is_active' => false])->save();

        $this->getJson('/api/provinces', ['Authorization' => 'Bearer '.$token])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthorized');
    }
}
