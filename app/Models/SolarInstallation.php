<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SolarInstallation extends Model
{
    protected $fillable = [
        'grid_substation_id',
        'meter_id',
        'capacity_kw',
        'address',
        'installed_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'capacity_kw' => 'decimal:2',
            'installed_at' => 'date',
        ];
    }

    public function gridSubstation(): BelongsTo
    {
        return $this->belongsTo(GridSubstation::class);
    }

    public function generationReadings(): HasMany
    {
        return $this->hasMany(GenerationReading::class);
    }

    public function latestReading(): HasOne
    {
        return $this->hasOne(GenerationReading::class)->latestOfMany('recorded_at');
    }
}
