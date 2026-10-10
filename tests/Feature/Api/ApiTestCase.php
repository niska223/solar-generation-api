<?php

namespace Tests\Feature\Api;

use App\Models\Device;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Base class for API feature tests.
 *
 * Uses DatabaseTransactions so any row created by a test (a principal, an
 * ingested reading or an administratively-created province) is rolled back
 * afterwards and the seeded data is never mutated permanently.
 *
 * All requests are authenticated with real, signed JWTs issued by the same
 * JwtService the application uses, so the tests exercise the complete
 * authentication and authorisation stack.
 */
abstract class ApiTestCase extends TestCase
{
    use DatabaseTransactions;

    /** SLSEA operator with national jurisdiction (read access to everything). */
    protected User $operator;

    /** SLSEA administrator (reference-data CRUD). */
    protected User $administrator;

    /** Solar device bound to installation #1. */
    protected Device $device;

    /** Solar device bound to installation #2 (used for cross-installation tests). */
    protected Device $foreignDevice;

    protected function setUp(): void
    {
        parent::setUp();

        config(['jwt.secret' => 'testing-jwt-signing-secret-0123456789abcdef']);

        $this->operator = User::factory()->create([
            'role' => User::ROLE_NATIONAL,
            'province_id' => null,
            'district_id' => null,
        ]);

        $this->administrator = User::factory()->create([
            'role' => User::ROLE_ADMINISTRATOR,
            'province_id' => null,
            'district_id' => null,
        ]);

        $this->device = Device::factory()->forInstallation(1)->create();
        $this->foreignDevice = Device::factory()->forInstallation(2)->create();
    }

    protected function jwt(): JwtService
    {
        return app(JwtService::class);
    }

    protected function tokenFor(Authenticatable $principal): string
    {
        return $this->jwt()->issueAccessToken($principal);
    }

    /**
     * @param  array<string, string>  $extra
     * @return array<string, string>
     */
    protected function operatorHeaders(array $extra = []): array
    {
        return $this->bearer($this->tokenFor($this->operator), $extra);
    }

    /**
     * @param  array<string, string>  $extra
     * @return array<string, string>
     */
    protected function administratorHeaders(array $extra = []): array
    {
        return $this->bearer($this->tokenFor($this->administrator), $extra);
    }

    /**
     * @param  array<string, string>  $extra
     * @return array<string, string>
     */
    protected function deviceHeaders(?Device $device = null, array $extra = []): array
    {
        return $this->bearer($this->tokenFor($device ?? $this->device), $extra);
    }

    /**
     * Perform a GET request as the default (national) operator.
     *
     * @param  array<string, string>  $headers
     */
    protected function authGet(string $uri, array $headers = []): TestResponse
    {
        return $this->getJson($uri, $this->operatorHeaders($headers));
    }

    /**
     * @param  array<string, string>  $extra
     * @return array<string, string>
     */
    private function bearer(string $token, array $extra): array
    {
        return ['Authorization' => 'Bearer '.$token] + $extra;
    }
}
