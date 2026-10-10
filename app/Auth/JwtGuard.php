<?php

namespace App\Auth;

use App\Models\Device;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;

/**
 * Stateless JWT bearer guard.
 *
 * The principal (an SLSEA operator User or a Device) is resolved from the
 * Authorization bearer token on demand. Because the guard resolves the current
 * request lazily it can be reused across requests in a single PHP test run.
 */
class JwtGuard implements Guard
{
    protected ?Authenticatable $user = null;

    protected bool $manuallySet = false;

    public function __construct(
        private readonly JwtService $jwt,
    ) {}

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return ! $this->check();
    }

    public function user(): ?Authenticatable
    {
        if ($this->manuallySet) {
            return $this->user;
        }

        $token = $this->currentRequest()?->bearerToken();

        return $this->user = $token === null ? null : $this->jwt->resolvePrincipal($token, 'access');
    }

    public function id(): int|string|null
    {
        return $this->user()?->getAuthIdentifier();
    }

    public function validate(array $credentials = []): bool
    {
        return false;
    }

    public function hasUser(): bool
    {
        return $this->user !== null;
    }

    public function setUser(Authenticatable $user): static
    {
        $this->user = $user;
        $this->manuallySet = true;

        return $this;
    }

    public function getProvider(): ?UserProvider
    {
        return null;
    }

    public function forgetUser(): void
    {
        $this->user = null;
        $this->manuallySet = false;
    }

    public function isOperator(): bool
    {
        return $this->user() instanceof User;
    }

    public function isDevice(): bool
    {
        return $this->user() instanceof Device;
    }

    private function currentRequest(): ?Request
    {
        return app('request');
    }
}
