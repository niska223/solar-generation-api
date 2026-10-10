<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Device;
use App\Models\User;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

/**
 * Issues and validates signed JWTs for operators (users) and devices.
 *
 * Only registered claims (iss, aud), the token type, the signature and the
 * lifetime are trusted. The identity is always re-loaded from the database so
 * a disabled account is rejected even while its token is still valid.
 */
class JwtService
{
    private const TYPE_ACCESS = 'access';

    private const TYPE_REFRESH = 'refresh';

    public function issueAccessToken(Authenticatable $principal): string
    {
        return $this->issue($principal, self::TYPE_ACCESS);
    }

    public function issueRefreshToken(Authenticatable $principal): string
    {
        return $this->issue($principal, self::TYPE_REFRESH);
    }

    public function accessLifetimeSeconds(): int
    {
        return (int) config('jwt.ttl', 60) * 60;
    }

    public function refreshLifetimeSeconds(): int
    {
        return (int) config('jwt.refresh_ttl', 20160) * 60;
    }

    /**
     * Build and sign a token.
     *
     * @return array{token: string, expires_in: int}
     */
    public function credentials(Authenticatable $principal): array
    {
        $access = $this->issue($principal, self::TYPE_ACCESS);

        return [
            'token' => $access,
            'expires_in' => $this->accessLifetimeSeconds(),
        ];
    }

    private function issue(Authenticatable $principal, string $type): string
    {
        $now = time();
        $lifetime = $type === self::TYPE_REFRESH
            ? $this->refreshLifetimeSeconds()
            : $this->accessLifetimeSeconds();

        $claims = [
            'iss' => (string) config('jwt.issuer'),
            'aud' => (string) config('jwt.audience'),
            'sub' => (string) $principal->getAuthIdentifier(),
            'jti' => (string) Str::uuid(),
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $lifetime,
            'typ' => 'JWT',
            'token_type' => $type,
        ];

        $claims += $this->principalClaims($principal);

        return JWT::encode($claims, $this->secret(), (string) config('jwt.algo', 'HS256'));
    }

    /**
     * @return array<string, mixed>
     */
    private function principalClaims(Authenticatable $principal): array
    {
        if ($principal instanceof Device) {
            return [
                'principal' => 'device',
                'name' => $principal->name,
                'installation_id' => (int) $principal->solar_installation_id,
                'meter_id' => $principal->solarInstallation?->meter_id,
                'scope' => $principal->scopes(),
            ];
        }

        if ($principal instanceof User) {
            return [
                'principal' => 'user',
                'name' => $principal->name,
                'email' => $principal->email,
                'role' => $principal->role,
                'scope' => $principal->scopes(),
                'province_id' => $principal->province_id,
                'district_id' => $principal->district_id,
            ];
        }

        throw new \LogicException('Unsupported JWT principal ['.$principal::class.'].');
    }

    /**
     * Decode and fully validate a token.
     *
     * @return array<string, mixed>
     */
    public function decode(string $token, string $expectedType = self::TYPE_ACCESS): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret(), (string) config('jwt.algo', 'HS256')));
        } catch (ExpiredException) {
            throw ApiException::unauthorized('The token has expired. Please authenticate again.');
        } catch (Throwable) {
            throw ApiException::unauthorized('The supplied bearer token is invalid.');
        }

        $payload = (array) $decoded;

        if (($payload['iss'] ?? null) !== config('jwt.issuer')) {
            throw ApiException::unauthorized('The token was not issued for this API.');
        }

        if (($payload['aud'] ?? null) !== config('jwt.audience')) {
            throw ApiException::unauthorized('The token was not issued for this audience.');
        }

        if (($payload['token_type'] ?? null) !== $expectedType) {
            throw ApiException::unauthorized('The token cannot be used for this operation.');
        }

        $jti = (string) ($payload['jti'] ?? '');

        if ($jti !== '' && $this->isRevoked($jti)) {
            throw ApiException::unauthorized('The token has been revoked.');
        }

        return $payload;
    }

    /**
     * Resolve the database principal for a validated token, or null when the
     * account no longer exists / is no longer active.
     */
    public function resolvePrincipal(string $token, string $expectedType = self::TYPE_ACCESS): ?Authenticatable
    {
        try {
            $payload = $this->decode($token, $expectedType);
        } catch (ApiException) {
            return null;
        }

        $principal = match ($payload['principal'] ?? null) {
            'user' => User::find($payload['sub'] ?? null),
            'device' => Device::find($payload['sub'] ?? null),
            default => null,
        };

        if ($principal === null) {
            return null;
        }

        if ($principal instanceof User && ! $principal->is_active) {
            return null;
        }

        if ($principal instanceof Device && ! $principal->is_active) {
            return null;
        }

        return $principal;
    }

    public function revoke(string $jti, int $expiresAt): void
    {
        $ttl = max(1, $expiresAt - time());

        Cache::put($this->blacklistKey($jti), true, $ttl);
    }

    public function isRevoked(string $jti): bool
    {
        return Cache::has($this->blacklistKey($jti));
    }

    private function blacklistKey(string $jti): string
    {
        return (string) config('jwt.blacklist_prefix').$jti;
    }

    private function secret(): string
    {
        $secret = (string) config('jwt.secret');

        if ($secret === '') {
            throw ApiException::unauthorized('JWT signing is not configured. Set JWT_SECRET.');
        }

        return $secret;
    }
}
