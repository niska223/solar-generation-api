<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\District;
use App\Models\Province;
use App\Models\SolarInstallation;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Provisions a small, predictable set of demonstration accounts so the JWT
 * login flow can be exercised by hand or by a marker.
 *
 * Secrets are never stored in the source tree or in this command: the operator
 * password and device secret are either supplied on the command line or
 * generated at random and printed once.
 */
class DemoCredentialsCommand extends Command
{
    protected $signature = 'demo:credentials
        {--password= : Password to set for every demo operator account (a strong random one is generated when omitted)}
        {--device-secret= : Secret to set for the demo solar device (a strong random one is generated when omitted)}';

    protected $description = 'Create or refresh the SLSEA demo operator and device accounts';

    public function handle(): int
    {
        $password = (string) ($this->option('password') ?: Str::password(16));
        $deviceSecret = (string) ($this->option('device-secret') ?: Str::password(24));

        $province = Province::query()->where('code', 'WP')->first();
        $district = District::query()->where('name', 'Colombo')->first();

        if ($province === null || $district === null) {
            $this->error('Reference data is missing. Run `php artisan db:seed` first.');

            return self::FAILURE;
        }

        $operators = [
            ['email' => 'admin@slsea.test', 'name' => 'SLSEA Administrator', 'role' => User::ROLE_ADMINISTRATOR, 'province_id' => null, 'district_id' => null],
            ['email' => 'national@slsea.test', 'name' => 'SLSEA National Operator', 'role' => User::ROLE_NATIONAL, 'province_id' => null, 'district_id' => null],
            ['email' => 'western@slsea.test', 'name' => 'Western Provincial Operator', 'role' => User::ROLE_PROVINCIAL, 'province_id' => $province->id, 'district_id' => null],
            ['email' => 'colombo@slsea.test', 'name' => 'Colombo District Operator', 'role' => User::ROLE_DISTRICT, 'province_id' => null, 'district_id' => $district->id],
        ];

        $rows = [];

        foreach ($operators as $operator) {
            User::query()->updateOrCreate(
                ['email' => $operator['email']],
                [
                    'name' => $operator['name'],
                    'password' => Hash::make($password),
                    'role' => $operator['role'],
                    'province_id' => $operator['province_id'],
                    'district_id' => $operator['district_id'],
                    'is_active' => true,
                ],
            );

            $rows[] = [$operator['email'], $password, $operator['role']];
        }

        $installation = SolarInstallation::query()->where('meter_id', 'MTR-000001')->first();
        $deviceIdentifier = $installation !== null ? 'DEMO-'.$installation->meter_id : null;

        if ($installation !== null) {
            Device::query()->updateOrCreate(
                ['solar_installation_id' => $installation->id],
                [
                    'name' => 'Demo Logger',
                    'device_identifier' => $deviceIdentifier,
                    'password_hash' => Hash::make($deviceSecret),
                    'is_active' => true,
                ],
            );
        }

        $this->info('Demo credentials provisioned. Store these securely and rotate before any real use.');
        $this->newLine();

        $this->table(['Operator e-mail', 'Password', 'Role'], $rows);

        if ($installation !== null) {
            $this->newLine();
            $this->table(
                ['Device identifier', 'Secret', 'Installation'],
                [[$deviceIdentifier, $deviceSecret, $installation->meter_id]],
            );
        }

        $this->newLine();
        $this->line('Operator login:  POST /api/auth/operator/login   {"email": "...", "password": "..."}');
        $this->line('Device login:    POST /api/auth/device/login      {"device_identifier": "...", "secret": "..."}');

        return self::SUCCESS;
    }
}
