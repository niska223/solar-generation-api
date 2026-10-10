<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GridSubstation extends Model
{
    protected $fillable = [
        'district_id',
        'name',
        'code',
        'location',
    ];

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function solarInstallations(): HasMany
    {
        return $this->hasMany(SolarInstallation::class);
    }
}
