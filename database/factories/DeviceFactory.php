<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\SolarInstallation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    /**
     * The current secret being used by the factory.
     */
    protected static ?string $secret;

    public function definition(): array
    {
        return [
            'solar_installation_id' => SolarInstallation::query()->inRandomOrder()->value('id') ?? 1,
            'name' => fake()->words(2, true).' Logger',
            'device_identifier' => 'DEV-'.strtoupper(Str::random(10)),
            'password_hash' => Hash::make(static::$secret ??= 'device-secret'),
            'is_active' => true,
        ];
    }

    public function forInstallation(SolarInstallation|int $installation, string $secret = 'device-secret'): static
    {
        return $this->state(fn (): array => [
            'solar_installation_id' => $installation instanceof SolarInstallation
                ? $installation->id
                : $installation,
            'device_identifier' => 'DEV-MTR-'.(is_numeric($installation) ? $installation : $installation->id),
            'password_hash' => Hash::make($secret),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
