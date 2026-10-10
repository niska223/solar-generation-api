<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Requests\DeviceLoginRequest;
use App\Http\Requests\OperatorLoginRequest;
use App\Http\Requests\RefreshTokenRequest;
use App\Models\Device;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends ApiController
{
    public function operatorLogin(OperatorLoginRequest $request, JwtService $jwt): JsonResponse
    {
        $user = User::query()->where('email', $request->validated('email'))->first();

        if ($user === null || ! Hash::check($request->validated('password'), $user->password)) {
            throw ApiException::unauthorized('The provided credentials are incorrect.');
        }

        if (! $user->isOperator() && ! $user->isAdministrator()) {
            throw ApiException::forbidden('This account is not permitted to access the API.');
        }

        if (! $user->is_active) {
            throw ApiException::forbidden('This account has been deactivated.');
        }

        return $this->tokenResponse($jwt, $user);
    }

    public function deviceLogin(DeviceLoginRequest $request, JwtService $jwt): JsonResponse
    {
        $device = Device::query()
            ->with('solarInstallation')
            ->where('device_identifier', $request->validated('device_identifier'))
            ->first();

        if ($device === null || ! Hash::check($request->validated('secret'), $device->password_hash)) {
            throw ApiException::unauthorized('The supplied device credentials are invalid.');
        }

        if (! $device->is_active) {
            throw ApiException::forbidden('This device has been disabled.');
        }

        $device->forceFill(['last_used_at' => now()])->saveQuietly();

        return $this->tokenResponse($jwt, $device);
    }

    public function refresh(RefreshTokenRequest $request, JwtService $jwt): JsonResponse
    {
        $principal = $jwt->resolvePrincipal($request->validated('refresh_token'), 'refresh');

        if ($principal === null) {
            throw ApiException::unauthorized('The refresh token is invalid or has expired.');
        }

        return $this->tokenResponse($jwt, $principal);
    }

    public function me(Request $request): JsonResponse
    {
        $principal = $request->user('api');

        if ($principal === null) {
            throw ApiException::unauthorized('Authentication is required to perform this action.');
        }

        return response()->json(['data' => $this->principalPayload($principal)]);
    }

    public function logout(Request $request, JwtService $jwt): JsonResponse
    {
        $token = (string) $request->bearerToken();
        $payload = $jwt->decode($token, 'access');

        $jwt->revoke((string) $payload['jti'], (int) $payload['exp']);

        return response()->json(['data' => ['revoked' => true]]);
    }

    private function tokenResponse(JwtService $jwt, User|Device $principal): JsonResponse
    {
        return response()->json([
            'data' => [
                'access_token' => $jwt->issueAccessToken($principal),
                'refresh_token' => $jwt->issueRefreshToken($principal),
                'token_type' => 'Bearer',
                'expires_in' => $jwt->accessLifetimeSeconds(),
                'principal' => $this->principalPayload($principal),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function principalPayload(User|Device $principal): array
    {
        $base = [
            'type' => $principal instanceof Device ? 'device' : 'user',
            'id' => $principal->getAuthIdentifier(),
            'name' => $principal->name,
            'scopes' => $principal->scopes(),
        ];

        if ($principal instanceof Device) {
            $base['solar_installation_id'] = $principal->solar_installation_id;
            $base['meter_id'] = $principal->solarInstallation?->meter_id;
            $base['is_active'] = $principal->is_active;

            return $base;
        }

        return $base + [
            'email' => $principal->email,
            'role' => $principal->role,
            'province_id' => $principal->province_id,
            'district_id' => $principal->district_id,
            'is_active' => $principal->is_active,
        ];
    }
}
