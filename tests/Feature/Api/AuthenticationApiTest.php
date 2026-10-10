<?php

namespace Tests\Feature\Api;

use App\Models\Device;
use App\Models\User;
use Firebase\JWT\JWT;
use PHPUnit\Framework\Attributes\Test;

class AuthenticationApiTest extends ApiTestCase
{
    #[Test]
    public function an_operator_can_log_in_and_receive_a_token_pair(): void
    {
        $response = $this->postJson('/api/auth/operator/login', [
            'email' => $this->operator->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.principal.type', 'user')
            ->assertJsonPath('data.principal.role', User::ROLE_NATIONAL)
            ->assertJsonStructure([
                'data' => ['access_token', 'refresh_token', 'token_type', 'expires_in', 'principal' => ['id', 'name', 'scopes']],
            ]);

        $this->assertIsString($response->json('data.access_token'));
        $this->assertIsString($response->json('data.refresh_token'));
    }

    #[Test]
    public function a_token_issued_at_log_in_can_access_a_protected_endpoint(): void
    {
        $token = $this->postJson('/api/auth/operator/login', [
            'email' => $this->operator->email,
            'password' => 'password',
        ])->json('data.access_token');

        $this->getJson('/api/auth/me', ['Authorization' => 'Bearer '.$token])
            ->assertOk()
            ->assertJsonPath('data.id', $this->operator->id)
            ->assertJsonPath('data.type', 'user');
    }

    #[Test]
    public function an_operator_cannot_log_in_with_the_wrong_password(): void
    {
        $this->postJson('/api/auth/operator/login', [
            'email' => $this->operator->email,
            'password' => 'not-the-password',
        ])->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthorized');
    }

    #[Test]
    public function an_operator_cannot_log_in_with_an_unknown_email(): void
    {
        $this->postJson('/api/auth/operator/login', [
            'email' => 'nobody@example.test',
            'password' => 'password',
        ])->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthorized');
    }

    #[Test]
    public function a_deactivated_operator_cannot_log_in(): void
    {
        $user = User::factory()->inactive()->create(['role' => User::ROLE_NATIONAL]);

        $this->postJson('/api/auth/operator/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');
    }

    #[Test]
    public function operator_login_validates_its_input(): void
    {
        $this->postJson('/api/auth/operator/login', [])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonPath('error.details.email.0', 'The email field is required.')
            ->assertJsonPath('error.details.password.0', 'The password field is required.');
    }

    #[Test]
    public function a_second_authentication_attempt_for_the_same_account_also_works(): void
    {
        $first = $this->postJson('/api/auth/operator/login', [
            'email' => $this->operator->email,
            'password' => 'password',
        ])->json('data.access_token');

        $second = $this->postJson('/api/auth/operator/login', [
            'email' => $this->operator->email,
            'password' => 'password',
        ])->json('data.access_token');

        $this->getJson('/api/auth/me', ['Authorization' => 'Bearer '.$first])->assertOk();
        $this->getJson('/api/auth/me', ['Authorization' => 'Bearer '.$second])->assertOk();
    }

    #[Test]
    public function a_device_can_log_in_with_its_identifier_and_secret(): void
    {
        $response = $this->postJson('/api/auth/device/login', [
            'device_identifier' => $this->device->device_identifier,
            'secret' => 'device-secret',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.principal.type', 'device')
            ->assertJsonPath('data.principal.solar_installation_id', 1)
            ->assertJsonPath('data.principal.meter_id', 'MTR-000001');
    }

    #[Test]
    public function a_device_cannot_log_in_with_the_wrong_secret(): void
    {
        $this->postJson('/api/auth/device/login', [
            'device_identifier' => $this->device->device_identifier,
            'secret' => 'wrong-secret',
        ])->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthorized');
    }

    #[Test]
    public function a_deactivated_device_cannot_log_in(): void
    {
        $device = Device::factory()->forInstallation(4)->inactive()->create();

        $this->postJson('/api/auth/device/login', [
            'device_identifier' => $device->device_identifier,
            'secret' => 'device-secret',
        ])->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');
    }

    #[Test]
    public function a_refresh_token_can_be_exchanged_for_a_new_token_pair(): void
    {
        $refresh = $this->postJson('/api/auth/operator/login', [
            'email' => $this->operator->email,
            'password' => 'password',
        ])->json('data.refresh_token');

        $response = $this->postJson('/api/auth/refresh', ['refresh_token' => $refresh]);

        $response->assertOk()
            ->assertJsonPath('data.principal.id', $this->operator->id);

        $this->getJson('/api/auth/me', ['Authorization' => 'Bearer '.$response->json('data.access_token')])
            ->assertOk();
    }

    #[Test]
    public function an_access_token_cannot_be_used_at_the_refresh_endpoint(): void
    {
        $access = $this->postJson('/api/auth/operator/login', [
            'email' => $this->operator->email,
            'password' => 'password',
        ])->json('data.access_token');

        $this->postJson('/api/auth/refresh', ['refresh_token' => $access])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthorized');
    }

    #[Test]
    public function a_refresh_token_cannot_be_used_as_an_access_token(): void
    {
        $refresh = $this->postJson('/api/auth/operator/login', [
            'email' => $this->operator->email,
            'password' => 'password',
        ])->json('data.refresh_token');

        $this->getJson('/api/auth/me', ['Authorization' => 'Bearer '.$refresh])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthorized');
    }

    #[Test]
    public function the_current_principal_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthorized');
    }

    #[Test]
    public function logging_out_revokes_the_presented_access_token(): void
    {
        $token = $this->postJson('/api/auth/operator/login', [
            'email' => $this->operator->email,
            'password' => 'password',
        ])->json('data.access_token');

        $headers = ['Authorization' => 'Bearer '.$token];

        $this->getJson('/api/auth/me', $headers)->assertOk();

        $this->postJson('/api/auth/logout', [], $headers)
            ->assertOk()
            ->assertJsonPath('data.revoked', true);

        $this->getJson('/api/auth/me', $headers)
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthorized');
    }

    #[Test]
    public function an_expired_token_is_rejected(): void
    {
        config(['jwt.ttl' => -1]);

        $token = $this->tokenFor($this->operator);

        config(['jwt.ttl' => 60]);

        $this->getJson('/api/auth/me', ['Authorization' => 'Bearer '.$token])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthorized');
    }

    #[Test]
    public function a_tampered_token_is_rejected(): void
    {
        $token = $this->tokenFor($this->operator);
        $tampered = substr($token, 0, -2).'xx';

        $this->getJson('/api/auth/me', ['Authorization' => 'Bearer '.$tampered])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthorized');
    }

    #[Test]
    public function a_token_signed_for_another_issuer_is_rejected(): void
    {
        $token = $this->forgeToken(['iss' => 'some-other-service']);

        $this->getJson('/api/auth/me', ['Authorization' => 'Bearer '.$token])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthorized');
    }

    #[Test]
    public function a_token_signed_for_another_audience_is_rejected(): void
    {
        $token = $this->forgeToken(['aud' => 'some-other-audience']);

        $this->getJson('/api/auth/me', ['Authorization' => 'Bearer '.$token])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthorized');
    }

    #[Test]
    public function a_token_signed_with_the_wrong_secret_is_rejected(): void
    {
        $now = time();

        $token = JWT::encode([
            'iss' => config('jwt.issuer'),
            'aud' => config('jwt.audience'),
            'sub' => (string) $this->operator->id,
            'principal' => 'user',
            'token_type' => 'access',
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + 3600,
        ], 'a-different-signing-secret-that-is-long-enough', 'HS256');

        $this->getJson('/api/auth/me', ['Authorization' => 'Bearer '.$token])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthorized');
    }

    /**
     * Build a token signed with the real secret but with overridden claims.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function forgeToken(array $overrides): string
    {
        $now = time();

        $claims = array_merge([
            'iss' => config('jwt.issuer'),
            'aud' => config('jwt.audience'),
            'sub' => (string) $this->operator->id,
            'principal' => 'user',
            'token_type' => 'access',
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + 3600,
        ], $overrides);

        return JWT::encode($claims, (string) config('jwt.secret'), 'HS256');
    }
}
