<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_NATIONAL = 'national';

    public const ROLE_PROVINCIAL = 'provincial';

    public const ROLE_DISTRICT = 'district';

    public const ROLE_ADMINISTRATOR = 'administrator';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'province_id',
        'district_id',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function isAdministrator(): bool
    {
        return $this->role === self::ROLE_ADMINISTRATOR;
    }

    public function isNational(): bool
    {
        return $this->role === self::ROLE_NATIONAL;
    }

    public function isProvincial(): bool
    {
        return $this->role === self::ROLE_PROVINCIAL;
    }

    public function isDistrict(): bool
    {
        return $this->role === self::ROLE_DISTRICT;
    }

    /**
     * An SLSEA operator holds a geographic read jurisdiction.
     */
    public function isOperator(): bool
    {
        return in_array($this->role, [
            self::ROLE_NATIONAL,
            self::ROLE_PROVINCIAL,
            self::ROLE_DISTRICT,
        ], true);
    }

    /**
     * OAuth-style scopes granted to this account, embedded in the JWT.
     *
     * @return list<string>
     */
    public function scopes(): array
    {
        return match ($this->role) {
            self::ROLE_NATIONAL => ['read:all'],
            self::ROLE_PROVINCIAL => ['read:province'],
            self::ROLE_DISTRICT => ['read:district'],
            self::ROLE_ADMINISTRATOR => ['read:all', 'admin'],
            default => [],
        };
    }
}
