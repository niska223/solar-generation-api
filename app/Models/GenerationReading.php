<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GenerationReading extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'solar_installation_id',
        'recorded_at',
        'power_kw',
        'energy_kwh',
        'voltage',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'power_kw' => 'decimal:3',
            'energy_kwh' => 'decimal:3',
            'voltage' => 'decimal:2',
        ];
    }

    public function solarInstallation(): BelongsTo
    {
        return $this->belongsTo(SolarInstallation::class);
    }
}
