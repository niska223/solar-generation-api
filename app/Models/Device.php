<?php

namespace App\Models;

use Database\Factories\DeviceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * A physical meter/logger attached to exactly one solar installation.
 *
 * The device secret is stored only as a hash (password_hash) and is never
 * returned by the API. A device token carries the installation id so readings
 * can only ever be appended to the device's own installation.
 */
class Device extends Authenticatable
{
    /** @use HasFactory<DeviceFactory> */
    use HasFactory;

    protected $fillable = [
        'solar_installation_id',
        'name',
        'device_identifier',
        'password_hash',
        'is_active',
        'last_used_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function solarInstallation(): BelongsTo
    {
        return $this->belongsTo(SolarInstallation::class);
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    /**
     * OAuth-style scopes granted to this principal, embedded in the JWT.
     *
     * @return list<string>
     */
    public function scopes(): array
    {
        return ['readings:write'];
    }
}
