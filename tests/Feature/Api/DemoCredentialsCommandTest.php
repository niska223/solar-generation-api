<?php

namespace Tests\Feature\Api;

use App\Models\Device;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;

class DemoCredentialsCommandTest extends ApiTestCase
{
    #[Test]
    public function it_provisions_demo_operators_and_a_device_without_hardcoded_secrets(): void
    {
        $this->artisan('demo:credentials', [
            '--password' => 'Demo-Password-123!',
            '--device-secret' => 'Demo-Device-Secret-456!',
        ])->assertExitCode(0);

        $this->assertDatabaseHas('users', ['email' => 'admin@slsea.test', 'role' => User::ROLE_ADMINISTRATOR]);
        $this->assertDatabaseHas('users', ['email' => 'national@slsea.test', 'role' => User::ROLE_NATIONAL]);
        $this->assertDatabaseHas('users', ['email' => 'western@slsea.test', 'role' => User::ROLE_PROVINCIAL, 'province_id' => 1]);
        $this->assertDatabaseHas('users', ['email' => 'colombo@slsea.test', 'role' => User::ROLE_DISTRICT, 'district_id' => 1]);

        $this->assertSame(4, User::query()->whereIn('email', [
            'admin@slsea.test',
            'national@slsea.test',
            'western@slsea.test',
            'colombo@slsea.test',
        ])->count());

        $this->assertDatabaseHas('devices', [
            'solar_installation_id' => 1,
            'device_identifier' => 'DEMO-MTR-000001',
        ]);
    }

    #[Test]
    public function a_provisioned_demo_operator_can_authenticate(): void
    {
        $this->artisan('demo:credentials', [
            '--password' => 'Demo-Password-123!',
            '--device-secret' => 'Demo-Device-Secret-456!',
        ])->assertExitCode(0);

        $this->postJson('/api/auth/operator/login', [
            'email' => 'admin@slsea.test',
            'password' => 'Demo-Password-123!',
        ])->assertOk()
            ->assertJsonPath('data.principal.role', User::ROLE_ADMINISTRATOR);
    }

    #[Test]
    public function a_provisioned_demo_device_can_authenticate(): void
    {
        $this->artisan('demo:credentials', [
            '--password' => 'Demo-Password-123!',
            '--device-secret' => 'Demo-Device-Secret-456!',
        ])->assertExitCode(0);

        $device = Device::query()->where('device_identifier', 'DEMO-MTR-000001')->firstOrFail();

        $this->postJson('/api/auth/device/login', [
            'device_identifier' => $device->device_identifier,
            'secret' => 'Demo-Device-Secret-456!',
        ])->assertOk()
            ->assertJsonPath('data.principal.type', 'device');
    }
}
